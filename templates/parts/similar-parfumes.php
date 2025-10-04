<?php
/**
 * Template Part: Similar Parfumes
 * Displays similar perfumes based on shared notes
 */

if (!defined('ABSPATH')) {
    exit;
}

global $post;

// Get settings
$columns = get_option('parfume_similar_columns', 4);
$count = get_option('parfume_similar_count', 4);

// Get current perfume's notes
$current_notes = wp_get_post_terms($post->ID, 'notes', ['fields' => 'ids']);

if (empty($current_notes)) {
    return;
}

// Query for similar perfumes
$args = [
    'post_type' => 'parfume',
    'posts_per_page' => $count,
    'post__not_in' => [$post->ID],
    'tax_query' => [
        [
            'taxonomy' => 'notes',
            'field' => 'term_id',
            'terms' => $current_notes,
            'operator' => 'IN'
        ]
    ],
    'orderby' => 'rand'
];

$similar_query = new WP_Query($args);

if (!$similar_query->have_posts()) {
    return;
}
?>

<section class="parfume-similar">
    <h2 class="section-title">Подобни аромати</h2>
    <p class="section-description">Парфюми със сходни ароматни нотки</p>
    
    <div class="similar-grid" data-columns="<?php echo esc_attr($columns); ?>">
        <?php while ($similar_query->have_posts()) : $similar_query->the_post(); ?>
            <article class="similar-item">
                <a href="<?php the_permalink(); ?>" class="similar-link">
                    <div class="similar-image">
                        <?php if (has_post_thumbnail()) : ?>
                            <?php the_post_thumbnail('medium', ['class' => 'parfume-thumbnail']); ?>
                        <?php else : ?>
                            <div class="no-image">
                                <svg width="60" height="60" viewBox="0 0 24 24" fill="none">
                                    <path d="M4 16L8.586 11.414C9.367 10.633 10.633 10.633 11.414 11.414L16 16M14 14L15.586 12.414C16.367 11.633 17.633 11.633 18.414 12.414L20 14M14 8H14.01M6 20H18C18.5304 20 19.0391 19.7893 19.4142 19.4142C19.7893 19.0391 20 18.5304 20 18V6C20 5.46957 19.7893 4.96086 19.4142 4.58579C19.0391 4.21071 18.5304 4 18 4H6C5.46957 4 4.96086 4.21071 4.58579 4.58579C4.21071 4.96086 4 5.46957 4 6V18C4 18.5304 4.21071 19.0391 4.58579 19.4142C4.96086 19.7893 5.46957 20 6 20Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </div>
                        <?php endif; ?>
                        
                        <?php 
                        // Get brand
                        $brands = get_the_terms(get_the_ID(), 'brand');
                        if ($brands && !is_wp_error($brands)) :
                        ?>
                            <span class="brand-badge"><?php echo esc_html($brands[0]->name); ?></span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="similar-content">
                        <h3 class="similar-title"><?php the_title(); ?></h3>
                        
                        <?php
                        // Get shared notes count
                        $item_notes = wp_get_post_terms(get_the_ID(), 'notes', ['fields' => 'ids']);
                        $shared_notes = array_intersect($current_notes, $item_notes);
                        $shared_count = count($shared_notes);
                        ?>
                        
                        <?php if ($shared_count > 0) : ?>
                            <div class="shared-notes">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
                                    <path d="M9 12L11 14L15 10M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                                <span><?php echo esc_html($shared_count); ?> общи нотки</span>
                            </div>
                        <?php endif; ?>
                        
                        <?php
                        // Get price from stores if available
                        $stores = get_post_meta(get_the_ID(), '_parfume_stores', true);
                        if (!empty($stores) && is_array($stores)) :
                            $lowest_price = null;
                            foreach ($stores as $store) {
                                if (!empty($store['scraped_data']['price'])) {
                                    $price = floatval($store['scraped_data']['price']);
                                    if ($lowest_price === null || $price < $lowest_price) {
                                        $lowest_price = $price;
                                    }
                                }
                            }
                            
                            if ($lowest_price) :
                        ?>
                            <div class="similar-price">
                                от <strong><?php echo number_format($lowest_price, 2); ?> лв.</strong>
                            </div>
                        <?php 
                            endif;
                        endif; 
                        ?>
                    </div>
                </a>
            </article>
        <?php endwhile; ?>
    </div>
    
    <?php wp_reset_postdata(); ?>
</section>

<style>
.parfume-similar {
    margin: 40px 0;
    padding: 30px;
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.section-title {
    font-size: 28px;
    margin-bottom: 10px;
    text-align: center;
    color: #333;
}

.section-description {
    text-align: center;
    color: #666;
    margin-bottom: 30px;
}

.similar-grid {
    display: grid;
    gap: 25px;
}

.similar-grid[data-columns="2"] {
    grid-template-columns: repeat(2, 1fr);
}

.similar-grid[data-columns="3"] {
    grid-template-columns: repeat(3, 1fr);
}

.similar-grid[data-columns="4"] {
    grid-template-columns: repeat(4, 1fr);
}

.similar-item {
    background: #f8f9fa;
    border-radius: 8px;
    overflow: hidden;
    transition: all 0.3s ease;
}

.similar-item:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 16px rgba(0,0,0,0.15);
}

.similar-link {
    display: block;
    text-decoration: none;
    color: inherit;
}

.similar-image {
    position: relative;
    aspect-ratio: 3/4;
    overflow: hidden;
    background: #e9ecef;
}

.similar-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s ease;
}

.similar-item:hover .similar-image img {
    transform: scale(1.1);
}

.no-image {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #adb5bd;
}

.brand-badge {
    position: absolute;
    top: 10px;
    left: 10px;
    padding: 5px 12px;
    background: rgba(0,0,0,0.7);
    color: #fff;
    font-size: 12px;
    border-radius: 4px;
    backdrop-filter: blur(5px);
}

.similar-content {
    padding: 20px;
}

.similar-title {
    font-size: 16px;
    font-weight: 600;
    margin: 0 0 10px 0;
    line-height: 1.4;
    color: #333;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.shared-notes {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 13px;
    color: #28a745;
    margin-bottom: 8px;
}

.shared-notes svg {
    flex-shrink: 0;
}

.similar-price {
    font-size: 14px;
    color: #666;
}

.similar-price strong {
    color: #ff6b35;
    font-size: 16px;
}

/* Responsive */
@media (max-width: 1024px) {
    .similar-grid[data-columns="4"] {
        grid-template-columns: repeat(3, 1fr);
    }
}

@media (max-width: 768px) {
    .parfume-similar {
        padding: 20px 15px;
    }
    
    .section-title {
        font-size: 24px;
    }
    
    .similar-grid[data-columns="3"],
    .similar-grid[data-columns="4"] {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .similar-grid {
        gap: 15px;
    }
    
    .similar-content {
        padding: 15px;
    }
    
    .similar-title {
        font-size: 14px;
    }
}

@media (max-width: 480px) {
    .similar-grid[data-columns="2"],
    .similar-grid[data-columns="3"],
    .similar-grid[data-columns="4"] {
        grid-template-columns: 1fr;
    }
}
</style>