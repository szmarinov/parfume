<?php
/**
 * Archive Parfumes Template
 * 
 * Template for displaying parfumes archive and category pages
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();

// Get current page info
$queried_object = get_queried_object();
$is_taxonomy = is_tax();
$taxonomy_name = '';
$term_name = '';

if ($is_taxonomy) {
    $taxonomy_name = $queried_object->taxonomy;
    $term_name = $queried_object->name;
}

// Get filters and settings
$comparison_settings = Parfume_Admin_Comparison::get_comparison_settings();
$current_view = isset($_GET['view']) ? sanitize_text_field($_GET['view']) : 'grid';
$posts_per_page = isset($_GET['per_page']) ? absint($_GET['per_page']) : 12;
$orderby = isset($_GET['orderby']) ? sanitize_text_field($_GET['orderby']) : 'date';
$order = isset($_GET['order']) ? sanitize_text_field($_GET['order']) : 'DESC';

// Get filter values
$selected_type = isset($_GET['parfume_type']) ? sanitize_text_field($_GET['parfume_type']) : '';
$selected_brand = isset($_GET['parfume_brand']) ? sanitize_text_field($_GET['parfume_brand']) : '';
$selected_season = isset($_GET['parfume_season']) ? sanitize_text_field($_GET['parfume_season']) : '';
$selected_intensity = isset($_GET['parfume_intensity']) ? sanitize_text_field($_GET['parfume_intensity']) : '';
$selected_price_range = isset($_GET['price_range']) ? sanitize_text_field($_GET['price_range']) : '';
$search_term = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';

// Modify main query with filters
add_action('pre_get_posts', 'parfume_archive_filter_query');
function parfume_archive_filter_query($query) {
    if (!is_admin() && $query->is_main_query()) {
        if (is_post_type_archive('parfumes') || is_tax(array('parfume_type', 'parfume_vid', 'parfume_marki', 'parfume_season', 'parfume_intensity', 'parfume_notes'))) {
            
            // Set posts per page
            $posts_per_page = isset($_GET['per_page']) ? absint($_GET['per_page']) : 12;
            $query->set('posts_per_page', $posts_per_page);
            
            // Set ordering
            $orderby = isset($_GET['orderby']) ? sanitize_text_field($_GET['orderby']) : 'date';
            $order = isset($_GET['order']) ? sanitize_text_field($_GET['order']) : 'DESC';
            
            switch ($orderby) {
                case 'title':
                    $query->set('orderby', 'title');
                    break;
                case 'rating':
                    $query->set('meta_key', '_parfume_manual_overall_rating');
                    $query->set('orderby', 'meta_value_num');
                    break;
                case 'popularity':
                    $query->set('meta_key', '_parfume_manual_popularity_score');
                    $query->set('orderby', 'meta_value_num');
                    break;
                case 'price':
                    $query->set('meta_key', '_parfume_price_range');
                    $query->set('orderby', 'meta_value_num');
                    break;
                default:
                    $query->set('orderby', 'date');
                    break;
            }
            
            $query->set('order', $order);
            
            // Apply filters
            $tax_query = array('relation' => 'AND');
            $meta_query = array('relation' => 'AND');
            
            // Type filter
            if (!empty($_GET['parfume_type'])) {
                $tax_query[] = array(
                    'taxonomy' => 'parfume_type',
                    'field' => 'slug',
                    'terms' => sanitize_text_field($_GET['parfume_type'])
                );
            }
            
            // Brand filter
            if (!empty($_GET['parfume_brand'])) {
                $tax_query[] = array(
                    'taxonomy' => 'parfume_marki',
                    'field' => 'slug',
                    'terms' => sanitize_text_field($_GET['parfume_brand'])
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
            $current_page = max(1, get_query_var('paged'));
            $posts_per_page = $wp_query->query_vars['posts_per_page'];
            $start = ($current_page - 1) * $posts_per_page + 1;
            $end = min($current_page * $posts_per_page, $total_posts);
            ?>
            <span class="results-count">
                <?php printf(__('Показани %d-%d от общо %d парфюма', 'parfume-catalog'), $start, $end, $total_posts); ?>
            </span>
        </div>
    </div>
    
    <div class="archive-controls">
        <div class="filters-section">
            <button type="button" id="toggle-filters" class="filters-toggle">
                <span class="filter-icon">🔍</span>
                <?php _e('Филтри', 'parfume-catalog'); ?>
                <span class="toggle-arrow">▼</span>
            </button>
            
            <div class="filters-container" id="filters-container">
                <form method="get" class="filters-form" id="filters-form">
                    <div class="filters-grid">
                        <!-- Search -->
                        <div class="filter-group">
                            <label for="search-input"><?php _e('Търсене', 'parfume-catalog'); ?></label>
                            <input type="text" 
                                   id="search-input" 
                                   name="s" 
                                   value="<?php echo esc_attr($search_term); ?>" 
                                   placeholder="<?php _e('Търсете парфюм...', 'parfume-catalog'); ?>" 
                                   class="filter-input" />
                        </div>
                        
                        <!-- Type Filter -->
                        <div class="filter-group">
                            <label for="type-filter"><?php _e('Тип', 'parfume-catalog'); ?></label>
                            <select id="type-filter" name="parfume_type" class="filter-select">
                                <option value=""><?php _e('Всички типове', 'parfume-catalog'); ?></option>
                                <?php
                                $types = get_terms(array(
                                    'taxonomy' => 'parfume_type',
                                    'hide_empty' => true
                                ));
                                foreach ($types as $type):
                                ?>
                                    <option value="<?php echo esc_attr($type->slug); ?>" <?php selected($selected_type, $type->slug); ?>>
                                        <?php echo esc_html($type->name); ?> (<?php echo $type->count; ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <!-- Brand Filter -->
                        <div class="filter-group">
                            <label for="brand-filter"><?php _e('Марка', 'parfume-catalog'); ?></label>
                            <select id="brand-filter" name="parfume_brand" class="filter-select">
                                <option value=""><?php _e('Всички марки', 'parfume-catalog'); ?></option>
                                <?php
                                $brands = get_terms(array(
                                    'taxonomy' => 'parfume_marki',
                                    'hide_empty' => true,
                                    'orderby' => 'name'
                                ));
                                foreach ($brands as $brand):
                                ?>
                                    <option value="<?php echo esc_attr($brand->slug); ?>" <?php selected($selected_brand, $brand->slug); ?>>
                                        <?php echo esc_html($brand->name); ?> (<?php echo $brand->count; ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <!-- Season Filter -->
                        <div class="filter-group">
                            <label for="season-filter"><?php _e('Сезон', 'parfume-catalog'); ?></label>
                            <select id="season-filter" name="parfume_season" class="filter-select">
                                <option value=""><?php _e('Всички сезони', 'parfume-catalog'); ?></option>
                                <?php
                                $seasons = get_terms(array(
                                    'taxonomy' => 'parfume_season',
                                    'hide_empty' => true
                                ));
                                foreach ($seasons as $season):
                                ?>
                                    <option value="<?php echo esc_attr($season->slug); ?>" <?php selected($selected_season, $season->slug); ?>>
                                        <?php echo esc_html($season->name); ?> (<?php echo $season->count; ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <!-- Intensity Filter -->
                        <div class="filter-group">
                            <label for="intensity-filter"><?php _e('Интензивност', 'parfume-catalog'); ?></label>
                            <select id="intensity-filter" name="parfume_intensity" class="filter-select">
                                <option value=""><?php _e('Всички интензивности', 'parfume-catalog'); ?></option>
                                <?php
                                $intensities = get_terms(array(
                                    'taxonomy' => 'parfume_intensity',
                                    'hide_empty' => true
                                ));
                                foreach ($intensities as $intensity):
                                ?>
                                    <option value="<?php echo esc_attr($intensity->slug); ?>" <?php selected($selected_intensity, $intensity->slug); ?>>
                                        <?php echo esc_html($intensity->name); ?> (<?php echo $intensity->count; ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <!-- Price Range Filter -->
                        <div class="filter-group">
                            <label for="price-filter"><?php _e('Ценова категория', 'parfume-catalog'); ?></label>
                            <select id="price-filter" name="price_range" class="filter-select">
                                <option value=""><?php _e('Всички цени', 'parfume-catalog'); ?></option>
                                <option value="1-2" <?php selected($selected_price_range, '1-2'); ?>><?php _e('Евтини (1-2)', 'parfume-catalog'); ?></option>
                                <option value="2-3" <?php selected($selected_price_range, '2-3'); ?>><?php _e('Добра цена (2-3)', 'parfume-catalog'); ?></option>
                                <option value="3-4" <?php selected($selected_price_range, '3-4'); ?>><?php _e('Средни (3-4)', 'parfume-catalog'); ?></option>
                                <option value="4-5" <?php selected($selected_price_range, '4-5'); ?>><?php _e('Скъпи (4-5)', 'parfume-catalog'); ?></option>
                            </select>
                        </div>
                        
                        <!-- Notes Search -->
                        <div class="filter-group">
                            <label for="notes-search"><?php _e('Търсене в нотки', 'parfume-catalog'); ?></label>
                            <input type="text" 
                                   id="notes-search" 
                                   name="notes_search" 
                                   value="<?php echo esc_attr(isset($_GET['notes_search']) ? $_GET['notes_search'] : ''); ?>" 
                                   placeholder="<?php _e('Роза, ванилия...', 'parfume-catalog'); ?>" 
                                   class="filter-input" />
                        </div>
                    </div>
                    
                    <div class="filters-actions">
                        <button type="submit" class="apply-filters-btn">
                            <?php _e('Приложи филтри', 'parfume-catalog'); ?>
                        </button>
                        <button type="button" id="clear-filters" class="clear-filters-btn">
                            <?php _e('Изчисти', 'parfume-catalog'); ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="sorting-section">
            <div class="view-controls">
                <button type="button" 
                        class="view-btn <?php echo $current_view === 'grid' ? 'active' : ''; ?>" 
                        data-view="grid" 
                        title="<?php _e('Мрежов изглед', 'parfume-catalog'); ?>">
                    <span class="dashicons dashicons-grid-view"></span>
                </button>
                <button type="button" 
                        class="view-btn <?php echo $current_view === 'list' ? 'active' : ''; ?>" 
                        data-view="list"
                        title="<?php _e('Списъчен изглед', 'parfume-catalog'); ?>">
                    <span class="dashicons dashicons-list-view"></span>
                </button>
            </div>
            
            <div class="per-page-control">
                <label for="per-page-select"><?php _e('Показвай:', 'parfume-catalog'); ?></label>
                <select id="per-page-select" name="per_page">
                    <option value="12" <?php selected($posts_per_page, 12); ?>>12</option>
                    <option value="24" <?php selected($posts_per_page, 24); ?>>24</option>
                    <option value="36" <?php selected($posts_per_page, 36); ?>>36</option>
                    <option value="48" <?php selected($posts_per_page, 48); ?>>48</option>
                </select>
            </div>
            
            <div class="sort-controls">
                <label for="orderby-select"><?php _e('Подреди по:', 'parfume-catalog'); ?></label>
                <select id="orderby-select" name="orderby">
                    <option value="date" <?php selected($orderby, 'date'); ?>><?php _e('Дата', 'parfume-catalog'); ?></option>
                    <option value="title" <?php selected($orderby, 'title'); ?>><?php _e('Име', 'parfume-catalog'); ?></option>
                    <option value="rating" <?php selected($orderby, 'rating'); ?>><?php _e('Рейтинг', 'parfume-catalog'); ?></option>
                    <option value="popularity" <?php selected($orderby, 'popularity'); ?>><?php _e('Популярност', 'parfume-catalog'); ?></option>
                </select>
                
                <select id="order-select" name="order">
                    <option value="DESC" <?php selected($order, 'DESC'); ?>><?php _e('Низходящо', 'parfume-catalog'); ?></option>
                    <option value="ASC" <?php selected($order, 'ASC'); ?>><?php _e('Възходящо', 'parfume-catalog'); ?></option>
                </select>
            </div>
        </div>
    </div>
    
    <!-- Active Filters Display -->
    <?php if ($this->has_active_filters()): ?>
        <div class="active-filters">
            <span class="active-filters-label"><?php _e('Активни филтри:', 'parfume-catalog'); ?></span>
            <div class="active-filters-list">
                <?php $this->render_active_filters(); ?>
            </div>
            <button type="button" id="clear-all-filters" class="clear-all-btn">
                <?php _e('Изчисти всички', 'parfume-catalog'); ?>
            </button>
        </div>
    <?php endif; ?>
    
    <div class="parfumes-grid-container">
        <?php if (have_posts()): ?>
            <div class="parfumes-grid <?php echo esc_attr($current_view); ?>-view" id="parfumes-grid">
                <?php while (have_posts()): the_post(); ?>
                    <?php $this->render_parfume_item($current_view); ?>
                <?php endwhile; ?>
            </div>
            
            <div class="pagination-container">
                <?php
                echo paginate_links(array(
                    'total' => $wp_query->max_num_pages,
                    'current' => max(1, get_query_var('paged')),
                    'format' => '?paged=%#%',
                    'show_all' => false,
                    'end_size' => 3,
                    'mid_size' => 3,
                    'prev_next' => true,
                    'prev_text' => __('‹ Предишна', 'parfume-catalog'),
                    'next_text' => __('Следваща ›', 'parfume-catalog'),
                    'add_args' => array_filter($_GET, function($key) {
                        return $key !== 'paged';
                    }, ARRAY_FILTER_USE_KEY)
                ));
                ?>
            </div>
            
        <?php else: ?>
            <div class="no-parfumes-found">
                <div class="no-results-icon">
                    <span class="dashicons dashicons-search"></span>
                </div>
                <h3><?php _e('Няма намерени парфюми', 'parfume-catalog'); ?></h3>
                <p><?php _e('Опитайте да промените филтрите или търсачката за да намерите други парфюми.', 'parfume-catalog'); ?></p>
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

<script>
jQuery(document).ready(function($) {
    // Toggle filters
    $('#toggle-filters').click(function() {
        $('#filters-container').slideToggle();
        $('.toggle-arrow').text($('#filters-container').is(':visible') ? '▲' : '▼');
    });
    
    // View controls
    $('.view-btn').click(function() {
        var view = $(this).data('view');
        $('.view-btn').removeClass('active');
        $(this).addClass('active');
        
        $('#parfumes-grid').removeClass('grid-view list-view').addClass(view + '-view');
        
        // Update URL
        var url = new URL(window.location);
        url.searchParams.set('view', view);
        window.history.replaceState({}, '', url);
    });
    
    // Per page and sorting controls
    $('#per-page-select, #orderby-select, #order-select').change(function() {
        var url = new URL(window.location);
        var name = $(this).attr('name');
        var value = $(this).val();
        
        url.searchParams.set(name, value);
        url.searchParams.delete('paged'); // Reset to first page
        
        window.location.href = url.toString();
    });
    
    // Clear filters
    $('#clear-filters').click(function() {
        $('#filters-form')[0].reset();
        $('#filters-form').submit();
    });
    
    $('#clear-all-filters').click(function() {
        window.location.href = window.location.pathname;
    });
    
    $('#reset-search').click(function() {
        window.location.href = window.location.pathname;
    });
    
    // Auto-submit filters on change
    $('.filter-select').change(function() {
        $('#filters-form').submit();
    });
    
    // Comparison functionality
    <?php if ($comparison_settings['enabled']): ?>
    var comparisonItems = JSON.parse(localStorage.getItem('parfumeComparison') || '[]');
    var maxItems = <?php echo $comparison_settings['max_items']; ?>;
    
    function updateComparisonButtons() {
        $('.comparison-btn').each(function() {
            var parfumeId = $(this).data('parfume-id');
            var isInComparison = comparisonItems.some(function(item) {
                return item.id == parfumeId;
            });
            
            if (isInComparison) {
                $(this).addClass('in-comparison')
                       .find('.comparison-text')
                       .text('<?php echo esc_js($comparison_settings['texts']['remove']); ?>');
            } else {
                $(this).removeClass('in-comparison')
                       .find('.comparison-text')
                       .text('<?php echo esc_js($comparison_settings['texts']['add']); ?>');
            }
        });
        
        // Update comparison popup
        if (comparisonItems.length >= 2) {
            updateComparisonPopup();
            $('#comparison-popup').show();
        } else {
            $('#comparison-popup').hide();
        }
    }
    
    function updateComparisonPopup() {
        var html = '';
        comparisonItems.forEach(function(item) {
            html += '<div class="comparison-item" data-id="' + item.id + '">' +
                '<img src="' + item.image + '" alt="" class="comparison-image">' +
                '<h4 class="comparison-title">' + item.title + '</h4>' +
                '<button type="button" class="remove-from-comparison">×</button>' +
                '</div>';
        });
        $('#comparison-items').html(html);
    }
    
    // Add/remove from comparison
    $(document).on('click', '.comparison-btn', function() {
        var parfumeId = parseInt($(this).data('parfume-id'));
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
    
    // Remove from comparison popup
    $(document).on('click', '.remove-from-comparison', function() {
        var parfumeId = parseInt($(this).closest('.comparison-item').data('id'));
        comparisonItems = comparisonItems.filter(function(item) {
            return item.id !== parfumeId;
        });
        
        localStorage.setItem('parfumeComparison', JSON.stringify(comparisonItems));
        updateComparisonButtons();
    });
    
    // Clear all comparison
    $('#clear-comparison').click(function() {
        comparisonItems = [];
        localStorage.setItem('parfumeComparison', JSON.stringify(comparisonItems));
        updateComparisonButtons();
    });
    
    // Close comparison popup
    $('.comparison-close').click(function() {
        $('#comparison-popup').hide();
    });
    
    // Initialize comparison
    updateComparisonButtons();
    <?php endif; ?>
});
</script>

<?php
get_footer();

// Helper methods
function has_active_filters() {
    return !empty($_GET['parfume_type']) || 
           !empty($_GET['parfume_brand']) || 
           !empty($_GET['parfume_season']) || 
           !empty($_GET['parfume_intensity']) || 
           !empty($_GET['price_range']) || 
           !empty($_GET['notes_search']) || 
           !empty($_GET['s']);
}

function render_active_filters() {
    $filters = array();
    
    if (!empty($_GET['parfume_type'])) {
        $term = get_term_by('slug', $_GET['parfume_type'], 'parfume_type');
        if ($term) {
            $filters[] = array('label' => $term->name, 'param' => 'parfume_type');
        }
    }
    
    if (!empty($_GET['parfume_brand'])) {
        $term = get_term_by('slug', $_GET['parfume_brand'], 'parfume_marki');
        if ($term) {
            $filters[] = array('label' => $term->name, 'param' => 'parfume_brand');
        }
    }
    
    if (!empty($_GET['parfume_season'])) {
        $term = get_term_by('slug', $_GET['parfume_season'], 'parfume_season');
        if ($term) {
            $filters[] = array('label' => $term->name, 'param' => 'parfume_season');
        }
    }
    
    if (!empty($_GET['parfume_intensity'])) {
        $term = get_term_by('slug', $_GET['parfume_intensity'], 'parfume_intensity');
        if ($term) {
            $filters[] = array('label' => $term->name, 'param' => 'parfume_intensity');
        }
    }
    
    if (!empty($_GET['price_range'])) {
        $price_labels = array(
            '1-2' => __('Евтини', 'parfume-catalog'),
            '2-3' => __('Добра цена', 'parfume-catalog'),
            '3-4' => __('Средни', 'parfume-catalog'),
            '4-5' => __('Скъпи', 'parfume-catalog')
        );
        
        $price_range = $_GET['price_range'];
        if (isset($price_labels[$price_range])) {
            $filters[] = array('label' => $price_labels[$price_range], 'param' => 'price_range');
        }
    }
    
    if (!empty($_GET['notes_search'])) {
        $filters[] = array('label' => __('Нотки: ', 'parfume-catalog') . $_GET['notes_search'], 'param' => 'notes_search');
    }
    
    if (!empty($_GET['s'])) {
        $filters[] = array('label' => __('Търсене: ', 'parfume-catalog') . $_GET['s'], 'param' => 's');
    }
    
    foreach ($filters as $filter):
        $remove_url = remove_query_arg($filter['param']);
    ?>
        <span class="active-filter">
            <?php echo esc_html($filter['label']); ?>
            <a href="<?php echo esc_url($remove_url); ?>" class="remove-filter">×</a>
        </span>
    <?php endforeach;
}

function render_parfume_item($view) {
    $post_id = get_the_ID();
    $parfume_stats = Parfume_Meta_Stats::get_public_stats($post_id);
    $parfume_basic = Parfume_Meta_Basic::get_parfume_info($post_id);
    $main_notes = Parfume_Meta_Notes::get_formatted_notes(get_post_meta($post_id, '_parfume_main_notes', true) ?: array());
    
    // Get taxonomies
    $parfume_marki = get_the_terms($post_id, 'parfume_marki');
    $parfume_type = get_the_terms($post_id, 'parfume_type');
    
    $comparison_settings = Parfume_Admin_Comparison::get_comparison_settings();
    ?>
    <div class="parfume-item <?php echo esc_attr($view); ?>-item">
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
            
            <?php if ($parfume_basic['limited_edition']): ?>
                <span class="limited-badge"><?php _e('Лимитирано', 'parfume-catalog'); ?></span>
            <?php endif; ?>
            
            <?php if ($parfume_basic['discontinued']): ?>
                <span class="discontinued-badge"><?php _e('Спрян', 'parfume-catalog'); ?></span>
            <?php endif; ?>
        </div>
        
        <div class="parfume-item-content">
            <h3 class="parfume-item-title">
                <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
            </h3>
            
            <div class="parfume-item-meta">
                <?php if ($parfume_marki): ?>
                    <div class="parfume-brand">
                        <a href="<?php echo get_term_link($parfume_marki[0]); ?>">
                            <?php echo esc_html($parfume_marki[0]->name); ?>
                        </a>
                    </div>
                <?php endif; ?>
                
                <?php if ($parfume_type): ?>
                    <div class="parfume-type">
                        <?php echo esc_html($parfume_type[0]->name); ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <?php if ($parfume_stats['total_reviews'] > 0): ?>
                <div class="parfume-rating">
                    <div class="stars-rating">
                        <?php echo Parfume_Meta_Stats::get_formatted_rating($post_id)['stars_html']; ?>
                    </div>
                    <span class="rating-count">
                        (<?php echo $parfume_stats['total_reviews']; ?>)
                    </span>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($main_notes) && $view === 'list'): ?>
                <div class="parfume-notes">
                    <strong><?php _e('Основни нотки:', 'parfume-catalog'); ?></strong>
                    <?php
                    $note_names = array_slice(array_column($main_notes, 'name'), 0, 3);
                    echo esc_html(implode(', ', $note_names));
                    if (count($main_notes) > 3) {
                        echo '...';
                    }
                    ?>
                </div>
            <?php endif; ?>
            
            <?php if ($view === 'list' && $parfume_basic['description_short']): ?>
                <div class="parfume-description">
                    <?php echo esc_html(wp_trim_words($parfume_basic['description_short'], 20)); ?>
                </div>
            <?php endif; ?>
            
            <div class="parfume-item-footer">
                <?php if (!empty($parfume_basic['suitable_for'])): ?>
                    <div class="suitable-icons">
                        <?php
                        $suitable_icons = array(
                            'spring' => '🌸',
                            'summer' => '☀️', 
                            'autumn' => '🍂',
                            'winter' => '❄️',
                            'day' => '🌅',
                            'night' => '🌙'
                        );
                        
                        foreach (array_slice($parfume_basic['suitable_for'], 0, 3) as $suitable):
                            if (isset($suitable_icons[$suitable])):
                        ?>
                            <span class="suitable-icon" title="<?php echo esc_attr(Parfume_Meta_Basic::get_suitable_for_labels()[$suitable] ?? ''); ?>">
                                <?php echo $suitable_icons[$suitable]; ?>
                            </span>
                        <?php 
                            endif;
                        endforeach; 
                        ?>
                    </div>
                <?php endif; ?>
                
                <a href="<?php the_permalink(); ?>" class="view-parfume-btn">
                    <?php _e('Виж детайли', 'parfume-catalog'); ?>
                </a>
            </div>
        </div>
    </div>
    <?php
}
?>

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

.apply-filters-btn {
    background: #0073aa;
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 4px;
    cursor: pointer;
}

.clear-filters-btn {
    background: #666;
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 4px;
    cursor: pointer;
}

.sorting-section {
    display: flex;
    align-items: center;
    gap: 20px;
    flex-wrap: wrap;
}

.view-controls {
    display: flex;
    gap: 5px;
}

.view-btn {
    background: #f0f0f0;
    border: 1px solid #ddd;
    padding: 8px 12px;
    cursor: pointer;
    border-radius: 4px;
}

.view-btn.active {
    background: #0073aa;
    color: white;
    border-color: #0073aa;
}

.per-page-control,
.sort-controls {
    display: flex;
    align-items: center;
    gap: 8px;
}

.per-page-control label,
.sort-controls label {
    font-size: 14px;
    color: #555;
}

.per-page-control select,
.sort-controls select {
    padding: 5px 8px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.active-filters {
    display: flex;
    align-items: center;
    gap: 15px;
    margin-bottom: 20px;
    padding: 15px;
    background: #fff3cd;
    border: 1px solid #ffeaa7;
    border-radius: 4px;
}

.active-filters-label {
    font-weight: 500;
    color: #856404;
}

.active-filters-list {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.active-filter {
    background: #fff;
    border: 1px solid #ddd;
    padding: 4px 8px;
    border-radius: 15px;
    font-size: 13px;
    display: flex;
    align-items: center;
    gap: 5px;
}

.remove-filter {
    color: #dc3232;
    text-decoration: none;
    font-weight: bold;
}

.clear-all-btn {
    background: #dc3232;
    color: white;
    border: none;
    padding: 5px 15px;
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
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
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
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.parfume-item.list-item {
    display: grid;
    grid-template-columns: 200px 1fr;
    gap: 20px;
}

.parfume-image-container {
    position: relative;
}

.parfume-item-image {
    width: 100%;
    height: 250px;
    object-fit: cover;
}

.parfume-item.list-item .parfume-item-image {
    height: 150px;
}

.parfume-placeholder {
    width: 100%;
    height: 250px;
    background: #f0f0f0;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 48px;
    color: #ccc;
}

.comparison-btn {
    position: absolute;
    top: 10px;
    right: 10px;
    background: rgba(0, 115, 170, 0.9);
    color: white;
    border: none;
    padding: 5px 10px;
    border-radius: 4px;
    cursor: pointer;
    font-size: 12px;
    display: flex;
    align-items: center;
    gap: 4px;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.parfume-item:hover .comparison-btn {
    opacity: 1;
}

.comparison-btn.in-comparison {
    background: #46b450;
    opacity: 1;
}

.limited-badge,
.discontinued-badge {
    position: absolute;
    top: 10px;
    left: 10px;
    padding: 3px 8px;
    border-radius: 3px;
    font-size: 11px;
    font-weight: bold;
    color: white;
}

.limited-badge {
    background: #ff6900;
}

.discontinued-badge {
    background: #dc3232;
}

.parfume-item-content {
    padding: 15px;
}

.parfume-item-title {
    margin: 0 0 10px 0;
    font-size: 16px;
    line-height: 1.3;
}

.parfume-item-title a {
    color: #333;
    text-decoration: none;
}

.parfume-item-title a:hover {
    color: #0073aa;
}

.parfume-item-meta {
    margin-bottom: 10px;
    font-size: 13px;
    color: #666;
}

.parfume-brand {
    font-weight: 500;
}

.parfume-brand a {
    color: #0073aa;
    text-decoration: none;
}

.parfume-rating {
    display: flex;
    align-items: center;
    gap: 5px;
    margin-bottom: 10px;
}

.stars-rating {
    color: #ffb900;
    font-size: 14px;
}

.rating-count {
    font-size: 12px;
    color: #666;
}

.parfume-notes {
    margin-bottom: 10px;
    font-size: 13px;
    color: #555;
}

.parfume-description {
    margin-bottom: 15px;
    font-size: 13px;
    color: #666;
    line-height: 1.4;
}

.parfume-item-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
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
    padding: 6px 12px;
    border-radius: 4px;
    text-decoration: none;
    font-size: 12px;
    font-weight: 500;
}

.view-parfume-btn:hover {
    background: #005a87;
    color: white;
    text-decoration: none;
}

.no-parfumes-found {
    text-align: center;
    padding: 60px 20px;
    background: #f9f9f9;
    border-radius: 8px;
}

.no-results-icon {
    font-size: 64px;
    color: #ccc;
    margin-bottom: 20px;
}

.no-parfumes-found h3 {
    margin: 0 0 10px 0;
    color: #333;
}

.no-parfumes-found p {
    margin: 0 0 20px 0;
    color: #666;
}

.pagination-container {
    display: flex;
    justify-content: center;
    margin-top: 40px;
}

.page-numbers {
    display: flex;
    gap: 5px;
}

.page-numbers a,
.page-numbers span {
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    text-decoration: none;
    color: #333;
}

.page-numbers a:hover {
    background: #0073aa;
    color: white;
    border-color: #0073aa;
}

.page-numbers .current {
    background: #0073aa;
    color: white;
    border-color: #0073aa;
}

.comparison-popup {
    position: fixed;
    bottom: 20px;
    right: 20px;
    width: 300px;
    background: white;
    border: 1px solid #ddd;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.2);
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