<?php
/**
 * Single Perfumer Template
 * Алтернативен template за single парфюмерист страници
 * 
 * Файл: templates/single-perfumer.php
 * 
 * ЗАБЕЛЕЖКА: Този файл може да се използва като backup
 * Основният template е taxonomy-perfumer.php
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();

$current_term = get_queried_object();
?>

<div class="single-perfumer-page">
    <div class="perfumer-hero">
        <div class="container">
            <div class="perfumer-header">
                <h1 class="perfumer-name"><?php echo esc_html($current_term->name); ?></h1>
                
                <?php if (!empty($current_term->description)): ?>
                    <div class="perfumer-bio">
                        <?php echo wpautop(wp_kses_post($current_term->description)); ?>
                    </div>
                <?php endif; ?>
                
                <div class="perfumer-stats">
                    <div class="stat-item">
                        <span class="stat-number"><?php echo $current_term->count; ?></span>
                        <span class="stat-label"><?php echo _n('Парфюм', 'Парфюма', $current_term->count, 'parfume-reviews'); ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="perfumer-content">
        <div class="container">
            <div class="perfumer-perfumes-section">
                <h2 class="section-title"><?php printf(__('Парфюми от %s', 'parfume-reviews'), esc_html($current_term->name)); ?></h2>
                
                <?php
                // Query за парфюмите на този парфюмерист  
                $perfumes_query = new WP_Query(array(
                    'post_type' => 'parfume',
                    'posts_per_page' => 16,
                    'paged' => get_query_var('paged'),
                    'tax_query' => array(
                        array(
                            'taxonomy' => 'perfumer',
                            'field' => 'slug', 
                            'terms' => $current_term->slug,
                        ),
                    ),
                    'meta_key' => '_parfume_rating',
                    'orderby' => 'meta_value_num',
                    'order' => 'DESC',
                ));
                ?>
                
                <?php if ($perfumes_query->have_posts()): ?>
                    <div class="perfumes-grid">
                        <?php while ($perfumes_query->have_posts()): $perfumes_query->the_post(); ?>
                            <?php parfume_reviews_display_parfume_card(get_the_ID()); ?>
                        <?php endwhile; ?>
                    </div>
                    
                    <?php
                    // Pagination
                    $pagination_links = paginate_links(array(
                        'total' => $perfumes_query->max_num_pages,
                        'current' => max(1, get_query_var('paged')),
                        'prev_text' => __('‹ Предишна', 'parfume-reviews'),
                        'next_text' => __('Следваща ›', 'parfume-reviews'),
                        'type' => 'array',
                    ));
                    
                    if ($pagination_links): ?>
                        <nav class="perfumes-pagination">
                            <ul class="pagination">
                                <?php foreach ($pagination_links as $link): ?>
                                    <li class="page-item"><?php echo $link; ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </nav>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="no-perfumes">
                        <p><?php _e('Няма намерени парфюми от този парфюмерист.', 'parfume-reviews'); ?></p>
                    </div>
                <?php endif; ?>
                
                <?php wp_reset_postdata(); ?>
            </div>
            
            <!-- Related Perfumers Section -->
            <div class="related-perfumers-section">
                <h2 class="section-title"><?php _e('Други парфюмеристи', 'parfume-reviews'); ?></h2>
                
                <?php
                // Взимаме други парфюмеристи
                $other_perfumers = get_terms(array(
                    'taxonomy' => 'perfumer',
                    'hide_empty' => true,
                    'exclude' => array($current_term->term_id),
                    'number' => 8,
                    'orderby' => 'count',
                    'order' => 'DESC',
                ));
                
                if (!empty($other_perfumers) && !is_wp_error($other_perfumers)): ?>
                    <div class="related-perfumers-grid">
                        <?php foreach ($other_perfumers as $perfumer): ?>
                            <div class="related-perfumer-card">
                                <h3 class="related-perfumer-name">
                                    <a href="<?php echo get_term_link($perfumer); ?>">
                                        <?php echo esc_html($perfumer->name); ?>
                                    </a>
                                </h3>
                                <span class="related-perfumer-count">
                                    <?php printf(_n('%d парфюм', '%d парфюма', $perfumer->count, 'parfume-reviews'), $perfumer->count); ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
/* Single Perfumer Page Styles */
.single-perfumer-page .container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
}

.perfumer-hero {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    padding: 60px 0;
}

.perfumer-header {
    text-align: center;
    max-width: 800px;
    margin: 0 auto;
}

.perfumer-name {
    font-size: 3em;
    font-weight: 700;
    color: #333;
    margin: 0 0 30px;
}

.perfumer-bio {
    font-size: 1.2em;
    line-height: 1.6;
    color: #666;
    margin-bottom: 40px;
}

.perfumer-stats {
    display: flex;
    justify-content: center;
    gap: 40px;
}

.stat-item {
    text-align: center;
}

.stat-number {
    display: block;
    font-size: 2.5em;
    font-weight: 700;
    color: #0073aa;
}

.stat-label {
    display: block;
    font-size: 1em;
    color: #666;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.perfumer-content {
    padding: 80px 0;
}

.section-title {
    font-size: 2.2em;
    font-weight: 600;
    color: #333;
    margin: 0 0 40px;
    text-align: center;
}

.perfumes-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 30px;
    margin-bottom: 60px;
}

.perfumes-pagination {
    text-align: center;
    margin: 60px 0;
}

.pagination {
    display: inline-flex;
    list-style: none;
    margin: 0;
    padding: 0;
    gap: 10px;
}

.pagination .page-item a,
.pagination .page-item span {
    display: block;
    padding: 12px 16px;
    border: 1px solid #dee2e6;
    border-radius: 6px;
    text-decoration: none;
    color: #333;
    transition: all 0.3s ease;
}

.pagination .page-item a:hover,
.pagination .page-item.current span {
    background: #0073aa;
    color: white;
    border-color: #0073aa;
}

.related-perfumers-section {
    margin-top: 80px;
    padding-top: 60px;
    border-top: 1px solid #dee2e6;
}

.related-perfumers-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 20px;
}

.related-perfumer-card {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
    text-align: center;
    transition: all 0.3s ease;
}

.related-perfumer-card:hover {
    background: white;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    transform: translateY(-3px);
}

.related-perfumer-name {
    margin: 0 0 10px;
    font-size: 1.1em;
}

.related-perfumer-name a {
    text-decoration: none;
    color: #333;
    transition: color 0.3s ease;
}

.related-perfumer-name a:hover {
    color: #0073aa;
}

.related-perfumer-count {
    color: #666;
    font-size: 0.9em;
}

.no-perfumes {
    text-align: center;
    padding: 60px 20px;
    color: #666;
    font-style: italic;
}

/* Responsive Design */
@media (max-width: 768px) {
    .perfumer-name {
        font-size: 2.2em;
    }
    
    .perfumer-bio {
        font-size: 1em;
    }
    
    .perfumer-stats {
        gap: 20px;
    }
    
    .stat-number {
        font-size: 2em;
    }
    
    .perfumes-grid {
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        gap: 20px;
    }
    
    .related-perfumers-grid {
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    }
}

@media (max-width: 480px) {
    .perfumer-hero {
        padding: 40px 0;
    }
    
    .perfumer-name {
        font-size: 1.8em;
    }
    
    .perfumer-stats {
        flex-direction: column;
        gap: 15px;
    }
    
    .perfumes-grid {
        grid-template-columns: 1fr;
    }
    
    .related-perfumers-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<?php get_footer(); ?>