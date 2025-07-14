<?php
/**
 * Single Brand Template - Страница за конкретна марка
 * 
 * Файл: templates/single-marki.php
 * Използва се за показване на информация за конкретна марка
 * ОБНОВЕНА ВЕРСИЯ - модерен бизнес дизайн без анимации
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header(); 

$current_term = get_queried_object();
$brand_image_id = get_term_meta($current_term->term_id, 'marki-image-id', true);
?>

<div class="single-brand-page brand-page">
    <!-- Breadcrumb navigation -->
    <div class="container">
        <nav class="breadcrumb">
            <a href="<?php echo home_url(); ?>"><?php _e('Начало', 'parfume-reviews'); ?></a>
            <span class="separator"> › </span>
            <a href="<?php echo home_url('/parfiumi/'); ?>"><?php _e('Парфюми', 'parfume-reviews'); ?></a>
            <span class="separator"> › </span>
            <a href="<?php echo home_url('/parfiumi/marki/'); ?>"><?php _e('Марки', 'parfume-reviews'); ?></a>
            <span class="separator"> › </span>
            <span class="current"><?php echo esc_html($current_term->name); ?></span>
        </nav>
    </div>

    <!-- Brand Hero Section -->
    <div class="brand-hero">
        <div class="container">
            <div class="brand-hero-content">
                <?php if ($brand_image_id): ?>
                    <div class="brand-logo-hero">
                        <?php echo wp_get_attachment_image($brand_image_id, 'large', false, array('alt' => $current_term->name)); ?>
                    </div>
                <?php endif; ?>
                
                <div class="brand-hero-text">
                    <h1 class="brand-title"><?php echo esc_html($current_term->name); ?></h1>
                    
                    <div class="brand-quick-stats">
                        <div class="quick-stat">
                            <span class="stat-number"><?php echo $current_term->count; ?></span>
                            <span class="stat-label">
                                <?php printf(_n('парфюм', 'парфюма', $current_term->count, 'parfume-reviews'), $current_term->count); ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Brand Content -->
    <div class="brand-content">
        <div class="container">
            
            <!-- Описание на марката -->
            <?php if (!empty($current_term->description)): ?>
                <div class="brand-description-section">
                    <h2><?php printf(__('За %s', 'parfume-reviews'), esc_html($current_term->name)); ?></h2>
                    <div class="brand-full-description">
                        <?php echo wpautop($current_term->description); ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Парфюми от тази марка -->
            <div class="brand-perfumes-section">
                <h2><?php printf(__('Парфюми от %s', 'parfume-reviews'), esc_html($current_term->name)); ?></h2>
                
                <?php
                // Query за парфюми от тази марка
                $brand_perfumes = new WP_Query(array(
                    'post_type' => 'parfume',
                    'posts_per_page' => 12,
                    'meta_query' => array(),
                    'tax_query' => array(
                        array(
                            'taxonomy' => 'marki',
                            'field'    => 'term_id',
                            'terms'    => $current_term->term_id,
                        ),
                    ),
                ));

                if ($brand_perfumes->have_posts()): ?>
                    <div class="brand-perfumes-grid">
                        <?php while ($brand_perfumes->have_posts()): $brand_perfumes->the_post(); ?>
                            <article class="parfume-card">
                                <div class="parfume-card-inner">
                                    
                                    <!-- Изображение на парфюма -->
                                    <div class="parfume-image">
                                        <?php if (has_post_thumbnail()): ?>
                                            <a href="<?php the_permalink(); ?>">
                                                <?php the_post_thumbnail('medium', array('alt' => get_the_title())); ?>
                                            </a>
                                        <?php else: ?>
                                            <a href="<?php the_permalink(); ?>" class="parfume-placeholder">
                                                <span class="placeholder-icon">🌸</span>
                                            </a>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Информация за парфюма -->
                                    <div class="parfume-info">
                                        <h3 class="parfume-title">
                                            <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                                        </h3>
                                        
                                        <!-- Пол -->
                                        <?php
                                        $gender_terms = get_the_terms(get_the_ID(), 'gender');
                                        if ($gender_terms && !is_wp_error($gender_terms)):
                                        ?>
                                            <div class="parfume-gender">
                                                <?php foreach ($gender_terms as $gender): ?>
                                                    <span class="gender-tag"><?php echo esc_html($gender->name); ?></span>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>

                                        <!-- Цена и наличност -->
                                        <?php
                                        $price = get_post_meta(get_the_ID(), '_parfume_price', true);
                                        $availability = get_post_meta(get_the_ID(), '_parfume_availability', true);
                                        ?>
                                        
                                        <?php if (!empty($price)): ?>
                                            <div class="parfume-price">
                                                <span class="price-amount"><?php echo esc_html($price); ?> лв.</span>
                                            </div>
                                        <?php endif; ?>

                                        <?php if (!empty($availability)): ?>
                                            <div class="parfume-availability">
                                                <span class="availability-status <?php echo esc_attr(strtolower($availability)); ?>">
                                                    <?php echo esc_html($availability); ?>
                                                </span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </article>

                        <?php endwhile; ?>
                    </div>
                    
                    <!-- Други марки секция - под парфюмите -->
                    <div class="other-brands-section">
                        <h3><?php _e('Други популярни марки:', 'parfume-reviews'); ?></h3>
                        
                        <?php
                        // Query за други популярни марки
                        $other_brands = get_terms(array(
                            'taxonomy' => 'marki',
                            'hide_empty' => true,
                            'number' => 12,
                            'exclude' => array($current_term->term_id),
                            'orderby' => 'count',
                            'order' => 'DESC'
                        ));
                        
                        if (!empty($other_brands) && !is_wp_error($other_brands)): ?>
                            <div class="other-brands-grid">
                                <?php foreach ($other_brands as $brand): ?>
                                    <div class="other-brand-item">
                                        <?php
                                        $brand_logo_id = get_term_meta($brand->term_id, 'marki-image-id', true);
                                        if ($brand_logo_id):
                                        ?>
                                            <div class="other-brand-logo">
                                                <a href="<?php echo get_term_link($brand); ?>">
                                                    <?php echo wp_get_attachment_image($brand_logo_id, 'thumbnail', false, array('alt' => $brand->name)); ?>
                                                </a>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <div class="other-brand-info">
                                            <h4 class="other-brand-name">
                                                <a href="<?php echo get_term_link($brand); ?>">
                                                    <?php echo esc_html($brand->name); ?>
                                                </a>
                                            </h4>
                                            <span class="other-brand-count">
                                                <?php printf(_n('%d парфюм', '%d парфюма', $brand->count, 'parfume-reviews'), $brand->count); ?>
                                            </span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <?php wp_reset_postdata(); ?>

                <?php else: ?>
                    <div class="no-perfumes-message">
                        <p><?php printf(__('Все още няма парфюми от %s в нашата база данни.', 'parfume-reviews'), esc_html($current_term->name)); ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
/* Модерен бизнес дизайн без анимации */
.single-brand-page {
    background: #f8f9fa;
    min-height: 80vh;
}

.container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
}

/* Breadcrumb */
.breadcrumb {
    padding: 20px 0;
    font-size: 0.95rem;
}

.breadcrumb a {
    color: #0073aa;
    text-decoration: none;
}

.breadcrumb a:hover {
    color: #005a87;
    text-decoration: underline;
}

.breadcrumb .separator {
    color: #999;
    margin: 0 10px;
}

.breadcrumb .current {
    color: #666;
    font-weight: 600;
}

/* Brand Hero */
.brand-hero {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 60px 0;
}

.brand-hero-content {
    display: flex;
    align-items: center;
    gap: 40px;
}

.brand-logo-hero {
    flex-shrink: 0;
}

.brand-logo-hero img {
    max-width: 120px;
    max-height: 120px;
    background: white;
    padding: 20px;
    border-radius: 12px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.2);
}

.brand-hero-text {
    flex: 1;
}

.brand-title {
    font-size: 3rem;
    margin: 0 0 20px;
    font-weight: 700;
}

.brand-quick-stats {
    display: flex;
    gap: 30px;
}

.quick-stat {
    text-align: center;
}

.stat-number {
    display: block;
    font-size: 2.5rem;
    font-weight: 700;
    line-height: 1;
    margin-bottom: 5px;
}

.stat-label {
    display: block;
    font-size: 1rem;
    opacity: 0.9;
    font-weight: 500;
}

/* Brand Content */
.brand-content {
    padding: 60px 0;
}

.brand-description-section {
    background: white;
    border-radius: 12px;
    padding: 40px;
    margin-bottom: 40px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
}

.brand-description-section h2 {
    font-size: 2rem;
    margin-bottom: 25px;
    color: #333;
    text-align: center;
}

.brand-full-description {
    font-size: 1.1rem;
    line-height: 1.7;
    color: #555;
    max-width: 800px;
    margin: 0 auto;
    text-align: justify;
}

/* Perfumes Section */
.brand-perfumes-section {
    background: white;
    border-radius: 12px;
    padding: 40px;
    margin-bottom: 40px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
}

.brand-perfumes-section h2 {
    font-size: 2rem;
    margin-bottom: 30px;
    color: #333;
    text-align: center;
}

.brand-perfumes-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 25px;
}

.parfume-card {
    background: #f8f9fa;
    border-radius: 12px;
    overflow: hidden;
    border: 1px solid #eee;
}

.parfume-card:hover {
    box-shadow: 0 8px 25px rgba(0,0,0,0.1);
}

.parfume-card-inner {
    padding: 20px;
}

.parfume-image {
    text-align: center;
    margin-bottom: 15px;
}

.parfume-image img {
    max-width: 100px;
    max-height: 120px;
    object-fit: contain;
}

.parfume-placeholder {
    display: inline-block;
    width: 100px;
    height: 120px;
    background: #e9ecef;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;
}

.placeholder-icon {
    font-size: 2rem;
}

.parfume-title {
    margin: 0 0 10px;
    font-size: 1.1rem;
    font-weight: 600;
}

.parfume-title a {
    color: #333;
    text-decoration: none;
}

.parfume-title a:hover {
    color: #0073aa;
}

.parfume-gender {
    margin-bottom: 10px;
}

.gender-tag {
    background: #e3f2fd;
    color: #1976d2;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 0.85rem;
    font-weight: 500;
}

.parfume-price {
    margin-bottom: 8px;
}

.price-amount {
    font-size: 1.1rem;
    font-weight: 600;
    color: #2e7d32;
}

.parfume-availability {
    font-size: 0.9rem;
}

.availability-status {
    padding: 3px 8px;
    border-radius: 4px;
    font-weight: 500;
}

.availability-status.в_наличност {
    background: #e8f5e8;
    color: #2e7d32;
}

.availability-status.изчерпан {
    background: #ffebee;
    color: #c62828;
}

/* Other Brands Section */
.other-brands-section {
    background: white;
    border-radius: 12px;
    padding: 40px;
    margin-top: 40px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
}

.other-brands-section h3 {
    font-size: 1.6rem;
    margin-bottom: 25px;
    color: #333;
    text-align: center;
}

.other-brands-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 20px;
}

.other-brand-item {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 8px;
    border: 1px solid #eee;
}

.other-brand-item:hover {
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    border-color: #0073aa;
}

.other-brand-logo {
    flex-shrink: 0;
    width: 50px;
    height: 50px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: white;
    border-radius: 6px;
    overflow: hidden;
}

.other-brand-logo img {
    max-width: 40px;
    max-height: 40px;
    object-fit: contain;
}

.other-brand-info {
    flex: 1;
}

.other-brand-name {
    margin: 0 0 5px;
    font-size: 1rem;
    font-weight: 600;
}

.other-brand-name a {
    color: #333;
    text-decoration: none;
}

.other-brand-name a:hover {
    color: #0073aa;
}

.other-brand-count {
    color: #666;
    font-size: 0.9rem;
}

/* No perfumes message */
.no-perfumes-message {
    text-align: center;
    padding: 40px;
    color: #666;
    font-size: 1.1rem;
}

/* Responsive Design */
@media (max-width: 768px) {
    .brand-hero-content {
        flex-direction: column;
        text-align: center;
        gap: 20px;
    }
    
    .brand-title {
        font-size: 2.2rem;
    }
    
    .brand-perfumes-grid {
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        gap: 20px;
    }
    
    .other-brands-grid {
        grid-template-columns: 1fr;
    }
    
    .brand-description-section,
    .brand-perfumes-section,
    .other-brands-section {
        padding: 25px 20px;
    }
}
</style>

<?php get_footer(); ?>