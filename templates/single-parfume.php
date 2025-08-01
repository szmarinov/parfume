<?php
/**
 * Single Parfume Template
 * UPDATED VERSION: Добавена "Колона 2" с магазини и Product Scraper
 * 
 * Файл: templates/single-parfume.php
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();

// Получаваме основните данни за парфюма
$post_id = get_the_ID();
$rating = get_post_meta($post_id, '_rating', true);
$price = get_post_meta($post_id, '_price', true);
$release_year = get_post_meta($post_id, '_release_year', true);
$concentration = get_post_meta($post_id, '_concentration', true);
$bottle_size = get_post_meta($post_id, '_bottle_size', true);
$longevity = get_post_meta($post_id, '_longevity', true);
$sillage = get_post_meta($post_id, '_sillage', true);
$pros = get_post_meta($post_id, '_pros', true);
$cons = get_post_meta($post_id, '_cons', true);
$occasions = get_post_meta($post_id, '_occasions', true);

// Таксономии
$brands = wp_get_post_terms($post_id, 'marki');
$genders = wp_get_post_terms($post_id, 'gender');
$aroma_types = wp_get_post_terms($post_id, 'aroma_type');
$seasons = wp_get_post_terms($post_id, 'season');
$intensities = wp_get_post_terms($post_id, 'intensity');
$notes = wp_get_post_terms($post_id, 'notes');
$perfumers = wp_get_post_terms($post_id, 'perfumer');

// Получаваме данните за магазините ("Колона 2")
$stores_data = get_post_meta($post_id, '_parfume_stores', true);
if (!is_array($stores_data)) {
    $stores_data = array();
}

// Получаваме настройките за магазините
$available_stores = get_option('parfume_reviews_stores', array());

// Mobile настройки
$global_mobile_settings = get_option('parfume_reviews_mobile_settings', array());
$post_mobile_settings = get_post_meta($post_id, '_parfume_mobile_settings', true);

// Определяваме дали да показваме фиксиран панел на mobile
$show_mobile_fixed = true; // По подразбиране
if (!empty($post_mobile_settings['fixed_panel_override'])) {
    $show_mobile_fixed = ($post_mobile_settings['fixed_panel_override'] === '1');
} elseif (isset($global_mobile_settings['fixed_panel_enabled'])) {
    $show_mobile_fixed = $global_mobile_settings['fixed_panel_enabled'];
}

// Подредба на магазините по order
if (!empty($stores_data)) {
    uasort($stores_data, function($a, $b) {
        $order_a = isset($a['order']) ? intval($a['order']) : 0;
        $order_b = isset($b['order']) ? intval($b['order']) : 0;
        return $order_a - $order_b;
    });
}
?>

<div class="single-parfume-page parfume-reviews-page">
    <div class="parfume-layout">
        <!-- Колона 1 - Основно съдържание -->
        <div class="parfume-main-content">
            <div class="container">
                <!-- Breadcrumb Navigation -->
                <nav class="breadcrumb">
                    <a href="<?php echo home_url(); ?>"><?php _e('Начало', 'parfume-reviews'); ?></a>
                    <span class="separator"> › </span>
                    <a href="<?php echo home_url('/parfiumi/'); ?>"><?php _e('Парфюми', 'parfume-reviews'); ?></a>
                    <span class="separator"> › </span>
                    <span class="current"><?php the_title(); ?></span>
                </nav>

                <!-- Parfume Header -->
                <header class="parfume-header">
                    <div class="parfume-hero">
                        <div class="parfume-image">
                            <?php if (has_post_thumbnail()): ?>
                                <?php the_post_thumbnail('large', array('class' => 'parfume-featured-image')); ?>
                            <?php else: ?>
                                <div class="no-image-placeholder">
                                    <span class="dashicons dashicons-format-image"></span>
                                    <p><?php _e('Няма изображение', 'parfume-reviews'); ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="parfume-info">
                            <h1 class="parfume-title"><?php the_title(); ?></h1>
                            
                            <?php if (!empty($brands)): ?>
                                <div class="parfume-brand">
                                    <?php foreach ($brands as $brand): ?>
                                        <a href="<?php echo get_term_link($brand); ?>" class="brand-link">
                                            <?php echo esc_html($brand->name); ?>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Rating -->
                            <?php if (!empty($rating)): ?>
                                <div class="parfume-rating">
                                    <div class="rating-stars" data-rating="<?php echo esc_attr($rating); ?>">
                                        <?php for ($i = 1; $i <= 10; $i++): ?>
                                            <span class="star <?php echo ($i <= $rating) ? 'filled' : ''; ?>">★</span>
                                        <?php endfor; ?>
                                    </div>
                                    <span class="rating-text"><?php echo esc_html($rating); ?>/10</span>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Quick Info -->
                            <div class="parfume-quick-info">
                                <?php if (!empty($release_year)): ?>
                                    <div class="info-item">
                                        <span class="info-label"><?php _e('Година:', 'parfume-reviews'); ?></span>
                                        <span class="info-value"><?php echo esc_html($release_year); ?></span>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($concentration)): ?>
                                    <div class="info-item">
                                        <span class="info-label"><?php _e('Концентрация:', 'parfume-reviews'); ?></span>
                                        <span class="info-value"><?php echo esc_html($concentration); ?></span>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($genders)): ?>
                                    <div class="info-item">
                                        <span class="info-label"><?php _e('Пол:', 'parfume-reviews'); ?></span>
                                        <span class="info-value">
                                            <?php 
                                            $gender_names = array_map(function($gender) { return $gender->name; }, $genders);
                                            echo esc_html(implode(', ', $gender_names)); 
                                            ?>
                                        </span>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($seasons)): ?>
                                    <div class="info-item">
                                        <span class="info-label"><?php _e('Сезон:', 'parfume-reviews'); ?></span>
                                        <span class="info-value">
                                            <?php 
                                            $season_names = array_map(function($season) { return $season->name; }, $seasons);
                                            echo esc_html(implode(', ', $season_names)); 
                                            ?>
                                        </span>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($intensities)): ?>
                                    <div class="info-item">
                                        <span class="info-label"><?php _e('Интензивност:', 'parfume-reviews'); ?></span>
                                        <span class="info-value">
                                            <?php 
                                            $intensity_names = array_map(function($intensity) { return $intensity->name; }, $intensities);
                                            echo esc_html(implode(', ', $intensity_names)); 
                                            ?>
                                        </span>
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

                    <!-- Pros and Cons -->
                    <?php if (!empty($pros) || !empty($cons)): ?>
                        <section class="pros-cons-section">
                            <div class="pros-cons-grid">
                                <?php if (!empty($pros)): ?>
                                    <div class="pros">
                                        <h3><?php _e('Предимства', 'parfume-reviews'); ?></h3>
                                        <ul>
                                            <?php foreach (explode("\n", $pros) as $pro): ?>
                                                <?php $pro = trim($pro); ?>
                                                <?php if (!empty($pro)): ?>
                                                    <li><?php echo esc_html($pro); ?></li>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($cons)): ?>
                                    <div class="cons">
                                        <h3><?php _e('Недостатъци', 'parfume-reviews'); ?></h3>
                                        <ul>
                                            <?php foreach (explode("\n", $cons) as $con): ?>
                                                <?php $con = trim($con); ?>
                                                <?php if (!empty($con)): ?>
                                                    <li><?php echo esc_html($con); ?></li>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </section>
                    <?php endif; ?>

                    <!-- Occasions -->
                    <?php if (!empty($occasions)): ?>
                        <section class="occasions-section">
                            <h2><?php _e('Подходящи случаи', 'parfume-reviews'); ?></h2>
                            <div class="occasions-content">
                                <?php echo wp_kses_post(wpautop($occasions)); ?>
                            </div>
                        </section>
                    <?php endif; ?>

                </div>
            </div>
        </div>

        <!-- Колона 2 - Магазини (Desktop Sidebar / Mobile Fixed Panel) -->
        <?php if (!empty($stores_data)): ?>
            <div class="parfume-stores-sidebar" id="parfume-column2" 
                 data-mobile-fixed="<?php echo $show_mobile_fixed ? '1' : '0'; ?>"
                 data-mobile-settings="<?php echo esc_attr(wp_json_encode($global_mobile_settings)); ?>">
                
                <!-- Desktop заглавие -->
                <div class="stores-header desktop-only">
                    <h3><?php _e('Къде да купите', 'parfume-reviews'); ?></h3>
                </div>
                
                <!-- Mobile заглавие и контроли -->
                <div class="stores-header mobile-only">
                    <h3><?php _e('Къде да купите', 'parfume-reviews'); ?></h3>
                    <div class="mobile-controls">
                        <?php if (count($stores_data) > 1): ?>
                            <button class="toggle-stores-btn" aria-label="<?php _e('Показва/скрива магазини', 'parfume-reviews'); ?>">
                                <span class="arrow-icon">↑</span>
                            </button>
                        <?php endif; ?>
                        
                        <?php if (isset($global_mobile_settings['show_close_button']) && $global_mobile_settings['show_close_button']): ?>
                            <button class="close-stores-btn" aria-label="<?php _e('Затвори', 'parfume-reviews'); ?>">
                                <span class="close-icon">×</span>
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Списък с магазини -->
                <div class="stores-list">
                    <?php 
                    $store_index = 0;
                    foreach ($stores_data as $store_id => $store_data): 
                        $store_info = isset($available_stores[$store_id]) ? $available_stores[$store_id] : array();
                        $store_name = isset($store_info['name']) ? $store_info['name'] : __('Неизвестен магазин', 'parfume-reviews');
                        $store_logo_id = isset($store_info['logo_id']) ? $store_info['logo_id'] : 0;
                        
                        // Scraped данни
                        $scraped_data = isset($store_data['scraped_data']) ? $store_data['scraped_data'] : array();
                        $product_url = isset($store_data['product_url']) ? $store_data['product_url'] : '';
                        $affiliate_url = isset($store_data['affiliate_url']) ? $store_data['affiliate_url'] : '';
                        $promo_code = isset($store_data['promo_code']) ? $store_data['promo_code'] : '';
                        $promo_code_info = isset($store_data['promo_code_info']) ? $store_data['promo_code_info'] : '';
                        
                        // Определяваме дали това е първия магазин (за mobile fixed)
                        $is_first_store = ($store_index === 0);
                        $mobile_class = $is_first_store ? 'mobile-fixed-store' : 'mobile-additional-store';
                        ?>
                        
                        <div class="store-item <?php echo esc_attr($mobile_class); ?>" data-store-id="<?php echo esc_attr($store_id); ?>">
                            
                            <!-- Store Header -->
                            <div class="store-header">
                                <!-- Logo -->
                                <?php if ($store_logo_id): ?>
                                    <div class="store-logo">
                                        <?php echo wp_get_attachment_image($store_logo_id, 'thumbnail', false, array('class' => 'store-logo-img')); ?>
                                    </div>
                                <?php endif; ?>
                                
                                <!-- Store Name and Price -->
                                <div class="store-info">
                                    <div class="store-name"><?php echo esc_html($store_name); ?></div>
                                    
                                    <?php if (!empty($scraped_data['prices'])): ?>
                                        <div class="store-price">
                                            <?php 
                                            // Показваме най-ниската цена
                                            $prices = $scraped_data['prices'];
                                            $lowest_price = $prices[0]; // Вече сортирани от scraper
                                            echo esc_html($lowest_price);
                                            ?>
                                            
                                            <!-- Info icon -->
                                            <span class="price-info-icon" title="<?php 
                                                $scraper_settings = get_option('parfume_reviews_scraper_settings', array());
                                                $interval = isset($scraper_settings['scrape_interval']) ? $scraper_settings['scrape_interval'] : 24;
                                                printf(__('Цената се актуализира на всеки %d часа', 'parfume-reviews'), $interval); 
                                            ?>">ⓘ</span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <!-- Store Details -->
                            <div class="store-details">
                                
                                <!-- Variant Information (if single variant) -->
                                <?php if (!empty($scraped_data['variants']) && count($scraped_data['variants']) === 1): ?>
                                    <div class="store-variants single-variant">
                                        <span class="variant-info"><?php echo esc_html($scraped_data['variants'][0]); ?></span>
                                    </div>
                                <?php endif; ?>
                                
                                <!-- Availability and Shipping -->
                                <div class="store-meta">
                                    <?php if (!empty($scraped_data['availability'])): ?>
                                        <div class="availability-info">
                                            <span class="availability-badge available">
                                                <span class="check-icon">✓</span>
                                                <?php echo esc_html($scraped_data['availability']); ?>
                                            </span>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($scraped_data['shipping'])): ?>
                                        <div class="shipping-info">
                                            <?php echo esc_html($scraped_data['shipping']); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Multiple Variants (if more than 1) -->
                                <?php if (!empty($scraped_data['variants']) && count($scraped_data['variants']) > 1): ?>
                                    <div class="store-variants multiple-variants">
                                        <?php foreach ($scraped_data['variants'] as $variant): ?>
                                            <button class="variant-btn" data-variant="<?php echo esc_attr($variant); ?>"
                                                    onclick="window.open('<?php echo esc_url($affiliate_url); ?>', '_blank', 'nofollow')">
                                                <div class="variant-size"><?php echo esc_html($variant); ?></div>
                                                <?php if (!empty($scraped_data['prices'])): ?>
                                                    <div class="variant-price"><?php echo esc_html($scraped_data['prices'][0]); ?></div>
                                                <?php endif; ?>
                                                
                                                <!-- Promotion indicator (if applicable) -->
                                                <?php if (!empty($promo_code)): ?>
                                                    <div class="promotion-indicator">%</div>
                                                <?php endif; ?>
                                            </button>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                                
                                <!-- Action Buttons -->
                                <div class="store-actions">
                                    <?php if (!empty($affiliate_url)): ?>
                                        <?php if (!empty($promo_code)): ?>
                                            <!-- Two buttons: Store + Promo -->
                                            <div class="action-buttons-row">
                                                <a href="<?php echo esc_url($affiliate_url); ?>" 
                                                   class="store-btn main-store-btn" 
                                                   target="_blank" rel="nofollow">
                                                    <?php _e('Към магазина', 'parfume-reviews'); ?>
                                                </a>
                                                
                                                <div class="promo-code-btn" data-code="<?php echo esc_attr($promo_code); ?>"
                                                     data-url="<?php echo esc_url($affiliate_url); ?>">
                                                    <?php if (!empty($promo_code_info)): ?>
                                                        <div class="promo-info"><?php echo esc_html($promo_code_info); ?></div>
                                                    <?php endif; ?>
                                                    <div class="promo-code">
                                                        <?php echo esc_html($promo_code); ?>
                                                        <span class="copy-icon">📋</span>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php else: ?>
                                            <!-- Single button: Store only -->
                                            <a href="<?php echo esc_url($affiliate_url); ?>" 
                                               class="store-btn full-width-btn" 
                                               target="_blank" rel="nofollow">
                                                <?php _e('Към магазина', 'parfume-reviews'); ?>
                                            </a>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Last Updated Info (for admins) -->
                                <?php if (current_user_can('edit_posts') && isset($store_data['last_scraped'])): ?>
                                    <div class="admin-info">
                                        <small>
                                            <?php _e('Последно обновяване:', 'parfume-reviews'); ?>
                                            <?php echo esc_html(date_i18n(get_option('time_format'), strtotime($store_data['last_scraped']))); ?>
                                        </small>
                                    </div>
                                <?php endif; ?>
                                
                            </div>
                        </div>
                        
                        <?php $store_index++; ?>
                    <?php endforeach; ?>
                </div>
                
                <!-- Show Panel Button (when hidden on mobile) -->
                <div class="show-panel-btn" style="display: none;">
                    <button class="show-stores-btn">
                        <span class="arrow-up">↑</span>
                        <?php _e('Къде да купите', 'parfume-reviews'); ?>
                    </button>
                </div>
                
            </div>
        <?php endif; ?>
        
    </div>
</div>

<!-- CSS Styles for Column 2 -->
<style>
.parfume-layout {
    display: flex;
    gap: 30px;
    max-width: 1200px;
    margin: 0 auto;
}

.parfume-main-content {
    flex: 1;
    min-width: 0;
}

.parfume-stores-sidebar {
    width: 350px;
    flex-shrink: 0;
}

/* Desktop Styles */
@media (min-width: 769px) {
    .parfume-stores-sidebar {
        position: sticky;
        top: 20px;
        height: fit-content;
        background: #fff;
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        padding: 20px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    
    .mobile-only {
        display: none !important;
    }
    
    .mobile-additional-store {
        display: block !important;
    }
}

/* Mobile Styles */
@media (max-width: 768px) {
    .parfume-layout {
        flex-direction: column;
        gap: 0;
    }
    
    .parfume-stores-sidebar {
        width: 100%;
        position: fixed;
        bottom: <?php echo isset($global_mobile_settings['bottom_offset']) ? intval($global_mobile_settings['bottom_offset']) : 0; ?>px;
        left: 0;
        right: 0;
        z-index: <?php echo isset($global_mobile_settings['z_index']) ? intval($global_mobile_settings['z_index']) : 9999; ?>;
        background: <?php echo isset($global_mobile_settings['background_color']) ? esc_attr($global_mobile_settings['background_color']) : '#ffffff'; ?>;
        border-top: 2px solid <?php echo isset($global_mobile_settings['border_color']) ? esc_attr($global_mobile_settings['border_color']) : '#e0e0e0'; ?>;
        <?php if (isset($global_mobile_settings['shadow_enabled']) && $global_mobile_settings['shadow_enabled']): ?>
        box-shadow: 0 -2px 10px rgba(0,0,0,0.1);
        <?php endif; ?>
        padding: 15px;
        transition: transform <?php echo isset($global_mobile_settings['animation_duration']) ? intval($global_mobile_settings['animation_duration']) : 300; ?>ms ease;
    }
    
    .parfume-stores-sidebar.stores-hidden {
        transform: translateY(100%);
    }
    
    .desktop-only {
        display: none !important;
    }
    
    .mobile-additional-store {
        display: none;
    }
    
    .mobile-additional-store.shown {
        display: block;
        animation: slideDown <?php echo isset($global_mobile_settings['animation_duration']) ? intval($global_mobile_settings['animation_duration']) : 300; ?>ms ease;
    }
    
    .stores-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 15px;
    }
    
    .mobile-controls {
        display: flex;
        gap: 10px;
    }
    
    .toggle-stores-btn, .close-stores-btn, .show-stores-btn {
        background: none;
        border: 1px solid #ddd;
        border-radius: 50%;
        width: 30px;
        height: 30px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        font-size: 16px;
        color: #666;
    }
    
    .show-panel-btn {
        position: fixed;
        bottom: <?php echo isset($global_mobile_settings['bottom_offset']) ? intval($global_mobile_settings['bottom_offset']) : 0; ?>px;
        right: 20px;
        z-index: <?php echo isset($global_mobile_settings['z_index']) ? intval($global_mobile_settings['z_index']) : 9999; ?>;
    }
    
    .show-stores-btn {
        width: auto;
        height: auto;
        border-radius: 20px;
        padding: 8px 15px;
        background: <?php echo isset($global_mobile_settings['background_color']) ? esc_attr($global_mobile_settings['background_color']) : '#ffffff'; ?>;
        font-size: 12px;
        display: flex;
        align-items: center;
        gap: 5px;
    }
}

/* Store Item Styles */
.store-item {
    margin-bottom: 20px;
    padding: 15px;
    border: 1px solid #e0e0e0;
    border-radius: 6px;
    background: #fafafa;
}

.store-item:last-child {
    margin-bottom: 0;
}

.store-header {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 10px;
}

.store-logo-img {
    width: 40px;
    height: auto;
    border-radius: 4px;
}

.store-info {
    flex: 1;
}

.store-name {
    font-weight: 600;
    font-size: 14px;
    margin-bottom: 2px;
}

.store-price {
    color: #ff6600;
    font-weight: 700;
    font-size: 16px;
    display: flex;
    align-items: center;
    gap: 5px;
}

.price-info-icon {
    font-size: 12px;
    color: #999;
    cursor: help;
}

.store-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-bottom: 10px;
}

.availability-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    background: #d4edda;
    color: #155724;
    padding: 3px 8px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
}

.shipping-info {
    font-size: 12px;
    color: #666;
}

.store-variants.multiple-variants {
    display: flex;
    gap: 8px;
    margin-bottom: 15px;
    flex-wrap: wrap;
}

.variant-btn {
    flex: 1;
    min-width: 80px;
    padding: 10px 8px;
    border: 1px solid #ddd;
    border-radius: 4px;
    background: #fff;
    cursor: pointer;
    text-align: center;
    position: relative;
    transition: all 0.2s ease;
}

.variant-btn:hover {
    border-color: #ff6600;
    background: #fff5f0;
}

.variant-size {
    font-size: 12px;
    font-weight: 600;
    margin-bottom: 2px;
}

.variant-price {
    font-size: 11px;
    color: #ff6600;
}

.promotion-indicator {
    position: absolute;
    top: -5px;
    right: -5px;
    background: #ff6600;
    color: white;
    width: 18px;
    height: 18px;
    border-radius: 50%;
    font-size: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
}

.store-actions {
    margin-top: 15px;
}

.action-buttons-row {
    display: flex;
    gap: 10px;
}

.store-btn {
    display: inline-block;
    padding: 12px 20px;
    background: #ff6600;
    color: white;
    text-decoration: none;
    border-radius: 4px;
    text-align: center;
    font-weight: 600;
    font-size: 14px;
    transition: background 0.2s ease;
}

.store-btn:hover {
    background: #e55a00;
    color: white;
    text-decoration: none;
}

.main-store-btn {
    flex: 1;
}

.full-width-btn {
    width: 100%;
}

.promo-code-btn {
    flex: 1;
    border: 2px dashed #dc3545;
    border-radius: 4px;
    padding: 8px;
    cursor: pointer;
    position: relative;
    background: #fff;
    transition: all 0.2s ease;
}

.promo-code-btn:hover {
    background: #f8f9fa;
}

.promo-info {
    font-size: 10px;
    color: #666;
    margin-bottom: 4px;
    text-align: center;
}

.promo-code {
    font-weight: bold;
    color: #dc3545;
    text-align: center;
    font-size: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 5px;
}

.copy-icon {
    font-size: 10px;
}

.admin-info {
    margin-top: 10px;
    padding-top: 10px;
    border-top: 1px solid #eee;
    text-align: center;
}

/* Animations */
@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Rating Stars */
.rating-stars {
    display: inline-flex;
    gap: 2px;
}

.rating-stars .star {
    color: #ddd;
    font-size: 18px;
}

.rating-stars .star.filled {
    color: #ffd700;
}

/* Info Grid */
.parfume-quick-info {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 10px;
    margin-top: 15px;
}

.info-item {
    display: flex;
    justify-content: space-between;
    padding: 5px 0;
    border-bottom: 1px solid #eee;
}

.info-label {
    font-weight: 600;
    color: #666;
}

.info-value {
    color: #333;
}

/* Notes Grid */
.notes-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
}

.note-item {
    background: #f0f0f0;
    border-radius: 15px;
    padding: 5px 12px;
    font-size: 14px;
}

.note-link {
    color: #333;
    text-decoration: none;
}

.note-link:hover {
    color: #ff6600;
}

/* Pros and Cons */
.pros-cons-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 30px;
}

@media (max-width: 768px) {
    .pros-cons-grid {
        grid-template-columns: 1fr;
        gap: 20px;
    }
}

.pros h3 {
    color: #28a745;
}

.cons h3 {
    color: #dc3545;
}

.pros ul, .cons ul {
    list-style: none;
    padding: 0;
}

.pros li::before {
    content: "✓ ";
    color: #28a745;
    font-weight: bold;
}

.cons li::before {
    content: "✗ ";
    color: #dc3545;
    font-weight: bold;
}

</style>

<!-- JavaScript for Column 2 Functionality -->
<script type="text/javascript">
jQuery(document).ready(function($) {
    
    // Mobile functionality
    if (window.innerWidth <= 768) {
        initMobileBehavior();
    }
    
    // Promo code functionality
    $('.promo-code-btn').on('click', function() {
        var code = $(this).data('code');
        var url = $(this).data('url');
        
        // Copy to clipboard
        if (navigator.clipboard) {
            navigator.clipboard.writeText(code).then(function() {
                showNotification(parfumeColumn2.strings.copied);
                // Redirect after short delay
                setTimeout(function() {
                    window.open(url, '_blank');
                }, 500);
            }).catch(function() {
                showNotification(parfumeColumn2.strings.copy_failed);
            });
        } else {
            // Fallback for older browsers
            var textArea = document.createElement('textarea');
            textArea.value = code;
            document.body.appendChild(textArea);
            textArea.select();
            try {
                document.execCommand('copy');
                showNotification(parfumeColumn2.strings.copied);
                setTimeout(function() {
                    window.open(url, '_blank');
                }, 500);
            } catch (err) {
                showNotification(parfumeColumn2.strings.copy_failed);
            }
            document.body.removeChild(textArea);
        }
    });
    
    function initMobileBehavior() {
        var $sidebar = $('#parfume-column2');
        var $toggleBtn = $('.toggle-stores-btn');
        var $closeBtn = $('.close-stores-btn');
        var $showBtn = $('.show-stores-btn');
        var $additionalStores = $('.mobile-additional-store');
        
        var isExpanded = false;
        var isHidden = false;
        
        // Toggle additional stores
        $toggleBtn.on('click', function() {
            if (isExpanded) {
                $additionalStores.removeClass('shown').fadeOut(300);
                $(this).find('.arrow-icon').text('↑');
                isExpanded = false;
            } else {
                $additionalStores.addClass('shown').fadeIn(300);
                $(this).find('.arrow-icon').text('↓');
                isExpanded = true;
            }
        });
        
        // Close panel
        $closeBtn.on('click', function() {
            $sidebar.addClass('stores-hidden');
            $('.show-panel-btn').fadeIn(300);
            isHidden = true;
        });
        
        // Show panel
        $showBtn.on('click', function() {
            $sidebar.removeClass('stores-hidden');
            $('.show-panel-btn').fadeOut(300);
            isHidden = false;
        });
        
        // Auto-detect conflicts with other fixed elements
        if (parfumeColumn2.mobile_settings.detect_conflicts) {
            detectFixedConflicts();
        }
    }
    
    function detectFixedConflicts() {
        var selectors = parfumeColumn2.mobile_settings.conflict_selectors || '';
        var conflictElements = selectors.split('\n').filter(function(selector) {
            return selector.trim() !== '';
        });
        
        var totalHeight = 0;
        conflictElements.forEach(function(selector) {
            var $element = $(selector.trim());
            if ($element.length && $element.css('position') === 'fixed') {
                var bottom = $element.css('bottom');
                if (bottom !== 'auto' && bottom !== '') {
                    totalHeight += $element.outerHeight();
                }
            }
        });
        
        if (totalHeight > 0) {
            $('#parfume-column2').css('bottom', totalHeight + 'px');
            $('.show-panel-btn').css('bottom', totalHeight + 'px');
        }
    }
    
    function showNotification(message) {
        // Simple notification
        var $notification = $('<div class="parfume-notification">' + message + '</div>');
        $notification.css({
            position: 'fixed',
            top: '20px',
            right: '20px',
            background: '#333',
            color: 'white',
            padding: '10px 15px',
            borderRadius: '4px',
            zIndex: 99999,
            fontSize: '14px'
        });
        
        $('body').append($notification);
        
        setTimeout(function() {
            $notification.fadeOut(300, function() {
                $(this).remove();
            });
        }, 2000);
    }
    
});
</script>

<?php get_footer(); ?>