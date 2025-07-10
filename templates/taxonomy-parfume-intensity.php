<?php
/**
 * Template for parfume intensity taxonomy archive
 * 
 * @package ParfumeCatalog
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

get_header(); ?>

<div class="parfume-taxonomy-archive parfume-intensity-archive">
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

            <!-- Intensity Navigation -->
            <div class="intensity-nav">
                <h3>Интензивност на аромата</h3>
                <div class="intensity-list">
                    <?php
                    $intensity_terms = get_terms(array(
                        'taxonomy' => 'parfume_intensity',
                        'hide_empty' => true,
                        'orderby' => 'name',
                        'order' => 'ASC'
                    ));
                    
                    if (!is_wp_error($intensity_terms) && !empty($intensity_terms)) {
                        $current_term = get_queried_object();
                        
                        // Define intensity levels and indicators
                        $intensity_indicators = array(
                            'леки' => array('level' => 1, 'icon' => '◦'),
                            'light' => array('level' => 1, 'icon' => '◦'),
                            'фини' => array('level' => 2, 'icon' => '◦◦'),
                            'деликатни' => array('level' => 2, 'icon' => '◦◦'),
                            'delicate' => array('level' => 2, 'icon' => '◦◦'),
                            'средни' => array('level' => 3, 'icon' => '●◦◦'),
                            'medium' => array('level' => 3, 'icon' => '●◦◦'),
                            'силни' => array('level' => 4, 'icon' => '●●◦'),
                            'strong' => array('level' => 4, 'icon' => '●●◦'),
                            'интензивни' => array('level' => 5, 'icon' => '●●●'),
                            'intensive' => array('level' => 5, 'icon' => '●●●'),
                            'тежки' => array('level' => 5, 'icon' => '●●●'),
                            'heavy' => array('level' => 5, 'icon' => '●●●'),
                            'пудрени' => array('level' => 2, 'icon' => '◦◦'),
                            'powdery' => array('level' => 2, 'icon' => '◦◦')
                        );
                        
                        foreach ($intensity_terms as $intensity_term) {
                            $is_current = ($current_term && $current_term->term_id === $intensity_term->term_id);
                            $class = $is_current ? 'intensity-link current' : 'intensity-link';
                            
                            // Get intensity indicator
                            $indicator = '';
                            $level = 0;
                            foreach ($intensity_indicators as $intensity_key => $intensity_data) {
                                if (stripos($intensity_term->name, $intensity_key) !== false || stripos($intensity_term->slug, $intensity_key) !== false) {
                                    $indicator = $intensity_data['icon'];
                                    $level = $intensity_data['level'];
                                    break;
                                }
                            }
                            
                            printf(
                                '<a href="%s" class="%s" data-level="%d">%s %s <span class="count">(%d)</span></a>',
                                esc_url(get_term_link($intensity_term)),
                                esc_attr($class),
                                $level,
                                $indicator,
                                esc_html($intensity_term->name),
                                $intensity_term->count
                            );
                        }
                    }
                    ?>
                </div>
            </div>

            <!-- Intensity Info -->
            <?php
            $current_intensity = get_queried_object();
            if ($current_intensity) :
            ?>
                <div class="intensity-info">
                    <h3>Парфюми с интензивност: <?php echo esc_html($current_intensity->name); ?></h3>
                    <div class="intensity-description">
                        <?php
                        $intensity_slug = $current_intensity->slug;
                        $intensity_descriptions = array(
                            'леки' => 'Леки парфюми са перфектни за ежедневна употреба. Те са дискретни и ненатрапчиви, подходящи за офиса и дневни дейности.',
                            'light' => 'Леки парфюми са перфектни за ежедневна употреба. Те са дискретни и ненатрапчиви, подходящи за офиса и дневни дейности.',
                            'фини' => 'Фини парфюми са елегантни и деликатни. Създават нежна ароматна аура около носещия ги.',
                            'деликатни' => 'Деликатни парфюми са нежни и изисквани. Идеални за хора, които предпочитат по-тънки аромати.',
                            'delicate' => 'Деликатни парфюми са нежни и изисквани. Идеални за хора, които предпочитат по-тънки аромати.',
                            'средни' => 'Парфюми със средна интензивност са универсални. Подходящи както за ден, така и за вечер.',
                            'medium' => 'Парфюми със средна интензивност са универсални. Подходящи както за ден, така и за вечер.',
                            'силни' => 'Силни парфюми правят впечатление. Те са с по-дълга трайност и се усещат от разстояние.',
                            'strong' => 'Силни парфюми правят впечатление. Те са с по-дълга трайност и се усещат от разстояние.',
                            'интензивни' => 'Интензивни парфюми са за специални поводи. Те са мощни, дълготрайни и оставят незабравима следа.',
                            'intensive' => 'Интензивни парфюми са за специални поводи. Те са мощни, дълготрайни и оставят незабравима следа.',
                            'тежки' => 'Тежки парфюми са богати и комплексни. Подходящи за вечерни излизания и специални събития.',
                            'heavy' => 'Тежки парфюми са богати и комплексни. Подходящи за вечерни излизания и специални събития.',
                            'пудрени' => 'Пудрени парфюми са мекі и успокояващи, с нотки що напомнят на детска пудра и нежност.',
                            'powdery' => 'Пудрени парфюми са меки и успокояващи, с нотки що напомнят на детска пудра и нежност.'
                        );
                        
                        foreach ($intensity_descriptions as $intensity_key => $description) {
                            if (stripos($intensity_slug, $intensity_key) !== false) {
                                echo '<p>' . esc_html($description) . '</p>';
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
                            
                            <!-- Intensity indicator -->
                            <?php
                            $intensities = wp_get_post_terms(get_the_ID(), 'parfume_intensity');
                            if (!empty($intensities) && !is_wp_error($intensities)) :
                            ?>
                                <div class="intensity-indicator">
                                    <?php
                                    foreach ($intensities as $intensity) {
                                        $indicator = '';
                                        $intensity_indicators = array(
                                            'леки' => '◦',
                                            'light' => '◦',
                                            'фини' => '◦◦',
                                            'деликатни' => '◦◦',
                                            'delicate' => '◦◦',
                                            'средни' => '●◦◦',
                                            'medium' => '●◦◦',
                                            'силни' => '●●◦',
                                            'strong' => '●●◦',
                                            'интензивни' => '●●●',
                                            'intensive' => '●●●',
                                            'тежки' => '●●●',
                                            'heavy' => '●●●',
                                            'пудрени' => '◦◦',
                                            'powdery' => '◦◦'
                                        );
                                        
                                        foreach ($intensity_indicators as $intensity_key => $intensity_icon) {
                                            if (stripos($intensity->name, $intensity_key) !== false || stripos($intensity->slug, $intensity_key) !== false) {
                                                $indicator = $intensity_icon;
                                                break;
                                            }
                                        }
                                        
                                        printf(
                                            '<span class="intensity-icon" title="%s">%s</span>',
                                            esc_attr($intensity->name),
                                            $indicator
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

                                // Текуща интензивност
                                $current_intensity = get_queried_object();
                                if ($current_intensity) {
                                    echo '<span class="parfume-current-intensity">';
                                    echo '<strong>Интензивност:</strong> ' . esc_html($current_intensity->name);
                                    echo '</span>';
                                }
                                ?>
                            </div>

                            <?php if (has_excerpt()) : ?>
                                <div class="parfume-excerpt">
                                    <?php the_excerpt(); ?>
                                </div>
                            <?php endif; ?>

                            <!-- Longevity and sillage indicators -->
                            <?php
                            $longevity = get_post_meta(get_the_ID(), '_parfume_longevity', true);
                            $sillage = get_post_meta(get_the_ID(), '_parfume_sillage', true);
                            if ($longevity || $sillage) :
                            ?>
                                <div class="parfume-performance">
                                    <?php if ($longevity) : ?>
                                        <span class="longevity" title="Дълготрайност: <?php echo esc_attr($longevity); ?>">
                                            <strong>Трайност:</strong> <?php echo esc_html($longevity); ?>
                                        </span>
                                    <?php endif; ?>
                                    
                                    <?php if ($sillage) : ?>
                                        <span class="sillage" title="Ароматна следа: <?php echo esc_attr($sillage); ?>">
                                            <strong>Следа:</strong> <?php echo esc_html($sillage); ?>
                                        </span>
                                    <?php endif; ?>
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
                <p>Не са намерени парфюми с тази интензивност. Опитайте с други филтри или се върнете към <a href="<?php echo esc_url(get_post_type_archive_link('parfumes')); ?>">главната страница с парфюми</a>.</p>
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
        var season = $('#filter-season').val();
        
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
        
        if (season) {
            url.searchParams.set('parfume_season', season);
        } else {
            url.searchParams.delete('parfume_season');
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
        url.searchParams.delete('parfume_season');
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