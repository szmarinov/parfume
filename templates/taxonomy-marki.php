<?php
/**
 * Template for individual Brand pages (e.g., /parfiumi/marki/dior/)
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header(); 

$current_term = get_queried_object();
$brand_image_id = get_term_meta($current_term->term_id, 'marki-image-id', true);
?>

<div class="parfume-archive brand-single-archive">
    <div class="archive-header">
        <div class="brand-header-content">
            <?php if ($brand_image_id): ?>
                <div class="brand-logo-large">
                    <?php echo wp_get_attachment_image($brand_image_id, 'medium', false, array('alt' => $current_term->name)); ?>
                </div>
            <?php endif; ?>
            
            <div class="brand-header-text">
                <h1 class="archive-title"><?php echo esc_html($current_term->name); ?></h1>
                
                <?php if ($current_term->description): ?>
                    <div class="archive-description">
                        <?php echo wpautop(esc_html($current_term->description)); ?>
                    </div>
                <?php endif; ?>
                
                <div class="archive-meta">
                    <span class="perfume-count">
                        <?php printf(_n('%d парфюм', '%d парфюма', $current_term->count, 'parfume-reviews'), $current_term->count); ?>
                    </span>
                    
                    <?php
                    // Calculate average rating for this brand
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
                            <span class="average-rating">
                                <span class="rating-label"><?php _e('Average Rating:', 'parfume-reviews'); ?></span>
                                <span class="rating-stars">
                                    <?php echo parfume_reviews_get_rating_stars($average_rating); ?>
                                </span>
                                <span class="rating-number"><?php echo number_format($average_rating, 1); ?>/5</span>
                                <span class="rating-count">(<?php printf(_n('%d rating', '%d ratings', $rated_count, 'parfume-reviews'), $rated_count); ?>)</span>
                            </span>
                            <?php
                        }
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>

    <?php if (have_posts()): ?>
        <!-- Brand Statistics -->
        <div class="brand-stats">
            <div class="stats-container">
                <div class="stat-item">
                    <span class="stat-number"><?php echo $current_term->count; ?></span>
                    <span class="stat-label"><?php _e('Total Perfumes', 'parfume-reviews'); ?></span>
                </div>
                
                <?php
                // Get most popular notes for this brand
                $brand_notes = array();
                while (have_posts()): the_post();
                    $notes = wp_get_post_terms(get_the_ID(), 'notes', array('fields' => 'names'));
                    if (!empty($notes) && !is_wp_error($notes)) {
                        foreach ($notes as $note) {
                            if (!isset($brand_notes[$note])) {
                                $brand_notes[$note] = 0;
                            }
                            $brand_notes[$note]++;
                        }
                    }
                endwhile;
                wp_reset_postdata();
                
                // Sort by popularity and get top 3
                arsort($brand_notes);
                $top_notes = array_slice(array_keys($brand_notes), 0, 3);
                ?>
                
                <?php if (!empty($top_notes)): ?>
                    <div class="stat-item">
                        <span class="stat-number"><?php echo count($brand_notes); ?></span>
                        <span class="stat-label"><?php _e('Different Notes', 'parfume-reviews'); ?></span>
                    </div>
                    
                    <div class="stat-item">
                        <span class="stat-notes"><?php echo implode(', ', array_slice($top_notes, 0, 2)); ?></span>
                        <span class="stat-label"><?php _e('Popular Notes', 'parfume-reviews'); ?></span>
                    </div>
                <?php endif; ?>
                
                <?php
                // Get newest perfume year
                $newest_year = '';
                $args = array(
                    'post_type' => 'parfume',
                    'posts_per_page' => 1,
                    'meta_key' => '_parfume_release_year',
                    'orderby' => 'meta_value_num',
                    'order' => 'DESC',
                    'tax_query' => array(
                        array(
                            'taxonomy' => 'marki',
                            'field' => 'term_id',
                            'terms' => $current_term->term_id,
                        ),
                    ),
                );
                
                $newest = new WP_Query($args);
                if ($newest->have_posts()) {
                    $newest->the_post();
                    $newest_year = get_post_meta(get_the_ID(), '_parfume_release_year', true);
                    wp_reset_postdata();
                }
                
                if ($newest_year):
                ?>
                    <div class="stat-item">
                        <span class="stat-number"><?php echo esc_html($newest_year); ?></span>
                        <span class="stat-label"><?php _e('Latest Release', 'parfume-reviews'); ?></span>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="archive-content">
            <div class="archive-sidebar">
                <?php echo do_shortcode('[parfume_filters show_brand="false"]'); ?>
                
                <!-- Popular notes for this brand -->
                <?php if (!empty($top_notes)): ?>
                    <div class="brand-popular-notes">
                        <h3><?php _e('Popular Notes in', 'parfume-reviews'); ?> <?php echo esc_html($current_term->name); ?></h3>
                        <div class="notes-list">
                            <?php foreach ($top_notes as $note_name): ?>
                                <?php 
                                $note_term = get_term_by('name', $note_name, 'notes');
                                if ($note_term):
                                ?>
                                    <a href="<?php echo get_term_link($note_term); ?>" class="note-tag">
                                        <?php echo esc_html($note_name); ?>
                                        <span class="note-count"><?php echo $brand_notes[$note_name]; ?></span>
                                    </a>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <div class="archive-main">
                <div class="parfume-grid">
                    <?php while (have_posts()): the_post(); ?>
                        <div class="parfume-card">
                            <div class="parfume-thumbnail">
                                <a href="<?php the_permalink(); ?>">
                                    <?php if (has_post_thumbnail()): ?>
                                        <?php the_post_thumbnail('medium'); ?>
                                    <?php else: ?>
                                        <div class="no-image">
                                            <span><?php _e('No Image', 'parfume-reviews'); ?></span>
                                        </div>
                                    <?php endif; ?>
                                </a>
                            </div>

                            <div class="parfume-content">
                                <h3 class="parfume-title">
                                    <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                                </h3>

                                <?php 
                                $release_year = get_post_meta(get_the_ID(), '_parfume_release_year', true);
                                if ($release_year): 
                                ?>
                                    <div class="parfume-year"><?php echo esc_html($release_year); ?></div>
                                <?php endif; ?>

                                <?php 
                                $rating = get_post_meta(get_the_ID(), '_parfume_rating', true);
                                if (!empty($rating) && is_numeric($rating)): 
                                ?>
                                    <div class="parfume-rating">
                                        <div class="rating-stars">
                                            <?php echo parfume_reviews_get_rating_stars($rating); ?>
                                        </div>
                                        <span class="rating-number"><?php echo number_format(floatval($rating), 1); ?>/5</span>
                                    </div>
                                <?php endif; ?>

                                <?php
                                // Show top 3 notes for this perfume
                                $perfume_notes = wp_get_post_terms(get_the_ID(), 'notes', array('fields' => 'names', 'number' => 3));
                                if (!empty($perfume_notes) && !is_wp_error($perfume_notes)):
                                ?>
                                    <div class="parfume-notes">
                                        <?php foreach ($perfume_notes as $note): ?>
                                            <span class="note-tag"><?php echo esc_html($note); ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>

                                <div class="parfume-actions">
                                    <?php echo parfume_reviews_get_comparison_button(get_the_ID()); ?>
                                    <?php echo parfume_reviews_get_collections_dropdown(get_the_ID()); ?>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>

                <?php
                // Pagination
                the_posts_pagination(array(
                    'mid_size' => 2,
                    'prev_text' => __('&laquo; Previous', 'parfume-reviews'),
                    'next_text' => __('Next &raquo;', 'parfume-reviews'),
                ));
                ?>
            </div>
        </div>

    <?php else: ?>
        <div class="no-perfumes">
            <p><?php printf(__('No perfumes found for %s.', 'parfume-reviews'), esc_html($current_term->name)); ?></p>
        </div>
    <?php endif; ?>
</div>

<style>
.brand-header-content {
    display: flex;
    align-items: center;
    gap: 30px;
    margin-bottom: 30px;
}

.brand-logo-large {
    flex: 0 0 120px;
}

.brand-logo-large img {
    max-width: 120px;
    max-height: 120px;
    object-fit: contain;
    border-radius: 8px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.brand-header-text {
    flex: 1;
}

.archive-title {
    font-size: 2.5em;
    margin-bottom: 15px;
    color: #333;
}

.archive-description {
    font-size: 1.1em;
    line-height: 1.6;
    color: #666;
    margin-bottom: 20px;
}

.archive-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
    align-items: center;
}

.perfume-count {
    background: #0073aa;
    color: white;
    padding: 8px 15px;
    border-radius: 20px;
    font-weight: bold;
    font-size: 0.9em;
}

.average-rating {
    display: flex;
    align-items: center;
    gap: 8px;
    background: #f8f9fa;
    padding: 8px 15px;
    border-radius: 20px;
}

.rating-label {
    font-weight: bold;
    color: #333;
}

.rating-stars {
    color: #ffc107;
}

.rating-number {
    font-weight: bold;
    color: #333;
}

.rating-count {
    color: #666;
    font-size: 0.9em;
}

/* Brand Statistics */
.brand-stats {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 15px;
    padding: 30px;
    margin: 30px 0;
    color: white;
}

.stats-container {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 30px;
    text-align: center;
}

.stat-item {
    display: flex;
    flex-direction: column;
    align-items: center;
}

.stat-number {
    font-size: 2.5em;
    font-weight: bold;
    margin-bottom: 5px;
    text-shadow: 0 2px 4px rgba(0,0,0,0.3);
}

.stat-notes {
    font-size: 1.1em;
    font-weight: bold;
    margin-bottom: 5px;
    text-shadow: 0 2px 4px rgba(0,0,0,0.3);
}

.stat-label {
    font-size: 1em;
    opacity: 0.9;
}

/* Archive content */
.archive-content {
    display: flex;
    gap: 30px;
    margin-top: 30px;
}

.archive-sidebar {
    flex: 0 0 250px;
}

.archive-main {
    flex: 1;
}

/* Popular notes sidebar */
.brand-popular-notes {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 20px;
    margin-top: 20px;
}

.brand-popular-notes h3 {
    margin-top: 0;
    margin-bottom: 15px;
    color: #333;
    font-size: 1.1em;
}

.notes-list {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.note-tag {
    display: inline-flex;
    align-items: center;
    justify-content: space-between;
    background: white;
    color: #333;
    padding: 8px 12px;
    border-radius: 15px;
    text-decoration: none;
    font-size: 0.9em;
    border: 1px solid #dee2e6;
    transition: all 0.3s ease;
}

.note-tag:hover {
    background: #0073aa;
    color: white;
    border-color: #0073aa;
}

.note-count {
    background: #0073aa;
    color: white;
    padding: 2px 6px;
    border-radius: 10px;
    font-size: 0.8em;
    margin-left: 8px;
}

.note-tag:hover .note-count {
    background: white;
    color: #0073aa;
}

/* Perfume cards */
.parfume-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 25px;
}

.parfume-card {
    background: white;
    border: 1px solid #dee2e6;
    border-radius: 12px;
    overflow: hidden;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.parfume-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 12px 35px rgba(0,0,0,0.15);
    border-color: #0073aa;
}

.parfume-thumbnail {
    height: 200px;
    overflow: hidden;
}

.parfume-thumbnail img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.no-image {
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f8f9fa;
    color: #666;
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

.parfume-year {
    color: #666;
    font-size: 0.9em;
    margin-bottom: 10px;
}

.parfume-rating {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 15px;
}

.parfume-notes {
    margin-bottom: 15px;
}

.parfume-notes .note-tag {
    display: inline-block;
    background: #e3f2fd;
    color: #1976d2;
    padding: 4px 8px;
    border-radius: 10px;
    font-size: 0.8em;
    margin-right: 5px;
    margin-bottom: 5px;
    text-decoration: none;
    border: 1px solid #bbdefb;
}

.parfume-notes .note-tag:hover {
    background: #1976d2;
    color: white;
    border-color: #1976d2;
}

.parfume-actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

/* No perfumes */
.no-perfumes {
    text-align: center;
    padding: 60px 20px;
    color: #666;
    font-size: 1.2em;
}

/* Responsive */
@media (max-width: 768px) {
    .brand-header-content {
        flex-direction: column;
        text-align: center;
    }
    
    .brand-logo-large {
        flex: 0 0 auto;
    }
    
    .archive-content {
        flex-direction: column;
    }
    
    .archive-sidebar {
        flex: 0 0 auto;
    }
    
    .stats-container {
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 20px;
    }
    
    .stat-number {
        font-size: 2em;
    }
    
    .parfume-grid {
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        gap: 20px;
    }
    
    .archive-meta {
        justify-content: center;
        flex-direction: column;
        gap: 10px;
    }
}

@media (max-width: 480px) {
    .parfume-grid {
        grid-template-columns: 1fr;
    }
    
    .stats-container {
        grid-template-columns: 1fr;
        gap: 15px;
    }
    
    .brand-stats {
        padding: 20px;
    }
}
</style>

<?php get_footer(); ?>