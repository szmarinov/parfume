<?php
get_header();

while (have_posts()): the_post();
    $brands = wp_get_post_terms(get_the_ID(), 'marki', ['fields' => 'names']);
    $notes = wp_get_post_terms(get_the_ID(), 'notes');
    $perfumers = wp_get_post_terms(get_the_ID(), 'perfumer', ['fields' => 'names']);
    
    $rating = get_post_meta(get_the_ID(), '_parfume_rating', true);
    $gender = get_post_meta(get_the_ID(), '_parfume_gender', true);
    $release_year = get_post_meta(get_the_ID(), '_parfume_release_year', true);
    $longevity = get_post_meta(get_the_ID(), '_parfume_longevity', true);
    $sillage = get_post_meta(get_the_ID(), '_parfume_sillage', true);
?>

<article class="parfume-single">
    <div class="parfume-container">
        <div class="parfume-main-content">
            <header class="parfume-header">
                <div class="parfume-gallery">
                    <?php if (has_post_thumbnail()): ?>
                        <div class="parfume-featured-image">
                            <?php the_post_thumbnail('large'); ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="parfume-summary">
                    <h1 class="parfume-title"><?php the_title(); ?></h1>
                    
                    <?php if (!empty($brands)): ?>
                        <div class="parfume-brand">
                            <span class="brand-label">Марка:</span>
                            <?php echo implode(', ', $brands); ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($rating)): ?>
                        <div class="parfume-rating">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <span class="star <?php echo $i <= round($rating) ? 'filled' : ''; ?>">★</span>
                            <?php endfor; ?>
                            <span class="rating-number"><?php echo number_format($rating, 1); ?>/5</span>
                        </div>
                    <?php endif; ?>
                    
                    <div class="parfume-meta">
                        <?php if (!empty($gender)): ?>
                            <div class="meta-item">
                                <strong>Пол:</strong> <?php echo esc_html($gender); ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($release_year)): ?>
                            <div class="meta-item">
                                <strong>Година:</strong> <?php echo esc_html($release_year); ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($longevity)): ?>
                            <div class="meta-item">
                                <strong>Издръжливост:</strong> <?php echo esc_html($longevity); ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($sillage)): ?>
                            <div class="meta-item">
                                <strong>Силаж:</strong> <?php echo esc_html($sillage); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </header>
            
            <div class="parfume-content">
                <div class="parfume-description">
                    <h2>Описание</h2>
                    <?php the_content(); ?>
                </div>
                
                <?php if (!empty($notes)): ?>
                    <div class="parfume-notes">
                        <h2>Ароматни нотки</h2>
                        <div class="notes-list">
                            <?php foreach ($notes as $note): ?>
                                <a href="<?php echo get_term_link($note); ?>" class="note-tag">
                                    <?php echo esc_html($note->name); ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($perfumers)): ?>
                    <div class="parfume-perfumer">
                        <h2>Парфюмерист</h2>
                        <p><?php echo implode(', ', $perfumers); ?></p>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Related perfumes -->
            <div class="parfume-related">
                <?php
                if (!empty($brands)):
                    $brand_term = get_term_by('name', $brands[0], 'marki');
                    if ($brand_term):
                        $related = new WP_Query([
                            'post_type' => 'parfume',
                            'posts_per_page' => 4,
                            'post__not_in' => [get_the_ID()],
                            'tax_query' => [[
                                'taxonomy' => 'marki',
                                'field' => 'term_id',
                                'terms' => $brand_term->term_id,
                            ]],
                        ]);
                        
                        if ($related->have_posts()):
                ?>
                            <h2>Други парфюми от <?php echo esc_html($brands[0]); ?></h2>
                            <div class="related-grid">
                                <?php while ($related->have_posts()): $related->the_post(); ?>
                                    <div class="related-item">
                                        <a href="<?php the_permalink(); ?>">
                                            <?php if (has_post_thumbnail()): ?>
                                                <?php the_post_thumbnail('thumbnail'); ?>
                                            <?php endif; ?>
                                            <h4><?php the_title(); ?></h4>
                                        </a>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                <?php
                        endif;
                        wp_reset_postdata();
                    endif;
                endif;
                ?>
            </div>
        </div>
    </div>
</article>

<style>
.parfume-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.parfume-header {
    display: flex;
    gap: 30px;
    margin-bottom: 40px;
}

.parfume-gallery {
    flex: 0 0 40%;
}

.parfume-featured-image img {
    max-width: 100%;
    height: auto;
    border-radius: 8px;
}

.parfume-summary {
    flex: 1;
}

.parfume-title {
    font-size: 2.2em;
    margin-bottom: 15px;
    color: #333;
}

.parfume-brand {
    font-size: 1.2em;
    color: #666;
    margin-bottom: 20px;
}

.parfume-rating {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 25px;
}

.star {
    font-size: 1.5em;
    color: #ddd;
}

.star.filled {
    color: #ffc107;
}

.rating-number {
    font-size: 1.2em;
    font-weight: bold;
}

.parfume-meta {
    display: grid;
    gap: 10px;
}

.meta-item {
    padding: 8px 0;
    border-bottom: 1px solid #eee;
}

.parfume-content {
    margin-bottom: 40px;
}

.parfume-content h2 {
    border-bottom: 2px solid #0073aa;
    padding-bottom: 10px;
    margin-bottom: 20px;
}

.notes-list {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
}

.note-tag {
    background: #e3f2fd;
    color: #1976d2;
    padding: 6px 12px;
    border-radius: 15px;
    text-decoration: none;
    font-size: 0.9em;
    transition: all 0.3s ease;
}

.note-tag:hover {
    background: #1976d2;
    color: white;
}

.related-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.related-item {
    text-align: center;
}

.related-item a {
    text-decoration: none;
    color: inherit;
}

.related-item img {
    width: 100%;
    height: 150px;
    object-fit: cover;
    border-radius: 8px;
    margin-bottom: 10px;
}

.related-item h4 {
    margin: 0;
    font-size: 1em;
}

@media (max-width: 768px) {
    .parfume-header {
        flex-direction: column;
    }
    
    .parfume-gallery {
        margin-bottom: 20px;
    }
    
    .related-grid {
        grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    }
}
</style>

<?php
endwhile;
get_footer();
?>