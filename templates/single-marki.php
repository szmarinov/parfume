<?php
/**
 * Single Brand Template - Страница за конкретна марка
 * 
 * Файл: templates/single-marki.php
 * Използва се за показване на информация за конкретна марка
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
                        
                        <?php
                        // Изчисляваме средния рейтинг за тази марка
                        $brand_perfumes = get_posts(array(
                            'post_type' => 'parfume',
                            'posts_per_page' => -1,
                            'fields' => 'ids',
                            'tax_query' => array(
                                array(
                                    'taxonomy' => 'marki',
                                    'field' => 'term_id',
                                    'terms' => $current_term->term_id,
                                ),
                            ),
                        ));
                        
                        if (!empty($brand_perfumes)) {
                            $total_rating = 0;
                            $rated_count = 0;
                            
                            foreach ($brand_perfumes as $perfume_id) {
                                $rating = get_post_meta($perfume_id, '_parfume_rating', true);
                                if (!empty($rating) && is_numeric($rating)) {
                                    $total_rating += floatval($rating);
                                    $rated_count++;
                                }
                            }
                            
                            if ($rated_count > 0) {
                                $average_rating = $total_rating / $rated_count;
                                ?>
                                <div class="quick-stat">
                                    <span class="stat-number"><?php echo number_format($average_rating, 1); ?></span>
                                    <span class="stat-label"><?php _e('среден рейтинг', 'parfume-reviews'); ?></span>
                                </div>
                                <?php
                            }
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Brand Content -->
    <div class="brand-content">
        <div class="container">
            
            <!-- Brand Description -->
            <?php if ($current_term->description): ?>
                <div class="brand-description-section">
                    <h2><?php printf(__('За %s', 'parfume-reviews'), esc_html($current_term->name)); ?></h2>
                    <div class="brand-full-description">
                        <?php echo wpautop(wp_kses_post($current_term->description)); ?>
                    </div>
                </div>
            <?php endif; ?>
           

            <!-- Brand Details -->
            <div class="brand-details-section">
                <h2><?php _e('Детайли за марката', 'parfume-reviews'); ?></h2>
                
                <div class="brand-details-grid">
                    <?php
                    // Получаваме допълнителни мета данни за марката
                    $brand_founded = get_term_meta($current_term->term_id, 'marki-founded', true);
                    $brand_country = get_term_meta($current_term->term_id, 'marki-country', true);
                    $brand_website = get_term_meta($current_term->term_id, 'marki-website', true);
                    $brand_founder = get_term_meta($current_term->term_id, 'marki-founder', true);
                    ?>
                    
                    <?php if ($brand_founded): ?>
                        <div class="brand-detail-item">
                            <div class="detail-icon">
                                <span class="dashicons dashicons-calendar-alt"></span>
                            </div>
                            <div class="detail-content">
                                <strong><?php _e('Основана:', 'parfume-reviews'); ?></strong>
                                <span><?php echo esc_html($brand_founded); ?></span>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($brand_country): ?>
                        <div class="brand-detail-item">
                            <div class="detail-icon">
                                <span class="dashicons dashicons-location"></span>
                            </div>
                            <div class="detail-content">
                                <strong><?php _e('Страна:', 'parfume-reviews'); ?></strong>
                                <span><?php echo esc_html($brand_country); ?></span>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($brand_founder): ?>
                        <div class="brand-detail-item">
                            <div class="detail-icon">
                                <span class="dashicons dashicons-admin-users"></span>
                            </div>
                            <div class="detail-content">
                                <strong><?php _e('Основател:', 'parfume-reviews'); ?></strong>
                                <span><?php echo esc_html($brand_founder); ?></span>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($brand_website): ?>
                        <div class="brand-detail-item">
                            <div class="detail-icon">
                                <span class="dashicons dashicons-admin-site"></span>
                            </div>
                            <div class="detail-content">
                                <strong><?php _e('Уебсайт:', 'parfume-reviews'); ?></strong>
                                <a href="<?php echo esc_url($brand_website); ?>" target="_blank" rel="nofollow">
                                    <?php _e('Посетете официалния сайт', 'parfume-reviews'); ?>
                                    <span class="dashicons dashicons-external"></span>
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Brand Perfumes Section -->
            <div class="brand-perfumes-section">
                <div class="section-header">
                    <h2><?php printf(__('Парфюми от %s', 'parfume-reviews'), esc_html($current_term->name)); ?></h2>
                    <a href="<?php echo get_term_link($current_term); ?>" class="view-all-link">
                        <?php _e('Вижте всички', 'parfume-reviews'); ?>
                        <span class="dashicons dashicons-arrow-right-alt2"></span>
                    </a>
                </div>
                
                <?php
                // Query за популярните парфюми от тази марка (ограничени до 8)
                $brand_perfumes_query = new WP_Query(array(
                    'post_type' => 'parfume',
                    'posts_per_page' => 8,
                    'tax_query' => array(
                        array(
                            'taxonomy' => 'marki',
                            'field' => 'term_id',
                            'terms' => $current_term->term_id,
                        ),
                    ),
                    'meta_key' => '_parfume_rating',
                    'orderby' => 'meta_value_num',
                    'order' => 'DESC',
                ));
                ?>
                
                <?php if ($brand_perfumes_query->have_posts()): ?>
                    <div class="brand-perfumes-grid">
                        <?php while ($brand_perfumes_query->have_posts()): $brand_perfumes_query->the_post(); ?>
                            
                            <article class="perfume-card-compact" data-post-id="<?php echo get_the_ID(); ?>">
                                <div class="perfume-card-image">
                                    <a href="<?php the_permalink(); ?>">
                                        <?php if (has_post_thumbnail()): ?>
                                            <?php the_post_thumbnail('medium', array('loading' => 'lazy')); ?>
                                        <?php else: ?>
                                            <div class="perfume-placeholder">
                                                <span class="dashicons dashicons-format-image"></span>
                                            </div>
                                        <?php endif; ?>
                                    </a>
                                    
                                    <?php
                                    $rating = get_post_meta(get_the_ID(), '_parfume_rating', true);
                                    if (!empty($rating)):
                                    ?>
                                        <div class="perfume-card-rating">
                                            <?php parfume_reviews_display_star_rating(floatval($rating)); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="perfume-card-content">
                                    <h3 class="perfume-card-title">
                                        <a href="<?php the_permalink(); ?>">
                                            <?php the_title(); ?>
                                        </a>
                                    </h3>
                                    
                                    <div class="perfume-card-meta">
                                        <?php
                                        // Получаваме цената
                                        $price = get_post_meta(get_the_ID(), '_parfume_price', true);
                                        if (!empty($price)):
                                        ?>
                                            <div class="perfume-card-price">
                                                <?php echo parfume_reviews_get_formatted_price($price); ?>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <?php
                                        // Получаваме gender за display
                                        $gender_terms = wp_get_post_terms(get_the_ID(), 'gender', array('fields' => 'names'));
                                        if (!empty($gender_terms)):
                                        ?>
                                            <div class="perfume-card-gender">
                                                <span class="gender-label"><?php echo esc_html($gender_terms[0]); ?></span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </article>

                        <?php endwhile; ?>
                    </div>
					 <!-- Други марки секция - под описанието -->
					<div class="other-brands-section">
						<h3><?php _e('Други популярни марки от парфюми:', 'parfume-reviews'); ?></h3>
						
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
/* Single Brand Page Styles */
.single-brand-page {
    background: #f8f9fa;
}

.brand-hero {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 80px 0 60px;
    position: relative;
    overflow: hidden;
}

.brand-hero::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="0.5" fill="rgba(255,255,255,0.1)"/><circle cx="75" cy="75" r="0.5" fill="rgba(255,255,255,0.1)"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
    opacity: 0.3;
}

.brand-hero-content {
    display: flex;
    align-items: center;
    gap: 50px;
    position: relative;
    z-index: 2;
}

.brand-logo-hero {
    flex-shrink: 0;
    max-width: 200px;
    background: rgba(255,255,255,0.1);
    padding: 30px;
    border-radius: 20px;
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255,255,255,0.2);
}

.brand-logo-hero img {
    max-width: 100%;
    height: auto;
    filter: brightness(1.1) contrast(1.1);
}

.brand-hero-text {
    flex: 1;
}

.brand-title {
    font-size: 3.5rem;
    margin-bottom: 30px;
    font-weight: 700;
    text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
}

.brand-quick-stats {
    display: flex;
    gap: 40px;
    flex-wrap: wrap;
}

.quick-stat {
    text-align: center;
    background: rgba(255,255,255,0.15);
    padding: 20px 30px;
    border-radius: 15px;
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255,255,255,0.2);
}

.quick-stat .stat-number {
    display: block;
    font-size: 2.5rem;
    font-weight: bold;
    line-height: 1;
    margin-bottom: 5px;
}

.quick-stat .stat-label {
    display: block;
    font-size: 1rem;
    opacity: 0.9;
    font-weight: 500;
}

.brand-content {
    padding: 60px 0;
}

.brand-description-section {
    background: white;
    border-radius: 20px;
    padding: 50px;
    margin-bottom: 50px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
}

.brand-description-section h2 {
    font-size: 2.2rem;
    margin-bottom: 30px;
    color: #333;
    text-align: center;
}

.brand-full-description {
    font-size: 1.2rem;
    line-height: 1.8;
    color: #555;
    max-width: 900px;
    margin: 0 auto;
    text-align: justify;
}

.other-brands-section {
    background: white;
    border-radius: 20px;
    padding: 50px;
    margin-bottom: 50px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
}

.other-brands-section h3 {
    font-size: 1.8rem;
    margin-bottom: 30px;
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
    border-radius: 12px;
    transition: all 0.3s ease;
    border: 1px solid #eee;
}

.other-brand-item:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.1);
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
    border-radius: 8px;
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
    transition: color 0.3s ease;
}

.other-brand-name a:hover {
    color: #0073aa;
}

.other-brand-count {
    color: #666;
    font-size: 0.9rem;
}

.brand-details-section {
    background: white;
    border-radius: 20px;
    padding: 50px;
    margin-bottom: 50px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
}

.brand-details-section h2 {
    font-size: 2.2rem;
    margin-bottom: 40px;
    color: #333;
    text-align: center;
}

.brand-details-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 30px;
}

.brand-detail-item {
    display: flex;
    align-items: center;
    gap: 20px;
    padding: 25px;
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border-radius: 15px;
    border-left: 5px solid #0073aa;
}

.detail-icon {
    flex-shrink: 0;
    width: 50px;
    height: 50px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #0073aa;
    color: white;
    border-radius: 50%;
}

.detail-icon .dashicons {
    font-size: 24px;
}

.detail-content {
    flex: 1;
}

.detail-content strong {
    display: block;
    font-weight: 600;
    color: #333;
    margin-bottom: 5px;
}

.detail-content span,
.detail-content a {
    color: #666;
    font-size: 1.1rem;
}

.detail-content a {
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 5px;
    transition: color 0.3s ease;
}

.detail-content a:hover {
    color: #0073aa;
}

.brand-perfumes-section {
    background: white;
    border-radius: 20px;
    padding: 50px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 40px;
    flex-wrap: wrap;
    gap: 20px;
}

.section-header h2 {
    font-size: 2.2rem;
    color: #333;
    margin: 0;
}

.view-all-link {
    display: flex;
    align-items: center;
    gap: 8px;
    color: #0073aa;
    text-decoration: none;
    font-weight: 600;
    font-size: 1.1rem;
    transition: all 0.3s ease;
}

.view-all-link:hover {
    color: #005a87;
    transform: translateX(5px);
}

.brand-perfumes-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 30px;
}

.perfume-card-compact {
    background: #f8f9fa;
    border: 1px solid #eee;
    border-radius: 15px;
    overflow: hidden;
    transition: all 0.3s ease;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.perfume-card-compact:hover {
    transform: translateY(-8px);
    box-shadow: 0 12px 35px rgba(0,0,0,0.15);
    border-color: #0073aa;
}

.perfume-card-image {
    position: relative;
    padding-top: 100%;
    overflow: hidden;
    background: white;
}

.perfume-card-image img,
.perfume-placeholder {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.perfume-placeholder {
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f8f9fa;
    color: #ccc;
}

.perfume-placeholder .dashicons {
    font-size: 3rem;
}

.perfume-card-rating {
    position: absolute;
    top: 10px;
    right: 10px;
    background: rgba(255,255,255,0.95);
    padding: 5px 10px;
    border-radius: 20px;
    backdrop-filter: blur(5px);
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.perfume-card-content {
    padding: 20px;
}

.perfume-card-title {
    margin: 0 0 15px;
    font-size: 1.1rem;
    line-height: 1.4;
    font-weight: 600;
}

.perfume-card-title a {
    color: #333;
    text-decoration: none;
    transition: color 0.3s ease;
}

.perfume-card-title a:hover {
    color: #0073aa;
}

.perfume-card-meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.perfume-card-price {
    font-weight: bold;
    color: #e74c3c;
    font-size: 1.1rem;
}

.perfume-card-gender .gender-label {
    background: #e9ecef;
    color: #495057;
    padding: 4px 10px;
    border-radius: 15px;
    font-size: 0.85rem;
    font-weight: 500;
}

.breadcrumb {
    margin-bottom: 0;
    padding: 20px 0;
    font-size: 0.95rem;
}

.breadcrumb a {
    color: #0073aa;
    text-decoration: none;
    transition: color 0.3s ease;
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

.no-perfumes-message {
    text-align: center;
    padding: 40px 20px;
    color: #666;
    font-style: italic;
}

/* Responsive Design */
@media (max-width: 1024px) {
    .brand-hero-content {
        flex-direction: column;
        text-align: center;
        gap: 30px;
    }
    
    .brand-title {
        font-size: 2.8rem;
    }
    
    .other-brands-grid {
        grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
        gap: 15px;
    }
    
    .brand-details-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 768px) {
    .brand-hero {
        padding: 50px 0 40px;
    }
    
    .brand-title {
        font-size: 2.2rem;
    }
    
    .quick-stat {
        padding: 15px 20px;
    }
    
    .quick-stat .stat-number {
        font-size: 2rem;
    }
    
    .brand-description-section,
    .other-brands-section,
    .brand-details-section,
    .brand-perfumes-section {
        padding: 30px 20px;
        margin-bottom: 30px;
    }
    
    .other-brands-grid {
        grid-template-columns: 1fr;
    }
    
    .other-brand-item {
        padding: 12px;
    }
    
    .brand-perfumes-grid {
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 20px;
    }
    
    .section-header {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .section-header h2 {
        font-size: 1.8rem;
    }
}

@media (max-width: 480px) {
    .brand-title {
        font-size: 1.8rem;
    }
    
    .quick-stat .stat-number {
        font-size: 1.6rem;
    }
    
    .brand-perfumes-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<?php get_footer(); ?>