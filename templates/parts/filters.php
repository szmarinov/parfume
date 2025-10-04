<?php
/**
 * Filters Component
 * 
 * Filter form for parfume archives
 * 
 * @package Parfume_Reviews
 * @since 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get current filters from URL
$current_filters = [];
$current_filters['brand'] = isset($_GET['brand']) ? sanitize_text_field($_GET['brand']) : '';
$current_filters['gender'] = isset($_GET['gender']) ? sanitize_text_field($_GET['gender']) : '';
$current_filters['aroma_type'] = isset($_GET['aroma_type']) ? sanitize_text_field($_GET['aroma_type']) : '';
$current_filters['season'] = isset($_GET['season']) ? sanitize_text_field($_GET['season']) : '';
$current_filters['intensity'] = isset($_GET['intensity']) ? sanitize_text_field($_GET['intensity']) : '';
$current_filters['min_price'] = isset($_GET['min_price']) ? absint($_GET['min_price']) : '';
$current_filters['max_price'] = isset($_GET['max_price']) ? absint($_GET['max_price']) : '';
$current_filters['min_rating'] = isset($_GET['min_rating']) ? floatval($_GET['min_rating']) : '';

// Get base URL
$base_url = get_post_type_archive_link('parfume');
if (is_tax()) {
    $queried_object = get_queried_object();
    $base_url = get_term_link($queried_object);
}

?>

<form class="parfume-filter-form" method="get" action="<?php echo esc_url($base_url); ?>">
    
    <!-- Brand Filter -->
    <div class="filter-group">
        <label for="filter-brand"><?php _e('Марка', 'parfume-reviews'); ?></label>
        <select name="brand" id="filter-brand" class="filter-select">
            <option value=""><?php _e('Всички марки', 'parfume-reviews'); ?></option>
            <?php
            $brands = get_terms([
                'taxonomy' => 'marki',
                'hide_empty' => true,
                'orderby' => 'name'
            ]);
            
            if (!empty($brands) && !is_wp_error($brands)) :
                foreach ($brands as $brand) :
                    ?>
                    <option value="<?php echo esc_attr($brand->slug); ?>" <?php selected($current_filters['brand'], $brand->slug); ?>>
                        <?php echo esc_html($brand->name); ?> (<?php echo $brand->count; ?>)
                    </option>
                <?php endforeach;
            endif;
            ?>
        </select>
    </div>
    
    <!-- Gender Filter -->
    <div class="filter-group">
        <label for="filter-gender"><?php _e('Пол', 'parfume-reviews'); ?></label>
        <select name="gender" id="filter-gender" class="filter-select">
            <option value=""><?php _e('Всички', 'parfume-reviews'); ?></option>
            <?php
            $genders = get_terms([
                'taxonomy' => 'gender',
                'hide_empty' => true,
                'orderby' => 'name'
            ]);
            
            if (!empty($genders) && !is_wp_error($genders)) :
                foreach ($genders as $gender) :
                    ?>
                    <option value="<?php echo esc_attr($gender->slug); ?>" <?php selected($current_filters['gender'], $gender->slug); ?>>
                        <?php echo esc_html($gender->name); ?> (<?php echo $gender->count; ?>)
                    </option>
                <?php endforeach;
            endif;
            ?>
        </select>
    </div>
    
    <!-- Aroma Type Filter -->
    <div class="filter-group">
        <label for="filter-aroma-type"><?php _e('Тип аромат', 'parfume-reviews'); ?></label>
        <select name="aroma_type" id="filter-aroma-type" class="filter-select">
            <option value=""><?php _e('Всички типове', 'parfume-reviews'); ?></option>
            <?php
            $aroma_types = get_terms([
                'taxonomy' => 'aroma_type',
                'hide_empty' => true,
                'orderby' => 'name'
            ]);
            
            if (!empty($aroma_types) && !is_wp_error($aroma_types)) :
                foreach ($aroma_types as $type) :
                    ?>
                    <option value="<?php echo esc_attr($type->slug); ?>" <?php selected($current_filters['aroma_type'], $type->slug); ?>>
                        <?php echo esc_html($type->name); ?> (<?php echo $type->count; ?>)
                    </option>
                <?php endforeach;
            endif;
            ?>
        </select>
    </div>
    
    <!-- Season Filter -->
    <div class="filter-group">
        <label for="filter-season"><?php _e('Сезон', 'parfume-reviews'); ?></label>
        <select name="season" id="filter-season" class="filter-select">
            <option value=""><?php _e('Всички сезони', 'parfume-reviews'); ?></option>
            <?php
            $seasons = get_terms([
                'taxonomy' => 'season',
                'hide_empty' => true,
                'orderby' => 'name'
            ]);
            
            if (!empty($seasons) && !is_wp_error($seasons)) :
                foreach ($seasons as $season) :
                    ?>
                    <option value="<?php echo esc_attr($season->slug); ?>" <?php selected($current_filters['season'], $season->slug); ?>>
                        <?php echo esc_html($season->name); ?> (<?php echo $season->count; ?>)
                    </option>
                <?php endforeach;
            endif;
            ?>
        </select>
    </div>
    
    <!-- Intensity Filter -->
    <div class="filter-group">
        <label for="filter-intensity"><?php _e('Интензивност', 'parfume-reviews'); ?></label>
        <select name="intensity" id="filter-intensity" class="filter-select">
            <option value=""><?php _e('Всички', 'parfume-reviews'); ?></option>
            <?php
            $intensities = get_terms([
                'taxonomy' => 'intensity',
                'hide_empty' => true,
                'orderby' => 'name'
            ]);
            
            if (!empty($intensities) && !is_wp_error($intensities)) :
                foreach ($intensities as $intensity) :
                    ?>
                    <option value="<?php echo esc_attr($intensity->slug); ?>" <?php selected($current_filters['intensity'], $intensity->slug); ?>>
                        <?php echo esc_html($intensity->name); ?> (<?php echo $intensity->count; ?>)
                    </option>
                <?php endforeach;
            endif;
            ?>
        </select>
    </div>
    
    <!-- Price Range Filter -->
    <div class="filter-group">
        <label><?php _e('Ценови диапазон', 'parfume-reviews'); ?></label>
        <div class="price-range-inputs">
            <input type="number" 
                   name="min_price" 
                   id="filter-min-price" 
                   placeholder="<?php _e('От', 'parfume-reviews'); ?>"
                   value="<?php echo esc_attr($current_filters['min_price']); ?>"
                   min="0"
                   step="1">
            <span class="separator">-</span>
            <input type="number" 
                   name="max_price" 
                   id="filter-max-price" 
                   placeholder="<?php _e('До', 'parfume-reviews'); ?>"
                   value="<?php echo esc_attr($current_filters['max_price']); ?>"
                   min="0"
                   step="1">
        </div>
    </div>
    
    <!-- Rating Filter -->
    <div class="filter-group">
        <label for="filter-min-rating"><?php _e('Минимална оценка', 'parfume-reviews'); ?></label>
        <select name="min_rating" id="filter-min-rating" class="filter-select">
            <option value=""><?php _e('Всички', 'parfume-reviews'); ?></option>
            <?php for ($i = 9; $i >= 5; $i--) : ?>
                <option value="<?php echo $i; ?>" <?php selected($current_filters['min_rating'], $i); ?>>
                    <?php echo $i; ?>+ ⭐
                </option>
            <?php endfor; ?>
        </select>
    </div>
    
    <!-- Filter Actions -->
    <div class="filter-actions">
        <button type="submit" class="button button-primary filter-submit">
            <?php _e('Приложи филтри', 'parfume-reviews'); ?>
        </button>
        
        <?php if (array_filter($current_filters)) : ?>
            <a href="<?php echo esc_url($base_url); ?>" class="button button-secondary clear-filters">
                <?php _e('Изчисти', 'parfume-reviews'); ?>
            </a>
        <?php endif; ?>
    </div>
    
</form>

<!-- Active Filters Display -->
<?php if (array_filter($current_filters)) : ?>
    <div class="active-filters">
        <div class="active-filters-header">
            <strong><?php _e('Активни филтри:', 'parfume-reviews'); ?></strong>
        </div>
        
        <div class="active-filters-list">
            <?php foreach ($current_filters as $key => $value) : ?>
                <?php if (empty($value)) continue; ?>
                
                <?php
                // Get filter label
                $label = '';
                switch ($key) {
                    case 'brand':
                        $term = get_term_by('slug', $value, 'marki');
                        $label = $term ? $term->name : $value;
                        break;
                    case 'gender':
                        $term = get_term_by('slug', $value, 'gender');
                        $label = $term ? $term->name : $value;
                        break;
                    case 'aroma_type':
                        $term = get_term_by('slug', $value, 'aroma_type');
                        $label = $term ? $term->name : $value;
                        break;
                    case 'season':
                        $term = get_term_by('slug', $value, 'season');
                        $label = $term ? $term->name : $value;
                        break;
                    case 'intensity':
                        $term = get_term_by('slug', $value, 'intensity');
                        $label = $term ? $term->name : $value;
                        break;
                    case 'min_price':
                        $label = sprintf(__('От %s лв', 'parfume-reviews'), $value);
                        break;
                    case 'max_price':
                        $label = sprintf(__('До %s лв', 'parfume-reviews'), $value);
                        break;
                    case 'min_rating':
                        $label = sprintf(__('Оценка %s+', 'parfume-reviews'), $value);
                        break;
                }
                
                if (empty($label)) continue;
                
                // Build remove URL
                $remove_url = remove_query_arg($key, $_SERVER['REQUEST_URI']);
                ?>
                
                <span class="active-filter-tag">
                    <?php echo esc_html($label); ?>
                    <a href="<?php echo esc_url($remove_url); ?>" class="remove-filter" aria-label="<?php _e('Премахни', 'parfume-reviews'); ?>">
                        ×
                    </a>
                </span>
                
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>