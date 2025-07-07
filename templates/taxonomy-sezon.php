<?php
/**
 * Template for displaying season taxonomy archive
 */

get_header(); ?>

<div class="parfume-catalog-archive season-archive">
    <div class="container">
        <header class="archive-header">
            <h1 class="archive-title">
                <?php single_cat_title('Сезон: '); ?>
            </h1>
            
            <?php if (term_description()) : ?>
                <div class="archive-description">
                    <?php echo term_description(); ?>
                </div>
            <?php endif; ?>
        </header>

        <?php
        $term = get_queried_object();
        $season_icon = '';
        switch (strtolower($term->slug)) {
            case 'prolet':
                $season_icon = '🌸';
                break;
            case 'lyato':
                $season_icon = '☀️';
                break;
            case 'esen':
                $season_icon = '🍂';
                break;
            case 'zima':
                $season_icon = '❄️';
                break;
        }
        ?>

        <?php if ($season_icon) : ?>
            <div class="season-info">
                <div class="season-icon"><?php echo $season_icon; ?></div>
            </div>
        <?php endif; ?>

        <div class="archive-filters">
            <div class="filter-group">
                <label>Сортиране:</label>
                <select id="sort-products">
                    <option value="date">Най-нови</option>
                    <option value="title">По име</option>
                    <option value="popularity">Популярност</option>
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
        </div>

        <div class="archive-content">
            <?php if (have_posts()) : ?>
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
                                // Season badges
                                $seasons = get_the_terms(get_the_ID(), 'sezon');
                                if ($seasons && !is_wp_error($seasons)) :
                                ?>
                                    <div class="season-badges">
                                        <?php foreach ($seasons as $season) : ?>
                                            <span class="season-badge season-<?php echo esc_attr($season->slug); ?>">
                                                <?php echo esc_html($season->name); ?>
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
                                // Display suitable times (day/night)
                                $suitable_day = get_post_meta(get_the_ID(), '_suitable_day', true);
                                $suitable_night = get_post_meta(get_the_ID(), '_suitable_night', true);
                                if ($suitable_day || $suitable_night) :
                                ?>
                                    <div class="suitable-times">
                                        <?php if ($suitable_day) : ?>
                                            <span class="time-badge day">☀️ Ден</span>
                                        <?php endif; ?>
                                        <?php if ($suitable_night) : ?>
                                            <span class="time-badge night">🌙 Нощ</span>
                                        <?php endif; ?>
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
                    <p>Няма парфюми за този сезон.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Sort functionality
    $('#sort-products').on('change', function() {
        var sortBy = $(this).val();
        var container = $('#products-container');
        var products = container.find('.product-card').get();
        
        products.sort(function(a, b) {
            switch(sortBy) {
                case 'title':
                    var aTitle = $(a).find('.product-title a').text();
                    var bTitle = $(b).find('.product-title a').text();
                    return aTitle.localeCompare(bTitle);
                case 'date':
                default:
                    return $(b).data('product-id') - $(a).data('product-id');
            }
        });
        
        container.empty().append(products);
    });
    
    // Brand filter
    $('#filter-brand').on('change', function() {
        var brandId = $(this).val();
        
        if (!brandId) {
            $('.product-card').show();
            return;
        }
        
        $('.product-card').each(function() {
            var productBrand = $(this).find('.product-brand a').attr('href');
            if (productBrand && productBrand.indexOf('term_id=' + brandId) > -1) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    });
});
</script>

<?php get_footer(); ?>