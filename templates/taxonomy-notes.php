<?php
/**
 * Template for individual Note pages (e.g., /parfiumi/notes/vaniliya/)
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header(); 

$current_term = get_queried_object();
?>

<div class="parfume-archive note-single-archive">
    <div class="archive-header">
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
            <span class="note-category"><?php _e('Fragrance Note', 'parfume-reviews'); ?></span>
        </div>
    </div>

    <div class="archive-content">
        <div class="archive-sidebar">
            <?php echo do_shortcode('[parfume_filters show_notes="false"]'); ?>
        </div>

        <div class="archive-main">
            <?php if (have_posts()): ?>
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
                                $brands = wp_get_post_terms(get_the_ID(), 'marki', array('fields' => 'names'));
                                if (!empty($brands) && !is_wp_error($brands)): 
                                ?>
                                    <div class="parfume-brand"><?php echo esc_html($brands[0]); ?></div>
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
                                // Show other notes in this perfume
                                $other_notes = wp_get_post_terms(get_the_ID(), 'notes', array('fields' => 'names'));
                                if (!empty($other_notes) && !is_wp_error($other_notes)):
                                    // Remove current note from the list
                                    $other_notes = array_filter($other_notes, function($note) use ($current_term) {
                                        return $note !== $current_term->name;
                                    });
                                    
                                    if (!empty($other_notes)):
                                ?>
                                    <div class="other-notes">
                                        <span class="notes-label"><?php _e('Also contains:', 'parfume-reviews'); ?></span>
                                        <?php foreach (array_slice($other_notes, 0, 3) as $note): ?>
                                            <span class="note-tag"><?php echo esc_html($note); ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php 
                                    endif;
                                endif; 
                                ?>

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

            <?php else: ?>
                <p class="no-perfumes"><?php _e('No perfumes found with this note.', 'parfume-reviews'); ?></p>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.archive-meta {
    display: flex;
    gap: 15px;
    align-items: center;
    margin-top: 15px;
}

.perfume-count {
    background: #4CAF50;
    color: white;
    padding: 8px 15px;
    border-radius: 20px;
    font-weight: bold;
    font-size: 0.9em;
}

.note-category {
    background: #f8f9fa;
    color: #666;
    padding: 8px 15px;
    border-radius: 20px;
    font-size: 0.9em;
    border: 1px solid #dee2e6;
}

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
    border-color: #4CAF50;
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
    color: #4CAF50;
}

.parfume-brand {
    color: #666;
    font-size: 0.9em;
    margin-bottom: 10px;
    font-weight: 500;
}

.parfume-rating {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 15px;
}

.rating-stars {
    color: #ffc107;
}

.rating-number {
    font-weight: bold;
    color: #333;
    font-size: 0.9em;
}

.other-notes {
    margin-bottom: 15px;
}

.notes-label {
    display: block;
    font-size: 0.85em;
    color: #666;
    margin-bottom: 5px;
}

.note-tag {
    display: inline-block;
    background: #e8f5e8;
    color: #2e7d32;
    padding: 4px 8px;
    border-radius: 10px;
    font-size: 0.8em;
    margin-right: 5px;
    margin-bottom: 3px;
    border: 1px solid #c8e6c9;
}

.parfume-actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.no-perfumes {
    text-align: center;
    padding: 60px 20px;
    color: #666;
    font-size: 1.2em;
}

/* Responsive */
@media (max-width: 768px) {
    .archive-content {
        flex-direction: column;
    }
    
    .archive-sidebar {
        flex: 0 0 auto;
    }
    
    .parfume-grid {
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        gap: 20px;
    }
    
    .archive-meta {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }
}

@media (max-width: 480px) {
    .parfume-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<?php get_footer(); ?>