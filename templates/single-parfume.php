<?php
/**
 * Template for single parfume posts
 * ПРЕРАБОТЕНА ВЕРСИЯ С НОВ STORES SIDEBAR ДИЗАЙН
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

// Check mobile settings
$settings = get_option('parfume_reviews_settings', array());
$mobile_fixed_panel = isset($settings['mobile_fixed_panel']) ? $settings['mobile_fixed_panel'] : 1;
$mobile_show_close_btn = isset($settings['mobile_show_close_btn']) ? $settings['mobile_show_close_btn'] : 0;
$mobile_z_index = isset($settings['mobile_z_index']) ? $settings['mobile_z_index'] : 9999;
$mobile_bottom_offset = isset($settings['mobile_bottom_offset']) ? $settings['mobile_bottom_offset'] : 0;

// Check individual post settings
$post_mobile_override = get_post_meta(get_the_ID(), '_parfume_mobile_fixed_stores', true);
if ($post_mobile_override !== '') {
    $mobile_fixed_panel = (bool) $post_mobile_override;
}
?>

<div class="single-parfume-container">
    <!-- Main Layout: Column 1 (Content) + Column 2 (Stores Sidebar) -->
    <div class="parfume-layout">
        
        <!-- Column 1: Main Content -->
        <div class="parfume-main-content">
            <article id="post-<?php the_ID(); ?>" <?php post_class('parfume-article'); ?>>
                
                <!-- Header Section -->
                <header class="parfume-header">
                    <div class="header-content">
                        <div class="parfume-image">
                            <?php if (has_post_thumbnail()): ?>
                                <img src="<?php echo get_the_post_thumbnail_url(get_the_ID(), 'large'); ?>" 
                                     alt="<?php echo esc_attr(get_the_title()); ?>" 
                                     onclick="openImageModal(this.src)" />
                            <?php else: ?>
                                <div class="placeholder-image">
                                    <span class="dashicons dashicons-admin-media"></span>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="parfume-details">
                            <h1 class="parfume-title"><?php the_title(); ?></h1>
                            
                            <?php if (!empty($brand_names)): ?>
                                <div class="parfume-brand">
                                    <?php echo implode(', ', $brand_names); ?>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($rating)): ?>
                                <div class="parfume-rating">
                                    <div class="stars">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <span class="star <?php echo $i <= $rating ? 'filled' : ''; ?>">★</span>
                                        <?php endfor; ?>
                                    </div>
                                    <span class="rating-value"><?php echo esc_html($rating); ?>/5</span>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Parfume Info Grid -->
                            <div class="parfume-info-grid">
                                <?php if (!empty($release_year)): ?>
                                    <div class="info-item">
                                        <span class="info-label"><?php _e('Година:', 'parfume-reviews'); ?></span>
                                        <span class="info-value"><?php echo esc_html($release_year); ?></span>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($gender_list)): ?>
                                    <div class="info-item">
                                        <span class="info-label"><?php _e('Пол:', 'parfume-reviews'); ?></span>
                                        <span class="info-value"><?php echo implode(', ', $gender_list); ?></span>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($aroma_type_list)): ?>
                                    <div class="info-item">
                                        <span class="info-label"><?php _e('Ароматна група:', 'parfume-reviews'); ?></span>
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
                    </div>
                </header>

                <!-- Main Content Sections -->
                <div class="parfume-content">
                    
                    <!-- Description Section -->
                    <?php if (get_the_content()): ?>
                        <section class="description-section">
                            <h2><?php _e('Описание', 'parfume-reviews'); ?></h2>
                            <div class="description-content">
                                <?php the_content(); ?>
                            </div>
                        </section>
                    <?php endif; ?>

                    <!-- Notes Section -->
                    <?php if (!empty($notes)): ?>
                        <section class="notes-section">
                            <h2><?php _e('Ароматни нотки', 'parfume-reviews'); ?></h2>
                            <div class="notes-grid">
                                <?php foreach ($notes as $note): ?>
                                    <a href="<?php echo get_term_link($note); ?>" class="note-item">
                                        <?php echo esc_html($note->name); ?>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </section>
                    <?php endif; ?>

                    <!-- Perfumer Section -->
                    <?php if (!empty($perfumers)): ?>
                        <section class="perfumer-section">
                            <h2><?php _e('Парфюмер', 'parfume-reviews'); ?></h2>
                            <div class="perfumer-content">
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

                    <!-- Aroma Chart Section -->
                    <?php if (!empty($aroma_chart)): ?>
                        <section class="aroma-chart-section">
                            <h2><?php _e('Ароматна диаграма', 'parfume-reviews'); ?></h2>
                            <div class="aroma-chart-content">
                                <?php echo wp_kses_post($aroma_chart); ?>
                            </div>
                        </section>
                    <?php endif; ?>

                    <!-- Pros and Cons Section -->
                    <?php if (!empty($pros) || !empty($cons)): ?>
                        <section class="pros-cons-section">
                            <h2><?php _e('Плюсове и минуси', 'parfume-reviews'); ?></h2>
                            <div class="pros-cons-grid">
                                <?php if (!empty($pros)): ?>
                                    <div class="pros-section">
                                        <h3><?php _e('Плюсове', 'parfume-reviews'); ?></h3>
                                        <ul class="pros-list">
                                            <?php foreach (explode("\n", $pros) as $pro): ?>
                                                <?php if (!empty(trim($pro))): ?>
                                                    <li><?php echo esc_html(trim($pro)); ?></li>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($cons)): ?>
                                    <div class="cons-section">
                                        <h3><?php _e('Минуси', 'parfume-reviews'); ?></h3>
                                        <ul class="cons-list">
                                            <?php foreach (explode("\n", $cons) as $con): ?>
                                                <?php if (!empty(trim($con))): ?>
                                                    <li><?php echo esc_html(trim($con)); ?></li>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </section>
                    <?php endif; ?>

                    <!-- Gallery Section -->
                    <?php if (!empty($gallery_images) && is_array($gallery_images)): ?>
                        <section class="gallery-section">
                            <h2><?php _e('Галерия', 'parfume-reviews'); ?></h2>
                            <div class="parfume-gallery">
                                <?php foreach ($gallery_images as $image_id): ?>
                                    <?php $image_url = wp_get_attachment_image_url($image_id, 'medium'); ?>
                                    <?php $full_image_url = wp_get_attachment_image_url($image_id, 'full'); ?>
                                    <?php if ($image_url): ?>
                                        <div class="gallery-item">
                                            <img src="<?php echo esc_url($image_url); ?>" 
                                                 alt="<?php echo esc_attr(get_the_title()); ?>" 
                                                 onclick="openImageModal('<?php echo esc_url($full_image_url); ?>')" />
                                        </div>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        </section>
                    <?php endif; ?>

                    <!-- Related Parfumes Section -->
                    <?php 
                    $related_args = array(
                        'post_type' => 'parfume',
                        'posts_per_page' => 4,
                        'post__not_in' => array(get_the_ID()),
                        'orderby' => 'rand'
                    );

                    if (!empty($brands)) {
                        $related_args['tax_query'] = array(
                            array(
                                'taxonomy' => 'marki',
                                'field' => 'term_id',
                                'terms' => wp_list_pluck($brands, 'term_id'),
                            ),
                        );
                    }

                    $related_query = new WP_Query($related_args);
                    
                    if ($related_query->have_posts()): ?>
                        <section class="related-parfumes-section">
                            <h2><?php _e('Подобни парфюми', 'parfume-reviews'); ?></h2>
                            <div class="related-parfumes-grid">
                                <?php while ($related_query->have_posts()): $related_query->the_post(); ?>
                                    <div class="related-parfume-item">
                                        <a href="<?php the_permalink(); ?>">
                                            <?php if (has_post_thumbnail()): ?>
                                                <div class="related-parfume-image">
                                                    <?php the_post_thumbnail('medium'); ?>
                                                </div>
                                            <?php endif; ?>
                                            <div class="related-parfume-info">
                                                <h4><?php the_title(); ?></h4>
                                                <?php 
                                                $related_brands = wp_get_post_terms(get_the_ID(), 'marki');
                                                if ($related_brands): ?>
                                                    <span class="related-brand"><?php echo esc_html($related_brands[0]->name); ?></span>
                                                <?php endif; ?>
                                            </div>
                                        </a>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        </section>
                        <?php wp_reset_postdata(); ?>
                    <?php endif; ?>

                </div>
            </article>
        </div>
        
        <!-- Column 2: Stores Sidebar -->
        <?php if (!empty($stores) && is_array($stores)): ?>
            <div class="parfume-stores-sidebar <?php echo $mobile_fixed_panel ? 'mobile-fixed' : ''; ?>" 
                 data-mobile-fixed="<?php echo $mobile_fixed_panel ? 'true' : 'false'; ?>"
                 data-show-close="<?php echo $mobile_show_close_btn ? 'true' : 'false'; ?>"
                 style="--mobile-z-index: <?php echo esc_attr($mobile_z_index); ?>; --mobile-bottom-offset: <?php echo esc_attr($mobile_bottom_offset); ?>px;">
                
                <!-- Mobile Controls -->
                <div class="mobile-controls">
                    <?php if (count($stores) > 1): ?>
                        <button class="stores-toggle" aria-label="<?php _e('Показване/скриване на останалите магазини', 'parfume-reviews'); ?>">
                            <span class="dashicons dashicons-arrow-up-alt2"></span>
                        </button>
                    <?php endif; ?>
                    
                    <?php if ($mobile_show_close_btn): ?>
                        <button class="stores-close" aria-label="<?php _e('Затваряне на панела с магазини', 'parfume-reviews'); ?>">
                            <span class="dashicons dashicons-no-alt"></span>
                        </button>
                    <?php endif; ?>
                </div>
                
                <!-- Stores List -->
                <div class="stores-list">
                    <?php foreach ($stores as $index => $store): ?>
                        <?php if (!empty($store['name'])): ?>
                            <div class="store-item <?php echo $index === 0 ? 'primary-store' : 'additional-store'; ?>" data-store-index="<?php echo $index; ?>">
                                
                                <!-- Store Header -->
                                <div class="store-header">
                                    <div class="store-logo">
                                        <?php if (!empty($store['logo'])): ?>
                                            <img src="<?php echo esc_url($store['logo']); ?>" alt="<?php echo esc_attr($store['name']); ?>" />
                                        <?php else: ?>
                                            <span class="store-name-text"><?php echo esc_html($store['name']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="store-price-info">
                                        <?php 
                                        // Calculate lowest price from variants or use single price
                                        $display_price = '';
                                        $has_discount = false;
                                        $discount_percent = 0;
                                        
                                        if (!empty($store['variants']) && is_array($store['variants'])) {
                                            // Multiple variants - show lowest price
                                            $prices = array();
                                            foreach ($store['variants'] as $variant) {
                                                if (!empty($variant['price'])) {
                                                    $prices[] = floatval(preg_replace('/[^\d.]/', '', $variant['price']));
                                                }
                                            }
                                            if (!empty($prices)) {
                                                $min_price = min($prices);
                                                $display_price = number_format($min_price, 2) . ' лв';
                                            }
                                        } else {
                                            // Single variant
                                            $display_price = !empty($store['price']) ? $store['price'] : '';
                                        }
                                        
                                        // Check for discount
                                        if (!empty($store['original_price']) && !empty($store['price'])) {
                                            $original = floatval(preg_replace('/[^\d.]/', '', $store['original_price']));
                                            $current = floatval(preg_replace('/[^\d.]/', '', $store['price']));
                                            if ($original > $current) {
                                                $has_discount = true;
                                                $discount_percent = round((($original - $current) / $original) * 100);
                                            }
                                        }
                                        ?>
                                        
                                        <div class="price-display">
                                            <?php if ($has_discount): ?>
                                                <span class="original-price"><?php echo esc_html($store['original_price']); ?></span>
                                            <?php endif; ?>
                                            <span class="current-price"><?php echo esc_html($display_price); ?></span>
                                            <?php if ($has_discount): ?>
                                                <span class="discount-text"><?php printf(__('По-изгодно с %d%%', 'parfume-reviews'), $discount_percent); ?></span>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div class="price-info-icon" title="<?php printf(__('Цената се актуализира на всеки %s часа', 'parfume-reviews'), isset($settings['scrape_interval']) ? $settings['scrape_interval'] : '24'); ?>">
                                            <span class="dashicons dashicons-info"></span>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Store Info Row -->
                                <div class="store-info-row">
                                    <?php if (!empty($store['availability'])): ?>
                                        <div class="availability-badge">
                                            <span class="dashicons dashicons-yes-alt"></span>
                                            <?php echo esc_html($store['availability']); ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($store['shipping_info'])): ?>
                                        <div class="shipping-info">
                                            <?php echo esc_html($store['shipping_info']); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Variants Row (if multiple sizes) -->
                                <?php if (!empty($store['variants']) && is_array($store['variants']) && count($store['variants']) > 1): ?>
                                    <div class="variants-row">
                                        <?php foreach ($store['variants'] as $variant): ?>
                                            <?php if (!empty($variant['size']) && !empty($variant['price'])): ?>
                                                <a href="<?php echo esc_url(!empty($store['affiliate_url']) ? $store['affiliate_url'] : '#'); ?>" 
                                                   target="_blank" rel="nofollow" class="variant-button <?php echo !empty($variant['has_discount']) ? 'has-promotion' : ''; ?>">
                                                    <?php if (!empty($variant['has_discount'])): ?>
                                                        <span class="promotion-badge">%</span>
                                                    <?php endif; ?>
                                                    <span class="variant-size"><?php echo esc_html($variant['size']); ?></span>
                                                    <span class="variant-price"><?php echo esc_html($variant['price']); ?></span>
                                                </a>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <!-- Single variant info -->
                                    <?php if (!empty($store['size'])): ?>
                                        <div class="single-variant-info">
                                            <span class="variant-size"><?php echo esc_html($store['size']); ?></span>
                                        </div>
                                    <?php endif; ?>
                                <?php endif; ?>
                                
                                <!-- Action Buttons Row -->
                                <div class="action-buttons-row">
                                    <?php if (!empty($store['promo_code'])): ?>
                                        <!-- Two buttons: Store + Promo Code -->
                                        <a href="<?php echo esc_url(!empty($store['affiliate_url']) ? $store['affiliate_url'] : '#'); ?>" 
                                           target="_blank" rel="nofollow" class="store-button half-width">
                                            <?php _e('Към магазина', 'parfume-reviews'); ?>
                                        </a>
                                        
                                        <div class="promo-button half-width" 
                                             onclick="copyPromoAndRedirect('<?php echo esc_js($store['promo_code']); ?>', '<?php echo esc_js(!empty($store['promo_url']) ? $store['promo_url'] : $store['affiliate_url']); ?>')">
                                            <?php if (!empty($store['promo_code_info'])): ?>
                                                <div class="promo-info"><?php echo esc_html($store['promo_code_info']); ?></div>
                                            <?php endif; ?>
                                            <div class="promo-code-display">
                                                <span class="promo-code"><?php echo esc_html($store['promo_code']); ?></span>
                                                <span class="copy-icon dashicons dashicons-clipboard"></span>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <!-- Single button: Store only -->
                                        <a href="<?php echo esc_url(!empty($store['affiliate_url']) ? $store['affiliate_url'] : '#'); ?>" 
                                           target="_blank" rel="nofollow" class="store-button full-width">
                                            <?php _e('Към магазина', 'parfume-reviews'); ?>
                                        </a>
                                    <?php endif; ?>
                                </div>
                                
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
                
            </div>
            
            <!-- Mobile Show Button (when sidebar is hidden) -->
            <?php if ($mobile_show_close_btn): ?>
                <div class="mobile-show-stores" style="display: none;">
                    <button class="show-stores-btn" aria-label="<?php _e('Показване на панела с магазини', 'parfume-reviews'); ?>">
                        <span class="dashicons dashicons-arrow-up-alt2"></span>
                    </button>
                </div>
            <?php endif; ?>
            
        <?php endif; ?>
    </div>
</div>

<!-- Modal for image viewing -->
<div id="imageModal" class="image-modal" onclick="closeImageModal()">
    <span class="close-modal">&times;</span>
    <img class="modal-content" id="modalImage">
    <div class="modal-caption" id="modalCaption"></div>
</div>

<script>
// Modal functionality
function openImageModal(imageSrc) {
    const modal = document.getElementById('imageModal');
    const modalImg = document.getElementById('modalImage');
    const caption = document.getElementById('modalCaption');
    
    modal.style.display = 'block';
    modalImg.src = imageSrc;
    caption.innerHTML = '<?php echo esc_js(get_the_title()); ?>';
    
    document.body.style.overflow = 'hidden';
}

function closeImageModal() {
    const modal = document.getElementById('imageModal');
    modal.style.display = 'none';
    document.body.style.overflow = 'auto';
}

// Copy promo code and redirect
function copyPromoAndRedirect(promoCode, url) {
    navigator.clipboard.writeText(promoCode).then(function() {
        // Show success message
        const notification = document.createElement('div');
        notification.textContent = '<?php _e('Промо кодът е копиран!', 'parfume-reviews'); ?>';
        notification.style.cssText = 'position:fixed;top:20px;right:20px;background:#28a745;color:white;padding:10px 20px;border-radius:4px;z-index:99999;';
        document.body.appendChild(notification);
        
        setTimeout(() => {
            document.body.removeChild(notification);
            if (url) {
                window.open(url, '_blank');
            }
        }, 1500);
    }).catch(function() {
        // Fallback for browsers that don't support clipboard API
        if (url) {
            window.open(url, '_blank');
        }
    });
}

// Mobile stores functionality
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.querySelector('.parfume-stores-sidebar');
    if (!sidebar) return;
    
    const toggleBtn = sidebar.querySelector('.stores-toggle');
    const closeBtn = sidebar.querySelector('.stores-close');
    const showBtn = document.querySelector('.show-stores-btn');
    const additionalStores = sidebar.querySelectorAll('.additional-store');
    
    let isExpanded = false;
    
    // Toggle additional stores on mobile
    if (toggleBtn && additionalStores.length > 0) {
        toggleBtn.addEventListener('click', function() {
            isExpanded = !isExpanded;
            
            additionalStores.forEach(store => {
                if (isExpanded) {
                    store.style.display = 'block';
                    store.style.animation = 'slideUp 0.3s ease-out';
                } else {
                    store.style.animation = 'slideDown 0.3s ease-out';
                    setTimeout(() => {
                        store.style.display = 'none';
                    }, 300);
                }
            });
            
            // Rotate arrow
            const arrow = toggleBtn.querySelector('.dashicons');
            arrow.style.transform = isExpanded ? 'rotate(180deg)' : 'rotate(0deg)';
        });
    }
    
    // Close sidebar
    if (closeBtn) {
        closeBtn.addEventListener('click', function() {
            sidebar.style.display = 'none';
            if (showBtn) {
                showBtn.parentElement.style.display = 'block';
            }
        });
    }
    
    // Show sidebar
    if (showBtn) {
        showBtn.addEventListener('click', function() {
            sidebar.style.display = 'block';
            showBtn.parentElement.style.display = 'none';
        });
    }
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