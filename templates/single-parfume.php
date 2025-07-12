<?php
get_header();

while (have_posts()): the_post();
    $post_id = get_the_ID();
    
    // Get all taxonomies and meta data
    $brands = wp_get_post_terms($post_id, 'marki', array('fields' => 'names'));
    $notes = wp_get_post_terms($post_id, 'notes');
    $perfumers = wp_get_post_terms($post_id, 'perfumer', array('fields' => 'names'));
    $aroma_types = wp_get_post_terms($post_id, 'aroma_type', array('fields' => 'names'));
    $seasons = wp_get_post_terms($post_id, 'season', array('fields' => 'names'));
    $intensities = wp_get_post_terms($post_id, 'intensity', array('fields' => 'names'));
    $genders = wp_get_post_terms($post_id, 'gender', array('fields' => 'names'));
    
    // Meta fields
    $rating = get_post_meta($post_id, '_parfume_rating', true);
    $gender_text = get_post_meta($post_id, '_parfume_gender', true);
    $release_year = get_post_meta($post_id, '_parfume_release_year', true);
    $longevity = get_post_meta($post_id, '_parfume_longevity', true);
    $sillage = get_post_meta($post_id, '_parfume_sillage', true);
    $bottle_size = get_post_meta($post_id, '_parfume_bottle_size', true);
    
    // New meta fields for characteristics
    $longevity_level = get_post_meta($post_id, '_parfume_longevity_level', true) ?: 3;
    $sillage_level = get_post_meta($post_id, '_parfume_sillage_level', true) ?: 3;
    $gender_level = get_post_meta($post_id, '_parfume_gender_level', true) ?: 3;
    $price_level = get_post_meta($post_id, '_parfume_price_level', true) ?: 3;
    $suitable_times = get_post_meta($post_id, '_parfume_suitable_times', true) ?: array();
    
    // Advantages and disadvantages
    $advantages = get_post_meta($post_id, '_parfume_advantages', true) ?: array();
    $disadvantages = get_post_meta($post_id, '_parfume_disadvantages', true) ?: array();
    
    // Stores data (new version)
    $stores_v2 = get_post_meta($post_id, '_parfume_stores_v2', true) ?: array();
    
    // Handle WP_Error for taxonomy terms
    if (is_wp_error($brands)) $brands = array();
    if (is_wp_error($notes)) $notes = array();
    if (is_wp_error($perfumers)) $perfumers = array();
    if (is_wp_error($aroma_types)) $aroma_types = array();
    if (is_wp_error($seasons)) $seasons = array();
    if (is_wp_error($intensities)) $intensities = array();
    if (is_wp_error($genders)) $genders = array();
    
    $settings = get_option('parfume_reviews_settings', array());
?>

<div class="parfume-single-new-layout">
    <!-- Main Container: 70% / 30% split -->
    <div class="parfume-main-container">
        
        <!-- ЛЯВА КОЛОНА 1 (70%) -->
        <div class="parfume-left-column">
            
            <!-- Header Section: Logo + Title + Basic Info -->
            <div class="parfume-header-section">
                <div class="parfume-image-title">
                    <div class="parfume-featured-image">
                        <?php if (has_post_thumbnail()): ?>
                            <?php the_post_thumbnail('large'); ?>
                        <?php else: ?>
                            <div class="no-image-placeholder">
                                <span class="placeholder-icon">📦</span>
                                <span class="placeholder-text"><?php _e('No Image', 'parfume-reviews'); ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="parfume-title-info">
                        <h1 class="parfume-title"><?php the_title(); ?></h1>
                        
                        <div class="parfume-basic-info">
                            <!-- Aroma Type -->
                            <?php if (!empty($aroma_types)): ?>
                                <div class="info-item aroma-type">
                                    <span class="info-label"><?php _e('Type:', 'parfume-reviews'); ?></span>
                                    <span class="info-value"><?php echo esc_html(implode(', ', $aroma_types)); ?></span>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Brand -->
                            <?php if (!empty($brands)): ?>
                                <div class="info-item brand">
                                    <span class="info-label"><?php _e('Brand:', 'parfume-reviews'); ?></span>
                                    <span class="info-value"><?php echo esc_html(implode(', ', $brands)); ?></span>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Comparison Button -->
                            <div class="info-item comparison-btn">
                                <?php echo parfume_reviews_get_comparison_button($post_id); ?>
                            </div>
                            
                            <!-- Main Notes (first 5) -->
                            <?php if (!empty($notes)): ?>
                                <div class="info-item main-notes">
                                    <span class="info-label"><?php _e('Main Notes:', 'parfume-reviews'); ?></span>
                                    <div class="notes-list">
                                        <?php foreach (array_slice($notes, 0, 5) as $note): ?>
                                            <span class="note-tag">
                                                <?php echo parfume_reviews_get_note_icon($note->term_id); ?>
                                                <?php echo esc_html($note->name); ?>
                                            </span>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Seasonal and Time Suitability -->
            <div class="parfume-suitability-section">
                <h3><?php _e('Suitable for', 'parfume-reviews'); ?></h3>
                <?php echo parfume_reviews_get_seasonal_icons($post_id); ?>
            </div>
            
            <!-- Content / Description -->
            <div class="parfume-content-section">
                <h3><?php _e('Description', 'parfume-reviews'); ?></h3>
                <div class="parfume-description">
                    <?php the_content(); ?>
                </div>
            </div>
            
            <!-- Notes Pyramid Section -->
            <div class="parfume-composition-section">
                <h3><?php _e('Composition', 'parfume-reviews'); ?></h3>
                <?php echo parfume_reviews_get_notes_with_icons($post_id, 'all'); ?>
            </div>
            
            <!-- Fragrance Characteristics Graph -->
            <div class="parfume-characteristics-section">
                <h3><?php _e('Fragrance Characteristics', 'parfume-reviews'); ?></h3>
                
                <div class="characteristics-grid">
                    <!-- Row 1: Longevity & Sillage -->
                    <div class="characteristics-row">
                        <div class="characteristic-item">
                            <h4><?php _e('LONGEVITY', 'parfume-reviews'); ?></h4>
                            <?php 
                            $longevity_labels = array(
                                __('Very Weak', 'parfume-reviews'),
                                __('Weak', 'parfume-reviews'), 
                                __('Moderate', 'parfume-reviews'),
                                __('Long-lasting', 'parfume-reviews'),
                                __('Extremely Long-lasting', 'parfume-reviews')
                            );
                            echo parfume_reviews_get_progress_bar($longevity_level, 5, $longevity_labels);
                            ?>
                        </div>
                        
                        <div class="characteristic-item">
                            <h4><?php _e('SILLAGE', 'parfume-reviews'); ?></h4>
                            <?php 
                            $sillage_labels = array(
                                __('Weak', 'parfume-reviews'),
                                __('Moderate', 'parfume-reviews'),
                                __('Strong', 'parfume-reviews'),
                                __('Enormous', 'parfume-reviews')
                            );
                            echo parfume_reviews_get_progress_bar($sillage_level, 4, $sillage_labels);
                            ?>
                        </div>
                    </div>
                    
                    <!-- Row 2: Gender & Price -->
                    <div class="characteristics-row">
                        <div class="characteristic-item">
                            <h4><?php _e('GENDER', 'parfume-reviews'); ?></h4>
                            <?php 
                            $gender_labels = array(
                                __('Feminine', 'parfume-reviews'),
                                __('Masculine', 'parfume-reviews'),
                                __('Unisex', 'parfume-reviews'),
                                __('Younger', 'parfume-reviews'),
                                __('Mature', 'parfume-reviews')
                            );
                            echo parfume_reviews_get_progress_bar($gender_level, 5, $gender_labels);
                            ?>
                        </div>
                        
                        <div class="characteristic-item">
                            <h4><?php _e('PRICE', 'parfume-reviews'); ?></h4>
                            <?php 
                            $price_labels = array(
                                __('Too Expensive', 'parfume-reviews'),
                                __('Expensive', 'parfume-reviews'),
                                __('Fair Price', 'parfume-reviews'),
                                __('Good Price', 'parfume-reviews'),
                                __('Cheap', 'parfume-reviews')
                            );
                            echo parfume_reviews_get_progress_bar($price_level, 5, $price_labels);
                            ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Advantages and Disadvantages -->
            <?php if (!empty($advantages) || !empty($disadvantages)): ?>
                <div class="parfume-pros-cons-section">
                    <?php echo parfume_reviews_get_advantages_disadvantages($post_id); ?>
                </div>
            <?php endif; ?>
            
            <!-- Similar Products -->
            <div class="parfume-similar-section">
                <?php echo parfume_reviews_get_similar_products($post_id, 
                    isset($settings['similar_products_count']) ? $settings['similar_products_count'] : 4
                ); ?>
            </div>
            
            <!-- Recently Viewed -->
            <div class="parfume-recently-viewed-section">
                <?php echo do_shortcode('[parfume_recently_viewed limit="' . 
                    (isset($settings['recently_viewed_count']) ? $settings['recently_viewed_count'] : 4) . 
                    '" title="' . __('Recently Viewed', 'parfume-reviews') . '"]'); ?>
            </div>
            
            <!-- Other Brand Products -->
            <?php if (!empty($brands)): ?>
                <div class="parfume-brand-products-section">
                    <?php echo do_shortcode('[parfume_brand_products limit="' . 
                        (isset($settings['brand_products_count']) ? $settings['brand_products_count'] : 4) . 
                        '" title="' . sprintf(__('Other perfumes from %s', 'parfume-reviews'), $brands[0]) . '"]'); ?>
                </div>
            <?php endif; ?>
            
            <!-- User Reviews Section -->
            <div class="parfume-user-reviews-section">
                <?php echo Parfume_Reviews\Comments::render_reviews_section($post_id); ?>
            </div>
            
        </div>
        
        <!-- ДЯСНА КОЛОНА 2 (30%) -->
        <div class="parfume-right-column">
            <div class="parfume-stores-sidebar" id="parfume-stores-sidebar">
                <?php if (!empty($stores_v2)): ?>
                    <?php foreach ($stores_v2 as $index => $store): ?>
                        <?php if (!empty($store['store_id'])): ?>
                            <div class="store-item-sidebar" data-store-index="<?php echo esc_attr($index); ?>">
                                <?php echo parfume_reviews_render_single_store($store, $post_id); ?>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="no-stores-message">
                        <p><?php _e('No stores available for this perfume yet.', 'parfume-reviews'); ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
    </div>
</div>

<style>
/* Main Layout Styles */
.parfume-single-new-layout {
    max-width: 1400px;
    margin: 0 auto;
    padding: 20px;
}

.parfume-main-container {
    display: grid;
    grid-template-columns: 70% 30%;
    gap: 40px;
    align-items: start;
}

/* Left Column Styles */
.parfume-left-column {
    display: flex;
    flex-direction: column;
    gap: 40px;
}

/* Header Section */
.parfume-header-section {
    background: #fff;
    border-radius: 12px;
    padding: 30px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.parfume-image-title {
    display: grid;
    grid-template-columns: 300px 1fr;
    gap: 30px;
    align-items: start;
}

.parfume-featured-image img {
    width: 100%;
    height: auto;
    border-radius: 8px;
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
}

.no-image-placeholder {
    width: 300px;
    height: 300px;
    background: #f8f9fa;
    border: 2px dashed #dee2e6;
    border-radius: 8px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    color: #6c757d;
}

.placeholder-icon {
    font-size: 3em;
    margin-bottom: 10px;
}

.parfume-title {
    font-size: 2.5em;
    color: #333;
    margin: 0 0 20px 0;
    line-height: 1.2;
}

.parfume-basic-info {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.info-item {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}

.info-label {
    font-weight: bold;
    color: #666;
    min-width: 60px;
}

.info-value {
    color: #333;
}

.notes-list {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.note-tag {
    background: #e3f2fd;
    color: #1976d2;
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 0.9em;
    display: flex;
    align-items: center;
    gap: 4px;
}

.note-icon, .note-icon-fallback {
    width: 16px;
    height: 16px;
}

/* Suitability Section */
.parfume-suitability-section {
    background: #fff;
    border-radius: 12px;
    padding: 25px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.parfume-suitability-section h3 {
    margin: 0 0 15px 0;
    color: #333;
    font-size: 1.3em;
}

.seasonal-time-info {
    display: flex;
    gap: 20px;
    align-items: center;
}

.seasonal-icons, .time-icons {
    display: flex;
    gap: 10px;
}

.season-icon, .time-icon {
    font-size: 2em;
    padding: 8px;
    background: #f8f9fa;
    border-radius: 50%;
    border: 2px solid #e9ecef;
    transition: all 0.3s ease;
}

.season-icon:hover, .time-icon:hover {
    transform: scale(1.1);
    box-shadow: 0 4px 8px rgba(0,0,0,0.2);
}

/* Content Section */
.parfume-content-section {
    background: #fff;
    border-radius: 12px;
    padding: 30px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.parfume-content-section h3 {
    margin: 0 0 20px 0;
    color: #333;
    font-size: 1.5em;
    border-bottom: 2px solid #0073aa;
    padding-bottom: 10px;
}

.parfume-description {
    line-height: 1.8;
    color: #555;
}

/* Composition Section */
.parfume-composition-section {
    background: #fff;
    border-radius: 12px;
    padding: 30px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.parfume-composition-section h3 {
    margin: 0 0 25px 0;
    color: #333;
    font-size: 1.5em;
    text-align: center;
}

.notes-pyramid {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.pyramid-level {
    padding: 20px;
    border-radius: 10px;
    text-align: center;
    position: relative;
}

.top-notes {
    background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
    margin: 0 0px;
}

.middle-notes {
    background: linear-gradient(135deg, #bbdefb 0%, #90caf9 100%);
    margin: 0 30px;
}

.base-notes {
    background: linear-gradient(135deg, #90caf9 0%, #64b5f6 100%);
    margin: 0 60px;
}

.pyramid-level h4 {
    margin: 0 0 15px 0;
    color: #1976d2;
    font-weight: bold;
}

.pyramid-level .notes-list {
    justify-content: center;
}

/* Characteristics Section */
.parfume-characteristics-section {
    background: #fff;
    border-radius: 12px;
    padding: 30px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.parfume-characteristics-section h3 {
    margin: 0 0 30px 0;
    color: #333;
    font-size: 1.5em;
    text-align: center;
}

.characteristics-grid {
    display: flex;
    flex-direction: column;
    gap: 30px;
}

.characteristics-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 30px;
}

.characteristic-item h4 {
    margin: 0 0 15px 0;
    color: #666;
    font-size: 1.1em;
    text-align: center;
    letter-spacing: 1px;
}

.progress-bar-container {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.progress-bar-item {
    display: flex;
    align-items: center;
    gap: 10px;
}

.progress-bar {
    height: 8px;
    background: #e9ecef;
    border-radius: 4px;
    flex: 1;
    position: relative;
    overflow: hidden;
}

.progress-bar-item.active .progress-bar {
    background: linear-gradient(90deg, #28a745 0%, #20c997 100%);
}

.progress-label {
    font-size: 0.9em;
    color: #666;
    min-width: 120px;
}

.progress-bar-item.active .progress-label {
    color: #28a745;
    font-weight: bold;
}

/* Pros and Cons Section */
.parfume-pros-cons-section {
    background: #fff;
    border-radius: 12px;
    padding: 30px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.advantages-disadvantages h3 {
    margin: 0 0 25px 0;
    color: #333;
    font-size: 1.5em;
    text-align: center;
}

.advantages-disadvantages-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 30px;
}

.advantages-title, .disadvantages-title {
    display: flex;
    align-items: center;
    gap: 8px;
    margin: 0 0 15px 0;
    font-size: 1.2em;
}

.advantages-title {
    color: #28a745;
}

.disadvantages-title {
    color: #dc3545;
}

.advantages-list, .disadvantages-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.advantage-item, .disadvantage-item {
    display: flex;
    align-items: flex-start;
    gap: 8px;
    margin-bottom: 10px;
    padding: 8px 0;
}

.advantage-item .icon-check {
    color: #28a745;
    font-weight: bold;
}

.disadvantage-item .icon-cross {
    color: #dc3545;
    font-weight: bold;
}

/* Similar, Recently Viewed, Brand Products Sections */
.parfume-similar-section,
.parfume-recently-viewed-section,
.parfume-brand-products-section {
    background: #fff;
    border-radius: 12px;
    padding: 30px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.parfume-similar-section h3,
.parfume-recently-viewed-section h3,
.parfume-brand-products-section h3 {
    margin: 0 0 20px 0;
    color: #333;
    font-size: 1.3em;
}

.products-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
}

.product-item {
    text-align: center;
    transition: transform 0.3s ease;
}

.product-item:hover {
    transform: translateY(-5px);
}

.product-image img {
    width: 100%;
    height: 200px;
    object-fit: cover;
    border-radius: 8px;
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.product-title {
    margin: 10px 0 0 0;
    font-size: 1em;
    color: #333;
}

/* User Reviews Section */
.parfume-user-reviews-section {
    background: #fff;
    border-radius: 12px;
    padding: 30px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

/* Right Column - Stores Sidebar */
.parfume-right-column {
    position: sticky;
    top: 20px;
    max-height: calc(100vh - 40px);
    overflow-y: auto;
}

.parfume-stores-sidebar {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.store-item-sidebar {
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    overflow: hidden;
    transition: transform 0.3s ease;
}

.store-item-sidebar:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
}

.single-store-display {
    padding: 20px;
}

.store-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}

.store-logo img {
    max-height: 40px;
    max-width: 120px;
    object-fit: contain;
}

.store-name-text {
    font-weight: bold;
    color: #333;
}

.price-display {
    text-align: right;
}

.old-price {
    text-decoration: line-through;
    color: #999;
    font-size: 0.9em;
    display: block;
}

.current-price {
    font-size: 1.3em;
    font-weight: bold;
    color: #e74c3c;
}

.discount-percent {
    font-size: 0.8em;
    color: #27ae60;
    font-weight: bold;
}

.price-update-info {
    margin-top: 5px;
}

.info-icon {
    cursor: help;
    color: #0073aa;
    font-style: normal;
}

.store-product-info {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-bottom: 15px;
}

.single-variant {
    background: #f8f9fa;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 0.9em;
}

.availability-badge {
    background: #d4edda;
    color: #155724;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 0.9em;
    display: flex;
    align-items: center;
    gap: 4px;
}

.check-icon {
    color: #28a745;
}

.delivery-info {
    font-size: 0.9em;
    color: #666;
}

.store-variants {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(80px, 1fr));
    gap: 8px;
    margin-bottom: 15px;
}

.variant-button {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 8px 4px;
    border: 1px solid #dee2e6;
    border-radius: 6px;
    text-decoration: none;
    color: #333;
    transition: all 0.3s ease;
    position: relative;
}

.variant-button:hover {
    border-color: #0073aa;
    background: #f8f9fa;
}

.discount-badge {
    position: absolute;
    top: -4px;
    right: -4px;
    background: #e74c3c;
    color: white;
    border-radius: 50%;
    width: 16px;
    height: 16px;
    font-size: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.variant-ml {
    font-size: 0.8em;
    font-weight: bold;
}

.variant-price {
    font-size: 0.9em;
    color: #e74c3c;
}

.store-actions {
    margin-top: 15px;
}

.action-buttons-split {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 8px;
}

.btn-shop {
    background: #ff6b35;
    color: white;
    padding: 10px 15px;
    border-radius: 6px;
    text-decoration: none;
    text-align: center;
    font-weight: bold;
    transition: background 0.3s ease;
}

.btn-shop:hover {
    background: #e55a2b;
    color: white;
}

.btn-shop.full-width {
    grid-column: 1 / -1;
}

.promo-code-button {
    border: 2px dashed #dc3545;
    border-radius: 6px;
    padding: 8px;
    cursor: pointer;
    transition: all 0.3s ease;
    background: #fff;
    position: relative;
}

.promo-code-button:hover {
    background: #f8f9fa;
}

.promo-info {
    font-size: 0.7em;
    color: #666;
    margin-bottom: 4px;
    text-align: center;
}

.promo-code-display {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 4px;
}

.promo-code {
    font-weight: bold;
    font-size: 0.9em;
    color: #333;
    letter-spacing: 1px;
}

.copy-icon {
    font-size: 0.8em;
}

.no-stores-message {
    background: #fff;
    border-radius: 12px;
    padding: 30px;
    text-align: center;
    color: #666;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

/* Mobile Responsive */
@media (max-width: 1024px) {
    .parfume-main-container {
        grid-template-columns: 65% 35%;
        gap: 25px;
    }
    
    .parfume-image-title {
        grid-template-columns: 250px 1fr;
        gap: 20px;
    }
    
    .characteristics-row {
        grid-template-columns: 1fr;
        gap: 20px;
    }
}

@media (max-width: 768px) {
    .parfume-main-container {
        grid-template-columns: 1fr;
        gap: 20px;
    }
    
    .parfume-right-column {
        position: relative;
        max-height: none;
        order: -1;
    }
    
    .parfume-stores-sidebar {
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        z-index: 1000;
        background: white;
        box-shadow: 0 -4px 15px rgba(0,0,0,0.2);
        max-height: 50vh;
        overflow-y: auto;
        border-radius: 15px 15px 0 0;
    }
    
    .parfume-image-title {
        grid-template-columns: 1fr;
        gap: 20px;
        text-align: center;
    }
    
    .parfume-featured-image,
    .no-image-placeholder {
        max-width: 300px;
        margin: 0 auto;
    }
    
    .advantages-disadvantages-grid {
        grid-template-columns: 1fr;
    }
    
    .products-grid {
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    }
    
    .store-variants {
        grid-template-columns: repeat(3, 1fr);
    }
    
    .action-buttons-split {
        grid-template-columns: 1fr;
        gap: 8px;
    }
}

@media (max-width: 480px) {
    .parfume-single-new-layout {
        padding: 10px;
    }
    
    .parfume-left-column {
        gap: 20px;
    }
    
    .parfume-header-section,
    .parfume-content-section,
    .parfume-composition-section,
    .parfume-characteristics-section {
        padding: 20px;
    }
    
    .parfume-title {
        font-size: 1.8em;
    }
    
    .products-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Promo code copy functionality
    document.querySelectorAll('.promo-code-button').forEach(function(button) {
        button.addEventListener('click', function() {
            const code = this.dataset.code;
            const url = this.dataset.url;
            
            if (code) {
                // Copy to clipboard
                navigator.clipboard.writeText(code).then(function() {
                    // Show copied message
                    const originalText = button.querySelector('.promo-code').textContent;
                    button.querySelector('.promo-code').textContent = 'Copied!';
                    
                    setTimeout(function() {
                        button.querySelector('.promo-code').textContent = originalText;
                    }, 1500);
                    
                    // Redirect after short delay
                    if (url) {
                        setTimeout(function() {
                            window.open(url, '_blank');
                        }, 1000);
                    }
                });
            }
        });
    });
    
    // Mobile stores sidebar behavior
    if (window.innerWidth <= 768) {
        const sidebar = document.querySelector('.parfume-stores-sidebar');
        const firstStore = sidebar.querySelector('.store-item-sidebar');
        
        if (sidebar && firstStore) {
            // Show only first store initially
            const allStores = sidebar.querySelectorAll('.store-item-sidebar');
            if (allStores.length > 1) {
                for (let i = 1; i < allStores.length; i++) {
                    allStores[i].style.display = 'none';
                }
                
                // Add toggle button
                const toggleBtn = document.createElement('button');
                toggleBtn.className = 'stores-toggle-btn';
                toggleBtn.innerHTML = '▲ ' + (allStores.length - 1) + ' more stores';
                toggleBtn.style.cssText = 'width: 100%; padding: 10px; background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 6px; margin-top: 10px;';
                
                firstStore.appendChild(toggleBtn);
                
                let isOpen = false;
                toggleBtn.addEventListener('click', function() {
                    isOpen = !isOpen;
                    
                    for (let i = 1; i < allStores.length; i++) {
                        allStores[i].style.display = isOpen ? 'block' : 'none';
                    }
                    
                    toggleBtn.innerHTML = (isOpen ? '▼ Hide stores' : '▲ ' + (allStores.length - 1) + ' more stores');
                });
            }
        }
    }
});
</script>

<?php
endwhile;

get_footer();
?>