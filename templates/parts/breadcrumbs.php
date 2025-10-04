<?php
/**
 * Breadcrumbs Component
 * 
 * Navigation breadcrumbs for parfume pages
 * 
 * @package Parfume_Reviews
 * @since 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Check if breadcrumbs are enabled
$settings = get_option('parfume_reviews_settings', []);
$enabled = isset($settings['enable_breadcrumbs']) ? $settings['enable_breadcrumbs'] : true;

if (!$enabled) {
    return;
}

// Build breadcrumb items
$breadcrumbs = [];

// Home
$breadcrumbs[] = [
    'url' => home_url('/'),
    'title' => __('Начало', 'parfume-reviews')
];

// Parfume archive
$breadcrumbs[] = [
    'url' => get_post_type_archive_link('parfume'),
    'title' => __('Парфюми', 'parfume-reviews')
];

// Add specific breadcrumbs based on current page
if (is_singular('parfume')) {
    // Single parfume
    $post_id = get_the_ID();
    
    // Add brand if exists
    $brands = wp_get_post_terms($post_id, 'marki');
    if (!empty($brands)) {
        $breadcrumbs[] = [
            'url' => get_term_link($brands[0]),
            'title' => $brands[0]->name
        ];
    }
    
    // Current parfume (no link)
    $breadcrumbs[] = [
        'url' => '',
        'title' => get_the_title()
    ];
    
} elseif (is_tax()) {
    // Taxonomy page
    $queried_object = get_queried_object();
    
    if ($queried_object) {
        // Get taxonomy object
        $taxonomy = get_taxonomy($queried_object->taxonomy);
        
        // Add taxonomy archive (if not perfumer)
        if ($queried_object->taxonomy !== 'perfumer') {
            $breadcrumbs[] = [
                'url' => get_post_type_archive_link('parfume') . '?taxonomy=' . $queried_object->taxonomy,
                'title' => $taxonomy->labels->name
            ];
        }
        
        // Current term (no link)
        $breadcrumbs[] = [
            'url' => '',
            'title' => $queried_object->name
        ];
    }
    
} elseif (is_search()) {
    // Search results
    $breadcrumbs[] = [
        'url' => '',
        'title' => sprintf(__('Търсене: %s', 'parfume-reviews'), get_search_query())
    ];
}

// Don't show if only home and parfumes
if (count($breadcrumbs) <= 2 && is_post_type_archive('parfume')) {
    return;
}

?>

<nav class="parfume-breadcrumbs" aria-label="<?php _e('Breadcrumbs', 'parfume-reviews'); ?>">
    <ol class="breadcrumb-list" itemscope itemtype="https://schema.org/BreadcrumbList">
        
        <?php foreach ($breadcrumbs as $index => $crumb) : ?>
            
            <li class="breadcrumb-item" itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
                
                <?php if (!empty($crumb['url'])) : ?>
                    <a href="<?php echo esc_url($crumb['url']); ?>" itemprop="item">
                        <span itemprop="name"><?php echo esc_html($crumb['title']); ?></span>
                    </a>
                <?php else : ?>
                    <span itemprop="name"><?php echo esc_html($crumb['title']); ?></span>
                <?php endif; ?>
                
                <meta itemprop="position" content="<?php echo $index + 1; ?>" />
                
                <?php if ($index < count($breadcrumbs) - 1) : ?>
                    <span class="separator" aria-hidden="true">/</span>
                <?php endif; ?>
                
            </li>
            
        <?php endforeach; ?>
        
    </ol>
</nav>