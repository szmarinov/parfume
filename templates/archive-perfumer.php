<?php
/**
 * Template for All Perfumers archive page (/parfiumi/parfumers/)
 * 📁 Файл: templates/archive-perfumer.php
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header(); 
?>

<div class="parfume-archive perfumer-archive">
    <div class="archive-header">
        <h1 class="archive-title"><?php _e('All Perfumers', 'parfume-reviews'); ?></h1>
        <div class="archive-description">
            <p><?php _e('Discover the master perfumers behind your favorite fragrances. Explore their signature styles and creative works.', 'parfume-reviews'); ?></p>
        </div>
    </div>

    <div class="archive-content">
        <div class="archive-main">
            <?php
            // Get all perfumers ordered alphabetically
            $all_perfumers = get_terms(array(
                'taxonomy' => 'perfumer',
                'hide_empty' => false,
                'orderby' => 'name',
                'order' => 'ASC',
            ));

            // Group perfumers by first letter for alphabetical navigation
            $perfumers_by_letter = array();
            $alphabet_nav = array();
            
            foreach ($all_perfumers as $perfumer) {
                $first_letter = mb_strtoupper(mb_substr($perfumer->name, 0, 1));
                if (!isset($perfumers_by_letter[$first_letter])) {
                    $perfumers_by_letter[$first_letter] = array();
                    $alphabet_nav[] = $first_letter;
                }
                $perfumers_by_letter[$first_letter][] = $perfumer;
            }
            
            sort($alphabet_nav);

            // Get featured perfumers (those with the most perfumes)
            $featured_perfumers = get_terms(array(
                'taxonomy' => 'perfumer',
                'hide_empty' => true,
                'orderby' => 'count',
                'order' => 'DESC',
                'number' => 6,
            ));
            ?>

            <?php if (!empty($featured_perfumers)): ?>
                <!-- Featured Perfumers Section -->
                <div class="featured-perfumers-section">
                    <h2><?php _e('Featured Perfumers', 'parfume-reviews'); ?></h2>
                    <div class="featured-perfumers-grid">
                        <?php foreach ($featured_perfumers as $perfumer): ?>
                            <?php
                            // Get perfumer statistics
                            $perfumer_perfumes = get_posts(array(
                                'post_type' => 'parfume',
                                'posts_per_page' => -1,
                                'tax_query' => array(
                                    array(
                                        'taxonomy' => 'perfumer',
                                        'field' => 'term_id',
                                        'terms' => $perfumer->term_id,
                                    ),
                                ),
                                'fields' => 'ids',
                            ));
                            
                            $perfume_count = count($perfumer_perfumes);
                            
                            // Calculate average rating
                            $total_rating = 0;
                            $rated_count = 0;
                            foreach ($perfumer_perfumes as $perfume_id) {
                                $rating = get_post_meta($perfume_id, '_parfume_rating', true);
                                if ($rating && is_numeric($rating)) {
                                    $total_rating += floatval($rating);
                                    $rated_count++;
                                }
                            }
                            $average_rating = $rated_count > 0 ? round($total_rating / $rated_count, 1) : 0;
                            
                            // Get most collaborated brands
                            $perfumer_brands = array();
                            foreach ($perfumer_perfumes as $perfume_id) {
                                $brands = wp_get_post_terms($perfume_id, 'marki', array('fields' => 'names'));
                                if (!empty($brands) && !is_wp_error($brands)) {
                                    foreach ($brands as $brand) {
                                        if (!isset($perfumer_brands[$brand])) {
                                            $perfumer_brands[$brand] = 0;
                                        }
                                        $perfumer_brands[$brand]++;
                                    }
                                }
                            }
                            
                            arsort($perfumer_brands);
                            $top_brands = array_slice(array_keys($perfumer_brands), 0, 3);
                            
                            // Get signature aroma types
                            $perfumer_aroma_types = array();
                            foreach ($perfumer_perfumes as $perfume_id) {
                                $aroma_types = wp_get_post_terms($perfume_id, 'aroma_type', array('fields' => 'names'));
                                if (!empty($aroma_types) && !is_wp_error($aroma_types)) {
                                    foreach ($aroma_types as $type) {
                                        if (!isset($perfumer_aroma_types[$type])) {
                                            $perfumer_aroma_types[$type] = 0;
                                        }
                                        $perfumer_aroma_types[$type]++;
                                    }
                                }
                            }
                            
                            arsort($perfumer_aroma_types);
                            $signature_style = !empty($perfumer_aroma_types) ? array_key_first($perfumer_aroma_types) : '';
                            
                            // Get most used notes
                            $perfumer_notes = array();
                            foreach ($perfumer_perfumes as $perfume_id) {
                                $notes = wp_get_post_terms($perfume_id, 'notes', array('fields' => 'names'));
                                if (!empty($notes) && !is_wp_error($notes)) {
                                    foreach ($notes as $note) {
                                        if (!isset($perfumer_notes[$note])) {
                                            $perfumer_notes[$note] = 0;
                                        }
                                        $perfumer_notes[$note]++;
                                    }
                                }
                            }
                            
                            arsort($perfumer_notes);
                            $signature_notes = array_slice(array_keys($perfumer_notes), 0, 3);
                            
                            // Get perfumer image
                            $perfumer_image_id = get_term_meta($perfumer->term_id, 'perfumer-image-id', true);
                            $perfumer_image = $perfumer_image_id ? wp_get_attachment_image_url($perfumer_image_id, 'medium') : '';
                            ?>
                            
                            <div class="featured-perfumer-card">
                                <div class="perfumer-header">
                                    <?php if ($perfumer_image): ?>
                                        <div class="perfumer-image">
                                            <a href="<?php echo get_term_link($perfumer); ?>">
                                                <img src="<?php echo esc_url($perfumer_image); ?>" alt="<?php echo esc_attr($perfumer->name); ?>">
                                            </a>
                                        </div>
                                    <?php else: ?>
                                        <div class="perfumer-avatar">
                                            <span class="avatar-icon">👨‍🔬</span>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <h3 class="perfumer-name">
                                        <a href="<?php echo get_term_link($perfumer); ?>"><?php echo esc_html($perfumer->name); ?></a>
                                    </h3>
                                </div>
                                
                                <?php if ($perfumer->description): ?>
                                    <div class="perfumer-bio">
                                        <?php echo wp_trim_words(esc_html($perfumer->description), 25, '...'); ?>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="perfumer-stats">
                                    <div class="stat-row">
                                        <div class="stat-item">
                                            <span class="stat-number"><?php echo $perfume_count; ?></span>
                                            <span class="stat-label"><?php _e('Fragrances', 'parfume-reviews'); ?></span>
                                        </div>
                                        
                                        <?php if ($average_rating > 0): ?>
                                            <div class="stat-item">
                                                <span class="stat-number"><?php echo $average_rating; ?></span>
                                                <span class="stat-label"><?php _e('Avg Rating', 'parfume-reviews'); ?></span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <?php if ($signature_style): ?>
                                    <div class="signature-style">
                                        <span class="style-label"><?php _e('Signature Style:', 'parfume-reviews'); ?></span>
                                        <span class="style-name"><?php echo esc_html($signature_style); ?></span>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($top_brands)): ?>
                                    <div class="collaborated-brands">
                                        <span class="brands-label"><?php _e('Key Collaborations:', 'parfume-reviews'); ?></span>
                                        <div class="brands-list">
                                            <?php echo implode(', ', array_slice($top_brands, 0, 2)); ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($signature_notes)): ?>
                                    <div class="signature-notes">
                                        <span class="notes-label"><?php _e('Signature Notes:', 'parfume-reviews'); ?></span>
                                        <div class="notes-tags">
                                            <?php foreach (array_slice($signature_notes, 0, 3) as $note): ?>
                                                <span class="note-tag"><?php echo esc_html($note); ?></span>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="perfumer-actions">
                                    <a href="<?php echo get_term_link($perfumer); ?>" class="view-perfumer-btn">
                                        <?php _e('View Portfolio', 'parfume-reviews'); ?>
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!empty($alphabet_nav)): ?>
                <!-- Alphabetical Navigation -->
                <div class="alphabet-navigation">
                    <h3><?php _e('Browse Perfumers Alphabetically:', 'parfume-reviews'); ?></h3>
                    <div class="alphabet-links">
                        <?php foreach ($alphabet_nav as $letter): ?>
                            <a href="#letter-<?php echo esc_attr($letter); ?>" class="letter-link">
                                <?php echo esc_html($letter); ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Perfumers List by Letter -->
                <div class="perfumers-by-letter">
                    <?php foreach ($alphabet_nav as $letter): ?>
                        <div class="letter-section" id="letter-<?php echo esc_attr($letter); ?>">
                            <h2 class="letter-heading"><?php echo esc_html($letter); ?></h2>
                            <div class="perfumers-grid">
                                <?php foreach ($perfumers_by_letter[$letter] as $perfumer): ?>
                                    <?php
                                    // Get basic perfumer statistics
                                    $perfumer_perfumes = get_posts(array(
                                        'post_type' => 'parfume',
                                        'posts_per_page' => -1,
                                        'tax_query' => array(
                                            array(
                                                'taxonomy' => 'perfumer',
                                                'field' => 'term_id',
                                                'terms' => $perfumer->term_id,
                                            ),
                                        ),
                                        'fields' => 'ids',
                                    ));
                                    
                                    $perfume_count = count($perfumer_perfumes);
                                    
                                    // Calculate average rating
                                    $total_rating = 0;
                                    $rated_count = 0;
                                    foreach ($perfumer_perfumes as $perfume_id) {
                                        $rating = get_post_meta($perfume_id, '_parfume_rating', true);
                                        if ($rating && is_numeric($rating)) {
                                            $total_rating += floatval($rating);
                                            $rated_count++;
                                        }
                                    }
                                    $average_rating = $rated_count > 0 ? round($total_rating / $rated_count, 1) : 0;
                                    
                                    // Get most collaborated brand
                                    $perfumer_brands = array();
                                    foreach ($perfumer_perfumes as $perfume_id) {
                                        $brands = wp_get_post_terms($perfume_id, 'marki', array('fields' => 'names'));
                                        if (!empty($brands) && !is_wp_error($brands)) {
                                            foreach ($brands as $brand) {
                                                if (!isset($perfumer_brands[$brand])) {
                                                    $perfumer_brands[$brand] = 0;
                                                }
                                                $perfumer_brands[$brand]++;
                                            }
                                        }
                                    }
                                    
                                    arsort($perfumer_brands);
                                    $top_brand = !empty($perfumer_brands) ? array_key_first($perfumer_brands) : '';
                                    
                                    // Get signature aroma type
                                    $perfumer_aroma_types = array();
                                    foreach ($perfumer_perfumes as $perfume_id) {
                                        $aroma_types = wp_get_post_terms($perfume_id, 'aroma_type', array('fields' => 'names'));
                                        if (!empty($aroma_types) && !is_wp_error($aroma_types)) {
                                            foreach ($aroma_types as $type) {
                                                if (!isset($perfumer_aroma_types[$type])) {
                                                    $perfumer_aroma_types[$type] = 0;
                                                }
                                                $perfumer_aroma_types[$type]++;
                                            }
                                        }
                                    }
                                    
                                    arsort($perfumer_aroma_types);
                                    $signature_style = !empty($perfumer_aroma_types) ? array_key_first($perfumer_aroma_types) : '';
                                    
                                    // Get perfumer image
                                    $perfumer_image_id = get_term_meta($perfumer->term_id, 'perfumer-image-id', true);
                                    $perfumer_image = $perfumer_image_id ? wp_get_attachment_image_url($perfumer_image_id, 'thumbnail') : '';
                                    ?>
                                    
                                    <div class="perfumer-card">
                                        <div class="perfumer-card-header">
                                            <?php if ($perfumer_image): ?>
                                                <div class="perfumer-card-image">
                                                    <a href="<?php echo get_term_link($perfumer); ?>">
                                                        <img src="<?php echo esc_url($perfumer_image); ?>" alt="<?php echo esc_attr($perfumer->name); ?>">
                                                    </a>
                                                </div>
                                            <?php else: ?>
                                                <div class="perfumer-card-avatar">
                                                    <span class="avatar-icon">👨‍🔬</span>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <div class="perfumer-card-info">
                                                <h3 class="perfumer-card-name">
                                                    <a href="<?php echo get_term_link($perfumer); ?>"><?php echo esc_html($perfumer->name); ?></a>
                                                </h3>
                                                
                                                <?php if ($top_brand): ?>
                                                    <div class="perfumer-card-brand">
                                                        <span class="brand-label"><?php _e('Known for:', 'parfume-reviews'); ?></span>
                                                        <span class="brand-name"><?php echo esc_html($top_brand); ?></span>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        
                                        <?php if ($perfumer->description): ?>
                                            <div class="perfumer-card-description">
                                                <?php echo wp_trim_words(esc_html($perfumer->description), 15, '...'); ?>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <div class="perfumer-card-stats">
                                            <div class="stat-item">
                                                <span class="stat-number"><?php echo $perfume_count; ?></span>
                                                <span class="stat-label"><?php _e('Fragrances', 'parfume-reviews'); ?></span>
                                            </div>
                                            
                                            <?php if ($average_rating > 0): ?>
                                                <div class="stat-item">
                                                    <span class="stat-number"><?php echo $average_rating; ?></span>
                                                    <span class="stat-label"><?php _e('Avg Rating', 'parfume-reviews'); ?></span>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <?php if ($signature_style): ?>
                                            <div class="perfumer-card-style">
                                                <span class="style-badge"><?php echo esc_html($signature_style); ?></span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="no-perfumers">
                    <p><?php _e('No perfumers found.', 'parfume-reviews'); ?></p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.perfumer-archive .featured-perfumers-section {
    margin-bottom: 4rem;
}

.perfumer-archive .featured-perfumers-section h2 {
    text-align: center;
    color: #2c3e50;
    margin-bottom: 2rem;
    font-size: 2rem;
}

.perfumer-archive .featured-perfumers-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
    gap: 2rem;
    margin-bottom: 3rem;
}

.perfumer-archive .featured-perfumer-card {
    background: white;
    border-radius: 16px;
    padding: 2rem;
    box-shadow: 0 8px 20px rgba(0,0,0,0.1);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    border: 1px solid #f1f3f4;
}

.perfumer-archive .featured-perfumer-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 12px 30px rgba(0,0,0,0.15);
}

.perfumer-archive .perfumer-header {
    text-align: center;
    margin-bottom: 1.5rem;
}

.perfumer-archive .perfumer-image img {
    width: 100px;
    height: 100px;
    object-fit: cover;
    border-radius: 50%;
    border: 4px solid #f8f9fa;
    margin-bottom: 1rem;
    transition: transform 0.2s ease;
}

.perfumer-archive .perfumer-image:hover img {
    transform: scale(1.05);
}

.perfumer-archive .perfumer-avatar {
    width: 100px;
    height: 100px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1rem;
}

.perfumer-archive .avatar-icon {
    font-size: 3rem;
}

.perfumer-archive .perfumer-name a {
    color: #2c3e50;
    text-decoration: none;
    font-size: 1.5rem;
    font-weight: 700;
}

.perfumer-archive .perfumer-name a:hover {
    color: #007cba;
}

.perfumer-archive .perfumer-bio {
    color: #6c757d;
    line-height: 1.6;
    margin-bottom: 1.5rem;
    text-align: center;
    font-style: italic;
}

.perfumer-archive .perfumer-stats {
    margin: 1.5rem 0;
}

.perfumer-archive .stat-row {
    display: flex;
    justify-content: center;
    gap: 2rem;
    margin-bottom: 1rem;
}

.perfumer-archive .stat-item {
    text-align: center;
}

.perfumer-archive .stat-number {
    display: block;
    font-size: 2rem;
    font-weight: 700;
    color: #007cba;
}

.perfumer-archive .stat-label {
    display: block;
    font-size: 0.875rem;
    color: #6c757d;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.perfumer-archive .signature-style {
    text-align: center;
    margin: 1rem 0;
    padding: 1rem;
    background: #f8f9fa;
    border-radius: 8px;
}

.perfumer-archive .style-label {
    font-weight: 600;
    color: #495057;
    margin-right: 0.5rem;
}

.perfumer-archive .style-name {
    color: #007cba;
    font-weight: 500;
}

.perfumer-archive .collaborated-brands {
    text-align: center;
    margin: 1rem 0;
}

.perfumer-archive .brands-label {
    font-weight: 600;
    color: #495057;
    display: block;
    margin-bottom: 0.5rem;
}

.perfumer-archive .brands-list {
    color: #6c757d;
    font-style: italic;
}

.perfumer-archive .signature-notes {
    margin: 1.5rem 0;
}

.perfumer-archive .notes-label {
    font-weight: 600;
    color: #495057;
    display: block;
    margin-bottom: 0.75rem;
    text-align: center;
}

.perfumer-archive .notes-tags {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 0.5rem;
}

.perfumer-archive .note-tag {
    background: #e3f2fd;
    color: #1976d2;
    padding: 0.4rem 0.8rem;
    border-radius: 16px;
    font-size: 0.875rem;
    font-weight: 500;
}

.perfumer-archive .perfumer-actions {
    text-align: center;
    margin-top: 1.5rem;
}

.perfumer-archive .view-perfumer-btn {
    display: inline-block;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 0.75rem 2rem;
    border-radius: 25px;
    text-decoration: none;
    font-weight: 600;
    transition: transform 0.2s ease;
}

.perfumer-archive .view-perfumer-btn:hover {
    transform: scale(1.05);
    color: white;
}

.perfumer-archive .alphabet-navigation {
    margin-bottom: 2rem;
    padding: 1.5rem;
    background: #f8f9fa;
    border-radius: 8px;
}

.perfumer-archive .alphabet-navigation h3 {
    color: #2c3e50;
    margin-bottom: 1rem;
    text-align: center;
}

.perfumer-archive .alphabet-links {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 0.5rem;
}

.perfumer-archive .letter-link {
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

.perfumer-archive .letter-link:hover {
    background: #007cba;
    color: white;
    border-color: #007cba;
}

.perfumer-archive .letter-section {
    margin-bottom: 3rem;
}

.perfumer-archive .letter-heading {
    font-size: 2rem;
    color: #2c3e50;
    border-bottom: 2px solid #007cba;
    padding-bottom: 0.5rem;
    margin-bottom: 1.5rem;
}

.perfumer-archive .perfumers-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 1.5rem;
}

.perfumer-archive .perfumer-card {
    background: white;
    border-radius: 8px;
    padding: 1.5rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    border: 1px solid #f1f3f4;
}

.perfumer-archive .perfumer-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
}

.perfumer-archive .perfumer-card-header {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 1rem;
}

.perfumer-archive .perfumer-card-image img {
    width: 60px;
    height: 60px;
    object-fit: cover;
    border-radius: 50%;
    border: 2px solid #f8f9fa;
}

.perfumer-archive .perfumer-card-avatar {
    width: 60px;
    height: 60px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.perfumer-archive .perfumer-card-avatar .avatar-icon {
    font-size: 1.5rem;
}

.perfumer-archive .perfumer-card-info {
    flex: 1;
}

.perfumer-archive .perfumer-card-name a {
    color: #2c3e50;
    text-decoration: none;
    font-size: 1.1rem;
    font-weight: 600;
}

.perfumer-archive .perfumer-card-name a:hover {
    color: #007cba;
}

.perfumer-archive .perfumer-card-brand {
    margin-top: 0.25rem;
}

.perfumer-archive .brand-label {
    font-size: 0.875rem;
    color: #6c757d;
}

.perfumer-archive .brand-name {
    font-weight: 500;
    color: #495057;
}

.perfumer-archive .perfumer-card-description {
    color: #6c757d;
    margin: 1rem 0;
    line-height: 1.5;
    font-size: 0.9rem;
}

.perfumer-archive .perfumer-card-stats {
    display: flex;
    justify-content: center;
    gap: 1.5rem;
    margin: 1rem 0;
}

.perfumer-archive .perfumer-card-stats .stat-item {
    text-align: center;
}

.perfumer-archive .perfumer-card-stats .stat-number {
    font-size: 1.5rem;
}

.perfumer-archive .perfumer-card-stats .stat-label {
    font-size: 0.75rem;
}

.perfumer-archive .perfumer-card-style {
    text-align: center;
    margin-top: 1rem;
}

.perfumer-archive .style-badge {
    background: #e3f2fd;
    color: #1976d2;
    padding: 0.4rem 0.8rem;
    border-radius: 16px;
    font-size: 0.875rem;
    font-weight: 500;
}

.perfumer-archive .no-perfumers {
    text-align: center;
    padding: 3rem;
    color: #6c757d;
}

/* Responsive */
@media (max-width: 768px) {
    .perfumer-archive .featured-perfumers-grid,
    .perfumer-archive .perfumers-grid {
        grid-template-columns: 1fr;
    }
    
    .perfumer-archive .featured-perfumer-card {
        padding: 1.5rem;
    }
    
    .perfumer-archive .alphabet-links {
        justify-content: center;
    }
    
    .perfumer-archive .letter-link {
        padding: 0.4rem 0.6rem;
        font-size: 0.9rem;
    }
    
    .perfumer-archive .stat-row {
        gap: 1rem;
    }
    
    .perfumer-archive .perfumer-card-header {
        flex-direction: column;
        text-align: center;
    }
    
    .perfumer-archive .perfumer-card-stats {
        gap: 1rem;
    }
}
</style>

<?php get_footer(); ?>