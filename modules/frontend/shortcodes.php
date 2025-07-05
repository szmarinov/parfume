<?php
namespace ParfumeReviews\Frontend;

if (!defined('ABSPATH')) {
    exit;
}

class Shortcodes {
    
    public function __construct() {
        add_shortcode('parfume_rating', [$this, 'rating_shortcode']);
        add_shortcode('parfume_filters', [$this, 'filters_shortcode']);
        add_shortcode('all_brands_archive', [$this, 'all_brands_archive_shortcode']);
        add_shortcode('all_notes_archive', [$this, 'all_notes_archive_shortcode']);
    }
    
    public function rating_shortcode($atts) {
        global $post;
        
        if (!$post || $post->post_type !== 'parfume') {
            return '';
        }
        
        $atts = shortcode_atts([
            'show_empty' => true,
            'show_average' => true,
        ], $atts);
        
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
    
    public function filters_shortcode($atts) {
        $atts = shortcode_atts([
            'show_gender' => true,
            'show_aroma_type' => true,
            'show_brand' => true,
            'show_season' => true,
            'show_notes' => false,
        ], $atts);
        
        // Convert string values to boolean
        foreach ($atts as $key => $value) {
            $atts[$key] = filter_var($value, FILTER_VALIDATE_BOOLEAN);
        }
        
        ob_start();
        ?>
        <div class="parfume-filters">
            <form method="get" class="filters-form">
                
                <?php if ($atts['show_brand']): ?>
                    <div class="filter-group">
                        <label for="brand-filter">Марка:</label>
                        <select name="marki" id="brand-filter">
                            <option value="">Всички марки</option>
                            <?php
                            $brands = get_terms(['taxonomy' => 'marki', 'hide_empty' => true]);
                            $selected_brand = isset($_GET['marki']) ? $_GET['marki'] : '';
                            
                            foreach ($brands as $brand):
                            ?>
                                <option value="<?php echo esc_attr($brand->slug); ?>" 
                                        <?php selected($selected_brand, $brand->slug); ?>>
                                    <?php echo esc_html($brand->name); ?> (<?php echo $brand->count; ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>
                
                <?php if ($atts['show_gender']): ?>
                    <div class="filter-group">
                        <label for="gender-filter">Категория:</label>
                        <select name="gender" id="gender-filter">
                            <option value="">Всички категории</option>
                            <?php
                            $genders = get_terms(['taxonomy' => 'gender', 'hide_empty' => true]);
                            $selected_gender = isset($_GET['gender']) ? $_GET['gender'] : '';
                            
                            foreach ($genders as $gender):
                            ?>
                                <option value="<?php echo esc_attr($gender->slug); ?>" 
                                        <?php selected($selected_gender, $gender->slug); ?>>
                                    <?php echo esc_html($gender->name); ?> (<?php echo $gender->count; ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>
                
                <?php if ($atts['show_aroma_type']): ?>
                    <div class="filter-group">
                        <label for="aroma-type-filter">Тип арома:</label>
                        <select name="aroma_type" id="aroma-type-filter">
                            <option value="">Всички типове</option>
                            <?php
                            $aroma_types = get_terms(['taxonomy' => 'aroma_type', 'hide_empty' => true]);
                            $selected_aroma_type = isset($_GET['aroma_type']) ? $_GET['aroma_type'] : '';
                            
                            foreach ($aroma_types as $aroma_type):
                            ?>
                                <option value="<?php echo esc_attr($aroma_type->slug); ?>" 
                                        <?php selected($selected_aroma_type, $aroma_type->slug); ?>>
                                    <?php echo esc_html($aroma_type->name); ?> (<?php echo $aroma_type->count; ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>
                
                <?php if ($atts['show_season']): ?>
                    <div class="filter-group">
                        <label for="season-filter">Сезон:</label>
                        <select name="season" id="season-filter">
                            <option value="">Всички сезони</option>
                            <?php
                            $seasons = get_terms(['taxonomy' => 'season', 'hide_empty' => true]);
                            $selected_season = isset($_GET['season']) ? $_GET['season'] : '';
                            
                            foreach ($seasons as $season):
                            ?>
                                <option value="<?php echo esc_attr($season->slug); ?>" 
                                        <?php selected($selected_season, $season->slug); ?>>
                                    <?php echo esc_html($season->name); ?> (<?php echo $season->count; ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>
                
                <div class="filter-submit">
                    <button type="submit" class="filter-button">Филтрирай</button>
                    <a href="<?php echo get_post_type_archive_link('parfume'); ?>" class="reset-button">Изчисти</a>
                </div>
            </form>
        </div>
        
        <style>
        .parfume-filters { background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 20px; }
        .filter-group { margin-bottom: 15px; }
        .filter-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .filter-group select { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
        .filter-submit { margin-top: 15px; }
        .filter-button { background: #0073aa; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; margin-right: 10px; }
        .filter-button:hover { background: #005a87; }
        .reset-button { background: #6c757d; color: white; padding: 10px 20px; border-radius: 4px; text-decoration: none; }
        .reset-button:hover { background: #545b62; color: white; }
        </style>
        <?php
        
        return ob_get_clean();
    }
    
    public function all_brands_archive_shortcode($atts) {
        ob_start();
        
        $all_brands = get_terms([
            'taxonomy' => 'marki',
            'hide_empty' => false,
            'orderby' => 'name',
            'order' => 'ASC',
        ]);

        if (!empty($all_brands) && !is_wp_error($all_brands)):
            ?>
            <div class="brands-archive">
                <h2>Всички марки парфюми</h2>
                <div class="brands-grid">
                    <?php foreach ($all_brands as $brand): ?>
                        <div class="brand-item">
                            <a href="<?php echo get_term_link($brand); ?>" class="brand-link">
                                <?php 
                                $brand_image_id = get_term_meta($brand->term_id, 'marki-image-id', true);
                                if ($brand_image_id): 
                                ?>
                                    <div class="brand-logo">
                                        <?php echo wp_get_attachment_image($brand_image_id, 'thumbnail'); ?>
                                    </div>
                                <?php else: ?>
                                    <div class="brand-logo brand-placeholder">
                                        <span class="brand-icon">🏷️</span>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="brand-info">
                                    <h3 class="brand-name"><?php echo esc_html($brand->name); ?></h3>
                                    <span class="brand-count">
                                        <?php printf(_n('%d парфюм', '%d парфюма', $brand->count, 'parfume-reviews'), $brand->count); ?>
                                    </span>
                                </div>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <style>
            .brands-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 20px; margin-top: 20px; }
            .brand-item { background: white; border: 1px solid #ddd; border-radius: 8px; overflow: hidden; transition: all 0.3s ease; }
            .brand-item:hover { transform: translateY(-5px); box-shadow: 0 8px 25px rgba(0,0,0,0.15); border-color: #0073aa; }
            .brand-link { display: block; padding: 20px; text-decoration: none; color: inherit; text-align: center; }
            .brand-logo { margin-bottom: 15px; }
            .brand-logo img { max-width: 60px; max-height: 60px; object-fit: contain; }
            .brand-placeholder { background: #f8f9fa; border: 1px solid #ddd; width: 60px; height: 60px; display: flex; align-items: center; justify-content: center; margin: 0 auto; border-radius: 4px; }
            .brand-icon { font-size: 24px; }
            .brand-name { font-size: 1.1em; font-weight: bold; margin: 0 0 8px; color: #333; }
            .brand-count { display: block; color: #0073aa; font-weight: 500; }
            </style>
            <?php
        else:
            ?>
            <p>Няма намерени марки.</p>
            <?php
        endif;
        
        return ob_get_clean();
    }
    
    public function all_notes_archive_shortcode($atts) {
        ob_start();
        
        $all_notes = get_terms([
            'taxonomy' => 'notes',
            'hide_empty' => false,
            'orderby' => 'name',
            'order' => 'ASC',
        ]);

        if (!empty($all_notes) && !is_wp_error($all_notes)):
            ?>
            <div class="notes-archive">
                <h2>Всички ароматни нотки</h2>
                <div class="notes-grid">
                    <?php foreach ($all_notes as $note): ?>
                        <div class="note-item">
                            <a href="<?php echo get_term_link($note); ?>" class="note-link">
                                <h3 class="note-name"><?php echo esc_html($note->name); ?></h3>
                                <span class="note-count">
                                    <?php printf(_n('%d парфюм', '%d парфюма', $note->count, 'parfume-reviews'), $note->count); ?>
                                </span>
                                <?php 
                                $group = get_term_meta($note->term_id, 'note_group', true);
                                if ($group): 
                                ?>
                                    <span class="note-group"><?php echo esc_html($group); ?></span>
                                <?php endif; ?>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <style>
            .notes-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 15px; margin-top: 20px; }
            .note-item { background: white; border: 1px solid #ddd; border-radius: 8px; overflow: hidden; transition: all 0.3s ease; }
            .note-item:hover { transform: translateY(-3px); box-shadow: 0 6px 20px rgba(0,0,0,0.15); border-color: #4CAF50; }
            .note-link { display: block; padding: 15px; text-decoration: none; color: inherit; text-align: center; }
            .note-name { font-size: 1em; font-weight: bold; margin: 0 0 8px; color: #333; }
            .note-count { display: block; color: #4CAF50; font-weight: 500; margin-bottom: 5px; }
            .note-group { display: block; background: #e8f5e8; color: #2e7d32; padding: 4px 8px; border-radius: 10px; font-size: 0.8em; }
            </style>
            <?php
        else:
            ?>
            <p>Няма намерени нотки.</p>
            <?php
        endif;
        
        return ob_get_clean();
    }
}