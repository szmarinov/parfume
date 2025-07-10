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

// Get settings - Fixed class names
$comparison_settings = array('enabled' => false); // Default fallback
if (class_exists('Parfume_Admin_Comparison')) {
    $comparison_settings = Parfume_Admin_Comparison::get_comparison_settings();
}

$filter_settings = array(); // Default fallback
if (class_exists('Parfume_Catalog_Filters')) {
    $filter_settings = Parfume_Catalog_Filters::get_filter_settings();
}

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
        
        // Filter by type
        if (!empty($_GET['parfume_type'])) {
            $tax_query[] = array(
                'taxonomy' => 'parfume_type',
                'field'    => 'slug',
                'terms'    => sanitize_text_field($_GET['parfume_type'])
            );
        }
        
        // Filter by vid
        if (!empty($_GET['parfume_vid'])) {
            $tax_query[] = array(
                'taxonomy' => 'parfume_vid',
                'field'    => 'slug',
                'terms'    => sanitize_text_field($_GET['parfume_vid'])
            );
        }
        
        // Filter by marki
        if (!empty($_GET['parfume_marki'])) {
            $tax_query[] = array(
                'taxonomy' => 'parfume_marki',
                'field'    => 'slug',
                'terms'    => sanitize_text_field($_GET['parfume_marki'])
            );
        }
        
        // Filter by season
        if (!empty($_GET['parfume_season'])) {
            $tax_query[] = array(
                'taxonomy' => 'parfume_season',
                'field'    => 'slug',
                'terms'    => sanitize_text_field($_GET['parfume_season'])
            );
        }
        
        // Filter by intensity
        if (!empty($_GET['parfume_intensity'])) {
            $tax_query[] = array(
                'taxonomy' => 'parfume_intensity',
                'field'    => 'slug',
                'terms'    => sanitize_text_field($_GET['parfume_intensity'])
            );
        }
        
        // Filter by notes
        if (!empty($_GET['parfume_notes'])) {
            $tax_query[] = array(
                'taxonomy' => 'parfume_notes',
                'field'    => 'slug',
                'terms'    => sanitize_text_field($_GET['parfume_notes'])
            );
        }
        
        // Price range filter
        if (!empty($_GET['min_price']) || !empty($_GET['max_price'])) {
            $price_query = array('key' => '_parfume_min_price');
            
            if (!empty($_GET['min_price']) && !empty($_GET['max_price'])) {
                $price_query['value'] = array(floatval($_GET['min_price']), floatval($_GET['max_price']));
                $price_query['compare'] = 'BETWEEN';
                $price_query['type'] = 'NUMERIC';
            } elseif (!empty($_GET['min_price'])) {
                $price_query['value'] = floatval($_GET['min_price']);
                $price_query['compare'] = '>=';
                $price_query['type'] = 'NUMERIC';
            } elseif (!empty($_GET['max_price'])) {
                $price_query['value'] = floatval($_GET['max_price']);
                $price_query['compare'] = '<=';
                $price_query['type'] = 'NUMERIC';
            }
            
            $meta_query[] = $price_query;
        }
        
        // Apply queries
        if (count($tax_query) > 1) {
            $query->set('tax_query', $tax_query);
        }
        
        if (count($meta_query) > 1) {
            $query->set('meta_query', $meta_query);
        }
        
        // Posts per page
        if (!empty($_GET['posts_per_page'])) {
            $query->set('posts_per_page', absint($_GET['posts_per_page']));
        }
    }
}

// Get current filter parameters
$current_view = isset($_GET['view']) ? sanitize_text_field($_GET['view']) : 'grid';
$posts_per_page = isset($_GET['posts_per_page']) ? absint($_GET['posts_per_page']) : 12;
$orderby = isset($_GET['orderby']) ? sanitize_text_field($_GET['orderby']) : 'date';
$order = isset($_GET['order']) ? sanitize_text_field($_GET['order']) : 'DESC';

// Get available filter options
$available_types = get_terms(array('taxonomy' => 'parfume_type', 'hide_empty' => true));
$available_vids = get_terms(array('taxonomy' => 'parfume_vid', 'hide_empty' => true));
$available_marki = get_terms(array('taxonomy' => 'parfume_marki', 'hide_empty' => true));
$available_seasons = get_terms(array('taxonomy' => 'parfume_season', 'hide_empty' => true));
$available_intensities = get_terms(array('taxonomy' => 'parfume_intensity', 'hide_empty' => true));
$available_notes = get_terms(array('taxonomy' => 'parfume_notes', 'hide_empty' => true));

?>

<div class="parfumes-archive-container">
    <div class="archive-header">
        <div class="archive-title-section">
            <h1 class="archive-title">
                <?php 
                if ($is_taxonomy) {
                    echo esc_html($term_name);
                } else {
                    echo esc_html(__('Всички парфюми', 'parfume-catalog'));
                }
                ?>
            </h1>
            
            <?php if ($is_taxonomy && !empty($queried_object->description)): ?>
                <div class="archive-description">
                    <?php echo wp_kses_post($queried_object->description); ?>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="archive-controls">
            <div class="view-toggle">
                <button type="button" class="view-btn <?php echo $current_view === 'grid' ? 'active' : ''; ?>" data-view="grid">
                    <span class="dashicons dashicons-grid-view"></span>
                    <?php _e('Мрежа', 'parfume-catalog'); ?>
                </button>
                <button type="button" class="view-btn <?php echo $current_view === 'list' ? 'active' : ''; ?>" data-view="list">
                    <span class="dashicons dashicons-list-view"></span>
                    <?php _e('Списък', 'parfume-catalog'); ?>
                </button>
            </div>
            
            <div class="sorting-section">
                <select name="sort_by" id="sort-by" class="archive-select">
                    <option value="date" <?php selected($orderby, 'date'); ?>><?php _e('Най-нови', 'parfume-catalog'); ?></option>
                    <option value="title" <?php selected($orderby, 'title'); ?>><?php _e('По име', 'parfume-catalog'); ?></option>
                    <option value="price" <?php selected($orderby, 'price'); ?>><?php _e('По цена', 'parfume-catalog'); ?></option>
                    <option value="rating" <?php selected($orderby, 'rating'); ?>><?php _e('По рейтинг', 'parfume-catalog'); ?></option>
                </select>
                
                <select name="posts_per_page" id="posts-per-page" class="archive-select">
                    <option value="12" <?php selected($posts_per_page, 12); ?>>12</option>
                    <option value="24" <?php selected($posts_per_page, 24); ?>>24</option>
                    <option value="48" <?php selected($posts_per_page, 48); ?>>48</option>
                    <option value="96" <?php selected($posts_per_page, 96); ?>>96</option>
                </select>
            </div>
        </div>
    </div>
    
    <!-- Filters Section -->
    <div class="filters-section">
        <div class="filters-toggle">
            <button type="button" id="filters-toggle" class="filters-toggle-btn">
                <span class="dashicons dashicons-filter"></span>
                <?php _e('Филтри', 'parfume-catalog'); ?>
            </button>
        </div>
        
        <div class="filters-content" id="filters-content">
            <form method="get" id="parfume-filters-form">
                <input type="hidden" name="parfume_filter" value="1">
                <input type="hidden" name="view" value="<?php echo esc_attr($current_view); ?>">
                
                <div class="filters-grid">
                    <!-- Type Filter -->
                    <?php if (!empty($available_types)): ?>
                        <div class="filter-group">
                            <label for="parfume_type"><?php _e('Тип', 'parfume-catalog'); ?></label>
                            <select name="parfume_type" id="parfume_type">
                                <option value=""><?php _e('Всички типове', 'parfume-catalog'); ?></option>
                                <?php foreach ($available_types as $type): ?>
                                    <option value="<?php echo esc_attr($type->slug); ?>" <?php selected($_GET['parfume_type'] ?? '', $type->slug); ?>>
                                        <?php echo esc_html($type->name); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Vid Filter -->
                    <?php if (!empty($available_vids)): ?>
                        <div class="filter-group">
                            <label for="parfume_vid"><?php _e('Вид аромат', 'parfume-catalog'); ?></label>
                            <select name="parfume_vid" id="parfume_vid">
                                <option value=""><?php _e('Всички видове', 'parfume-catalog'); ?></option>
                                <?php foreach ($available_vids as $vid): ?>
                                    <option value="<?php echo esc_attr($vid->slug); ?>" <?php selected($_GET['parfume_vid'] ?? '', $vid->slug); ?>>
                                        <?php echo esc_html($vid->name); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Marki Filter -->
                    <?php if (!empty($available_marki)): ?>
                        <div class="filter-group">
                            <label for="parfume_marki"><?php _e('Марка', 'parfume-catalog'); ?></label>
                            <select name="parfume_marki" id="parfume_marki">
                                <option value=""><?php _e('Всички марки', 'parfume-catalog'); ?></option>
                                <?php foreach ($available_marki as $marka): ?>
                                    <option value="<?php echo esc_attr($marka->slug); ?>" <?php selected($_GET['parfume_marki'] ?? '', $marka->slug); ?>>
                                        <?php echo esc_html($marka->name); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Season Filter -->
                    <?php if (!empty($available_seasons)): ?>
                        <div class="filter-group">
                            <label for="parfume_season"><?php _e('Сезон', 'parfume-catalog'); ?></label>
                            <select name="parfume_season" id="parfume_season">
                                <option value=""><?php _e('Всички сезони', 'parfume-catalog'); ?></option>
                                <?php foreach ($available_seasons as $season): ?>
                                    <option value="<?php echo esc_attr($season->slug); ?>" <?php selected($_GET['parfume_season'] ?? '', $season->slug); ?>>
                                        <?php echo esc_html($season->name); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Intensity Filter -->
                    <?php if (!empty($available_intensities)): ?>
                        <div class="filter-group">
                            <label for="parfume_intensity"><?php _e('Интензивност', 'parfume-catalog'); ?></label>
                            <select name="parfume_intensity" id="parfume_intensity">
                                <option value=""><?php _e('Всички интензивности', 'parfume-catalog'); ?></option>
                                <?php foreach ($available_intensities as $intensity): ?>
                                    <option value="<?php echo esc_attr($intensity->slug); ?>" <?php selected($_GET['parfume_intensity'] ?? '', $intensity->slug); ?>>
                                        <?php echo esc_html($intensity->name); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Price Range Filter -->
                    <div class="filter-group">
                        <label><?php _e('Ценови диапазон', 'parfume-catalog'); ?></label>
                        <div class="price-range-inputs">
                            <input type="number" name="min_price" id="min_price" 
                                   placeholder="<?php _e('Мин. цена', 'parfume-catalog'); ?>" 
                                   value="<?php echo esc_attr($_GET['min_price'] ?? ''); ?>"
                                   min="0" step="0.01">
                            <span class="price-separator">-</span>
                            <input type="number" name="max_price" id="max_price" 
                                   placeholder="<?php _e('Макс. цена', 'parfume-catalog'); ?>" 
                                   value="<?php echo esc_attr($_GET['max_price'] ?? ''); ?>"
                                   min="0" step="0.01">
                        </div>
                    </div>
                </div>
                
                <div class="filters-actions">
                    <button type="submit" class="button button-primary">
                        <?php _e('Приложи филтри', 'parfume-catalog'); ?>
                    </button>
                    <button type="button" id="reset-filters" class="button button-secondary">
                        <?php _e('Изчисти филтрите', 'parfume-catalog'); ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Active Filters -->
    <?php if (!empty($_GET['parfume_filter'])): ?>
        <div class="active-filters">
            <span class="active-filters-label"><?php _e('Активни филтри:', 'parfume-catalog'); ?></span>
            <div class="active-filters-list">
                <?php
                $active_filters = array();
                
                if (!empty($_GET['parfume_type'])) {
                    $term = get_term_by('slug', $_GET['parfume_type'], 'parfume_type');
                    if ($term) $active_filters[] = $term->name;
                }
                
                if (!empty($_GET['parfume_vid'])) {
                    $term = get_term_by('slug', $_GET['parfume_vid'], 'parfume_vid');
                    if ($term) $active_filters[] = $term->name;
                }
                
                if (!empty($_GET['parfume_marki'])) {
                    $term = get_term_by('slug', $_GET['parfume_marki'], 'parfume_marki');
                    if ($term) $active_filters[] = $term->name;
                }
                
                if (!empty($_GET['parfume_season'])) {
                    $term = get_term_by('slug', $_GET['parfume_season'], 'parfume_season');
                    if ($term) $active_filters[] = $term->name;
                }
                
                if (!empty($_GET['parfume_intensity'])) {
                    $term = get_term_by('slug', $_GET['parfume_intensity'], 'parfume_intensity');
                    if ($term) $active_filters[] = $term->name;
                }
                
                if (!empty($_GET['min_price']) || !empty($_GET['max_price'])) {
                    $price_text = '';
                    if (!empty($_GET['min_price']) && !empty($_GET['max_price'])) {
                        $price_text = sprintf(__('Цена: %s - %s лв.', 'parfume-catalog'), $_GET['min_price'], $_GET['max_price']);
                    } elseif (!empty($_GET['min_price'])) {
                        $price_text = sprintf(__('Цена: над %s лв.', 'parfume-catalog'), $_GET['min_price']);
                    } elseif (!empty($_GET['max_price'])) {
                        $price_text = sprintf(__('Цена: под %s лв.', 'parfume-catalog'), $_GET['max_price']);
                    }
                    $active_filters[] = $price_text;
                }
                
                foreach ($active_filters as $filter) {
                    echo '<span class="active-filter">' . esc_html($filter) . '</span>';
                }
                ?>
            </div>
        </div>
    <?php endif; ?>
    
    <!-- Results -->
    <div class="parfumes-results">
        <?php if (have_posts()): ?>
            <div class="results-header">
                <div class="results-count">
                    <?php
                    global $wp_query;
                    $total = $wp_query->found_posts;
                    $current_page = max(1, get_query_var('paged'));
                    $per_page = $wp_query->query_vars['posts_per_page'];
                    $start = ($current_page - 1) * $per_page + 1;
                    $end = min($current_page * $per_page, $total);
                    
                    printf(
                        __('Показани %d - %d от %d резултата', 'parfume-catalog'),
                        $start,
                        $end,
                        $total
                    );
                    ?>
                </div>
            </div>
            
            <div class="parfumes-grid <?php echo esc_attr($current_view); ?>-view">
                <?php while (have_posts()): the_post(); ?>
                    <?php parfume_render_item(get_the_ID()); ?>
                <?php endwhile; ?>
            </div>
            
            <?php
            // Pagination
            $pagination_args = array(
                'total' => $wp_query->max_num_pages,
                'current' => $current_page,
                'format' => '?paged=%#%',
                'show_all' => false,
                'end_size' => 1,
                'mid_size' => 2,
                'prev_next' => true,
                'prev_text' => __('« Предишна', 'parfume-catalog'),
                'next_text' => __('Следваща »', 'parfume-catalog'),
                'type' => 'array'
            );
            
            $pagination_links = paginate_links($pagination_args);
            
            if ($pagination_links):
            ?>
                <div class="parfumes-pagination">
                    <nav class="pagination-nav">
                        <?php foreach ($pagination_links as $link): ?>
                            <?php echo $link; ?>
                        <?php endforeach; ?>
                    </nav>
                </div>
            <?php endif; ?>
            
        <?php else: ?>
            <div class="no-results">
                <h3><?php _e('Няма намерени парфюми', 'parfume-catalog'); ?></h3>
                <p><?php _e('Моля, опитайте с други филтри или започнете ново търсене.', 'parfume-catalog'); ?></p>
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
    
    // Safe method calls with fallbacks
    $parfume_basic = array();
    if (class_exists('Parfume_Catalog_Meta_Basic')) {
        $parfume_basic = Parfume_Catalog_Meta_Basic::get_parfume_info($post_id);
    }
    
    $parfume_stores = array();
    if (class_exists('Parfume_Catalog_Meta_Stores')) {
        $parfume_stores = Parfume_Catalog_Meta_Stores::get_formatted_stores($post_id);
    }
    
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
    
    // Get rating
    $rating = get_post_meta($post_id, '_parfume_rating', true);
    
    // Get comparison settings
    $comparison_settings = array('enabled' => false);
    if (class_exists('Parfume_Admin_Comparison')) {
        $comparison_settings = Parfume_Admin_Comparison::get_comparison_settings();
    }
    
    global $current_view;
    ?>
    <div class="parfume-item <?php echo esc_attr($current_view); ?>-item">
        <div class="parfume-image-container">
            <a href="<?php the_permalink(); ?>">
                <?php if (has_post_thumbnail()): ?>
                    <?php the_post_thumbnail('medium', array('class' => 'parfume-item-image')); ?>
                <?php else: ?>
                    <div class="parfume-placeholder">
                        <span class="dashicons dashicons-format-image"></span>
                    </div>
                <?php endif; ?>
            </a>
            
            <?php if ($comparison_settings['enabled']): ?>
                <button type="button" 
                        class="comparison-btn" 
                        data-parfume-id="<?php echo $post_id; ?>"
                        data-parfume-title="<?php echo esc_attr(get_the_title()); ?>"
                        data-parfume-image="<?php echo esc_url(get_the_post_thumbnail_url($post_id, 'thumbnail')); ?>"
                        title="<?php echo esc_attr($comparison_settings['texts']['add']); ?>">
                    <span class="comparison-icon">⚖️</span>
                    <span class="comparison-text"><?php echo esc_html($comparison_settings['texts']['add']); ?></span>
                </button>
            <?php endif; ?>
        </div>
        
        <div class="parfume-content">
            <header class="parfume-header">
                <h3 class="parfume-title">
                    <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                </h3>
                
                <?php if ($brands && !is_wp_error($brands)): ?>
                    <div class="parfume-brand">
                        <a href="<?php echo esc_url(get_term_link($brands[0])); ?>">
                            <?php echo esc_html($brands[0]->name); ?>
                        </a>
                    </div>
                <?php endif; ?>
                
                <?php if ($types && !is_wp_error($types)): ?>
                    <div class="parfume-type">
                        <?php echo esc_html($types[0]->name); ?>
                    </div>
                <?php endif; ?>
            </header>
            
            <?php if (!empty($suitable_conditions)): ?>
                <div class="parfume-suitable">
                    <?php
                    $suitable_icons = array(
                        'prolet' => '🌸',
                        'liato' => '☀️',
                        'esen' => '🍂',
                        'zima' => '❄️',
                        'den' => '🌞',
                        'nosht' => '🌙'
                    );
                    
                    foreach ($suitable_conditions as $condition) {
                        if (isset($suitable_icons[$condition])) {
                            echo '<span class="suitable-icon" title="' . esc_attr($condition) . '">' . $suitable_icons[$condition] . '</span>';
                        }
                    }
                    ?>
                </div>
            <?php endif; ?>
            
            <?php if ($notes && !is_wp_error($notes)): ?>
                <div class="parfume-notes">
                    <span class="notes-label"><?php _e('Основни нотки:', 'parfume-catalog'); ?></span>
                    <span class="notes-list">
                        <?php 
                        $note_names = array();
                        foreach (array_slice($notes, 0, 3) as $note) {
                            $note_names[] = $note->name;
                        }
                        echo esc_html(implode(', ', $note_names));
                        if (count($notes) > 3) {
                            echo '...';
                        }
                        ?>
                    </span>
                </div>
            <?php endif; ?>
            
            <div class="parfume-meta">
                <?php if ($rating): ?>
                    <div class="parfume-rating">
                        <?php
                        $rating_stars = '';
                        for ($i = 1; $i <= 5; $i++) {
                            if ($i <= $rating) {
                                $rating_stars .= '<span class="star filled">★</span>';
                            } else {
                                $rating_stars .= '<span class="star empty">☆</span>';
                            }
                        }
                        echo $rating_stars;
                        ?>
                        <span class="rating-value"><?php echo esc_html($rating); ?></span>
                    </div>
                <?php endif; ?>
                
                <?php if ($min_price): ?>
                    <div class="parfume-price">
                        <span class="price-label"><?php _e('от', 'parfume-catalog'); ?></span>
                        <span class="price-value"><?php echo esc_html($min_price . ' ' . $price_currency); ?></span>
                    </div>
                <?php endif; ?>
            </div>
            
            <?php if ($current_view === 'list'): ?>
                <div class="parfume-excerpt">
                    <?php the_excerpt(); ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php
}

// Make current view accessible in function
global $current_view;
?>

<script>
jQuery(document).ready(function($) {
    // View toggle
    $('.view-btn').on('click', function() {
        var view = $(this).data('view');
        var url = new URL(window.location.href);
        url.searchParams.set('view', view);
        window.location.href = url.toString();
    });
    
    // Sort and pagination
    $('#sort-by, #posts-per-page').on('change', function() {
        var url = new URL(window.location.href);
        url.searchParams.set($(this).attr('name'), $(this).val());
        url.searchParams.set('paged', 1); // Reset to first page
        window.location.href = url.toString();
    });
    
    // Filters toggle
    $('#filters-toggle').on('click', function() {
        $('#filters-content').slideToggle();
        $(this).toggleClass('active');
    });
    
    // Reset filters
    $('#reset-filters, #reset-search').on('click', function() {
        window.location.href = window.location.pathname;
    });
    
    <?php if ($comparison_settings['enabled']): ?>
    // Comparison functionality
    var maxItems = <?php echo intval($comparison_settings['max_items']); ?>;
    var comparisonItems = JSON.parse(localStorage.getItem('parfumeComparison')) || [];
    
    function updateComparisonButtons() {
        $('.comparison-btn').each(function() {
            var parfumeId = $(this).data('parfume-id');
            var isInComparison = comparisonItems.some(function(item) {
                return item.id === parfumeId;
            });
            
            if (isInComparison) {
                $(this).addClass('active');
                $(this).find('.comparison-text').text('<?php echo esc_js($comparison_settings['texts']['remove']); ?>');
            } else {
                $(this).removeClass('active');
                $(this).find('.comparison-text').text('<?php echo esc_js($comparison_settings['texts']['add']); ?>');
            }
        });
        
        if (comparisonItems.length > 0) {
            updateComparisonPopup();
        }
    }
    
    function updateComparisonPopup() {
        var popup = $('#comparison-popup');
        var itemsContainer = $('#comparison-items');
        
        if (comparisonItems.length === 0) {
            popup.hide();
            return;
        }
        
        var html = '';
        comparisonItems.forEach(function(item) {
            html += '<div class="comparison-item">';
            if (item.image) {
                html += '<img src="' + item.image + '" alt="' + item.title + '" class="comparison-image">';
            }
            html += '<h4 class="comparison-title">' + item.title + '</h4>';
            html += '<button type="button" class="remove-from-comparison" data-parfume-id="' + item.id + '">×</button>';
            html += '</div>';
        });
        
        itemsContainer.html(html);
        popup.show();
    }
    
    // Toggle comparison
    $(document).on('click', '.comparison-btn', function() {
        var parfumeId = $(this).data('parfume-id');
        var parfumeTitle = $(this).data('parfume-title');
        var parfumeImage = $(this).data('parfume-image');
        
        var existingIndex = comparisonItems.findIndex(function(item) {
            return item.id === parfumeId;
        });
        
        if (existingIndex !== -1) {
            // Remove from comparison
            comparisonItems.splice(existingIndex, 1);
        } else {
            // Add to comparison
            if (comparisonItems.length >= maxItems) {
                alert('<?php echo esc_js($comparison_settings['texts']['max_reached']); ?>');
                return;
            }
            
            comparisonItems.push({
                id: parfumeId,
                title: parfumeTitle,
                image: parfumeImage
            });
        }
        
        localStorage.setItem('parfumeComparison', JSON.stringify(comparisonItems));
        updateComparisonButtons();
    });
    
    // Remove from comparison
    $(document).on('click', '.remove-from-comparison', function() {
        var parfumeId = $(this).data('parfume-id');
        comparisonItems = comparisonItems.filter(function(item) {
            return item.id !== parfumeId;
        });
        
        localStorage.setItem('parfumeComparison', JSON.stringify(comparisonItems));
        updateComparisonButtons();
    });
    
    // Clear all comparison
    $('#clear-comparison').on('click', function() {
        comparisonItems = [];
        localStorage.removeItem('parfumeComparison');
        updateComparisonButtons();
    });
    
    // Close comparison popup
    $('.comparison-close').on('click', function() {
        $('#comparison-popup').hide();
    });
    
    // Initialize comparison
    updateComparisonButtons();
    <?php endif; ?>
});
</script>

<style>
/* Archive styles */
.parfumes-archive-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.archive-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 2px solid #e0e0e0;
}

.archive-title {
    font-size: 2.5rem;
    margin: 0;
    color: #333;
}

.archive-description {
    margin-top: 10px;
    color: #666;
    line-height: 1.6;
}

.archive-controls {
    display: flex;
    gap: 20px;
    align-items: center;
}

.view-toggle {
    display: flex;
    gap: 5px;
    border: 1px solid #ddd;
    border-radius: 4px;
    overflow: hidden;
}

.view-btn {
    background: #f9f9f9;
    border: none;
    padding: 8px 12px;
    cursor: pointer;
    font-size: 12px;
    display: flex;
    align-items: center;
    gap: 5px;
}

.view-btn.active {
    background: #0073aa;
    color: white;
}

.sorting-section {
    display: flex;
    gap: 10px;
    align-items: center;
}

.archive-select {
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
}

.filters-section {
    margin-bottom: 20px;
}

.filters-toggle-btn {
    background: #f0f0f0;
    border: 1px solid #ddd;
    padding: 10px 15px;
    cursor: pointer;
    border-radius: 4px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.filters-content {
    display: none;
    background: #f9f9f9;
    border: 1px solid #ddd;
    border-top: none;
    padding: 20px;
    border-radius: 0 0 4px 4px;
}

.filters-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin-bottom: 20px;
}

.filter-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: bold;
    color: #333;
}

.filter-group select,
.filter-group input {
    width: 100%;
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
}

.price-range-inputs {
    display: flex;
    align-items: center;
    gap: 10px;
}

.price-range-inputs input {
    flex: 1;
}

.price-separator {
    color: #666;
    font-weight: bold;
}

.filters-actions {
    display: flex;
    gap: 10px;
}

.active-filters {
    margin-bottom: 20px;
    padding: 15px;
    background: #fff3cd;
    border: 1px solid #ffeaa7;
    border-radius: 4px;
    display: flex;
    align-items: center;
    gap: 15px;
}

.active-filters-label {
    font-weight: bold;
    color: #856404;
}

.active-filters-list {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.active-filter {
    background: #fff;
    padding: 4px 8px;
    border-radius: 4px;
    border: 1px solid #ddd;
    font-size: 12px;
}

.results-header {
    margin-bottom: 20px;
    padding-bottom: 10px;
    border-bottom: 1px solid #eee;
}

.results-count {
    color: #666;
    font-size: 14px;
}

.parfumes-grid {
    display: grid;
    gap: 20px;
    margin-bottom: 30px;
}

.parfumes-grid.grid-view {
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
}

.parfumes-grid.list-view {
    grid-template-columns: 1fr;
}

.parfume-item {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 8px;
    overflow: hidden;
    transition: box-shadow 0.3s ease;
}

.parfume-item:hover {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.parfume-item.list-item {
    display: grid;
    grid-template-columns: 200px 1fr;
    gap: 20px;
    align-items: start;
}

.parfume-image-container {
    position: relative;
    overflow: hidden;
}

.parfume-item-image {
    width: 100%;
    height: 250px;
    object-fit: cover;
    display: block;
}

.parfume-item.list-item .parfume-item-image {
    height: 200px;
}

.parfume-placeholder {
    width: 100%;
    height: 250px;
    background: #f0f0f0;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #999;
    font-size: 48px;
}

.comparison-btn {
    position: absolute;
    top: 10px;
    right: 10px;
    background: rgba(0, 0, 0, 0.7);
    color: white;
    border: none;
    padding: 8px;
    border-radius: 4px;
    cursor: pointer;
    font-size: 11px;
    display: flex;
    align-items: center;
    gap: 4px;
    transition: background 0.3s ease;
}

.comparison-btn:hover {
    background: rgba(0, 0, 0, 0.9);
}

.comparison-btn.active {
    background: #0073aa;
}

.parfume-content {
    padding: 15px;
}

.parfume-title {
    margin: 0 0 8px 0;
    font-size: 18px;
}

.parfume-title a {
    color: #333;
    text-decoration: none;
}

.parfume-title a:hover {
    color: #0073aa;
}

.parfume-brand {
    margin-bottom: 5px;
}

.parfume-brand a {
    color: #666;
    text-decoration: none;
    font-size: 14px;
}

.parfume-type {
    color: #999;
    font-size: 13px;
    margin-bottom: 10px;
}

.parfume-suitable {
    margin-bottom: 10px;
}

.suitable-icon {
    font-size: 18px;
    margin-right: 5px;
}

.parfume-notes {
    margin-bottom: 10px;
    font-size: 14px;
}

.notes-label {
    font-weight: bold;
    color: #333;
}

.notes-list {
    color: #666;
}

.parfume-meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 15px;
    padding-top: 15px;
    border-top: 1px solid #eee;
}

.parfume-rating {
    display: flex;
    align-items: center;
    gap: 5px;
}

.star {
    color: #ffd700;
    font-size: 14px;
}

.star.empty {
    color: #ddd;
}

.rating-value {
    font-size: 12px;
    color: #666;
    margin-left: 5px;
}

.parfume-price {
    font-weight: bold;
    color: #0073aa;
}

.price-label {
    font-weight: normal;
    color: #666;
    font-size: 12px;
}

.parfume-excerpt {
    margin-top: 10px;
    color: #666;
    line-height: 1.5;
}

.parfumes-pagination {
    display: flex;
    justify-content: center;
    margin-top: 40px;
}

.pagination-nav {
    display: flex;
    gap: 5px;
}

.pagination-nav a,
.pagination-nav span {
    padding: 8px 12px;
    border: 1px solid #ddd;
    text-decoration: none;
    color: #333;
    background: #f9f9f9;
}

.pagination-nav a:hover {
    background: #0073aa;
    color: white;
}

.pagination-nav .current {
    background: #0073aa;
    color: white;
}

.no-results {
    text-align: center;
    padding: 40px;
    background: #f9f9f9;
    border-radius: 8px;
}

.comparison-popup {
    position: fixed;
    bottom: 20px;
    right: 20px;
    width: 300px;
    background: white;
    border: 1px solid #ddd;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
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