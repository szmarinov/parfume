<?php
/**
 * Template for single parfume posts
 * ПОЧИСТЕНА ВЕРСИЯ БЕЗ CSS - СТИЛОВЕТЕ СА В single-parfume.css
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();

while (have_posts()): the_post();

// Get meta data
$rating = get_post_meta(get_the_ID(), '_parfume_rating', true);
$gender_text = get_post_meta(get_the_ID(), '_parfume_gender_text', true);
$release_year = get_post_meta(get_the_ID(), '_parfume_release_year', true);
$longevity = get_post_meta(get_the_ID(), '_parfume_longevity', true);
$sillage = get_post_meta(get_the_ID(), '_parfume_sillage', true);
$bottle_size = get_post_meta(get_the_ID(), '_parfume_bottle_size', true);
$aroma_chart = get_post_meta(get_the_ID(), '_parfume_aroma_chart', true);
$pros = get_post_meta(get_the_ID(), '_parfume_pros', true);
$cons = get_post_meta(get_the_ID(), '_parfume_cons', true);
$stores = get_post_meta(get_the_ID(), '_parfume_stores', true);
$gallery_images = get_post_meta(get_the_ID(), '_parfume_gallery', true);

// Get taxonomies
$brands = wp_get_post_terms(get_the_ID(), 'marki');
$genders = wp_get_post_terms(get_the_ID(), 'gender');
$aroma_types = wp_get_post_terms(get_the_ID(), 'aroma_type');
$seasons = wp_get_post_terms(get_the_ID(), 'season');
$intensities = wp_get_post_terms(get_the_ID(), 'intensity');
$notes = wp_get_post_terms(get_the_ID(), 'notes');
$perfumers = wp_get_post_terms(get_the_ID(), 'perfumer');

// Generate links
$brand_names = array();
if ($brands) {
    foreach ($brands as $brand) {
        $brand_names[] = '<a href="' . get_term_link($brand) . '">' . esc_html($brand->name) . '</a>';
    }
}

$gender_list = array();
if ($genders) {
    foreach ($genders as $gender) {
        $gender_list[] = '<a href="' . get_term_link($gender) . '">' . esc_html($gender->name) . '</a>';
    }
}

$aroma_type_list = array();
if ($aroma_types) {
    foreach ($aroma_types as $aroma_type) {
        $aroma_type_list[] = '<a href="' . get_term_link($aroma_type) . '">' . esc_html($aroma_type->name) . '</a>';
    }
}

$seasons_list = array();
if ($seasons) {
    foreach ($seasons as $season) {
        $seasons_list[] = '<a href="' . get_term_link($season) . '">' . esc_html($season->name) . '</a>';
    }
}

$intensities_list = array();
if ($intensities) {
    foreach ($intensities as $intensity) {
        $intensities_list[] = '<a href="' . get_term_link($intensity) . '">' . esc_html($intensity->name) . '</a>';
    }
}
?>

<article id="post-<?php the_ID(); ?>" <?php post_class('parfume-single'); ?>>
    <div class="parfume-container">
        <div class="parfume-main-content">
            <header class="parfume-header">
                <!-- Галерия секция -->
                <div class="parfume-gallery">
                    <?php if (has_post_thumbnail()): ?>
                        <div class="parfume-featured-image">
                            <?php the_post_thumbnail('large'); ?>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Допълнителни снимки от галерията -->
                    <?php if (!empty($gallery_images) && is_array($gallery_images)): ?>
                        <div class="parfume-additional-images">
                            <div class="additional-images-grid">
                                <?php foreach ($gallery_images as $image_id): ?>
                                    <?php if ($image_id): ?>
                                        <div class="additional-image-item">
                                            <img src="<?php echo wp_get_attachment_image_url($image_id, 'medium'); ?>" 
                                                 alt="<?php echo get_post_meta($image_id, '_wp_attachment_image_alt', true); ?>"
                                                 onclick="openImageModal('<?php echo wp_get_attachment_image_url($image_id, 'large'); ?>')">
                                        </div>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="parfume-summary">
                    <h1 class="parfume-title"><?php the_title(); ?></h1>
                    
                    <?php if (!empty($brand_names)): ?>
                        <div class="parfume-brand">
                            <span class="brand-label"><?php _e('Марка:', 'parfume-reviews'); ?></span>
                            <?php echo implode(', ', $brand_names); ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($rating)): ?>
                        <div class="parfume-rating">
                            <div class="rating-stars" data-rating="<?php echo esc_attr($rating); ?>">
                                <?php
                                for ($i = 1; $i <= 5; $i++) {
                                    $class = $i <= round($rating) ? 'star filled' : 'star';
                                    echo '<span class="' . $class . '">★</span>';
                                }
                                ?>
                            </div>
                            <span class="rating-text"><?php echo number_format($rating, 1); ?>/5</span>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Quick Info Grid -->
                    <div class="parfume-quick-info">
                        <?php if (!empty($gender_list)): ?>
                            <div class="info-item">
                                <span class="info-label"><?php _e('Пол:', 'parfume-reviews'); ?></span>
                                <span class="info-value"><?php echo implode(', ', $gender_list); ?></span>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($release_year)): ?>
                            <div class="info-item">
                                <span class="info-label"><?php _e('Година:', 'parfume-reviews'); ?></span>
                                <span class="info-value"><?php echo esc_html($release_year); ?></span>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($aroma_type_list)): ?>
                            <div class="info-item">
                                <span class="info-label"><?php _e('Тип арома:', 'parfume-reviews'); ?></span>
                                <span class="info-value"><?php echo implode(', ', $aroma_type_list); ?></span>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($seasons_list)): ?>
                            <div class="info-item">
                                <span class="info-label"><?php _e('Сезон:', 'parfume-reviews'); ?></span>
                                <span class="info-value"><?php echo implode(', ', $seasons_list); ?></span>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($intensities_list)): ?>
                            <div class="info-item">
                                <span class="info-label"><?php _e('Интензивност:', 'parfume-reviews'); ?></span>
                                <span class="info-value"><?php echo implode(', ', $intensities_list); ?></span>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($longevity)): ?>
                            <div class="info-item">
                                <span class="info-label"><?php _e('Издръжливост:', 'parfume-reviews'); ?></span>
                                <span class="info-value"><?php echo esc_html($longevity); ?></span>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($sillage)): ?>
                            <div class="info-item">
                                <span class="info-label"><?php _e('Силаж:', 'parfume-reviews'); ?></span>
                                <span class="info-value"><?php echo esc_html($sillage); ?></span>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($bottle_size)): ?>
                            <div class="info-item">
                                <span class="info-label"><?php _e('Размер:', 'parfume-reviews'); ?></span>
                                <span class="info-value"><?php echo esc_html($bottle_size); ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </header>

            <!-- Content sections -->
            <div class="parfume-content">
                <!-- Tab Navigation -->
                <nav class="content-tabs">
                    <a href="#description" class="tab-link active"><?php _e('Описание', 'parfume-reviews'); ?></a>
                    <?php if (!empty($notes)): ?>
                        <a href="#notes" class="tab-link"><?php _e('Нотки', 'parfume-reviews'); ?></a>
                    <?php endif; ?>
                    <?php if (!empty($perfumers)): ?>
                        <a href="#perfumer" class="tab-link"><?php _e('Парфюмер', 'parfume-reviews'); ?></a>
                    <?php endif; ?>
                    <?php if (!empty($pros) || !empty($cons)): ?>
                        <a href="#reviews" class="tab-link"><?php _e('Ревю', 'parfume-reviews'); ?></a>
                    <?php endif; ?>
                </nav>

                <!-- Description Section -->
                <section id="description" class="content-section active">
                    <h2><?php _e('Описание', 'parfume-reviews'); ?></h2>
                    <div class="section-content">
                        <?php the_content(); ?>
                        
                        <?php if (!empty($aroma_chart) && is_array($aroma_chart)): ?>
                            <div class="aroma-chart-container">
                                <h3><?php _e('Профил на аромата', 'parfume-reviews'); ?></h3>
                                <div class="aroma-chart">
                                    <?php foreach ($aroma_chart as $aspect => $value): ?>
                                        <div class="chart-item">
                                            <label><?php echo esc_html(ucfirst($aspect)); ?></label>
                                            <div class="chart-bar">
                                                <div class="chart-fill" style="width: <?php echo intval($value) * 10; ?>%"></div>
                                            </div>
                                            <span class="chart-value"><?php echo intval($value); ?>/10</span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </section>

                <!-- Notes Section -->
                <?php if (!empty($notes)): ?>
                    <section id="notes" class="content-section">
                        <h2><?php _e('Ароматни нотки', 'parfume-reviews'); ?></h2>
                        <div class="section-content">
                            <div class="notes-grid">
                                <?php foreach ($notes as $note): ?>
                                    <a href="<?php echo get_term_link($note); ?>" class="note-item">
                                        <?php echo esc_html($note->name); ?>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </section>
                <?php endif; ?>

                <!-- Perfumer Section -->
                <?php if (!empty($perfumers)): ?>
                    <section id="perfumer" class="content-section">
                        <h2><?php _e('Парфюмер', 'parfume-reviews'); ?></h2>
                        <div class="section-content">
                            <?php foreach ($perfumers as $perfumer): ?>
                                <div class="perfumer-info">
                                    <h3><a href="<?php echo get_term_link($perfumer); ?>"><?php echo esc_html($perfumer->name); ?></a></h3>
                                    <?php if (!empty($perfumer->description)): ?>
                                        <p><?php echo esc_html($perfumer->description); ?></p>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </section>
                <?php endif; ?>

                <!-- Reviews Section -->
                <?php if (!empty($pros) || !empty($cons)): ?>
                    <section id="reviews" class="content-section">
                        <h2><?php _e('Нашето мнение', 'parfume-reviews'); ?></h2>
                        <div class="section-content">
                            <div class="pros-cons-grid">
                                <?php if (!empty($pros)): ?>
                                    <div class="pros-section">
                                        <h3><?php _e('Предимства', 'parfume-reviews'); ?></h3>
                                        <ul class="pros-list">
                                            <?php foreach (explode("\n", $pros) as $pro): ?>
                                                <?php if (trim($pro)): ?>
                                                    <li><?php echo esc_html(trim($pro)); ?></li>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($cons)): ?>
                                    <div class="cons-section">
                                        <h3><?php _e('Недостатъци', 'parfume-reviews'); ?></h3>
                                        <ul class="cons-list">
                                            <?php foreach (explode("\n", $cons) as $con): ?>
                                                <?php if (trim($con)): ?>
                                                    <li><?php echo esc_html(trim($con)); ?></li>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </section>
                <?php endif; ?>
            </div>
        </div>

        <!-- Stores Sidebar -->
        <?php if (!empty($stores) && is_array($stores)): ?>
            <aside class="parfume-stores-sidebar">
                <div class="stores-container">
                    <h3 class="stores-title"><?php _e('Къде да купя', 'parfume-reviews'); ?></h3>
                    
                    <div class="store-list">
                        <?php foreach ($stores as $store): ?>
                            <?php if (!empty($store['name'])): ?>
                                <div class="store-item">
                                    <div class="store-header">
                                        <div class="store-info">
                                            <?php if (!empty($store['logo'])): ?>
                                                <div class="store-logo">
                                                    <img src="<?php echo esc_url($store['logo']); ?>" 
                                                         alt="<?php echo esc_attr($store['name']); ?>">
                                                </div>
                                            <?php endif; ?>
                                            <div class="store-details">
                                                <div class="store-name"><?php echo esc_html($store['name']); ?></div>
                                                <?php if (!empty($store['size'])): ?>
                                                    <div class="store-size"><?php echo esc_html($store['size']); ?></div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        
                                        <?php if (!empty($store['availability'])): ?>
                                            <div class="availability-badge <?php echo $store['availability'] === 'in_stock' ? 'in-stock' : 'out-of-stock'; ?>">
                                                <?php echo $store['availability'] === 'in_stock' ? '✓ Наличен' : '✗ Няма'; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="store-pricing">
                                        <?php if (!empty($store['price'])): ?>
                                            <div class="price-display">
                                                <?php echo esc_html($store['price']); ?>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <?php if (!empty($store['affiliate_url']) || !empty($store['url'])): ?>
                                            <div class="store-actions">
                                                <a href="<?php echo esc_url($store['affiliate_url'] ?: $store['url']); ?>" 
                                                   target="_blank" 
                                                   rel="nofollow" 
                                                   class="store-button">
                                                    <?php _e('Към магазина', 'parfume-reviews'); ?>
                                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none">
                                                        <path d="M7 17L17 7M17 7H7M17 7V17" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                                    </svg>
                                                </a>
                                                
                                                <?php if (!empty($store['promo_code'])): ?>
                                                    <div class="promo-code">
                                                        <span class="promo-text"><?php echo esc_html($store['promo_text'] ?: 'Промо код:'); ?></span>
                                                        <code class="promo-code-value" onclick="copyPromoCode('<?php echo esc_js($store['promo_code']); ?>')">
                                                            <?php echo esc_html($store['promo_code']); ?>
                                                        </code>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <?php if (!empty($store['shipping_cost'])): ?>
                                        <div class="shipping-info">
                                            <small><?php _e('Доставка:', 'parfume-reviews'); ?> <?php echo esc_html($store['shipping_cost']); ?></small>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            </aside>
        <?php endif; ?>
    </div>
</article>

<!-- Image Modal -->
<div id="imageModal" class="image-modal" onclick="closeImageModal()">
    <span class="close-modal">&times;</span>
    <img class="modal-content" id="modalImage">
</div>

<script>
// Image modal functionality
function openImageModal(imageSrc) {
    const modal = document.getElementById('imageModal');
    const modalImg = document.getElementById('modalImage');
    modal.style.display = 'block';
    modalImg.src = imageSrc;
}

function closeImageModal() {
    document.getElementById('imageModal').style.display = 'none';
}

// Promo code copy functionality
function copyPromoCode(code) {
    navigator.clipboard.writeText(code).then(function() {
        // Show success message
        const event = new CustomEvent('promoCodeCopied', { detail: code });
        document.dispatchEvent(event);
        
        // Visual feedback
        const codeElement = event.target;
        const originalText = codeElement.textContent;
        codeElement.textContent = 'Копирано!';
        codeElement.style.background = '#28a745';
        codeElement.style.color = 'white';
        
        setTimeout(() => {
            codeElement.textContent = originalText;
            codeElement.style.background = '';
            codeElement.style.color = '';
        }, 1500);
    });
}

// Tab functionality
document.addEventListener('DOMContentLoaded', function() {
    const tabLinks = document.querySelectorAll('.tab-link');
    const contentSections = document.querySelectorAll('.content-section');
    
    tabLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Remove active class from all tabs and sections
            tabLinks.forEach(tab => tab.classList.remove('active'));
            contentSections.forEach(section => section.classList.remove('active'));
            
            // Add active class to clicked tab
            this.classList.add('active');
            
            // Show corresponding section
            const targetId = this.getAttribute('href').substring(1);
            const targetSection = document.getElementById(targetId);
            if (targetSection) {
                targetSection.classList.add('active');
            }
        });
    });
});

// Close modal on Escape key
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeImageModal();
    }
});
</script>

<?php endwhile; ?>

<?php get_footer(); ?>