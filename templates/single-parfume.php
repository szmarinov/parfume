<?php
get_header();

while (have_posts()): the_post();
    $brands = wp_get_post_terms(get_the_ID(), 'marki', array('fields' => 'names'));
    $notes = wp_get_post_terms(get_the_ID(), 'notes');
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
    
    // Get aroma chart data
    $aroma_chart = parfume_reviews_get_aroma_chart(get_the_ID());
    
    // Get pros and cons
    $pros_cons = parfume_reviews_get_pros_cons(get_the_ID());
    
    // Get main notes grouped
    $grouped_notes = parfume_reviews_get_main_notes_by_group(get_the_ID());
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
                            <?php echo parfume_reviews_get_rating_stars($rating); ?>
                            <span class="rating-number"><?php echo number_format($rating, 1); ?>/5</span>
                        </div>
                    <?php endif; ?>
                    
                    <div class="parfume-meta">
                        <?php if (!empty($gender_text)): ?>
                            <div class="meta-item">
                                <strong>Пол:</strong>
                                <?php echo esc_html($gender_text); ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($release_year)): ?>
                            <div class="meta-item">
                                <strong>Година на издаване:</strong>
                                <?php echo esc_html($release_year); ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($aroma_types)): ?>
                            <div class="meta-item">
                                <strong>Вид аромат:</strong>
                                <?php echo implode(', ', $aroma_types); ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($seasons)): ?>
                            <div class="meta-item">
                                <strong>Сезон:</strong>
                                <?php echo implode(', ', $seasons); ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($intensities)): ?>
                            <div class="meta-item">
                                <strong>Интензивност:</strong>
                                <?php echo implode(', ', $intensities); ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($longevity)): ?>
                            <div class="meta-item">
                                <strong>Издръжливост:</strong>
                                <?php echo esc_html($longevity); ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($sillage)): ?>
                            <div class="meta-item">
                                <strong>Силаж:</strong>
                                <?php echo esc_html($sillage); ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($bottle_size)): ?>
                            <div class="meta-item">
                                <strong>Размер на шишето:</strong>
                                <?php echo esc_html($bottle_size); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </header>
            
            <div class="parfume-content">
                <div class="parfume-tabs">
                    <ul class="tabs-nav">
                        <li><a href="#description">Описание</a></li>
                        <li><a href="#notes">Нотки</a></li>
                        <?php if (!empty($perfumers)): ?>
                            <li><a href="#perfumer">Парфюмерист</a></li>
                        <?php endif; ?>
                        <?php if (array_sum($aroma_chart) > 0): ?>
                            <li><a href="#aroma-chart">Графика на аромата</a></li>
                        <?php endif; ?>
                        <?php if (!empty($pros_cons['pros']) || !empty($pros_cons['cons'])): ?>
                            <li><a href="#pros-cons">Предимства и недостатъци</a></li>
                        <?php endif; ?>
                        <li><a href="#reviews">Ревюта</a></li>
                    </ul>
                    
                    <div class="tabs-content">
                        <div id="description" class="tab-panel">
                            <?php the_content(); ?>
                        </div>
                        
                        <div id="notes" class="tab-panel">
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
                                                    <li><a href="<?php echo get_term_link($note); ?>"><?php echo $note->name; ?></a></li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </div>
                                        
                                        <div class="pyramid-level middle-notes">
                                            <h4>Средни нотки</h4>
                                            <ul>
                                                <?php foreach (array_slice($notes, 3, 3) as $note): ?>
                                                    <li><a href="<?php echo get_term_link($note); ?>"><?php echo $note->name; ?></a></li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </div>
                                        
                                        <div class="pyramid-level base-notes">
                                            <h4>Базови нотки</h4>
                                            <ul>
                                                <?php foreach (array_slice($notes, 6) as $note): ?>
                                                    <li><a href="<?php echo get_term_link($note); ?>"><?php echo $note->name; ?></a></li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </div>
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
                                            
                                            <div class="perfumer-details">
                                                <h3><?php echo $perfumer; ?></h3>
                                                
                                                <div class="perfumer-bio">
                                                    <?php echo term_description($term->term_id, 'perfumer'); ?>
                                                </div>
                                                
                                                <div class="perfumer-other-works">
                                                    <h4>Други творби</h4>
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
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (array_sum($aroma_chart) > 0): ?>
                            <div id="aroma-chart" class="tab-panel">
                                <div class="aroma-chart">
                                    <h3>Графика на аромата</h3>
                                    <div class="chart-container">
                                        <div class="chart-item">
                                            <label>Свежест</label>
                                            <div class="chart-bar">
                                                <div class="chart-fill" style="width: <?php echo ($aroma_chart['freshness'] * 10); ?>%"></div>
                                                <span class="chart-value"><?php echo $aroma_chart['freshness']; ?>/10</span>
                                            </div>
                                        </div>
                                        
                                        <div class="chart-item">
                                            <label>Сладост</label>
                                            <div class="chart-bar">
                                                <div class="chart-fill" style="width: <?php echo ($aroma_chart['sweetness'] * 10); ?>%"></div>
                                                <span class="chart-value"><?php echo $aroma_chart['sweetness']; ?>/10</span>
                                            </div>
                                        </div>
                                        
                                        <div class="chart-item">
                                            <label>Интензивност</label>
                                            <div class="chart-bar">
                                                <div class="chart-fill" style="width: <?php echo ($aroma_chart['intensity'] * 10); ?>%"></div>
                                                <span class="chart-value"><?php echo $aroma_chart['intensity']; ?>/10</span>
                                            </div>
                                        </div>
                                        
                                        <div class="chart-item">
                                            <label>Топлота</label>
                                            <div class="chart-bar">
                                                <div class="chart-fill" style="width: <?php echo ($aroma_chart['warmth'] * 10); ?>%"></div>
                                                <span class="chart-value"><?php echo $aroma_chart['warmth']; ?>/10</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($pros_cons['pros']) || !empty($pros_cons['cons'])): ?>
                            <div id="pros-cons" class="tab-panel">
                                <div class="pros-cons">
                                    <?php if (!empty($pros_cons['pros'])): ?>
                                        <div class="pros">
                                            <h3>Предимства</h3>
                                            <ul>
                                                <?php foreach ($pros_cons['pros'] as $pro): ?>
                                                    <?php if (trim($pro)): ?>
                                                        <li><?php echo esc_html(trim($pro)); ?></li>
                                                    <?php endif; ?>
                                                <?php endforeach; ?>
                                            </ul>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($pros_cons['cons'])): ?>
                                        <div class="cons">
                                            <h3>Недостатъци</h3>
                                            <ul>
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
                        <?php endif; ?>
                        
                        <div id="reviews" class="tab-panel">
                            <?php comments_template(); ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Similar and brand perfumes -->
            <div class="parfume-related">
                <?php
                // Show other perfumes from same brand
                if (!empty($brands)):
                    $brand_term = get_term_by('name', $brands[0], 'marki');
                    if ($brand_term):
                        $brand_perfumes = new \WP_Query(array(
                            'post_type' => 'parfume',
                            'posts_per_page' => 4,
                            'post__not_in' => array(get_the_ID()),
                            'tax_query' => array(
                                array(
                                    'taxonomy' => 'marki',
                                    'field' => 'term_id',
                                    'terms' => $brand_term->term_id,
                                ),
                            ),
                        ));
                        
                        if ($brand_perfumes->have_posts()):
                ?>
                            <div class="brand-perfumes">
                                <h3>Други парфюми от <?php echo esc_html($brands[0]); ?></h3>
                                <div class="related-grid">
                                    <?php while ($brand_perfumes->have_posts()): $brand_perfumes->the_post(); ?>
                                        <div class="related-item">
                                            <a href="<?php the_permalink(); ?>">
                                                <?php if (has_post_thumbnail()): ?>
                                                    <div class="related-thumbnail">
                                                        <?php the_post_thumbnail('thumbnail'); ?>
                                                    </div>
                                                <?php endif; ?>
                                                <h4><?php the_title(); ?></h4>
                                            </a>
                                        </div>
                                    <?php endwhile; ?>
                                </div>
                            </div>
                <?php
                        endif;
                        wp_reset_postdata();
                    endif;
                endif;
                
                // Show similar perfumes based on notes
                if (!empty($notes)):
                    $note_ids = array();
                    foreach ($notes as $note) {
                        $note_ids[] = $note->term_id;
                    }
                    
                    $similar_perfumes = new \WP_Query(array(
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
                        <div class="similar-perfumes">
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
                                            <h4><?php the_title(); ?></h4>
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

<style>
.parfume-container {
    display: flex;
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
    gap: 30px;
}

.parfume-main-content {
    flex: 1;
}

.parfume-stores-sidebar {
    flex: 0 0 350px;
    position: sticky;
    top: 20px;
    height: fit-content;
    max-height: calc(100vh - 40px);
    overflow-y: auto;
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

.brand-label {
    font-weight: bold;
    margin-right: 5px;
}

.parfume-rating {
    display: flex;
    align-items: center;
    margin-bottom: 25px;
    gap: 10px;
}

.rating-stars {
    font-size: 1.5em;
    color: #ffc107;
}

.rating-number {
    font-size: 1.2em;
    font-weight: bold;
    color: #333;
}

.parfume-meta {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 10px;
    margin-bottom: 20px;
}

.meta-item {
    padding: 8px 0;
    border-bottom: 1px solid #eee;
}

.meta-item strong {
    display: inline-block;
    min-width: 120px;
    color: #333;
}

/* Tabs */
.parfume-tabs {
    margin-bottom: 40px;
}

.tabs-nav {
    display: flex;
    list-style: none;
    padding: 0;
    margin: 0 0 20px;
    border-bottom: 2px solid #dee2e6;
    flex-wrap: wrap;
}

.tabs-nav li {
    margin-right: 5px;
}

.tabs-nav a {
    display: block;
    padding: 12px 20px;
    text-decoration: none;
    color: #666;
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-bottom: none;
    border-radius: 8px 8px 0 0;
    transition: all 0.3s ease;
}

.tabs-nav a:hover,
.tabs-nav a.active {
    background: #fff;
    color: #333;
    border-color: #0073aa;
}

.tab-panel {
    padding: 30px;
    background: #fff;
    border: 1px solid #dee2e6;
    border-radius: 0 8px 8px 8px;
    margin-top: -1px;
}

/* Main notes groups */
.notes-groups-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.note-group {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 8px;
    border-left: 4px solid #0073aa;
}

.group-title {
    margin: 0 0 10px;
    color: #0073aa;
    font-size: 1.1em;
}

.group-notes {
    list-style: none;
    padding: 0;
    margin: 0;
}

.group-notes li {
    margin-bottom: 5px;
}

.group-notes a {
    text-decoration: none;
    color: #333;
    padding: 2px 4px;
    border-radius: 3px;
    transition: background-color 0.3s ease;
}

.group-notes a:hover {
    background: #e3f2fd;
    color: #1976d2;
}

/* Notes pyramid */
.notes-pyramid {
    margin-top: 30px;
}

.pyramid-levels {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.pyramid-level {
    padding: 20px;
    border-radius: 8px;
    background: linear-gradient(135deg, #e3f2fd, #bbdefb);
}

.pyramid-level.middle-notes {
    margin: 0 20px;
    background: linear-gradient(135deg, #f3e5f5, #e1bee7);
}

.pyramid-level.base-notes {
    margin: 0 40px;
    background: linear-gradient(135deg, #fff3e0, #ffcc02);
}

.pyramid-level h4 {
    margin: 0 0 10px;
    color: #333;
}

.pyramid-level ul {
    list-style: none;
    padding: 0;
    margin: 0;
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
}

.pyramid-level li {
    background: rgba(255, 255, 255, 0.8);
    padding: 5px 10px;
    border-radius: 15px;
    border: 1px solid rgba(0, 0, 0, 0.1);
}

.pyramid-level a {
    text-decoration: none;
    color: #333;
    font-weight: 500;
}

/* Aroma chart */
.chart-container {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.chart-item {
    display: flex;
    align-items: center;
    gap: 15px;
}

.chart-item label {
    min-width: 100px;
    font-weight: bold;
    color: #333;
}

.chart-bar {
    flex: 1;
    height: 30px;
    background: #e9ecef;
    border-radius: 15px;
    position: relative;
    overflow: hidden;
}

.chart-fill {
    height: 100%;
    background: linear-gradient(90deg, #4CAF50, #81C784);
    border-radius: 15px;
    transition: width 0.3s ease;
}

.chart-value {
    position: absolute;
    right: 10px;
    top: 50%;
    transform: translateY(-50%);
    font-weight: bold;
    color: #333;
    font-size: 0.9em;
}

/* Pros and cons */
.pros-cons {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 30px;
}

.pros, .cons {
    padding: 20px;
    border-radius: 8px;
}

.pros {
    background: #e8f5e8;
    border-left: 4px solid #4CAF50;
}

.cons {
    background: #ffebee;
    border-left: 4px solid #f44336;
}

.pros h3 {
    color: #2e7d32;
    margin: 0 0 15px;
}

.cons h3 {
    color: #c62828;
    margin: 0 0 15px;
}

.pros ul, .cons ul {
    list-style: none;
    padding: 0;
    margin: 0;
}

.pros li, .cons li {
    margin-bottom: 10px;
    padding-left: 20px;
    position: relative;
}

.pros li:before {
    content: "✓";
    position: absolute;
    left: 0;
    color: #4CAF50;
    font-weight: bold;
}

.cons li:before {
    content: "✗";
    position: absolute;
    left: 0;
    color: #f44336;
    font-weight: bold;
}

/* Perfumer card */
.perfumer-card {
    display: flex;
    gap: 20px;
    margin-bottom: 30px;
    padding: 20px;
    background: #f8f9fa;
    border-radius: 8px;
}

.perfumer-photo {
    flex: 0 0 150px;
}

.perfumer-photo img {
    max-width: 100%;
    height: auto;
    border-radius: 50%;
}

.perfumer-details {
    flex: 1;
}

.perfumer-bio {
    margin: 15px 0;
    line-height: 1.6;
    color: #666;
}

.perfumer-other-works h4 {
    margin-bottom: 10px;
    color: #333;
}

.perfumer-other-works ul {
    list-style: none;
    padding: 0;
}

.perfumer-other-works li {
    margin-bottom: 5px;
}

.perfumer-other-works a {
    text-decoration: none;
    color: #0073aa;
}

.perfumer-other-works a:hover {
    text-decoration: underline;
}

/* Related perfumes */
.parfume-related {
    margin-top: 50px;
    padding-top: 30px;
    border-top: 2px solid #dee2e6;
}

.brand-perfumes, .similar-perfumes {
    margin-bottom: 40px;
}

.brand-perfumes h3, .similar-perfumes h3 {
    margin-bottom: 20px;
    color: #333;
    font-size: 1.5em;
    border-bottom: 2px solid #0073aa;
    padding-bottom: 10px;
}

.related-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 20px;
}

.related-item {
    background: white;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    overflow: hidden;
    transition: all 0.3s ease;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.related-item:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
    border-color: #0073aa;
}

.related-item a {
    display: block;
    text-decoration: none;
    color: inherit;
}

.related-thumbnail {
    height: 150px;
    overflow: hidden;
}

.related-thumbnail img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.related-item h4 {
    padding: 15px;
    margin: 0;
    font-size: 1em;
    color: #333;
}

.related-item:hover h4 {
    color: #0073aa;
}

/* Responsive design */
@media (max-width: 1024px) {
    .parfume-container {
        flex-direction: column;
    }
    
    .parfume-stores-sidebar {
        position: static;
        max-height: none;
        order: 2;
    }
    
    .parfume-main-content {
        order: 1;
    }
}

@media (max-width: 768px) {
    .parfume-header {
        flex-direction: column;
    }
    
    .parfume-gallery {
        margin-bottom: 20px;
    }
    
    .tabs-nav {
        flex-direction: column;
    }
    
    .tabs-nav li {
        margin-right: 0;
        margin-bottom: 5px;
    }
    
    .notes-groups-grid {
        grid-template-columns: 1fr;
    }
    
    .pyramid-level.middle-notes,
    .pyramid-level.base-notes {
        margin: 0;
    }
    
    .pros-cons {
        grid-template-columns: 1fr;
    }
    
    .perfumer-card {
        flex-direction: column;
        text-align: center;
    }
    
    .perfumer-photo {
        align-self: center;
    }
    
    .related-grid {
        grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    }
    
    .parfume-meta {
        grid-template-columns: 1fr;
    }
    
    .chart-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 8px;
    }
    
    .chart-item label {
        min-width: auto;
    }
}

/* Comments styling */
.comments-area {
    margin-top: 30px;
    background: #f8f9fa;
    padding: 30px;
    border-radius: 8px;
}

.comments-title {
    margin-bottom: 25px;
    color: #333;
    border-bottom: 2px solid #0073aa;
    padding-bottom: 10px;
}

.comment-list {
    list-style: none;
    padding: 0;
}

.comment {
    margin-bottom: 25px;
    padding: 20px;
    background: white;
    border-radius: 8px;
    border-left: 4px solid #0073aa;
}

.comment-author {
    font-weight: bold;
    color: #333;
    margin-bottom: 5px;
}

.comment-meta {
    font-size: 0.9em;
    color: #666;
    margin-bottom: 15px;
}

.comment-content {
    line-height: 1.6;
    color: #333;
}

.comment-reply-link {
    color: #0073aa;
    text-decoration: none;
    font-size: 0.9em;
}

.comment-reply-link:hover {
    text-decoration: underline;
}

.comment-respond {
    margin-top: 30px;
    padding: 25px;
    background: white;
    border-radius: 8px;
    border: 1px solid #dee2e6;
}

.comment-reply-title {
    margin-bottom: 20px;
    color: #333;
}

.comment-form-comment textarea {
    width: 100%;
    min-height: 120px;
    padding: 15px;
    border: 1px solid #dee2e6;
    border-radius: 4px;
    font-family: inherit;
    resize: vertical;
}

.comment-form-author input,
.comment-form-email input,
.comment-form-url input {
    width: 100%;
    padding: 10px;
    border: 1px solid #dee2e6;
    border-radius: 4px;
}

.form-submit .submit {
    background: #0073aa;
    color: white;
    border: none;
    padding: 12px 25px;
    border-radius: 4px;
    cursor: pointer;
    font-size: 1em;
    transition: background-color 0.3s ease;
}

.form-submit .submit:hover {
    background: #005a87;
}
</style>

<?php
endwhile;

get_footer();
?>