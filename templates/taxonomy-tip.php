<?php
/**
 * Template for displaying perfume types taxonomy archive
 */

get_header(); ?>

<div class="parfume-catalog-archive type-archive">
    <div class="container">
        <header class="archive-header">
            <h1 class="archive-title">
                <?php single_cat_title('Тип аромат: '); ?>
            </h1>
            
            <?php if (term_description()) : ?>
                <div class="archive-description">
                    <?php echo term_description(); ?>
                </div>
            <?php endif; ?>
        </header>

        <?php
        $term = get_queried_object();
        $type_info = '';
        $type_description = '';
        
        switch (strtolower($term->slug)) {
            case 'toaletna-voda':
                $type_info = 'EDT - Eau de Toilette (5-15% ароматни масла)';
                $type_description = 'По-лека концентрация, подходяща за ежедневна употреба.';
                break;
            case 'parfyumna-voda':
                $type_info = 'EDP - Eau de Parfum (15-20% ароматни масла)';
                $type_description = 'Средна концентрация с добра трайност.';
                break;
            case 'parfyum':
                $type_info = 'Parfum - Extrait de Parfum (20-40% ароматни масла)';
                $type_description = 'Най-концентрираната форма с отлична трайност.';
                break;
            case 'parfyumen-eleksir':
                $type_info = 'Elixir - Parfum Elixir (25-40% ароматни масла)';
                $type_description = 'Много концентрирана и трайна формула.';
                break;
        }
        ?>

        <?php if ($type_info) : ?>
            <div class="type-info">
                <div class="concentration-info">
                    <h3><?php echo esc_html($type_info); ?></h3>
                    <p><?php echo esc_html($type_description); ?></p>
                </div>
            </div>
        <?php endif; ?>

        <div class="archive-filters">
            <div class="filters-row">
                <div class="filter-group">
                    <label>Сортиране:</label>
                    <select id="sort-products">
                        <option value="date">Най-нови</option>
                        <option value="title">По име</option>
                        <option value="popularity">Популярност</option>
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
                    <label>Пол:</label>
                    <select id="filter-gender">
                        <option value="">Всички</option>
                        <option value="damski">Дамски</option>
                        <option value="myzhki">Мъжки</option>
                        <option value="uniseks">Унисекс</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label>Сезон:</label>
                    <select id="filter-season">
                        <option value="">Всички сезони</option>
                        <?php
                        $seasons = get_terms(array(
                            'taxonomy' => 'sezon',
                            'hide_empty' => true,
                        ));
                        foreach ($seasons as $season) :
                        ?>
                            <option value="<?php echo $season->term_id; ?>"><?php echo esc_html($season->name); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>

        <div class="archive-content">
            <?php if (have_posts()) : ?>
                <div class="archive-stats">
                    <p>Намерени <?php echo $wp_query->found_posts; ?> парфюма от тип "<?php single_cat_title(); ?>"</p>
                </div>

                <div class="products-grid" id="products-container">
                    <?php while (have_posts()) : the_post(); ?>
                        <article class="product-card" data-product-id="<?php echo get_the_ID(); ?>">
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
                                // Type badge
                                $types = get_the_terms(get_the_ID(), 'tip');
                                if ($types && !is_wp_error($types)) :
                                ?>
                                    <div class="type-badge-overlay">
                                        <?php foreach ($types as $type) : ?>
                                            <span class="type-badge type-<?php echo esc_attr($type->slug); ?>">
                                                <?php 
                                                $short_name = '';
                                                switch (strtolower($type->slug)) {
                                                    case 'toaletna-voda': $short_name = 'EDT'; break;
                                                    case 'parfyumna-voda': $short_name = 'EDP'; break;
                                                    case 'parfyum': $short_name = 'Parfum'; break;
                                                    case 'parfyumen-eleksir': $short_name = 'Elixir'; break;
                                                    default: $short_name = $type->name;
                                                }
                                                echo esc_html($short_name);
                                                ?>
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
                                // Longevity and sillage
                                $longevity = get_post_meta(get_the_ID(), '_longevity', true);
                                $sillage = get_post_meta(get_the_ID(), '_sillage', true);
                                if ($longevity || $sillage) :
                                ?>
                                    <div class="product-characteristics">
                                        <?php if ($longevity) : ?>
                                            <div class="characteristic">
                                                <span class="char-label">Трайност:</span>
                                                <div class="rating-bars">
                                                    <?php for ($i = 1; $i <= 5; $i++) : ?>
                                                        <span class="bar <?php echo $i <= $longevity ? 'filled' : ''; ?>"></span>
                                                    <?php endfor; ?>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <?php if ($sillage) : ?>
                                            <div class="characteristic">
                                                <span class="char-label">Следа:</span>
                                                <div class="rating-bars">
                                                    <?php for ($i = 1; $i <= 4; $i++) : ?>
                                                        <span class="bar <?php echo $i <= $sillage ? 'filled' : ''; ?>"></span>
                                                    <?php endfor; ?>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>

                                <?php
                                // Top notes preview
                                $top_notes = get_the_terms(get_the_ID(), 'notes');
                                if ($top_notes && !is_wp_error($top_notes)) :
                                    $top_notes_names = array_slice(wp_list_pluck($top_notes, 'name'), 0, 3);
                                ?>
                                    <div class="notes-preview">
                                        <span class="notes-label">Нотки:</span>
                                        <span class="notes-list"><?php echo implode(', ', $top_notes_names); ?><?php echo count($top_notes) > 3 ? '...' : ''; ?></span>
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
                    <p>Няма парфюми от този тип.</p>
                    <a href="<?php echo get_post_type_archive_link('parfumes'); ?>" class="button">
                        Разгледай всички парфюми
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <!-- Type comparison info -->
        <div class="type-comparison-info">
            <h3>Сравнение на типовете аромати</h3>
            <table class="concentration-table">
                <thead>
                    <tr>
                        <th>Тип</th>
                        <th>Концентрация</th>
                        <th>Трайност</th>
                        <th>Подходящ за</th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="<?php echo $term->slug === 'toaletna-voda' ? 'current' : ''; ?>">
                        <td>EDT - Тоалетна вода</td>
                        <td>5-15%</td>
                        <td>2-4 часа</td>
                        <td>Ежедневна употреба, офис</td>
                    </tr>
                    <tr class="<?php echo $term->slug === 'parfyumna-voda' ? 'current' : ''; ?>">
                        <td>EDP - Парфюмна вода</td>
                        <td>15-20%</td>
                        <td>4-6 часа</td>
                        <td>Специални поводи, вечер</td>
                    </tr>
                    <tr class="<?php echo $term->slug === 'parfyum' ? 'current' : ''; ?>">
                        <td>Parfum - Парфюм</td>
                        <td>20-40%</td>
                        <td>6-8 часа</td>
                        <td>Специални събития, интимност</td>
                    </tr>
                    <tr class="<?php echo $term->slug === 'parfyumen-eleksir' ? 'current' : ''; ?>">
                        <td>Elixir - Парфюмен еликсир</td>
                        <td>25-40%</td>
                        <td>8+ часа</td>
                        <td>Много специални поводи</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Filtering and sorting functionality
    function filterProducts() {
        var brandFilter = $('#filter-brand').val();
        var genderFilter = $('#filter-gender').val();
        var seasonFilter = $('#filter-season').val();
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
            
            // Gender filter (would need to be implemented based on categories)
            if (genderFilter && show) {
                // This would need additional implementation based on how gender is stored
            }
            
            // Season filter
            if (seasonFilter && show) {
                // This would need additional implementation
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
                case 'longevity':
                    var aLongevity = $(a).find('.rating-bars .filled').length || 0;
                    var bLongevity = $(b).find('.rating-bars .filled').length || 0;
                    return bLongevity - aLongevity;
                case 'popularity':
                    // Would need view count or similar metric
                    return 0;
                case 'date':
                default:
                    return $(b).data('product-id') - $(a).data('product-id');
            }
        });
        
        // Re-append sorted products
        $('#products-container').empty().append(visibleProducts);
    }
    
    // Bind filter change events
    $('#filter-brand, #filter-gender, #filter-season, #sort-products').on('change', filterProducts);
    
    // Update stats when filtering
    function updateStats() {
        var visibleCount = $('#products-container .product-card:visible').length;
        $('.archive-stats p').text('Показани ' + visibleCount + ' парфюма от тип "<?php single_cat_title(); ?>"');
    }
    
    // Update stats after filtering
    $('#filter-brand, #filter-gender, #filter-season').on('change', function() {
        setTimeout(updateStats, 100);
    });
});
</script>

<?php get_footer(); ?>