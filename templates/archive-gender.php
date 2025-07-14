<?php
/**
 * Template for All Genders archive page (/parfiumi/gender/)
 * 📁 Файл: templates/archive-gender.php
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header(); 
?>

<div class="parfume-archive gender-archive">
    <div class="archive-header">
        <h1 class="archive-title"><?php _e('Perfumes by Gender', 'parfume-reviews'); ?></h1>
        <div class="archive-description">
            <p><?php _e('Explore fragrances categorized by gender. Find perfumes designed for men, women, or unisex scents that work for everyone.', 'parfume-reviews'); ?></p>
        </div>
    </div>

    <div class="archive-content">
        <div class="archive-main">
            <?php
            // Get all genders ordered by count (most popular first)
            $all_genders = get_terms(array(
                'taxonomy' => 'gender',
                'hide_empty' => false,
                'orderby' => 'count',
                'order' => 'DESC',
            ));

            // Gender icons mapping
            $gender_icons = array(
                'Мъжки' => '👨',
                'Мъжки/Мъже' => '👨',
                'За мъже' => '👨',
                'Male' => '👨',
                'Men' => '👨',
                'Мъжки парфюм' => '👨',
                'Женски' => '👩',
                'Женски/Жени' => '👩',
                'За жени' => '👩',
                'Female' => '👩',
                'Women' => '👩',
                'Женски парфюм' => '👩',
                'Унисекс' => '👫',
                'Unisex' => '👫',
                'За всички' => '👫',
                'Общ' => '👫',
            );

            // Function to get gender icon
            function get_gender_icon($gender_name, $icons) {
                foreach ($icons as $key => $icon) {
                    if (stripos($gender_name, $key) !== false || stripos($key, $gender_name) !== false) {
                        return $icon;
                    }
                }
                return '👤'; // Default icon
            }
            ?>

            <?php if (!empty($all_genders)): ?>
                <div class="genders-grid">
                    <?php foreach ($all_genders as $gender): ?>
                        <?php
                        // Get gender statistics
                        $gender_perfumes = get_posts(array(
                            'post_type' => 'parfume',
                            'posts_per_page' => -1,
                            'tax_query' => array(
                                array(
                                    'taxonomy' => 'gender',
                                    'field' => 'term_id',
                                    'terms' => $gender->term_id,
                                ),
                            ),
                            'fields' => 'ids',
                        ));
                        
                        $perfume_count = count($gender_perfumes);
                        
                        // Calculate average rating
                        $total_rating = 0;
                        $rated_count = 0;
                        foreach ($gender_perfumes as $perfume_id) {
                            $rating = get_post_meta($perfume_id, '_parfume_rating', true);
                            if ($rating && is_numeric($rating)) {
                                $total_rating += floatval($rating);
                                $rated_count++;
                            }
                        }
                        $average_rating = $rated_count > 0 ? round($total_rating / $rated_count, 1) : 0;
                        
                        // Get most popular brands for this gender
                        $gender_brands = array();
                        foreach ($gender_perfumes as $perfume_id) {
                            $brands = wp_get_post_terms($perfume_id, 'marki', array('fields' => 'names'));
                            if (!empty($brands) && !is_wp_error($brands)) {
                                foreach ($brands as $brand) {
                                    if (!isset($gender_brands[$brand])) {
                                        $gender_brands[$brand] = 0;
                                    }
                                    $gender_brands[$brand]++;
                                }
                            }
                        }
                        
                        arsort($gender_brands);
                        $top_brands = array_slice(array_keys($gender_brands), 0, 3);
                        
                        // Get most popular aroma types
                        $gender_aroma_types = array();
                        foreach ($gender_perfumes as $perfume_id) {
                            $aroma_types = wp_get_post_terms($perfume_id, 'aroma_type', array('fields' => 'names'));
                            if (!empty($aroma_types) && !is_wp_error($aroma_types)) {
                                foreach ($aroma_types as $type) {
                                    if (!isset($gender_aroma_types[$type])) {
                                        $gender_aroma_types[$type] = 0;
                                    }
                                    $gender_aroma_types[$type]++;
                                }
                            }
                        }
                        
                        arsort($gender_aroma_types);
                        $top_aroma_type = !empty($gender_aroma_types) ? array_key_first($gender_aroma_types) : '';
                        
                        // Get gender image
                        $gender_image_id = get_term_meta($gender->term_id, 'gender-image-id', true);
                        $gender_image = $gender_image_id ? wp_get_attachment_image_url($gender_image_id, 'medium') : '';
                        
                        $gender_icon = get_gender_icon($gender->name, $gender_icons);
                        ?>
                        
                        <div class="gender-card">
                            <div class="gender-header">
                                <?php if ($gender_image): ?>
                                    <div class="gender-image">
                                        <a href="<?php echo get_term_link($gender); ?>">
                                            <img src="<?php echo esc_url($gender_image); ?>" alt="<?php echo esc_attr($gender->name); ?>">
                                        </a>
                                    </div>
                                <?php else: ?>
                                    <div class="gender-icon">
                                        <span class="icon"><?php echo $gender_icon; ?></span>
                                    </div>
                                <?php endif; ?>
                                
                                <h2 class="gender-name">
                                    <a href="<?php echo get_term_link($gender); ?>"><?php echo esc_html($gender->name); ?></a>
                                </h2>
                            </div>
                            
                            <?php if ($gender->description): ?>
                                <div class="gender-description">
                                    <?php echo wp_trim_words(esc_html($gender->description), 25, '...'); ?>
                                </div>
                            <?php endif; ?>
                            
                            <div class="gender-stats">
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
                                
                                <?php if ($top_aroma_type): ?>
                                    <div class="popular-type">
                                        <span class="section-label"><?php _e('Most Popular Type:', 'parfume-reviews'); ?></span>
                                        <span class="aroma-type"><?php echo esc_html($top_aroma_type); ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="gender-actions">
                                <a href="<?php echo get_term_link($gender); ?>" class="view-all-btn">
                                    <?php printf(__('View All %s Perfumes', 'parfume-reviews'), esc_html($gender->name)); ?>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Alphabetical Quick Access -->
                <?php
                // Create alphabetical navigation for all genders
                $all_genders_alpha = get_terms(array(
                    'taxonomy' => 'gender',
                    'hide_empty' => false,
                    'orderby' => 'name',
                    'order' => 'ASC',
                ));
                ?>
                
                <?php if (count($all_genders_alpha) > 6): ?>
                    <div class="alphabetical-section">
                        <h3><?php _e('Quick Access - All Genders Alphabetically', 'parfume-reviews'); ?></h3>
                        <div class="alpha-genders-list">
                            <?php foreach ($all_genders_alpha as $gender): ?>
                                <div class="alpha-gender-item">
                                    <a href="<?php echo get_term_link($gender); ?>" class="alpha-gender-link">
                                        <span class="alpha-icon"><?php echo get_gender_icon($gender->name, $gender_icons); ?></span>
                                        <span class="alpha-name"><?php echo esc_html($gender->name); ?></span>
                                        <span class="alpha-count">(<?php echo $gender->count; ?>)</span>
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
                
            <?php else: ?>
                <div class="no-genders">
                    <p><?php _e('No gender categories found.', 'parfume-reviews'); ?></p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.gender-archive .genders-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
    gap: 2rem;
    margin-bottom: 3rem;
}

.gender-archive .gender-card {
    background: white;
    border-radius: 12px;
    padding: 2rem;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
    border: 1px solid #e9ecef;
}

.gender-archive .gender-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 15px rgba(0,0,0,0.15);
}

.gender-archive .gender-header {
    display: flex;
    align-items: center;
    margin-bottom: 1.5rem;
    gap: 1rem;
}

.gender-archive .gender-image img {
    width: 60px;
    height: 60px;
    object-fit: cover;
    border-radius: 50%;
}

.gender-archive .gender-icon {
    width: 60px;
    height: 60px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 50%;
}

.gender-archive .gender-icon .icon {
    font-size: 24px;
}

.gender-archive .gender-name a {
    color: #2c3e50;
    text-decoration: none;
    font-size: 1.5rem;
    font-weight: 600;
}

.gender-archive .gender-name a:hover {
    color: #007cba;
}

.gender-archive .gender-description {
    color: #6c757d;
    line-height: 1.6;
    margin-bottom: 1.5rem;
}

.gender-archive .stat-row {
    display: flex;
    gap: 2rem;
    margin-bottom: 1rem;
}

.gender-archive .stat-item {
    text-align: center;
}

.gender-archive .stat-number {
    display: block;
    font-size: 2rem;
    font-weight: 700;
    color: #007cba;
}

.gender-archive .stat-label {
    display: block;
    font-size: 0.875rem;
    color: #6c757d;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.gender-archive .popular-brands,
.gender-archive .popular-type {
    margin: 0.75rem 0;
    padding: 0.5rem 0;
    border-top: 1px solid #f1f3f4;
}

.gender-archive .section-label {
    font-weight: 600;
    color: #495057;
    margin-right: 0.5rem;
}

.gender-archive .brands-list,
.gender-archive .aroma-type {
    color: #6c757d;
    font-style: italic;
}

.gender-archive .gender-actions {
    margin-top: 1.5rem;
    text-align: center;
}

.gender-archive .view-all-btn {
    display: inline-block;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 0.75rem 1.5rem;
    border-radius: 25px;
    text-decoration: none;
    font-weight: 500;
    transition: transform 0.2s ease;
}

.gender-archive .view-all-btn:hover {
    transform: scale(1.05);
    color: white;
}

.gender-archive .alphabetical-section {
    background: #f8f9fa;
    padding: 2rem;
    border-radius: 8px;
    margin-top: 3rem;
}

.gender-archive .alphabetical-section h3 {
    color: #2c3e50;
    margin-bottom: 1.5rem;
    text-align: center;
}

.gender-archive .alpha-genders-list {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
}

.gender-archive .alpha-gender-link {
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

.gender-archive .alpha-gender-link:hover {
    background: #007cba;
    color: white;
    transform: translateX(4px);
}

.gender-archive .alpha-icon {
    font-size: 1.2rem;
}

.gender-archive .alpha-name {
    flex: 1;
    font-weight: 500;
}

.gender-archive .alpha-count {
    font-size: 0.875rem;
    opacity: 0.7;
}

/* Responsive */
@media (max-width: 768px) {
    .gender-archive .genders-grid {
        grid-template-columns: 1fr;
    }
    
    .gender-archive .stat-row {
        justify-content: center;
    }
    
    .gender-archive .alpha-genders-list {
        grid-template-columns: 1fr;
    }
}
</style>

<?php get_footer(); ?>