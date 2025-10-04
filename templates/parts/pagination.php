<?php
/**
 * Pagination Component
 * 
 * Custom pagination for parfume archives
 * 
 * @package Parfume_Reviews
 * @since 2.0.0
 * 
 * @var WP_Query $query Optional query object (defaults to global $wp_query)
 */

if (!defined('ABSPATH')) {
    exit;
}

// Use provided query or global
global $wp_query;
if (!isset($query)) {
    $query = $wp_query;
}

// Get pagination info
$total_pages = $query->max_num_pages;
$current_page = max(1, get_query_var('paged'));

// Don't show if only one page
if ($total_pages <= 1) {
    return;
}

// Calculate page range
$range = 2; // Show 2 pages on each side of current page
$show_first = true;
$show_last = true;

// Build page numbers array
$pages = [];

// Always show first page
if ($show_first) {
    $pages[] = 1;
}

// Add dots before range if needed
if ($current_page > $range + 2) {
    $pages[] = '...';
}

// Add pages in range
for ($i = max(2, $current_page - $range); $i <= min($total_pages - 1, $current_page + $range); $i++) {
    $pages[] = $i;
}

// Add dots after range if needed
if ($current_page < $total_pages - $range - 1) {
    $pages[] = '...';
}

// Always show last page
if ($show_last && $total_pages > 1) {
    $pages[] = $total_pages;
}

// Get base URL for pagination
$base_url = get_pagenum_link(1);
if (is_tax()) {
    $queried_object = get_queried_object();
    $base_url = get_term_link($queried_object);
}

?>

<nav class="parfume-pagination" role="navigation" aria-label="<?php _e('Навигация между страниците', 'parfume-reviews'); ?>">
    
    <div class="pagination-info">
        <?php
        printf(
            __('Страница %d от %d', 'parfume-reviews'),
            $current_page,
            $total_pages
        );
        ?>
    </div>
    
    <ul class="pagination-list">
        
        <!-- Previous Page -->
        <?php if ($current_page > 1) : ?>
            <li class="pagination-item pagination-prev">
                <a href="<?php echo esc_url(get_pagenum_link($current_page - 1)); ?>" 
                   class="pagination-link"
                   aria-label="<?php _e('Предишна страница', 'parfume-reviews'); ?>">
                    <span class="dashicons dashicons-arrow-left-alt2"></span>
                    <span class="pagination-text"><?php _e('Предишна', 'parfume-reviews'); ?></span>
                </a>
            </li>
        <?php else : ?>
            <li class="pagination-item pagination-prev disabled">
                <span class="pagination-link">
                    <span class="dashicons dashicons-arrow-left-alt2"></span>
                    <span class="pagination-text"><?php _e('Предишна', 'parfume-reviews'); ?></span>
                </span>
            </li>
        <?php endif; ?>
        
        <!-- Page Numbers -->
        <?php foreach ($pages as $page) : ?>
            
            <?php if ($page === '...') : ?>
                <!-- Dots -->
                <li class="pagination-item pagination-dots">
                    <span class="pagination-link">...</span>
                </li>
                
            <?php elseif ($page == $current_page) : ?>
                <!-- Current Page -->
                <li class="pagination-item pagination-current">
                    <span class="pagination-link current" aria-current="page">
                        <?php echo $page; ?>
                    </span>
                </li>
                
            <?php else : ?>
                <!-- Other Pages -->
                <li class="pagination-item">
                    <a href="<?php echo esc_url(get_pagenum_link($page)); ?>" 
                       class="pagination-link"
                       aria-label="<?php printf(__('Страница %d', 'parfume-reviews'), $page); ?>">
                        <?php echo $page; ?>
                    </a>
                </li>
            <?php endif; ?>
            
        <?php endforeach; ?>
        
        <!-- Next Page -->
        <?php if ($current_page < $total_pages) : ?>
            <li class="pagination-item pagination-next">
                <a href="<?php echo esc_url(get_pagenum_link($current_page + 1)); ?>" 
                   class="pagination-link"
                   aria-label="<?php _e('Следваща страница', 'parfume-reviews'); ?>">
                    <span class="pagination-text"><?php _e('Следваща', 'parfume-reviews'); ?></span>
                    <span class="dashicons dashicons-arrow-right-alt2"></span>
                </a>
            </li>
        <?php else : ?>
            <li class="pagination-item pagination-next disabled">
                <span class="pagination-link">
                    <span class="pagination-text"><?php _e('Следваща', 'parfume-reviews'); ?></span>
                    <span class="dashicons dashicons-arrow-right-alt2"></span>
                </span>
            </li>
        <?php endif; ?>
        
    </ul>
    
    <!-- Jump to Page (optional) -->
    <?php if ($total_pages > 10) : ?>
        <div class="pagination-jump">
            <form method="get" action="<?php echo esc_url($base_url); ?>" class="jump-form">
                <label for="jump-to-page" class="screen-reader-text">
                    <?php _e('Отиди на страница', 'parfume-reviews'); ?>
                </label>
                
                <input type="number" 
                       id="jump-to-page" 
                       name="paged" 
                       min="1" 
                       max="<?php echo $total_pages; ?>" 
                       value="<?php echo $current_page; ?>"
                       class="jump-input">
                
                <button type="submit" class="jump-button">
                    <?php _e('Отиди', 'parfume-reviews'); ?>
                </button>
                
                <?php
                // Preserve query parameters
                $query_params = $_GET;
                unset($query_params['paged']);
                
                foreach ($query_params as $key => $value) {
                    if (is_array($value)) {
                        foreach ($value as $v) {
                            echo '<input type="hidden" name="' . esc_attr($key) . '[]" value="' . esc_attr($v) . '">';
                        }
                    } else {
                        echo '<input type="hidden" name="' . esc_attr($key) . '" value="' . esc_attr($value) . '">';
                    }
                }
                ?>
            </form>
        </div>
    <?php endif; ?>
    
</nav>