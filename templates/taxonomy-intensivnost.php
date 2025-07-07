<?php
/**
 * Template for displaying intensity taxonomy archive
 */

get_header(); ?>

<div class="parfume-catalog-archive intensity-archive">
    <div class="container">
        <header class="archive-header">
            <h1 class="archive-title">
                <?php single_cat_title('Интензивност: '); ?>
            </h1>
            
            <?php if (term_description()) : ?>
                <div class="archive-description">
                    <?php echo term_description(); ?>
                </div>
            <?php endif; ?>
        </header>

        <?php
        $term = get_queried_object();
        $intensity_info = '';
        $intensity_description = '';
        $intensity_level = 0;
        
        switch (strtolower($term->slug)) {
            case 'silni':
                $intensity_info = 'Силни парфюми';
                $intensity_description = 'Парфюми с висока интензивност и проекция, подходящи за специални поводи.';
                $intensity_level = 5;
                break;
            case 'sredni':
                $intensity_info = 'Средни парфюми';
                $intensity_description = 'Балансирани парфюми с умерена интензивност, подходящи за ежедневна употреба.';
                $intensity_level = 3;
                break;
            case 'leki':
                $intensity_info = 'Леки парфюми';
                $intensity_description = 'Деликатни парфюми с ниска интензивност, подходящи за офис и дневна употреба.';
                $intensity_level = 2;
                break;
            case 'fini-delikatni':
                $intensity_info = 'Фини/деликатни парфюми';
                $intensity_description = 'Много деликатни и фини парфюми с минимална проекция.';
                $intensity_level = 1;
                break;
            case 'intensivni':
                $intensity_info = 'Интензивни парфюми';
                $intensity_description = 'Много силни парфюми с мощна проекция и дълга трайност.';
                $intensity_level = 5;
                break;
            case 'pudreni':
                $intensity_info = 'Пудрени парфюми';
                $intensity_description = 'Парфюми с пудрен характер, меки и комфортни.';
                $intensity_level = 2;
                break;
            case 'tezhki-dalbok':
                $intensity_info = 'Тежки/дълбоки парфюми';
                $intensity_description = 'Сложни и дълбоки парфюми с богат характер.';
                $intensity_level = 4;
                break;
        }
        ?>

        <?php if ($intensity_info) : ?>
            <div class="intensity-info">
                <div class="intensity-visual">
                    <h3><?php echo esc_html($intensity_info); ?></h3>
                    <div class="intensity-meter">
                        <span class="meter-label">Ниво на интензивност:</span>
                        <div class="meter-bars">
                            <?php for ($i = 1; $i <= 5; $i++) : ?>
                                <span class="meter-bar <?php echo $i <= $intensity_level ? 'active level-' . $i : ''; ?>"></span>
                            <?php endfor; ?>
                        </div>
                    </div>
                    <p><?php echo esc_html($intensity_description); ?></p>
                </div>
            </div>
        <?php endif; ?>

        <div class="archive-filters">
            <div class="filters-row">
                <div class="filter-group">
                    <label>Сортиране:</label>
                    <select id="sort-products">
                        <option value="intensity">По интензивност</option>
                        <option value="date">Най-нови</option>
                        <option value="title">По име</option>
                        <option value="longevity">Трайност</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label>Марка:</label>
                    <select id="filter-brand">
                        <option value="">Всички марки</option>
                        <?php
                        $brands = get_terms(array(
                            'taxonomy' => 'marki',
                            'hide_empty' => true,
                        ));
                        foreach ($brands as $brand) :
                        ?>
                            <option value="<?php echo $brand->term_id; ?>"><?php echo esc_html($brand->name); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="filter-group">
                    <label>Тип аромат:</label>
                    <select id="filter-type">
                        <option value="">Всички типове</option>
                        <?php
                        $types = get_terms(array(
                            'taxonomy' => 'tip',
                            'hide_empty' => true,
                        ));
                        foreach ($types as $type) :
                        ?>
                            <option value="<?php echo $type->term_id; ?>"><?php echo esc_html($type->name); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="filter-group">
                    <label>Показвай само:</label>
                    <div class="checkbox-filters">
                        <label><input type="checkbox" id="filter-high-rated"> Високо оценени</label>
                        <label><input type="checkbox" id="filter-long-lasting"> Дълготрайни</label>
                    </div>
                </div>
            </div>
        </div>

        <div class="archive-content">
            <?php if (have_posts()) : ?>
                <div class="archive-stats">
                    <p>Намерени <?php echo $wp_query->found_posts; ?> парфюма с интензивност "<?php single_cat_title(); ?>"</p>
                </div>

                <div class="products-grid" id="products-container">
                    <?php while (have_posts()) : the_post(); ?>
                        <article class="product-card intensity-card" data-product-id="<?php echo get_the_ID(); ?>">
                            <div class="product-image">
                                <a href="<?php the_permalink(); ?>">
                                    <?php if (has_post_thumbnail()) : ?>
                                        <?php the_post_thumbnail('medium'); ?>
                                    <?php else : ?>
                                        <div class="no-image">
                                            <span class="dashicons dashicons-image-alt"></span>
                                        </div>
                                    <?php endif; ?>
                                </a>
                                
                                <?php
                                // Intensity badge
                                $intensities = get_the_terms(get_the_ID(), 'intensivnost');
                                if ($intensities && !is_wp_error($intensities)) :
                                ?>
                                    <div class="intensity-badge-overlay">
                                        <?php foreach ($intensities as $intensity) : ?>
                                            <span class="intensity-badge intensity-<?php echo esc_attr($intensity->slug); ?>">
                                                <?php echo esc_html($intensity->name); ?>
                                            </span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="product-info">
                                <h2 class="product-title">
                                    <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                                </h2>
                                
                                <?php
                                $brands = get_the_terms(get_the_ID(), 'marki');
                                if ($brands && !is_wp_error($brands)) :
                                ?>
                                    <p class="product-brand">
                                        <?php foreach ($brands as $brand) : ?>
                                            <a href="<?php echo get_term_link($brand); ?>">
                                                <?php echo esc_html($brand->name); ?>
                                            </a>
                                        <?php endforeach; ?>
                                    </p>
                                <?php endif; ?>

                                <?php
                                $types = get_the_terms(get_the_ID(), 'tip');
                                if ($types && !is_wp_error($types)) :
                                ?>
                                    <p class="product-type">
                                        <?php foreach ($types as $type) : ?>
                                            <span class="type-badge"><?php echo esc_html($type->name); ?></span>
                                        <?php endforeach; ?>
                                    </p>
                                <?php endif; ?>

                                <?php
                                // Display characteristics relevant to intensity
                                $longevity = get_post_meta(get_the_ID(), '_longevity', true);
                                $sillage = get_post_meta(get_the_ID(), '_sillage', true);
                                $projection = get_post_meta(get_the_ID(), '_projection', true);
                                ?>
                                <div class="product-characteristics">
                                    <?php if ($longevity) : ?>
                                        <div class="characteristic">
                                            <span class="char-label">Трайност:</span>
                                            <div class="rating-bars small">
                                                <?php for ($i = 1; $i <= 5; $i++) : ?>
                                                    <span class="bar <?php echo $i <= $longevity ? 'filled' : ''; ?>"></span>
                                                <?php endfor; ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($sillage) : ?>
                                        <div class="characteristic">
                                            <span class="char-label">Следа:</span>
                                            <div class="rating-bars small">
                                                <?php for ($i = 1; $i <= 4; $i++) : ?>
                                                    <span class="bar <?php echo $i <= $sillage ? 'filled' : ''; ?>"></span>
                                                <?php endfor; ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>

                                    <?php if ($projection) : ?>
                                        <div class="characteristic">
                                            <span class="char-label">Проекция:</span>
                                            <div class="rating-bars small">
                                                <?php for ($i = 1; $i <= 4; $i++) : ?>
                                                    <span class="bar <?php echo $i <= $projection ? 'filled' : ''; ?>"></span>
                                                <?php endfor; ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <?php
                                // Average rating
                                $comments_avg = get_post_meta(get_the_ID(), '_comments_average_rating', true);
                                if ($comments_avg) :
                                ?>
                                    <div class="product-rating">
                                        <div class="stars">
                                            <?php for ($i = 1; $i <= 5; $i++) : ?>
                                                <span class="star <?php echo $i <= round($comments_avg) ? 'filled' : ''; ?>">★</span>
                                            <?php endfor; ?>
                                        </div>
                                        <span class="rating-text"><?php echo number_format($comments_avg, 1); ?></span>
                                    </div>
                                <?php endif; ?>

                                <?php
                                // Main notes
                                $main_notes = get_the_terms(get_the_ID(), 'notes');
                                if ($main_notes && !is_wp_error($main_notes)) :
                                    $main_notes_names = array_slice(wp_list_pluck($main_notes, 'name'), 0, 3);
                                ?>
                                    <div class="notes-preview">
                                        <span class="notes-label">Главни нотки:</span>
                                        <span class="notes-list"><?php echo implode(', ', $main_notes_names); ?><?php echo count($main_notes) > 3 ? '...' : ''; ?></span>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="product-actions">
                                    <button class="add-to-comparison" data-product-id="<?php echo get_the_ID(); ?>">
                                        <span class="dashicons dashicons-plus"></span>
                                        Добави за сравнение
                                    </button>
                                </div>
                            </div>
                        </article>
                    <?php endwhile; ?>
                </div>

                <?php
                // Pagination
                the_posts_pagination(array(
                    'mid_size' => 2,
                    'prev_text' => __('← Предишна'),
                    'next_text' => __('Следваща →'),
                ));
                ?>

            <?php else : ?>
                <div class="no-products">
                    <p>Няма парфюми с тази интензивност.</p>
                    <a href="<?php echo get_post_type_archive_link('parfumes'); ?>" class="button">
                        Разгледай всички парфюми
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <!-- Intensity guide -->
        <div class="intensity-guide">
            <h3>Ръководство за интензивност на парфюмите</h3>
            <div class="intensity-scale">
                <div class="scale-item">
                    <div class="scale-visual">
                        <div class="meter-bars">
                            <span class="meter-bar active level-1"></span>
                            <span class="meter-bar"></span>
                            <span class="meter-bar"></span>
                            <span class="meter-bar"></span>
                            <span class="meter-bar"></span>
                        </div>
                    </div>
                    <div class="scale-info">
                        <h4>Фини/Деликатни</h4>
                        <p>Много деликатни, близо до кожата, подходящи за интимни моменти</p>
                    </div>
                </div>

                <div class="scale-item">
                    <div class="scale-visual">
                        <div class="meter-bars">
                            <span class="meter-bar active level-2"></span>
                            <span class="meter-bar active level-2"></span>
                            <span class="meter-bar"></span>
                            <span class="meter-bar"></span>
                            <span class="meter-bar"></span>
                        </div>
                    </div>
                    <div class="scale-info">
                        <h4>Леки/Пудрени</h4>
                        <p>Приятни за ежедневна употреба, не притискат, подходящи за офис</p>
                    </div>
                </div>

                <div class="scale-item">
                    <div class="scale-visual">
                        <div class="meter-bars">
                            <span class="meter-bar active level-3"></span>
                            <span class="meter-bar active level-3"></span>
                            <span class="meter-bar active level-3"></span>
                            <span class="meter-bar"></span>
                            <span class="meter-bar"></span>
                        </div>
                    </div>
                    <div class="scale-info">
                        <h4>Средни</h4>
                        <p>Балансирани, универсални за повечето ситуации</p>
                    </div>
                </div>

                <div class="scale-item">
                    <div class="scale-visual">
                        <div class="meter-bars">
                            <span class="meter-bar active level-4"></span>
                            <span class="meter-bar active level-4"></span>
                            <span class="meter-bar active level-4"></span>
                            <span class="meter-bar active level-4"></span>
                            <span class="meter-bar"></span>
                        </div>
                    </div>
                    <div class="scale-info">
                        <h4>Тежки/Дълбоки</h4>
                        <p>Сложни и богати, подходящи за вечер и специални поводи</p>
                    </div>
                </div>

                <div class="scale-item">
                    <div class="scale-visual">
                        <div class="meter-bars">
                            <span class="meter-bar active level-5"></span>
                            <span class="meter-bar active level-5"></span>
                            <span class="meter-bar active level-5"></span>
                            <span class="meter-bar active level-5"></span>
                            <span class="meter-bar active level-5"></span>
                        </div>
                    </div>
                    <div class="scale-info">
                        <h4>Силни/Интензивни</h4>
                        <p>Много мощни с голяма проекция, за специални случаи</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Advanced filtering and sorting
    function filterProducts() {
        var brandFilter = $('#filter-brand').val();
        var typeFilter = $('#filter-type').val();
        var highRatedFilter = $('#filter-high-rated').is(':checked');
        var longLastingFilter = $('#filter-long-lasting').is(':checked');
        var sortBy = $('#sort-products').val();
        
        var products = $('#products-container .product-card').get();
        
        // Filter products
        $(products).each(function() {
            var show = true;
            var $product = $(this);
            
            // Brand filter
            if (brandFilter) {
                var brandMatch = $product.find('.product-brand a[href*="term=' + brandFilter + '"]').length > 0;
                if (!brandMatch) show = false;
            }
            
            // Type filter
            if (typeFilter && show) {
                var typeMatch = $product.find('.product-type .type-badge').text().trim().length > 0;
                if (!typeMatch) show = false;
            }
            
            // High rated filter
            if (highRatedFilter && show) {
                var rating = parseFloat($product.find('.rating-text').text()) || 0;
                if (rating < 4.0) show = false;
            }
            
            // Long lasting filter
            if (longLastingFilter && show) {
                var longevity = $product.find('.characteristic:contains("Трайност") .filled').length || 0;
                if (longevity < 4) show = false;
            }
            
            if (show) {
                $product.show();
            } else {
                $product.hide();
            }
        });
        
        // Sort visible products
        var visibleProducts = $('#products-container .product-card:visible').get();
        
        visibleProducts.sort(function(a, b) {
            switch(sortBy) {
                case 'title':
                    var aTitle = $(a).find('.product-title a').text();
                    var bTitle = $(b).find('.product-title a').text();
                    return aTitle.localeCompare(bTitle);
                case 'intensity':
                    var aIntensity = $(a).find('.rating-bars .filled').length || 0;
                    var bIntensity = $(b).find('.rating-bars .filled').length || 0;
                    return bIntensity - aIntensity;
                case 'longevity':
                    var aLongevity = $(a).find('.characteristic:contains("Трайност") .filled').length || 0;
                    var bLongevity = $(b).find('.characteristic:contains("Трайност") .filled').length || 0;
                    return bLongevity - aLongevity;
                case 'date':
                default:
                    return $(b).data('product-id') - $(a).data('product-id');
            }
        });
        
        // Re-append sorted products
        $('#products-container').empty().append(visibleProducts);
        
        // Update stats
        updateStats();
    }
    
    function updateStats() {
        var visibleCount = $('#products-container .product-card:visible').length;
        $('.archive-stats p').text('Показани ' + visibleCount + ' парфюма с интензивност "<?php single_cat_title(); ?>"');
    }
    
    // Bind all filter events
    $('#filter-brand, #filter-type, #sort-products').on('change', filterProducts);
    $('#filter-high-rated, #filter-long-lasting').on('change', filterProducts);
    
    // Highlight current intensity in guide
    $('.intensity-guide .scale-item').each(function() {
        var intensityName = $(this).find('h4').text().toLowerCase();
        var currentIntensity = '<?php echo strtolower($term->name); ?>';
        
        if (intensityName.indexOf(currentIntensity) !== -1 || currentIntensity.indexOf(intensityName) !== -1) {
            $(this).addClass('current-intensity');
        }
    });
});
</script>

<?php get_footer(); ?>