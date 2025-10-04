<?php
/**
 * Generic Taxonomy Template
 * 
 * Fallback template for all parfume taxonomies
 * (brands, gender, notes, aroma_type, season, intensity)
 * 
 * @package Parfume_Reviews
 * @since 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();

$queried_object = get_queried_object();

?>

<div class="taxonomy-page-wrapper">
    
    <!-- Breadcrumbs -->
    <?php if (function_exists('parfume_reviews_breadcrumbs')) : ?>
        <div class="parfume-breadcrumbs">
            <?php parfume_reviews_breadcrumbs(); ?>
        </div>
    <?php endif; ?>
    
    <div class="taxonomy-container">
        
        <div class="taxonomy-layout">
            
            <!-- Sidebar with Filters -->
            <aside class="taxonomy-sidebar">
                <div class="sidebar-inner">
                    
                    <h3><?php _e('Филтри', 'parfume-reviews'); ?></h3>
                    
                    <!-- Active Filters -->
                    <?php if (function_exists('parfume_reviews_display_active_filters')) : ?>
                        <?php parfume_reviews_display_active_filters(); ?>
                    <?php endif; ?>
                    
                    <!-- Filter Form -->
                    <?php if (function_exists('parfume_reviews_display_filter_form')) : ?>
                        <?php parfume_reviews_display_filter_form(); ?>
                    <?php endif; ?>
                    
                </div>
            </aside>
            
            <!-- Main Content -->
            <main class="taxonomy-main">
                
                <!-- Taxonomy Header -->
                <header class="taxonomy-header">
                    
                    <?php
                    // Get term image if exists
                    $term_image = get_term_meta($queried_object->term_id, 'image', true);
                    if ($term_image) :
                        ?>
                        <div class="term-featured-image">
                            <img src="<?php echo esc_url($term_image); ?>" alt="<?php echo esc_attr($queried_object->name); ?>">
                        </div>
                    <?php endif; ?>
                    
                    <div class="taxonomy-header-content">
                        
                        <div class="taxonomy-type">
                            <?php
                            // Display taxonomy label
                            $taxonomy_obj = get_taxonomy($queried_object->taxonomy);
                            if ($taxonomy_obj) {
                                echo esc_html($taxonomy_obj->labels->singular_name);
                            }
                            ?>
                        </div>
                        
                        <h1 class="taxonomy-title"><?php echo esc_html($queried_object->name); ?></h1>
                        
                        <?php if (!empty($queried_object->description)) : ?>
                            <div class="taxonomy-description">
                                <?php echo wpautop($queried_object->description); ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="taxonomy-meta">
                            <span class="meta-item">
                                <span class="dashicons dashicons-admin-post"></span>
                                <?php
                                printf(
                                    _n('%d парфюм', '%d парфюма', $queried_object->count, 'parfume-reviews'),
                                    $queried_object->count
                                );
                                ?>
                            </span>
                        </div>
                        
                    </div>
                    
                </header>
                
                <!-- Results Bar -->
                <div class="results-bar">
                    <div class="results-count">
                        <?php
                        global $wp_query;
                        if ($wp_query->found_posts > 0) {
                            printf(
                                __('Показани %d от %d резултата', 'parfume-reviews'),
                                $wp_query->post_count,
                                $wp_query->found_posts
                            );
                        }
                        ?>
                    </div>
                    
                    <!-- Sort Options -->
                    <?php if (function_exists('parfume_reviews_display_sort_options')) : ?>
                        <div class="sort-options">
                            <?php parfume_reviews_display_sort_options(); ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Parfumes Grid -->
                <?php if (have_posts()) : ?>
                    
                    <div class="parfumes-grid">
                        <?php while (have_posts()) : the_post(); ?>
                            
                            <?php
                            if (function_exists('parfume_reviews_display_parfume_card')) {
                                parfume_reviews_display_parfume_card(get_the_ID());
                            } else {
                                // Fallback basic card
                                ?>
                                <article id="post-<?php the_ID(); ?>" <?php post_class('parfume-card'); ?>>
                                    <a href="<?php the_permalink(); ?>" class="card-link">
                                        
                                        <?php if (has_post_thumbnail()) : ?>
                                            <div class="card-image">
                                                <?php the_post_thumbnail('medium'); ?>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <div class="card-content">
                                            <h3 class="card-title"><?php the_title(); ?></h3>
                                            
                                            <?php
                                            $brands = wp_get_post_terms(get_the_ID(), 'marki');
                                            if (!empty($brands)) :
                                                ?>
                                                <div class="card-brand">
                                                    <?php echo esc_html($brands[0]->name); ?>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <?php
                                            $rating = get_post_meta(get_the_ID(), '_parfume_rating', true);
                                            if ($rating) :
                                                ?>
                                                <div class="card-rating">
                                                    <span class="rating-value"><?php echo esc_html($rating); ?>/10</span>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        
                                    </a>
                                </article>
                                <?php
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
                    
                    <!-- No Results -->
                    <div class="no-results">
                        <h2><?php _e('Няма намерени парфюми', 'parfume-reviews'); ?></h2>
                        <p><?php _e('В момента няма парфюми в тази категория.', 'parfume-reviews'); ?></p>
                        
                        <a href="<?php echo esc_url(get_post_type_archive_link('parfume')); ?>" class="button">
                            <?php _e('Виж всички парфюми', 'parfume-reviews'); ?>
                        </a>
                    </div>
                    
                <?php endif; ?>
                
            </main>
            
        </div>
        
    </div>
    
</div>

<?php get_footer(); ?>