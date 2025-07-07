<?php
/**
 * Template for parfume type taxonomy archive
 * 
 * @package ParfumeCatalog
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

get_header(); ?>

<div class="parfume-taxonomy-archive parfume-type-archive">
    <div class="container">
        
        <?php if (have_posts()) : ?>
            
            <header class="archive-header">
                <h1 class="archive-title">
                    <?php
                    $term = get_queried_object();
                    echo esc_html($term->name);
                    ?>
                </h1>
                
                <?php if ($term->description) : ?>
                    <div class="archive-description">
                        <?php echo wp_kses_post($term->description); ?>
                    </div>
                <?php endif; ?>
                
                <div class="archive-meta">
                    <span class="post-count">
                        <?php
                        global $wp_query;
                        printf(
                            _n(
                                'Намерен %s парфюм',
                                'Намерени %s парфюма',
                                $wp_query->found_posts,
                                'parfume-catalog'
                            ),
                            number_format_i18n($wp_query->found_posts)
                        );
                        ?>
                    </span>
                </div>
            </header>

            <div class="parfume-filters">
                <div class="filter-section">
                    <h3>Филтри</h3>
                    
                    <!-- Марка филтър -->
                    <div class="filter-group">
                        <label for="filter-marki">Марка:</label>
                        <select id="filter-marki" name="parfume_marki">
                            <option value="">Всички марки</option>
                            <?php
                            $marki_terms = get_terms(array(
                                'taxonomy' => 'parfume_marki',
                                'hide_empty' => true,
                                'orderby' => 'name',
                                'order' => 'ASC'
                            ));
                            
                            if (!is_wp_error($marki_terms) && !empty($marki_terms)) {
                                foreach ($marki_terms as $marka) {
                                    $selected = (isset($_GET['parfume_marki']) && $_GET['parfume_marki'] === $marka->slug) ? 'selected' : '';
                                    printf(
                                        '<option value="%s" %s>%s (%d)</option>',
                                        esc_attr($marka->slug),
                                        $selected,
                                        esc_html($marka->name),
                                        $marka->count
                                    );
                                }
                            }
                            ?>
                        </select>
                    </div>

                    <!-- Вид аромат филтър -->
                    <div class="filter-group">
                        <label for="filter-vid">Вид аромат:</label>
                        <select id="filter-vid" name="parfume_vid">
                            <option value="">Всички видове</option>
                            <?php
                            $vid_terms = get_terms(array(
                                'taxonomy' => 'parfume_vid',
                                'hide_empty' => true,
                                'orderby' => 'name',
                                'order' => 'ASC'
                            ));
                            
                            if (!is_wp_error($vid_terms) && !empty($vid_terms)) {
                                foreach ($vid_terms as $vid) {
                                    $selected = (isset($_GET['parfume_vid']) && $_GET['parfume_vid'] === $vid->slug) ? 'selected' : '';
                                    printf(
                                        '<option value="%s" %s>%s (%d)</option>',
                                        esc_attr($vid->slug),
                                        $selected,
                                        esc_html($vid->name),
                                        $vid->count
                                    );
                                }
                            }
                            ?>
                        </select>
                    </div>

                    <!-- Сезон филтър -->
                    <div class="filter-group">
                        <label for="filter-season">Сезон:</label>
                        <select id="filter-season" name="parfume_season">
                            <option value="">Всички сезони</option>
                            <?php
                            $season_terms = get_terms(array(
                                'taxonomy' => 'parfume_season',
                                'hide_empty' => true,
                                'orderby' => 'name',
                                'order' => 'ASC'
                            ));
                            
                            if (!is_wp_error($season_terms) && !empty($season_terms)) {
                                foreach ($season_terms as $season) {
                                    $selected = (isset($_GET['parfume_season']) && $_GET['parfume_season'] === $season->slug) ? 'selected' : '';
                                    printf(
                                        '<option value="%s" %s>%s (%d)</option>',
                                        esc_attr($season->slug),
                                        $selected,
                                        esc_html($season->name),
                                        $season->count
                                    );
                                }
                            }
                            ?>
                        </select>
                    </div>

                    <!-- Интензивност филтър -->
                    <div class="filter-group">
                        <label for="filter-intensity">Интензивност:</label>
                        <select id="filter-intensity" name="parfume_intensity">
                            <option value="">Всички интензивности</option>
                            <?php
                            $intensity_terms = get_terms(array(
                                'taxonomy' => 'parfume_intensity',
                                'hide_empty' => true,
                                'orderby' => 'name',
                                'order' => 'ASC'
                            ));
                            
                            if (!is_wp_error($intensity_terms) && !empty($intensity_terms)) {
                                foreach ($intensity_terms as $intensity) {
                                    $selected = (isset($_GET['parfume_intensity']) && $_GET['parfume_intensity'] === $intensity->slug) ? 'selected' : '';
                                    printf(
                                        '<option value="%s" %s>%s (%d)</option>',
                                        esc_attr($intensity->slug),
                                        $selected,
                                        esc_html($intensity->name),
                                        $intensity->count
                                    );
                                }
                            }
                            ?>
                        </select>
                    </div>

                    <button type="button" id="apply-filters" class="btn btn-primary">Приложи филтри</button>
                    <button type="button" id="clear-filters" class="btn btn-secondary">Изчисти филтри</button>
                </div>
            </div>

            <div class="parfume-grid" id="parfume-results">
                <?php while (have_posts()) : the_post(); ?>
                    
                    <article class="parfume-item" id="post-<?php the_ID(); ?>">
                        <div class="parfume-image">
                            <?php if (has_post_thumbnail()) : ?>
                                <a href="<?php the_permalink(); ?>">
                                    <?php the_post_thumbnail('medium', array('alt' => get_the_title())); ?>
                                </a>
                            <?php else : ?>
                                <a href="<?php the_permalink(); ?>">
                                    <div class="no-image-placeholder">
                                        <span>Няма изображение</span>
                                    </div>
                                </a>
                            <?php endif; ?>
                            
                            <!-- Comparison button -->
                            <button class="compare-btn" data-parfume-id="<?php echo get_the_ID(); ?>" data-action="add">
                                <span class="compare-icon">⚖</span>
                                <span class="compare-text">Сравни</span>
                            </button>
                        </div>

                        <div class="parfume-content">
                            <h3 class="parfume-title">
                                <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                            </h3>

                            <div class="parfume-meta">
                                <?php
                                // Марка
                                $marki = wp_get_post_terms(get_the_ID(), 'parfume_marki');
                                if (!empty($marki) && !is_wp_error($marki)) {
                                    echo '<span class="parfume-brand">';
                                    echo '<strong>Марка:</strong> ';
                                    $brand_links = array();
                                    foreach ($marki as $marka) {
                                        $brand_links[] = sprintf(
                                            '<a href="%s">%s</a>',
                                            esc_url(get_term_link($marka)),
                                            esc_html($marka->name)
                                        );
                                    }
                                    echo implode(', ', $brand_links);
                                    echo '</span>';
                                }

                                // Вид аромат
                                $vid_aromati = wp_get_post_terms(get_the_ID(), 'parfume_vid');
                                if (!empty($vid_aromati) && !is_wp_error($vid_aromati)) {
                                    echo '<span class="parfume-type">';
                                    echo '<strong>Вид:</strong> ';
                                    $type_links = array();
                                    foreach ($vid_aromati as $vid) {
                                        $type_links[] = sprintf(
                                            '<a href="%s">%s</a>',
                                            esc_url(get_term_link($vid)),
                                            esc_html($vid->name)
                                        );
                                    }
                                    echo implode(', ', $type_links);
                                    echo '</span>';
                                }
                                ?>
                            </div>

                            <?php if (has_excerpt()) : ?>
                                <div class="parfume-excerpt">
                                    <?php the_excerpt(); ?>
                                </div>
                            <?php endif; ?>

                            <div class="parfume-actions">
                                <a href="<?php the_permalink(); ?>" class="btn btn-primary">Виж детайли</a>
                            </div>
                        </div>
                    </article>

                <?php endwhile; ?>
            </div>

            <?php
            // Pagination
            the_posts_pagination(array(
                'mid_size' => 2,
                'prev_text' => '« Предишна',
                'next_text' => 'Следваща »',
                'before_page_number' => '<span class="meta-nav screen-reader-text">Страница </span>',
            ));
            ?>

        <?php else : ?>
            
            <div class="no-results">
                <h2>Няма намерени парфюми</h2>
                <p>Не са намерени парфюми от този тип. Опитайте с други филтри или се върнете към <a href="<?php echo esc_url(get_post_type_archive_link('parfumes')); ?>">главната страница с парфюми</a>.</p>
            </div>

        <?php endif; ?>

    </div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // Filter functionality
    $('#apply-filters').on('click', function() {
        var url = new URL(window.location);
        
        // Get filter values
        var marki = $('#filter-marki').val();
        var vid = $('#filter-vid').val();
        var season = $('#filter-season').val();
        var intensity = $('#filter-intensity').val();
        
        // Update URL parameters
        if (marki) {
            url.searchParams.set('parfume_marki', marki);
        } else {
            url.searchParams.delete('parfume_marki');
        }
        
        if (vid) {
            url.searchParams.set('parfume_vid', vid);
        } else {
            url.searchParams.delete('parfume_vid');
        }
        
        if (season) {
            url.searchParams.set('parfume_season', season);
        } else {
            url.searchParams.delete('parfume_season');
        }
        
        if (intensity) {
            url.searchParams.set('parfume_intensity', intensity);
        } else {
            url.searchParams.delete('parfume_intensity');
        }
        
        // Redirect to filtered URL
        window.location.href = url.toString();
    });

    // Clear filters
    $('#clear-filters').on('click', function() {
        var url = new URL(window.location);
        url.searchParams.delete('parfume_marki');
        url.searchParams.delete('parfume_vid');
        url.searchParams.delete('parfume_season');
        url.searchParams.delete('parfume_intensity');
        window.location.href = url.toString();
    });
    
    // Comparison functionality placeholder
    $('.compare-btn').on('click', function() {
        var parfumeId = $(this).data('parfume-id');
        var action = $(this).data('action');
        
        // This will be handled by the comparison module
        if (typeof window.parfumeComparison !== 'undefined') {
            if (action === 'add') {
                window.parfumeComparison.addParfume(parfumeId);
            } else {
                window.parfumeComparison.removeParfume(parfumeId);
            }
        }
    });
});
</script>

<?php get_footer(); ?>