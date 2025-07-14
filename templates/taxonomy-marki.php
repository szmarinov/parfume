<?php
/**
 * Template for Brand Taxonomy pages
 * 
 * Този файл обработва:
 * - Archive страница с всички марки (/parfiumi/marki/)
 * - Single страница на конкретна марка (/parfiumi/marki/dior/) - пренасочва към single-marki.php
 * 
 * Файл: templates/taxonomy-marki.php
 * АКТУАЛИЗИРАНА ВЕРСИЯ
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header(); 

$current_term = get_queried_object();
$is_single_brand = $current_term && isset($current_term->name) && !empty($current_term->name);

// Ако е конкретна марка, използваме single-marki.php template
if ($is_single_brand) {
    // Проверяваме дали single-marki.php съществува
    $single_brand_template = PARFUME_REVIEWS_PLUGIN_DIR . 'templates/single-marki.php';
    if (file_exists($single_brand_template)) {
        include $single_brand_template;
        get_footer();
        return;
    }
    // Ако single-marki.php не съществува, продължаваме с този template
}

// Archive page - показваме всички марки
?>

<div class="brands-archive-page marki-taxonomy-page">
    <!-- Breadcrumb navigation -->
    <div class="container">
        <nav class="breadcrumb">
            <a href="<?php echo home_url(); ?>"><?php _e('Начало', 'parfume-reviews'); ?></a>
            <span class="separator"> › </span>
            <a href="<?php echo home_url('/parfiumi/'); ?>"><?php _e('Парфюми', 'parfume-reviews'); ?></a>
            <span class="separator"> › </span>
            <span class="current"><?php _e('Марки', 'parfume-reviews'); ?></span>
        </nav>
    </div>

    <!-- Archive Header -->
    <div class="archive-header">
        <div class="container">
            <h1 class="archive-title"><?php _e('Всички Марки', 'parfume-reviews'); ?></h1>
            <div class="archive-description">
                <p><?php _e('Открийте парфюми по техните марки. Всяка марка носи своя уникална история и стил.', 'parfume-reviews'); ?></p>
            </div>
        </div>
    </div>

    <!-- Archive Content -->
    <div class="archive-content">
        <div class="container">
            
            <!-- Search and Filter Section -->
            <div class="brands-controls">
                <div class="brands-search">
                    <input type="text" id="brands-search" placeholder="<?php _e('Търсене в марките...', 'parfume-reviews'); ?>">
                    <button type="button" class="search-button">
                        <span class="dashicons dashicons-search"></span>
                    </button>
                </div>
                
                <div class="brands-sort">
                    <label for="brands-sort-select"><?php _e('Сортиране:', 'parfume-reviews'); ?></label>
                    <select id="brands-sort-select">
                        <option value="name"><?php _e('По име', 'parfume-reviews'); ?></option>
                        <option value="count"><?php _e('По брой парфюми', 'parfume-reviews'); ?></option>
                        <option value="popular"><?php _e('По популярност', 'parfume-reviews'); ?></option>
                    </select>
                </div>
            </div>

            <!-- Brands Grid -->
            <?php
            // Query за всички марки
            $brands = get_terms(array(
                'taxonomy' => 'marki',
                'hide_empty' => true,
                'orderby' => 'name',
                'order' => 'ASC'
            ));
            
            if (!empty($brands) && !is_wp_error($brands)): ?>
                
                <div class="brands-stats">
                    <div class="stat-item">
                        <span class="stat-number"><?php echo count($brands); ?></span>
                        <span class="stat-label"><?php _e('марки', 'parfume-reviews'); ?></span>
                    </div>
                    
                    <?php
                    // Изчисляваме общо парфюми
                    $total_perfumes = 0;
                    foreach ($brands as $brand) {
                        $total_perfumes += $brand->count;
                    }
                    ?>
                    <div class="stat-item">
                        <span class="stat-number"><?php echo $total_perfumes; ?></span>
                        <span class="stat-label"><?php _e('парфюма общо', 'parfume-reviews'); ?></span>
                    </div>
                </div>

                <div class="brands-grid" id="brands-grid">
                    <?php foreach ($brands as $brand): ?>
                        <div class="brand-card" data-brand-name="<?php echo esc_attr(strtolower($brand->name)); ?>" data-brand-count="<?php echo esc_attr($brand->count); ?>">
                            <div class="brand-card-inner">
                                
                                <!-- Brand Logo -->
                                <div class="brand-logo">
                                    <?php
                                    $brand_logo_id = get_term_meta($brand->term_id, 'marki-image-id', true);
                                    if ($brand_logo_id):
                                    ?>
                                        <a href="<?php echo get_term_link($brand); ?>">
                                            <?php echo wp_get_attachment_image($brand_logo_id, 'medium', false, array('alt' => $brand->name)); ?>
                                        </a>
                                    <?php else: ?>
                                        <a href="<?php echo get_term_link($brand); ?>" class="brand-logo-placeholder">
                                            <span class="brand-initial"><?php echo esc_html(mb_substr($brand->name, 0, 1)); ?></span>
                                        </a>
                                    <?php endif; ?>
                                </div>

                                <!-- Brand Info -->
                                <div class="brand-info">
                                    <h3 class="brand-name">
                                        <a href="<?php echo get_term_link($brand); ?>">
                                            <?php echo esc_html($brand->name); ?>
                                        </a>
                                    </h3>
                                    
                                    <div class="brand-meta">
                                        <span class="brand-count">
                                            <?php printf(_n('%d парфюм', '%d парфюма', $brand->count, 'parfume-reviews'), $brand->count); ?>
                                        </span>
                                        
                                        <?php
                                        // Изчисляваме средния рейтинг за тази марка
                                        $brand_perfumes = get_posts(array(
                                            'post_type' => 'parfume',
                                            'posts_per_page' => -1,
                                            'fields' => 'ids',
                                            'tax_query' => array(
                                                array(
                                                    'taxonomy' => 'marki',
                                                    'field' => 'term_id',
                                                    'terms' => $brand->term_id,
                                                ),
                                            ),
                                        ));
                                        
                                        if (!empty($brand_perfumes)) {
                                            $total_rating = 0;
                                            $rated_count = 0;
                                            
                                            foreach ($brand_perfumes as $perfume_id) {
                                                $rating = get_post_meta($perfume_id, '_parfume_rating', true);
                                                if (!empty($rating) && is_numeric($rating)) {
                                                    $total_rating += floatval($rating);
                                                    $rated_count++;
                                                }
                                            }
                                            
                                            if ($rated_count > 0) {
                                                $average_rating = $total_rating / $rated_count;
                                                ?>
                                                <div class="brand-rating">
                                                    <?php parfume_reviews_display_star_rating($average_rating, 5, false); ?>
                                                    <span class="rating-text"><?php echo number_format($average_rating, 1); ?></span>
                                                </div>
                                                <?php
                                            }
                                        }
                                        ?>
                                    </div>
                                    
                                    <?php if (!empty($brand->description)): ?>
                                        <div class="brand-excerpt">
                                            <?php echo wp_trim_words($brand->description, 15, '...'); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <!-- Brand Actions -->
                                <div class="brand-actions">
                                    <a href="<?php echo get_term_link($brand); ?>" class="brand-link-primary">
                                        <?php _e('Разгледай парфюмите', 'parfume-reviews'); ?>
                                        <span class="dashicons dashicons-arrow-right-alt2"></span>
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

            <?php else: ?>
                <div class="no-brands-message">
                    <div class="no-results-content">
                        <h3><?php _e('Няма намерени марки', 'parfume-reviews'); ?></h3>
                        <p><?php _e('Все още няма добавени марки в базата данни.', 'parfume-reviews'); ?></p>
                        <a href="<?php echo home_url('/parfiumi/'); ?>" class="button">
                            <?php _e('Разгледайте всички парфюми', 'parfume-reviews'); ?>
                        </a>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Popular Brands Section -->
            <?php if (!empty($brands)): ?>
                <div class="popular-brands-section">
                    <h2><?php _e('Най-популярни марки', 'parfume-reviews'); ?></h2>
                    
                    <?php
                    // Сортираме марките по брой парфюми
                    usort($brands, function($a, $b) {
                        return $b->count - $a->count;
                    });
                    
                    $popular_brands = array_slice($brands, 0, 6);
                    ?>
                    
                    <div class="popular-brands-grid">
                        <?php foreach ($popular_brands as $brand): ?>
                            <div class="popular-brand-item">
                                <div class="popular-brand-logo">
                                    <?php
                                    $brand_logo_id = get_term_meta($brand->term_id, 'marki-image-id', true);
                                    if ($brand_logo_id):
                                    ?>
                                        <a href="<?php echo get_term_link($brand); ?>">
                                            <?php echo wp_get_attachment_image($brand_logo_id, 'thumbnail', false, array('alt' => $brand->name)); ?>
                                        </a>
                                    <?php else: ?>
                                        <a href="<?php echo get_term_link($brand); ?>" class="popular-brand-placeholder">
                                            <span><?php echo esc_html(mb_substr($brand->name, 0, 2)); ?></span>
                                        </a>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="popular-brand-info">
                                    <h4 class="popular-brand-name">
                                        <a href="<?php echo get_term_link($brand); ?>">
                                            <?php echo esc_html($brand->name); ?>
                                        </a>
                                    </h4>
                                    <span class="popular-brand-count">
                                        <?php printf(_n('%d парфюм', '%d парфюма', $brand->count, 'parfume-reviews'), $brand->count); ?>
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<style>
/* Brands Archive Page Styles */
.brands-archive-page {
    background: #f8f9fa;
}

.archive-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 60px 0;
    text-align: center;
}

.archive-title {
    font-size: 3rem;
    margin-bottom: 20px;
    font-weight: 700;
}

.archive-description {
    font-size: 1.2rem;
    opacity: 0.9;
    max-width: 600px;
    margin: 0 auto;
}

.archive-content {
    padding: 60px 0;
}

.brands-controls {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 40px;
    padding: 30px;
    background: white;
    border-radius: 15px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.1);
    flex-wrap: wrap;
    gap: 20px;
}

.brands-search {
    display: flex;
    align-items: center;
    position: relative;
    flex: 1;
    min-width: 300px;
}

.brands-search input {
    width: 100%;
    padding: 12px 50px 12px 20px;
    border: 2px solid #e9ecef;
    border-radius: 25px;
    font-size: 1rem;
    transition: all 0.3s ease;
}

.brands-search input:focus {
    outline: none;
    border-color: #0073aa;
    box-shadow: 0 0 0 3px rgba(0, 115, 170, 0.1);
}

.search-button {
    position: absolute;
    right: 5px;
    background: #0073aa;
    color: white;
    border: none;
    padding: 8px;
    border-radius: 50%;
    cursor: pointer;
    transition: all 0.3s ease;
}

.search-button:hover {
    background: #005a87;
    transform: scale(1.1);
}

.brands-sort {
    display: flex;
    align-items: center;
    gap: 10px;
}

.brands-sort label {
    font-weight: 600;
    color: #333;
}

.brands-sort select {
    padding: 10px 15px;
    border: 2px solid #e9ecef;
    border-radius: 8px;
    background: white;
    font-size: 1rem;
    cursor: pointer;
}

.brands-stats {
    display: flex;
    justify-content: center;
    gap: 50px;
    margin-bottom: 50px;
    flex-wrap: wrap;
}

.stat-item {
    text-align: center;
    background: white;
    padding: 25px 35px;
    border-radius: 15px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.1);
    border-top: 4px solid #0073aa;
}

.stat-number {
    display: block;
    font-size: 2.5rem;
    font-weight: bold;
    color: #0073aa;
    line-height: 1;
}

.stat-label {
    display: block;
    font-size: 1rem;
    color: #666;
    margin-top: 8px;
    font-weight: 500;
}

.brands-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 30px;
    margin-bottom: 70px;
}

.brand-card {
    background: white;
    border-radius: 20px;
    overflow: hidden;
    transition: all 0.3s ease;
    box-shadow: 0 8px 25px rgba(0,0,0,0.1);
    border: 1px solid #eee;
}

.brand-card:hover {
    transform: translateY(-10px);
    box-shadow: 0 15px 40px rgba(0,0,0,0.15);
    border-color: #0073aa;
}

.brand-card-inner {
    padding: 30px;
    text-align: center;
}

.brand-logo {
    margin-bottom: 25px;
    height: 100px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.brand-logo img {
    max-width: 120px;
    max-height: 80px;
    object-fit: contain;
    transition: all 0.3s ease;
}

.brand-logo a:hover img {
    transform: scale(1.05);
}

.brand-logo-placeholder,
.popular-brand-placeholder {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 80px;
    height: 80px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 50%;
    font-size: 1.8rem;
    font-weight: bold;
    text-decoration: none;
    transition: all 0.3s ease;
    margin: 0 auto;
}

.brand-logo-placeholder:hover,
.popular-brand-placeholder:hover {
    transform: scale(1.1);
    box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
}

.brand-info {
    margin-bottom: 25px;
}

.brand-name {
    font-size: 1.5rem;
    margin-bottom: 15px;
    font-weight: 700;
}

.brand-name a {
    color: #333;
    text-decoration: none;
    transition: color 0.3s ease;
}

.brand-name a:hover {
    color: #0073aa;
}

.brand-meta {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 20px;
    margin-bottom: 15px;
    flex-wrap: wrap;
}

.brand-count {
    background: #e9f4ff;
    color: #0073aa;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 0.9rem;
    font-weight: 600;
}

.brand-rating {
    display: flex;
    align-items: center;
    gap: 8px;
}

.rating-text {
    font-weight: 600;
    color: #333;
    font-size: 0.9rem;
}

.brand-excerpt {
    color: #666;
    font-size: 0.95rem;
    line-height: 1.5;
    margin-top: 15px;
}

.brand-actions {
    margin-top: 20px;
}

.brand-link-primary {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: linear-gradient(135deg, #0073aa 0%, #005a87 100%);
    color: white;
    padding: 12px 25px;
    border-radius: 25px;
    text-decoration: none;
    font-weight: 600;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(0, 115, 170, 0.3);
}

.brand-link-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(0, 115, 170, 0.4);
    background: linear-gradient(135deg, #005a87 0%, #004066 100%);
}

.popular-brands-section {
    background: white;
    border-radius: 20px;
    padding: 50px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
}

.popular-brands-section h2 {
    text-align: center;
    font-size: 2.2rem;
    margin-bottom: 40px;
    color: #333;
}

.popular-brands-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 25px;
}

.popular-brand-item {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 20px;
    background: #f8f9fa;
    border-radius: 15px;
    transition: all 0.3s ease;
    border: 1px solid #eee;
}

.popular-brand-item:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.1);
    border-color: #0073aa;
    background: white;
}

.popular-brand-logo {
    flex-shrink: 0;
    width: 60px;
    height: 60px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.popular-brand-logo img {
    max-width: 50px;
    max-height: 50px;
    object-fit: contain;
}

.popular-brand-placeholder {
    width: 50px;
    height: 50px;
    font-size: 1.2rem;
}

.popular-brand-info {
    flex: 1;
}

.popular-brand-name {
    margin: 0 0 5px;
    font-size: 1.1rem;
    font-weight: 600;
}

.popular-brand-name a {
    color: #333;
    text-decoration: none;
    transition: color 0.3s ease;
}

.popular-brand-name a:hover {
    color: #0073aa;
}

.popular-brand-count {
    color: #666;
    font-size: 0.9rem;
}

.no-brands-message {
    text-align: center;
    padding: 80px 20px;
    background: white;
    border-radius: 20px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
}

.no-results-content h3 {
    color: #666;
    margin-bottom: 15px;
    font-size: 1.8rem;
}

.no-results-content p {
    color: #888;
    margin-bottom: 30px;
    font-size: 1.1rem;
}

.breadcrumb {
    margin-bottom: 0;
    padding: 20px 0;
    font-size: 0.95rem;
}

.breadcrumb a {
    color: #0073aa;
    text-decoration: none;
    transition: color 0.3s ease;
}

.breadcrumb a:hover {
    color: #005a87;
    text-decoration: underline;
}

.breadcrumb .separator {
    color: #999;
    margin: 0 10px;
}

.breadcrumb .current {
    color: #666;
    font-weight: 600;
}

/* Responsive Design */
@media (max-width: 1024px) {
    .brands-grid {
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 25px;
    }
    
    .brands-controls {
        flex-direction: column;
        align-items: stretch;
    }
    
    .brands-search {
        min-width: 100%;
    }
}

@media (max-width: 768px) {
    .archive-title {
        font-size: 2.2rem;
    }
    
    .brands-grid {
        grid-template-columns: 1fr;
        gap: 20px;
    }
    
    .brands-stats {
        gap: 20px;
    }
    
    .stat-item {
        padding: 20px 25px;
    }
    
    .brand-card-inner {
        padding: 25px 20px;
    }
    
    .popular-brands-grid {
        grid-template-columns: 1fr;
    }
    
    .popular-brand-item {
        padding: 15px;
    }
    
    .popular-brands-section {
        padding: 30px 20px;
    }
}

@media (max-width: 480px) {
    .archive-header {
        padding: 40px 0;
    }
    
    .archive-title {
        font-size: 1.8rem;
    }
    
    .brands-controls {
        padding: 20px;
    }
    
    .stat-number {
        font-size: 2rem;
    }
}

/* JavaScript Enhancement Styles */
.brand-card.hidden {
    display: none;
}

.brands-grid.sorting {
    opacity: 0.7;
    pointer-events: none;
}
</style>

<script>
// JavaScript for brands filtering and sorting
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('brands-search');
    const sortSelect = document.getElementById('brands-sort-select');
    const brandsGrid = document.getElementById('brands-grid');
    const brandCards = brandsGrid ? brandsGrid.querySelectorAll('.brand-card') : [];

    // Search functionality
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            
            brandCards.forEach(card => {
                const brandName = card.dataset.brandName || '';
                const shouldShow = brandName.includes(searchTerm);
                card.classList.toggle('hidden', !shouldShow);
            });
        });
    }

    // Sort functionality
    if (sortSelect) {
        sortSelect.addEventListener('change', function() {
            const sortBy = this.value;
            const cardsArray = Array.from(brandCards);
            
            brandsGrid.classList.add('sorting');
            
            cardsArray.sort((a, b) => {
                switch (sortBy) {
                    case 'name':
                        return a.dataset.brandName.localeCompare(b.dataset.brandName);
                    case 'count':
                        return parseInt(b.dataset.brandCount) - parseInt(a.dataset.brandCount);
                    case 'popular':
                        return parseInt(b.dataset.brandCount) - parseInt(a.dataset.brandCount);
                    default:
                        return 0;
                }
            });
            
            // Re-append sorted cards
            cardsArray.forEach(card => brandsGrid.appendChild(card));
            
            setTimeout(() => {
                brandsGrid.classList.remove('sorting');
            }, 300);
        });
    }
});
</script>

<?php get_footer(); ?>