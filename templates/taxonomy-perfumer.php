<?php
/**
 * Perfumer Taxonomy Template
 * 
 * Template for displaying perfumer taxonomy archive and single perfumer pages
 * 
 * @package Parfume_Reviews
 * @since 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();

$queried_object = get_queried_object();
$is_archive = isset($queried_object->taxonomy) && empty($queried_object->slug);

?>

<div class="perfumer-page-wrapper">
    
    <!-- Breadcrumbs -->
    <?php if (function_exists('parfume_reviews_breadcrumbs')) : ?>
        <div class="parfume-breadcrumbs">
            <?php parfume_reviews_breadcrumbs(); ?>
        </div>
    <?php endif; ?>
    
    <div class="perfumer-container">
        
        <?php if ($is_archive) : ?>
            
            <!-- All Perfumers Archive -->
            <header class="perfumer-archive-header">
                <h1><?php _e('Всички парфюмери', 'parfume-reviews'); ?></h1>
                <p><?php _e('Разгледайте парфюмите създадени от талантливи парфюмери', 'parfume-reviews'); ?></p>
            </header>
            
            <?php
            // Get all perfumer terms
            $perfumers = get_terms([
                'taxonomy' => 'perfumer',
                'hide_empty' => true,
                'orderby' => 'name',
                'order' => 'ASC'
            ]);
            
            if (!empty($perfumers) && !is_wp_error($perfumers)) :
                ?>
                
                <div class="perfumers-grid">
                    <?php foreach ($perfumers as $perfumer) : ?>
                        
                        <article class="perfumer-card">
                            <a href="<?php echo esc_url(get_term_link($perfumer)); ?>" class="perfumer-link">
                                
                                <?php
                                // Get perfumer image if exists
                                $image = get_term_meta($perfumer->term_id, 'image', true);
                                if ($image) :
                                    ?>
                                    <div class="perfumer-image">
                                        <img src="<?php echo esc_url($image); ?>" alt="<?php echo esc_attr($perfumer->name); ?>">
                                    </div>
                                <?php else : ?>
                                    <div class="perfumer-image placeholder">
                                        <span class="dashicons dashicons-admin-users"></span>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="perfumer-info">
                                    <h3 class="perfumer-name"><?php echo esc_html($perfumer->name); ?></h3>
                                    
                                    <div class="perfumer-count">
                                        <?php
                                        printf(
                                            _n('%d парфюм', '%d парфюма', $perfumer->count, 'parfume-reviews'),
                                            $perfumer->count
                                        );
                                        ?>
                                    </div>
                                    
                                    <?php if (!empty($perfumer->description)) : ?>
                                        <div class="perfumer-excerpt">
                                            <?php echo wp_trim_words($perfumer->description, 20); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                            </a>
                        </article>
                        
                    <?php endforeach; ?>
                </div>
                
            <?php else : ?>
                
                <div class="no-results">
                    <p><?php _e('Няма намерени парфюмери', 'parfume-reviews'); ?></p>
                </div>
                
            <?php endif; ?>
            
        <?php else : ?>
            
            <!-- Single Perfumer Page -->
            <div class="single-perfumer-wrapper">
                
                <header class="perfumer-header">
                    
                    <?php
                    // Get perfumer image
                    $image = get_term_meta($queried_object->term_id, 'image', true);
                    if ($image) :
                        ?>
                        <div class="perfumer-avatar">
                            <img src="<?php echo esc_url($image); ?>" alt="<?php echo esc_attr($queried_object->name); ?>">
                        </div>
                    <?php endif; ?>
                    
                    <div class="perfumer-header-content">
                        <h1 class="perfumer-title"><?php echo esc_html($queried_object->name); ?></h1>
                        
                        <div class="perfumer-stats">
                            <span class="stat-item">
                                <span class="dashicons dashicons-admin-post"></span>
                                <?php
                                printf(
                                    _n('%d парфюм', '%d парфюма', $queried_object->count, 'parfume-reviews'),
                                    $queried_object->count
                                );
                                ?>
                            </span>
                        </div>
                        
                        <?php if (!empty($queried_object->description)) : ?>
                            <div class="perfumer-description">
                                <?php echo wpautop($queried_object->description); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                </header>
                
                <!-- Perfumer's Parfumes -->
                <div class="perfumer-parfumes">
                    
                    <h2><?php _e('Парфюми от този парфюмер', 'parfume-reviews'); ?></h2>
                    
                    <?php if (have_posts()) : ?>
                        
                        <div class="parfumes-grid">
                            <?php while (have_posts()) : the_post(); ?>
                                
                                <?php
                                if (function_exists('parfume_reviews_display_parfume_card')) {
                                    parfume_reviews_display_parfume_card(get_the_ID());
                                } else {
                                    // Fallback
                                    get_template_part('templates/parts/parfume-card');
                                }
                                ?>
                                
                            <?php endwhile; ?>
                        </div>
                        
                        <!-- Pagination -->
                        <?php if (function_exists('parfume_reviews_display_pagination')) : ?>
                            <div class="parfume-pagination">
                                <?php parfume_reviews_display_pagination(); ?>
                            </div>
                        <?php else : ?>
                            <div class="parfume-pagination">
                                <?php
                                the_posts_pagination([
                                    'prev_text' => __('&laquo; Предишна', 'parfume-reviews'),
                                    'next_text' => __('Следваща &raquo;', 'parfume-reviews'),
                                ]);
                                ?>
                            </div>
                        <?php endif; ?>
                        
                    <?php else : ?>
                        
                        <div class="no-results">
                            <p><?php _e('Няма парфюми от този парфюмер', 'parfume-reviews'); ?></p>
                        </div>
                        
                    <?php endif; ?>
                    
                </div>
                
            </div>
            
        <?php endif; ?>
        
    </div>
    
</div>

<?php get_footer(); ?>