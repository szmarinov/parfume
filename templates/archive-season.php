<?php
/**
 * Archive template for Season taxonomy
 * Показва архивна страница с всички сезони и техните парфюми
 * 
 * Файл: templates/archive-season.php
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header(); 

// Получаваме всички season термини
$seasons = get_terms(array(
    'taxonomy' => 'season',
    'hide_empty' => false,
    'orderby' => 'name',
    'order' => 'ASC'
));
?>

<div class="parfume-archive season-archive-page">
    <div class="archive-header">
        <h1 class="archive-title"><?php _e('Парфюми по сезони', 'parfume-reviews'); ?></h1>
        <div class="archive-description">
            <p><?php _e('Открийте подходящите парфюми за всеки сезон. Всеки сезон има своя уникална атмосфера и подходящи аромати.', 'parfume-reviews'); ?></p>
        </div>
    </div>

    <div class="archive-content">
        <div class="archive-sidebar">
            <?php 
            // Показваме филтри без season филтъра
            echo do_shortcode('[parfume_filters show_season="false"]'); 
            ?>
        </div>

        <div class="archive-main">
            <?php if (!empty($seasons) && !is_wp_error($seasons)): ?>
                <div class="seasons-grid">
                    <?php foreach ($seasons as $season): 
                        // Получаваме парфюми за този сезон
                        $season_posts = get_posts(array(
                            'post_type' => 'parfume',
                            'posts_per_page' => 6,
                            'tax_query' => array(
                                array(
                                    'taxonomy' => 'season',
                                    'field' => 'term_id',
                                    'terms' => $season->term_id
                                )
                            ),
                            'meta_key' => '_parfume_rating',
                            'orderby' => 'meta_value_num',
                            'order' => 'DESC'
                        ));
                        
                        // Получаваме изображение на сезона ако има
                        $season_image = get_term_meta($season->term_id, 'season_image', true);
                        ?>
                        
                        <div class="season-block season-<?php echo esc_attr($season->slug); ?>">
                            <div class="season-header">
                                <?php if ($season_image): ?>
                                    <div class="season-image">
                                        <img src="<?php echo esc_url($season_image); ?>" alt="<?php echo esc_attr($season->name); ?>">
                                    </div>
                                <?php endif; ?>
                                
                                <div class="season-info">
                                    <h2 class="season-title">
                                        <a href="<?php echo esc_url(get_term_link($season)); ?>">
                                            <?php echo esc_html($season->name); ?>
                                        </a>
                                    </h2>
                                    
                                    <div class="season-meta">
                                        <span class="perfume-count">
                                            <?php printf(_n('%d парфюм', '%d парфюма', $season->count, 'parfume-reviews'), $season->count); ?>
                                        </span>
                                    </div>
                                    
                                    <?php if ($season->description): ?>
                                        <div class="season-description">
                                            <?php echo wpautop(esc_html($season->description)); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <?php if (!empty($season_posts)): ?>
                                <div class="season-parfumes">
                                    <h3 class="section-title"><?php _e('Топ парфюми за сезона', 'parfume-reviews'); ?></h3>
                                    
                                    <div class="parfumes-preview">
                                        <?php foreach ($season_posts as $post): 
                                            setup_postdata($post); ?>
                                            
                                            <div class="parfume-mini-card">
                                                <div class="parfume-thumbnail">
                                                    <a href="<?php the_permalink(); ?>">
                                                        <?php if (has_post_thumbnail()): ?>
                                                            <?php the_post_thumbnail('thumbnail'); ?>
                                                        <?php else: ?>
                                                            <div class="no-image-mini">
                                                                <span>?</span>
                                                            </div>
                                                        <?php endif; ?>
                                                    </a>
                                                    
                                                    <?php 
                                                    $rating = get_post_meta(get_the_ID(), '_parfume_rating', true);
                                                    if ($rating): ?>
                                                        <div class="mini-rating">
                                                            <span class="rating-number"><?php echo esc_html($rating); ?></span>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                                
                                                <div class="parfume-info">
                                                    <h4 class="parfume-title">
                                                        <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                                                    </h4>
                                                    
                                                    <?php 
                                                    $brands = get_the_terms(get_the_ID(), 'marki');
                                                    if ($brands && !is_wp_error($brands)): ?>
                                                        <div class="parfume-brand">
                                                            <?php echo esc_html($brands[0]->name); ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            
                                        <?php endforeach; 
                                        wp_reset_postdata(); ?>
                                    </div>
                                    
                                    <div class="season-actions">
                                        <a href="<?php echo esc_url(get_term_link($season)); ?>" class="view-all-button">
                                            <?php printf(__('Всички %s парфюми', 'parfume-reviews'), esc_html($season->name)); ?>
                                            <span class="arrow">→</span>
                                        </a>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="season-no-parfumes">
                                    <p><?php printf(__('Все още няма добавени парфюми за %s.', 'parfume-reviews'), esc_html($season->name)); ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                    <?php endforeach; ?>
                </div>
                
                <!-- Допълнителна информация за сезоните -->
                <div class="seasons-info-section">
                    <h2><?php _e('Как да избера парфюм според сезона?', 'parfume-reviews'); ?></h2>
                    
                    <div class="seasons-guide">
                        <div class="season-guide-item">
                            <h3><?php _e('Пролет', 'parfume-reviews'); ?></h3>
                            <p><?php _e('Свежи, цветни и леки аромати. Подходящи са цитрусови, зелени и цветни нотки.', 'parfume-reviews'); ?></p>
                        </div>
                        
                        <div class="season-guide-item">
                            <h3><?php _e('Лято', 'parfume-reviews'); ?></h3>
                            <p><?php _e('Лесни, свежи и морски аромати. Избягвайте твърде тежки и сладки парфюми.', 'parfume-reviews'); ?></p>
                        </div>
                        
                        <div class="season-guide-item">
                            <h3><?php _e('Есен', 'parfume-reviews'); ?></h3>
                            <p><?php _e('Топли, пряни и дървесни аромати. Подходящи са ванилия, канела и ориенталски нотки.', 'parfume-reviews'); ?></p>
                        </div>
                        
                        <div class="season-guide-item">
                            <h3><?php _e('Зима', 'parfume-reviews'); ?></h3>
                            <p><?php _e('Богати, интензивни и затоплящи аромати. Мускус, амбра и тежки цветни нотки.', 'parfume-reviews'); ?></p>
                        </div>
                    </div>
                </div>
                
            <?php else: ?>
                <div class="no-seasons">
                    <h2><?php _e('Няма налични сезони', 'parfume-reviews'); ?></h2>
                    <p><?php _e('Сезоните все още не са настроени.', 'parfume-reviews'); ?></p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
/* Специфични стилове за season archive */
.season-archive-page .seasons-grid {
    display: grid;
    gap: 2rem;
    margin-bottom: 3rem;
}

.season-block {
    border: 1px solid #e1e1e1;
    border-radius: 8px;
    padding: 1.5rem;
    background: #fff;
    transition: box-shadow 0.3s ease;
}

.season-block:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.season-header {
    display: flex;
    gap: 1rem;
    margin-bottom: 1.5rem;
    align-items: flex-start;
}

.season-image img {
    width: 80px;
    height: 80px;
    object-fit: cover;
    border-radius: 50%;
}

.season-title a {
    color: #2c3e50;
    text-decoration: none;
    font-size: 1.5rem;
    font-weight: 600;
}

.season-title a:hover {
    color: #3498db;
}

.season-meta {
    margin: 0.5rem 0;
    color: #7f8c8d;
}

.parfumes-preview {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 1rem;
    margin-bottom: 1rem;
}

.parfume-mini-card {
    text-align: center;
    padding: 0.5rem;
    border: 1px solid #eee;
    border-radius: 4px;
    transition: transform 0.2s ease;
}

.parfume-mini-card:hover {
    transform: translateY(-2px);
}

.parfume-mini-card .parfume-thumbnail {
    position: relative;
    margin-bottom: 0.5rem;
}

.parfume-mini-card img {
    width: 60px;
    height: 60px;
    object-fit: cover;
    border-radius: 4px;
}

.no-image-mini {
    width: 60px;
    height: 60px;
    background: #f8f9fa;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 4px;
    margin: 0 auto;
    font-size: 1.5rem;
    color: #ccc;
}

.mini-rating {
    position: absolute;
    top: -5px;
    right: -5px;
    background: #f39c12;
    color: white;
    font-size: 0.7rem;
    padding: 2px 4px;
    border-radius: 3px;
    font-weight: bold;
}

.parfume-mini-card .parfume-title {
    font-size: 0.8rem;
    margin: 0;
    line-height: 1.2;
}

.parfume-mini-card .parfume-title a {
    color: #2c3e50;
    text-decoration: none;
}

.parfume-brand {
    font-size: 0.7rem;
    color: #7f8c8d;
    margin-top: 0.2rem;
}

.view-all-button {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    background: #3498db;
    color: white;
    text-decoration: none;
    border-radius: 4px;
    font-weight: 500;
    transition: background 0.3s ease;
}

.view-all-button:hover {
    background: #2980b9;
    color: white;
}

.seasons-guide {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
    margin-top: 1rem;
}

.season-guide-item {
    padding: 1rem;
    background: #f8f9fa;
    border-radius: 6px;
    border-left: 4px solid #3498db;
}

.season-guide-item h3 {
    margin: 0 0 0.5rem 0;
    color: #2c3e50;
}

.season-guide-item p {
    margin: 0;
    color: #555;
    font-size: 0.9rem;
    line-height: 1.5;
}

@media (max-width: 768px) {
    .season-header {
        flex-direction: column;
        text-align: center;
    }
    
    .parfumes-preview {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .seasons-guide {
        grid-template-columns: 1fr;
    }
}
</style>

<?php get_footer(); ?>