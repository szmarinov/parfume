<?php
namespace Parfume_Reviews;

/**
 * Shortcodes Handler - управлява всички shortcodes
 * 📁 Файл: includes/class-shortcodes.php
 * ПОПРАВЕНО: Добавени проверки за валидни обекти
 */
class Shortcodes {
    
    public function __construct() {
        // Регистрираме shortcodes
        add_shortcode('parfume_filters', array($this, 'parfume_filters_shortcode'));
        add_shortcode('parfume_grid', array($this, 'parfume_grid_shortcode'));
        add_shortcode('latest_parfumes', array($this, 'latest_parfumes_shortcode'));
        add_shortcode('featured_parfumes', array($this, 'featured_parfumes_shortcode'));
        add_shortcode('top_rated_parfumes', array($this, 'top_rated_parfumes_shortcode'));
        add_shortcode('all_brands_archive', array($this, 'all_brands_archive_shortcode'));
        add_shortcode('all_notes_archive', array($this, 'all_notes_archive_shortcode'));
        add_shortcode('all_perfumers_archive', array($this, 'all_perfumers_archive_shortcode'));
    }
    
    /**
     * Shortcode за филтри
     */
    public function parfume_filters_shortcode($atts) {
        $atts = shortcode_atts(array(
            'show_brand' => 'true',
            'show_gender' => 'true',
            'show_aroma_type' => 'true',
            'show_season' => 'true',
            'show_intensity' => 'true',
            'show_notes' => 'true',
            'show_perfumer' => 'true',
            'hide_empty' => 'true',
            'ajax' => 'true',
        ), $atts);
        
        ob_start();
        ?>
        <div class="parfume-filters-widget" data-ajax="<?php echo esc_attr($atts['ajax']); ?>">
            <form method="get" action="" class="parfume-filters-form">
                <?php
                // Preserve existing query vars
                foreach ($_GET as $key => $value) {
                    if (!in_array($key, array('brand', 'gender', 'aroma_type', 'season', 'intensity', 'notes', 'perfumer'))) {
                        if (is_array($value)) {
                            foreach ($value as $val) {
                                echo '<input type="hidden" name="' . esc_attr($key) . '[]" value="' . esc_attr($val) . '">';
                            }
                        } else {
                            echo '<input type="hidden" name="' . esc_attr($key) . '" value="' . esc_attr($value) . '">';
                        }
                    }
                }
                ?>
                
                <?php if ($atts['show_brand'] === 'true'): ?>
                    <div class="filter-group">
                        <h4 class="filter-title">
                            <span class="toggle-arrow">▼</span>
                            <?php _e('Марки', 'parfume-reviews'); ?>
                        </h4>
                        <div class="filter-options">
                            <input type="text" class="filter-search" placeholder="<?php _e('Търсене в марките...', 'parfume-reviews'); ?>">
                            <label class="filter-option">
                                <input type="checkbox" name="brand[]" value="all" class="select-all"> 
                                <?php _e('Всички', 'parfume-reviews'); ?>
                            </label>
                            <div class="scrollable-options" style="max-height: 200px; overflow-y: auto;">
                                <?php
                                $brands = get_terms(array(
                                    'taxonomy' => 'marki',
                                    'hide_empty' => filter_var($atts['hide_empty'], FILTER_VALIDATE_BOOLEAN),
                                    'number' => 50,
                                ));
                                
                                if (!is_wp_error($brands) && !empty($brands)):
                                    $selected_brands = isset($_GET['brand']) ? (array) $_GET['brand'] : array();
                                    
                                    foreach ($brands as $brand): 
                                        // ПОПРАВЕНО: Проверяваме че $brand е валиден object
                                        if (!is_object($brand) || !isset($brand->slug) || !isset($brand->name) || !isset($brand->count)) {
                                            continue;
                                        }
                                        ?>
                                        <label class="filter-option">
                                            <input type="checkbox" name="brand[]" value="<?php echo esc_attr($brand->slug); ?>" <?php echo in_array($brand->slug, $selected_brands) ? 'checked' : ''; ?>>
                                            <?php echo esc_html($brand->name); ?> (<?php echo intval($brand->count); ?>)
                                        </label>
                                    <?php endforeach;
                                endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if ($atts['show_gender'] === 'true'): ?>
                    <div class="filter-group">
                        <h4 class="filter-title">
                            <span class="toggle-arrow">▼</span>
                            <?php _e('Пол', 'parfume-reviews'); ?>
                        </h4>
                        <div class="filter-options">
                            <label class="filter-option">
                                <input type="checkbox" name="gender[]" value="all" class="select-all"> 
                                <?php _e('Всички', 'parfume-reviews'); ?>
                            </label>
                            <div class="scrollable-options">
                                <?php
                                $genders = get_terms(array(
                                    'taxonomy' => 'gender',
                                    'hide_empty' => filter_var($atts['hide_empty'], FILTER_VALIDATE_BOOLEAN),
                                ));
                                
                                if (!is_wp_error($genders) && !empty($genders)):
                                    $selected_genders = isset($_GET['gender']) ? (array) $_GET['gender'] : array();
                                    
                                    foreach ($genders as $gender): 
                                        // ПОПРАВЕНО: Проверяваме че $gender е валиден object
                                        if (!is_object($gender) || !isset($gender->slug) || !isset($gender->name) || !isset($gender->count)) {
                                            continue;
                                        }
                                        ?>
                                        <label class="filter-option">
                                            <input type="checkbox" name="gender[]" value="<?php echo esc_attr($gender->slug); ?>" <?php echo in_array($gender->slug, $selected_genders) ? 'checked' : ''; ?>>
                                            <?php echo esc_html($gender->name); ?> (<?php echo intval($gender->count); ?>)
                                        </label>
                                    <?php endforeach;
                                endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if ($atts['show_aroma_type'] === 'true'): ?>
                    <div class="filter-group">
                        <h4 class="filter-title">
                            <span class="toggle-arrow">▼</span>
                            <?php _e('Тип аромат', 'parfume-reviews'); ?>
                        </h4>
                        <div class="filter-options">
                            <label class="filter-option">
                                <input type="checkbox" name="aroma_type[]" value="all" class="select-all"> 
                                <?php _e('Всички', 'parfume-reviews'); ?>
                            </label>
                            <div class="scrollable-options">
                                <?php
                                $aroma_types = get_terms(array(
                                    'taxonomy' => 'aroma_type',
                                    'hide_empty' => filter_var($atts['hide_empty'], FILTER_VALIDATE_BOOLEAN),
                                ));
                                
                                if (!is_wp_error($aroma_types) && !empty($aroma_types)):
                                    $selected_aroma_types = isset($_GET['aroma_type']) ? (array) $_GET['aroma_type'] : array();
                                    
                                    foreach ($aroma_types as $aroma_type): 
                                        // ПОПРАВЕНО: Проверяваме че $aroma_type е валиден object
                                        if (!is_object($aroma_type) || !isset($aroma_type->slug) || !isset($aroma_type->name) || !isset($aroma_type->count)) {
                                            continue;
                                        }
                                        ?>
                                        <label class="filter-option">
                                            <input type="checkbox" name="aroma_type[]" value="<?php echo esc_attr($aroma_type->slug); ?>" <?php echo in_array($aroma_type->slug, $selected_aroma_types) ? 'checked' : ''; ?>>
                                            <?php echo esc_html($aroma_type->name); ?> (<?php echo intval($aroma_type->count); ?>)
                                        </label>
                                    <?php endforeach;
                                endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if ($atts['show_season'] === 'true'): ?>
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
                            <div class="scrollable-options">
                                <?php
                                $seasons = get_terms(array(
                                    'taxonomy' => 'season',
                                    'hide_empty' => filter_var($atts['hide_empty'], FILTER_VALIDATE_BOOLEAN),
                                ));
                                
                                if (!is_wp_error($seasons) && !empty($seasons)):
                                    $selected_seasons = isset($_GET['season']) ? (array) $_GET['season'] : array();
                                    
                                    foreach ($seasons as $season): 
                                        // ПОПРАВЕНО: Проверяваме че $season е валиден object
                                        if (!is_object($season) || !isset($season->slug) || !isset($season->name) || !isset($season->count)) {
                                            continue;
                                        }
                                        ?>
                                        <label class="filter-option">
                                            <input type="checkbox" name="season[]" value="<?php echo esc_attr($season->slug); ?>" <?php echo in_array($season->slug, $selected_seasons) ? 'checked' : ''; ?>>
                                            <?php echo esc_html($season->name); ?> (<?php echo intval($season->count); ?>)
                                        </label>
                                    <?php endforeach;
                                endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if ($atts['show_intensity'] === 'true'): ?>
                    <div class="filter-group">
                        <h4 class="filter-title">
                            <span class="toggle-arrow">▼</span>
                            <?php _e('Интензивност', 'parfume-reviews'); ?>
                        </h4>
                        <div class="filter-options">
                            <label class="filter-option">
                                <input type="checkbox" name="intensity[]" value="all" class="select-all"> 
                                <?php _e('Всички', 'parfume-reviews'); ?>
                            </label>
                            <div class="scrollable-options">
                                <?php
                                $intensities = get_terms(array(
                                    'taxonomy' => 'intensity',
                                    'hide_empty' => filter_var($atts['hide_empty'], FILTER_VALIDATE_BOOLEAN),
                                ));
                                
                                if (!is_wp_error($intensities) && !empty($intensities)):
                                    $selected_intensities = isset($_GET['intensity']) ? (array) $_GET['intensity'] : array();
                                    
                                    foreach ($intensities as $intensity): 
                                        // ПОПРАВЕНО: Проверяваме че $intensity е валиден object
                                        if (!is_object($intensity) || !isset($intensity->slug) || !isset($intensity->name) || !isset($intensity->count)) {
                                            continue;
                                        }
                                        ?>
                                        <label class="filter-option">
                                            <input type="checkbox" name="intensity[]" value="<?php echo esc_attr($intensity->slug); ?>" <?php echo in_array($intensity->slug, $selected_intensities) ? 'checked' : ''; ?>>
                                            <?php echo esc_html($intensity->name); ?> (<?php echo intval($intensity->count); ?>)
                                        </label>
                                    <?php endforeach;
                                endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if ($atts['show_notes'] === 'true'): ?>
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
                                    'hide_empty' => filter_var($atts['hide_empty'], FILTER_VALIDATE_BOOLEAN),
                                    'number' => 50,
                                ));
                                
                                if (!is_wp_error($notes) && !empty($notes)):
                                    $selected_notes = isset($_GET['notes']) ? (array) $_GET['notes'] : array();
                                    
                                    foreach ($notes as $note): 
                                        // ПОПРАВЕНО: Проверяваме че $note е валиден object
                                        if (!is_object($note) || !isset($note->slug) || !isset($note->name) || !isset($note->count)) {
                                            continue;
                                        }
                                        ?>
                                        <label class="filter-option">
                                            <input type="checkbox" name="notes[]" value="<?php echo esc_attr($note->slug); ?>" <?php echo in_array($note->slug, $selected_notes) ? 'checked' : ''; ?>>
                                            <?php echo esc_html($note->name); ?> (<?php echo intval($note->count); ?>)
                                        </label>
                                    <?php endforeach;
                                endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if ($atts['show_perfumer'] === 'true'): ?>
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
                                    'hide_empty' => filter_var($atts['hide_empty'], FILTER_VALIDATE_BOOLEAN),
                                    'number' => 50,
                                ));
                                
                                if (!is_wp_error($perfumers) && !empty($perfumers)):
                                    $selected_perfumers = isset($_GET['perfumer']) ? (array) $_GET['perfumer'] : array();
                                    
                                    foreach ($perfumers as $perfumer): 
                                        // ПОПРАВЕНО: Проверяваме че $perfumer е валиден object
                                        if (!is_object($perfumer) || !isset($perfumer->slug) || !isset($perfumer->name) || !isset($perfumer->count)) {
                                            continue;
                                        }
                                        ?>
                                        <label class="filter-option">
                                            <input type="checkbox" name="perfumer[]" value="<?php echo esc_attr($perfumer->slug); ?>" <?php echo in_array($perfumer->slug, $selected_perfumers) ? 'checked' : ''; ?>>
                                            <?php echo esc_html($perfumer->name); ?> (<?php echo intval($perfumer->count); ?>)
                                        </label>
                                    <?php endforeach;
                                endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                
                <div class="filter-actions">
                    <button type="submit" class="btn btn-primary"><?php _e('Филтрирай', 'parfume-reviews'); ?></button>
                    <a href="<?php echo esc_url(strtok($_SERVER['REQUEST_URI'], '?')); ?>" class="btn btn-secondary"><?php _e('Изчисти', 'parfume-reviews'); ?></a>
                </div>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Shortcode за мрежа от парфюми
     */
    public function parfume_grid_shortcode($atts) {
        $atts = shortcode_atts(array(
            'posts_per_page' => 12,
            'columns' => 3,
            'orderby' => 'date',
            'order' => 'DESC',
            'meta_key' => '',
            'meta_value' => '',
            'brand' => '',
            'gender' => '',
            'aroma_type' => '',
            'season' => '',
            'intensity' => '',
            'show_rating' => true,
            'show_price' => true,
            'show_excerpt' => false,
        ), $atts);
        
        $args = array(
            'post_type' => 'parfume',
            'posts_per_page' => intval($atts['posts_per_page']),
            'orderby' => sanitize_text_field($atts['orderby']),
            'order' => sanitize_text_field($atts['order']),
            'post_status' => 'publish',
        );
        
        // Meta query
        if (!empty($atts['meta_key']) && !empty($atts['meta_value'])) {
            $args['meta_query'] = array(
                array(
                    'key' => sanitize_text_field($atts['meta_key']),
                    'value' => sanitize_text_field($atts['meta_value']),
                    'compare' => '='
                )
            );
        }
        
        // Tax query
        $tax_query = array();
        $taxonomies = array('brand' => 'marki', 'gender' => 'gender', 'aroma_type' => 'aroma_type', 'season' => 'season', 'intensity' => 'intensity');
        
        foreach ($taxonomies as $att_key => $taxonomy) {
            if (!empty($atts[$att_key])) {
                $tax_query[] = array(
                    'taxonomy' => $taxonomy,
                    'field' => 'slug',
                    'terms' => sanitize_text_field($atts[$att_key])
                );
            }
        }
        
        if (!empty($tax_query)) {
            $args['tax_query'] = $tax_query;
        }
        
        $query = new \WP_Query($args);
        
        if (!$query->have_posts()) {
            return '<p>' . __('No parfumes found.', 'parfume-reviews') . '</p>';
        }
        
        $output = '<div class="parfume-grid columns-' . intval($atts['columns']) . '">';
        
        while ($query->have_posts()) {
            $query->the_post();
            
            $output .= '<div class="parfume-card">';
            $output .= '<div class="parfume-thumbnail">';
            $output .= '<a href="' . get_permalink() . '">';
            
            if (has_post_thumbnail()) {
                $output .= get_the_post_thumbnail(get_the_ID(), 'medium');
            } else {
                $output .= '<div class="no-image"><span>' . __('No Image', 'parfume-reviews') . '</span></div>';
            }
            
            $output .= '</a>';
            $output .= '</div>';
            
            $output .= '<div class="parfume-content">';
            $output .= '<h3 class="parfume-title"><a href="' . get_permalink() . '">' . get_the_title() . '</a></h3>';
            
            // Brand
            $brands = wp_get_post_terms(get_the_ID(), 'marki', array('fields' => 'names'));
            if (!is_wp_error($brands) && !empty($brands)) {
                $output .= '<div class="parfume-brand">' . esc_html($brands[0]) . '</div>';
            }
            
            // Rating
            if (filter_var($atts['show_rating'], FILTER_VALIDATE_BOOLEAN)) {
                $rating = get_post_meta(get_the_ID(), '_parfume_rating', true);
                if (!empty($rating) && is_numeric($rating)) {
                    $output .= '<div class="parfume-rating">';
                    $output .= '<span class="stars">';
                    for ($i = 1; $i <= 5; $i++) {
                        $output .= '<span class="star ' . ($i <= $rating ? 'filled' : '') . '">★</span>';
                    }
                    $output .= '</span>';
                    $output .= '<span class="rating-value">' . esc_html($rating) . '</span>';
                    $output .= '</div>';
                }
            }
            
            // Excerpt
            if (filter_var($atts['show_excerpt'], FILTER_VALIDATE_BOOLEAN) && has_excerpt()) {
                $output .= '<div class="parfume-excerpt">' . get_the_excerpt() . '</div>';
            }
            
            $output .= '</div>';
            $output .= '</div>';
        }
        
        $output .= '</div>';
        
        wp_reset_postdata();
        
        return $output;
    }
    
    /**
     * Shortcode за най-нови парфюми
     */
    public function latest_parfumes_shortcode($atts) {
        $atts = shortcode_atts(array(
            'posts_per_page' => 6,
            'columns' => 3,
        ), $atts);
        
        $atts['orderby'] = 'date';
        $atts['order'] = 'DESC';
        
        return $this->parfume_grid_shortcode($atts);
    }
    
    /**
     * Shortcode за препоръчани парфюми
     */
    public function featured_parfumes_shortcode($atts) {
        $atts = shortcode_atts(array(
            'posts_per_page' => 6,
            'columns' => 3,
        ), $atts);
        
        $atts['meta_key'] = '_parfume_featured';
        $atts['meta_value'] = 'yes';
        
        return $this->parfume_grid_shortcode($atts);
    }
    
    /**
     * Shortcode за най-високо оценени парфюми
     */
    public function top_rated_parfumes_shortcode($atts) {
        $atts = shortcode_atts(array(
            'posts_per_page' => 6,
            'columns' => 3,
        ), $atts);
        
        $atts['meta_key'] = '_parfume_rating';
        $atts['orderby'] = 'meta_value_num';
        $atts['order'] = 'DESC';
        
        return $this->parfume_grid_shortcode($atts);
    }
    
    /**
     * Shortcode за архив на всички марки
     */
    public function all_brands_archive_shortcode($atts) {
        $atts = shortcode_atts(array(
            'show_count' => true,
            'show_image' => true,
            'columns' => 4,
            'orderby' => 'name',
            'order' => 'ASC',
            'hide_empty' => true,
        ), $atts);
        
        $terms = get_terms(array(
            'taxonomy' => 'marki',
            'hide_empty' => filter_var($atts['hide_empty'], FILTER_VALIDATE_BOOLEAN),
            'orderby' => sanitize_text_field($atts['orderby']),
            'order' => sanitize_text_field($atts['order']),
        ));
        
        if (is_wp_error($terms) || empty($terms)) {
            return '<p>' . __('No brands found.', 'parfume-reviews') . '</p>';
        }
        
        $output = '<div class="brands-archive-grid columns-' . intval($atts['columns']) . '">';
        
        foreach ($terms as $term) {
            // ПОПРАВЕНО: Проверяваме че $term е валиден object
            if (!is_object($term) || !isset($term->slug) || !isset($term->name) || !isset($term->count)) {
                continue;
            }
            
            $term_link = get_term_link($term);
            if (is_wp_error($term_link)) {
                continue;
            }
            
            $output .= '<div class="brand-card">';
            $output .= '<a href="' . esc_url($term_link) . '">';
            
            if (filter_var($atts['show_image'], FILTER_VALIDATE_BOOLEAN)) {
                $image_id = get_term_meta($term->term_id, 'marki-image-id', true);
                if ($image_id) {
                    $image_url = wp_get_attachment_image_url($image_id, 'medium');
                    if ($image_url) {
                        $output .= '<div class="brand-image"><img src="' . esc_url($image_url) . '" alt="' . esc_attr($term->name) . '"></div>';
                    }
                }
            }
            
            $output .= '<h3>' . esc_html($term->name) . '</h3>';
            
            if (filter_var($atts['show_count'], FILTER_VALIDATE_BOOLEAN)) {
                $output .= '<span class="count">(' . $term->count . ' ' . __('parfumes', 'parfume-reviews') . ')</span>';
            }
            
            $output .= '</a>';
            $output .= '</div>';
        }
        
        $output .= '</div>';
        
        return $output;
    }
    
    /**
     * Shortcode за архив на всички нотки
     */
    public function all_notes_archive_shortcode($atts) {
        $atts = shortcode_atts(array(
            'show_count' => true,
            'columns' => 6,
            'orderby' => 'name',
            'order' => 'ASC',
            'hide_empty' => true,
            'number' => 100,
        ), $atts);
        
        $terms = get_terms(array(
            'taxonomy' => 'notes',
            'hide_empty' => filter_var($atts['hide_empty'], FILTER_VALIDATE_BOOLEAN),
            'orderby' => sanitize_text_field($atts['orderby']),
            'order' => sanitize_text_field($atts['order']),
            'number' => intval($atts['number']),
        ));
        
        if (is_wp_error($terms) || empty($terms)) {
            return '<p>' . __('No notes found.', 'parfume-reviews') . '</p>';
        }
        
        $output = '<div class="notes-archive-grid columns-' . intval($atts['columns']) . '">';
        
        foreach ($terms as $term) {
            // ПОПРАВЕНО: Проверяваме че $term е валиден object
            if (!is_object($term) || !isset($term->slug) || !isset($term->name) || !isset($term->count)) {
                continue;
            }
            
            $term_link = get_term_link($term);
            if (is_wp_error($term_link)) {
                continue;
            }
            
            $output .= '<div class="note-card">';
            $output .= '<a href="' . esc_url($term_link) . '">';
            $output .= '<span class="note-name">' . esc_html($term->name) . '</span>';
            
            if (filter_var($atts['show_count'], FILTER_VALIDATE_BOOLEAN)) {
                $output .= '<span class="count">(' . $term->count . ')</span>';
            }
            
            $output .= '</a>';
            $output .= '</div>';
        }
        
        $output .= '</div>';
        
        return $output;
    }
    
    /**
     * Shortcode за архив на всички парфюмеристи
     */
    public function all_perfumers_archive_shortcode($atts) {
        $atts = shortcode_atts(array(
            'show_count' => true,
            'show_image' => true,
            'columns' => 3,
            'orderby' => 'name',
            'order' => 'ASC',
            'hide_empty' => true,
        ), $atts);
        
        $terms = get_terms(array(
            'taxonomy' => 'perfumer',
            'hide_empty' => filter_var($atts['hide_empty'], FILTER_VALIDATE_BOOLEAN),
            'orderby' => sanitize_text_field($atts['orderby']),
            'order' => sanitize_text_field($atts['order']),
        ));
        
        if (is_wp_error($terms) || empty($terms)) {
            return '<p>' . __('No perfumers found.', 'parfume-reviews') . '</p>';
        }
        
        $output = '<div class="perfumers-archive-grid columns-' . intval($atts['columns']) . '">';
        
        foreach ($terms as $term) {
            // ПОПРАВЕНО: Проверяваме че $term е валиден object
            if (!is_object($term) || !isset($term->slug) || !isset($term->name) || !isset($term->count)) {
                continue;
            }
            
            $term_link = get_term_link($term);
            if (is_wp_error($term_link)) {
                continue;
            }
            
            $output .= '<div class="perfumer-card">';
            $output .= '<a href="' . esc_url($term_link) . '">';
            
            if (filter_var($atts['show_image'], FILTER_VALIDATE_BOOLEAN)) {
                $image_id = get_term_meta($term->term_id, 'perfumer-image-id', true);
                if ($image_id) {
                    $image_url = wp_get_attachment_image_url($image_id, 'medium');
                    if ($image_url) {
                        $output .= '<div class="perfumer-image"><img src="' . esc_url($image_url) . '" alt="' . esc_attr($term->name) . '"></div>';
                    }
                }
            }
            
            $output .= '<h3>' . esc_html($term->name) . '</h3>';
            
            if (filter_var($atts['show_count'], FILTER_VALIDATE_BOOLEAN)) {
                $output .= '<span class="count">(' . $term->count . ' ' . __('parfumes', 'parfume-reviews') . ')</span>';
            }
            
            $output .= '</a>';
            $output .= '</div>';
        }
        
        $output .= '</div>';
        
        return $output;
    }
}