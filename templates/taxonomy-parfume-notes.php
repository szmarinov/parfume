<?php
/**
 * Template for parfume notes taxonomy archive
 * 
 * @package ParfumeCatalog
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

get_header(); ?>

<div class="parfume-taxonomy-archive parfume-notes-archive">
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

            <!-- Note Group Info -->
            <?php
            $current_note = get_queried_object();
            $note_group = get_term_meta($current_note->term_id, 'note_group', true);
            if ($note_group) :
            ?>
                <div class="note-info">
                    <div class="note-group-badge">
                        <span class="note-group-label">Група:</span>
                        <span class="note-group-name <?php echo esc_attr(sanitize_title($note_group)); ?>"><?php echo esc_html($note_group); ?></span>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Notes by Group Navigation -->
            <div class="notes-by-group">
                <h3>Нотки по групи</h3>
                <div class="note-groups-nav">
                    <?php
                    // Get all notes and group them
                    $all_notes = get_terms(array(
                        'taxonomy' => 'parfume_notes',
                        'hide_empty' => true,
                        'orderby' => 'name',
                        'order' => 'ASC'
                    ));
                    
                    $grouped_notes = array();
                    if (!is_wp_error($all_notes) && !empty($all_notes)) {
                        foreach ($all_notes as $note) {
                            $group = get_term_meta($note->term_id, 'note_group', true);
                            if (empty($group)) {
                                $group = 'Други';
                            }
                            
                            if (!isset($grouped_notes[$group])) {
                                $grouped_notes[$group] = array();
                            }
                            $grouped_notes[$group][] = $note;
                        }
                    }
                    
                    // Display group navigation
                    if (!empty($grouped_notes)) {
                        $group_colors = array(
                            'цветни' => '#ff6b9d',
                            'плодови' => '#ff8c00',
                            'цитрусови' => '#ffd700',
                            'зелени' => '#32cd32',
                            'ароматни' => '#9370db',
                            'подправки' => '#dc143c',
                            'дървесни' => '#8b4513',
                            'ориенталски' => '#cd853f',
                            'мускусни' => '#708090',
                            'амброви' => '#ffb347',
                            'морски' => '#4682b4',
                            'гурме' => '#d2691e',
                            'други' => '#696969'
                        );
                        
                        foreach ($grouped_notes as $group_name => $group_notes) {
                            $group_slug = sanitize_title($group_name);
                            $color = isset($group_colors[strtolower($group_slug)]) ? $group_colors[strtolower($group_slug)] : '#696969';
                            $total_count = count($group_notes);
                            
                            printf(
                                '<a href="#group-%s" class="group-nav-link" style="border-color: %s;">
                                    <span class="group-color" style="background-color: %s;"></span>
                                    %s <span class="count">(%d)</span>
                                </a>',
                                esc_attr($group_slug),
                                esc_attr($color),
                                esc_attr($color),
                                esc_html($group_name),
                                $total_count
                            );
                        }
                    }
                    ?>
                </div>
            </div>

            <!-- Notes Groups Display -->
            <div class="notes-groups-display">
                <?php
                if (!empty($grouped_notes)) {
                    foreach ($grouped_notes as $group_name => $group_notes) {
                        $group_slug = sanitize_title($group_name);
                        $current_note = get_queried_object();
                        $is_current_group = false;
                        
                        // Check if current note is in this group
                        foreach ($group_notes as $note) {
                            if ($note->term_id === $current_note->term_id) {
                                $is_current_group = true;
                                break;
                            }
                        }
                        
                        printf('<div id="group-%s" class="note-group-section %s">', 
                               esc_attr($group_slug), 
                               $is_current_group ? 'current-group' : '');
                        
                        printf('<h4 class="group-title">%s (%d нотки)</h4>', 
                               esc_html($group_name), 
                               count($group_notes));
                        
                        echo '<div class="notes-in-group">';
                        foreach ($group_notes as $note) {
                            $is_current = ($note->term_id === $current_note->term_id);
                            $class = $is_current ? 'note-link current' : 'note-link';
                            
                            printf(
                                '<a href="%s" class="%s">%s <span class="count">(%d)</span></a>',
                                esc_url(get_term_link($note)),
                                esc_attr($class),
                                esc_html($note->name),
                                $note->count
                            );
                        }
                        echo '</div>';
                        echo '</div>';
                    }
                }
                ?>
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

                    <!-- Група нотки филтър -->
                    <div class="filter-group">
                        <label for="filter-note-group">Група нотки:</label>
                        <select id="filter-note-group" name="note_group">
                            <option value="">Всички групи</option>
                            <?php
                            if (!empty($grouped_notes)) {
                                $current_group = isset($_GET['note_group']) ? $_GET['note_group'] : '';
                                foreach ($grouped_notes as $group_name => $group_notes) {
                                    $group_slug = sanitize_title($group_name);
                                    $selected = ($current_group === $group_slug) ? 'selected' : '';
                                    printf(
                                        '<option value="%s" %s>%s (%d)</option>',
                                        esc_attr($group_slug),
                                        $selected,
                                        esc_html($group_name),
                                        count($group_notes)
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
                            
                            <!-- Note position indicator -->
                            <?php
                            $current_note_id = $current_note->term_id;
                            $top_notes = get_post_meta(get_the_ID(), '_parfume_top_notes', true);
                            $middle_notes = get_post_meta(get_the_ID(), '_parfume_middle_notes', true);
                            $base_notes = get_post_meta(get_the_ID(), '_parfume_base_notes', true);
                            
                            $note_position = '';
                            if (is_array($top_notes) && in_array($current_note_id, $top_notes)) {
                                $note_position = 'Върхна нотка';
                            } elseif (is_array($middle_notes) && in_array($current_note_id, $middle_notes)) {
                                $note_position = 'Средна нотка';
                            } elseif (is_array($base_notes) && in_array($current_note_id, $base_notes)) {
                                $note_position = 'Базова нотка';
                            }
                            
                            if ($note_position) :
                            ?>
                                <div class="note-position-indicator">
                                    <span class="position-badge"><?php echo esc_html($note_position); ?></span>
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

                                // Note position in this perfume
                                if ($note_position) {
                                    echo '<span class="note-position">';
                                    echo '<strong>Позиция:</strong> ' . esc_html($note_position);
                                    echo '</span>';
                                }
                                ?>
                            </div>

                            <?php if (has_excerpt()) : ?>
                                <div class="parfume-excerpt">
                                    <?php the_excerpt(); ?>
                                </div>
                            <?php endif; ?>

                            <!-- Related notes in same perfume -->
                            <?php
                            $all_notes_in_perfume = array();
                            if (is_array($top_notes)) $all_notes_in_perfume = array_merge($all_notes_in_perfume, $top_notes);
                            if (is_array($middle_notes)) $all_notes_in_perfume = array_merge($all_notes_in_perfume, $middle_notes);
                            if (is_array($base_notes)) $all_notes_in_perfume = array_merge($all_notes_in_perfume, $base_notes);
                            
                            $all_notes_in_perfume = array_unique($all_notes_in_perfume);
                            $other_notes = array_diff($all_notes_in_perfume, array($current_note_id));
                            
                            if (!empty($other_notes)) :
                            ?>
                                <div class="related-notes">
                                    <strong>Други нотки в този парфюм:</strong>
                                    <div class="notes-list">
                                        <?php
                                        $note_links = array();
                                        foreach (array_slice($other_notes, 0, 5) as $note_id) {
                                            $note_term = get_term($note_id, 'parfume_notes');
                                            if (!is_wp_error($note_term) && $note_term) {
                                                $note_links[] = sprintf(
                                                    '<a href="%s" class="note-tag">%s</a>',
                                                    esc_url(get_term_link($note_term)),
                                                    esc_html($note_term->name)
                                                );
                                            }
                                        }
                                        echo implode(', ', $note_links);
                                        
                                        if (count($other_notes) > 5) {
                                            echo '...';
                                        }
                                        ?>
                                    </div>
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
                <p>Не са намерени парфюми с тази нотка. Опитайте с други филтри или се върнете към <a href="<?php echo esc_url(get_post_type_archive_link('parfumes')); ?>">главната страница с парфюми</a>.</p>
            </div>

        <?php endif; ?>

    </div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // Smooth scroll to group sections
    $('.group-nav-link').on('click', function(e) {
        e.preventDefault();
        var target = $(this).attr('href');
        if ($(target).length) {
            $('html, body').animate({
                scrollTop: $(target).offset().top - 100
            }, 800);
        }
    });

    // Filter functionality
    $('#apply-filters').on('click', function() {
        var url = new URL(window.location);
        
        // Get filter values
        var type = $('#filter-type').val();
        var marki = $('#filter-marki').val();
        var noteGroup = $('#filter-note-group').val();
        
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
        
        if (noteGroup) {
            url.searchParams.set('note_group', noteGroup);
        } else {
            url.searchParams.delete('note_group');
        }
        
        // Redirect to filtered URL
        window.location.href = url.toString();
    });

    // Clear filters
    $('#clear-filters').on('click', function() {
        var url = new URL(window.location);
        url.searchParams.delete('parfume_type');
        url.searchParams.delete('parfume_marki');
        url.searchParams.delete('note_group');
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