<?php
/**
 * Perfume Grid Module for Parfume Reviews
 * 
 * Reusable grid component for displaying multiple perfumes
 * 
 * @param WP_Query|array $posts         The posts to display (WP_Query object or array of post IDs)
 * @param string         $layout        Grid layout: 'grid', 'list', 'masonry', 'featured'
 * @param array          $grid_options  Grid display options
 * @param array          $card_options  Card display options (passed to each card)
 */

if (!defined('ABSPATH')) {
    exit;
}

// Default parameters
$posts = isset($posts) ? $posts : null;
$layout = isset($layout) ? sanitize_text_field($layout) : 'grid';
$grid_options = isset($grid_options) ? $grid_options : array();
$card_options = isset($card_options) ? $card_options : array();

// Default grid options
$default_grid_options = array(
    'columns' => 3,
    'columns_tablet' => 2,
    'columns_mobile' => 1,
    'gap' => '20px',
    'show_pagination' => true,
    'show_count' => true,
    'show_sorting' => false,
    'show_filters' => false,
    'container_class' => '',
    'grid_class' => '',
    'no_results_message' => __('No perfumes found.', 'parfume-reviews'),
    'loading_message' => __('Loading perfumes...', 'parfume-reviews'),
);

$grid_options = wp_parse_args($grid_options, $default_grid_options);

// Handle different post sources
$perfume_posts = array();
$total_posts = 0;
$max_pages = 1;

if (is_a($posts, 'WP_Query')) {
    // WP_Query object
    $perfume_posts = $posts->posts;
    $total_posts = $posts->found_posts;
    $max_pages = $posts->max_num_pages;
} elseif (is_array($posts)) {
    // Array of post IDs
    $perfume_posts = array_map('get_post', $posts);
    $total_posts = count($perfume_posts);
} elseif (is_null($posts)) {
    // Use global $wp_query
    global $wp_query;
    if (have_posts()) {
        while (have_posts()) {
            the_post();
            $perfume_posts[] = get_post();
        }
        wp_reset_postdata();
        $total_posts = $wp_query->found_posts;
        $max_pages = $wp_query->max_num_pages;
    }
}

// Filter out invalid posts
$perfume_posts = array_filter($perfume_posts, function($post) {
    return $post && $post->post_type === 'parfume' && $post->post_status === 'publish';
});

// Generate CSS classes
$container_classes = array('parfume-grid-container', 'parfume-grid-container--' . $layout);
if (!empty($grid_options['container_class'])) {
    $container_classes[] = $grid_options['container_class'];
}

$grid_classes = array('parfume-grid', 'parfume-grid--' . $layout);
$grid_classes[] = 'parfume-grid--' . $grid_options['columns'] . '-cols';
if (!empty($grid_options['grid_class'])) {
    $grid_classes[] = $grid_options['grid_class'];
}

// Determine card layout based on grid layout
$card_layout = $layout === 'list' ? 'list' : 'grid';
if ($layout === 'featured' && !empty($perfume_posts)) {
    // First item as featured, rest as grid
    $card_layout = 'grid';
}
?>

<div class="<?php echo esc_attr(implode(' ', $container_classes)); ?>" data-layout="<?php echo esc_attr($layout); ?>">
    
    <?php if ($grid_options['show_count'] || $grid_options['show_sorting']): ?>
        <div class="parfume-grid__header">
            
            <?php if ($grid_options['show_count']): ?>
                <div class="parfume-grid__count">
                    <?php 
                    printf(
                        _n(
                            'Showing %d perfume', 
                            'Showing %d perfumes', 
                            count($perfume_posts), 
                            'parfume-reviews'
                        ), 
                        count($perfume_posts)
                    );
                    
                    if ($total_posts > count($perfume_posts)) {
                        printf(' ' . __('of %d total', 'parfume-reviews'), $total_posts);
                    }
                    ?>
                </div>
            <?php endif; ?>
            
            <?php if ($grid_options['show_sorting']): ?>
                <div class="parfume-grid__sorting">
                    <label for="parfume-sort"><?php _e('Sort by:', 'parfume-reviews'); ?></label>
                    <select id="parfume-sort" class="parfume-grid__sort-select">
                        <option value="date-desc" <?php selected(isset($_GET['orderby']) && $_GET['orderby'] === 'date'); ?>>
                            <?php _e('Newest First', 'parfume-reviews'); ?>
                        </option>
                        <option value="date-asc" <?php selected(isset($_GET['orderby']) && $_GET['orderby'] === 'date' && isset($_GET['order']) && $_GET['order'] === 'asc'); ?>>
                            <?php _e('Oldest First', 'parfume-reviews'); ?>
                        </option>
                        <option value="title-asc" <?php selected(isset($_GET['orderby']) && $_GET['orderby'] === 'title'); ?>>
                            <?php _e('Name A-Z', 'parfume-reviews'); ?>
                        </option>
                        <option value="title-desc" <?php selected(isset($_GET['orderby']) && $_GET['orderby'] === 'title' && isset($_GET['order']) && $_GET['order'] === 'desc'); ?>>
                            <?php _e('Name Z-A', 'parfume-reviews'); ?>
                        </option>
                        <option value="rating-desc" <?php selected(isset($_GET['orderby']) && $_GET['orderby'] === 'meta_value_num' && isset($_GET['meta_key']) && $_GET['meta_key'] === '_parfume_rating'); ?>>
                            <?php _e('Highest Rated', 'parfume-reviews'); ?>
                        </option>
                        <option value="rating-asc" <?php selected(isset($_GET['orderby']) && $_GET['orderby'] === 'meta_value_num' && isset($_GET['meta_key']) && $_GET['meta_key'] === '_parfume_rating' && isset($_GET['order']) && $_GET['order'] === 'asc'); ?>>
                            <?php _e('Lowest Rated', 'parfume-reviews'); ?>
                        </option>
                    </select>
                </div>
            <?php endif; ?>
            
        </div>
    <?php endif; ?>
    
    <?php if ($grid_options['show_filters']): ?>
        <div class="parfume-grid__filters">
            <?php echo do_shortcode('[parfume_filters]'); ?>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($perfume_posts)): ?>
        
        <div class="<?php echo esc_attr(implode(' ', $grid_classes)); ?>" 
             style="--grid-gap: <?php echo esc_attr($grid_options['gap']); ?>; --grid-columns: <?php echo esc_attr($grid_options['columns']); ?>; --grid-columns-tablet: <?php echo esc_attr($grid_options['columns_tablet']); ?>; --grid-columns-mobile: <?php echo esc_attr($grid_options['columns_mobile']); ?>;">
            
            <?php foreach ($perfume_posts as $index => $post): ?>
                <?php 
                // Setup post data
                $GLOBALS['post'] = $post;
                setup_postdata($post);
                
                // Determine card layout for this item
                $current_card_layout = $card_layout;
                if ($layout === 'featured' && $index === 0) {
                    $current_card_layout = 'featured';
                }
                
                // Add index class for special styling
                $current_card_options = $card_options;
                if (!isset($current_card_options['additional_classes'])) {
                    $current_card_options['additional_classes'] = array();
                }
                $current_card_options['additional_classes'][] = 'parfume-grid__item';
                $current_card_options['additional_classes'][] = 'parfume-grid__item--' . $index;
                
                if ($layout === 'featured' && $index === 0) {
                    $current_card_options['additional_classes'][] = 'parfume-grid__item--featured';
                }
                ?>
                
                <div class="parfume-grid__card-wrapper">
                    <?php parfume_reviews_render_card($post->ID, $current_card_layout, $current_card_options); ?>
                </div>
                
            <?php endforeach; ?>
            
        </div>
        
        <?php wp_reset_postdata(); ?>
        
        <?php if ($grid_options['show_pagination'] && $max_pages > 1): ?>
            <div class="parfume-grid__pagination">
                <?php
                // Use WordPress pagination if available
                if (function_exists('the_posts_pagination')) {
                    the_posts_pagination(array(
                        'mid_size' => 2,
                        'prev_text' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 18l-6-6 6-6"/></svg>' . __('Previous', 'parfume-reviews'),
                        'next_text' => __('Next', 'parfume-reviews') . '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18l6-6-6-6"/></svg>',
                        'screen_reader_text' => __('Posts navigation', 'parfume-reviews'),
                    ));
                } else {
                    // Fallback pagination
                    echo '<div class="pagination-fallback">';
                    echo '<p>' . sprintf(__('Page %d of %d', 'parfume-reviews'), max(1, get_query_var('paged')), $max_pages) . '</p>';
                    echo '</div>';
                }
                ?>
            </div>
        <?php endif; ?>
        
    <?php else: ?>
        
        <div class="parfume-grid__empty">
            <div class="parfume-grid__empty-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path d="M9.75 9.75l4.5 4.5m0-4.5l-4.5 4.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <h3 class="parfume-grid__empty-title"><?php _e('No Perfumes Found', 'parfume-reviews'); ?></h3>
            <p class="parfume-grid__empty-message"><?php echo esc_html($grid_options['no_results_message']); ?></p>
            
            <?php if (isset($_GET) && count($_GET) > 0): ?>
                <div class="parfume-grid__empty-actions">
                    <a href="<?php echo esc_url(remove_query_arg(array_keys($_GET))); ?>" class="parfume-grid__clear-filters">
                        <?php _e('Clear All Filters', 'parfume-reviews'); ?>
                    </a>
                </div>
            <?php endif; ?>
        </div>
        
    <?php endif; ?>
    
</div>

<style>
/* Grid Container */
.parfume-grid-container {
    margin-bottom: 40px;
}

/* Grid Header */
.parfume-grid__header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
    padding-bottom: 16px;
    border-bottom: 1px solid #e5e7eb;
    flex-wrap: wrap;
    gap: 16px;
}

.parfume-grid__count {
    font-size: 14px;
    color: #6b7280;
    font-weight: 500;
}

.parfume-grid__sorting {
    display: flex;
    align-items: center;
    gap: 8px;
}

.parfume-grid__sorting label {
    font-size: 14px;
    color: #374151;
    font-weight: 500;
}

.parfume-grid__sort-select {
    padding: 6px 12px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    background: white;
    font-size: 14px;
    color: #374151;
}

/* Grid Filters */
.parfume-grid__filters {
    margin-bottom: 32px;
}

/* Main Grid */
.parfume-grid {
    display: grid;
    gap: var(--grid-gap, 20px);
    grid-template-columns: repeat(var(--grid-columns, 3), 1fr);
}

/* Grid Layouts */
.parfume-grid--grid {
    grid-template-columns: repeat(var(--grid-columns, 3), 1fr);
}

.parfume-grid--list .parfume-grid__card-wrapper {
    grid-column: 1 / -1;
}

.parfume-grid--masonry {
    grid-template-rows: masonry;
    align-items: start;
}

.parfume-grid--featured .parfume-grid__item--featured {
    grid-column: 1 / -1;
    grid-row: 1;
}

.parfume-grid--featured .parfume-grid__card-wrapper:first-child {
    margin-bottom: var(--grid-gap, 20px);
}

/* Column Variations */
.parfume-grid--1-cols { grid-template-columns: 1fr; }
.parfume-grid--2-cols { grid-template-columns: repeat(2, 1fr); }
.parfume-grid--3-cols { grid-template-columns: repeat(3, 1fr); }
.parfume-grid--4-cols { grid-template-columns: repeat(4, 1fr); }
.parfume-grid--5-cols { grid-template-columns: repeat(5, 1fr); }
.parfume-grid--6-cols { grid-template-columns: repeat(6, 1fr); }

/* Card Wrapper */
.parfume-grid__card-wrapper {
    display: flex;
    height: 100%;
}

.parfume-grid__card-wrapper .parfume-card {
    width: 100%;
}

/* Empty State */
.parfume-grid__empty {
    text-align: center;
    padding: 80px 20px;
    color: #6b7280;
}

.parfume-grid__empty-icon {
    width: 64px;
    height: 64px;
    margin: 0 auto 24px;
    color: #d1d5db;
}

.parfume-grid__empty-title {
    font-size: 24px;
    font-weight: 600;
    color: #374151;
    margin: 0 0 12px;
}

.parfume-grid__empty-message {
    font-size: 16px;
    line-height: 1.5;
    margin: 0 0 24px;
    max-width: 500px;
    margin-left: auto;
    margin-right: auto;
}

.parfume-grid__empty-actions {
    margin-top: 24px;
}

.parfume-grid__clear-filters {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 12px 24px;
    background: #4a90e2;
    color: white;
    text-decoration: none;
    border-radius: 8px;
    font-weight: 500;
    transition: all 0.2s ease;
}

.parfume-grid__clear-filters:hover {
    background: #357abd;
    transform: translateY(-1px);
}

/* Pagination */
.parfume-grid__pagination {
    margin-top: 48px;
    display: flex;
    justify-content: center;
}

.parfume-grid__pagination .page-numbers {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 12px 16px;
    border: 1px solid #d1d5db;
    background: white;
    color: #374151;
    text-decoration: none;
    border-radius: 6px;
    font-weight: 500;
    transition: all 0.2s ease;
    margin: 0 4px;
}

.parfume-grid__pagination .page-numbers:hover,
.parfume-grid__pagination .page-numbers.current {
    background: #4a90e2;
    border-color: #4a90e2;
    color: white;
}

.parfume-grid__pagination .page-numbers svg {
    width: 16px;
    height: 16px;
}

.pagination-fallback {
    text-align: center;
    padding: 20px;
    color: #6b7280;
}

/* Loading State */
.parfume-grid-container--loading {
    opacity: 0.6;
    pointer-events: none;
}

.parfume-grid-container--loading::after {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(255, 255, 255, 0.8);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 10;
}

/* Responsive Design */
@media (max-width: 1024px) {
    .parfume-grid {
        grid-template-columns: repeat(var(--grid-columns-tablet, 2), 1fr);
    }
    
    .parfume-grid--4-cols,
    .parfume-grid--5-cols,
    .parfume-grid--6-cols {
        grid-template-columns: repeat(3, 1fr);
    }
}

@media (max-width: 768px) {
    .parfume-grid {
        grid-template-columns: repeat(var(--grid-columns-mobile, 1), 1fr);
        gap: 16px;
    }
    
    .parfume-grid--2-cols,
    .parfume-grid--3-cols,
    .parfume-grid--4-cols,
    .parfume-grid--5-cols,
    .parfume-grid--6-cols {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .parfume-grid__header {
        flex-direction: column;
        align-items: stretch;
        gap: 12px;
    }
    
    .parfume-grid__sorting {
        justify-content: space-between;
    }
}

@media (max-width: 480px) {
    .parfume-grid {
        grid-template-columns: 1fr;
    }
    
    .parfume-grid__empty {
        padding: 60px 20px;
    }
    
    .parfume-grid__empty-icon {
        width: 48px;
        height: 48px;
        margin-bottom: 16px;
    }
    
    .parfume-grid__empty-title {
        font-size: 20px;
    }
    
    .parfume-grid__empty-message {
        font-size: 14px;
    }
}

/* Dark Mode Support */
@media (prefers-color-scheme: dark) {
    .parfume-grid__header {
        border-bottom-color: #374151;
    }
    
    .parfume-grid__count {
        color: #9ca3af;
    }
    
    .parfume-grid__sort-select {
        background: #1f2937;
        border-color: #374151;
        color: #f9fafb;
    }
    
    .parfume-grid__empty {
        color: #9ca3af;
    }
    
    .parfume-grid__empty-title {
        color: #f9fafb;
    }
    
    .parfume-grid__empty-icon {
        color: #4b5563;
    }
}

/* Print Styles */
@media print {
    .parfume-grid__header,
    .parfume-grid__filters,
    .parfume-grid__pagination {
        display: none;
    }
    
    .parfume-grid {
        display: block;
    }
    
    .parfume-grid__card-wrapper {
        break-inside: avoid;
        margin-bottom: 20px;
    }
}

/* Accessibility */
@media (prefers-reduced-motion: reduce) {
    .parfume-card,
    .parfume-grid__clear-filters,
    .page-numbers {
        transition: none;
    }
}

/* Focus States */
.parfume-grid__sort-select:focus {
    outline: 2px solid #4a90e2;
    outline-offset: 2px;
}

.parfume-grid__clear-filters:focus,
.page-numbers:focus {
    outline: 2px solid #4a90e2;
    outline-offset: 2px;
}
</style>