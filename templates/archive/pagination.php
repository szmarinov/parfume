<?php
/**
 * Archive Pagination
 * 
 * @package ParfumeReviews
 * @subpackage Templates\Archive
 */

namespace ParfumeReviews\Templates\Archive;

if (!defined('ABSPATH')) {
    exit;
}

class Pagination {
    
    /**
     * Render pagination
     */
    public static function render($query = null) {
        global $wp_query;
        
        if ($query === null) {
            $query = $wp_query;
        }
        
        if ($query->max_num_pages <= 1) {
            return;
        }
        
        $current_page = max(1, get_query_var('paged'));
        $max_pages = $query->max_num_pages;
        
        ob_start();
        ?>
        <nav class="parfume-pagination" aria-label="<?php _e('Perfume pagination', 'parfume-reviews'); ?>">
            <?php self::render_pagination_info($current_page, $max_pages, $query); ?>
            <?php self::render_pagination_links($current_page, $max_pages); ?>
        </nav>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Render pagination info
     */
    private static function render_pagination_info($current_page, $max_pages, $query) {
        $total_posts = $query->found_posts;
        $posts_per_page = $query->get('posts_per_page');
        
        $start = (($current_page - 1) * $posts_per_page) + 1;
        $end = min($current_page * $posts_per_page, $total_posts);
        ?>
        <div class="pagination-info">
            <span class="pagination-results">
                <?php
                printf(
                    __('Showing %d-%d of %d perfumes', 'parfume-reviews'),
                    $start,
                    $end,
                    $total_posts
                );
                ?>
            </span>
            
            <?php if ($max_pages > 1): ?>
                <span class="pagination-pages">
                    <?php
                    printf(
                        __('Page %d of %d', 'parfume-reviews'),
                        $current_page,
                        $max_pages
                    );
                    ?>
                </span>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * Render pagination links
     */
    private static function render_pagination_links($current_page, $max_pages) {
        $base_url = self::get_base_url();
        ?>
        <div class="pagination-links">
            <?php
            // Previous page link
            if ($current_page > 1) {
                $prev_url = self::get_page_url($current_page - 1);
                ?>
                <a href="<?php echo esc_url($prev_url); ?>" 
                   class="pagination-link prev-link"
                   aria-label="<?php _e('Previous page', 'parfume-reviews'); ?>">
                    <span class="arrow">‹</span>
                    <span class="text"><?php _e('Previous', 'parfume-reviews'); ?></span>
                </a>
                <?php
            }
            
            // Page number links
            self::render_page_numbers($current_page, $max_pages);
            
            // Next page link
            if ($current_page < $max_pages) {
                $next_url = self::get_page_url($current_page + 1);
                ?>
                <a href="<?php echo esc_url($next_url); ?>" 
                   class="pagination-link next-link"
                   aria-label="<?php _e('Next page', 'parfume-reviews'); ?>">
                    <span class="text"><?php _e('Next', 'parfume-reviews'); ?></span>
                    <span class="arrow">›</span>
                </a>
                <?php
            }
            ?>
        </div>
        <?php
    }
    
    /**
     * Render page number links
     */
    private static function render_page_numbers($current_page, $max_pages) {
        $range = 2; // Number of pages to show on each side of current page
        $start = max(1, $current_page - $range);
        $end = min($max_pages, $current_page + $range);
        
        ?>
        <div class="pagination-numbers">
            <?php
            // First page + ellipsis
            if ($start > 1) {
                ?>
                <a href="<?php echo esc_url(self::get_page_url(1)); ?>" 
                   class="pagination-link page-link">1</a>
                <?php
                
                if ($start > 2) {
                    ?>
                    <span class="pagination-ellipsis">…</span>
                    <?php
                }
            }
            
            // Page numbers in range
            for ($i = $start; $i <= $end; $i++) {
                if ($i == $current_page) {
                    ?>
                    <span class="pagination-link current-page" aria-current="page">
                        <?php echo $i; ?>
                    </span>
                    <?php
                } else {
                    ?>
                    <a href="<?php echo esc_url(self::get_page_url($i)); ?>" 
                       class="pagination-link page-link">
                        <?php echo $i; ?>
                    </a>
                    <?php
                }
            }
            
            // Last page + ellipsis
            if ($end < $max_pages) {
                if ($end < $max_pages - 1) {
                    ?>
                    <span class="pagination-ellipsis">…</span>
                    <?php
                }
                
                ?>
                <a href="<?php echo esc_url(self::get_page_url($max_pages)); ?>" 
                   class="pagination-link page-link"><?php echo $max_pages; ?></a>
                <?php
            }
            ?>
        </div>
        <?php
    }
    
    /**
     * Get base URL for pagination
     */
    private static function get_base_url() {
        global $wp;
        return home_url(add_query_arg(array(), $wp->request));
    }
    
    /**
     * Get URL for specific page
     */
    private static function get_page_url($page) {
        if ($page <= 1) {
            // Remove page parameter for page 1
            return remove_query_arg('paged');
        }
        
        return add_query_arg('paged', $page);
    }
}