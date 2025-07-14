<?php
/**
 * Template for All Intensity archive page (/parfiumi/intensity/)
 * 📁 Файл: templates/archive-intensity.php
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header(); 
?>

<div class="parfume-archive intensity-archive">
    <div class="archive-header">
        <h1 class="archive-title"><?php _e('Perfumes by Intensity', 'parfume-reviews'); ?></h1>
        <div class="archive-description">
            <p><?php _e('Choose fragrances based on their intensity and projection. From subtle and close-to-skin to bold and room-filling scents.', 'parfume-reviews'); ?></p>
        </div>
    </div>

    <div class="archive-content">
        <div class="archive-main">
            <?php
            // Get all intensities ordered by a logical progression (Light -> Medium -> Strong)
            $all_intensities = get_terms(array(
                'taxonomy' => 'intensity',
                'hide_empty' => false,
                'orderby' => 'name',
                'order' => 'ASC',
            ));

            // Define intensity levels with progression data
            $intensity_levels = array(
                'Леки' => array(
                    'icon' => '🕊️',
                    'level' => 1,
                    'color' => '#28a745',
                    'gradient' => 'linear-gradient(135deg, #a8edea 0%, #fed6e3 100%)',
                    'description' => 'Subtle, close-to-skin fragrances',
                    'characteristics' => array('Intimate', 'Delicate', 'Soft', 'Understated'),
                    'projection' => '1-2 hours',
                    'sillage' => 'Close to skin',
                    'aliases' => array('light', 'weak', 'soft', 'subtle', 'леки', 'слаби')
                ),
                'Средни' => array(
                    'icon' => '🌿',
                    'level' => 2,
                    'color' => '#ffc107',
                    'gradient' => 'linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%)',
                    'description' => 'Balanced projection and longevity',
                    'characteristics' => array('Balanced', 'Versatile', 'Moderate', 'Wearable'),
                    'projection' => '3-5 hours',
                    'sillage' => 'Arm\'s length',
                    'aliases' => array('medium', 'moderate', 'normal', 'средни', 'умерени')
                ),
                'Силни' => array(
                    'icon' => '💪',
                    'level' => 3,
                    'color' => '#dc3545',
                    'gradient' => 'linear-gradient(135deg, #fa709a 0%, #fee140 100%)',
                    'description' => 'Bold, projecting, room-filling scents',
                    'characteristics' => array('Powerful', 'Projecting', 'Long-lasting', 'Statement'),
                    'projection' => '6+ hours',
                    'sillage' => 'Room-filling',
                    'aliases' => array('strong', 'powerful', 'beast mode', 'силни', 'мощни')
                )
            );

            // Function to match intensity level
            function match_intensity($intensity_name, $intensity_levels) {
                foreach ($intensity_levels as $key => $data) {
                    if (stripos($intensity_name, $key) !== false) {
                        return $key;
                    }
                    foreach ($data['aliases'] as $alias) {
                        if (stripos($intensity_name, $alias) !== false) {
                            return $key;
                        }
                    }
                }
                return null;
            }

            // Organize intensities by our predefined levels
            $organized_intensities = array();
            $other_intensities = array();

            foreach ($all_intensities as $intensity) {
                $matched = match_intensity($intensity->name, $intensity_levels);
                if ($matched) {
                    $organized_intensities[$matched] = $intensity;
                } else {
                    $other_intensities[] = $intensity;
                }
            }

            // Sort organized intensities by level
            uksort($organized_intensities, function($a, $b) use ($intensity_levels) {
                return $intensity_levels[$a]['level'] - $intensity_levels[$b]['level'];
            });
            ?>

            <?php if (!empty($organized_intensities) || !empty($other_intensities)): ?>
                <!-- Intensity Comparison Guide -->
                <div class="intensity-guide">
                    <h2><?php _e('Intensity Guide', 'parfume-reviews'); ?></h2>
                    <div class="intensity-spectrum">
                        <?php foreach ($intensity_levels as $level_name => $level_data): ?>
                            <div class="spectrum-point" data-level="<?php echo $level_data['level']; ?>">
                                <div class="point-marker" style="background: <?php echo $level_data['color']; ?>">
                                    <span class="point-icon"><?php echo $level_data['icon']; ?></span>
                                </div>
                                <div class="point-label">
                                    <strong><?php echo esc_html($level_name); ?></strong>
                                    <small><?php echo esc_html($level_data['projection']); ?></small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Main Intensities Grid -->
                <div class="intensities-main-grid">
                    <?php foreach ($organized_intensities as $level_key => $intensity): ?>
                        <?php $level_info = $intensity_levels[$level_key]; ?>
                        <?php
                        // Get intensity statistics
                        $intensity_perfumes = get_posts(array(
                            'post_type' => 'parfume',
                            'posts_per_page' => -1,
                            'tax_query' => array(
                                array(
                                    'taxonomy' => 'intensity',
                                    'field' => 'term_id',
                                    'terms' => $intensity->term_id,
                                ),
                            ),
                            'fields' => 'ids',
                        ));
                        
                        $perfume_count = count($intensity_perfumes);
                        
                        // Calculate average rating
                        $total_rating = 0;
                        $rated_count = 0;
                        foreach ($intensity_perfumes as $perfume_id) {
                            $rating = get_post_meta($perfume_id, '_parfume_rating', true);
                            if ($rating && is_numeric($rating)) {
                                $total_rating += floatval($rating);
                                $rated_count++;
                            }
                        }
                        $average_rating = $rated_count > 0 ? round($total_rating / $rated_count, 1) : 0;
                        
                        // Get most popular brands for this intensity
                        $intensity_brands = array();
                        foreach ($intensity_perfumes as $perfume_id) {
                            $brands = wp_get_post_terms($perfume_id, 'marki', array('fields' => 'names'));
                            if (!empty($brands) && !is_wp_error($brands)) {
                                foreach ($brands as $brand) {
                                    if (!isset($intensity_brands[$brand])) {
                                        $intensity_brands[$brand] = 0;
                                    }
                                    $intensity_brands[$brand]++;
                                }
                            }
                        }
                        
                        arsort($intensity_brands);
                        $top_brands = array_slice(array_keys($intensity_brands), 0, 3);
                        
                        // Get most popular aroma types
                        $intensity_aroma_types = array();
                        foreach ($intensity_perfumes as $perfume_id) {
                            $aroma_types = wp_get_post_terms($perfume_id, 'aroma_type', array('fields' => 'names'));
                            if (!empty($aroma_types) && !is_wp_error($aroma_types)) {
                                foreach ($aroma_types as $type) {
                                    if (!isset($intensity_aroma_types[$type])) {
                                        $intensity_aroma_types[$type] = 0;
                                    }
                                    $intensity_aroma_types[$type]++;
                                }
                            }
                        }
                        
                        arsort($intensity_aroma_types);
                        $top_aroma_types = array_slice(array_keys($intensity_aroma_types), 0, 2);
                        
                        // Get intensity image
                        $intensity_image_id = get_term_meta($intensity->term_id, 'intensity-image-id', true);
                        $intensity_image = $intensity_image_id ? wp_get_attachment_image_url($intensity_image_id, 'large') : '';
                        ?>
                        
                        <div class="intensity-card" data-level="<?php echo $level_info['level']; ?>" style="background: <?php echo $level_info['gradient']; ?>">
                            <div class="intensity-overlay">
                                <?php if ($intensity_image): ?>
                                    <div class="intensity-background" style="background-image: url('<?php echo esc_url($intensity_image); ?>')"></div>
                                <?php endif; ?>
                                
                                <div class="intensity-content">
                                    <div class="intensity-header">
                                        <span class="intensity-icon"><?php echo $level_info['icon']; ?></span>
                                        <div class="intensity-level-indicator">
                                            <?php for ($i = 1; $i <= 3; $i++): ?>
                                                <span class="level-dot <?php echo $i <= $level_info['level'] ? 'active' : ''; ?>"></span>
                                            <?php endfor; ?>
                                        </div>
                                        <h2 class="intensity-name">
                                            <a href="<?php echo get_term_link($intensity); ?>"><?php echo esc_html($intensity->name); ?></a>
                                        </h2>
                                    </div>
                                    
                                    <div class="intensity-description">
                                        <?php if ($intensity->description): ?>
                                            <p><?php echo esc_html($intensity->description); ?></p>
                                        <?php else: ?>
                                            <p><?php echo esc_html($level_info['description']); ?></p>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="intensity-characteristics">
                                        <?php foreach ($level_info['characteristics'] as $char): ?>
                                            <span class="characteristic-tag"><?php echo esc_html($char); ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                    
                                    <div class="intensity-specs">
                                        <div class="spec-item">
                                            <span class="spec-label"><?php _e('Projection:', 'parfume-reviews'); ?></span>
                                            <span class="spec-value"><?php echo esc_html($level_info['projection']); ?></span>
                                        </div>
                                        <div class="spec-item">
                                            <span class="spec-label"><?php _e('Sillage:', 'parfume-reviews'); ?></span>
                                            <span class="spec-value"><?php echo esc_html($level_info['sillage']); ?></span>
                                        </div>
                                    </div>
                                    
                                    <div class="intensity-stats">
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
                                    
                                    <div class="intensity-actions">
                                        <a href="<?php echo get_term_link($intensity); ?>" class="view-intensity-btn">
                                            <?php printf(__('Explore %s Perfumes', 'parfume-reviews'), esc_html($intensity->name)); ?>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Other Intensities (if any) -->
                <?php if (!empty($other_intensities)): ?>
                    <div class="other-intensities-section">
                        <h2><?php _e('Other Intensity Categories', 'parfume-reviews'); ?></h2>
                        <div class="other-intensities-grid">
                            <?php foreach ($other_intensities as $intensity): ?>
                                <?php
                                // Get basic stats for other intensities
                                $intensity_perfumes = get_posts(array(
                                    'post_type' => 'parfume',
                                    'posts_per_page' => -1,
                                    'tax_query' => array(
                                        array(
                                            'taxonomy' => 'intensity',
                                            'field' => 'term_id',
                                            'terms' => $intensity->term_id,
                                        ),
                                    ),
                                    'fields' => 'ids',
                                ));
                                
                                $perfume_count = count($intensity_perfumes);
                                
                                $intensity_image_id = get_term_meta($intensity->term_id, 'intensity-image-id', true);
                                $intensity_image = $intensity_image_id ? wp_get_attachment_image_url($intensity_image_id, 'medium') : '';
                                ?>
                                
                                <div class="other-intensity-card">
                                    <?php if ($intensity_image): ?>
                                        <div class="other-intensity-image">
                                            <a href="<?php echo get_term_link($intensity); ?>">
                                                <img src="<?php echo esc_url($intensity_image); ?>" alt="<?php echo esc_attr($intensity->name); ?>">
                                            </a>
                                        </div>
                                    <?php else: ?>
                                        <div class="other-intensity-icon">
                                            <span>⚡</span>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="other-intensity-content">
                                        <h3 class="other-intensity-name">
                                            <a href="<?php echo get_term_link($intensity); ?>"><?php echo esc_html($intensity->name); ?></a>
                                        </h3>
                                        
                                        <?php if ($intensity->description): ?>
                                            <div class="other-intensity-description">
                                                <?php echo wp_trim_words(esc_html($intensity->description), 15, '...'); ?>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <div class="other-intensity-stats">
                                            <span class="perfume-count"><?php echo $perfume_count; ?> <?php _e('perfumes', 'parfume-reviews'); ?></span>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Alphabetical Listing of All Intensities -->
                <?php
                $all_intensities_alpha = get_terms(array(
                    'taxonomy' => 'intensity',
                    'hide_empty' => false,
                    'orderby' => 'name',
                    'order' => 'ASC',
                ));
                ?>
                
                <?php if (count($all_intensities_alpha) > 3): ?>
                    <div class="alphabetical-section">
                        <h3><?php _e('All Intensities Alphabetically', 'parfume-reviews'); ?></h3>
                        <div class="alpha-intensities-list">
                            <?php foreach ($all_intensities_alpha as $intensity): ?>
                                <div class="alpha-intensity-item">
                                    <a href="<?php echo get_term_link($intensity); ?>" class="alpha-intensity-link">
                                        <?php
                                        $matched_key = match_intensity($intensity->name, $intensity_levels);
                                        $icon = $matched_key ? $intensity_levels[$matched_key]['icon'] : '⚡';
                                        ?>
                                        <span class="alpha-icon"><?php echo $icon; ?></span>
                                        <span class="alpha-name"><?php echo esc_html($intensity->name); ?></span>
                                        <span class="alpha-count">(<?php echo $intensity->count; ?>)</span>
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
                
            <?php else: ?>
                <div class="no-intensities">
                    <p><?php _e('No intensity categories found.', 'parfume-reviews'); ?></p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.intensity-archive .intensity-guide {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 2rem;
    border-radius: 16px;
    margin-bottom: 3rem;
    text-align: center;
}

.intensity-archive .intensity-guide h2 {
    color: white;
    margin-bottom: 2rem;
}

.intensity-archive .intensity-spectrum {
    display: flex;
    justify-content: space-between;
    align-items: center;
    position: relative;
    max-width: 600px;
    margin: 0 auto;
}

.intensity-archive .intensity-spectrum::before {
    content: '';
    position: absolute;
    top: 50%;
    left: 10%;
    right: 10%;
    height: 3px;
    background: linear-gradient(to right, #28a745, #ffc107, #dc3545);
    border-radius: 2px;
    z-index: 1;
}

.intensity-archive .spectrum-point {
    position: relative;
    z-index: 2;
    text-align: center;
}

.intensity-archive .point-marker {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 3px solid white;
    box-shadow: 0 4px 8px rgba(0,0,0,0.2);
    margin: 0 auto 0.5rem;
}

.intensity-archive .point-icon {
    font-size: 1.5rem;
}

.intensity-archive .point-label strong {
    display: block;
    font-size: 1rem;
}

.intensity-archive .point-label small {
    opacity: 0.8;
    font-size: 0.8rem;
}

.intensity-archive .intensities-main-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
    gap: 2rem;
    margin-bottom: 3rem;
}

.intensity-archive .intensity-card {
    border-radius: 16px;
    overflow: hidden;
    position: relative;
    min-height: 450px;
    box-shadow: 0 8px 20px rgba(0,0,0,0.15);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.intensity-archive .intensity-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 12px 30px rgba(0,0,0,0.2);
}

.intensity-archive .intensity-overlay {
    position: relative;
    height: 100%;
    color: white;
    z-index: 2;
}

.intensity-archive .intensity-background {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-size: cover;
    background-position: center;
    opacity: 0.2;
    z-index: -1;
}

.intensity-archive .intensity-content {
    padding: 2rem;
    height: 100%;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}

.intensity-archive .intensity-header {
    text-align: center;
    margin-bottom: 1.5rem;
}

.intensity-archive .intensity-icon {
    font-size: 3rem;
    display: block;
    margin-bottom: 1rem;
}

.intensity-archive .intensity-level-indicator {
    display: flex;
    justify-content: center;
    gap: 0.5rem;
    margin-bottom: 1rem;
}

.intensity-archive .level-dot {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background: rgba(255,255,255,0.3);
    transition: all 0.2s ease;
}

.intensity-archive .level-dot.active {
    background: white;
    box-shadow: 0 0 8px rgba(255,255,255,0.8);
}

.intensity-archive .intensity-name a {
    color: white;
    text-decoration: none;
    font-size: 2rem;
    font-weight: 700;
    text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
}

.intensity-archive .intensity-name a:hover {
    color: rgba(255,255,255,0.9);
}

.intensity-archive .intensity-description {
    text-align: center;
    margin-bottom: 1.5rem;
}

.intensity-archive .intensity-description p {
    font-size: 1.1rem;
    line-height: 1.6;
    text-shadow: 1px 1px 2px rgba(0,0,0,0.3);
    margin: 0;
}

.intensity-archive .intensity-characteristics {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 0.5rem;
    margin-bottom: 1.5rem;
}

.intensity-archive .characteristic-tag {
    background: rgba(255,255,255,0.2);
    backdrop-filter: blur(10px);
    padding: 0.4rem 0.8rem;
    border-radius: 20px;
    font-size: 0.875rem;
    font-weight: 500;
    border: 1px solid rgba(255,255,255,0.3);
}

.intensity-archive .intensity-specs {
    background: rgba(255,255,255,0.1);
    backdrop-filter: blur(10px);
    padding: 1rem;
    border-radius: 8px;
    margin-bottom: 1.5rem;
}

.intensity-archive .spec-item {
    display: flex;
    justify-content: space-between;
    margin-bottom: 0.5rem;
}

.intensity-archive .spec-item:last-child {
    margin-bottom: 0;
}

.intensity-archive .spec-label {
    font-weight: 600;
}

.intensity-archive .spec-value {
    opacity: 0.9;
}

.intensity-archive .intensity-stats {
    margin-bottom: 1.5rem;
}

.intensity-archive .stat-row {
    display: flex;
    justify-content: center;
    gap: 2rem;
    margin-bottom: 1rem;
}

.intensity-archive .stat-item {
    text-align: center;
}

.intensity-archive .stat-number {
    display: block;
    font-size: 2rem;
    font-weight: 700;
    text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
}

.intensity-archive .stat-label {
    display: block;
    font-size: 0.875rem;
    text-transform: uppercase;
    letter-spacing: 1px;
    opacity: 0.9;
}

.intensity-archive .popular-brands,
.intensity-archive .popular-types {
    text-align: center;
    margin: 0.75rem 0;
    padding: 0.75rem;
    background: rgba(255,255,255,0.1);
    backdrop-filter: blur(10px);
    border-radius: 8px;
}

.intensity-archive .section-label {
    font-weight: 600;
    margin-right: 0.5rem;
}

.intensity-archive .brands-list,
.intensity-archive .types-list {
    font-style: italic;
    opacity: 0.9;
}

.intensity-archive .intensity-actions {
    text-align: center;
}

.intensity-archive .view-intensity-btn {
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

.intensity-archive .view-intensity-btn:hover {
    background: rgba(255,255,255,0.3);
    color: white;
    transform: scale(1.05);
}

.intensity-archive .other-intensities-section {
    margin: 3rem 0;
}

.intensity-archive .other-intensities-section h2 {
    text-align: center;
    color: #2c3e50;
    margin-bottom: 2rem;
}

.intensity-archive .other-intensities-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
}

.intensity-archive .other-intensity-card {
    background: white;
    border-radius: 8px;
    padding: 1.5rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    transition: transform 0.2s ease;
    text-align: center;
}

.intensity-archive .other-intensity-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
}

.intensity-archive .other-intensity-image img {
    width: 80px;
    height: 80px;
    object-fit: cover;
    border-radius: 50%;
    margin-bottom: 1rem;
}

.intensity-archive .other-intensity-icon {
    font-size: 3rem;
    margin-bottom: 1rem;
}

.intensity-archive .other-intensity-name a {
    color: #2c3e50;
    text-decoration: none;
    font-size: 1.25rem;
    font-weight: 600;
}

.intensity-archive .other-intensity-name a:hover {
    color: #007cba;
}

.intensity-archive .other-intensity-description {
    color: #6c757d;
    margin: 1rem 0;
    line-height: 1.5;
}

.intensity-archive .other-intensity-stats {
    color: #007cba;
    font-weight: 500;
}

.intensity-archive .alphabetical-section {
    background: #f8f9fa;
    padding: 2rem;
    border-radius: 8px;
    margin-top: 3rem;
}

.intensity-archive .alphabetical-section h3 {
    color: #2c3e50;
    margin-bottom: 1.5rem;
    text-align: center;
}

.intensity-archive .alpha-intensities-list {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
}

.intensity-archive .alpha-intensity-link {
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

.intensity-archive .alpha-intensity-link:hover {
    background: #007cba;
    color: white;
    transform: translateX(4px);
}

.intensity-archive .alpha-icon {
    font-size: 1.2rem;
}

.intensity-archive .alpha-name {
    flex: 1;
    font-weight: 500;
}

.intensity-archive .alpha-count {
    font-size: 0.875rem;
    opacity: 0.7;
}

/* Responsive */
@media (max-width: 768px) {
    .intensity-archive .intensities-main-grid {
        grid-template-columns: 1fr;
        gap: 1.5rem;
    }
    
    .intensity-archive .intensity-card {
        min-height: 400px;
    }
    
    .intensity-archive .intensity-content {
        padding: 1.5rem;
    }
    
    .intensity-archive .intensity-spectrum {
        flex-direction: column;
        gap: 1rem;
    }
    
    .intensity-archive .intensity-spectrum::before {
        display: none;
    }
    
    .intensity-archive .stat-row {
        gap: 1.5rem;
    }
    
    .intensity-archive .other-intensities-grid,
    .intensity-archive .alpha-intensities-list {
        grid-template-columns: 1fr;
    }
}
</style>

<?php get_footer(); ?>