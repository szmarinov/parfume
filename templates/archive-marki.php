<?php
/**
 * Template for All Brands archive page (/parfiumi/marki/)
 * 📁 Файл: templates/archive-marki.php
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header(); 
?>

<div class="parfume-archive brands-archive">
    <div class="archive-header">
        <h1 class="archive-title"><?php _e('All Perfume Brands', 'parfume-reviews'); ?></h1>
        <div class="archive-description">
            <p><?php _e('Explore perfumes by brands. Discover fragrances from your favorite perfume houses and niche creators.', 'parfume-reviews'); ?></p>
        </div>
    </div>

    <div class="archive-content">
        <div class="archive-main">
            <?php
            // Get all brands ordered alphabetically
            $all_brands = get_terms(array(
                'taxonomy' => 'marki',
                'hide_empty' => false,
                'orderby' => 'name',
                'order' => 'ASC',
            ));

            // Group brands by first letter for alphabetical navigation
            $brands_by_letter = array();
            $alphabet_nav = array();
            
            foreach ($all_brands as $brand) {
                $first_letter = mb_strtoupper(mb_substr($brand->name, 0, 1));
                if (!isset($brands_by_letter[$first_letter])) {
                    $brands_by_letter[$first_letter] = array();
                    $alphabet_nav[] = $first_letter;
                }
                $brands_by_letter[$first_letter][] = $brand;
            }
            
            sort($alphabet_nav);
            ?>

            <?php if (!empty($alphabet_nav)): ?>
                <!-- Alphabetical Navigation -->
                <div class="alphabet-navigation">
                    <h3><?php _e('Jump to Letter:', 'parfume-reviews'); ?></h3>
                    <div class="alphabet-links">
                        <?php foreach ($alphabet_nav as $letter): ?>
                            <a href="#letter-<?php echo esc_attr($letter); ?>" class="letter-link">
                                <?php echo esc_html($letter); ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Brands List by Letter -->
                <div class="brands-by-letter">
                    <?php foreach ($alphabet_nav as $letter): ?>
                        <div class="letter-section" id="letter-<?php echo esc_attr($letter); ?>">
                            <h2 class="letter-heading"><?php echo esc_html($letter); ?></h2>
                            <div class="brands-grid">
                                <?php foreach ($brands_by_letter[$letter] as $brand): ?>
                                    <?php
                                    // Get brand statistics
                                    $brand_perfumes = get_posts(array(
                                        'post_type' => 'parfume',
                                        'posts_per_page' => -1,
                                        'tax_query' => array(
                                            array(
                                                'taxonomy' => 'marki',
                                                'field' => 'term_id',
                                                'terms' => $brand->term_id,
                                            ),
                                        ),
                                        'fields' => 'ids',
                                    ));
                                    
                                    $perfume_count = count($brand_perfumes);
                                    
                                    // Calculate average rating
                                    $total_rating = 0;
                                    $rated_count = 0;
                                    foreach ($brand_perfumes as $perfume_id) {
                                        $rating = get_post_meta($perfume_id, '_parfume_rating', true);
                                        if ($rating && is_numeric($rating)) {
                                            $total_rating += floatval($rating);
                                            $rated_count++;
                                        }
                                    }
                                    $average_rating = $rated_count > 0 ? round($total_rating / $rated_count, 1) : 0;
                                    
                                    // Get brand image
                                    $brand_image_id = get_term_meta($brand->term_id, 'marki-image-id', true);
                                    $brand_image = $brand_image_id ? wp_get_attachment_image_url($brand_image_id, 'medium') : '';
                                    ?>
                                    
                                    <div class="brand-card">
                                        <?php if ($brand_image): ?>
                                            <div class="brand-image">
                                                <a href="<?php echo get_term_link($brand); ?>">
                                                    <img src="<?php echo esc_url($brand_image); ?>" alt="<?php echo esc_attr($brand->name); ?>">
                                                </a>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <div class="brand-content">
                                            <h3 class="brand-name">
                                                <a href="<?php echo get_term_link($brand); ?>"><?php echo esc_html($brand->name); ?></a>
                                            </h3>
                                            
                                            <?php if ($brand->description): ?>
                                                <div class="brand-description">
                                                    <?php echo wp_trim_words(esc_html($brand->description), 20, '...'); ?>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <div class="brand-stats">
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
                                            
                                            <?php
                                            // Get most popular note for this brand
                                            $brand_notes = array();
                                            foreach ($brand_perfumes as $perfume_id) {
                                                $notes = wp_get_post_terms($perfume_id, 'notes', array('fields' => 'names'));
                                                if (!empty($notes) && !is_wp_error($notes)) {
                                                    foreach ($notes as $note) {
                                                        if (!isset($brand_notes[$note])) {
                                                            $brand_notes[$note] = 0;
                                                        }
                                                        $brand_notes[$note]++;
                                                    }
                                                }
                                            }
                                            
                                            if (!empty($brand_notes)) {
                                                arsort($brand_notes);
                                                $top_note = array_key_first($brand_notes);
                                                ?>
                                                <div class="brand-signature">
                                                    <span class="signature-label"><?php _e('Signature Note:', 'parfume-reviews'); ?></span>
                                                    <span class="signature-note"><?php echo esc_html($top_note); ?></span>
                                                </div>
                                            <?php } ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="no-brands">
                    <p><?php _e('No brands found.', 'parfume-reviews'); ?></p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.brands-archive .alphabet-navigation {
    margin-bottom: 2rem;
    padding: 1rem;
    background: #f8f9fa;
    border-radius: 8px;
}

.brands-archive .alphabet-links {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.brands-archive .letter-link {
    display: inline-block;
    padding: 0.5rem 0.75rem;
    background: #ffffff;
    border: 1px solid #dee2e6;
    border-radius: 4px;
    text-decoration: none;
    color: #495057;
    font-weight: 500;
    transition: all 0.2s ease;
}

.brands-archive .letter-link:hover {
    background: #007cba;
    color: white;
    border-color: #007cba;
}

.brands-archive .letter-section {
    margin-bottom: 3rem;
}

.brands-archive .letter-heading {
    font-size: 2rem;
    color: #2c3e50;
    border-bottom: 2px solid #007cba;
    padding-bottom: 0.5rem;
    margin-bottom: 1.5rem;
}

.brands-archive .brands-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 1.5rem;
}

.brands-archive .brand-card {
    background: white;
    border-radius: 8px;
    padding: 1.5rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.brands-archive .brand-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
}

.brands-archive .brand-image {
    margin-bottom: 1rem;
}

.brands-archive .brand-image img {
    width: 100%;
    height: 120px;
    object-fit: contain;
    border-radius: 4px;
}

.brands-archive .brand-name a {
    color: #2c3e50;
    text-decoration: none;
    font-size: 1.25rem;
    font-weight: 600;
}

.brands-archive .brand-name a:hover {
    color: #007cba;
}

.brands-archive .brand-description {
    color: #6c757d;
    margin: 0.5rem 0 1rem 0;
    line-height: 1.5;
}

.brands-archive .brand-stats {
    display: flex;
    gap: 1rem;
    margin: 1rem 0;
}

.brands-archive .stat-item {
    text-align: center;
}

.brands-archive .stat-number {
    display: block;
    font-size: 1.5rem;
    font-weight: 700;
    color: #007cba;
}

.brands-archive .stat-label {
    display: block;
    font-size: 0.875rem;
    color: #6c757d;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.brands-archive .brand-signature {
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px solid #e9ecef;
}

.brands-archive .signature-label {
    font-size: 0.875rem;
    color: #6c757d;
}

.brands-archive .signature-note {
    font-weight: 500;
    color: #495057;
}

/* Responsive */
@media (max-width: 768px) {
    .brands-archive .brands-grid {
        grid-template-columns: 1fr;
    }
    
    .brands-archive .alphabet-links {
        justify-content: center;
    }
    
    .brands-archive .letter-link {
        padding: 0.4rem 0.6rem;
        font-size: 0.9rem;
    }
}
</style>

<?php get_footer(); ?>