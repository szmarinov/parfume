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

// Get perfumer meta data (using existing meta fields - no new fields added)
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

// Calculate statistics for this perfumer only
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
        
        // Collect brands this perfumer worked with
        $brands = wp_get_post_terms(get_the_ID(), 'marki');
        foreach ($brands as $brand) {
            if (!isset($brands_worked_with[$brand->term_id])) {
                $brands_worked_with[$brand->term_id] = array(
                    'name' => $brand->name,
                    'count' => 0
                );
            }
            $brands_worked_with[$brand->term_id]['count']++;
        }
        
        // Collect notes used by this perfumer
        $notes = wp_get_post_terms(get_the_ID(), 'notes');
        foreach ($notes as $note) {
            if (!isset($popular_notes[$note->term_id])) {
                $popular_notes[$note->term_id] = array(
                    'name' => $note->name,
                    'count' => 0
                );
            }
            $popular_notes[$note->term_id]['count']++;
        }
    }
    wp_reset_postdata();
}

// Calculate average rating
$average_rating = $rated_count > 0 ? $total_rating / $rated_count : 0;

// Sort brands and notes by popularity
uasort($brands_worked_with, function($a, $b) {
    return $b['count'] - $a['count'];
});

uasort($popular_notes, function($a, $b) {
    return $b['count'] - $a['count'];
});

// Get most popular perfumes by this perfumer (top 3)
$popular_perfumes_args = array(
    'post_type' => 'parfume',
    'tax_query' => array(
        array(
            'taxonomy' => 'perfumer',
            'field' => 'term_id',
            'terms' => $perfumer->term_id,
        ),
    ),
    'posts_per_page' => 3,
    'meta_key' => '_parfume_rating',
    'orderby' => 'meta_value_num',
    'order' => 'DESC',
    'meta_query' => array(
        array(
            'key' => '_parfume_rating',
            'value' => '',
            'compare' => '!='
        )
    )
);

$popular_perfumes_query = new WP_Query($popular_perfumes_args);
?>

<div class="single-perfumer-page">
    <!-- Breadcrumbs -->
    <div class="breadcrumbs">
        <a href="<?php echo home_url(); ?>"><?php _e('Home', 'parfume-reviews'); ?></a>
        <span class="separator">/</span>
        <a href="<?php echo get_post_type_archive_link('parfume'); ?>"><?php _e('Perfumes', 'parfume-reviews'); ?></a>
        <span class="separator">/</span>
        <a href="<?php echo get_term_link(get_taxonomy('perfumer')); ?>"><?php _e('Perfumers', 'parfume-reviews'); ?></a>
        <span class="separator">/</span>
        <span class="current"><?php echo esc_html($perfumer->name); ?></span>
    </div>

    <!-- Perfumer Header -->
    <header class="perfumer-header">
        <div class="perfumer-photo">
            <?php if (!empty($perfumer_photo)): ?>
                <img src="<?php echo esc_url($perfumer_photo); ?>" alt="<?php echo esc_attr($perfumer->name); ?>" class="perfumer-image">
            <?php else: ?>
                <div class="perfumer-avatar">
                    <span class="perfumer-initials">
                        <?php 
                        $name_parts = explode(' ', $perfumer->name);
                        echo esc_html(substr($name_parts[0], 0, 1));
                        if (isset($name_parts[1])) {
                            echo esc_html(substr($name_parts[1], 0, 1));
                        }
                        ?>
                    </span>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="perfumer-info">
            <h1 class="perfumer-name"><?php echo esc_html($perfumer->name); ?></h1>
            
            <?php if (!empty($perfumer->description)): ?>
                <div class="perfumer-description">
                    <?php echo wpautop(esc_html($perfumer->description)); ?>
                </div>
            <?php endif; ?>
            
            <div class="perfumer-meta">
                <?php if (!empty($perfumer_nationality)): ?>
                    <div class="meta-item nationality">
                        <span class="meta-label"><?php _e('Nationality:', 'parfume-reviews'); ?></span>
                        <span class="meta-value"><?php echo esc_html($perfumer_nationality); ?></span>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($perfumer_birthdate)): ?>
                    <div class="meta-item birthdate">
                        <span class="meta-label"><?php _e('Born:', 'parfume-reviews'); ?></span>
                        <span class="meta-value"><?php echo esc_html($perfumer_birthdate); ?></span>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="perfumer-stats">
                <div class="stat-item">
                    <span class="stat-number"><?php echo $total_perfumes; ?></span>
                    <span class="stat-label"><?php _e('Perfumes', 'parfume-reviews'); ?></span>
                </div>
                
                <?php if ($average_rating > 0): ?>
                    <div class="stat-item">
                        <span class="stat-number"><?php echo number_format($average_rating, 1); ?></span>
                        <span class="stat-label"><?php _e('Avg Rating', 'parfume-reviews'); ?></span>
                    </div>
                <?php endif; ?>
                
                <div class="stat-item">
                    <span class="stat-number"><?php echo count($brands_worked_with); ?></span>
                    <span class="stat-label"><?php _e('Brands', 'parfume-reviews'); ?></span>
                </div>
            </div>
        </div>
    </header>

    <!-- Navigation Tabs -->
    <nav class="perfumer-tabs">
        <a href="#overview" class="tab-link active" data-tab="overview"><?php _e('Overview', 'parfume-reviews'); ?></a>
        <a href="#perfumes" class="tab-link" data-tab="perfumes"><?php _e('Perfumes', 'parfume-reviews'); ?></a>
        <?php if (!empty($perfumer_education) || !empty($perfumer_awards) || !empty($perfumer_website) || !empty($perfumer_social_media)): ?>
            <a href="#biography" class="tab-link" data-tab="biography"><?php _e('Biography', 'parfume-reviews'); ?></a>
        <?php endif; ?>
        <?php if (!empty($brands_worked_with)): ?>
            <a href="#collaborations" class="tab-link" data-tab="collaborations"><?php _e('Collaborations', 'parfume-reviews'); ?></a>
        <?php endif; ?>
    </nav>

    <!-- Tab Contents -->
    <div class="tab-contents">
        
        <!-- Overview Tab -->
        <div id="overview" class="tab-content active">
            <!-- Quick Stats Grid -->
            <div class="quick-stats-grid">
                <?php if (!empty($perfumer_signature_style)): ?>
                    <div class="quick-stat-card">
                        <h3><?php _e('Signature Style', 'parfume-reviews'); ?></h3>
                        <p><?php echo esc_html($perfumer_signature_style); ?></p>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($brands_worked_with)): ?>
                    <div class="quick-stat-card">
                        <h3><?php _e('Top Brands', 'parfume-reviews'); ?></h3>
                        <div class="brands-list">
                            <?php 
                            $top_brands = array_slice($brands_worked_with, 0, 3, true);
                            foreach ($top_brands as $brand): 
                            ?>
                                <span class="brand-tag"><?php echo esc_html($brand['name']); ?> (<?php echo $brand['count']; ?>)</span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($popular_notes)): ?>
                    <div class="quick-stat-card">
                        <h3><?php _e('Popular Notes', 'parfume-reviews'); ?></h3>
                        <div class="notes-list">
                            <?php 
                            $top_notes = array_slice($popular_notes, 0, 5, true);
                            foreach ($top_notes as $note): 
                            ?>
                                <span class="note-tag"><?php echo esc_html($note['name']); ?></span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Most Popular Perfumes -->
            <?php if ($popular_perfumes_query->have_posts()): ?>
                <section class="popular-perfumes">
                    <h2><?php _e('Most Popular Perfumes by', 'parfume-reviews'); ?> <?php echo esc_html($perfumer->name); ?></h2>
                    <div class="perfumes-grid">
                        <?php while ($popular_perfumes_query->have_posts()): $popular_perfumes_query->the_post(); ?>
                            <div class="perfume-card">
                                <a href="<?php the_permalink(); ?>" class="perfume-link">
                                    <div class="perfume-thumbnail">
                                        <?php if (has_post_thumbnail()): ?>
                                            <?php the_post_thumbnail('medium'); ?>
                                        <?php else: ?>
                                            <div class="no-image">
                                                <span><?php _e('No Image', 'parfume-reviews'); ?></span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="perfume-info">
                                        <h3 class="perfume-title"><?php the_title(); ?></h3>
                                        
                                        <?php 
                                        $brands = wp_get_post_terms(get_the_ID(), 'marki', array('fields' => 'names'));
                                        if (!empty($brands) && !is_wp_error($brands)): 
                                        ?>
                                            <div class="perfume-brand"><?php echo esc_html($brands[0]); ?></div>
                                        <?php endif; ?>
                                        
                                        <?php 
                                        $rating = get_post_meta(get_the_ID(), '_parfume_rating', true);
                                        if (!empty($rating)): 
                                        ?>
                                            <div class="perfume-rating">
                                                <span class="stars">
                                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                                        <span class="star <?php echo $i <= $rating ? 'filled' : ''; ?>">★</span>
                                                    <?php endfor; ?>
                                                </span>
                                                <span class="rating-number"><?php echo number_format($rating, 1); ?></span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </a>
                            </div>
                        <?php endwhile; wp_reset_postdata(); ?>
                    </div>
                </section>
            <?php endif; ?>
        </div>

        <!-- Perfumes Tab -->
        <div id="perfumes" class="tab-content">
            <?php if ($perfume_query->have_posts()): ?>
                <h2><?php _e('All Perfumes by', 'parfume-reviews'); ?> <?php echo esc_html($perfumer->name); ?> (<?php echo $total_perfumes; ?>)</h2>
                <div class="all-perfumes-grid">
                    <?php while ($perfume_query->have_posts()): $perfume_query->the_post(); ?>
                        <div class="perfume-card">
                            <a href="<?php the_permalink(); ?>" class="perfume-link">
                                <div class="perfume-thumbnail">
                                    <?php if (has_post_thumbnail()): ?>
                                        <?php the_post_thumbnail('medium'); ?>
                                    <?php else: ?>
                                        <div class="no-image">
                                            <span><?php _e('No Image', 'parfume-reviews'); ?></span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="perfume-info">
                                    <h3 class="perfume-title"><?php the_title(); ?></h3>
                                    
                                    <?php 
                                    $brands = wp_get_post_terms(get_the_ID(), 'marki', array('fields' => 'names'));
                                    if (!empty($brands) && !is_wp_error($brands)): 
                                    ?>
                                        <div class="perfume-brand"><?php echo esc_html($brands[0]); ?></div>
                                    <?php endif; ?>
                                    
                                    <?php 
                                    $rating = get_post_meta(get_the_ID(), '_parfume_rating', true);
                                    if (!empty($rating)): 
                                    ?>
                                        <div class="perfume-rating">
                                            <span class="stars">
                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                    <span class="star <?php echo $i <= $rating ? 'filled' : ''; ?>">★</span>
                                                <?php endfor; ?>
                                            </span>
                                            <span class="rating-number"><?php echo number_format($rating, 1); ?></span>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="perfume-year">
                                        <?php echo get_the_date('Y'); ?>
                                    </div>
                                </div>
                            </a>
                        </div>
                    <?php endwhile; wp_reset_postdata(); ?>
                </div>
            <?php else: ?>
                <p class="no-perfumes"><?php _e('No perfumes found for this perfumer.', 'parfume-reviews'); ?></p>
            <?php endif; ?>
        </div>

        <!-- Biography Tab -->
        <?php if (!empty($perfumer_education) || !empty($perfumer_awards) || !empty($perfumer_website) || !empty($perfumer_social_media)): ?>
            <div id="biography" class="tab-content">
                <div class="biography-content">
                    <?php if (!empty($perfumer_education)): ?>
                        <section class="bio-section">
                            <h3><?php _e('Education', 'parfume-reviews'); ?></h3>
                            <p><?php echo esc_html($perfumer_education); ?></p>
                        </section>
                    <?php endif; ?>
                    
                    <?php if (!empty($perfumer_awards)): ?>
                        <section class="bio-section">
                            <h3><?php _e('Awards & Recognition', 'parfume-reviews'); ?></h3>
                            <p><?php echo esc_html($perfumer_awards); ?></p>
                        </section>
                    <?php endif; ?>
                    
                    <?php if (!empty($perfumer_website) || !empty($perfumer_social_media)): ?>
                        <section class="bio-section">
                            <h3><?php _e('External Links', 'parfume-reviews'); ?></h3>
                            <?php if (!empty($perfumer_website)): ?>
                                <p><a href="<?php echo esc_url($perfumer_website); ?>" target="_blank" rel="noopener"><?php _e('Official Website', 'parfume-reviews'); ?></a></p>
                            <?php endif; ?>
                            
                            <?php if (!empty($perfumer_social_media)): ?>
                                <p><a href="<?php echo esc_url($perfumer_social_media); ?>" target="_blank" rel="noopener"><?php _e('Social Media', 'parfume-reviews'); ?></a></p>
                            <?php endif; ?>
                        </section>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Collaborations Tab -->
        <?php if (!empty($brands_worked_with)): ?>
            <div id="collaborations" class="tab-content">
                <div class="collaborations-content">
                    <!-- Brand Collaborations -->
                    <section class="collab-section">
                        <h3><?php _e('Brand Collaborations for', 'parfume-reviews'); ?> <?php echo esc_html($perfumer->name); ?></h3>
                        <div class="brands-grid">
                            <?php foreach ($brands_worked_with as $brand): ?>
                                <div class="brand-collab-card">
                                    <h4><?php echo esc_html($brand['name']); ?></h4>
                                    <p><?php printf(_n('%d perfume', '%d perfumes', $brand['count'], 'parfume-reviews'), $brand['count']); ?></p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </section>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Back to All Perfumers -->
    <div class="back-to-perfumers">
        <a href="<?php echo get_term_link(get_taxonomy('perfumer')); ?>" class="back-button">
            <span class="dashicons dashicons-arrow-left-alt2"></span>
            <?php _e('Back to All Perfumers', 'parfume-reviews'); ?>
        </a>
    </div>
</div>

<!-- CSS се зарежда автоматично през enqueue_scripts() метода -->

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