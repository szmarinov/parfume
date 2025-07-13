<?php
/**
 * Template for single perfumer (parfumeur) pages
 * Отделна страница за парфюмьор с пълна информация, биография и парфюми
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();

// Get the current perfumer term
$queried_object = get_queried_object();
if (!$queried_object || !isset($queried_object->taxonomy) || $queried_object->taxonomy !== 'perfumer') {
    get_template_part('404');
    get_footer();
    return;
}

$perfumer = $queried_object;

// Get perfumer meta data
$perfumer_photo = get_term_meta($perfumer->term_id, 'perfumer_photo', true);
$perfumer_birthdate = get_term_meta($perfumer->term_id, 'perfumer_birthdate', true);
$perfumer_nationality = get_term_meta($perfumer->term_id, 'perfumer_nationality', true);
$perfumer_education = get_term_meta($perfumer->term_id, 'perfumer_education', true);
$perfumer_awards = get_term_meta($perfumer->term_id, 'perfumer_awards', true);
$perfumer_signature_style = get_term_meta($perfumer->term_id, 'perfumer_signature_style', true);
$perfumer_website = get_term_meta($perfumer->term_id, 'perfumer_website', true);
$perfumer_social_media = get_term_meta($perfumer->term_id, 'perfumer_social_media', true);

// Get perfumer's perfumes
$perfume_args = array(
    'post_type' => 'parfume',
    'tax_query' => array(
        array(
            'taxonomy' => 'perfumer',
            'field' => 'term_id',
            'terms' => $perfumer->term_id,
        ),
    ),
    'posts_per_page' => -1,
    'orderby' => 'date',
    'order' => 'DESC',
);

$perfume_query = new WP_Query($perfume_args);

// Calculate statistics
$total_perfumes = $perfume_query->found_posts;
$total_rating = 0;
$rated_count = 0;
$brands_worked_with = array();
$popular_notes = array();

if ($perfume_query->have_posts()) {
    while ($perfume_query->have_posts()) {
        $perfume_query->the_post();
        
        // Collect ratings
        $rating = get_post_meta(get_the_ID(), '_parfume_rating', true);
        if (!empty($rating) && is_numeric($rating)) {
            $total_rating += floatval($rating);
            $rated_count++;
        }
        
        // Collect brands
        $brands = wp_get_post_terms(get_the_ID(), 'marki', array('fields' => 'names'));
        if (!empty($brands) && !is_wp_error($brands)) {
            foreach ($brands as $brand) {
                if (!in_array($brand, $brands_worked_with)) {
                    $brands_worked_with[] = $brand;
                }
            }
        }
        
        // Collect popular notes
        $notes = wp_get_post_terms(get_the_ID(), 'notes', array('fields' => 'names'));
        if (!empty($notes) && !is_wp_error($notes)) {
            foreach ($notes as $note) {
                if (!isset($popular_notes[$note])) {
                    $popular_notes[$note] = 0;
                }
                $popular_notes[$note]++;
            }
        }
    }
    wp_reset_postdata();
}

$average_rating = $rated_count > 0 ? $total_rating / $rated_count : 0;

// Sort popular notes by frequency
arsort($popular_notes);
$top_notes = array_slice(array_keys($popular_notes), 0, 10);

?>

<div class="single-perfumer-page">
    <!-- Perfumer Header -->
    <header class="perfumer-header">
        <div class="container">
            <div class="perfumer-header-content">
                <div class="perfumer-photo-section">
                    <?php if (!empty($perfumer_photo)): ?>
                        <div class="perfumer-photo-large">
                            <img src="<?php echo esc_url($perfumer_photo); ?>" alt="<?php echo esc_attr($perfumer->name); ?>" />
                        </div>
                    <?php else: ?>
                        <div class="perfumer-avatar-large">
                            <span class="perfumer-initials-large">
                                <?php echo esc_html(strtoupper(substr($perfumer->name, 0, 2))); ?>
                            </span>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="perfumer-header-info">
                    <h1 class="perfumer-title"><?php echo esc_html($perfumer->name); ?></h1>
                    
                    <?php if (!empty($perfumer_nationality)): ?>
                        <div class="perfumer-nationality">
                            <span class="info-icon">🌍</span>
                            <?php echo esc_html($perfumer_nationality); ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($perfumer_birthdate)): ?>
                        <div class="perfumer-birthdate">
                            <span class="info-icon">📅</span>
                            <?php echo esc_html($perfumer_birthdate); ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="perfumer-stats">
                        <div class="stat-item">
                            <span class="stat-number"><?php echo $total_perfumes; ?></span>
                            <span class="stat-label"><?php _e('Парфюма', 'parfume-reviews'); ?></span>
                        </div>
                        
                        <?php if ($average_rating > 0): ?>
                            <div class="stat-item">
                                <span class="stat-number"><?php echo number_format($average_rating, 1); ?>/5</span>
                                <span class="stat-label"><?php _e('Среден рейтинг', 'parfume-reviews'); ?></span>
                            </div>
                        <?php endif; ?>
                        
                        <div class="stat-item">
                            <span class="stat-number"><?php echo count($brands_worked_with); ?></span>
                            <span class="stat-label"><?php _e('Марки', 'parfume-reviews'); ?></span>
                        </div>
                    </div>
                    
                    <!-- Social Links -->
                    <?php if (!empty($perfumer_website) || !empty($perfumer_social_media)): ?>
                        <div class="perfumer-social">
                            <?php if (!empty($perfumer_website)): ?>
                                <a href="<?php echo esc_url($perfumer_website); ?>" target="_blank" rel="noopener" class="social-link website">
                                    <span class="social-icon">🌐</span>
                                    <?php _e('Официален сайт', 'parfume-reviews'); ?>
                                </a>
                            <?php endif; ?>
                            
                            <?php if (!empty($perfumer_social_media) && is_array($perfumer_social_media)): ?>
                                <?php foreach ($perfumer_social_media as $platform => $url): ?>
                                    <?php if (!empty($url)): ?>
                                        <a href="<?php echo esc_url($url); ?>" target="_blank" rel="noopener" class="social-link <?php echo esc_attr($platform); ?>">
                                            <span class="social-icon">
                                                <?php
                                                switch ($platform) {
                                                    case 'instagram': echo '📷'; break;
                                                    case 'facebook': echo '📘'; break;
                                                    case 'twitter': echo '🐦'; break;
                                                    case 'linkedin': echo '💼'; break;
                                                    default: echo '🔗'; break;
                                                }
                                                ?>
                                            </span>
                                            <?php echo esc_html(ucfirst($platform)); ?>
                                        </a>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </header>

    <!-- Navigation Tabs -->
    <nav class="perfumer-tabs">
        <div class="container">
            <div class="tab-nav">
                <a href="#biography" class="tab-link active" data-tab="biography">
                    <?php _e('Биография', 'parfume-reviews'); ?>
                </a>
                <a href="#perfumes" class="tab-link" data-tab="perfumes">
                    <?php _e('Парфюми', 'parfume-reviews'); ?> (<?php echo $total_perfumes; ?>)
                </a>
                <a href="#signature-style" class="tab-link" data-tab="signature-style">
                    <?php _e('Подпис стил', 'parfume-reviews'); ?>
                </a>
                <a href="#awards" class="tab-link" data-tab="awards">
                    <?php _e('Награди', 'parfume-reviews'); ?>
                </a>
                <a href="#collaborations" class="tab-link" data-tab="collaborations">
                    <?php _e('Сътрудничества', 'parfume-reviews'); ?>
                </a>
            </div>
        </div>
    </nav>

    <!-- Content Sections -->
    <main class="perfumer-content">
        <div class="container">
            
            <!-- Biography Tab -->
            <section id="biography" class="tab-content active">
                <div class="content-wrapper">
                    <div class="main-content">
                        <h2><?php _e('За парфюмьора', 'parfume-reviews'); ?></h2>
                        
                        <?php if (!empty($perfumer->description)): ?>
                            <div class="perfumer-biography">
                                <?php echo wp_kses_post(wpautop($perfumer->description)); ?>
                            </div>
                        <?php else: ?>
                            <p class="no-biography">
                                <?php _e('Няма налична биография за този парфюмьор.', 'parfume-reviews'); ?>
                            </p>
                        <?php endif; ?>
                        
                        <?php if (!empty($perfumer_education)): ?>
                            <div class="perfumer-education">
                                <h3><?php _e('Образование', 'parfume-reviews'); ?></h3>
                                <?php echo wp_kses_post(wpautop($perfumer_education)); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <aside class="sidebar-content">
                        <!-- Quick Facts -->
                        <div class="quick-facts-card">
                            <h3><?php _e('Бързи факти', 'parfume-reviews'); ?></h3>
                            <ul class="facts-list">
                                <?php if (!empty($perfumer_nationality)): ?>
                                    <li>
                                        <strong><?php _e('Националност:', 'parfume-reviews'); ?></strong>
                                        <?php echo esc_html($perfumer_nationality); ?>
                                    </li>
                                <?php endif; ?>
                                
                                <?php if (!empty($perfumer_birthdate)): ?>
                                    <li>
                                        <strong><?php _e('Дата на раждане:', 'parfume-reviews'); ?></strong>
                                        <?php echo esc_html($perfumer_birthdate); ?>
                                    </li>
                                <?php endif; ?>
                                
                                <li>
                                    <strong><?php _e('Общо парфюми:', 'parfume-reviews'); ?></strong>
                                    <?php echo $total_perfumes; ?>
                                </li>
                                
                                <?php if ($average_rating > 0): ?>
                                    <li>
                                        <strong><?php _e('Среден рейтинг:', 'parfume-reviews'); ?></strong>
                                        <div class="rating-display">
                                            <?php echo parfume_reviews_get_rating_stars($average_rating); ?>
                                            <span class="rating-number"><?php echo number_format($average_rating, 1); ?>/5</span>
                                        </div>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </div>
                        
                        <!-- Popular Notes -->
                        <?php if (!empty($top_notes)): ?>
                            <div class="popular-notes-card">
                                <h3><?php _e('Най-използвани нотки', 'parfume-reviews'); ?></h3>
                                <div class="notes-cloud">
                                    <?php foreach ($top_notes as $note): ?>
                                        <span class="note-tag" data-count="<?php echo $popular_notes[$note]; ?>">
                                            <?php echo esc_html($note); ?>
                                            <span class="note-count"><?php echo $popular_notes[$note]; ?></span>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </aside>
                </div>
            </section>

            <!-- Perfumes Tab -->
            <section id="perfumes" class="tab-content">
                <h2><?php _e('Парфюми от', 'parfume-reviews'); ?> <?php echo esc_html($perfumer->name); ?></h2>
                
                <?php if ($perfume_query->have_posts()): ?>
                    <div class="perfumes-grid">
                        <?php
                        // Reset query and loop through perfumes
                        $perfume_query->rewind_posts();
                        while ($perfume_query->have_posts()): 
                            $perfume_query->the_post();
                        ?>
                            <article class="perfume-card">
                                <div class="perfume-image">
                                    <a href="<?php the_permalink(); ?>">
                                        <?php if (has_post_thumbnail()): ?>
                                            <?php the_post_thumbnail('medium'); ?>
                                        <?php else: ?>
                                            <div class="no-image-placeholder">
                                                <span class="placeholder-icon">🌸</span>
                                            </div>
                                        <?php endif; ?>
                                    </a>
                                </div>
                                
                                <div class="perfume-content">
                                    <h3 class="perfume-title">
                                        <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                                    </h3>
                                    
                                    <?php 
                                    $brands = wp_get_post_terms(get_the_ID(), 'marki', array('fields' => 'names'));
                                    if (!empty($brands) && !is_wp_error($brands)): 
                                    ?>
                                        <div class="perfume-brand"><?php echo esc_html($brands[0]); ?></div>
                                    <?php endif; ?>
                                    
                                    <?php 
                                    $rating = get_post_meta(get_the_ID(), '_parfume_rating', true);
                                    if (!empty($rating) && is_numeric($rating)): 
                                    ?>
                                        <div class="perfume-rating">
                                            <div class="rating-stars">
                                                <?php echo parfume_reviews_get_rating_stars($rating); ?>
                                            </div>
                                            <span class="rating-number"><?php echo number_format(floatval($rating), 1); ?>/5</span>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="perfume-excerpt">
                                        <?php echo wp_trim_words(get_the_excerpt(), 15); ?>
                                    </div>
                                    
                                    <div class="perfume-actions">
                                        <a href="<?php the_permalink(); ?>" class="button view-perfume">
                                            <?php _e('Виж детайли', 'parfume-reviews'); ?>
                                        </a>
                                        <?php echo parfume_reviews_get_comparison_button(get_the_ID()); ?>
                                    </div>
                                </div>
                            </article>
                        <?php endwhile; ?>
                    </div>
                    
                    <?php wp_reset_postdata(); ?>
                <?php else: ?>
                    <p class="no-perfumes">
                        <?php _e('Няма намерени парфюми за този парфюмьор.', 'parfume-reviews'); ?>
                    </p>
                <?php endif; ?>
            </section>

            <!-- Signature Style Tab -->
            <section id="signature-style" class="tab-content">
                <h2><?php _e('Подпис стил', 'parfume-reviews'); ?></h2>
                
                <?php if (!empty($perfumer_signature_style)): ?>
                    <div class="signature-style-content">
                        <?php echo wp_kses_post(wpautop($perfumer_signature_style)); ?>
                    </div>
                <?php endif; ?>
                
                <!-- Style Analysis -->
                <div class="style-analysis">
                    <h3><?php _e('Анализ на стила', 'parfume-reviews'); ?></h3>
                    
                    <?php if (!empty($top_notes)): ?>
                        <div class="style-notes">
                            <h4><?php _e('Предпочитани ароматни нотки:', 'parfume-reviews'); ?></h4>
                            <div class="notes-frequency">
                                <?php foreach (array_slice($top_notes, 0, 5) as $note): ?>
                                    <div class="note-frequency-item">
                                        <span class="note-name"><?php echo esc_html($note); ?></span>
                                        <div class="frequency-bar">
                                            <div class="frequency-fill" style="width: <?php echo min(100, ($popular_notes[$note] / max($popular_notes)) * 100); ?>%"></div>
                                        </div>
                                        <span class="frequency-count"><?php echo $popular_notes[$note]; ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($brands_worked_with)): ?>
                        <div class="collaboration-brands">
                            <h4><?php _e('Марки сътрудници:', 'parfume-reviews'); ?></h4>
                            <div class="brands-list">
                                <?php foreach ($brands_worked_with as $brand): ?>
                                    <span class="brand-tag"><?php echo esc_html($brand); ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </section>

            <!-- Awards Tab -->
            <section id="awards" class="tab-content">
                <h2><?php _e('Награди и признания', 'parfume-reviews'); ?></h2>
                
                <?php if (!empty($perfumer_awards)): ?>
                    <div class="awards-content">
                        <?php echo wp_kses_post(wpautop($perfumer_awards)); ?>
                    </div>
                <?php else: ?>
                    <p class="no-awards">
                        <?php _e('Няма налична информация за награди.', 'parfume-reviews'); ?>
                    </p>
                <?php endif; ?>
            </section>

            <!-- Collaborations Tab -->
            <section id="collaborations" class="tab-content">
                <h2><?php _e('Сътрудничества и партньорства', 'parfume-reviews'); ?></h2>
                
                <?php if (!empty($brands_worked_with)): ?>
                    <div class="collaborations-grid">
                        <?php foreach ($brands_worked_with as $brand): ?>
                            <?php
                            // Get perfumes for this brand
                            $brand_perfumes_args = array(
                                'post_type' => 'parfume',
                                'tax_query' => array(
                                    'relation' => 'AND',
                                    array(
                                        'taxonomy' => 'perfumer',
                                        'field' => 'term_id',
                                        'terms' => $perfumer->term_id,
                                    ),
                                    array(
                                        'taxonomy' => 'marki',
                                        'field' => 'name',
                                        'terms' => $brand,
                                    ),
                                ),
                                'posts_per_page' => -1,
                            );
                            
                            $brand_perfumes_query = new WP_Query($brand_perfumes_args);
                            $brand_perfume_count = $brand_perfumes_query->found_posts;
                            ?>
                            
                            <div class="collaboration-item">
                                <h3><?php echo esc_html($brand); ?></h3>
                                <p class="perfume-count">
                                    <?php printf(_n('%d парфюм', '%d парфюма', $brand_perfume_count, 'parfume-reviews'), $brand_perfume_count); ?>
                                </p>
                                
                                <?php if ($brand_perfumes_query->have_posts()): ?>
                                    <div class="brand-perfumes-list">
                                        <?php while ($brand_perfumes_query->have_posts()): ?>
                                            <?php $brand_perfumes_query->the_post(); ?>
                                            <a href="<?php the_permalink(); ?>" class="brand-perfume-link">
                                                <?php the_title(); ?>
                                            </a>
                                        <?php endwhile; ?>
                                    </div>
                                    <?php wp_reset_postdata(); ?>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="no-collaborations">
                        <?php _e('Няма налична информация за сътрудничества.', 'parfume-reviews'); ?>
                    </p>
                <?php endif; ?>
            </section>
        </div>
    </main>

    <!-- Related Perfumers -->
    <?php
    // Get related perfumers (perfumers who worked with similar brands)
    $related_perfumers = array();
    if (!empty($brands_worked_with)) {
        $related_args = array(
            'taxonomy' => 'perfumer',
            'hide_empty' => true,
            'exclude' => array($perfumer->term_id),
            'meta_query' => array(
                'relation' => 'OR',
            ),
        );
        
        // This is a simplified approach - in reality you'd want a more complex query
        $all_perfumers = get_terms($related_args);
        $related_perfumers = array_slice($all_perfumers, 0, 3);
    }
    ?>
    
    <?php if (!empty($related_perfumers)): ?>
        <section class="related-perfumers">
            <div class="container">
                <h2><?php _e('Други парфюмьори', 'parfume-reviews'); ?></h2>
                <div class="related-perfumers-grid">
                    <?php foreach ($related_perfumers as $related_perfumer): ?>
                        <div class="related-perfumer-item">
                            <a href="<?php echo get_term_link($related_perfumer); ?>" class="related-perfumer-link">
                                <?php
                                $related_photo = get_term_meta($related_perfumer->term_id, 'perfumer_photo', true);
                                if (!empty($related_photo)): ?>
                                    <div class="related-perfumer-photo">
                                        <img src="<?php echo esc_url($related_photo); ?>" alt="<?php echo esc_attr($related_perfumer->name); ?>" />
                                    </div>
                                <?php else: ?>
                                    <div class="related-perfumer-avatar">
                                        <span class="perfumer-initials">
                                            <?php echo esc_html(strtoupper(substr($related_perfumer->name, 0, 2))); ?>
                                        </span>
                                    </div>
                                <?php endif; ?>
                                
                                <h3><?php echo esc_html($related_perfumer->name); ?></h3>
                                <p class="perfume-count">
                                    <?php printf(_n('%d парфюм', '%d парфюма', $related_perfumer->count, 'parfume-reviews'), $related_perfumer->count); ?>
                                </p>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    <?php endif; ?>
</div>

<!-- Load specific CSS for single perfumer -->
<link rel="stylesheet" href="<?php echo PARFUME_REVIEWS_PLUGIN_URL; ?>assets/css/single-perfumer.css">

<script>
// Simple tab functionality
document.addEventListener('DOMContentLoaded', function() {
    const tabLinks = document.querySelectorAll('.tab-link');
    const tabContents = document.querySelectorAll('.tab-content');
    
    tabLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Remove active class from all tabs
            tabLinks.forEach(l => l.classList.remove('active'));
            tabContents.forEach(c => c.classList.remove('active'));
            
            // Add active class to clicked tab
            this.classList.add('active');
            const targetTab = this.getAttribute('data-tab');
            document.getElementById(targetTab).classList.add('active');
        });
    });
});
</script>

<?php get_footer(); ?>