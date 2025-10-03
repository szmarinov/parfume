<?php
/**
 * Template for single parfume posts
 * АКТУАЛИЗИРАНА ВЕРСИЯ - Работи със СЪЩЕСТВУВАЩИ мета полета
 * БЕЗ ПРОМЯНА НА ПОЛЕТА - само визуализация според заданието
 * 
 * @package Parfume_Reviews
 * @version 2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();

while (have_posts()): the_post();

// Get meta data - ЗАПАЗЕНИ ОРИГИНАЛНИ ПОЛЕТА (БЕЗ ПРОМЯНА)
$rating = get_post_meta(get_the_ID(), '_parfume_rating', true);
$gender_text = get_post_meta(get_the_ID(), '_parfume_gender_text', true);
$release_year = get_post_meta(get_the_ID(), '_parfume_release_year', true);
$longevity = get_post_meta(get_the_ID(), '_parfume_longevity', true);
$sillage = get_post_meta(get_the_ID(), '_parfume_sillage', true);
$bottle_size = get_post_meta(get_the_ID(), '_parfume_bottle_size', true);
$aroma_chart = get_post_meta(get_the_ID(), '_parfume_aroma_chart', true);
$pros = get_post_meta(get_the_ID(), '_parfume_pros', true);
$cons = get_post_meta(get_the_ID(), '_parfume_cons', true);
$gallery_images = get_post_meta(get_the_ID(), '_parfume_gallery', true);

// STORES данни - използваме СЪЩЕСТВУВАЩОТО поле _parfume_stores
$stores = get_post_meta(get_the_ID(), '_parfume_stores', true);
if (!is_array($stores)) {
    $stores = array();
}

// Get taxonomies - ЗАПАЗЕНИ ОРИГИНАЛНИ
$brands = wp_get_post_terms(get_the_ID(), 'marki');
$genders = wp_get_post_terms(get_the_ID(), 'gender');
$aroma_types = wp_get_post_terms(get_the_ID(), 'aroma_type');
$seasons = wp_get_post_terms(get_the_ID(), 'season');
$intensities = wp_get_post_terms(get_the_ID(), 'intensity');
$notes = wp_get_post_terms(get_the_ID(), 'notes');
$perfumers = wp_get_post_terms(get_the_ID(), 'perfumer');

// Generate taxonomy links - ЗАПАЗЕНИ ОРИГИНАЛНИ
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

// Mobile настройки от глобални settings
$global_settings = get_option('parfume_reviews_settings', array());

// Per-post mobile настройки (ако има) имат приоритет
$post_mobile_fixed = get_post_meta(get_the_ID(), '_parfume_mobile_fixed_panel', true);
$post_mobile_close_btn = get_post_meta(get_the_ID(), '_parfume_mobile_show_close_btn', true);

// Определяме финалните настройки
$mobile_fixed_panel = ($post_mobile_fixed !== '') ? $post_mobile_fixed : (isset($global_settings['mobile_fixed_panel']) ? $global_settings['mobile_fixed_panel'] : 1);
$mobile_show_close_btn = ($post_mobile_close_btn !== '') ? $post_mobile_close_btn : (isset($global_settings['mobile_show_close_btn']) ? $global_settings['mobile_show_close_btn'] : 0);
$mobile_z_index = isset($global_settings['mobile_z_index']) ? $global_settings['mobile_z_index'] : 9999;
$mobile_bottom_offset = isset($global_settings['mobile_bottom_offset']) ? $global_settings['mobile_bottom_offset'] : 0;

// Scraper interval за tooltip
$scrape_interval = isset($global_settings['scrape_interval']) ? $global_settings['scrape_interval'] : 24;

// Stores config от settings
$all_stores_config = isset($global_settings['stores']) ? $global_settings['stores'] : array();

?>

<div class="single-parfume-container">
    <div class="parfume-layout">
        
        <!-- COLUMN 1: Main Content (ЗАПАЗЕНО ОРИГИНАЛНО СЪДЪРЖАНИЕ) -->
        <div class="parfume-main-content">
            <article class="parfume-article">
                
                <!-- Header Section -->
                <header class="parfume-header">
                    <div class="header-content">
                        
                        <!-- Featured Image -->
                        <div class="parfume-image" onclick="openImageModal('<?php echo esc_url(get_the_post_thumbnail_url(get_the_ID(), 'large')); ?>')">
                            <?php if (has_post_thumbnail()): ?>
                                <?php the_post_thumbnail('medium_large'); ?>
                            <?php else: ?>
                                <div class="no-image-placeholder">
                                    <span class="dashicons dashicons-format-image"></span>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Main Info -->
                        <div class="parfume-info">
                            <h1 class="parfume-title"><?php the_title(); ?></h1>
                            
                            <?php if (!empty($brand_names)): ?>
                                <div class="parfume-brand">
                                    <?php echo implode(', ', $brand_names); ?>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($rating): ?>
                                <div class="parfume-rating">
                                    <div class="rating-stars">
                                        <?php 
                                        for ($i = 1; $i <= 5; $i++) {
                                            if ($i <= $rating) {
                                                echo '<span class="dashicons dashicons-star-filled"></span>';
                                            } else {
                                                echo '<span class="dashicons dashicons-star-empty"></span>';
                                            }
                                        }
                                        ?>
                                    </div>
                                    <span class="rating-number"><?php echo number_format($rating, 1); ?>/5</span>
                                </div>
                            <?php endif; ?>
                            
                            <div class="parfume-meta">
                                <?php if (!empty($gender_list)): ?>
                                    <div class="meta-item">
                                        <span class="meta-label"><?php _e('За:', 'parfume-reviews'); ?></span>
                                        <span class="meta-value"><?php echo implode(', ', $gender_list); ?></span>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($aroma_type_list)): ?>
                                    <div class="meta-item">
                                        <span class="meta-label"><?php _e('Тип:', 'parfume-reviews'); ?></span>
                                        <span class="meta-value"><?php echo implode(', ', $aroma_type_list); ?></span>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($release_year): ?>
                                    <div class="meta-item">
                                        <span class="meta-label"><?php _e('Година:', 'parfume-reviews'); ?></span>
                                        <span class="meta-value"><?php echo esc_html($release_year); ?></span>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($bottle_size): ?>
                                    <div class="meta-item">
                                        <span class="meta-label"><?php _e('Обем:', 'parfume-reviews'); ?></span>
                                        <span class="meta-value"><?php echo esc_html($bottle_size); ?></span>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($longevity): ?>
                                    <div class="meta-item">
                                        <span class="meta-label"><?php _e('Дълготрайност:', 'parfume-reviews'); ?></span>
                                        <span class="meta-value"><?php echo esc_html($longevity); ?></span>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($sillage): ?>
                                    <div class="meta-item">
                                        <span class="meta-label"><?php _e('Сила:', 'parfume-reviews'); ?></span>
                                        <span class="meta-value"><?php echo esc_html($sillage); ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <?php if (!empty($seasons_list)): ?>
                                <div class="seasons-tags">
                                    <span class="seasons-label"><?php _e('Сезони:', 'parfume-reviews'); ?></span>
                                    <?php echo implode(', ', $seasons_list); ?>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($intensities_list)): ?>
                                <div class="intensity-tags">
                                    <span class="intensity-label"><?php _e('Интензитет:', 'parfume-reviews'); ?></span>
                                    <?php echo implode(', ', $intensities_list); ?>
                                </div>
                            <?php endif; ?>
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
                                    <div class="gallery-item" onclick="openImageModal('<?php echo esc_url(wp_get_attachment_url($image_id)); ?>')">
                                        <?php echo wp_get_attachment_image($image_id, 'medium'); ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </section>
                    <?php endif; ?>

                    <!-- Pros & Cons -->
                    <?php if (!empty($pros) || !empty($cons)): ?>
                        <section class="pros-cons-section">
                            <h2><?php _e('Плюсове и минуси', 'parfume-reviews'); ?></h2>
                            <div class="pros-cons-grid">
                                <?php if (!empty($pros)): ?>
                                    <div class="pros">
                                        <h3><?php _e('Предимства', 'parfume-reviews'); ?></h3>
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
                                <?php echo wp_kses_post($aroma_chart); ?>
                            </div>
                        </section>
                    <?php endif; ?>

                </div>
            </article>
        </div>
        
        <!-- COLUMN 2: STORES SIDEBAR (НОВА ВИЗУАЛИЗАЦИЯ СПОРЕД ЗАДАНИЕТО) -->
        <?php if (!empty($stores) && is_array($stores)): ?>
            <aside class="parfume-stores-sidebar <?php echo $mobile_fixed_panel ? 'mobile-fixed' : ''; ?>" 
                   data-mobile-fixed="<?php echo $mobile_fixed_panel ? '1' : '0'; ?>"
                   data-show-close="<?php echo $mobile_show_close_btn ? '1' : '0'; ?>"
                   style="--mobile-z-index: <?php echo esc_attr($mobile_z_index); ?>; --mobile-bottom-offset: <?php echo esc_attr($mobile_bottom_offset); ?>px;">
                
                <!-- Mobile Controls -->
                <div class="mobile-controls">
                    <?php if (count($stores) > 1): ?>
                        <button class="stores-toggle" type="button" aria-label="<?php esc_attr_e('Покажи/скрий останалите магазини', 'parfume-reviews'); ?>">
                            <span class="dashicons dashicons-arrow-up-alt2"></span>
                        </button>
                    <?php endif; ?>
                    
                    <?php if ($mobile_show_close_btn): ?>
                        <button class="stores-close" type="button" aria-label="<?php esc_attr_e('Затвори панела', 'parfume-reviews'); ?>">
                            <span class="dashicons dashicons-no-alt"></span>
                        </button>
                    <?php endif; ?>
                </div>
                
                <!-- Stores List -->
                <div class="stores-list">
                    <?php 
                    $store_index = 0;
                    foreach ($stores as $store): 
                        $store_index++;
                        
                        // Извличаме данни от store (СЪЩЕСТВУВАЩИ ПОЛЕТА)
                        $store_key = isset($store['store_key']) ? $store['store_key'] : '';
                        $store_config = isset($all_stores_config[$store_key]) ? $all_stores_config[$store_key] : array();
                        
                        // Store info
                        $store_name = !empty($store['name']) ? $store['name'] : (!empty($store_config['name']) ? $store_config['name'] : __('Магазин', 'parfume-reviews'));
                        $store_logo = !empty($store_config['logo_url']) ? $store_config['logo_url'] : '';
                        $affiliate_url = !empty($store['affiliate_url']) ? $store['affiliate_url'] : '';
                        $promo_code = !empty($store['promo_code']) ? $store['promo_code'] : '';
                        $promo_code_info = !empty($store['promo_code_info']) ? $store['promo_code_info'] : '';
                        
                        // Scraped data (СЪЩЕСТВУВАЩИ ПОЛЕТА)
                        $scraped_price = !empty($store['scraped_price']) ? $store['scraped_price'] : '';
                        $scraped_old_price = !empty($store['scraped_old_price']) ? $store['scraped_old_price'] : '';
                        $scraped_sizes = !empty($store['scraped_sizes']) ? $store['scraped_sizes'] : '';
                        $scraped_availability = !empty($store['scraped_availability']) ? $store['scraped_availability'] : '';
                        $scraped_delivery = !empty($store['scraped_delivery']) ? $store['scraped_delivery'] : '';
                        $last_scraped = !empty($store['last_scraped']) ? $store['last_scraped'] : '';
                        
                        // Parse variants from scraped_sizes if it's a string
                        $variants = array();
                        if (is_string($scraped_sizes) && !empty($scraped_sizes)) {
                            // Format: "30ml - 45лв, 50ml - 67лв, 90ml - 89лв"
                            $sizes_array = explode(',', $scraped_sizes);
                            foreach ($sizes_array as $size_item) {
                                $parts = explode('-', trim($size_item));
                                if (count($parts) >= 2) {
                                    $variants[] = array(
                                        'size' => trim($parts[0]),
                                        'price' => trim($parts[1])
                                    );
                                }
                            }
                        } elseif (is_array($scraped_sizes)) {
                            $variants = $scraped_sizes;
                        }
                        
                        // Discount calculation
                        $discount_percent = 0;
                        if ($scraped_old_price && $scraped_price) {
                            $old = floatval(preg_replace('/[^0-9.]/', '', $scraped_old_price));
                            $new = floatval(preg_replace('/[^0-9.]/', '', $scraped_price));
                            if ($old > $new && $old > 0) {
                                $discount_percent = round((($old - $new) / $old) * 100);
                            }
                        }
                        
                        // Additional store class for mobile
                        $additional_class = ($store_index > 1) ? 'additional-store' : '';
                        ?>
                        
                        <div class="store-item <?php echo esc_attr($additional_class); ?>" data-store-index="<?php echo esc_attr($store_index); ?>">
                            
                            <!-- Store Header: Logo + Price (РЕД 1 от заданието) -->
                            <div class="store-header">
                                <div class="store-logo">
                                    <?php if ($store_logo): ?>
                                        <img src="<?php echo esc_url($store_logo); ?>" alt="<?php echo esc_attr($store_name); ?>">
                                    <?php else: ?>
                                        <span class="dashicons dashicons-store"></span>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="store-price-info">
                                    <?php if ($scraped_price): ?>
                                        <div class="price-display">
                                            <?php if ($scraped_old_price && $discount_percent > 0): ?>
                                                <span class="old-price"><?php echo esc_html($scraped_old_price); ?></span>
                                            <?php endif; ?>
                                            <span class="current-price"><?php echo esc_html($scraped_price); ?></span>
                                            <span class="price-info-icon" title="<?php echo esc_attr(sprintf(__('Цената се актуализира на всеки %s часа', 'parfume-reviews'), $scrape_interval)); ?>">
                                                <span class="dashicons dashicons-info"></span>
                                            </span>
                                        </div>
                                        <?php if ($discount_percent > 0): ?>
                                            <div class="discount-badge">
                                                <?php echo sprintf(__('По-изгодно с %d%%', 'parfume-reviews'), $discount_percent); ?>
                                            </div>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <!-- РЕД 2: Availability + Delivery (според заданието) -->
                            <?php if (count($variants) <= 1): ?>
                                <div class="store-info-row">
                                    <?php if (!empty($variants)): ?>
                                        <div class="single-variant-size">
                                            <?php echo esc_html($variants[0]['size']); ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($scraped_availability): ?>
                                        <div class="availability-badge">
                                            <span class="dashicons dashicons-yes-alt"></span>
                                            <?php echo esc_html($scraped_availability); ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($scraped_delivery): ?>
                                        <div class="delivery-info">
                                            <?php echo esc_html($scraped_delivery); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                            
                            <!-- РЕД 3: Variants (ако има повече от 1) - според заданието -->
                            <?php if (count($variants) > 1): ?>
                                <!-- Показваме availability и delivery преди вариантите -->
                                <?php if ($scraped_availability || $scraped_delivery): ?>
                                    <div class="store-info-row">
                                        <?php if ($scraped_availability): ?>
                                            <div class="availability-badge">
                                                <span class="dashicons dashicons-yes-alt"></span>
                                                <?php echo esc_html($scraped_availability); ?>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <?php if ($scraped_delivery): ?>
                                            <div class="delivery-info">
                                                <?php echo esc_html($scraped_delivery); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                                
                                <!-- Бутони за варианти -->
                                <div class="variants-row">
                                    <?php foreach ($variants as $variant): ?>
                                        <?php 
                                        $variant_size = !empty($variant['size']) ? $variant['size'] : '';
                                        $variant_price = !empty($variant['price']) ? $variant['price'] : '';
                                        $variant_old_price = !empty($variant['old_price']) ? $variant['old_price'] : '';
                                        $has_discount = $variant_old_price && $variant_price;
                                        ?>
                                        <a href="<?php echo esc_url($affiliate_url); ?>" target="_blank" rel="nofollow" class="variant-button <?php echo $has_discount ? 'has-discount' : ''; ?>">
                                            <?php if ($has_discount): ?>
                                                <span class="discount-badge-corner">
                                                    <span class="dashicons dashicons-tag"></span>
                                                </span>
                                            <?php endif; ?>
                                            <span class="variant-size"><?php echo esc_html($variant_size); ?></span>
                                            <span class="variant-price"><?php echo esc_html($variant_price); ?></span>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                            
                            <!-- РЕД 4 (или 3 ако няма варианти): Action Buttons - според заданието -->
                            <div class="store-actions">
                                <?php if ($promo_code && $affiliate_url): ?>
                                    <!-- Split buttons: Store (50%) + Promo (50%) -->
                                    <div class="action-buttons-split">
                                        <a href="<?php echo esc_url($affiliate_url); ?>" target="_blank" rel="nofollow" class="store-button">
                                            <?php _e('Към магазина', 'parfume-reviews'); ?>
                                        </a>
                                        <button type="button" class="promo-button" onclick="copyPromoCode('<?php echo esc_js($promo_code); ?>', '<?php echo esc_url($affiliate_url); ?>')">
                                            <?php if ($promo_code_info): ?>
                                                <span class="promo-info"><?php echo esc_html($promo_code_info); ?></span>
                                            <?php endif; ?>
                                            <span class="promo-code"><?php echo esc_html($promo_code); ?></span>
                                            <span class="dashicons dashicons-clipboard"></span>
                                        </button>
                                    </div>
                                <?php elseif ($affiliate_url): ?>
                                    <!-- Single button: Store only (100% width) -->
                                    <a href="<?php echo esc_url($affiliate_url); ?>" target="_blank" rel="nofollow" class="store-button full-width">
                                        <?php _e('Към магазина', 'parfume-reviews'); ?>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </aside>
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

// Promo code copy and redirect functionality (според заданието)
function copyPromoCode(code, url) {
    navigator.clipboard.writeText(code).then(function() {
        // Show success notification
        const notification = document.createElement('div');
        notification.textContent = '<?php _e('Промо кодът е копиран!', 'parfume-reviews'); ?>';
        notification.style.cssText = 'position:fixed;top:20px;right:20px;background:#28a745;color:white;padding:15px 20px;border-radius:8px;z-index:99999;box-shadow:0 4px 12px rgba(0,0,0,0.2);font-weight:600;';
        document.body.appendChild(notification);
        
        setTimeout(function() {
            notification.remove();
            // Redirect to affiliate URL after copy (според заданието)
            window.open(url, '_blank');
        }, 1500);
    }).catch(function(err) {
        console.error('Failed to copy promo code:', err);
        alert('<?php _e('Грешка при копиране на промо кода', 'parfume-reviews'); ?>');
    });
}
</script>

<?php
endwhile;

get_footer();
?>