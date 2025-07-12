<?php
/**
 * Single Parfume Template - Without Tabs
 */

get_header();

// Get post data
$post_id = get_the_ID();
$brands = get_the_terms($post_id, 'marki');
$notes = get_the_terms($post_id, 'notes');
$perfumers = get_the_terms($post_id, 'perfumer');
$gender_terms = get_the_terms($post_id, 'gender');
$aroma_types = get_the_terms($post_id, 'aroma_type');
$seasons = get_the_terms($post_id, 'season');
$intensities = get_the_terms($post_id, 'intensity');

// Get meta data
$rating = get_post_meta($post_id, '_parfume_rating', true);
$release_year = get_post_meta($post_id, '_parfume_release_year', true);
$longevity = get_post_meta($post_id, '_parfume_longevity', true);
$sillage = get_post_meta($post_id, '_parfume_sillage', true);
$bottle_size = get_post_meta($post_id, '_parfume_bottle_size', true);

// Aroma chart data
$aroma_chart = array(
    'freshness' => get_post_meta($post_id, '_parfume_freshness', true) ?: 0,
    'sweetness' => get_post_meta($post_id, '_parfume_sweetness', true) ?: 0,
    'intensity' => get_post_meta($post_id, '_parfume_intensity', true) ?: 0,
    'warmth' => get_post_meta($post_id, '_parfume_warmth', true) ?: 0,
);

// Pros and cons
$pros_raw = get_post_meta($post_id, '_parfume_pros', true);
$cons_raw = get_post_meta($post_id, '_parfume_cons', true);
$pros_cons = array(
    'pros' => !empty($pros_raw) ? array_filter(array_map('trim', explode("\n", $pros_raw))) : array(),
    'cons' => !empty($cons_raw) ? array_filter(array_map('trim', explode("\n", $cons_raw))) : array(),
);

// Prepare display variables
$brand_names = array();
if ($brands) {
    foreach ($brands as $brand) {
        $brand_names[] = '<a href="' . get_term_link($brand) . '">' . esc_html($brand->name) . '</a>';
    }
}

$gender_text = '';
if ($gender_terms) {
    $gender_names = array();
    foreach ($gender_terms as $gender) {
        $gender_names[] = esc_html($gender->name);
    }
    $gender_text = implode(', ', $gender_names);
}

$aroma_types_list = array();
if ($aroma_types) {
    foreach ($aroma_types as $aroma_type) {
        $aroma_types_list[] = '<a href="' . get_term_link($aroma_type) . '">' . esc_html($aroma_type->name) . '</a>';
    }
}

$seasons_list = array();
if ($seasons) {
    foreach ($seasons as $season) {
        $seasons_list[] = '<a href="' . get_term_link($season) . '">' . esc_html($season->name) . '</a>';
    }
}

$intensities_list = array();
if ($intensities) {
    foreach ($intensities as $intensity) {
        $intensities_list[] = '<a href="' . get_term_link($intensity) . '">' . esc_html($intensity->name) . '</a>';
    }
}

// Group notes by type if available
$grouped_notes = array();
if ($notes) {
    foreach ($notes as $note) {
        $note_group = get_term_meta($note->term_id, 'note_group', true);
        $group_name = !empty($note_group) ? $note_group : 'Други нотки';
        if (!isset($grouped_notes[$group_name])) {
            $grouped_notes[$group_name] = array();
        }
        $grouped_notes[$group_name][] = $note;
    }
}
?>

<article id="post-<?php the_ID(); ?>" <?php post_class('parfume-single'); ?>>
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
                    
                    <?php if (!empty($brand_names)): ?>
                        <div class="parfume-brand">
                            <span class="brand-label">Марка:</span>
                            <?php echo implode(', ', $brand_names); ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($rating)): ?>
                        <div class="parfume-rating">
                            <div class="rating-stars" data-rating="<?php echo esc_attr($rating); ?>">
                                <?php
                                for ($i = 1; $i <= 5; $i++) {
                                    $class = $i <= round($rating) ? 'star filled' : 'star';
                                    echo '<span class="' . $class . '">★</span>';
                                }
                                ?>
                            </div>
                            <span class="rating-number"><?php echo esc_html($rating); ?></span>
                            <span class="rating-count">/5</span>
                        </div>
                    <?php endif; ?>
                    
                    <div class="parfume-meta">
                        <?php if (!empty($gender_text)): ?>
                            <div class="meta-item">
                                <strong>Пол:</strong>
                                <span><?php echo esc_html($gender_text); ?></span>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($release_year)): ?>
                            <div class="meta-item">
                                <strong>Година на издаване:</strong>
                                <span><?php echo esc_html($release_year); ?></span>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($aroma_types_list)): ?>
                            <div class="meta-item">
                                <strong>Вид аромат:</strong>
                                <span><?php echo implode(', ', $aroma_types_list); ?></span>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($seasons_list)): ?>
                            <div class="meta-item">
                                <strong>Сезон:</strong>
                                <span><?php echo implode(', ', $seasons_list); ?></span>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($intensities_list)): ?>
                            <div class="meta-item">
                                <strong>Интензивност:</strong>
                                <span><?php echo implode(', ', $intensities_list); ?></span>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($longevity)): ?>
                            <div class="meta-item">
                                <strong>Издръжливост:</strong>
                                <span><?php echo esc_html($longevity); ?></span>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($sillage)): ?>
                            <div class="meta-item">
                                <strong>Силаж:</strong>
                                <span><?php echo esc_html($sillage); ?></span>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($bottle_size)): ?>
                            <div class="meta-item">
                                <strong>Размер на шишето:</strong>
                                <span><?php echo esc_html($bottle_size); ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="parfume-actions">
                        <a href="#" class="action-button primary add-to-comparison" data-post-id="<?php echo get_the_ID(); ?>" data-post-title="<?php echo esc_attr(get_the_title()); ?>">
                            Сравни
                        </a>
                        <a href="#" class="action-button secondary share-button">
                            Сподели
                        </a>
                    </div>
                </div>
            </header>
            
            <div class="parfume-content">
                <!-- Description Section -->
                <section id="description" class="content-section">
                    <h2>Описание</h2>
                    <div class="section-content">
                        <?php the_content(); ?>
                    </div>
                </section>
                
                <!-- Notes Section -->
                <?php if (!empty($notes) || !empty($grouped_notes)): ?>
                    <section id="notes" class="content-section">
                        <h2>Ароматни нотки</h2>
                        <div class="section-content">
                            <?php if (!empty($grouped_notes)): ?>
                                <div class="main-notes-groups">
                                    <h3>Основни ароматни нотки</h3>
                                    <div class="notes-groups-grid">
                                        <?php foreach ($grouped_notes as $group => $group_notes): ?>
                                            <div class="note-group">
                                                <h4 class="group-title"><?php echo esc_html($group); ?></h4>
                                                <ul class="group-notes">
                                                    <?php foreach ($group_notes as $note): ?>
                                                        <li>
                                                            <a href="<?php echo get_term_link($note); ?>">
                                                                <?php echo esc_html($note->name); ?>
                                                            </a>
                                                        </li>
                                                    <?php endforeach; ?>
                                                </ul>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($notes)): ?>
                                <div class="notes-pyramid">
                                    <h3>Пирамида на ароматите</h3>
                                    <div class="pyramid-levels">
                                        <div class="pyramid-level top-notes">
                                            <h4>Горни нотки</h4>
                                            <ul>
                                                <?php foreach (array_slice($notes, 0, 3) as $note): ?>
                                                    <li><a href="<?php echo get_term_link($note); ?>"><?php echo esc_html($note->name); ?></a></li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </div>
                                        
                                        <div class="pyramid-level middle-notes">
                                            <h4>Средни нотки</h4>
                                            <ul>
                                                <?php foreach (array_slice($notes, 3, 3) as $note): ?>
                                                    <li><a href="<?php echo get_term_link($note); ?>"><?php echo esc_html($note->name); ?></a></li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </div>
                                        
                                        <div class="pyramid-level base-notes">
                                            <h4>Базови нотки</h4>
                                            <ul>
                                                <?php foreach (array_slice($notes, 6) as $note): ?>
                                                    <li><a href="<?php echo get_term_link($note); ?>"><?php echo esc_html($note->name); ?></a></li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </section>
                <?php endif; ?>
                
                <!-- Perfumer Section -->
                <?php if (!empty($perfumers)): ?>
                    <section id="perfumer" class="content-section">
                        <h2>Парфюмерист</h2>
                        <div class="section-content">
                            <div class="perfumer-info">
                                <?php foreach ($perfumers as $perfumer): ?>
                                    <div class="perfumer-card">
                                        <div class="perfumer-photo">
                                            <?php
                                            $perfumer_image_id = get_term_meta($perfumer->term_id, 'perfumer-image-id', true);
                                            if ($perfumer_image_id) {
                                                echo wp_get_attachment_image($perfumer_image_id, 'thumbnail');
                                            }
                                            ?>
                                        </div>
                                        <div class="perfumer-details">
                                            <h3><?php echo esc_html($perfumer->name); ?></h3>
                                            <?php if (!empty($perfumer->description)): ?>
                                                <div class="perfumer-bio">
                                                    <?php echo wpautop(wp_kses_post($perfumer->description)); ?>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <div class="perfumer-other-works">
                                                <h4>Други творби</h4>
                                                <?php
                                                $other_works = new WP_Query(array(
                                                    'post_type' => 'parfume',
                                                    'posts_per_page' => 5,
                                                    'post__not_in' => array(get_the_ID()),
                                                    'tax_query' => array(
                                                        array(
                                                            'taxonomy' => 'perfumer',
                                                            'field' => 'term_id',
                                                            'terms' => $perfumer->term_id,
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
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </section>
                <?php endif; ?>
                
                <!-- Aroma Chart Section -->
                <?php if (array_sum($aroma_chart) > 0): ?>
                    <section id="aroma-chart" class="content-section">
                        <h2>Графика на аромата</h2>
                        <div class="section-content">
                            <div class="aroma-chart">
                                <div class="chart-container">
                                    <div class="chart-item">
                                        <span class="chart-label">Свежест</span>
                                        <div class="chart-bar">
                                            <div class="chart-fill" style="width: <?php echo ($aroma_chart['freshness'] * 10); ?>%"></div>
                                        </div>
                                        <span class="chart-value"><?php echo $aroma_chart['freshness']; ?>/10</span>
                                    </div>
                                    
                                    <div class="chart-item">
                                        <span class="chart-label">Сладост</span>
                                        <div class="chart-bar">
                                            <div class="chart-fill" style="width: <?php echo ($aroma_chart['sweetness'] * 10); ?>%"></div>
                                        </div>
                                        <span class="chart-value"><?php echo $aroma_chart['sweetness']; ?>/10</span>
                                    </div>
                                    
                                    <div class="chart-item">
                                        <span class="chart-label">Интензивност</span>
                                        <div class="chart-bar">
                                            <div class="chart-fill" style="width: <?php echo ($aroma_chart['intensity'] * 10); ?>%"></div>
                                        </div>
                                        <span class="chart-value"><?php echo $aroma_chart['intensity']; ?>/10</span>
                                    </div>
                                    
                                    <div class="chart-item">
                                        <span class="chart-label">Топлота</span>
                                        <div class="chart-bar">
                                            <div class="chart-fill" style="width: <?php echo ($aroma_chart['warmth'] * 10); ?>%"></div>
                                        </div>
                                        <span class="chart-value"><?php echo $aroma_chart['warmth']; ?>/10</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>
                <?php endif; ?>
                
                <!-- Pros and Cons Section -->
                <?php if (!empty($pros_cons['pros']) || !empty($pros_cons['cons'])): ?>
                    <section id="pros-cons" class="content-section">
                        <h2>Предимства и недостатъци</h2>
                        <div class="section-content">
                            <div class="pros-cons-grid">
                                <?php if (!empty($pros_cons['pros'])): ?>
                                    <div class="pros-section">
                                        <h4>Предимства</h4>
                                        <ul class="pros">
                                            <?php foreach ($pros_cons['pros'] as $pro): ?>
                                                <?php if (trim($pro)): ?>
                                                    <li><?php echo esc_html(trim($pro)); ?></li>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($pros_cons['cons'])): ?>
                                    <div class="cons-section">
                                        <h4>Недостатъци</h4>
                                        <ul class="cons">
                                            <?php foreach ($pros_cons['cons'] as $con): ?>
                                                <?php if (trim($con)): ?>
                                                    <li><?php echo esc_html(trim($con)); ?></li>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </section>
                <?php endif; ?>
                
                <!-- Reviews Section -->
                <section id="reviews" class="content-section">
                    <h2>Ревюта и коментари</h2>
                    <div class="section-content">
                        <?php
                        // Display WordPress comments
                        if (comments_open() || get_comments_number()) {
                            comments_template();
                        } else {
                            echo '<p>Коментарите са затворени за този парфюм.</p>';
                        }
                        ?>
                    </div>
                </section>
            </div>
            
            <!-- Related Products -->
            <div class="parfume-related">
                <?php
                // Show other perfumes from the same brand
                if (!empty($brands)):
                    $brand_ids = array();
                    foreach ($brands as $brand) {
                        $brand_ids[] = $brand->term_id;
                    }
                    
                    $brand_perfumes = new WP_Query(array(
                        'post_type' => 'parfume',
                        'posts_per_page' => 4,
                        'post__not_in' => array(get_the_ID()),
                        'tax_query' => array(
                            array(
                                'taxonomy' => 'marki',
                                'field' => 'term_id',
                                'terms' => $brand_ids,
                                'operator' => 'IN',
                            ),
                        ),
                    ));
                    
                    if ($brand_perfumes->have_posts()):
                ?>
                        <div class="related-section brand-perfumes">
                            <h3>Други парфюми от същата марка</h3>
                            <div class="related-grid">
                                <?php while ($brand_perfumes->have_posts()): $brand_perfumes->the_post(); ?>
                                    <div class="related-item">
                                        <a href="<?php the_permalink(); ?>">
                                            <?php if (has_post_thumbnail()): ?>
                                                <div class="related-thumbnail">
                                                    <?php the_post_thumbnail('thumbnail'); ?>
                                                </div>
                                            <?php endif; ?>
                                            <div class="related-content">
                                                <h4><?php the_title(); ?></h4>
                                                <?php
                                                $related_brands = get_the_terms(get_the_ID(), 'marki');
                                                if ($related_brands): ?>
                                                    <div class="related-brand"><?php echo esc_html($related_brands[0]->name); ?></div>
                                                <?php endif; ?>
                                                
                                                <?php
                                                $related_rating = get_post_meta(get_the_ID(), '_parfume_rating', true);
                                                if ($related_rating): ?>
                                                    <div class="related-rating">
                                                        <span class="stars">
                                                            <?php
                                                            for ($i = 1; $i <= 5; $i++) {
                                                                echo $i <= round($related_rating) ? '★' : '☆';
                                                            }
                                                            ?>
                                                        </span>
                                                        <span><?php echo esc_html($related_rating); ?></span>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </a>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        </div>
                <?php
                    endif;
                    wp_reset_postdata();
                endif;
                
                // Show similar perfumes based on notes
                if (!empty($notes)):
                    $note_ids = array();
                    foreach ($notes as $note) {
                        $note_ids[] = $note->term_id;
                    }
                    
                    $similar_perfumes = new WP_Query(array(
                        'post_type' => 'parfume',
                        'posts_per_page' => 4,
                        'post__not_in' => array(get_the_ID()),
                        'tax_query' => array(
                            array(
                                'taxonomy' => 'notes',
                                'field' => 'term_id',
                                'terms' => $note_ids,
                                'operator' => 'IN',
                            ),
                        ),
                    ));
                    
                    if ($similar_perfumes->have_posts()):
                ?>
                        <div class="related-section similar-perfumes">
                            <h3>Подобни аромати</h3>
                            <div class="related-grid">
                                <?php while ($similar_perfumes->have_posts()): $similar_perfumes->the_post(); ?>
                                    <div class="related-item">
                                        <a href="<?php the_permalink(); ?>">
                                            <?php if (has_post_thumbnail()): ?>
                                                <div class="related-thumbnail">
                                                    <?php the_post_thumbnail('thumbnail'); ?>
                                                </div>
                                            <?php endif; ?>
                                            <div class="related-content">
                                                <h4><?php the_title(); ?></h4>
                                                <?php
                                                $related_brands = get_the_terms(get_the_ID(), 'marki');
                                                if ($related_brands): ?>
                                                    <div class="related-brand"><?php echo esc_html($related_brands[0]->name); ?></div>
                                                <?php endif; ?>
                                                
                                                <?php
                                                $related_rating = get_post_meta(get_the_ID(), '_parfume_rating', true);
                                                if ($related_rating): ?>
                                                    <div class="related-rating">
                                                        <span class="stars">
                                                            <?php
                                                            for ($i = 1; $i <= 5; $i++) {
                                                                echo $i <= round($related_rating) ? '★' : '☆';
                                                            }
                                                            ?>
                                                        </span>
                                                        <span><?php echo esc_html($related_rating); ?></span>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </a>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        </div>
                <?php
                    endif;
                    wp_reset_postdata();
                endif;
                ?>
            </div>
        </div>
        
        <!-- Fixed sidebar with stores -->
        <aside class="parfume-stores-sidebar">
            <?php echo do_shortcode('[parfume_stores]'); ?>
        </aside>
    </div>
</article>

<?php
get_footer();
?>