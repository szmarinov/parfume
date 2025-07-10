<?php
/**
 * Archive template for parfumes
 * 
 * Advanced template with filtering, sorting, and comparison features
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();

// Get current query data
$queried_object = get_queried_object();
$is_taxonomy = is_tax();
$taxonomy_name = $is_taxonomy ? $queried_object->taxonomy : '';
$term_name = $is_taxonomy ? $queried_object->name : '';
$term_slug = $is_taxonomy ? $queried_object->slug : '';

// Get settings
$comparison_settings = Parfume_Catalog_Admin_Comparison::get_comparison_settings();
$filter_settings = Parfume_Catalog_Filters::get_filter_settings();

// Handle query modifications for filters
if (isset($_GET['parfume_filter']) && $_GET['parfume_filter'] === '1') {
    add_action('pre_get_posts', 'parfume_modify_archive_query');
}

function parfume_modify_archive_query($query) {
    if (!is_admin() && $query->is_main_query() && (is_post_type_archive('parfumes') || is_tax())) {
        // Sorting
        if (!empty($_GET['sort_by'])) {
            switch ($_GET['sort_by']) {
                case 'date':
                    $query->set('orderby', 'date');
                    $query->set('order', 'DESC');
                    break;
                case 'title':
                    $query->set('orderby', 'title');
                    $query->set('order', 'ASC');
                    break;
                case 'price':
                    $query->set('meta_key', '_parfume_min_price');
                    $query->set('orderby', 'meta_value_num');
                    $query->set('order', 'ASC');
                    break;
                case 'rating':
                    $query->set('meta_key', '_parfume_rating');
                    $query->set('orderby', 'meta_value_num');
                    $query->set('order', 'DESC');
                    break;
            }
        }
        
        // Build tax query
        $tax_query = array('relation' => 'AND');
        $meta_query = array('relation' => 'AND');
        
        // Brand filter
        if (!empty($_GET['parfume_marki'])) {
            $tax_query[] = array(
                'taxonomy' => 'parfume_marki',
                'field' => 'slug',
                'terms' => sanitize_text_field($_GET['parfume_marki'])
            );
        }
        
        // Type filter
        if (!empty($_GET['parfume_type'])) {
            $tax_query[] = array(
                'taxonomy' => 'parfume_type',
                'field' => 'slug',
                'terms' => sanitize_text_field($_GET['parfume_type'])
            );
        }
        
        // Vid filter
        if (!empty($_GET['parfume_vid'])) {
            $tax_query[] = array(
                'taxonomy' => 'parfume_vid',
                'field' => 'slug',
                'terms' => sanitize_text_field($_GET['parfume_vid'])
            );
        }
        
        // Season filter
        if (!empty($_GET['parfume_season'])) {
            $tax_query[] = array(
                'taxonomy' => 'parfume_season',
                'field' => 'slug',
                'terms' => sanitize_text_field($_GET['parfume_season'])
            );
        }
        
        // Intensity filter
        if (!empty($_GET['parfume_intensity'])) {
            $tax_query[] = array(
                'taxonomy' => 'parfume_intensity',
                'field' => 'slug',
                'terms' => sanitize_text_field($_GET['parfume_intensity'])
            );
        }
        
        // Price range filter
        if (!empty($_GET['price_range'])) {
            $price_range = sanitize_text_field($_GET['price_range']);
            $price_parts = explode('-', $price_range);
            
            if (count($price_parts) == 2) {
                $meta_query[] = array(
                    'key' => '_parfume_price_range',
                    'value' => array(absint($price_parts[0]), absint($price_parts[1])),
                    'compare' => 'BETWEEN',
                    'type' => 'NUMERIC'
                );
            }
        }
        
        // Search in notes
        if (!empty($_GET['notes_search'])) {
            $tax_query[] = array(
                'taxonomy' => 'parfume_notes',
                'field' => 'name',
                'terms' => sanitize_text_field($_GET['notes_search']),
                'operator' => 'LIKE'
            );
        }
        
        if (!empty($tax_query) && count($tax_query) > 1) {
            $query->set('tax_query', $tax_query);
        }
        
        if (!empty($meta_query) && count($meta_query) > 1) {
            $query->set('meta_query', $meta_query);
        }
    }
}
?>

<div class="parfumes-archive-container">
    <div class="archive-header">
        <div class="archive-title-section">
            <?php if ($is_taxonomy): ?>
                <h1 class="archive-title">
                    <?php 
                    switch ($taxonomy_name) {
                        case 'parfume_type':
                            printf(__('Парфюми за %s', 'parfume-catalog'), $term_name);
                            break;
                        case 'parfume_vid':
                            printf(__('%s', 'parfume-catalog'), $term_name);
                            break;
                        case 'parfume_marki':
                            printf(__('Парфюми от %s', 'parfume-catalog'), $term_name);
                            break;
                        case 'parfume_season':
                            printf(__('Парфюми за %s', 'parfume-catalog'), $term_name);
                            break;
                        case 'parfume_intensity':
                            printf(__('%s парфюми', 'parfume-catalog'), $term_name);
                            break;
                        case 'parfume_notes':
                            printf(__('Парфюми с нотка %s', 'parfume-catalog'), $term_name);
                            break;
                        default:
                            echo esc_html($term_name);
                            break;
                    }
                    ?>
                </h1>
                
                <?php if ($queried_object->description): ?>
                    <div class="archive-description">
                        <?php echo wpautop($queried_object->description); ?>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <h1 class="archive-title"><?php _e('Всички парфюми', 'parfume-catalog'); ?></h1>
                <div class="archive-description">
                    <p><?php _e('Открийте перфектния аромат от нашата колекция от парфюми.', 'parfume-catalog'); ?></p>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="archive-stats">
            <?php
            global $wp_query;
            $total_posts = $wp_query->found_posts;
            printf(_n('Намерен %d парфюм', 'Намерени %d парфюма', $total_posts, 'parfume-catalog'), $total_posts);
            ?>
        </div>
    </div>

    <div class="archive-controls">
        <div class="filters-section">
            <button type="button" id="toggle-filters" class="filters-toggle">
                <span class="dashicons dashicons-filter"></span>
                <?php _e('Филтри', 'parfume-catalog'); ?>
                <span class="toggle-arrow">▼</span>
            </button>
            
            <div id="filters-container" class="filters-container" style="display: none;">
                <form method="get" action="" class="filters-form">
                    <input type="hidden" name="parfume_filter" value="1">
                    
                    <div class="filters-grid">
                        <!-- Brand Filter -->
                        <div class="filter-group">
                            <label for="parfume_marki"><?php _e('Марка', 'parfume-catalog'); ?></label>
                            <select name="parfume_marki" id="parfume_marki" class="filter-select">
                                <option value=""><?php _e('Всички марки', 'parfume-catalog'); ?></option>
                                <?php
                                $brands = get_terms(array(
                                    'taxonomy' => 'parfume_marki',
                                    'hide_empty' => true,
                                    'orderby' => 'name',
                                    'order' => 'ASC'
                                ));
                                foreach ($brands as $brand):
                                ?>
                                    <option value="<?php echo esc_attr($brand->slug); ?>" <?php selected($_GET['parfume_marki'] ?? '', $brand->slug); ?>>
                                        <?php echo esc_html($brand->name); ?> (<?php echo $brand->count; ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <!-- Type Filter -->
                        <div class="filter-group">
                            <label for="parfume_type"><?php _e('Тип', 'parfume-catalog'); ?></label>
                            <select name="parfume_type" id="parfume_type" class="filter-select">
                                <option value=""><?php _e('Всички типове', 'parfume-catalog'); ?></option>
                                <?php
                                $types = get_terms(array(
                                    'taxonomy' => 'parfume_type',
                                    'hide_empty' => true,
                                    'orderby' => 'name',
                                    'order' => 'ASC'
                                ));
                                foreach ($types as $type):
                                ?>
                                    <option value="<?php echo esc_attr($type->slug); ?>" <?php selected($_GET['parfume_type'] ?? '', $type->slug); ?>>
                                        <?php echo esc_html($type->name); ?> (<?php echo $type->count; ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <!-- Vid Filter -->
                        <div class="filter-group">
                            <label for="parfume_vid"><?php _e('Вид аромат', 'parfume-catalog'); ?></label>
                            <select name="parfume_vid" id="parfume_vid" class="filter-select">
                                <option value=""><?php _e('Всички видове', 'parfume-catalog'); ?></option>
                                <?php
                                $vids = get_terms(array(
                                    'taxonomy' => 'parfume_vid',
                                    'hide_empty' => true,
                                    'orderby' => 'name',
                                    'order' => 'ASC'
                                ));
                                foreach ($vids as $vid):
                                ?>
                                    <option value="<?php echo esc_attr($vid->slug); ?>" <?php selected($_GET['parfume_vid'] ?? '', $vid->slug); ?>>
                                        <?php echo esc_html($vid->name); ?> (<?php echo $vid->count; ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <!-- Season Filter -->
                        <div class="filter-group">
                            <label for="parfume_season"><?php _e('Сезон', 'parfume-catalog'); ?></label>
                            <select name="parfume_season" id="parfume_season" class="filter-select">
                                <option value=""><?php _e('Всички сезони', 'parfume-catalog'); ?></option>
                                <?php
                                $seasons = get_terms(array(
                                    'taxonomy' => 'parfume_season',
                                    'hide_empty' => true,
                                    'orderby' => 'name',
                                    'order' => 'ASC'
                                ));
                                foreach ($seasons as $season):
                                ?>
                                    <option value="<?php echo esc_attr($season->slug); ?>" <?php selected($_GET['parfume_season'] ?? '', $season->slug); ?>>
                                        <?php echo esc_html($season->name); ?> (<?php echo $season->count; ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <!-- Intensity Filter -->
                        <div class="filter-group">
                            <label for="parfume_intensity"><?php _e('Интензивност', 'parfume-catalog'); ?></label>
                            <select name="parfume_intensity" id="parfume_intensity" class="filter-select">
                                <option value=""><?php _e('Всички интензивности', 'parfume-catalog'); ?></option>
                                <?php
                                $intensities = get_terms(array(
                                    'taxonomy' => 'parfume_intensity',
                                    'hide_empty' => true,
                                    'orderby' => 'name',
                                    'order' => 'ASC'
                                ));
                                foreach ($intensities as $intensity):
                                ?>
                                    <option value="<?php echo esc_attr($intensity->slug); ?>" <?php selected($_GET['parfume_intensity'] ?? '', $intensity->slug); ?>>
                                        <?php echo esc_html($intensity->name); ?> (<?php echo $intensity->count; ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <!-- Price Range Filter -->
                        <div class="filter-group">
                            <label for="price_range"><?php _e('Ценови диапазон', 'parfume-catalog'); ?></label>
                            <select name="price_range" id="price_range" class="filter-select">
                                <option value=""><?php _e('Всички цени', 'parfume-catalog'); ?></option>
                                <option value="0-50" <?php selected($_GET['price_range'] ?? '', '0-50'); ?>><?php _e('0-50 лв.', 'parfume-catalog'); ?></option>
                                <option value="50-100" <?php selected($_GET['price_range'] ?? '', '50-100'); ?>><?php _e('50-100 лв.', 'parfume-catalog'); ?></option>
                                <option value="100-200" <?php selected($_GET['price_range'] ?? '', '100-200'); ?>><?php _e('100-200 лв.', 'parfume-catalog'); ?></option>
                                <option value="200-500" <?php selected($_GET['price_range'] ?? '', '200-500'); ?>><?php _e('200-500 лв.', 'parfume-catalog'); ?></option>
                                <option value="500-1000" <?php selected($_GET['price_range'] ?? '', '500-1000'); ?>><?php _e('500+ лв.', 'parfume-catalog'); ?></option>
                            </select>
                        </div>
                        
                        <!-- Notes Search -->
                        <div class="filter-group">
                            <label for="notes_search"><?php _e('Търсене в нотки', 'parfume-catalog'); ?></label>
                            <input type="text" name="notes_search" id="notes_search" class="filter-input" 
                                   value="<?php echo esc_attr($_GET['notes_search'] ?? ''); ?>" 
                                   placeholder="<?php _e('Напр. роза, ванилия...', 'parfume-catalog'); ?>">
                        </div>
                    </div>
                    
                    <div class="filters-actions">
                        <button type="submit" class="button button-primary"><?php _e('Приложи филтрите', 'parfume-catalog'); ?></button>
                        <button type="button" id="clear-filters" class="button"><?php _e('Изчисти', 'parfume-catalog'); ?></button>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="sorting-section">
            <label for="sort-by"><?php _e('Сортиране по:', 'parfume-catalog'); ?></label>
            <select name="sort_by" id="sort-by" class="sort-select">
                <option value="date" <?php selected($_GET['sort_by'] ?? 'date', 'date'); ?>><?php _e('Най-нови', 'parfume-catalog'); ?></option>
                <option value="title" <?php selected($_GET['sort_by'] ?? '', 'title'); ?>><?php _e('Име', 'parfume-catalog'); ?></option>
                <option value="price" <?php selected($_GET['sort_by'] ?? '', 'price'); ?>><?php _e('Цена', 'parfume-catalog'); ?></option>
                <option value="rating" <?php selected($_GET['sort_by'] ?? '', 'rating'); ?>><?php _e('Рейтинг', 'parfume-catalog'); ?></option>
            </select>
        </div>
        
        <div class="view-toggle">
            <button type="button" id="grid-view" class="view-btn active" data-view="grid">
                <span class="dashicons dashicons-grid-view"></span>
            </button>
            <button type="button" id="list-view" class="view-btn" data-view="list">
                <span class="dashicons dashicons-list-view"></span>
            </button>
        </div>
    </div>

    <!-- Active Filters Display -->
    <?php if (!empty($_GET['parfume_filter'])): ?>
        <div class="active-filters">
            <span class="active-filters-label"><?php _e('Активни филтри:', 'parfume-catalog'); ?></span>
            <div class="active-filters-list">
                <?php
                $active_filters = array();
                
                if (!empty($_GET['parfume_marki'])) {
                    $term = get_term_by('slug', $_GET['parfume_marki'], 'parfume_marki');
                    if ($term) {
                        $active_filters[] = array(
                            'label' => $term->name,
                            'param' => 'parfume_marki',
                            'value' => $_GET['parfume_marki']
                        );
                    }
                }
                
                if (!empty($_GET['parfume_type'])) {
                    $term = get_term_by('slug', $_GET['parfume_type'], 'parfume_type');
                    if ($term) {
                        $active_filters[] = array(
                            'label' => $term->name,
                            'param' => 'parfume_type',
                            'value' => $_GET['parfume_type']
                        );
                    }
                }
                
                if (!empty($_GET['price_range'])) {
                    $active_filters[] = array(
                        'label' => $_GET['price_range'] . ' лв.',
                        'param' => 'price_range',
                        'value' => $_GET['price_range']
                    );
                }
                
                if (!empty($_GET['notes_search'])) {
                    $active_filters[] = array(
                        'label' => __('Нотки: ', 'parfume-catalog') . $_GET['notes_search'],
                        'param' => 'notes_search',
                        'value' => $_GET['notes_search']
                    );
                }
                
                foreach ($active_filters as $filter):
                ?>
                    <span class="active-filter-item">
                        <?php echo esc_html($filter['label']); ?>
                        <button type="button" class="remove-filter" data-param="<?php echo esc_attr($filter['param']); ?>">×</button>
                    </span>
                <?php endforeach; ?>
            </div>
            <button type="button" id="clear-all-filters" class="clear-all-filters"><?php _e('Изчисти всички', 'parfume-catalog'); ?></button>
        </div>
    <?php endif; ?>

    <div class="parfumes-content">
        <?php if (have_posts()): ?>
            <div id="parfumes-grid" class="parfumes-grid grid-view">
                <?php while (have_posts()): the_post(); ?>
                    <?php parfume_render_item(get_the_ID()); ?>
                <?php endwhile; ?>
            </div>
            
            <div class="parfumes-pagination">
                <?php
                echo paginate_links(array(
                    'prev_text' => __('← Предишна', 'parfume-catalog'),
                    'next_text' => __('Следваща →', 'parfume-catalog'),
                    'mid_size' => 2,
                    'type' => 'list'
                ));
                ?>
            </div>
        <?php else: ?>
            <div class="no-results">
                <h2><?php _e('Няма намерени парфюми', 'parfume-catalog'); ?></h2>
                <p><?php _e('Моля, опитайте с други критерии за търсене.', 'parfume-catalog'); ?></p>
                <button type="button" id="reset-search" class="button-primary">
                    <?php _e('Изчисти търсенето', 'parfume-catalog'); ?>
                </button>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Comparison Popup -->
<?php if ($comparison_settings['enabled']): ?>
    <div id="comparison-popup" class="comparison-popup" style="display: none;">
        <div class="comparison-header">
            <h3><?php echo esc_html($comparison_settings['texts']['popup_title']); ?></h3>
            <button type="button" class="comparison-close">×</button>
        </div>
        <div class="comparison-content">
            <div class="comparison-items" id="comparison-items"></div>
        </div>
        <div class="comparison-footer">
            <?php if ($comparison_settings['show_clear_all']): ?>
                <button type="button" id="clear-comparison" class="button-secondary">
                    <?php echo esc_html($comparison_settings['texts']['clear_all']); ?>
                </button>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<?php
/**
 * Render single parfume item
 */
function parfume_render_item($post_id) {
    $post = get_post($post_id);
    $parfume_basic = Parfume_Catalog_Meta_Basic::get_parfume_info($post_id);
    $parfume_stores = Parfume_Catalog_Meta_Stores::get_formatted_stores($post_id);
    
    // Get taxonomies
    $brands = get_the_terms($post_id, 'parfume_marki');
    $types = get_the_terms($post_id, 'parfume_type');
    $seasons = get_the_terms($post_id, 'parfume_season');
    $intensities = get_the_terms($post_id, 'parfume_intensity');
    $notes = get_the_terms($post_id, 'parfume_notes');
    
    // Get suitable conditions
    $suitable_conditions = array();
    if ($seasons && !is_wp_error($seasons)) {
        foreach ($seasons as $season) {
            $suitable_conditions[] = $season->slug;
        }
    }
    
    // Add day/night suitability (this would come from meta fields)
    $day_night_suitable = get_post_meta($post_id, '_parfume_day_night_suitable', true);
    if ($day_night_suitable) {
        $suitable_conditions = array_merge($suitable_conditions, $day_night_suitable);
    }
    
    // Get price info
    $min_price = get_post_meta($post_id, '_parfume_min_price', true);
    $price_currency = get_post_meta($post_id, '_parfume_price_currency', true) ?: 'лв.';
    
    // Suitable icons
    $suitable_icons = array(
        'prolet' => '🌸',
        'liato' => '☀️',
        'esen' => '🍂',
        'zima' => '❄️',
        'den' => '🌞',
        'nosht' => '🌙'
    );
    
    $suitable_labels = array(
        'prolet' => __('Пролет', 'parfume-catalog'),
        'liato' => __('Лято', 'parfume-catalog'),
        'esen' => __('Есен', 'parfume-catalog'),
        'zima' => __('Зима', 'parfume-catalog'),
        'den' => __('Ден', 'parfume-catalog'),
        'nosht' => __('Нощ', 'parfume-catalog')
    );
    ?>
    <div class="parfume-item" data-post-id="<?php echo $post_id; ?>">
        <div class="parfume-item-image">
            <?php if (has_post_thumbnail($post_id)): ?>
                <a href="<?php echo get_permalink($post_id); ?>">
                    <?php echo get_the_post_thumbnail($post_id, 'medium', array('class' => 'parfume-image')); ?>
                </a>
            <?php else: ?>
                <div class="parfume-placeholder">
                    <span class="dashicons dashicons-admin-customizer"></span>
                </div>
            <?php endif; ?>
            
            <div class="parfume-item-overlay">
                <button type="button" class="add-to-comparison" data-post-id="<?php echo $post_id; ?>" title="<?php _e('Добави за сравнение', 'parfume-catalog'); ?>">
                    <span class="dashicons dashicons-plus-alt"></span>
                </button>
            </div>
        </div>
        
        <div class="parfume-item-content">
            <div class="parfume-item-header">
                <h3 class="parfume-item-title">
                    <a href="<?php echo get_permalink($post_id); ?>"><?php echo get_the_title($post_id); ?></a>
                </h3>
                
                <?php if ($brands && !is_wp_error($brands)): ?>
                    <div class="parfume-item-brand">
                        <a href="<?php echo get_term_link($brands[0]); ?>">
                            <?php echo esc_html($brands[0]->name); ?>
                        </a>
                    </div>
                <?php endif; ?>
                
                <?php if ($types && !is_wp_error($types)): ?>
                    <div class="parfume-item-type">
                        <?php echo esc_html($types[0]->name); ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="parfume-item-meta">
                <?php if ($min_price): ?>
                    <div class="parfume-item-price">
                        <?php _e('от', 'parfume-catalog'); ?> <strong><?php echo esc_html($min_price . ' ' . $price_currency); ?></strong>
                    </div>
                <?php endif; ?>
                
                <?php if ($notes && !is_wp_error($notes)): ?>
                    <div class="parfume-item-notes">
                        <strong><?php _e('Нотки:', 'parfume-catalog'); ?></strong>
                        <?php
                        $note_names = array();
                        $count = 0;
                        foreach ($notes as $note) {
                            if ($count >= 3) break;
                            $note_names[] = $note->name;
                            $count++;
                        }
                        echo esc_html(implode(', ', $note_names));
                        if (count($notes) > 3) {
                            echo ' <span class="more-notes">+' . (count($notes) - 3) . '</span>';
                        }
                        ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <?php if (!empty($suitable_conditions)): ?>
                <div class="parfume-item-suitable">
                    <div class="suitable-icons">
                        <?php 
                        foreach ($suitable_conditions as $suitable): 
                            if (isset($suitable_icons[$suitable])):
                        ?>
                            <span class="suitable-icon" title="<?php echo esc_attr($suitable_labels[$suitable] ?? ''); ?>">
                                <?php echo $suitable_icons[$suitable]; ?>
                            </span>
                        <?php 
                            endif;
                        endforeach; 
                        ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <div class="parfume-item-footer">
                <a href="<?php echo get_permalink($post_id); ?>" class="view-parfume-btn">
                    <?php _e('Виж детайли', 'parfume-catalog'); ?>
                </a>
            </div>
        </div>
    </div>
    <?php
}
?>

<script>
jQuery(document).ready(function($) {
    // Toggle filters
    $('#toggle-filters').click(function() {
        $('#filters-container').slideToggle();
        $('.toggle-arrow').text($('#filters-container').is(':visible') ? '▲' : '▼');
    });
    
    // Clear filters
    $('#clear-filters').click(function() {
        $('.filters-form')[0].reset();
        window.location.href = window.location.pathname;
    });
    
    // Clear all filters
    $('#clear-all-filters').click(function() {
        window.location.href = window.location.pathname;
    });
    
    // Remove single filter
    $('.remove-filter').click(function() {
        var param = $(this).data('param');
        var url = new URL(window.location.href);
        url.searchParams.delete(param);
        window.location.href = url.toString();
    });
    
    // Sort change
    $('#sort-by').change(function() {
        var url = new URL(window.location.href);
        url.searchParams.set('sort_by', $(this).val());
        url.searchParams.set('parfume_filter', '1');
        window.location.href = url.toString();
    });
    
    // View toggle
    $('.view-btn').click(function() {
        var view = $(this).data('view');
        $('.view-btn').removeClass('active');
        $(this).addClass('active');
        
        $('#parfumes-grid').removeClass('grid-view list-view').addClass(view + '-view');
        $('.parfume-item').removeClass('list-item grid-item').addClass(view + '-item');
        
        localStorage.setItem('parfume_view_preference', view);
    });
    
    // Restore view preference
    var savedView = localStorage.getItem('parfume_view_preference');
    if (savedView) {
        $('.view-btn[data-view="' + savedView + '"]').click();
    }
    
    // Comparison functionality
    $('.add-to-comparison').click(function() {
        var postId = $(this).data('post-id');
        // Add to comparison logic here
        console.log('Add to comparison:', postId);
    });
    
    // Reset search
    $('#reset-search').click(function() {
        window.location.href = window.location.pathname;
    });
});
</script>

<style>
.parfumes-archive-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.archive-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 2px solid #eee;
}

.archive-title {
    margin: 0 0 10px 0;
    font-size: 32px;
    line-height: 1.2;
}

.archive-description {
    color: #666;
    margin: 0;
}

.archive-stats {
    font-size: 14px;
    color: #999;
}

.archive-controls {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 30px;
    gap: 20px;
}

.filters-toggle {
    background: #0073aa;
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 4px;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 14px;
}

.filters-toggle:hover {
    background: #005a87;
}

.filters-container {
    background: #f9f9f9;
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 20px;
    margin-top: 15px;
}

.filters-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 20px;
}

.filter-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: 500;
    color: #555;
}

.filter-input,
.filter-select {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
}

.filters-actions {
    display: flex;
    gap: 10px;
}

.sorting-section {
    display: flex;
    align-items: center;
    gap: 10px;
}

.sort-select {
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.view-toggle {
    display: flex;
    gap: 5px;
}

.view-btn {
    background: #f1f1f1;
    border: 1px solid #ddd;
    padding: 8px 12px;
    cursor: pointer;
    border-radius: 4px;
}

.view-btn.active {
    background: #0073aa;
    color: white;
}

.active-filters {
    display: flex;
    align-items: center;
    gap: 15px;
    margin-bottom: 20px;
    padding: 15px;
    background: #f0f6fc;
    border-radius: 4px;
}

.active-filters-label {
    font-weight: 500;
    color: #333;
}

.active-filters-list {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.active-filter-item {
    background: #0073aa;
    color: white;
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 12px;
    display: flex;
    align-items: center;
    gap: 5px;
}

.remove-filter {
    background: none;
    border: none;
    color: white;
    cursor: pointer;
    font-size: 14px;
    line-height: 1;
}

.clear-all-filters {
    background: #dc3232;
    color: white;
    border: none;
    padding: 6px 12px;
    border-radius: 4px;
    cursor: pointer;
    font-size: 12px;
}

.parfumes-grid {
    display: grid;
    gap: 30px;
    margin-bottom: 40px;
}

.parfumes-grid.grid-view {
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
}

.parfumes-grid.list-view {
    grid-template-columns: 1fr;
}

.parfume-item {
    background: white;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    transition: transform 0.3s ease;
}

.parfume-item:hover {
    transform: translateY(-5px);
}

.parfume-item.list-item {
    display: grid;
    grid-template-columns: 200px 1fr;
    gap: 20px;
}

.parfume-item-image {
    position: relative;
    height: 200px;
    overflow: hidden;
}

.parfume-item.list-item .parfume-item-image {
    height: 150px;
}

.parfume-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.parfume-placeholder {
    width: 100%;
    height: 100%;
    background: #f5f5f5;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #999;
    font-size: 48px;
}

.parfume-item-overlay {
    position: absolute;
    top: 10px;
    right: 10px;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.parfume-item:hover .parfume-item-overlay {
    opacity: 1;
}

.add-to-comparison {
    background: #28a745;
    color: white;
    border: none;
    border-radius: 50%;
    width: 40px;
    height: 40px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
}

.parfume-item-content {
    padding: 20px;
}

.parfume-item-title {
    margin: 0 0 10px 0;
    font-size: 18px;
    line-height: 1.3;
}

.parfume-item-title a {
    color: #333;
    text-decoration: none;
}

.parfume-item-title a:hover {
    color: #0073aa;
}

.parfume-item-brand a {
    color: #0073aa;
    text-decoration: none;
    font-weight: 500;
}

.parfume-item-type {
    color: #666;
    font-size: 14px;
    margin-bottom: 15px;
}

.parfume-item-price {
    font-size: 16px;
    color: #d63384;
    margin-bottom: 10px;
}

.parfume-item-notes {
    font-size: 14px;
    color: #666;
    margin-bottom: 15px;
}

.more-notes {
    color: #0073aa;
    font-weight: 500;
}

.parfume-item-suitable {
    margin-bottom: 15px;
}

.suitable-icons {
    display: flex;
    gap: 5px;
}

.suitable-icon {
    font-size: 16px;
}

.view-parfume-btn {
    background: #0073aa;
    color: white;
    padding: 10px 20px;
    border-radius: 4px;
    text-decoration: none;
    display: inline-block;
    font-size: 14px;
    font-weight: 500;
}

.view-parfume-btn:hover {
    background: #005a87;
}

.parfumes-pagination {
    text-align: center;
}

.parfumes-pagination .page-numbers {
    display: inline-block;
    padding: 8px 12px;
    margin: 0 4px;
    background: #f1f1f1;
    color: #333;
    text-decoration: none;
    border-radius: 4px;
}

.parfumes-pagination .page-numbers:hover,
.parfumes-pagination .page-numbers.current {
    background: #0073aa;
    color: white;
}

.no-results {
    text-align: center;
    padding: 60px 20px;
    color: #666;
}

.comparison-popup {
    position: fixed;
    bottom: 20px;
    right: 20px;
    width: 300px;
    background: white;
    border: 1px solid #ddd;
    border-radius: 8px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.15);
    z-index: 1000;
}

.comparison-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px;
    border-bottom: 1px solid #ddd;
    background: #f9f9f9;
    border-radius: 8px 8px 0 0;
}

.comparison-header h3 {
    margin: 0;
    font-size: 14px;
}

.comparison-close {
    background: none;
    border: none;
    font-size: 18px;
    cursor: pointer;
    color: #666;
}

.comparison-content {
    max-height: 300px;
    overflow-y: auto;
}

.comparison-items {
    padding: 15px;
}

.comparison-item {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 10px;
    position: relative;
}

.comparison-image {
    width: 40px;
    height: 40px;
    object-fit: cover;
    border-radius: 4px;
}

.comparison-title {
    flex: 1;
    margin: 0;
    font-size: 12px;
    line-height: 1.3;
}

.remove-from-comparison {
    background: #dc3232;
    color: white;
    border: none;
    border-radius: 50%;
    width: 20px;
    height: 20px;
    cursor: pointer;
    font-size: 12px;
}

.comparison-footer {
    padding: 15px;
    border-top: 1px solid #ddd;
    text-align: center;
}

@media (max-width: 768px) {
    .archive-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 15px;
    }
    
    .archive-controls {
        flex-direction: column;
        align-items: stretch;
    }
    
    .sorting-section {
        justify-content: center;
    }
    
    .filters-grid {
        grid-template-columns: 1fr;
    }
    
    .parfumes-grid.grid-view {
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    }
    
    .parfume-item.list-item {
        grid-template-columns: 120px 1fr;
        gap: 15px;
    }
    
    .parfume-item.list-item .parfume-item-image {
        height: 120px;
    }
    
    .active-filters {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .comparison-popup {
        bottom: 10px;
        right: 10px;
        left: 10px;
        width: auto;
    }
}
</style>

<?php get_footer(); ?>