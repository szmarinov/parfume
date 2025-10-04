<?php
/**
 * Single Parfume Template
 * 
 * Template for displaying single parfume posts
 * 
 * @package Parfume_Reviews
 * @since 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();

while (have_posts()) : the_post();
    
    $post_id = get_the_ID();
    
    // Get meta data
    $rating = get_post_meta($post_id, '_parfume_rating', true);
    $release_year = get_post_meta($post_id, '_parfume_release_year', true);
    $longevity = get_post_meta($post_id, '_parfume_longevity', true);
    $sillage = get_post_meta($post_id, '_parfume_sillage', true);
    $bottle_size = get_post_meta($post_id, '_parfume_bottle_size', true);
    $pros = get_post_meta($post_id, '_parfume_pros', true);
    $cons = get_post_meta($post_id, '_parfume_cons', true);
    $stores = get_post_meta($post_id, '_parfume_stores', true);
    $gallery = get_post_meta($post_id, '_parfume_gallery', true);
    
    // Get taxonomies
    $brands = wp_get_post_terms($post_id, 'marki');
    $genders = wp_get_post_terms($post_id, 'gender');
    $aroma_types = wp_get_post_terms($post_id, 'aroma_type');
    $seasons = wp_get_post_terms($post_id, 'season');
    $intensities = wp_get_post_terms($post_id, 'intensity');
    $notes = wp_get_post_terms($post_id, 'notes');
    $perfumers = wp_get_post_terms($post_id, 'perfumer');
    
    ?>
    
    <article id="post-<?php the_ID(); ?>" <?php post_class('parfume-single'); ?>>
        
        <div class="parfume-container">
            
            <!-- Breadcrumbs -->
            <?php if (function_exists('parfume_reviews_breadcrumbs')) : ?>
                <div class="parfume-breadcrumbs">
                    <?php parfume_reviews_breadcrumbs(); ?>
                </div>
            <?php endif; ?>
            
            <div class="parfume-content-wrapper">
                
                <!-- Main Content -->
                <div class="parfume-main-content">
                    
                    <!-- Header -->
                    <header class="parfume-header">
                        <h1 class="parfume-title"><?php the_title(); ?></h1>
                        
                        <?php if (!empty($brands)) : ?>
                            <div class="parfume-brand">
                                <?php foreach ($brands as $brand) : ?>
                                    <a href="<?php echo esc_url(get_term_link($brand)); ?>">
                                        <?php echo esc_html($brand->name); ?>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($rating) : ?>
                            <div class="parfume-rating">
                                <span class="rating-value"><?php echo esc_html($rating); ?>/10</span>
                                <div class="rating-stars">
                                    <?php echo parfume_reviews_get_rating_stars($rating); ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </header>
                    
                    <!-- Gallery -->
                    <div class="parfume-gallery">
                        <div class="main-image">
                            <?php if (has_post_thumbnail()) : ?>
                                <?php the_post_thumbnail('large'); ?>
                            <?php else : ?>
                                <img src="<?php echo esc_url(PARFUME_REVIEWS_URL . 'assets/images/placeholder.jpg'); ?>" alt="<?php the_title(); ?>">
                            <?php endif; ?>
                        </div>
                        
                        <?php if (!empty($gallery) && is_array($gallery)) : ?>
                            <div class="gallery-thumbnails">
                                <?php foreach ($gallery as $image_id) : ?>
                                    <div class="thumbnail">
                                        <?php echo wp_get_attachment_image($image_id, 'thumbnail'); ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Meta Info -->
                    <div class="parfume-meta-info">
                        <div class="meta-grid">
                            <?php if ($release_year) : ?>
                                <div class="meta-item">
                                    <span class="meta-label"><?php _e('Година:', 'parfume-reviews'); ?></span>
                                    <span class="meta-value"><?php echo esc_html($release_year); ?></span>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($longevity) : ?>
                                <div class="meta-item">
                                    <span class="meta-label"><?php _e('Дълготрайност:', 'parfume-reviews'); ?></span>
                                    <span class="meta-value"><?php echo esc_html($longevity); ?></span>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($sillage) : ?>
                                <div class="meta-item">
                                    <span class="meta-label"><?php _e('Силаж:', 'parfume-reviews'); ?></span>
                                    <span class="meta-value"><?php echo esc_html($sillage); ?></span>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($bottle_size) : ?>
                                <div class="meta-item">
                                    <span class="meta-label"><?php _e('Обем:', 'parfume-reviews'); ?></span>
                                    <span class="meta-value"><?php echo esc_html($bottle_size); ?>ml</span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Description -->
                    <div class="parfume-description">
                        <h2><?php _e('Описание', 'parfume-reviews'); ?></h2>
                        <div class="description-content">
                            <?php the_content(); ?>
                        </div>
                    </div>
                    
                    <!-- Taxonomies -->
                    <div class="parfume-taxonomies">
                        
                        <?php if (!empty($notes)) : ?>
                            <div class="taxonomy-group">
                                <h3><?php _e('Ароматни нотки', 'parfume-reviews'); ?></h3>
                                <div class="taxonomy-terms">
                                    <?php foreach ($notes as $note) : ?>
                                        <a href="<?php echo esc_url(get_term_link($note)); ?>" class="term-badge">
                                            <?php echo esc_html($note->name); ?>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($perfumers)) : ?>
                            <div class="taxonomy-group">
                                <h3><?php _e('Парфюмер', 'parfume-reviews'); ?></h3>
                                <div class="taxonomy-terms">
                                    <?php foreach ($perfumers as $perfumer) : ?>
                                        <a href="<?php echo esc_url(get_term_link($perfumer)); ?>" class="term-badge">
                                            <?php echo esc_html($perfumer->name); ?>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($seasons)) : ?>
                            <div class="taxonomy-group">
                                <h3><?php _e('Сезон', 'parfume-reviews'); ?></h3>
                                <div class="taxonomy-terms">
                                    <?php foreach ($seasons as $season) : ?>
                                        <a href="<?php echo esc_url(get_term_link($season)); ?>" class="term-badge">
                                            <?php echo esc_html($season->name); ?>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Pros & Cons -->
                    <?php if ($pros || $cons) : ?>
                        <div class="parfume-pros-cons">
                            <div class="pros-cons-grid">
                                <?php if ($pros) : ?>
                                    <div class="pros">
                                        <h3><?php _e('Предимства', 'parfume-reviews'); ?></h3>
                                        <ul>
                                            <?php foreach (explode("\n", $pros) as $pro) : ?>
                                                <?php if (trim($pro)) : ?>
                                                    <li><?php echo esc_html(trim($pro)); ?></li>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($cons) : ?>
                                    <div class="cons">
                                        <h3><?php _e('Недостатъци', 'parfume-reviews'); ?></h3>
                                        <ul>
                                            <?php foreach (explode("\n", $cons) as $con) : ?>
                                                <?php if (trim($con)) : ?>
                                                    <li><?php echo esc_html(trim($con)); ?></li>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                </div>
                
                <!-- Sidebar -->
                <aside class="parfume-sidebar">
                    
                    <!-- Stores -->
                    <?php if (!empty($stores) && is_array($stores)) : ?>
                        <div class="parfume-stores">
                            <h3><?php _e('Къде да купите', 'parfume-reviews'); ?></h3>
                            
                            <div class="stores-list">
                                <?php foreach ($stores as $index => $store) : ?>
                                    <div class="store-item">
                                        <div class="store-header">
                                            <h4 class="store-name"><?php echo esc_html($store['name']); ?></h4>
                                            <?php if (!empty($store['price'])) : ?>
                                                <div class="store-price">
                                                    <span class="price-value"><?php echo esc_html($store['price']); ?></span>
                                                    <span class="price-currency"><?php echo esc_html($store['currency'] ?? 'BGN'); ?></span>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <?php if (!empty($store['in_stock'])) : ?>
                                            <div class="store-stock in-stock">
                                                <?php _e('В наличност', 'parfume-reviews'); ?>
                                            </div>
                                        <?php else : ?>
                                            <div class="store-stock out-of-stock">
                                                <?php _e('Изчерпан', 'parfume-reviews'); ?>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <?php if (!empty($store['url'])) : ?>
                                            <a href="<?php echo esc_url($store['url']); ?>" 
                                               class="store-button" 
                                               target="_blank" 
                                               rel="nofollow noopener">
                                                <?php _e('Купи сега', 'parfume-reviews'); ?>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Comparison Button -->
                    <div class="parfume-actions">
                        <?php if (function_exists('parfume_reviews_comparison_button')) : ?>
                            <?php parfume_reviews_comparison_button($post_id); ?>
                        <?php endif; ?>
                    </div>
                    
                </aside>
                
            </div>
            
            <!-- Related Parfumes -->
            <?php
            if (class_exists('Parfume_Reviews\PostTypes\Parfume\Repository')) {
                $repo = new Parfume_Reviews\PostTypes\Parfume\Repository();
                $related = $repo->related($post_id, 4);
                
                if ($related->have_posts()) :
                    ?>
                    <div class="related-parfumes">
                        <h2><?php _e('Подобни парфюми', 'parfume-reviews'); ?></h2>
                        <div class="parfumes-grid">
                            <?php while ($related->have_posts()) : $related->the_post(); ?>
                                <?php if (function_exists('parfume_reviews_display_parfume_card')) : ?>
                                    <?php parfume_reviews_display_parfume_card(get_the_ID()); ?>
                                <?php endif; ?>
                            <?php endwhile; wp_reset_postdata(); ?>
                        </div>
                    </div>
                    <?php
                endif;
            }
            ?>
            
        </div>
        
    </article>
    
<?php endwhile;

get_footer();