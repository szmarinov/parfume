<?php
/**
 * Template for parfume season taxonomy archive
 * 
 * @package ParfumeCatalog
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

get_header(); ?>

<div class="parfume-taxonomy-archive parfume-season-archive">
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

            <!-- Season Navigation -->
            <div class="season-nav">
                <h3>Сезони</h3>
                <div class="season-list">
                    <?php
                    $season_terms = get_terms(array(
                        'taxonomy' => 'parfume_season',
                        'hide_empty' => true,
                        'orderby' => 'name',
                        'order' => 'ASC'
                    ));
                    
                    if (!is_wp_error($season_terms) && !empty($season_terms)) {
                        $current_term = get_queried_object();
                        
                        // Define season icons
                        $season_icons = array(
                            'prolет' => '🌸',
                            'spring' => '🌸',
                            'лято' => '☀️',
                            'summer' => '☀️',
                            'есен' => '🍂',
                            'autumn' => '🍂',
                            'зима' => '❄️',
                            'winter' => '❄️'
                        );
                        
                        foreach ($season_terms as $season_term) {
                            $is_current = ($current_term && $current_term->term_id === $season_term->term_id);
                            $class = $is_current ? 'season-link current' : 'season-link';
                            
                            // Get season icon
                            $icon = '';
                            foreach ($season_icons as $season_key => $season_icon) {
                                if (stripos($season_term->name, $season_key) !== false || stripos($season_term->slug, $season_key) !== false) {
                                    $icon = $season_icon;
                                    break;
                                }
                            }
                            
                            printf(
                                '<a href="%s" class="%s">%s %s <span class="count">(%d)</span></a>',
                                esc_url(get_term_link($season_term)),
                                esc_attr($class),
                                $icon,
                                esc_html($season_term->name),
                                $season_term->count
                            );
                        }
                    }
                    ?>
                </div>
            </div>

            <!-- Season Info -->
            <?php
            $current_season = get_queried_object();
            if ($current_season) :
            ?>
                <div class="season-info">
                    <h3>Парфюми за <?php echo esc_html($current_season->name); ?></h3>
                    <div class="season-description">
                        <?php
                        $season_slug = $current_season->slug;
                        $season_tips = array(
                            'prolет' => 'Пролетните парфюми са свежи и цветни, с нотки на цъфнали цветя и зелени листа. Идеални за прохладните пролетни дни.',
                            'spring' => 'Пролетните парфюми са свежи и цветни, с нотки на цъфнали цветя и зелени листа. Идеални за прохладните пролетни дни.',
                            'лято' => 'Летните парфюми са леки и освежаващи, с цитрусови и морски нотки. Перфектни за горещите летни дни.',
                            'summer' => 'Летните парфюми са леки и освежаващи, с цитрусови и морски нотки. Перфектни за горещите летни дни.',
                            'есен' => 'Есенните парфюми са топли и пикантни, с нотки на подправки и дърво. Подходящи за прохладните есенни дни.',
                            'autumn' => 'Есенните парфюми са топли и пикантни, с нотки на подправки и дърво. Подходящи за прохладните есенни дни.',
                            'зима' => 'Зимните парфюми са богати и интензивни, с нотки на амбра, мускус и ванилия. Идеални за студените зимни дни.',
                            'winter' => 'Зимните парфюми са богати и интензивни, с нотки на амбра, мускус и ванилия. Идеални за студените зимни дни.'
                        );
                        
                        foreach ($season_tips as $season_key => $tip) {
                            if (stripos($season_slug, $season_key) !== false) {
                                echo '<p>' . esc_html($tip) . '</p>';
                                break;
                            }
                        }
                        ?>
                    </div>
                </div>
            <?php endif; ?>

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
                            
                            <!-- Season indicator -->
                            <?php
                            $seasons = wp_get_post_terms(get_the_ID(), 'parfume_season');
                            if (!empty($seasons) && !is_wp_error($seasons)) :
                            ?>
                                <div class="season-indicators">
                                    <?php
                                    foreach ($seasons as $season) {
                                        $icon = '';
                                        $season_icons = array(
                                            'prolет' => '🌸',
                                            'spring' => '🌸',
                                            'лято' => '☀️',
                                            'summer' => '☀️',
                                            'есен' => '🍂',
                                            'autumn' => '🍂',
                                            'зима' => '❄️',
                                            'winter' => '❄️'
                                        );
                                        
                                        foreach ($season_icons as $season_key => $season_icon) {
                                            if (stripos($season->name, $season_key) !== false || stripos($season->slug, $season_key) !== false) {
                                                $icon = $season_icon;
                                                break;
                                            }
                                        }
                                        
                                        printf(
                                            '<span class="season-icon" title="%s">%s</span>',
                                            esc_attr($season->name),
                                            $icon
                                        );
                                    }
                                    ?>
                                </div>
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

                                // Интензивност
                                $intensity = wp_get_post_terms(get_the_ID(), 'parfume_intensity');
                                if (!empty($intensity) && !is_wp_error($intensity)) {
                                    echo '<span class="parfume-intensity">';
                                    echo '<strong>Интензивност:</strong> ';
                                    $intensity_links = array();
                                    foreach ($intensity as $int) {
                                        $intensity_links[] = sprintf(
                                            '<a href="%s">%s</a>',
                                            esc_url(get_term_link($int)),
                                            esc_html($int->name)
                                        );
                                    }
                                    echo implode(', ', $intensity_links);
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
                <p>Не са намерени парфюми за този сезон. Опитайте с други филтри или се върнете към <a href="<?php echo esc_url(get_post_type_archive_link('parfumes')); ?>">главната страница с парфюми</a>.</p>
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
        var vid = $('#filter-vid').val();
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
        
        if (vid) {
            url.searchParams.set('parfume_vid', vid);
        } else {
            url.searchParams.delete('parfume_vid');
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
        url.searchParams.delete('parfume_vid');
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