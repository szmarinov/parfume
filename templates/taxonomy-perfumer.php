<?php
/**
 * Taxonomy Perfumer Template - Archive page за парфюмеристи
 * 
 * Този файл се зарежда за:
 * - Archive страница с всички парфюмеристи (/parfiumi/parfumeri/)
 * - Single страница на конкретен парфюмерист (/parfiumi/parfumeri/alberto-morillas/)
 * 
 * Файл: templates/taxonomy-perfumer.php
 * ПОПРАВЕНА ВЕРСИЯ
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();

$current_term = get_queried_object();
$is_single_perfumer = $current_term && isset($current_term->name) && !empty($current_term->name);

// Ако е конкретен парфюмерист, показваме single page
if ($is_single_perfumer) {
    ?>
    <div class="single-perfumer-page perfumer-taxonomy-page">
        <div class="perfumer-hero">
            <div class="container">
                <div class="perfumer-header">
                    <nav class="breadcrumb">
                        <a href="<?php echo home_url(); ?>"><?php _e('Начало', 'parfume-reviews'); ?></a>
                        <span class="separator"> › </span>
                        <a href="<?php echo home_url('/parfiumi/'); ?>"><?php _e('Парфюми', 'parfume-reviews'); ?></a>
                        <span class="separator"> › </span>
                        <a href="<?php echo home_url('/parfiumi/parfumeri/'); ?>"><?php _e('Парфюмеристи', 'parfume-reviews'); ?></a>
                        <span class="separator"> › </span>
                        <span class="current"><?php echo esc_html($current_term->name); ?></span>
                    </nav>
                    
                    <h1 class="perfumer-name"><?php echo esc_html($current_term->name); ?></h1>
                    
                    <?php if (!empty($current_term->description)): ?>
                        <div class="perfumer-description">
                            <?php echo wpautop($current_term->description); ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="perfumer-stats">
                        <span class="perfume-count">
                            <?php printf(_n('%d парфюм', '%d парфюма', $current_term->count, 'parfume-reviews'), $current_term->count); ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="perfumer-content">
            <div class="container">
                <div class="perfumer-perfumes">
                    <h2><?php _e('Парфюми от този парфюмерист', 'parfume-reviews'); ?></h2>
                    
                    <?php
                    // Query парфюми от този парфюмерист
                    $parfumes_query = new WP_Query(array(
                        'post_type' => 'parfume',
                        'posts_per_page' => 12,
                        'tax_query' => array(
                            array(
                                'taxonomy' => 'perfumer',
                                'field' => 'term_id',
                                'terms' => $current_term->term_id,
                            ),
                        ),
                        'orderby' => 'title',
                        'order' => 'ASC'
                    ));
                    
                    if ($parfumes_query->have_posts()): ?>
                        <div class="parfumes-grid">
                            <?php while ($parfumes_query->have_posts()): $parfumes_query->the_post(); ?>
                                <div class="parfume-card">
                                    <div class="parfume-image">
                                        <?php if (has_post_thumbnail()): ?>
                                            <a href="<?php the_permalink(); ?>">
                                                <?php the_post_thumbnail('medium'); ?>
                                            </a>
                                        <?php else: ?>
                                            <div class="placeholder-image">
                                                <span class="dashicons dashicons-format-image"></span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="parfume-info">
                                        <h3 class="parfume-title">
                                            <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                                        </h3>
                                        
                                        <?php
                                        // Показваме марката
                                        $brands = get_the_terms(get_the_ID(), 'marki');
                                        if (!empty($brands) && !is_wp_error($brands)): ?>
                                            <div class="parfume-brand">
                                                <?php echo esc_html($brands[0]->name); ?>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <?php
                                        // Показваме цена ако има
                                        $price = get_post_meta(get_the_ID(), '_price', true);
                                        if (!empty($price)): ?>
                                            <div class="parfume-price">
                                                <?php echo esc_html($price); ?> лв.
                                            </div>
                                        <?php endif; ?>
                                        
                                        <div class="parfume-excerpt">
                                            <?php the_excerpt(); ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                        
                        <?php
                        // Pagination
                        if ($parfumes_query->max_num_pages > 1): ?>
                            <div class="pagination-wrapper">
                                <?php
                                echo paginate_links(array(
                                    'total' => $parfumes_query->max_num_pages,
                                    'current' => max(1, get_query_var('paged')),
                                    'prev_text' => '‹ ' . __('Предишна', 'parfume-reviews'),
                                    'next_text' => __('Следваща', 'parfume-reviews') . ' ›',
                                ));
                                ?>
                            </div>
                        <?php endif; ?>
                        
                    <?php else: ?>
                        <div class="no-parfumes-message">
                            <p><?php _e('Все още няма парфюми от този парфюмерист.', 'parfume-reviews'); ?></p>
                        </div>
                    <?php endif; ?>
                    
                    <?php wp_reset_postdata(); ?>
                </div>
                
                <!-- Други парфюмеристи -->
                <div class="related-perfumers">
                    <h2><?php _e('Други парфюмеристи', 'parfume-reviews'); ?></h2>
                    
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
    
    <?php
} else {
    // Archive page - показваме всички парфюмеристи
    ?>
    <div class="perfumers-archive-page perfumer-taxonomy-page">
        <div class="archive-header">
            <div class="container">
                <nav class="breadcrumb">
                    <a href="<?php echo home_url(); ?>"><?php _e('Начало', 'parfume-reviews'); ?></a>
                    <span class="separator"> › </span>
                    <a href="<?php echo home_url('/parfiumi/'); ?>"><?php _e('Парфюми', 'parfume-reviews'); ?></a>
                    <span class="separator"> › </span>
                    <span class="current"><?php _e('Парфюмеристи', 'parfume-reviews'); ?></span>
                </nav>
                
                <h1 class="archive-title"><?php _e('Всички Парфюмеристи', 'parfume-reviews'); ?></h1>
                <div class="archive-description">
                    <p><?php _e('Открийте парфюми по техните създатели. Разгледайте колекциите на най-известните парфюмеристи в света.', 'parfume-reviews'); ?></p>
                </div>
            </div>
        </div>
        
        <div class="archive-content">
            <div class="container">
                <div class="perfumers-grid">
                    <?php
                    // ПОПРАВЕНА ЧАСТ - Query всички парфюмеристи, не парфюми!
                    $all_perfumers = get_terms(array(
                        'taxonomy' => 'perfumer',
                        'hide_empty' => true,
                        'orderby' => 'name',
                        'order' => 'ASC',
                        'number' => 0 // Без лимит - всички парфюмеристи
                    ));
                    
                    if (!empty($all_perfumers) && !is_wp_error($all_perfumers)):
                        foreach ($all_perfumers as $perfumer): ?>
                            <div class="perfumer-archive-card">
                                <div class="perfumer-card-content">
                                    <h2 class="perfumer-card-name">
                                        <a href="<?php echo get_term_link($perfumer); ?>">
                                            <?php echo esc_html($perfumer->name); ?>
                                        </a>
                                    </h2>
                                    
                                    <?php if (!empty($perfumer->description)): ?>
                                        <div class="perfumer-card-description">
                                            <?php echo wp_trim_words($perfumer->description, 20, '...'); ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="perfumer-card-stats">
                                        <span class="perfume-count">
                                            <?php printf(_n('%d парфюм', '%d парфюма', $perfumer->count, 'parfume-reviews'), $perfumer->count); ?>
                                        </span>
                                    </div>
                                    
                                    <div class="perfumer-card-actions">
                                        <a href="<?php echo get_term_link($perfumer); ?>" class="view-perfumer-btn">
                                            <?php _e('Виж парфюмите', 'parfume-reviews'); ?>
                                        </a>
                                    </div>
                                </div>
                                
                                <?php
                                // Показваме първите няколко парфюма като preview
                                $perfumer_parfumes = get_posts(array(
                                    'post_type' => 'parfume',
                                    'posts_per_page' => 3,
                                    'tax_query' => array(
                                        array(
                                            'taxonomy' => 'perfumer',
                                            'field' => 'term_id',
                                            'terms' => $perfumer->term_id,
                                        ),
                                    ),
                                    'orderby' => 'title',
                                    'order' => 'ASC'
                                ));
                                
                                if (!empty($perfumer_parfumes)): ?>
                                    <div class="perfumer-preview-parfumes">
                                        <h4><?php _e('Популярни парфюми:', 'parfume-reviews'); ?></h4>
                                        <ul class="preview-parfumes-list">
                                            <?php foreach ($perfumer_parfumes as $parfume): ?>
                                                <li>
                                                    <a href="<?php echo get_permalink($parfume->ID); ?>">
                                                        <?php echo esc_html($parfume->post_title); ?>
                                                    </a>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="no-perfumers-message">
                            <p><?php _e('Все още няма добавени парфюмеристи.', 'parfume-reviews'); ?></p>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Статистики за парфюмеристите -->
                <div class="perfumers-stats">
                    <h2><?php _e('Статистики', 'parfume-reviews'); ?></h2>
                    <div class="stats-grid">
                        <div class="stat-item">
                            <span class="stat-number"><?php echo count($all_perfumers); ?></span>
                            <span class="stat-label"><?php _e('Парфюмеристи', 'parfume-reviews'); ?></span>
                        </div>
                        
                        <?php
                        // Общо парфюми от всички парфюмеристи
                        $total_parfumes = 0;
                        if (!empty($all_perfumers)) {
                            foreach ($all_perfumers as $perfumer) {
                                $total_parfumes += $perfumer->count;
                            }
                        }
                        ?>
                        <div class="stat-item">
                            <span class="stat-number"><?php echo $total_parfumes; ?></span>
                            <span class="stat-label"><?php _e('Общо парфюми', 'parfume-reviews'); ?></span>
                        </div>
                        
                        <?php
                        // Най-активен парфюмерист
                        if (!empty($all_perfumers)) {
                            $most_active = $all_perfumers[0];
                            foreach ($all_perfumers as $perfumer) {
                                if ($perfumer->count > $most_active->count) {
                                    $most_active = $perfumer;
                                }
                            }
                        ?>
                            <div class="stat-item">
                                <span class="stat-label"><?php _e('Най-активен:', 'parfume-reviews'); ?></span>
                                <span class="stat-value">
                                    <a href="<?php echo get_term_link($most_active); ?>">
                                        <?php echo esc_html($most_active->name); ?>
                                    </a>
                                </span>
                            </div>
                        <?php } ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php
}

get_footer();
?>