<?php
/**
 * Template for parfume vid (fragrance type) taxonomy archive
 * 
 * @package ParfumeCatalog
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

get_header(); ?>

<div class="parfume-taxonomy-archive parfume-vid-archive">
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

            <!-- Vid Types Navigation -->
            <div class="vid-types-nav">
                <h3>Видове аромати</h3>
                <div class="vid-types-list">
                    <?php
                    $vid_terms = get_terms(array(
                        'taxonomy' => 'parfume_vid',
                        'hide_empty' => true,
                        'orderby' => 'name',
                        'order' => 'ASC'
                    ));
                    
                    if (!is_wp_error($vid_terms) && !empty($vid_terms)) {
                        $current_term = get_queried_object();
                        foreach ($vid_terms as $vid_term) {
                            $is_current = ($current_term && $current_term->term_id === $vid_term->term_id);
                            $class = $is_current ? 'vid-type-link current' : 'vid-type-link';
                            
                            printf(
                                '<a href="%s" class="%s">%s <span class="count">(%d)</span></a>',
                                esc_url(get_term_link($vid_term)),
                                esc_attr($class),
                                esc_html($vid_term->name),
                                $vid_term->count
                            );
                        }
                    }
                    ?>
                </div>
            </div>

            <div class="parfume-filters">
                <div class="filter-section">
                    <h3>Филтри</h3>
                    
                    <!-- Тип филтър -->
                    <div class="filter-group">
                        <label for="filter-type">Тип:</label>
                        <select id="filter-type" name="parfume_type">
                            <option value="">Всички типове</option>
                            <?php
                            $type_terms = get_terms(array(
                                'taxonomy' => 'parfume_type',
                                'hide_empty' => true,
                                'orderby' => 'name',
                                'order' => 'ASC'
                            ));
                            
                            if (!is_wp_error($type_terms) && !empty($type_terms)) {
                                foreach ($type_terms as $type) {
                                    $selected = (isset($_GET['parfume_type']) && $_GET['parfume_type'] === $type->slug) ? 'selected' : '';
                                    printf(
                                        '<option value="%s" %s>%s (%d)</option>',
                                        esc_attr($type->slug),
                                        $selected,
                                        esc_html($type->name),
                                        $type->count
                                    );
                                }
                            }
                            ?>
                        </select>
                    </div>

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

            <!-- Sorting Options -->
            <div class="parfume-sorting">
                <label for="sort-by">Подреди по:</label>
                <select id="sort-by" name="orderby">
                    <option value="date" <?php selected(get_query_var('orderby'), 'date'); ?>>Най-нови</option>
                    <option value="title" <?php selected(get_query_var('orderby'), 'title'); ?>>Име (А-Я)</option>
                    <option value="menu_order" <?php selected(get_query_var('orderby'), 'menu_order'); ?>>Подредба</option>
                    <option value="rand" <?php selected(get_query_var('orderby'), 'rand'); ?>>Случайно</option>
                </select>
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

                                // Тип
                                $types = wp_get_post_terms(get_the_ID(), 'parfume_type');
                                if (!empty($types) && !is_wp_error($types)) {
                                    echo '<span class="parfume-category">';
                                    echo '<strong>Тип:</strong> ';
                                    $type_links = array();
                                    foreach ($types as $type) {
                                        $type_links[] = sprintf(
                                            '<a href="%s">%s</a>',
                                            esc_url(get_term_link($type)),
                                            esc_html($type->name)
                                        );
                                    }
                                    echo implode(', ', $type_links);
                                    echo '</span>';
                                }

                                // Концентрация информация
                                $current_vid = get_queried_object();
                                if ($current_vid) {
                                    echo '<span class="parfume-concentration">';
                                    echo '<strong>Концентрация:</strong> ' . esc_html($current_vid->name);
                                    echo '</span>';
                                }
                                ?>
                            </div>

                            <?php if (has_excerpt()) : ?>
                                <div class="parfume-excerpt">
                                    <?php the_excerpt(); ?>
                                </div>
                            <?php endif; ?>

                            <!-- Basic notes preview -->
                            <?php
                            $top_notes = get_post_meta(get_the_ID(), '_parfume_top_notes', true);
                            if (!empty($top_notes)) :
                            ?>
                                <div class="parfume-notes-preview">
                                    <strong>Върхни нотки:</strong>
                                    <span class="notes-list">
                                        <?php 
                                        if (is_array($top_notes)) {
                                            $note_names = array();
                                            foreach ($top_notes as $note_id) {
                                                $note_term = get_term($note_id, 'parfume_notes');
                                                if (!is_wp_error($note_term) && $note_term) {
                                                    $note_names[] = $note_term->name;
                                                }
                                            }
                                            echo esc_html(implode(', ', array_slice($note_names, 0, 3)));
                                            if (count($note_names) > 3) {
                                                echo '...';
                                            }
                                        }
                                        ?>
                                    </span>
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
                <p>Не са намерени парфюми от този вид аромат. Опитайте с други филтри или се върнете към <a href="<?php echo esc_url(get_post_type_archive_link('parfumes')); ?>">главната страница с парфюми</a>.</p>
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
        var type = $('#filter-type').val();
        var marki = $('#filter-marki').val();
        var season = $('#filter-season').val();
        var intensity = $('#filter-intensity').val();
        
        // Update URL parameters
        if (type) {
            url.searchParams.set('parfume_type', type);
        } else {
            url.searchParams.delete('parfume_type');
        }
        
        if (marki) {
            url.searchParams.set('parfume_marki', marki);
        } else {
            url.searchParams.delete('parfume_marki');
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
        url.searchParams.delete('parfume_type');
        url.searchParams.delete('parfume_marki');
        url.searchParams.delete('parfume_season');
        url.searchParams.delete('parfume_intensity');
        window.location.href = url.toString();
    });

    // Sorting functionality
    $('#sort-by').on('change', function() {
        var url = new URL(window.location);
        var orderby = $(this).val();
        
        if (orderby && orderby !== 'date') {
            url.searchParams.set('orderby', orderby);
        } else {
            url.searchParams.delete('orderby');
        }
        
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