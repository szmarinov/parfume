<?php
/**
 * Single Perfumer Template
 * Показва информация за конкретен парфюмерист и неговите парфюми
 * 
 * Файл: templates/single-perfumer.php
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();

$current_term = get_queried_object();
$perfumer_image_id = get_term_meta($current_term->term_id, 'perfumer-image-id', true);
?>

<div class="single-perfumer-page perfumer-taxonomy-page">
    <div class="archive-header perfumer-hero">
        <div class="container">
            <nav class="breadcrumb">
                <a href="<?php echo home_url(); ?>"><?php _e('Начало', 'parfume-reviews'); ?></a>
                <span class="separator"> › </span>
                <a href="<?php echo home_url('/parfiumi/'); ?>"><?php _e('Парфюми', 'parfume-reviews'); ?></a>
                <span class="separator"> › </span>
                <a href="<?php echo home_url('/parfiumi/parfumeri/'); ?>"><?php _e('Парфюмеристи', 'parfume-reviews'); ?></a>
                <span class="separator"> › </span>
                <span class="current"><?php echo esc_html($current_term->name); ?></span>
            </nav>
            
            <div class="perfumer-header">
                <?php if ($perfumer_image_id): ?>
                    <div class="perfumer-image">
                        <?php echo wp_get_attachment_image($perfumer_image_id, 'medium', false, array('class' => 'perfumer-avatar')); ?>
                    </div>
                <?php endif; ?>
                
                <div class="perfumer-info">
                    <h1 class="archive-title perfumer-name"><?php echo esc_html($current_term->name); ?></h1>
                    
                    <?php if (!empty($current_term->description)): ?>
                        <div class="archive-description perfumer-bio">
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
    </div>
    
    <div class="archive-content perfumer-content">
        <div class="container">
            <div class="archive-main">
                <div class="perfumer-perfumes-section">
                    <h2 class="section-title"><?php printf(__('Парфюми от %s', 'parfume-reviews'), esc_html($current_term->name)); ?></h2>
                    
                    <?php
                    // Query за парфюмите на този парфюмерист  
                    $perfumes_query = new WP_Query(array(
                        'post_type' => 'parfume',
                        'posts_per_page' => 12,
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
                        <div class="parfume-grid">
                            <?php while ($perfumes_query->have_posts()): $perfumes_query->the_post(); ?>
                                <div class="parfume-card">
                                    <div class="parfume-image">
                                        <?php if (has_post_thumbnail()): ?>
                                            <a href="<?php the_permalink(); ?>">
                                                <?php the_post_thumbnail('medium'); ?>
                                            </a>
                                        <?php else: ?>
                                            <a href="<?php the_permalink(); ?>" class="placeholder-image">
                                                <span class="placeholder-text"><?php _e('Няма изображение', 'parfume-reviews'); ?></span>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="parfume-content">
                                        <h3 class="parfume-title">
                                            <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                                        </h3>
                                        
                                        <?php
                                        // Получаваме марката
                                        $brands = get_the_terms(get_the_ID(), 'marki');
                                        if ($brands && !is_wp_error($brands)):
                                            $brand = $brands[0];
                                        ?>
                                            <div class="parfume-brand">
                                                <a href="<?php echo get_term_link($brand); ?>"><?php echo esc_html($brand->name); ?></a>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <?php
                                        // Рейтинг
                                        $rating = get_post_meta(get_the_ID(), '_parfume_rating', true);
                                        if ($rating):
                                        ?>
                                            <div class="parfume-rating">
                                                <div class="stars">
                                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                                        <span class="star <?php echo $i <= $rating ? 'filled' : ''; ?>">★</span>
                                                    <?php endfor; ?>
                                                </div>
                                                <span class="rating-value">(<?php echo esc_html($rating); ?>/5)</span>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <?php
                                        // Цена ако има
                                        $price = get_post_meta(get_the_ID(), '_parfume_price', true);
                                        if ($price):
                                        ?>
                                            <div class="parfume-price">
                                                <?php echo esc_html($price); ?> лв.
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                        
                        <?php
                        // Пагинация
                        if ($perfumes_query->max_num_pages > 1):
                            echo paginate_links(array(
                                'total' => $perfumes_query->max_num_pages,
                                'current' => max(1, get_query_var('paged')),
                                'format' => '?paged=%#%',
                                'show_all' => false,
                                'end_size' => 1,
                                'mid_size' => 2,
                                'prev_next' => true,
                                'prev_text' => __('‹ Предишна', 'parfume-reviews'),
                                'next_text' => __('Следваща ›', 'parfume-reviews'),
                            ));
                        endif;
                        ?>
                        
                    <?php else: ?>
                        <div class="no-parfumes-message">
                            <p><?php _e('Все още няма парфюми от този парфюмерист.', 'parfume-reviews'); ?></p>
                        </div>
                    <?php endif; ?>
                    
                    <?php wp_reset_postdata(); ?>
                </div>
                
                <!-- Други парфюмеристи -->
                <div class="related-perfumers-section">
                    <h2 class="section-title"><?php _e('Други парфюмеристи', 'parfume-reviews'); ?></h2>
                    
                    <?php
                    // Query други парфюмеристи
                    $other_perfumers = get_terms(array(
                        'taxonomy' => 'perfumer',
                        'hide_empty' => true,
                        'number' => 8,
                        'exclude' => array($current_term->term_id),
                        'orderby' => 'count',
                        'order' => 'DESC'
                    ));
                    
                    if (!empty($other_perfumers) && !is_wp_error($other_perfumers)): ?>
                        <div class="perfumers-archive-grid columns-4">
                            <?php foreach ($other_perfumers as $perfumer): ?>
                                <div class="perfumer-item">
                                    <h3>
                                        <a href="<?php echo get_term_link($perfumer); ?>">
                                            <?php echo esc_html($perfumer->name); ?>
                                        </a>
                                    </h3>
                                    <span class="count">
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
</div>

<?php get_footer(); ?>