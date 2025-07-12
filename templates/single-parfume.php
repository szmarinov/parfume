<?php
get_header();

while (have_posts()): the_post();
    $brands = wp_get_post_terms(get_the_ID(), 'marki', array('fields' => 'names'));
    $notes = wp_get_post_terms(get_the_ID(), 'notes', array('fields' => 'names'));
    $perfumers = wp_get_post_terms(get_the_ID(), 'perfumer', array('fields' => 'names'));
    $aroma_types = wp_get_post_terms(get_the_ID(), 'aroma_type', array('fields' => 'names'));
    $seasons = wp_get_post_terms(get_the_ID(), 'season', array('fields' => 'names'));
    $intensities = wp_get_post_terms(get_the_ID(), 'intensity', array('fields' => 'names'));
    $genders = wp_get_post_terms(get_the_ID(), 'gender', array('fields' => 'names'));
    
    $rating = get_post_meta(get_the_ID(), '_parfume_rating', true);
    $gender_text = get_post_meta(get_the_ID(), '_parfume_gender', true);
    $release_year = get_post_meta(get_the_ID(), '_parfume_release_year', true);
    $longevity = get_post_meta(get_the_ID(), '_parfume_longevity', true);
    $sillage = get_post_meta(get_the_ID(), '_parfume_sillage', true);
    $bottle_size = get_post_meta(get_the_ID(), '_parfume_bottle_size', true);
?>

<article class="parfume-single">
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
                    <?php _e('Brand:', 'parfume-reviews'); ?>
                    <?php echo implode(', ', $brands); ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($rating)): ?>
                <div class="parfume-rating">
                    <?php echo parfume_reviews_get_rating_stars($rating); ?>
                    <span class="rating-number"><?php echo number_format($rating, 1); ?>/5</span>
                </div>
            <?php endif; ?>
            
            <div class="parfume-meta">
                <?php if (!empty($gender_text)): ?>
                    <div class="meta-item">
                        <strong><?php _e('Gender:', 'parfume-reviews'); ?></strong>
                        <?php echo esc_html($gender_text); ?>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($release_year)): ?>
                    <div class="meta-item">
                        <strong><?php _e('Release Year:', 'parfume-reviews'); ?></strong>
                        <?php echo esc_html($release_year); ?>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($aroma_types)): ?>
                    <div class="meta-item">
                        <strong><?php _e('Aroma Type:', 'parfume-reviews'); ?></strong>
                        <?php echo implode(', ', $aroma_types); ?>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($seasons)): ?>
                    <div class="meta-item">
                        <strong><?php _e('Season:', 'parfume-reviews'); ?></strong>
                        <?php echo implode(', ', $seasons); ?>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($intensities)): ?>
                    <div class="meta-item">
                        <strong><?php _e('Intensity:', 'parfume-reviews'); ?></strong>
                        <?php echo implode(', ', $intensities); ?>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($longevity)): ?>
                    <div class="meta-item">
                        <strong><?php _e('Longevity:', 'parfume-reviews'); ?></strong>
                        <?php echo esc_html($longevity); ?>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($sillage)): ?>
                    <div class="meta-item">
                        <strong><?php _e('Sillage:', 'parfume-reviews'); ?></strong>
                        <?php echo esc_html($sillage); ?>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($bottle_size)): ?>
                    <div class="meta-item">
                        <strong><?php _e('Bottle Size:', 'parfume-reviews'); ?></strong>
                        <?php echo esc_html($bottle_size); ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="parfume-actions">
                <?php echo parfume_reviews_get_comparison_button(get_the_ID()); ?>
                <?php echo parfume_reviews_get_collections_dropdown(get_the_ID()); ?>
            </div>
        </div>
    </header>
    
    <div class="parfume-content">
        <div class="parfume-tabs">
            <ul class="tabs-nav">
                <li><a href="#description"><?php _e('Description', 'parfume-reviews'); ?></a></li>
                <li><a href="#notes"><?php _e('Notes', 'parfume-reviews'); ?></a></li>
                <?php if (!empty($perfumers)): ?>
                    <li><a href="#perfumer"><?php _e('Perfumer', 'parfume-reviews'); ?></a></li>
                <?php endif; ?>
                <li><a href="#reviews"><?php _e('Reviews', 'parfume-reviews'); ?></a></li>
            </ul>
            
            <div class="tabs-content">
                <div id="description" class="tab-panel">
                    <?php the_content(); ?>
                </div>
                
                <div id="notes" class="tab-panel">
                    <?php if (!empty($notes)): ?>
                        <div class="notes-pyramid">
                            <div class="pyramid-level top-notes">
                                <h3><?php _e('Top Notes', 'parfume-reviews'); ?></h3>
                                <ul>
                                    <?php foreach (array_slice($notes, 0, 3) as $note): ?>
                                        <li><a href="<?php echo get_term_link($note, 'notes'); ?>"><?php echo $note; ?></a></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                            
                            <div class="pyramid-level middle-notes">
                                <h3><?php _e('Middle Notes', 'parfume-reviews'); ?></h3>
                                <ul>
                                    <?php foreach (array_slice($notes, 3, 3) as $note): ?>
                                        <li><a href="<?php echo get_term_link($note, 'notes'); ?>"><?php echo $note; ?></a></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                            
                            <div class="pyramid-level base-notes">
                                <h3><?php _e('Base Notes', 'parfume-reviews'); ?></h3>
                                <ul>
                                    <?php foreach (array_slice($notes, 6) as $note): ?>
                                        <li><a href="<?php echo get_term_link($note, 'notes'); ?>"><?php echo $note; ?></a></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                
                <?php if (!empty($perfumers)): ?>
                    <div id="perfumer" class="tab-panel">
                        <div class="perfumer-info">
                            <?php foreach ($perfumers as $perfumer): ?>
                                <?php
                                $term = get_term_by('name', $perfumer, 'perfumer');
                                $photo = parfume_reviews_get_perfumer_photo($term->term_id);
                                ?>
                                
                                <div class="perfumer-card">
                                    <?php if ($photo): ?>
                                        <div class="perfumer-photo">
                                            <?php echo $photo; ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <h3><?php echo $perfumer; ?></h3>
                                    
                                    <div class="perfumer-bio">
                                        <?php echo term_description($term->term_id, 'perfumer'); ?>
                                    </div>
                                    
                                    <div class="perfumer-other-works">
                                        <h4><?php _e('Other Works', 'parfume-reviews'); ?></h4>
                                        <?php
                                        $other_works = new \WP_Query(array(
                                            'post_type' => 'parfume',
                                            'posts_per_page' => 5,
                                            'post__not_in' => array(get_the_ID()),
                                            'tax_query' => array(
                                                array(
                                                    'taxonomy' => 'perfumer',
                                                    'field' => 'term_id',
                                                    'terms' => $term->term_id,
                                                ),
                                            ),
                                        ));
                                        
                                        if ($other_works->have_posts()): ?>
                                            <ul>
                                                <?php while ($other_works->have_posts()): $other_works->the_post(); ?>
                                                    <li><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></li>
                                                <?php endwhile; ?>
                                            </ul>
                                        <?php endif;
                                        
                                        wp_reset_postdata();
                                        ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <div id="reviews" class="tab-panel">
                    <?php comments_template(); ?>
                </div>
            </div>
        </div>
        
        <aside class="parfume-sidebar">
            <?php echo do_shortcode('[parfume_stores]'); ?>
            
            <div class="price-history">
                <h3><?php _e('Price History', 'parfume-reviews'); ?></h3>
                <?php
                $history = parfume_reviews_get_price_history(get_the_ID());
                
                if (!empty($history)): ?>
                    <table>
                        <thead>
                            <tr>
                                <th><?php _e('Date', 'parfume-reviews'); ?></th>
                                <th><?php _e('Price', 'parfume-reviews'); ?></th>
                                <th><?php _e('Store', 'parfume-reviews'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($history as $item): ?>
                                <tr>
                                    <td><?php echo date_i18n(get_option('date_format'), strtotime($item['date'])); ?></td>
                                    <td><?php echo esc_html($item['price']); ?></td>
                                    <td><?php echo esc_html($item['store']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p><?php _e('No price history available.', 'parfume-reviews'); ?></p>
                <?php endif; ?>
            </div>
        </aside>
    </div>
    
    <div class="parfume-related">
        <?php echo do_shortcode('[parfume_similar]'); ?>
        <?php echo do_shortcode('[parfume_brand_products]'); ?>
        <?php echo do_shortcode('[parfume_recently_viewed]'); ?>
    </div>
</article>

<?php
endwhile;

get_footer();
?>