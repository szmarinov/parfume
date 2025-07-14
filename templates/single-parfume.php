<?php
/**
 * Template for single parfume posts
 * ПРЕРАБОТЕНА ВЕРСИЯ С НОВ STORES SIDEBAR ДИЗАЙН И MOBILE ФУНКЦИОНАЛНОСТИ
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
$mobile_bottom_offset = isset($settings['mobile_bottom_offset']) ? $settings['mobile_bottom_offset'] : 20;

// Price formatting settings
$price_format = isset($settings['price_format']) ? $settings['price_format'] : 'after';
$currency_symbol = isset($settings['currency_symbol']) ? $settings['currency_symbol'] : 'лв.';
$scrape_interval = isset($settings['scrape_interval']) ? $settings['scrape_interval'] : 24;

// Ensure stores is an array
if (!is_array($stores)) {
    $stores = array();
}
?>

<div class="single-parfume-container">
    <div class="parfume-layout">
        
        <!-- MAIN CONTENT (Column 1) -->
        <div class="parfume-main-content">
            <article class="parfume-article">
                
                <!-- Header Section -->
                <header class="parfume-header">
                    <div class="header-content">
                        <div class="parfume-image">
                            <?php if (has_post_thumbnail()): ?>
                                <img src="<?php echo get_the_post_thumbnail_url(get_the_ID(), 'large'); ?>" 
                                     alt="<?php the_title_attribute(); ?>" 
                                     onclick="openImageModal('<?php echo get_the_post_thumbnail_url(get_the_ID(), 'full'); ?>')">
                            <?php else: ?>
                                <div class="no-image-placeholder">
                                    <span class="dashicons dashicons-format-image"></span>
                                    <p><?php _e('Няма изображение', 'parfume-reviews'); ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="header-info">
                            <h1 class="parfume-title"><?php the_title(); ?></h1>
                            
                            <?php if (!empty($brand_names)): ?>
                                <div class="parfume-brand">
                                    <?php echo implode(', ', $brand_names); ?>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($rating)): ?>
                                <div class="parfume-rating">
                                    <div class="rating-stars" data-rating="<?php echo esc_attr($rating); ?>">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <span class="star <?php echo ($i <= $rating) ? 'filled' : ''; ?>">★</span>
                                        <?php endfor; ?>
                                    </div>
                                    <span class="rating-text"><?php echo esc_html($rating); ?>/5</span>
                                </div>
                            <?php endif; ?>
                            
                            <div class="parfume-meta">
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
                                        <span class="info-label"><?php _e('Тип:', 'parfume-reviews'); ?></span>
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
                    
                    <!-- Description -->
                    <section class="description-section">
                        <h2><?php _e('Описание', 'parfume-reviews'); ?></h2>
                        <div class="parfume-description">
                            <?php the_content(); ?>
                        </div>
                    </section>

                    <!-- Notes Section -->
                    <?php if (!empty($notes)): ?>
                        <section class="notes-section">
                            <h2><?php _e('Ароматни ноти', 'parfume-reviews'); ?></h2>
                            <div class="notes-grid">
                                <?php foreach ($notes as $note): ?>
                                    <div class="note-item">
                                        <a href="<?php echo get_term_link($note); ?>" class="note-link">
                                            <?php echo esc_html($note->name); ?>
                                        </a>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </section>
                    <?php endif; ?>

                    <!-- Perfumer Section -->
                    <?php if (!empty($perfumers)): ?>
                        <section class="perfumer-section">
                            <h2><?php _e('Парфюмерист', 'parfume-reviews'); ?></h2>
                            <div class="perfumer-list">
                                <?php foreach ($perfumers as $perfumer): ?>
                                    <a href="<?php echo get_term_link($perfumer); ?>" class="perfumer-link">
                                        <?php echo esc_html($perfumer->name); ?>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </section>
                    <?php endif; ?>

                    <!-- Gallery Section -->
                    <?php if (!empty($gallery_images) && is_array($gallery_images)): ?>
                        <section class="gallery-section">
                            <h2><?php _e('Галерия', 'parfume-reviews'); ?></h2>
                            <div class="parfume-gallery">
                                <?php foreach ($gallery_images as $image_id): ?>
                                    <?php $image = wp_get_attachment_image_src($image_id, 'medium'); ?>
                                    <?php if ($image): ?>
                                        <div class="gallery-item">
                                            <img src="<?php echo esc_url($image[0]); ?>" alt="<?php the_title_attribute(); ?>" onclick="openImageModal('<?php echo esc_url(wp_get_attachment_image_src($image_id, 'large')[0]); ?>')">
                                        </div>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        </section>
                    <?php endif; ?>

                    <!-- Pros and Cons -->
                    <?php if (!empty($pros) || !empty($cons)): ?>
                        <section class="pros-cons-section">
                            <h2><?php _e('Преимущества и недостатъци', 'parfume-reviews'); ?></h2>
                            <div class="pros-cons-grid">
                                <?php if (!empty($pros)): ?>
                                    <div class="pros">
                                        <h3><?php _e('Преимущества', 'parfume-reviews'); ?></h3>
                                        <ul>
                                            <?php 
                                            $pros_array = is_string($pros) ? explode("\n", $pros) : $pros;
                                            foreach ($pros_array as $pro): 
                                                if (trim($pro)): ?>
                                                    <li><?php echo esc_html(trim($pro)); ?></li>
                                                <?php endif;
                                            endforeach; ?>
                                        </ul>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($cons)): ?>
                                    <div class="cons">
                                        <h3><?php _e('Недостатъци', 'parfume-reviews'); ?></h3>
                                        <ul>
                                            <?php 
                                            $cons_array = is_string($cons) ? explode("\n", $cons) : $cons;
                                            foreach ($cons_array as $con): 
                                                if (trim($con)): ?>
                                                    <li><?php echo esc_html(trim($con)); ?></li>
                                                <?php endif;
                                            endforeach; ?>
                                        </ul>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </section>
                    <?php endif; ?>

                    <!-- Aroma Chart -->
                    <?php if (!empty($aroma_chart)): ?>
                        <section class="aroma-chart-section">
                            <h2><?php _e('Ароматна схема', 'parfume-reviews'); ?></h2>
                            <div class="aroma-chart-wrapper">
                                <div class="aroma-chart-info">
                                    <?php echo wp_kses_post($aroma_chart); ?>
                                </div>
                            </div>
                        </section>
                    <?php endif; ?>

                </div>
            </article>
        </div>
        
        <!-- STORES SIDEBAR (Column 2) -->
        <?php if (!empty($stores) && is_array($stores)): ?>
            <div class="stores-sidebar" 
                 class="<?php echo $mobile_fixed_panel ? 'mobile-fixed' : ''; ?>" 
                 data-mobile-fixed="<?php echo $mobile_fixed_panel ? 'true' : 'false'; ?>"
                 data-show-close="<?php echo $mobile_show_close_btn ? 'true' : 'false'; ?>"
                 style="--mobile-z-index: <?php echo esc_attr($mobile_z_index); ?>; --mobile-bottom-offset: <?php echo esc_attr($mobile_bottom_offset); ?>px;">
                
                <!-- Mobile Controls -->
                <div class="mobile-controls">
                    <?php if (count($stores) > 1): ?>
                        <button class="stores-toggle" type="button" title="<?php _e('Покажи/скрий останалите магазини', 'parfume-reviews'); ?>">
                            <span class="dashicons dashicons-arrow-up"></span>
                        </button>
                    <?php endif; ?>
                    
                    <?php if ($mobile_show_close_btn): ?>
                        <button class="stores-close" type="button" title="<?php _e('Затвори панела', 'parfume-reviews'); ?>">
                            <span class="dashicons dashicons-no"></span>
                        </button>
                    <?php endif; ?>
                </div>
                
                <!-- Stores List -->
                <div class="stores-list">
                    <?php foreach ($stores as $index => $store): ?>
                        <?php
                        // ПОПРАВКА: Добавяме isset() проверки за всички ключове
                        $store_name = isset($store['name']) ? $store['name'] : '';
                        $store_logo = isset($store['logo']) ? $store['logo'] : '';
                        $affiliate_url = isset($store['affiliate_url']) ? $store['affiliate_url'] : '';
                        $promo_code = isset($store['promo_code']) ? $store['promo_code'] : '';
                        $promo_code_info = isset($store['promo_code_info']) ? $store['promo_code_info'] : '';
                        
                        // ПОПРАВКА: Scraped data с isset() проверки
                        $scraped_price = isset($store['scraped_price']) ? $store['scraped_price'] : '';
                        $scraped_old_price = isset($store['scraped_old_price']) ? $store['scraped_old_price'] : '';
                        $scraped_variants = isset($store['scraped_variants']) ? $store['scraped_variants'] : array();
                        $scraped_availability = isset($store['scraped_availability']) ? $store['scraped_availability'] : '';
                        $scraped_delivery = isset($store['scraped_delivery']) ? $store['scraped_delivery'] : '';
                        $last_scraped = isset($store['last_scraped']) ? $store['last_scraped'] : '';
                        
                        // Add additional-store class for mobile hide/show functionality
                        $additional_class = ($index > 0) ? 'additional-store' : '';
                        ?>
                        
                        <div class="store-item <?php echo esc_attr($additional_class); ?>">
                            <!-- Store Header -->
                            <div class="store-header">
                                <div class="store-logo">
                                    <?php if (!empty($store_logo)): ?>
                                        <img src="<?php echo esc_url($store_logo); ?>" alt="<?php echo esc_attr($store_name); ?>">
                                    <?php else: ?>
                                        <span class="dashicons dashicons-store"></span>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="store-price-info">
                                    <?php if (!empty($scraped_price)): ?>
                                        <div class="price-display">
                                            <?php if (!empty($scraped_old_price) && $scraped_old_price != $scraped_price): ?>
                                                <span class="old-price"><?php echo esc_html($scraped_old_price . ' ' . $currency_symbol); ?></span>
                                            <?php endif; ?>
                                            <span class="current-price">
                                                <?php 
                                                if ($price_format === 'before') {
                                                    echo esc_html($currency_symbol . ' ' . $scraped_price);
                                                } else {
                                                    echo esc_html($scraped_price . ' ' . $currency_symbol);
                                                }
                                                ?>
                                            </span>
                                            <?php if (!empty($scraped_old_price) && $scraped_old_price != $scraped_price): ?>
                                                <?php 
                                                $discount_percent = round((($scraped_old_price - $scraped_price) / $scraped_old_price) * 100);
                                                ?>
                                                <span class="discount-percent"><?php printf(__('По-изгодно с %d%%', 'parfume-reviews'), $discount_percent); ?></span>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="price-update-info">
                                        <span class="dashicons dashicons-info" title="<?php printf(__('Цената се актуализира на всеки %d часа', 'parfume-reviews'), $scrape_interval); ?>"></span>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Store Info Row -->
                            <div class="store-info-row">
                                <?php if (is_array($scraped_variants) && count($scraped_variants) === 1): ?>
                                    <!-- Single variant display -->
                                    <div class="single-variant-info">
                                        <span class="variant-size"><?php echo esc_html($scraped_variants[0]['ml']); ?> ml</span>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($scraped_availability) && $scraped_availability === 'available'): ?>
                                    <div class="availability-badge available">
                                        <span class="dashicons dashicons-yes"></span>
                                        <?php _e('наличен', 'parfume-reviews'); ?>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($scraped_delivery)): ?>
                                    <div class="delivery-info">
                                        <?php echo esc_html($scraped_delivery); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Variants (if multiple) -->
                            <?php if (is_array($scraped_variants) && count($scraped_variants) > 1): ?>
                                <div class="variants-row">
                                    <?php foreach ($scraped_variants as $variant): ?>
                                        <button type="button" class="variant-button" onclick="window.open('<?php echo esc_url($affiliate_url); ?>', '_blank')">
                                            <span class="variant-ml"><?php echo esc_html($variant['ml']); ?> мл.</span>
                                            <?php if (isset($variant['price'])): ?>
                                                <span class="variant-price"><?php echo esc_html($variant['price'] . ' ' . $currency_symbol); ?></span>
                                            <?php endif; ?>
                                            <?php if (isset($variant['discount']) && $variant['discount']): ?>
                                                <span class="variant-discount">%</span>
                                            <?php endif; ?>
                                        </button>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Action Buttons -->
                            <div class="store-actions">
                                <?php if (!empty($affiliate_url) && !empty($promo_code)): ?>
                                    <!-- Two buttons: Store + Promo Code -->
                                    <div class="action-buttons-split">
                                        <a href="<?php echo esc_url($affiliate_url); ?>" target="_blank" rel="nofollow" class="store-button">
                                            <?php _e('Към магазина', 'parfume-reviews'); ?>
                                        </a>
                                        <button type="button" class="promo-button" onclick="copyPromoCode('<?php echo esc_js($promo_code); ?>', '<?php echo esc_url($affiliate_url); ?>')">
                                            <?php if (!empty($promo_code_info)): ?>
                                                <span class="promo-info"><?php echo esc_html($promo_code_info); ?></span>
                                            <?php endif; ?>
                                            <span class="promo-code"><?php echo esc_html($promo_code); ?></span>
                                            <span class="dashicons dashicons-clipboard"></span>
                                        </button>
                                    </div>
                                <?php elseif (!empty($affiliate_url)): ?>
                                    <!-- Single button: Store only -->
                                    <a href="<?php echo esc_url($affiliate_url); ?>" target="_blank" rel="nofollow" class="store-button full-width">
                                        <?php _e('Към магазина', 'parfume-reviews'); ?>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Mobile Show Button (when sidebar is hidden) -->
<?php if ($mobile_show_close_btn && !empty($stores)): ?>
    <div class="show-stores-panel" style="display: none;">
        <button class="show-stores-btn" type="button">
            <span class="dashicons dashicons-cart"></span>
            <?php _e('Покажи магазини', 'parfume-reviews'); ?>
        </button>
    </div>
<?php endif; ?>

<!-- Image Modal -->
<div id="image-modal" class="image-modal" onclick="closeImageModal()">
    <div class="modal-content">
        <img id="modal-image" src="" alt="">
        <span class="close-modal">&times;</span>
    </div>
</div>

<script>
// Image modal functionality
function openImageModal(src) {
    document.getElementById('image-modal').style.display = 'flex';
    document.getElementById('modal-image').src = src;
    document.body.style.overflow = 'hidden';
}

function closeImageModal() {
    document.getElementById('image-modal').style.display = 'none';
    document.body.style.overflow = 'auto';
}

// Promo code copy functionality
function copyPromoCode(code, url) {
    navigator.clipboard.writeText(code).then(function() {
        // Show success notification
        const notification = document.createElement('div');
        notification.textContent = '<?php _e('Промо кодът е копиран!', 'parfume-reviews'); ?>';
        notification.style.cssText = 'position: fixed; top: 20px; right: 20px; background: #4CAF50; color: white; padding: 15px; border-radius: 5px; z-index: 10000;';
        document.body.appendChild(notification);
        
        setTimeout(() => {
            document.body.removeChild(notification);
        }, 3000);
        
        // Optionally open store URL after copying
        window.open(url, '_blank');
    });
}

// Mobile stores functionality
document.addEventListener('DOMContentLoaded', function() {
    const storesToggle = document.querySelector('.stores-toggle');
    const storesClose = document.querySelector('.stores-close');
    const showStoresBtn = document.querySelector('.show-stores-btn');
    const storesSidebar = document.querySelector('.stores-sidebar');
    const showStoresPanel = document.querySelector('.show-stores-panel');
    const additionalStores = document.querySelectorAll('.additional-store');
    
    if (storesToggle) {
        storesToggle.addEventListener('click', function() {
            additionalStores.forEach(store => {
                if (store.style.display === 'none') {
                    store.style.display = 'block';
                    storesToggle.querySelector('.dashicons').classList.remove('dashicons-arrow-up');
                    storesToggle.querySelector('.dashicons').classList.add('dashicons-arrow-down');
                } else {
                    store.style.display = 'none';
                    storesToggle.querySelector('.dashicons').classList.remove('dashicons-arrow-down');
                    storesToggle.querySelector('.dashicons').classList.add('dashicons-arrow-up');
                }
            });
        });
        
        // Initially hide additional stores on mobile
        if (window.innerWidth <= 768) {
            additionalStores.forEach(store => {
                store.style.display = 'none';
            });
        }
    }
    
    if (storesClose && showStoresPanel) {
        storesClose.addEventListener('click', function() {
            storesSidebar.style.display = 'none';
            showStoresPanel.style.display = 'block';
        });
    }
    
    if (showStoresBtn) {
        showStoresBtn.addEventListener('click', function() {
            storesSidebar.style.display = 'block';
            showStoresPanel.style.display = 'none';
        });
    }
});
</script>

<?php
endwhile;

get_footer();
?>