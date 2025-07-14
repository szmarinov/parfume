<?php
/**
 * Universal template for taxonomy archives
 * Fallback for any taxonomy that doesn't have specific archive template
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header(); 

global $wp_query;
$taxonomy = isset($wp_query->query_vars['is_parfume_taxonomy_archive']) ? $wp_query->query_vars['is_parfume_taxonomy_archive'] : '';
$taxonomy_obj = get_taxonomy($taxonomy);
?>

<div class="parfume-archive <?php echo esc_attr($taxonomy); ?>-archive">
    <div class="archive-header">
        <h1 class="archive-title">
            <?php 
            if ($taxonomy_obj) {
                printf(__('All %s', 'parfume-reviews'), $taxonomy_obj->labels->name);
            } else {
                _e('All Items', 'parfume-reviews');
            }
            ?>
        </h1>
        <div class="archive-description">
            <p>
                <?php 
                if ($taxonomy_obj) {
                    printf(__('Browse all %s and discover perfumes organized by this category.', 'parfume-reviews'), strtolower($taxonomy_obj->labels->name));
                }
                ?>
            </p>
        </div>
    </div>

    <div class="archive-content">
        <div class="archive-main">
            <?php
            if ($taxonomy && taxonomy_exists($taxonomy)) {
                $all_terms = get_terms(array(
                    'taxonomy' => $taxonomy,
                    'hide_empty' => false,
                    'orderby' => 'name',
                    'order' => 'ASC',
                ));

                if (!empty($all_terms) && !is_wp_error($all_terms)): 
                ?>
                    <div class="terms-grid">
                        <?php foreach ($all_terms as $term): ?>
                            <div class="term-item">
                                <a href="<?php echo get_term_link($term); ?>" class="term-link">
                                    <?php 
                                    // Check for term image (all taxonomies now have images)
                                    $image_meta_key = $taxonomy . '-image-id';
                                    $image_id = get_term_meta($term->term_id, $image_meta_key, true);
                                    if ($image_id): 
                                    ?>
                                        <div class="term-image">
                                            <?php echo wp_get_attachment_image($image_id, 'thumbnail', false, array('alt' => $term->name)); ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="term-image term-placeholder">
                                            <span class="term-icon">
                                                <?php 
                                                // Different icons for different taxonomies
                                                switch($taxonomy) {
                                                    case 'marki': echo '🏷️'; break;
                                                    case 'notes': echo '🌿'; break;
                                                    case 'perfumer': echo '👨‍🔬'; break;
                                                    case 'gender': echo '👥'; break;
                                                    case 'aroma_type': echo '💧'; break;
                                                    case 'season': echo '🌞'; break;
                                                    case 'intensity': echo '⚡'; break;
                                                    default: echo '📦'; break;
                                                }
                                                ?>
                                            </span>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="term-info">
                                        <h3 class="term-name"><?php echo esc_html($term->name); ?></h3>
                                        <span class="term-count">
                                            <?php printf(_n('%d парфюм', '%d парфюма', $term->count, 'parfume-reviews'), $term->count); ?>
                                        </span>
                                        
                                        <?php if ($term->description): ?>
                                            <p class="term-description"><?php echo wp_trim_words(esc_html($term->description), 15); ?></p>
                                        <?php endif; ?>
                                    </div>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="no-terms">
                        <?php 
                        if ($taxonomy_obj) {
                            printf(__('No %s found.', 'parfume-reviews'), strtolower($taxonomy_obj->labels->name));
                        } else {
                            _e('No items found.', 'parfume-reviews');
                        }
                        ?>
                    </p>
                <?php endif;
            } else {
                ?>
                <p class="error"><?php _e('Invalid taxonomy.', 'parfume-reviews'); ?></p>
                <?php
            }
            ?>
        </div>
    </div>
</div>

<style>
.terms-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 20px;
    margin-top: 30px;
}

.term-item {
    background: white;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    overflow: hidden;
    transition: all 0.3s ease;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.term-item:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
    border-color: #0073aa;
}

.term-link {
    display: block;
    padding: 20px;
    text-decoration: none;
    color: inherit;
    text-align: center;
}

.term-image {
    margin-bottom: 15px;
}

.term-image img {
    max-width: 80px;
    max-height: 80px;
    object-fit: contain;
    border-radius: 4px;
}

.term-placeholder {
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    width: 80px;
    height: 80px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto;
}

.term-icon {
    font-size: 32px;
}

.term-name {
    font-size: 1.2em;
    font-weight: bold;
    margin: 0 0 8px;
    color: #333;
}

.term-count {
    display: block;
    color: #0073aa;
    font-weight: 500;
    margin-bottom: 10px;
    font-size: 0.9em;
}

.term-description {
    color: #666;
    font-size: 0.9em;
    line-height: 1.4;
    margin: 0;
}

.no-terms, .error {
    text-align: center;
    padding: 40px 20px;
    color: #666;
    font-size: 1.1em;
}

.error {
    color: #dc3232;
}

@media (max-width: 768px) {
    .terms-grid {
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 15px;
    }
    
    .term-link {
        padding: 15px;
    }
    
    .term-name {
        font-size: 1.1em;
    }
}
</style>

<?php get_footer(); ?>