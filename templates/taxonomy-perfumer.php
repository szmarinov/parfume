<?php
/**
 * Taxonomy Perfumer Template - Archive page за парфюмеристи
 * 
 * Този файл се зарежда за:
 * - Archive страница с всички парфюмеристи (/parfiumi/parfumeri/)
 * - Single страница на конкретен парфюмерист (/parfiumi/parfumeri/alberto-morillas/)
 * 
 * Файл: templates/taxonomy-perfumer.php
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();

$current_term = get_queried_object();
$is_single_perfumer = $current_term && isset($current_term->name);

// Ако е конкретен парфюмерист, показваме single page
if ($is_single_perfumer && !empty($current_term->name)) {
    ?>
    <div class="single-perfumer-page perfumer-taxonomy-page">
        <div class="perfumer-hero">
            <div class="container">
                <div class="perfumer-header">
                    <nav class="breadcrumb">
                        <a href="<?php echo home_url(); ?>"><?php _e('Начало', 'parfume-reviews'); ?></a>
                        <span class="separator"> › </span>
                        <a href="<?php echo home_url('/parfiumi/'); ?>"><?php _e('Парфюми', 'parfume-reviews'); ?></a>
                        <span class="separator"> › </span>
                        <a href="<?php echo home_url('/parfiumi/parfumeri/'); ?>"><?php _e('Парфюмеристи', 'parfume-reviews'); ?></a>
                        <span class="separator"> › </span>
                        <span class="current"><?php echo esc_html($current_term->name); ?></span>
                    </nav>
                    
                    <h1 class="perfumer-name"><?php echo esc_html($current_term->name); ?></h1>
                    
                    <?php if (!empty($current_term->description)): ?>
                        <div class="perfumer-bio">
                            <?php echo wpautop(wp_kses_post($current_term->description)); ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="perfumer-stats">
                        <div class="stat-item">
                            <span class="stat-number"><?php echo $current_term->count; ?></span>
                            <span class="stat-label"><?php echo _n('Парфюм', 'Парфюма', $current_term->count, 'parfume-reviews'); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="perfumer-content">
            <div class="container">
                <div class="perfumer-perfumes-section">
                    <h2 class="section-title"><?php printf(__('Парфюми от %s', 'parfume-reviews'), esc_html($current_term->name)); ?></h2>
                    
                    <?php
                    // Query за парфюмите на този парфюмерист  
                    $perfumes_query = new WP_Query(array(
                        'post_type' => 'parfume',
                        'posts_per_page' => 16,
                        'paged' => get_query_var('paged'),
                        'tax_query' => array(
                            array(
                                'taxonomy' => 'perfumer',
                                'field' => 'slug', 
                                'terms' => $current_term->slug,
                            ),
                        ),
                        'meta_key' => '_parfume_rating',
                        'orderby' => 'meta_value_num',
                        'order' => 'DESC',
                    ));
                    ?>
                    
                    <?php if ($perfumes_query->have_posts()): ?>
                        <div class="perfumes-grid">
                            <?php while ($perfumes_query->have_posts()): $perfumes_query->the_post(); ?>
                                <?php parfume_reviews_display_parfume_card(get_the_ID()); ?>
                            <?php endwhile; ?>
                        </div>
                        
                        <?php
                        // Pagination
                        $pagination_links = paginate_links(array(
                            'total' => $perfumes_query->max_num_pages,
                            'current' => max(1, get_query_var('paged')),
                            'prev_text' => __('‹ Предишна', 'parfume-reviews'),
                            'next_text' => __('Следваща ›', 'parfume-reviews'),
                            'type' => 'array',
                        ));
                        
                        if ($pagination_links): ?>
                            <nav class="perfumes-pagination">
                                <ul class="pagination">
                                    <?php foreach ($pagination_links as $link): ?>
                                        <li class="page-item"><?php echo $link; ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </nav>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="no-perfumes">
                            <p><?php _e('Няма намерени парфюми от този парфюмерист.', 'parfume-reviews'); ?></p>
                        </div>
                    <?php endif; ?>
                    
                    <?php wp_reset_postdata(); ?>
                </div>
                
                <!-- Related Perfumers Section -->
                <div class="related-perfumers-section">
                    <h2 class="section-title"><?php _e('Други парфюмеристи', 'parfume-reviews'); ?></h2>
                    
                    <?php
                    // Взимаме други парфюмеристи
                    $other_perfumers = get_terms(array(
                        'taxonomy' => 'perfumer',
                        'hide_empty' => true,
                        'exclude' => array($current_term->term_id),
                        'number' => 8,
                        'orderby' => 'count',
                        'order' => 'DESC',
                    ));
                    
                    if (!empty($other_perfumers) && !is_wp_error($other_perfumers)): ?>
                        <div class="related-perfumers-grid">
                            <?php foreach ($other_perfumers as $perfumer): ?>
                                <div class="related-perfumer-card">
                                    <h3 class="related-perfumer-name">
                                        <a href="<?php echo get_term_link($perfumer); ?>">
                                            <?php echo esc_html($perfumer->name); ?>
                                        </a>
                                    </h3>
                                    <span class="related-perfumer-count">
                                        <?php printf(_n('%d парфюм', '%d парфюма', $perfumer->count, 'parfume-reviews'), $perfumer->count); ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <?php
} else {
    // Archive page - показваме всички парфюмеристи
    ?>
    <div class="perfumers-archive-page perfumer-taxonomy-page">
        <div class="archive-header">
            <div class="container">
                <nav class="breadcrumb">
                    <a href="<?php echo home_url(); ?>"><?php _e('Начало', 'parfume-reviews'); ?></a>
                    <span class="separator"> › </span>
                    <a href="<?php echo home_url('/parfiumi/'); ?>"><?php _e('Парфюми', 'parfume-reviews'); ?></a>
                    <span class="separator"> › </span>
                    <span class="current"><?php _e('Парфюмеристи', 'parfume-reviews'); ?></span>
                </nav>
                
                <h1 class="archive-title"><?php _e('Всички Парфюмеристи', 'parfume-reviews'); ?></h1>
                <div class="archive-description">
                    <p><?php _e('Открийте парфюми по техните създатели. Разгледайте колекциите на най-известните парфюмеристи в света.', 'parfume-reviews'); ?></p>
                </div>
            </div>
        </div>

        <div class="archive-content">
            <div class="container">
                <div class="archive-main">
                    <?php
                    // Вземаме всички парфюмеристи
                    $perfumers = get_terms(array(
                        'taxonomy' => 'perfumer',
                        'hide_empty' => true,
                        'orderby' => 'count',
                        'order' => 'DESC',
                        'number' => 0, // Всички
                    ));

                    if (!empty($perfumers) && !is_wp_error($perfumers)): ?>
                        <div class="perfumers-filter-section">
                            <div class="filter-controls">
                                <input type="text" id="perfumer-search" placeholder="<?php _e('Търсене по име на парфюмерист...', 'parfume-reviews'); ?>" class="perfumer-search-input">
                                <select id="perfumer-sort" class="perfumer-sort-select">
                                    <option value="count"><?php _e('Подреди по брой парфюми', 'parfume-reviews'); ?></option>
                                    <option value="name"><?php _e('Подреди по име', 'parfume-reviews'); ?></option>
                                </select>
                            </div>
                        </div>

                        <div class="perfumers-grid" id="perfumers-grid">
                            <?php foreach ($perfumers as $perfumer): 
                                $perfumer_link = get_term_link($perfumer);
                                $perfumer_image = get_term_meta($perfumer->term_id, 'taxonomy_image', true);
                                ?>
                                <div class="perfumer-card" data-name="<?php echo esc_attr(strtolower($perfumer->name)); ?>" data-count="<?php echo esc_attr($perfumer->count); ?>">
                                    <div class="perfumer-card-inner">
                                        <?php if ($perfumer_image): ?>
                                            <div class="perfumer-image">
                                                <img src="<?php echo esc_url($perfumer_image); ?>" alt="<?php echo esc_attr($perfumer->name); ?>" loading="lazy">
                                            </div>
                                        <?php else: ?>
                                            <div class="perfumer-image placeholder">
                                                <span class="perfumer-initials"><?php echo esc_html(mb_substr($perfumer->name, 0, 2, 'UTF-8')); ?></span>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <div class="perfumer-info">
                                            <h3 class="perfumer-name">
                                                <a href="<?php echo esc_url($perfumer_link); ?>">
                                                    <?php echo esc_html($perfumer->name); ?>
                                                </a>
                                            </h3>
                                            <div class="perfumer-stats">
                                                <span class="perfume-count">
                                                    <?php printf(_n('%d парфюм', '%d парфюма', $perfumer->count, 'parfume-reviews'), $perfumer->count); ?>
                                                </span>
                                            </div>
                                            
                                            <?php if (!empty($perfumer->description)): ?>
                                                <div class="perfumer-excerpt">
                                                    <?php echo wp_trim_words($perfumer->description, 15, '...'); ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Статистики -->
                        <div class="archive-stats">
                            <div class="stats-grid">
                                <div class="stat-item">
                                    <span class="stat-number"><?php echo count($perfumers); ?></span>
                                    <span class="stat-label"><?php _e('Парфюмеристи', 'parfume-reviews'); ?></span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-number">
                                        <?php 
                                        $total_perfumes = array_sum(wp_list_pluck($perfumers, 'count'));
                                        echo $total_perfumes;
                                        ?>
                                    </span>
                                    <span class="stat-label"><?php _e('Парфюма общо', 'parfume-reviews'); ?></span>
                                </div>
                            </div>
                        </div>

                    <?php else: ?>
                        <div class="no-perfumers">
                            <p><?php _e('Няма намерени парфюмеристи.', 'parfume-reviews'); ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php
}
?>

<style>
/* Perfumer Taxonomy Styles */
.perfumer-taxonomy-page {
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
}

/* Single Perfumer Styles */
.perfumer-hero {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 60px 0;
    position: relative;
}

.perfumer-hero::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.3);
    z-index: 1;
}

.perfumer-header {
    position: relative;
    z-index: 2;
    text-align: center;
}

.perfumer-name {
    font-size: 3rem;
    font-weight: 700;
    margin: 20px 0;
    text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
}

.perfumer-bio {
    font-size: 1.1rem;
    line-height: 1.6;
    max-width: 600px;
    margin: 0 auto 30px;
    opacity: 0.95;
}

.perfumer-stats {
    display: flex;
    justify-content: center;
    gap: 30px;
}

.stat-item {
    text-align: center;
}

.stat-number {
    display: block;
    font-size: 2.5rem;
    font-weight: 700;
    color: #fff;
}

.stat-label {
    font-size: 0.9rem;
    opacity: 0.8;
    text-transform: uppercase;
    letter-spacing: 1px;
}

/* Archive Styles */
.archive-header {
    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
    color: white;
    padding: 60px 0;
    text-align: center;
}

.archive-title {
    font-size: 2.5rem;
    font-weight: 700;
    margin: 20px 0;
}

.archive-description {
    font-size: 1.1rem;
    max-width: 600px;
    margin: 0 auto;
    opacity: 0.95;
}

/* Breadcrumb */
.breadcrumb {
    margin-bottom: 20px;
    font-size: 0.9rem;
}

.breadcrumb a {
    color: rgba(255,255,255,0.8);
    text-decoration: none;
}

.breadcrumb a:hover {
    color: white;
}

.breadcrumb .separator {
    color: rgba(255,255,255,0.6);
    margin: 0 8px;
}

.breadcrumb .current {
    color: white;
    font-weight: 500;
}

/* Filter Controls */
.perfumers-filter-section {
    margin: 40px 0;
    padding: 20px;
    background: #f8f9fa;
    border-radius: 12px;
}

.filter-controls {
    display: flex;
    gap: 20px;
    justify-content: center;
    flex-wrap: wrap;
}

.perfumer-search-input,
.perfumer-sort-select {
    padding: 12px 16px;
    border: 2px solid #e9ecef;
    border-radius: 8px;
    font-size: 1rem;
    background: white;
    transition: border-color 0.3s ease;
}

.perfumer-search-input:focus,
.perfumer-sort-select:focus {
    outline: none;
    border-color: #667eea;
}

/* Perfumers Grid */
.perfumers-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 30px;
    margin: 40px 0;
}

.perfumer-card {
    background: white;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
    border: 1px solid #f0f0f0;
}

.perfumer-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 30px rgba(0,0,0,0.15);
}

.perfumer-card-inner {
    padding: 0;
}

.perfumer-image {
    width: 100%;
    height: 200px;
    overflow: hidden;
    position: relative;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.perfumer-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s ease;
}

.perfumer-card:hover .perfumer-image img {
    transform: scale(1.05);
}

.perfumer-image.placeholder {
    display: flex;
    align-items: center;
    justify-content: center;
}

.perfumer-initials {
    font-size: 2.5rem;
    font-weight: 700;
    color: white;
    text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
}

.perfumer-info {
    padding: 24px;
}

.perfumer-name {
    margin: 0 0 12px 0;
    font-size: 1.3rem;
    font-weight: 600;
}

.perfumer-name a {
    color: #2c3e50;
    text-decoration: none;
    transition: color 0.3s ease;
}

.perfumer-name a:hover {
    color: #667eea;
}

.perfumer-stats {
    margin-bottom: 12px;
}

.perfume-count {
    color: #667eea;
    font-weight: 500;
    font-size: 0.95rem;
}

.perfumer-excerpt {
    color: #6c757d;
    font-size: 0.9rem;
    line-height: 1.5;
}

/* Perfumes Grid */
.perfumes-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 24px;
    margin: 30px 0;
}

/* Related Perfumers */
.related-perfumers-section {
    margin-top: 60px;
    padding-top: 40px;
    border-top: 1px solid #e9ecef;
}

.related-perfumers-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.related-perfumer-card {
    text-align: center;
    padding: 20px;
    background: #f8f9fa;
    border-radius: 12px;
    transition: all 0.3s ease;
}

.related-perfumer-card:hover {
    background: #e9ecef;
    transform: translateY(-2px);
}

.related-perfumer-name {
    margin: 0 0 8px 0;
    font-size: 1.1rem;
    font-weight: 600;
}

.related-perfumer-name a {
    color: #2c3e50;
    text-decoration: none;
}

.related-perfumer-name a:hover {
    color: #667eea;
}

.related-perfumer-count {
    color: #6c757d;
    font-size: 0.9rem;
}

/* Archive Stats */
.archive-stats {
    margin-top: 60px;
    padding: 40px 0;
    text-align: center;
    background: #f8f9fa;
    border-radius: 16px;
}

.stats-grid {
    display: flex;
    justify-content: center;
    gap: 60px;
}

.archive-stats .stat-item {
    text-align: center;
}

.archive-stats .stat-number {
    display: block;
    font-size: 2.5rem;
    font-weight: 700;
    color: #667eea;
    margin-bottom: 8px;
}

.archive-stats .stat-label {
    font-size: 1rem;
    color: #6c757d;
    text-transform: uppercase;
    letter-spacing: 1px;
}

/* Section Titles */
.section-title {
    font-size: 1.8rem;
    font-weight: 600;
    margin: 0 0 30px 0;
    color: #2c3e50;
    position: relative;
    padding-bottom: 12px;
}

.section-title::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    width: 60px;
    height: 3px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 2px;
}

/* Pagination */
.perfumes-pagination {
    margin-top: 40px;
    text-align: center;
}

.pagination {
    display: inline-flex;
    list-style: none;
    margin: 0;
    padding: 0;
    gap: 8px;
}

.page-item a,
.page-item span {
    display: block;
    padding: 12px 16px;
    text-decoration: none;
    color: #667eea;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    transition: all 0.3s ease;
}

.page-item a:hover,
.page-item .current {
    background: #667eea;
    color: white;
    border-color: #667eea;
}

/* Responsive Design */
@media (max-width: 768px) {
    .perfumer-name {
        font-size: 2rem;
    }
    
    .archive-title {
        font-size: 2rem;
    }
    
    .perfumers-grid {
        grid-template-columns: 1fr;
        gap: 20px;
    }
    
    .perfumes-grid {
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        gap: 20px;
    }
    
    .filter-controls {
        flex-direction: column;
        align-items: stretch;
    }
    
    .perfumer-search-input,
    .perfumer-sort-select {
        width: 100%;
    }
    
    .stats-grid {
        gap: 30px;
    }
    
    .related-perfumers-grid {
        grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    }
}

@media (max-width: 480px) {
    .perfumer-hero,
    .archive-header {
        padding: 40px 0;
    }
    
    .perfumer-name,
    .archive-title {
        font-size: 1.5rem;
    }
    
    .perfumer-bio,
    .archive-description {
        font-size: 1rem;
    }
    
    .stats-grid {
        flex-direction: column;
        gap: 20px;
    }
}

/* No results states */
.no-perfumes,
.no-perfumers {
    text-align: center;
    padding: 60px 20px;
    background: #f8f9fa;
    border-radius: 12px;
    color: #6c757d;
}

.no-perfumes p,
.no-perfumers p {
    font-size: 1.1rem;
    margin: 0;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Search and sort functionality for perfumers archive
    const searchInput = document.getElementById('perfumer-search');
    const sortSelect = document.getElementById('perfumer-sort');
    const perfumersGrid = document.getElementById('perfumers-grid');
    
    if (searchInput && sortSelect && perfumersGrid) {
        const perfumerCards = Array.from(perfumersGrid.querySelectorAll('.perfumer-card'));
        
        function filterAndSort() {
            const searchTerm = searchInput.value.toLowerCase();
            const sortBy = sortSelect.value;
            
            // Filter cards
            const filteredCards = perfumerCards.filter(card => {
                const name = card.dataset.name;
                return name.includes(searchTerm);
            });
            
            // Sort cards
            filteredCards.sort((a, b) => {
                if (sortBy === 'name') {
                    return a.dataset.name.localeCompare(b.dataset.name);
                } else {
                    return parseInt(b.dataset.count) - parseInt(a.dataset.count);
                }
            });
            
            // Clear grid and add filtered/sorted cards
            perfumersGrid.innerHTML = '';
            filteredCards.forEach(card => {
                perfumersGrid.appendChild(card);
            });
            
            // Show/hide no results message
            if (filteredCards.length === 0) {
                if (!document.querySelector('.no-results-message')) {
                    const noResults = document.createElement('div');
                    noResults.className = 'no-results-message';
                    noResults.innerHTML = '<p>Няма намерени парфюмеристи, които да отговарят на търсенето.</p>';
                    perfumersGrid.appendChild(noResults);
                }
            } else {
                const noResults = document.querySelector('.no-results-message');
                if (noResults) {
                    noResults.remove();
                }
            }
        }
        
        searchInput.addEventListener('input', filterAndSort);
        sortSelect.addEventListener('change', filterAndSort);
    }
});
</script>

<?php get_footer(); ?>