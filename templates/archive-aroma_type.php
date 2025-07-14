<?php
/**
 * Template for All Aroma Types archive page (/parfiumi/aroma-type/)
 * 📁 Файл: templates/archive-aroma_type.php
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header(); 
?>

<div class="parfume-archive aroma-type-archive">
    <div class="archive-header">
        <h1 class="archive-title"><?php _e('Perfumes by Aroma Type', 'parfume-reviews'); ?></h1>
        <div class="archive-description">
            <p><?php _e('Discover fragrances organized by their dominant aroma characteristics. From fresh and citrusy to woody and oriental.', 'parfume-reviews'); ?></p>
        </div>
    </div>

    <div class="archive-content">
        <div class="archive-main">
            <?php
            // Get all aroma types ordered alphabetically
            $all_aroma_types = get_terms(array(
                'taxonomy' => 'aroma_type',
                'hide_empty' => false,
                'orderby' => 'name',
                'order' => 'ASC',
            ));

            // Aroma type icons and categories
            $aroma_categories = array(
                'Цитрусови' => array(
                    'icon' => '🍋',
                    'color' => '#ffc107',
                    'keywords' => array('цитрус', 'лимон', 'портокал', 'citrus', 'fresh'),
                    'types' => array()
                ),
                'Флорални' => array(
                    'icon' => '🌸',
                    'color' => '#e91e63',
                    'keywords' => array('флорал', 'цветен', 'роза', 'жасмин', 'floral'),
                    'types' => array()
                ),
                'Дървесни' => array(
                    'icon' => '🌳',
                    'color' => '#795548',
                    'keywords' => array('дървесен', 'дърво', 'кедър', 'woody', 'wood'),
                    'types' => array()
                ),
                'Ориенталски' => array(
                    'icon' => '🔥',
                    'color' => '#ff5722',
                    'keywords' => array('ориентал', 'сладък', 'ванилия', 'oriental', 'spicy'),
                    'types' => array()
                ),
                'Свежи' => array(
                    'icon' => '💧',
                    'color' => '#03a9f4',
                    'keywords' => array('свеж', 'водн', 'морск', 'fresh', 'aquatic'),
                    'types' => array()
                ),
                'Други' => array(
                    'icon' => '✨',
                    'color' => '#9c27b0',
                    'keywords' => array(),
                    'types' => array()
                )
            );

            // Categorize aroma types
            foreach ($all_aroma_types as $aroma_type) {
                $categorized = false;
                foreach ($aroma_categories as $category_name => &$category) {
                    if ($category_name === 'Други') continue;
                    
                    foreach ($category['keywords'] as $keyword) {
                        if (stripos($aroma_type->name, $keyword) !== false) {
                            $category['types'][] = $aroma_type;
                            $categorized = true;
                            break 2;
                        }
                    }
                }
                
                if (!$categorized) {
                    $aroma_categories['Други']['types'][] = $aroma_type;
                }
            }

            // Group by first letter for alphabetical navigation
            $types_by_letter = array();
            $alphabet_nav = array();
            
            foreach ($all_aroma_types as $type) {
                $first_letter = mb_strtoupper(mb_substr($type->name, 0, 1));
                if (!isset($types_by_letter[$first_letter])) {
                    $types_by_letter[$first_letter] = array();
                    $alphabet_nav[] = $first_letter;
                }
                $types_by_letter[$first_letter][] = $type;
            }
            
            sort($alphabet_nav);
            ?>

            <?php if (!empty($all_aroma_types)): ?>
                <!-- Category Overview -->
                <div class="category-overview">
                    <h2><?php _e('Aroma Categories', 'parfume-reviews'); ?></h2>
                    <div class="categories-grid">
                        <?php foreach ($aroma_categories as $category_name => $category): ?>
                            <?php if (!empty($category['types'])): ?>
                                <div class="category-card" style="border-left: 4px solid <?php echo $category['color']; ?>">
                                    <div class="category-header">
                                        <span class="category-icon"><?php echo $category['icon']; ?></span>
                                        <h3 class="category-name"><?php echo esc_html($category_name); ?></h3>
                                        <span class="category-count"><?php echo count($category['types']); ?> <?php _e('types', 'parfume-reviews'); ?></span>
                                    </div>
                                    <div class="category-types">
                                        <?php foreach (array_slice($category['types'], 0, 4) as $type): ?>
                                            <a href="<?php echo get_term_link($type); ?>" class="type-tag">
                                                <?php echo esc_html($type->name); ?>
                                                <span class="type-count">(<?php echo $type->count; ?>)</span>
                                            </a>
                                        <?php endforeach; ?>
                                        <?php if (count($category['types']) > 4): ?>
                                            <span class="more-types">+<?php echo count($category['types']) - 4; ?> <?php _e('more', 'parfume-reviews'); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Alphabetical Navigation -->
                <div class="alphabet-navigation">
                    <h3><?php _e('Browse Alphabetically:', 'parfume-reviews'); ?></h3>
                    <div class="alphabet-links">
                        <?php foreach ($alphabet_nav as $letter): ?>
                            <a href="#letter-<?php echo esc_attr($letter); ?>" class="letter-link">
                                <?php echo esc_html($letter); ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Aroma Types by Letter -->
                <div class="types-by-letter">
                    <?php foreach ($alphabet_nav as $letter): ?>
                        <div class="letter-section" id="letter-<?php echo esc_attr($letter); ?>">
                            <h2 class="letter-heading"><?php echo esc_html($letter); ?></h2>
                            <div class="aroma-types-grid">
                                <?php foreach ($types_by_letter[$letter] as $aroma_type): ?>
                                    <?php
                                    // Get aroma type statistics
                                    $type_perfumes = get_posts(array(
                                        'post_type' => 'parfume',
                                        'posts_per_page' => -1,
                                        'tax_query' => array(
                                            array(
                                                'taxonomy' => 'aroma_type',
                                                'field' => 'term_id',
                                                'terms' => $aroma_type->term_id,
                                            ),
                                        ),
                                        'fields' => 'ids',
                                    ));
                                    
                                    $perfume_count = count($type_perfumes);
                                    
                                    // Calculate average rating
                                    $total_rating = 0;
                                    $rated_count = 0;
                                    foreach ($type_perfumes as $perfume_id) {
                                        $rating = get_post_meta($perfume_id, '_parfume_rating', true);
                                        if ($rating && is_numeric($rating)) {
                                            $total_rating += floatval($rating);
                                            $rated_count++;
                                        }
                                    }
                                    $average_rating = $rated_count > 0 ? round($total_rating / $rated_count, 1) : 0;
                                    
                                    // Get most popular brand for this type
                                    $type_brands = array();
                                    foreach ($type_perfumes as $perfume_id) {
                                        $brands = wp_get_post_terms($perfume_id, 'marki', array('fields' => 'names'));
                                        if (!empty($brands) && !is_wp_error($brands)) {
                                            foreach ($brands as $brand) {
                                                if (!isset($type_brands[$brand])) {
                                                    $type_brands[$brand] = 0;
                                                }
                                                $type_brands[$brand]++;
                                            }
                                        }
                                    }
                                    
                                    arsort($type_brands);
                                    $top_brand = !empty($type_brands) ? array_key_first($type_brands) : '';
                                    
                                    // Determine category for styling
                                    $type_category = 'Други';
                                    foreach ($aroma_categories as $cat_name => $cat_data) {
                                        if (in_array($aroma_type, $cat_data['types'])) {
                                            $type_category = $cat_name;
                                            break;
                                        }
                                    }
                                    
                                    // Get aroma type image
                                    $type_image_id = get_term_meta($aroma_type->term_id, 'aroma_type-image-id', true);
                                    $type_image = $type_image_id ? wp_get_attachment_image_url($type_image_id, 'medium') : '';
                                    ?>
                                    
                                    <div class="aroma-type-card" data-category="<?php echo esc_attr($type_category); ?>">
                                        <div class="type-header">
                                            <?php if ($type_image): ?>
                                                <div class="type-image">
                                                    <a href="<?php echo get_term_link($aroma_type); ?>">
                                                        <img src="<?php echo esc_url($type_image); ?>" alt="<?php echo esc_attr($aroma_type->name); ?>">
                                                    </a>
                                                </div>
                                            <?php else: ?>
                                                <div class="type-icon">
                                                    <span class="icon" style="color: <?php echo $aroma_categories[$type_category]['color']; ?>">
                                                        <?php echo $aroma_categories[$type_category]['icon']; ?>
                                                    </span>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <h3 class="type-name">
                                                <a href="<?php echo get_term_link($aroma_type); ?>"><?php echo esc_html($aroma_type->name); ?></a>
                                            </h3>
                                            
                                            <div class="type-category-badge" style="background: <?php echo $aroma_categories[$type_category]['color']; ?>">
                                                <?php echo esc_html($type_category); ?>
                                            </div>
                                        </div>
                                        
                                        <?php if ($aroma_type->description): ?>
                                            <div class="type-description">
                                                <?php echo wp_trim_words(esc_html($aroma_type->description), 15, '...'); ?>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <div class="type-stats">
                                            <div class="stat-item">
                                                <span class="stat-number"><?php echo $perfume_count; ?></span>
                                                <span class="stat-label"><?php _e('Perfumes', 'parfume-reviews'); ?></span>
                                            </div>
                                            
                                            <?php if ($average_rating > 0): ?>
                                                <div class="stat-item">
                                                    <span class="stat-number"><?php echo $average_rating; ?></span>
                                                    <span class="stat-label"><?php _e('Avg Rating', 'parfume-reviews'); ?></span>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <?php if ($top_brand): ?>
                                            <div class="top-brand">
                                                <span class="brand-label"><?php _e('Popular Brand:', 'parfume-reviews'); ?></span>
                                                <span class="brand-name"><?php echo esc_html($top_brand); ?></span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
            <?php else: ?>
                <div class="no-aroma-types">
                    <p><?php _e('No aroma types found.', 'parfume-reviews'); ?></p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.aroma-type-archive .category-overview {
    margin-bottom: 3rem;
}

.aroma-type-archive .categories-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.aroma-type-archive .category-card {
    background: white;
    border-radius: 8px;
    padding: 1.5rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.aroma-type-archive .category-header {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 1rem;
}

.aroma-type-archive .category-icon {
    font-size: 1.5rem;
}

.aroma-type-archive .category-name {
    flex: 1;
    margin: 0;
    font-size: 1.25rem;
    color: #2c3e50;
}

.aroma-type-archive .category-count {
    font-size: 0.875rem;
    color: #6c757d;
    background: #f8f9fa;
    padding: 0.25rem 0.5rem;
    border-radius: 12px;
}

.aroma-type-archive .category-types {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.aroma-type-archive .type-tag {
    background: #f8f9fa;
    padding: 0.4rem 0.8rem;
    border-radius: 16px;
    text-decoration: none;
    color: #495057;
    font-size: 0.875rem;
    transition: all 0.2s ease;
}

.aroma-type-archive .type-tag:hover {
    background: #007cba;
    color: white;
}

.aroma-type-archive .type-count {
    opacity: 0.7;
    margin-left: 0.25rem;
}

.aroma-type-archive .more-types {
    color: #6c757d;
    font-size: 0.875rem;
    font-style: italic;
}

.aroma-type-archive .alphabet-navigation {
    margin-bottom: 2rem;
    padding: 1rem;
    background: #f8f9fa;
    border-radius: 8px;
}

.aroma-type-archive .alphabet-links {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.aroma-type-archive .letter-link {
    display: inline-block;
    padding: 0.5rem 0.75rem;
    background: white;
    border: 1px solid #dee2e6;
    border-radius: 4px;
    text-decoration: none;
    color: #495057;
    font-weight: 500;
    transition: all 0.2s ease;
}

.aroma-type-archive .letter-link:hover {
    background: #007cba;
    color: white;
    border-color: #007cba;
}

.aroma-type-archive .letter-section {
    margin-bottom: 3rem;
}

.aroma-type-archive .letter-heading {
    font-size: 2rem;
    color: #2c3e50;
    border-bottom: 2px solid #007cba;
    padding-bottom: 0.5rem;
    margin-bottom: 1.5rem;
}

.aroma-type-archive .aroma-types-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 1.5rem;
}

.aroma-type-archive .aroma-type-card {
    background: white;
    border-radius: 8px;
    padding: 1.5rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    position: relative;
}

.aroma-type-archive .aroma-type-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
}

.aroma-type-archive .type-header {
    text-align: center;
    margin-bottom: 1rem;
}

.aroma-type-archive .type-image img {
    width: 60px;
    height: 60px;
    object-fit: cover;
    border-radius: 50%;
    margin-bottom: 0.5rem;
}

.aroma-type-archive .type-icon .icon {
    font-size: 2.5rem;
    display: block;
    margin-bottom: 0.5rem;
}

.aroma-type-archive .type-name a {
    color: #2c3e50;
    text-decoration: none;
    font-size: 1.1rem;
    font-weight: 600;
}

.aroma-type-archive .type-name a:hover {
    color: #007cba;
}

.aroma-type-archive .type-category-badge {
    position: absolute;
    top: 0.5rem;
    right: 0.5rem;
    color: white;
    padding: 0.25rem 0.5rem;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 500;
}

.aroma-type-archive .type-description {
    color: #6c757d;
    margin: 1rem 0;
    text-align: center;
    font-size: 0.9rem;
    line-height: 1.4;
}

.aroma-type-archive .type-stats {
    display: flex;
    justify-content: center;
    gap: 1.5rem;
    margin: 1rem 0;
}

.aroma-type-archive .stat-item {
    text-align: center;
}

.aroma-type-archive .stat-number {
    display: block;
    font-size: 1.5rem;
    font-weight: 700;
    color: #007cba;
}

.aroma-type-archive .stat-label {
    display: block;
    font-size: 0.75rem;
    color: #6c757d;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.aroma-type-archive .top-brand {
    text-align: center;
    padding-top: 1rem;
    border-top: 1px solid #f1f3f4;
}

.aroma-type-archive .brand-label {
    font-size: 0.875rem;
    color: #6c757d;
}

.aroma-type-archive .brand-name {
    font-weight: 500;
    color: #495057;
    margin-left: 0.5rem;
}

/* Responsive */
@media (max-width: 768px) {
    .aroma-type-archive .categories-grid,
    .aroma-type-archive .aroma-types-grid {
        grid-template-columns: 1fr;
    }
    
    .aroma-type-archive .alphabet-links {
        justify-content: center;
    }
    
    .aroma-type-archive .type-stats {
        gap: 1rem;
    }
}
</style>

<?php get_footer(); ?>