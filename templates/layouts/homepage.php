<?php
/**
 * Homepage Layout Module for Parfume Reviews
 * 
 * Complete homepage layout with all sections
 * Can be used in page templates or shortcodes
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get settings
$settings = get_option('parfume_reviews_settings', array());
$grid_settings = parfume_reviews_get_grid_settings('homepage');
$card_settings = parfume_reviews_get_card_settings();

// Override some card settings for homepage
$homepage_card_settings = array_merge($card_settings, array(
    'show_notes' => false,
    'show_excerpt' => false,
));
?>

<div class="parfume-homepage">
    
    <!-- Hero Section -->
    <section class="parfume-homepage__hero">
        <div class="hero-container">
            <div class="hero-content">
                <h1 class="hero-title"><?php _e('Discover Your Perfect Fragrance', 'parfume-reviews'); ?></h1>
                <p class="hero-subtitle"><?php _e('Explore our comprehensive collection of perfume reviews and find your signature scent', 'parfume-reviews'); ?></p>
                
                <div class="hero-search">
                    <form role="search" method="get" class="search-form" action="<?php echo esc_url(home_url('/')); ?>">
                        <div class="search-input-group">
                            <input type="search" 
                                   class="search-field" 
                                   placeholder="<?php _e('Search for perfumes, brands, notes...', 'parfume-reviews'); ?>" 
                                   value="<?php echo get_search_query(); ?>" 
                                   name="s" 
                                   required>
                            <input type="hidden" name="post_type" value="parfume">
                            <button type="submit" class="search-submit">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="11" cy="11" r="8"/>
                                    <path d="m21 21-4.35-4.35"/>
                                </svg>
                                <span class="sr-only"><?php _e('Search', 'parfume-reviews'); ?></span>
                            </button>
                        </div>
                    </form>
                </div>
                
                <div class="hero-stats">
                    <?php
                    $total_perfumes = wp_count_posts('parfume');
                    $total_brands = wp_count_terms('marki');
                    $total_notes = wp_count_terms('notes');
                    ?>
                    <div class="stat-item">
                        <span class="stat-number"><?php echo number_format($total_perfumes->publish); ?></span>
                        <span class="stat-label"><?php _e('Perfumes', 'parfume-reviews'); ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number"><?php echo number_format($total_brands); ?></span>
                        <span class="stat-label"><?php _e('Brands', 'parfume-reviews'); ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number"><?php echo number_format($total_notes); ?></span>
                        <span class="stat-label"><?php _e('Notes', 'parfume-reviews'); ?></span>
                    </div>
                </div>
            </div>
            
            <div class="hero-image">
                <div class="hero-perfume-bottles">
                    <!-- SVG illustration or image can go here -->
                    <svg viewBox="0 0 400 300" class="bottles-illustration">
                        <!-- Simplified perfume bottles illustration -->
                        <defs>
                            <linearGradient id="bottle1" x1="0%" y1="0%" x2="100%" y2="100%">
                                <stop offset="0%" style="stop-color:#667eea;stop-opacity:1" />
                                <stop offset="100%" style="stop-color:#764ba2;stop-opacity:1" />
                            </linearGradient>
                            <linearGradient id="bottle2" x1="0%" y1="0%" x2="100%" y2="100%">
                                <stop offset="0%" style="stop-color:#f093fb;stop-opacity:1" />
                                <stop offset="100%" style="stop-color:#f5576c;stop-opacity:1" />
                            </linearGradient>
                        </defs>
                        
                        <!-- Bottle 1 -->
                        <rect x="80" y="120" width="60" height="120" rx="8" fill="url(#bottle1)" opacity="0.9"/>
                        <rect x="95" y="100" width="30" height="25" rx="4" fill="url(#bottle1)"/>
                        <circle cx="110" cy="110" r="6" fill="#fff" opacity="0.3"/>
                        
                        <!-- Bottle 2 -->
                        <rect x="180" y="100" width="50" height="140" rx="6" fill="url(#bottle2)" opacity="0.9"/>
                        <rect x="192" y="85" width="26" height="20" rx="3" fill="url(#bottle2)"/>
                        <circle cx="205" cy="130" r="8" fill="#fff" opacity="0.3"/>
                        
                        <!-- Bottle 3 -->
                        <rect x="260" y="130" width="55" height="110" rx="7" fill="#ffd89b" opacity="0.9"/>
                        <rect x="274" y="115" width="27" height="20" rx="3" fill="#ffd89b"/>
                        <circle cx="287" cy="140" r="5" fill="#fff" opacity="0.3"/>
                        
                        <!-- Floating elements -->
                        <circle cx="120" cy="60" r="3" fill="#667eea" opacity="0.6">
                            <animate attributeName="cy" values="60;50;60" dur="3s" repeatCount="indefinite"/>
                        </circle>
                        <circle cx="280" cy="70" r="2" fill="#f5576c" opacity="0.6">
                            <animate attributeName="cy" values="70;65;70" dur="2s" repeatCount="indefinite"/>
                        </circle>
                        <circle cx="160" cy="50" r="2.5" fill="#ffd89b" opacity="0.6">
                            <animate attributeName="cy" values="50;45;50" dur="2.5s" repeatCount="indefinite"/>
                        </circle>
                    </svg>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Featured Categories -->
    <section class="parfume-homepage__categories">
        <div class="section-container">
            <h2 class="section-title"><?php _e('Shop by Category', 'parfume-reviews'); ?></h2>
            
            <div class="categories-grid">
                <?php
                $featured_categories = array(
                    array(
                        'taxonomy' => 'gender',
                        'slug' => 'muzhki-parfiumi',
                        'name' => __('Men\'s Fragrances', 'parfume-reviews'),
                        'icon' => '👨',
                        'color' => '#4a90e2'
                    ),
                    array(
                        'taxonomy' => 'gender', 
                        'slug' => 'damski-parfiumi',
                        'name' => __('Women\'s Fragrances', 'parfume-reviews'),
                        'icon' => '👩',
                        'color' => '#e91e63'
                    ),
                    array(
                        'taxonomy' => 'gender',
                        'slug' => 'arabski-parfiumi', 
                        'name' => __('Arabic Perfumes', 'parfume-reviews'),
                        'icon' => '🏺',
                        'color' => '#ff9800'
                    ),
                    array(
                        'taxonomy' => 'gender',
                        'slug' => 'luksozni-parfiumi',
                        'name' => __('Luxury Perfumes', 'parfume-reviews'),
                        'icon' => '💎',
                        'color' => '#9c27b0'
                    ),
                    array(
                        'taxonomy' => 'gender',
                        'slug' => 'nishovi-parfiumi',
                        'name' => __('Niche Perfumes', 'parfume-reviews'),
                        'icon' => '🌟',
                        'color' => '#607d8b'
                    ),
                );
                
                foreach ($featured_categories as $category):
                    $term = get_term_by('slug', $category['slug'], $category['taxonomy']);
                    if ($term):
                ?>
                    <a href="<?php echo get_term_link($term); ?>" class="category-card" style="--category-color: <?php echo esc_attr($category['color']); ?>">
                        <div class="category-icon"><?php echo $category['icon']; ?></div>
                        <h3 class="category-name"><?php echo esc_html($category['name']); ?></h3>
                        <span class="category-count"><?php printf(_n('%d perfume', '%d perfumes', $term->count, 'parfume-reviews'), $term->count); ?></span>
                    </a>
                <?php 
                    endif;
                endforeach; 
                ?>
            </div>
        </div>
    </section>
    
    <!-- Latest Reviews -->
    <?php if ($grid_settings['latest_count'] > 0): ?>
        <section class="parfume-homepage__latest">
            <div class="section-container">
                <div class="section-header">
                    <h2 class="section-title"><?php _e('Latest Reviews', 'parfume-reviews'); ?></h2>
                    <a href="<?php echo get_post_type_archive_link('parfume'); ?>" class="section-link">
                        <?php _e('View All', 'parfume-reviews'); ?>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M5 12h14M12 5l7 7-7 7"/>
                        </svg>
                    </a>
                </div>
                
                <?php
                $latest_posts = get_posts(array(
                    'post_type' => 'parfume',
                    'posts_per_page' => $grid_settings['latest_count'],
                    'orderby' => 'date',
                    'order' => 'DESC',
                    'post_status' => 'publish',
                ));
                
                if (!empty($latest_posts)):
                    parfume_reviews_render_grid(
                        $latest_posts,
                        'grid',
                        array(
                            'columns' => 4,
                            'columns_tablet' => 3,
                            'columns_mobile' => 2,
                            'show_pagination' => false,
                            'show_count' => false,
                            'show_sorting' => false,
                        ),
                        $homepage_card_settings
                    );
                endif;
                ?>
            </div>
        </section>
    <?php endif; ?>
    
    <!-- Featured Brands -->
    <?php
    $featured_brands = isset($settings['homepage_featured_brands']) ? $settings['homepage_featured_brands'] : array();
    if (!empty($featured_brands)):
    ?>
        <section class="parfume-homepage__brands">
            <div class="section-container">
                <h2 class="section-title"><?php _e('Featured Brands', 'parfume-reviews'); ?></h2>
                
                <div class="brands-grid">
                    <?php 
                    foreach ($featured_brands as $brand_id):
                        $brand = get_term($brand_id, 'marki');
                        if ($brand && !is_wp_error($brand)):
                            $brand_image_id = get_term_meta($brand->term_id, 'marki-image-id', true);
                    ?>
                        <a href="<?php echo get_term_link($brand); ?>" class="brand-card">
                            <?php if ($brand_image_id): ?>
                                <div class="brand-logo">
                                    <?php echo wp_get_attachment_image($brand_image_id, 'thumbnail', false, array('alt' => $brand->name)); ?>
                                </div>
                            <?php else: ?>
                                <div class="brand-logo brand-logo-placeholder">
                                    <span class="brand-initial"><?php echo esc_html(substr($brand->name, 0, 1)); ?></span>
                                </div>
                            <?php endif; ?>
                            
                            <div class="brand-info">
                                <h3 class="brand-name"><?php echo esc_html($brand->name); ?></h3>
                                <span class="brand-count"><?php printf(_n('%d perfume', '%d perfumes', $brand->count, 'parfume-reviews'), $brand->count); ?></span>
                            </div>
                        </a>
                    <?php 
                        endif;
                    endforeach; 
                    ?>
                </div>
                
                <div class="brands-cta">
                    <a href="<?php echo home_url('/parfiumi/marki/'); ?>" class="cta-button">
                        <?php _e('View All Brands', 'parfume-reviews'); ?>
                    </a>
                </div>
            </div>
        </section>
    <?php endif; ?>
    
    <!-- Men's Best Perfumes -->
    <?php
    $men_perfumes = isset($settings['homepage_men_perfumes']) ? $settings['homepage_men_perfumes'] : array();
    if (!empty($men_perfumes)):
    ?>
        <section class="parfume-homepage__men">
            <div class="section-container">
                <div class="section-header">
                    <h2 class="section-title"><?php _e('Best Men\'s Perfumes', 'parfume-reviews'); ?></h2>
                    <a href="<?php echo get_term_link(get_term_by('slug', 'muzhki-parfiumi', 'gender')); ?>" class="section-link">
                        <?php _e('View All', 'parfume-reviews'); ?>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M5 12h14M12 5l7 7-7 7"/>
                        </svg>
                    </a>
                </div>
                
                <?php
                parfume_reviews_render_grid(
                    $men_perfumes,
                    'grid',
                    array(
                        'columns' => 4,
                        'columns_tablet' => 3, 
                        'columns_mobile' => 2,
                        'show_pagination' => false,
                        'show_count' => false,
                        'show_sorting' => false,
                    ),
                    $homepage_card_settings
                );
                ?>
            </div>
        </section>
    <?php endif; ?>
    
    <!-- Women's Popular Perfumes -->
    <?php
    $women_perfumes = isset($settings['homepage_women_perfumes']) ? $settings['homepage_women_perfumes'] : array();
    if (!empty($women_perfumes)):
    ?>
        <section class="parfume-homepage__women">
            <div class="section-container">
                <div class="section-header">
                    <h2 class="section-title"><?php _e('Popular Women\'s Perfumes', 'parfume-reviews'); ?></h2>
                    <a href="<?php echo get_term_link(get_term_by('slug', 'damski-parfiumi', 'gender')); ?>" class="section-link">
                        <?php _e('View All', 'parfume-reviews'); ?>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M5 12h14M12 5l7 7-7 7"/>
                        </svg>
                    </a>
                </div>
                
                <?php
                parfume_reviews_render_grid(
                    $women_perfumes,
                    'grid',
                    array(
                        'columns' => 4,
                        'columns_tablet' => 3,
                        'columns_mobile' => 2,
                        'show_pagination' => false,
                        'show_count' => false,
                        'show_sorting' => false,
                    ),
                    $homepage_card_settings
                );
                ?>
            </div>
        </section>
    <?php endif; ?>
    
    <!-- Blog Articles -->
    <?php if ($grid_settings['blog_count'] > 0): ?>
        <section class="parfume-homepage__blog">
            <div class="section-container">
                <div class="section-header">
                    <h2 class="section-title"><?php _e('Latest Articles', 'parfume-reviews'); ?></h2>
                    <a href="<?php echo get_permalink(get_option('page_for_posts')); ?>" class="section-link">
                        <?php _e('View All Articles', 'parfume-reviews'); ?>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M5 12h14M12 5l7 7-7 7"/>
                        </svg>
                    </a>
                </div>
                
                <div class="blog-grid">
                    <?php
                    $blog_posts = get_posts(array(
                        'post_type' => 'post',
                        'posts_per_page' => $grid_settings['blog_count'],
                        'orderby' => 'date',
                        'order' => 'DESC',
                        'post_status' => 'publish',
                    ));
                    
                    foreach ($blog_posts as $post):
                        setup_postdata($post);
                    ?>
                        <article class="blog-card">
                            <?php if (has_post_thumbnail()): ?>
                                <div class="blog-card__image">
                                    <a href="<?php the_permalink(); ?>">
                                        <?php the_post_thumbnail('medium'); ?>
                                    </a>
                                </div>
                            <?php endif; ?>
                            
                            <div class="blog-card__content">
                                <div class="blog-card__meta">
                                    <time class="blog-card__date"><?php echo get_the_date(); ?></time>
                                    <span class="blog-card__category">
                                        <?php
                                        $categories = get_the_category();
                                        if (!empty($categories)) {
                                            echo esc_html($categories[0]->name);
                                        }
                                        ?>
                                    </span>
                                </div>
                                
                                <h3 class="blog-card__title">
                                    <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                                </h3>
                                
                                <div class="blog-card__excerpt">
                                    <?php echo wp_trim_words(get_the_excerpt(), 20); ?>
                                </div>
                                
                                <a href="<?php the_permalink(); ?>" class="blog-card__link">
                                    <?php _e('Read More', 'parfume-reviews'); ?>
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M5 12h14M12 5l7 7-7 7"/>
                                    </svg>
                                </a>
                            </div>
                        </article>
                    <?php 
                    endforeach; 
                    wp_reset_postdata();
                    ?>
                </div>
            </div>
        </section>
    <?php endif; ?>
    
    <!-- Homepage Description/CTA -->
    <?php if (!empty($settings['homepage_description'])): ?>
        <section class="parfume-homepage__description">
            <div class="section-container">
                <div class="description-content">
                    <?php echo wp_kses_post($settings['homepage_description']); ?>
                </div>
            </div>
        </section>
    <?php endif; ?>
    
</div>

<style>
/* Homepage Base */
.parfume-homepage {
    overflow-x: hidden;
}

.section-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
}

.section-title {
    font-size: 2.5em;
    font-weight: 700;
    color: #1a202c;
    margin-bottom: 40px;
    text-align: center;
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 40px;
}

.section-header .section-title {
    margin-bottom: 0;
    text-align: left;
}

.section-link {
    display: flex;
    align-items: center;
    gap: 8px;
    color: #4a90e2;
    text-decoration: none;
    font-weight: 600;
    transition: all 0.3s ease;
}

.section-link:hover {
    color: #357abd;
    transform: translateX(4px);
}

.section-link svg {
    width: 16px;
    height: 16px;
}

/* Hero Section */
.parfume-homepage__hero {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 80px 0 120px;
    position: relative;
    overflow: hidden;
}

.parfume-homepage__hero::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="white" opacity="0.1"/><circle cx="75" cy="75" r="1" fill="white" opacity="0.1"/><circle cx="50" cy="10" r="0.5" fill="white" opacity="0.15"/><circle cx="90" cy="40" r="0.5" fill="white" opacity="0.15"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
    opacity: 0.3;
}

.hero-container {
    display: flex;
    align-items: center;
    gap: 60px;
    position: relative;
    z-index: 1;
}

.hero-content {
    flex: 1;
    max-width: 600px;
}

.hero-title {
    font-size: 3.5em;
    font-weight: 800;
    line-height: 1.1;
    margin-bottom: 20px;
    text-shadow: 0 4px 8px rgba(0,0,0,0.2);
}

.hero-subtitle {
    font-size: 1.3em;
    line-height: 1.6;
    margin-bottom: 40px;
    opacity: 0.9;
}

.hero-search {
    margin-bottom: 50px;
}

.search-input-group {
    display: flex;
    background: white;
    border-radius: 50px;
    padding: 4px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.1);
    max-width: 500px;
}

.search-field {
    flex: 1;
    border: none;
    padding: 18px 24px;
    font-size: 16px;
    border-radius: 50px;
    background: transparent;
    color: #333;
}

.search-field:focus {
    outline: none;
}

.search-field::placeholder {
    color: #9ca3af;
}

.search-submit {
    background: #4a90e2;
    border: none;
    border-radius: 50px;
    padding: 18px 24px;
    color: white;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
}

.search-submit:hover {
    background: #357abd;
    transform: scale(1.05);
}

.search-submit svg {
    width: 20px;
    height: 20px;
}

.hero-stats {
    display: flex;
    gap: 40px;
}

.stat-item {
    text-align: center;
}

.stat-number {
    display: block;
    font-size: 2.5em;
    font-weight: 800;
    margin-bottom: 8px;
    text-shadow: 0 2px 4px rgba(0,0,0,0.2);
}

.stat-label {
    font-size: 1em;
    opacity: 0.9;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.hero-image {
    flex: 0 0 400px;
}

.bottles-illustration {
    width: 100%;
    height: auto;
    filter: drop-shadow(0 10px 30px rgba(0,0,0,0.2));
}

/* Categories Section */
.parfume-homepage__categories {
    padding: 80px 0;
    background: #f8fafc;
}

.categories-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 30px;
    max-width: 1000px;
    margin: 0 auto;
}

.category-card {
    background: white;
    padding: 40px 30px;
    border-radius: 20px;
    text-align: center;
    text-decoration: none;
    color: inherit;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    border: 2px solid transparent;
    position: relative;
    overflow: hidden;
}

.category-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: var(--category-color);
    transform: scaleX(0);
    transition: transform 0.3s ease;
}

.category-card:hover::before {
    transform: scaleX(1);
}

.category-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 20px 40px rgba(0,0,0,0.1);
    border-color: var(--category-color);
}

.category-icon {
    font-size: 3em;
    margin-bottom: 20px;
    display: block;
}

.category-name {
    font-size: 1.3em;
    font-weight: 600;
    margin: 0 0 12px;
    color: #1a202c;
}

.category-count {
    color: #6b7280;
    font-size: 0.95em;
}

/* Latest/Featured Sections */
.parfume-homepage__latest,
.parfume-homepage__men,
.parfume-homepage__women {
    padding: 80px 0;
}

.parfume-homepage__men {
    background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
}

.parfume-homepage__women {
    background: linear-gradient(135deg, #fce4ec 0%, #f8bbd9 100%);
}

/* Brands Section */
.parfume-homepage__brands {
    padding: 80px 0;
    background: #1a202c;
    color: white;
}

.parfume-homepage__brands .section-title {
    color: white;
}

.brands-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 30px;
    margin-bottom: 50px;
}

.brand-card {
    background: #2d3748;
    padding: 30px;
    border-radius: 15px;
    text-align: center;
    text-decoration: none;
    color: white;
    transition: all 0.3s ease;
    border: 1px solid #4a5568;
}

.brand-card:hover {
    background: #4a5568;
    transform: translateY(-4px);
    box-shadow: 0 15px 35px rgba(0,0,0,0.3);
}

.brand-logo {
    width: 80px;
    height: 80px;
    margin: 0 auto 20px;
    border-radius: 12px;
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
}

.brand-logo img {
    max-width: 100%;
    max-height: 100%;
    object-fit: contain;
}

.brand-logo-placeholder {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    font-size: 2em;
    font-weight: bold;
}

.brand-name {
    font-size: 1.2em;
    font-weight: 600;
    margin: 0 0 8px;
}

.brand-count {
    color: #a0aec0;
    font-size: 0.9em;
}

.brands-cta {
    text-align: center;
}

.cta-button {
    display: inline-block;
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    padding: 16px 32px;
    border-radius: 50px;
    text-decoration: none;
    font-weight: 600;
    font-size: 1.1em;
    transition: all 0.3s ease;
}

.cta-button:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
}

/* Blog Section */
.parfume-homepage__blog {
    padding: 80px 0;
    background: #f8fafc;
}

.blog-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 30px;
}

.blog-card {
    background: white;
    border-radius: 15px;
    overflow: hidden;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    transition: all 0.3s ease;
}

.blog-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 40px rgba(0,0,0,0.15);
}

.blog-card__image {
    height: 200px;
    overflow: hidden;
}

.blog-card__image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s ease;
}

.blog-card:hover .blog-card__image img {
    transform: scale(1.05);
}

.blog-card__content {
    padding: 25px;
}

.blog-card__meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
    font-size: 0.85em;
    color: #6b7280;
}

.blog-card__title {
    margin: 0 0 15px;
    font-size: 1.2em;
    line-height: 1.4;
}

.blog-card__title a {
    color: #1a202c;
    text-decoration: none;
}

.blog-card__title a:hover {
    color: #4a90e2;
}

.blog-card__excerpt {
    color: #6b7280;
    line-height: 1.6;
    margin-bottom: 20px;
}

.blog-card__link {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    color: #4a90e2;
    text-decoration: none;
    font-weight: 600;
    transition: all 0.3s ease;
}

.blog-card__link:hover {
    color: #357abd;
    transform: translateX(4px);
}

.blog-card__link svg {
    width: 14px;
    height: 14px;
}

/* Description Section */
.parfume-homepage__description {
    padding: 80px 0;
    background: #1a202c;
    color: white;
}

.description-content {
    max-width: 800px;
    margin: 0 auto;
    text-align: center;
    font-size: 1.1em;
    line-height: 1.8;
}

/* Responsive Design */
@media (max-width: 1024px) {
    .hero-container {
        flex-direction: column;
        text-align: center;
        gap: 40px;
    }
    
    .hero-image {
        flex: 0 0 300px;
    }
    
    .hero-title {
        font-size: 2.8em;
    }
    
    .categories-grid {
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 20px;
    }
    
    .hero-stats {
        justify-content: center;
        gap: 30px;
    }
}

@media (max-width: 768px) {
    .parfume-homepage__hero {
        padding: 60px 0 80px;
    }
    
    .hero-title {
        font-size: 2.2em;
    }
    
    .hero-subtitle {
        font-size: 1.1em;
    }
    
    .section-title {
        font-size: 2em;
    }
    
    .section-header {
        flex-direction: column;
        gap: 20px;
        align-items: stretch;
    }
    
    .section-header .section-title {
        text-align: center;
    }
    
    .hero-stats {
        flex-direction: column;
        gap: 20px;
    }
    
    .stat-number {
        font-size: 2em;
    }
    
    .categories-grid {
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    }
    
    .category-card {
        padding: 30px 20px;
    }
    
    .brands-grid,
    .blog-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 480px) {
    .section-container {
        padding: 0 15px;
    }
    
    .parfume-homepage__hero,
    .parfume-homepage__categories,
    .parfume-homepage__latest,
    .parfume-homepage__brands,
    .parfume-homepage__men,
    .parfume-homepage__women,
    .parfume-homepage__blog,
    .parfume-homepage__description {
        padding: 60px 0;
    }
    
    .hero-title {
        font-size: 1.8em;
    }
    
    .search-input-group {
        flex-direction: column;
        border-radius: 15px;
    }
    
    .search-field,
    .search-submit {
        border-radius: 15px;
    }
    
    .categories-grid {
        grid-template-columns: 1fr;
        gap: 15px;
    }
}

/* Dark mode support */
@media (prefers-color-scheme: dark) {
    .category-card {
        background: #2d3748;
        color: white;
    }
    
    .blog-card {
        background: #2d3748;
        color: white;
    }
    
    .blog-card__title a {
        color: white;
    }
}

/* Print styles */
@media print {
    .parfume-homepage__hero,
    .hero-search,
    .section-link,
    .cta-button,
    .blog-card__link {
        display: none;
    }
}

/* Animation delays for staggered effects */
.category-card:nth-child(1) { animation-delay: 0.1s; }
.category-card:nth-child(2) { animation-delay: 0.2s; }
.category-card:nth-child(3) { animation-delay: 0.3s; }
.category-card:nth-child(4) { animation-delay: 0.4s; }
.category-card:nth-child(5) { animation-delay: 0.5s; }

/* Smooth scrolling */
html {
    scroll-behavior: smooth;
}

/* Focus states for accessibility */
.search-field:focus,
.search-submit:focus,
.category-card:focus,
.brand-card:focus,
.section-link:focus,
.cta-button:focus,
.blog-card__link:focus {
    outline: 2px solid #4a90e2;
    outline-offset: 2px;
}
</style>