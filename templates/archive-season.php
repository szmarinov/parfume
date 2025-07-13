<?php
/**
 * Template for All Seasons archive page (/parfiumi/season/)
 * 📁 Файл: templates/archive-season.php
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header(); 
?>

<div class="parfume-archive season-archive">
    <div class="archive-header">
        <h1 class="archive-title"><?php _e('Perfumes by Season', 'parfume-reviews'); ?></h1>
        <div class="archive-description">
            <p><?php _e('Find the perfect fragrance for every season. From light summer scents to warm winter aromas.', 'parfume-reviews'); ?></p>
        </div>
    </div>

    <div class="archive-content">
        <div class="archive-main">
            <?php
            // Get all seasons ordered by a custom order (Spring, Summer, Autumn, Winter)
            $all_seasons = get_terms(array(
                'taxonomy' => 'season',
                'hide_empty' => false,
                'orderby' => 'name',
                'order' => 'ASC',
            ));

            // Define season data with colors, icons and characteristics
            $season_data = array(
                'Пролет' => array(
                    'icon' => '🌸',
                    'color' => '#28a745',
                    'gradient' => 'linear-gradient(135deg, #56ab2f 0%, #a8e6cf 100%)',
                    'description' => 'Fresh, floral, and revitalizing scents',
                    'characteristics' => array('Fresh', 'Floral', 'Green', 'Light'),
                    'aliases' => array('spring', 'пролет')
                ),
                'Лято' => array(
                    'icon' => '☀️',
                    'color' => '#ffc107',
                    'gradient' => 'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)',
                    'description' => 'Light, citrusy, and aquatic fragrances',
                    'characteristics' => array('Citrus', 'Aquatic', 'Light', 'Fresh'),
                    'aliases' => array('summer', 'лято')
                ),
                'Есен' => array(
                    'icon' => '🍂',
                    'color' => '#fd7e14',
                    'gradient' => 'linear-gradient(135deg, #fa709a 0%, #fee140 100%)',
                    'description' => 'Warm, spicy, and woody compositions',
                    'characteristics' => array('Spicy', 'Woody', 'Warm', 'Rich'),
                    'aliases' => array('autumn', 'fall', 'есен')
                ),
                'Зима' => array(
                    'icon' => '❄️',
                    'color' => '#6f42c1',
                    'gradient' => 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
                    'description' => 'Rich, oriental, and enveloping aromas',
                    'characteristics' => array('Oriental', 'Heavy', 'Warm', 'Intense'),
                    'aliases' => array('winter', 'зима')
                )
            );

            // Function to match season
            function match_season($season_name, $season_data) {
                foreach ($season_data as $key => $data) {
                    if (stripos($season_name, $key) !== false) {
                        return $key;
                    }
                    foreach ($data['aliases'] as $alias) {
                        if (stripos($season_name, $alias) !== false) {
                            return $key;
                        }
                    }
                }
                return null;
            }

            // Organize seasons by our predefined data
            $organized_seasons = array();
            $other_seasons = array();

            foreach ($all_seasons as $season) {
                $matched = match_season($season->name, $season_data);
                if ($matched) {
                    $organized_seasons[$matched] = $season;
                } else {
                    $other_seasons[] = $season;
                }
            }
            ?>

            <?php if (!empty($organized_seasons) || !empty($other_seasons)): ?>
                <!-- Main Seasons Grid -->
                <div class="seasons-main-grid">
                    <?php foreach ($season_data as $season_key => $season_info): ?>
                        <?php if (isset($organized_seasons[$season_key])): ?>
                            <?php $season = $organized_seasons[$season_key]; ?>
                            <?php
                            // Get season statistics
                            $season_perfumes = get_posts(array(
                                'post_type' => 'parfume',
                                'posts_per_page' => -1,
                                'tax_query' => array(
                                    array(
                                        'taxonomy' => 'season',
                                        'field' => 'term_id',
                                        'terms' => $season->term_id,
                                    ),
                                ),
                                'fields' => 'ids',
                            ));
                            
                            $perfume_count = count($season_perfumes);
                            
                            // Calculate average rating
                            $total_rating = 0;
                            $rated_count = 0;
                            foreach ($season_perfumes as $perfume_id) {
                                $rating = get_post_meta($perfume_id, '_parfume_rating', true);
                                if ($rating && is_numeric($rating)) {
                                    $total_rating += floatval($rating);
                                    $rated_count++;
                                }
                            }
                            $average_rating = $rated_count > 0 ? round($total_rating / $rated_count, 1) : 0;
                            
                            // Get most popular brands for this season
                            $season_brands = array();
                            foreach ($season_perfumes as $perfume_id) {
                                $brands = wp_get_post_terms($perfume_id, 'marki', array('fields' => 'names'));
                                if (!empty($brands) && !is_wp_error($brands)) {
                                    foreach ($brands as $brand) {
                                        if (!isset($season_brands[$brand])) {
                                            $season_brands[$brand] = 0;
                                        }
                                        $season_brands[$brand]++;
                                    }
                                }
                            }
                            
                            arsort($season_brands);
                            $top_brands = array_slice(array_keys($season_brands), 0, 3);
                            
                            // Get most popular aroma types
                            $season_aroma_types = array();
                            foreach ($season_perfumes as $perfume_id) {
                                $aroma_types = wp_get_post_terms($perfume_id, 'aroma_type', array('fields' => 'names'));
                                if (!empty($aroma_types) && !is_wp_error($aroma_types)) {
                                    foreach ($aroma_types as $type) {
                                        if (!isset($season_aroma_types[$type])) {
                                            $season_aroma_types[$type] = 0;
                                        }
                                        $season_aroma_types[$type]++;
                                    }
                                }
                            }
                            
                            arsort($season_aroma_types);
                            $top_aroma_types = array_slice(array_keys($season_aroma_types), 0, 2);
                            
                            // Get season image
                            $season_image_id = get_term_meta($season->term_id, 'season-image-id', true);
                            $season_image = $season_image_id ? wp_get_attachment_image_url($season_image_id, 'large') : '';
                            ?>
                            
                            <div class="season-card" style="background: <?php echo $season_info['gradient']; ?>">
                                <div class="season-overlay">
                                    <?php if ($season_image): ?>
                                        <div class="season-background" style="background-image: url('<?php echo esc_url($season_image); ?>')"></div>
                                    <?php endif; ?>
                                    
                                    <div class="season-content">
                                        <div class="season-header">
                                            <span class="season-icon"><?php echo $season_info['icon']; ?></span>
                                            <h2 class="season-name">
                                                <a href="<?php echo get_term_link($season); ?>"><?php echo esc_html($season->name); ?></a>
                                            </h2>
                                        </div>
                                        
                                        <div class="season-description">
                                            <?php if ($season->description): ?>
                                                <p><?php echo esc_html($season->description); ?></p>
                                            <?php else: ?>
                                                <p><?php echo esc_html($season_info['description']); ?></p>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div class="season-characteristics">
                                            <?php foreach ($season_info['characteristics'] as $char): ?>
                                                <span class="characteristic-tag"><?php echo esc_html($char); ?></span>
                                            <?php endforeach; ?>
                                        </div>
                                        
                                        <div class="season-stats">
                                            <div class="stat-row">
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
                                            
                                            <?php if (!empty($top_brands)): ?>
                                                <div class="popular-brands">
                                                    <span class="section-label"><?php _e('Popular Brands:', 'parfume-reviews'); ?></span>
                                                    <span class="brands-list"><?php echo implode(', ', array_slice($top_brands, 0, 2)); ?></span>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <?php if (!empty($top_aroma_types)): ?>
                                                <div class="popular-types">
                                                    <span class="section-label"><?php _e('Common Types:', 'parfume-reviews'); ?></span>
                                                    <span class="types-list"><?php echo implode(', ', $top_aroma_types); ?></span>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div class="season-actions">
                                            <a href="<?php echo get_term_link($season); ?>" class="view-season-btn">
                                                <?php printf(__('Explore %s Perfumes', 'parfume-reviews'), esc_html($season->name)); ?>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>

                <!-- Other Seasons (if any) -->
                <?php if (!empty($other_seasons)): ?>
                    <div class="other-seasons-section">
                        <h2><?php _e('Other Seasonal Categories', 'parfume-reviews'); ?></h2>
                        <div class="other-seasons-grid">
                            <?php foreach ($other_seasons as $season): ?>
                                <?php
                                // Get basic stats for other seasons
                                $season_perfumes = get_posts(array(
                                    'post_type' => 'parfume',
                                    'posts_per_page' => -1,
                                    'tax_query' => array(
                                        array(
                                            'taxonomy' => 'season',
                                            'field' => 'term_id',
                                            'terms' => $season->term_id,
                                        ),
                                    ),
                                    'fields' => 'ids',
                                ));
                                
                                $perfume_count = count($season_perfumes);
                                
                                $season_image_id = get_term_meta($season->term_id, 'season-image-id', true);
                                $season_image = $season_image_id ? wp_get_attachment_image_url($season_image_id, 'medium') : '';
                                ?>
                                
                                <div class="other-season-card">
                                    <?php if ($season_image): ?>
                                        <div class="other-season-image">
                                            <a href="<?php echo get_term_link($season); ?>">
                                                <img src="<?php echo esc_url($season_image); ?>" alt="<?php echo esc_attr($season->name); ?>">
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="other-season-content">
                                        <h3 class="other-season-name">
                                            <a href="<?php echo get_term_link($season); ?>"><?php echo esc_html($season->name); ?></a>
                                        </h3>
                                        
                                        <?php if ($season->description): ?>
                                            <div class="other-season-description">
                                                <?php echo wp_trim_words(esc_html($season->description), 15, '...'); ?>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <div class="other-season-stats">
                                            <span class="perfume-count"><?php echo $perfume_count; ?> <?php _e('perfumes', 'parfume-reviews'); ?></span>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Alphabetical Listing of All Seasons -->
                <?php
                $all_seasons_alpha = get_terms(array(
                    'taxonomy' => 'season',
                    'hide_empty' => false,
                    'orderby' => 'name',
                    'order' => 'ASC',
                ));
                ?>
                
                <?php if (count($all_seasons_alpha) > 4): ?>
                    <div class="alphabetical-section">
                        <h3><?php _e('All Seasons Alphabetically', 'parfume-reviews'); ?></h3>
                        <div class="alpha-seasons-list">
                            <?php foreach ($all_seasons_alpha as $season): ?>
                                <div class="alpha-season-item">
                                    <a href="<?php echo get_term_link($season); ?>" class="alpha-season-link">
                                        <?php
                                        $matched_key = match_season($season->name, $season_data);
                                        $icon = $matched_key ? $season_data[$matched_key]['icon'] : '🌟';
                                        ?>
                                        <span class="alpha-icon"><?php echo $icon; ?></span>
                                        <span class="alpha-name"><?php echo esc_html($season->name); ?></span>
                                        <span class="alpha-count">(<?php echo $season->count; ?>)</span>
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
                
            <?php else: ?>
                <div class="no-seasons">
                    <p><?php _e('No seasonal categories found.', 'parfume-reviews'); ?></p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.season-archive .seasons-main-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: 2rem;
    margin-bottom: 3rem;
}

.season-archive .season-card {
    border-radius: 16px;
    overflow: hidden;
    position: relative;
    min-height: 400px;
    box-shadow: 0 8px 20px rgba(0,0,0,0.15);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.season-archive .season-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 12px 30px rgba(0,0,0,0.2);
}

.season-archive .season-overlay {
    position: relative;
    height: 100%;
    color: white;
    z-index: 2;
}

.season-archive .season-background {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-size: cover;
    background-position: center;
    opacity: 0.3;
    z-index: -1;
}

.season-archive .season-content {
    padding: 2rem;
    height: 100%;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}

.season-archive .season-header {
    text-align: center;
    margin-bottom: 1.5rem;
}

.season-archive .season-icon {
    font-size: 3rem;
    display: block;
    margin-bottom: 1rem;
}

.season-archive .season-name a {
    color: white;
    text-decoration: none;
    font-size: 2rem;
    font-weight: 700;
    text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
}

.season-archive .season-name a:hover {
    color: rgba(255,255,255,0.9);
}

.season-archive .season-description {
    text-align: center;
    margin-bottom: 1.5rem;
}

.season-archive .season-description p {
    font-size: 1.1rem;
    line-height: 1.6;
    text-shadow: 1px 1px 2px rgba(0,0,0,0.3);
    margin: 0;
}

.season-archive .season-characteristics {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 0.5rem;
    margin-bottom: 1.5rem;
}

.season-archive .characteristic-tag {
    background: rgba(255,255,255,0.2);
    backdrop-filter: blur(10px);
    padding: 0.4rem 0.8rem;
    border-radius: 20px;
    font-size: 0.875rem;
    font-weight: 500;
    border: 1px solid rgba(255,255,255,0.3);
}

.season-archive .season-stats {
    margin-bottom: 1.5rem;
}

.season-archive .stat-row {
    display: flex;
    justify-content: center;
    gap: 2rem;
    margin-bottom: 1rem;
}

.season-archive .stat-item {
    text-align: center;
}

.season-archive .stat-number {
    display: block;
    font-size: 2rem;
    font-weight: 700;
    text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
}

.season-archive .stat-label {
    display: block;
    font-size: 0.875rem;
    text-transform: uppercase;
    letter-spacing: 1px;
    opacity: 0.9;
}

.season-archive .popular-brands,
.season-archive .popular-types {
    text-align: center;
    margin: 0.75rem 0;
    padding: 0.75rem;
    background: rgba(255,255,255,0.1);
    backdrop-filter: blur(10px);
    border-radius: 8px;
}

.season-archive .section-label {
    font-weight: 600;
    margin-right: 0.5rem;
}

.season-archive .brands-list,
.season-archive .types-list {
    font-style: italic;
    opacity: 0.9;
}

.season-archive .season-actions {
    text-align: center;
}

.season-archive .view-season-btn {
    display: inline-block;
    background: rgba(255,255,255,0.2);
    backdrop-filter: blur(10px);
    color: white;
    padding: 0.875rem 2rem;
    border-radius: 30px;
    text-decoration: none;
    font-weight: 600;
    border: 2px solid rgba(255,255,255,0.3);
    transition: all 0.3s ease;
}

.season-archive .view-season-btn:hover {
    background: rgba(255,255,255,0.3);
    color: white;
    transform: scale(1.05);
}

.season-archive .other-seasons-section {
    margin: 3rem 0;
}

.season-archive .other-seasons-section h2 {
    text-align: center;
    color: #2c3e50;
    margin-bottom: 2rem;
}

.season-archive .other-seasons-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
}

.season-archive .other-season-card {
    background: white;
    border-radius: 8px;
    padding: 1.5rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    transition: transform 0.2s ease;
}

.season-archive .other-season-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
}

.season-archive .other-season-image img {
    width: 100%;
    height: 120px;
    object-fit: cover;
    border-radius: 4px;
    margin-bottom: 1rem;
}

.season-archive .other-season-name a {
    color: #2c3e50;
    text-decoration: none;
    font-size: 1.25rem;
    font-weight: 600;
}

.season-archive .other-season-name a:hover {
    color: #007cba;
}

.season-archive .other-season-description {
    color: #6c757d;
    margin: 1rem 0;
    line-height: 1.5;
}

.season-archive .other-season-stats {
    color: #007cba;
    font-weight: 500;
}

.season-archive .alphabetical-section {
    background: #f8f9fa;
    padding: 2rem;
    border-radius: 8px;
    margin-top: 3rem;
}

.season-archive .alphabetical-section h3 {
    color: #2c3e50;
    margin-bottom: 1.5rem;
    text-align: center;
}

.season-archive .alpha-seasons-list {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
}

.season-archive .alpha-season-link {
    display: flex;
    align-items: center;
    padding: 0.75rem;
    background: white;
    border-radius: 6px;
    text-decoration: none;
    color: #495057;
    transition: all 0.2s ease;
    gap: 0.5rem;
}

.season-archive .alpha-season-link:hover {
    background: #007cba;
    color: white;
    transform: translateX(4px);
}

.season-archive .alpha-icon {
    font-size: 1.2rem;
}

.season-archive .alpha-name {
    flex: 1;
    font-weight: 500;
}

.season-archive .alpha-count {
    font-size: 0.875rem;
    opacity: 0.7;
}

/* Responsive */
@media (max-width: 768px) {
    .season-archive .seasons-main-grid {
        grid-template-columns: 1fr;
        gap: 1.5rem;
    }
    
    .season-archive .season-card {
        min-height: 350px;
    }
    
    .season-archive .season-content {
        padding: 1.5rem;
    }
    
    .season-archive .stat-row {
        gap: 1.5rem;
    }
    
    .season-archive .other-seasons-grid,
    .season-archive .alpha-seasons-list {
        grid-template-columns: 1fr;
    }
}
</style>

<?php get_footer(); ?>