<?php
namespace Parfume_Reviews;

class Shortcodes {
    public function __construct() {
        add_shortcode('parfume_rating', array($this, 'rating_shortcode'));
        add_shortcode('parfume_details', array($this, 'details_shortcode'));
        add_shortcode('parfume_stores', array($this, 'stores_shortcode'));
        add_shortcode('parfume_filters', array($this, 'filters_shortcode'));
        add_shortcode('parfume_similar', array($this, 'similar_shortcode'));
        add_shortcode('parfume_brand_products', array($this, 'brand_products_shortcode'));
        add_shortcode('parfume_recently_viewed', array($this, 'recently_viewed_shortcode'));
    }
    
    public function rating_shortcode($atts) {
        global $post;
        
        if (!$post || $post->post_type !== 'parfume') {
            return '';
        }
        
        $atts = shortcode_atts(array(
            'show_empty' => true,
            'show_average' => true,
        ), $atts);
        
        // Convert string values to boolean
        $show_empty = filter_var($atts['show_empty'], FILTER_VALIDATE_BOOLEAN);
        $show_average = filter_var($atts['show_average'], FILTER_VALIDATE_BOOLEAN);
        
        $rating = get_post_meta($post->ID, '_parfume_rating', true);
        $rating = !empty($rating) ? floatval($rating) : 0;
        
        if (empty($rating) && !$show_empty) {
            return '';
        }
        
        ob_start();
        ?>
        <div class="parfume-rating">
            <div class="rating-stars">
                <?php for ($i = 1; $i <= 5; $i++): ?>
                    <span class="star <?php echo $i <= round($rating) ? 'filled' : ''; ?>">★</span>
                <?php endfor; ?>
            </div>
            <?php if ($show_average && $rating > 0): ?>
                <div class="rating-average"><?php echo number_format($rating, 1); ?>/5</div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
    
    public function details_shortcode($atts) {
        global $post;
        
        if (!$post || $post->post_type !== 'parfume') {
            return '';
        }
        
        $atts = shortcode_atts(array(
            'show_empty' => true,
        ), $atts);
        
        $show_empty = filter_var($atts['show_empty'], FILTER_VALIDATE_BOOLEAN);
        
        $gender = get_post_meta($post->ID, '_parfume_gender', true);
        $release_year = get_post_meta($post->ID, '_parfume_release_year', true);
        $longevity = get_post_meta($post->ID, '_parfume_longevity', true);
        $sillage = get_post_meta($post->ID, '_parfume_sillage', true);
        $bottle_size = get_post_meta($post->ID, '_parfume_bottle_size', true);
        
        $has_details = !empty($gender) || !empty($release_year) || !empty($longevity) || !empty($sillage) || !empty($bottle_size);
        
        if (!$has_details && !$show_empty) {
            return '';
        }
        
        ob_start();
        ?>
        <div class="parfume-details">
            <?php if ($has_details): ?>
                <ul>
                    <?php if (!empty($gender)): ?>
                        <li><strong><?php _e('Пол:', 'parfume-reviews'); ?></strong> <?php echo esc_html($gender); ?></li>
                    <?php endif; ?>
                    
                    <?php if (!empty($release_year)): ?>
                        <li><strong><?php _e('Година на издаване:', 'parfume-reviews'); ?></strong> <?php echo esc_html($release_year); ?></li>
                    <?php endif; ?>
                    
                    <?php if (!empty($longevity)): ?>
                        <li><strong><?php _e('Издръжливост:', 'parfume-reviews'); ?></strong> <?php echo esc_html($longevity); ?></li>
                    <?php endif; ?>
                    
                    <?php if (!empty($sillage)): ?>
                        <li><strong><?php _e('Силаж:', 'parfume-reviews'); ?></strong> <?php echo esc_html($sillage); ?></li>
                    <?php endif; ?>
                    
                    <?php if (!empty($bottle_size)): ?>
                        <li><strong><?php _e('Размер на шишето:', 'parfume-reviews'); ?></strong> <?php echo esc_html($bottle_size); ?></li>
                    <?php endif; ?>
                </ul>
            <?php else: ?>
                <p><?php _e('Няма налични детайли.', 'parfume-reviews'); ?></p>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
    
    public function stores_shortcode($atts) {
        global $post;
        
        if (!$post || $post->post_type !== 'parfume') {
            return '';
        }
        
        $atts = shortcode_atts(array(
            'show_empty' => true,
        ), $atts);
        
        $show_empty = filter_var($atts['show_empty'], FILTER_VALIDATE_BOOLEAN);
        
        $stores = get_post_meta($post->ID, '_parfume_stores', true);
        $stores = !empty($stores) && is_array($stores) ? $stores : array();
        
        if (empty($stores) && !$show_empty) {
            return '';
        }
        
        ob_start();
        ?>
        <div class="parfume-stores-sidebar">
            <h3><?php _e('Къде да купите', 'parfume-reviews'); ?></h3>
            
            <?php if (!empty($stores)): ?>
                <div class="store-list">
                    <?php foreach ($stores as $store): ?>
                        <?php if (empty($store['name'])) continue; ?>
                        <div class="row py-0 py-md-3 px-0 px-md-2 program-parfium-bg">
                            <div class="col-7">
                                <?php if (!empty($store['logo'])): ?>
                                    <a class="brand-logo" href="<?php echo esc_url($store['affiliate_url'] ?: $store['url']); ?>" target="_blank" rel="<?php echo esc_attr($store['affiliate_rel'] ?: 'sponsored'); ?>">
                                        <img src="<?php echo esc_url($store['logo']); ?>" class="object-fit-contain" loading="lazy" width="140" height="40" alt="<?php echo esc_attr($store['name']); ?>">
                                    </a>
                                <?php else: ?>
                                    <div class="brand-logo">
                                        <strong><?php echo esc_html($store['name']); ?></strong>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="more_info mt-2 flex-wrap" style="width: max-content;">
                                    <?php if (!empty($store['availability'])): ?>
                                        <div class="availability">
                                            <img src="<?php echo PARFUME_REVIEWS_PLUGIN_URL; ?>assets/images/tick-icon.svg" width="16" height="16" alt="availability"> 
                                            <?php echo esc_html($store['availability']); ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($store['shipping_cost'])): ?>
                                        <div class="free_shipping">
                                            <img src="<?php echo PARFUME_REVIEWS_PLUGIN_URL; ?>assets/images/truck-icon.svg" width="16" height="16"> 
                                            <?php echo esc_html($store['shipping_cost']); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="col-5">
                                <?php if (!empty($store['price'])): ?>
                                    <a class="brand-price" href="<?php echo esc_url($store['affiliate_url'] ?: $store['url']); ?>" target="_blank" rel="<?php echo esc_attr($store['affiliate_rel'] ?: 'sponsored'); ?>">
                                        <div class="price">
                                            <?php echo esc_html($store['price']); ?>
                                            <div class="disclosure">
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64" height="0.9em" width="24px" color="#ababab">
                                                    <path fill="currentColor" d="M32,0A32,32,0,1,0,64,32,32,32,0,0,0,32,0Zm4,52H28V26h8ZM32,22.86a5,5,0,1,1,5-5A5,5,0,0,1,32,22.86Z"></path>
                                                </svg>
                                                <span class="tooltip-text"><?php _e('Цените ни се актуализират на всеки 12ч. Моля, извинете ни за евентуално несъответствие.', 'parfume-reviews'); ?></span>
                                            </div>
                                        </div>
                                    </a>
                                <?php endif; ?>
                            </div>
                            
                            <?php if (!empty($store['size'])): ?>
                                <div class="col-12 d-flex overflow-auto gap-2 mt-3">
                                    <?php 
                                    $sizes = explode(',', $store['size']);
                                    foreach ($sizes as $size): 
                                        $size = trim($size);
                                        if (empty($size)) continue;
                                    ?>
                                        <div class="variant-box position-relative p-1 rounded text-decoration-none d-flex flex-column align-items-start">
                                            <a href="<?php echo esc_url($store['affiliate_url'] ?: $store['url']); ?>" target="_blank" rel="<?php echo esc_attr($store['affiliate_rel'] ?: 'sponsored'); ?>" class="text-decoration-none d-flex flex-column w-100">
                                                <div class="fw-bold text-center fsize"><?php echo esc_html($size); ?></div>
                                            </a>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                            
                            <div class="col-12 d-flex gap-2">
                                <a class="cta-button-a w-100 mt-3 aff-button" href="<?php echo esc_url($store['affiliate_url'] ?: $store['url']); ?>" target="<?php echo esc_attr($store['affiliate_target'] ?: '_blank'); ?>" rel="<?php echo esc_attr($store['affiliate_rel'] ?: 'sponsored'); ?>">
                                    <span>
                                        <?php echo !empty($store['affiliate_anchor']) ? esc_html($store['affiliate_anchor']) : __('към магазина', 'parfume-reviews'); ?>
                                        <svg width="16px" height="16px" fill="#fff" focusable="false" aria-hidden="true" viewBox="0 0 24 24">
                                            <path d="M19 19H5V5h7V3H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2v-7h-2v7zM14 3v2h3.59l-9.83 9.83 1.41 1.41L19 6.41V10h2V3h-7z"></path>
                                        </svg>
                                    </span>
                                </a>
                                
                                <?php if (!empty($store['promo_code'])): ?>
                                    <a class="promocode-button-a w-100 mt-3 position-relative" href="<?php echo esc_url($store['affiliate_url'] ?: $store['url']); ?>" target="<?php echo esc_attr($store['affiliate_target'] ?: '_blank'); ?>" rel="<?php echo esc_attr($store['affiliate_rel'] ?: 'sponsored'); ?>">
                                        <?php if (!empty($store['promo_text'])): ?>
                                            <label><?php echo esc_html($store['promo_text']); ?></label>
                                        <?php endif; ?>
                                        <span>
                                            <?php echo esc_html($store['promo_code']); ?>
                                            <svg viewBox="0 0 24 24" width="16px" height="16px" fill="none" xmlns="http://www.w3.org/2000/svg" transform="matrix(-1, 0, 0, 1, 0, 0)">
                                                <g id="SVGRepo_bgCarrier" stroke-width="0"></g>
                                                <g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g>
                                                <g id="SVGRepo_iconCarrier">
                                                    <path fill-rule="evenodd" clip-rule="evenodd" d="M21 8C21 6.34315 19.6569 5 18 5H10C8.34315 5 7 6.34315 7 8V20C7 21.6569 8.34315 23 10 23H18C19.6569 23 21 21.6569 21 20V8ZM19 8C19 7.44772 18.5523 7 18 7H10C9.44772 7 9 7.44772 9 8V20C9 20.5523 9.44772 21 10 21H18C18.5523 21 19 20.5523 19 20V8Z" fill="#fd4f00"></path>
                                                    <path d="M6 3H16C16.5523 3 17 2.55228 17 2C17 1.44772 16.5523 1 16 1H6C4.34315 1 3 2.34315 3 4V18C3 18.5523 3.44772 19 4 19C4.55228 19 5 18.5523 5 18V4C5 3.44772 5.44772 3 6 3Z" fill="#fd4f00"></path>
                                                </g>
                                            </svg>
                                        </span>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p><?php _e('Няма намерени магазини за този парфюм.', 'parfume-reviews'); ?></p>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
    
    public function filters_shortcode($atts) {
        $atts = shortcode_atts(array(
            'show_gender' => true,
            'show_aroma_type' => true,
            'show_brand' => true,
            'show_season' => true,
            'show_intensity' => true,
            'show_notes' => true,
            'show_perfumer' => true,
        ), $atts);
        
        // Convert string values to boolean
        foreach ($atts as $key => $value) {
            $atts[$key] = filter_var($value, FILTER_VALIDATE_BOOLEAN);
        }
        
        ob_start();
        ?>
        <div class="parfume-filters">
            <form method="get" id="parfume-filters-form">
                
                <?php if ($atts['show_gender']): ?>
                    <div class="filter-group">
                        <h4 class="filter-title">
                            <span class="toggle-arrow">▼</span>
                            <?php _e('Категории', 'parfume-reviews'); ?>
                        </h4>
                        <div class="filter-options">
                            <label class="filter-option">
                                <input type="checkbox" name="gender[]" value="all" class="select-all"> 
                                <?php _e('Всички', 'parfume-reviews'); ?>
                            </label>
                            <?php
                            $genders = get_terms(array(
                                'taxonomy' => 'gender',
                                'hide_empty' => false,
                            ));
                            
                            $selected_genders = isset($_GET['gender']) ? (array) $_GET['gender'] : array();
                            
                            foreach ($genders as $gender): ?>
                                <label class="filter-option">
                                    <input type="checkbox" name="gender[]" value="<?php echo esc_attr($gender->slug); ?>" <?php echo in_array($gender->slug, $selected_genders) ? 'checked' : ''; ?>>
                                    <?php echo esc_html($gender->name); ?> (<?php echo $gender->count; ?>)
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if ($atts['show_aroma_type']): ?>
                    <div class="filter-group">
                        <h4 class="filter-title">
                            <span class="toggle-arrow">▼</span>
                            <?php _e('Вид аромат', 'parfume-reviews'); ?>
                        </h4>
                        <div class="filter-options">
                            <label class="filter-option">
                                <input type="checkbox" name="aroma_type[]" value="all" class="select-all"> 
                                <?php _e('Всички', 'parfume-reviews'); ?>
                            </label>
                            <?php
                            $aroma_types = get_terms(array(
                                'taxonomy' => 'aroma_type',
                                'hide_empty' => false,
                            ));
                            
                            $selected_aroma_types = isset($_GET['aroma_type']) ? (array) $_GET['aroma_type'] : array();
                            
                            foreach ($aroma_types as $aroma_type): ?>
                                <label class="filter-option">
                                    <input type="checkbox" name="aroma_type[]" value="<?php echo esc_attr($aroma_type->slug); ?>" <?php echo in_array($aroma_type->slug, $selected_aroma_types) ? 'checked' : ''; ?>>
                                    <?php echo esc_html($aroma_type->name); ?> (<?php echo $aroma_type->count; ?>)
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if ($atts['show_brand']): ?>
                    <div class="filter-group">
                        <h4 class="filter-title">
                            <span class="toggle-arrow">▼</span>
                            <?php _e('Марка', 'parfume-reviews'); ?>
                        </h4>
                        <div class="filter-options">
                            <input type="text" class="filter-search" placeholder="<?php _e('Търсене в марките...', 'parfume-reviews'); ?>">
                            <label class="filter-option">
                                <input type="checkbox" name="marki[]" value="all" class="select-all"> 
                                <?php _e('Всички', 'parfume-reviews'); ?>
                            </label>
                            <div class="scrollable-options" style="max-height: 200px; overflow-y: auto;">
                                <?php
                                $brands = get_terms(array(
                                    'taxonomy' => 'marki',
                                    'hide_empty' => false,
                                    'number' => 50,
                                ));
                                
                                $selected_brands = isset($_GET['marki']) ? (array) $_GET['marki'] : array();
                                
                                foreach ($brands as $brand): ?>
                                    <label class="filter-option">
                                        <input type="checkbox" name="marki[]" value="<?php echo esc_attr($brand->slug); ?>" <?php echo in_array($brand->slug, $selected_brands) ? 'checked' : ''; ?>>
                                        <?php echo esc_html($brand->name); ?> (<?php echo $brand->count; ?>)
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if ($atts['show_season']): ?>
                    <div class="filter-group">
                        <h4 class="filter-title">
                            <span class="toggle-arrow">▼</span>
                            <?php _e('Сезон', 'parfume-reviews'); ?>
                        </h4>
                        <div class="filter-options">
                            <label class="filter-option">
                                <input type="checkbox" name="season[]" value="all" class="select-all"> 
                                <?php _e('Всички', 'parfume-reviews'); ?>
                            </label>
                            <?php
                            $seasons = get_terms(array(
                                'taxonomy' => 'season',
                                'hide_empty' => false,
                            ));
                            
                            $selected_seasons = isset($_GET['season']) ? (array) $_GET['season'] : array();
                            
                            foreach ($seasons as $season): ?>
                                <label class="filter-option">
                                    <input type="checkbox" name="season[]" value="<?php echo esc_attr($season->slug); ?>" <?php echo in_array($season->slug, $selected_seasons) ? 'checked' : ''; ?>>
                                    <?php echo esc_html($season->name); ?> (<?php echo $season->count; ?>)
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if ($atts['show_notes']): ?>
                    <div class="filter-group">
                        <h4 class="filter-title collapsed">
                            <span class="toggle-arrow">▶</span>
                            <?php _e('Ароматни нотки', 'parfume-reviews'); ?>
                        </h4>
                        <div class="filter-options" style="display: none;">
                            <input type="text" class="filter-search" placeholder="<?php _e('Търсене в нотките...', 'parfume-reviews'); ?>">
                            <label class="filter-option">
                                <input type="checkbox" name="notes[]" value="all" class="select-all"> 
                                <?php _e('Всички', 'parfume-reviews'); ?>
                            </label>
                            <div class="scrollable-options" style="max-height: 200px; overflow-y: auto;">
                                <?php
                                $notes = get_terms(array(
                                    'taxonomy' => 'notes',
                                    'hide_empty' => false,
                                    'number' => 50,
                                ));
                                
                                $selected_notes = isset($_GET['notes']) ? (array) $_GET['notes'] : array();
                                
                                foreach ($notes as $note): ?>
                                    <label class="filter-option">
                                        <input type="checkbox" name="notes[]" value="<?php echo esc_attr($note->slug); ?>" <?php echo in_array($note->slug, $selected_notes) ? 'checked' : ''; ?>>
                                        <?php echo esc_html($note->name); ?> (<?php echo $note->count; ?>)
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if ($atts['show_perfumer']): ?>
                    <div class="filter-group">
                        <h4 class="filter-title collapsed">
                            <span class="toggle-arrow">▶</span>
                            <?php _e('Парфюмеристи', 'parfume-reviews'); ?>
                        </h4>
                        <div class="filter-options" style="display: none;">
                            <input type="text" class="filter-search" placeholder="<?php _e('Търсене в парфюмеристите...', 'parfume-reviews'); ?>">
                            <label class="filter-option">
                                <input type="checkbox" name="perfumer[]" value="all" class="select-all"> 
                                <?php _e('Всички', 'parfume-reviews'); ?>
                            </label>
                            <div class="scrollable-options" style="max-height: 200px; overflow-y: auto;">
                                <?php
                                $perfumers = get_terms(array(
                                    'taxonomy' => 'perfumer',
                                    'hide_empty' => false,
                                    'number' => 50,
                                ));
                                
                                $selected_perfumers = isset($_GET['perfumer']) ? (array) $_GET['perfumer'] : array();
                                
                                foreach ($perfumers as $perfumer): ?>
                                    <label class="filter-option">
                                        <input type="checkbox" name="perfumer[]" value="<?php echo esc_attr($perfumer->slug); ?>" <?php echo in_array($perfumer->slug, $selected_perfumers) ? 'checked' : ''; ?>>
                                        <?php echo esc_html($perfumer->name); ?> (<?php echo $perfumer->count; ?>)
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                
                <div class="filter-submit">
                    <button type="submit" class="filter-button"><?php _e('Филтрирай', 'parfume-reviews'); ?></button>
                    <a href="<?php echo esc_url(remove_query_arg(array('gender', 'aroma_type', 'marki', 'season', 'notes', 'perfumer'))); ?>" class="reset-button"><?php _e('Изчисти', 'parfume-reviews'); ?></a>
                </div>
            </form>
        </div>
        
        <style>
        .parfume-filters {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
        }
        
        .filter-group {
            margin-bottom: 20px;
            border-bottom: 1px solid #e9ecef;
            padding-bottom: 15px;
        }
        
        .filter-title {
            cursor: pointer;
            margin: 0 0 10px;
            font-size: 16px;
            font-weight: bold;
            color: #333;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .toggle-arrow {
            transition: transform 0.3s ease;
            font-size: 12px;
        }
        
        .filter-title.collapsed .toggle-arrow {
            transform: rotate(0deg);
        }
        
        .filter-options {
            padding-left: 20px;
        }
        
        .filter-search {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin-bottom: 10px;
            font-size: 14px;
        }
        
        .filter-option {
            display: block;
            margin-bottom: 8px;
            cursor: pointer;
            font-size: 14px;
        }
        
        .filter-option input[type="checkbox"] {
            margin-right: 8px;
        }
        
        .scrollable-options {
            max-height: 200px;
            overflow-y: auto;
            border: 1px solid #e9ecef;
            border-radius: 4px;
            padding: 10px;
            background: white;
        }
        
        .filter-submit {
            margin-top: 20px;
            display: flex;
            gap: 10px;
        }
        
        .filter-button {
            background: linear-gradient(135deg, #0073aa, #005a87);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(0,115,170,0.3);
        }
        
        .filter-button:hover {
            background: linear-gradient(135deg, #005a87, #004466);
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0,115,170,0.4);
        }
        
        .reset-button {
            background: linear-gradient(135deg, #6c757d, #545b62);
            color: white;
            padding: 12px 24px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: bold;
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(108,117,125,0.3);
        }
        
        .reset-button:hover {
            background: linear-gradient(135deg, #545b62, #3d4449);
            color: white;
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(108,117,125,0.4);
        }
        </style>
        
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Toggle filter sections
            document.querySelectorAll('.filter-title').forEach(title => {
                title.addEventListener('click', function() {
                    const options = this.nextElementSibling;
                    const arrow = this.querySelector('.toggle-arrow');
                    
                    if (options.style.display === 'none') {
                        options.style.display = 'block';
                        arrow.textContent = '▼';
                        this.classList.remove('collapsed');
                    } else {
                        options.style.display = 'none';
                        arrow.textContent = '▶';
                        this.classList.add('collapsed');
                    }
                });
            });
            
            // Handle "Select All" checkboxes
            document.querySelectorAll('.select-all').forEach(selectAll => {
                selectAll.addEventListener('change', function() {
                    const group = this.closest('.filter-group');
                    const checkboxes = group.querySelectorAll('input[type="checkbox"]:not(.select-all)');
                    
                    if (this.checked) {
                        checkboxes.forEach(cb => cb.checked = false);
                    }
                });
            });
            
            // Uncheck "Select All" when other options are selected
            document.querySelectorAll('.filter-option input[type="checkbox"]:not(.select-all)').forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    if (this.checked) {
                        const selectAll = this.closest('.filter-group').querySelector('.select-all');
                        if (selectAll) selectAll.checked = false;
                    }
                });
            });
            
            // Search functionality
            document.querySelectorAll('.filter-search').forEach(search => {
                search.addEventListener('input', function() {
                    const query = this.value.toLowerCase();
                    const options = this.parentNode.querySelectorAll('.filter-option:not(:first-child)');
                    
                    options.forEach(option => {
                        const text = option.textContent.toLowerCase();
                        option.style.display = text.includes(query) ? 'block' : 'none';
                    });
                });
            });
            
            // Fix form submission to use current page URL
            document.getElementById('parfume-filters-form').addEventListener('submit', function(e) {
                // Ensure we stay on the current archive page
                const currentUrl = window.location.pathname;
                this.action = currentUrl;
            });
        });
        </script>
        <?php
        return ob_get_clean();
    }
    
    public function similar_shortcode($atts) {
        global $post;
        
        if (!$post || $post->post_type !== 'parfume') {
            return '';
        }
        
        $atts = shortcode_atts(array(
            'limit' => 4,
            'title' => __('Подобни парфюми', 'parfume-reviews'),
        ), $atts);
        
        $limit = intval($atts['limit']);
        if ($limit <= 0) $limit = 4;
        
        // Get current perfume taxonomies
        $brands = wp_get_post_terms($post->ID, 'marki', array('fields' => 'ids'));
        $notes = wp_get_post_terms($post->ID, 'notes', array('fields' => 'ids'));
        $genders = wp_get_post_terms($post->ID, 'gender', array('fields' => 'ids'));
        
        if (is_wp_error($brands)) $brands = array();
        if (is_wp_error($notes)) $notes = array();
        if (is_wp_error($genders)) $genders = array();
        
        $tax_query = array('relation' => 'OR');
        
        if (!empty($brands)) {
            $tax_query[] = array(
                'taxonomy' => 'marki',
                'field' => 'term_id',
                'terms' => $brands,
            );
        }
        
        if (!empty($notes)) {
            $tax_query[] = array(
                'taxonomy' => 'notes',
                'field' => 'term_id',
                'terms' => $notes,
            );
        }
        
        if (!empty($genders)) {
            $tax_query[] = array(
                'taxonomy' => 'gender',
                'field' => 'term_id',
                'terms' => $genders,
            );
        }
        
        if (count($tax_query) === 1) {
            return ''; // No taxonomies found
        }
        
        $args = array(
            'post_type' => 'parfume',
            'posts_per_page' => $limit,
            'post__not_in' => array($post->ID),
            'tax_query' => $tax_query,
        );
        
        $similar = new \WP_Query($args);
        
        ob_start();
        
        if ($similar->have_posts()):
            ?>
            <div class="similar-parfumes">
                <h3><?php echo esc_html($atts['title']); ?></h3>
                
                <div class="parfume-grid">
                    <?php while ($similar->have_posts()): $similar->the_post(); ?>
                        <div class="parfume-item">
                            <a href="<?php the_permalink(); ?>">
                                <?php if (has_post_thumbnail()): ?>
                                    <div class="parfume-thumbnail">
                                        <?php the_post_thumbnail('thumbnail'); ?>
                                    </div>
                                <?php endif; ?>
                                <h4><?php the_title(); ?></h4>
                                <?php 
                                $rating = get_post_meta(get_the_ID(), '_parfume_rating', true);
                                if (!empty($rating) && is_numeric($rating)): 
                                ?>
                                    <div class="parfume-rating">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <span class="star <?php echo $i <= round(floatval($rating)) ? 'filled' : ''; ?>">★</span>
                                        <?php endfor; ?>
                                    </div>
                                <?php endif; ?>
                            </a>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
            <?php
        endif;
        
        wp_reset_postdata();
        
        return ob_get_clean();
    }
    
    public function brand_products_shortcode($atts) {
        global $post;
        
        if (!$post || $post->post_type !== 'parfume') {
            return '';
        }
        
        $atts = shortcode_atts(array(
            'limit' => 4,
            'title' => __('Други продукти от тази марка', 'parfume-reviews'),
        ), $atts);
        
        $limit = intval($atts['limit']);
        if ($limit <= 0) $limit = 4;
        
        // Get current perfume brand
        $brands = wp_get_post_terms($post->ID, 'marki', array('fields' => 'ids'));
        
        if (is_wp_error($brands) || empty($brands)) {
            return '';
        }
        
        $args = array(
            'post_type' => 'parfume',
            'posts_per_page' => $limit,
            'post__not_in' => array($post->ID),
            'tax_query' => array(
                array(
                    'taxonomy' => 'marki',
                    'field' => 'term_id',
                    'terms' => $brands,
                ),
            ),
        );
        
        $brand_products = new \WP_Query($args);
        
        ob_start();
        
        if ($brand_products->have_posts()):
            ?>
            <div class="brand-products">
                <h3><?php echo esc_html($atts['title']); ?></h3>
                
                <div class="parfume-grid">
                    <?php while ($brand_products->have_posts()): $brand_products->the_post(); ?>
                        <div class="parfume-item">
                            <a href="<?php the_permalink(); ?>">
                                <?php if (has_post_thumbnail()): ?>
                                    <div class="parfume-thumbnail">
                                        <?php the_post_thumbnail('thumbnail'); ?>
                                    </div>
                                <?php endif; ?>
                                <h4><?php the_title(); ?></h4>
                                <?php 
                                $rating = get_post_meta(get_the_ID(), '_parfume_rating', true);
                                if (!empty($rating) && is_numeric($rating)): 
                                ?>
                                    <div class="parfume-rating">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <span class="star <?php echo $i <= round(floatval($rating)) ? 'filled' : ''; ?>">★</span>
                                        <?php endfor; ?>
                                    </div>
                                <?php endif; ?>
                            </a>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
            <?php
        endif;
        
        wp_reset_postdata();
        
        return ob_get_clean();
    }
    
    public function recently_viewed_shortcode($atts) {
        $atts = shortcode_atts(array(
            'limit' => 4,
            'title' => __('Наскоро разгледани', 'parfume-reviews'),
        ), $atts);
        
        $limit = intval($atts['limit']);
        if ($limit <= 0) $limit = 4;
        
        // Get recently viewed from localStorage via JavaScript
        // This will be populated by frontend.js
        
        ob_start();
        ?>
        <div class="recently-viewed" id="recently-viewed-container" style="display: none;">
            <h3><?php echo esc_html($atts['title']); ?></h3>
            <div class="parfume-grid" id="recently-viewed-grid">
                <!-- Content will be populated by JavaScript -->
            </div>
        </div>
        
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Get recently viewed from localStorage
            var viewed = JSON.parse(localStorage.getItem('parfume_recently_viewed') || '[]');
            
            if (viewed.length > 0) {
                var container = document.getElementById('recently-viewed-container');
                var grid = document.getElementById('recently-viewed-grid');
                
                // Show container
                container.style.display = 'block';
                
                // Take only the specified limit
                var limitedViewed = viewed.slice(0, <?php echo $limit; ?>);
                
                // For each viewed perfume, create a card (simplified version)
                limitedViewed.forEach(function(postId) {
                    // Create a simple placeholder - in real implementation, 
                    // you'd make AJAX calls to get post data
                    var item = document.createElement('div');
                    item.className = 'parfume-item';
                    item.innerHTML = '<a href="/parfiumi/' + postId + '/">Парфюм #' + postId + '</a>';
                    grid.appendChild(item);
                });
            }
        });
        </script>
        <?php
        
        return ob_get_clean();
    }
}