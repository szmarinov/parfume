<?php
namespace Parfume_Reviews;

/**
 * Shortcodes Handler - управлява всички shortcodes
 * 📁 Файл: includes/class-shortcodes.php
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
                                        // Проверяваме че $brand е валиден object
                                        if (!is_object($brand) || !isset($brand->slug)) continue;
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
                            <?php
                            $genders = get_terms(array(
                                'taxonomy' => 'gender',
                                'hide_empty' => filter_var($atts['hide_empty'], FILTER_VALIDATE_BOOLEAN),
                            ));
                            
                            if (!is_wp_error($genders) && !empty($genders)):
                                $selected_genders = isset($_GET['gender']) ? (array) $_GET['gender'] : array();
                                
                                foreach ($genders as $gender): 
                                    // Проверяваме че $gender е валиден object
                                    if (!is_object($gender) || !isset($gender->slug)) continue;
                                    ?>
                                    <label class="filter-option">
                                        <input type="checkbox" name="gender[]" value="<?php echo esc_attr($gender->slug); ?>" <?php echo in_array($gender->slug, $selected_genders) ? 'checked' : ''; ?>>
                                        <?php echo esc_html($gender->name); ?> (<?php echo intval($gender->count); ?>)
                                    </label>
                                <?php endforeach;
                            endif; ?>
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
                            <?php
                            $aroma_types = get_terms(array(
                                'taxonomy' => 'aroma_type',
                                'hide_empty' => filter_var($atts['hide_empty'], FILTER_VALIDATE_BOOLEAN),
                            ));
                            
                            if (!is_wp_error($aroma_types) && !empty($aroma_types)):
                                $selected_aroma_types = isset($_GET['aroma_type']) ? (array) $_GET['aroma_type'] : array();
                                
                                foreach ($aroma_types as $aroma_type): 
                                    // Проверяваме че $aroma_type е валиден object
                                    if (!is_object($aroma_type) || !isset($aroma_type->slug)) continue;
                                    ?>
                                    <label class="filter-option">
                                        <input type="checkbox" name="aroma_type[]" value="<?php echo esc_attr($aroma_type->slug); ?>" <?php echo in_array($aroma_type->slug, $selected_aroma_types) ? 'checked' : ''; ?>>
                                        <?php echo esc_html($aroma_type->name); ?> (<?php echo intval($aroma_type->count); ?>)
                                    </label>
                                <?php endforeach;
                            endif; ?>
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
                            <?php
                            $seasons = get_terms(array(
                                'taxonomy' => 'season',
                                'hide_empty' => filter_var($atts['hide_empty'], FILTER_VALIDATE_BOOLEAN),
                            ));
                            
                            if (!is_wp_error($seasons) && !empty($seasons)):
                                $selected_seasons = isset($_GET['season']) ? (array) $_GET['season'] : array();
                                
                                foreach ($seasons as $season): 
                                    // Проверяваме че $season е валиден object
                                    if (!is_object($season) || !isset($season->slug)) continue;
                                    ?>
                                    <label class="filter-option">
                                        <input type="checkbox" name="season[]" value="<?php echo esc_attr($season->slug); ?>" <?php echo in_array($season->slug, $selected_seasons) ? 'checked' : ''; ?>>
                                        <?php echo esc_html($season->name); ?> (<?php echo intval($season->count); ?>)
                                    </label>
                                <?php endforeach;
                            endif; ?>
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
                            <?php
                            $intensities = get_terms(array(
                                'taxonomy' => 'intensity',
                                'hide_empty' => filter_var($atts['hide_empty'], FILTER_VALIDATE_BOOLEAN),
                            ));
                            
                            if (!is_wp_error($intensities) && !empty($intensities)):
                                $selected_intensities = isset($_GET['intensity']) ? (array) $_GET['intensity'] : array();
                                
                                foreach ($intensities as $intensity): 
                                    // Проверяваме че $intensity е валиден object
                                    if (!is_object($intensity) || !isset($intensity->slug)) continue;
                                    ?>
                                    <label class="filter-option">
                                        <input type="checkbox" name="intensity[]" value="<?php echo esc_attr($intensity->slug); ?>" <?php echo in_array($intensity->slug, $selected_intensities) ? 'checked' : ''; ?>>
                                        <?php echo esc_html($intensity->name); ?> (<?php echo intval($intensity->count); ?>)
                                    </label>
                                <?php endforeach;
                            endif; ?>
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
                                        // Проверяваме че $note е валиден object
                                        if (!is_object($note) || !isset($note->slug)) continue;
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
                                        // Проверяваме че $perfumer е валиден object
                                        if (!is_object($perfumer) || !isset($perfumer->slug)) continue;
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
                    <button type="submit" class="apply-filters-btn">
                        <?php _e('Приложи филтри', 'parfume-reviews'); ?>
                    </button>
                    <button type="button" class="clear-filters-btn">
                        <?php _e('Изчисти', 'parfume-reviews'); ?>
                    </button>
                </div>
            </form>
        </div>
        
        <style>
        .parfume-filters-widget {
            background: white;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 1rem;
            margin-bottom: 1rem;
        }
        
        .filter-group {
            margin-bottom: 1rem;
            border-bottom: 1px solid #eee;
            padding-bottom: 0.5rem;
        }
        
        .filter-title {
            cursor: pointer;
            margin: 0 0 0.5rem 0;
            font-size: 1rem;
            color: #333;
        }
        
        .toggle-arrow {
            display: inline-block;
            margin-right: 0.5rem;
            transition: transform 0.2s;
        }
        
        .filter-title.collapsed .toggle-arrow {
            transform: rotate(0deg);
        }
        
        .filter-options {
            margin-left: 1rem;
        }
        
        .filter-option {
            display: block;
            margin-bottom: 0.25rem;
            cursor: pointer;
        }
        
        .filter-search {
            width: 100%;
            padding: 0.25rem;
            margin-bottom: 0.5rem;
            border: 1px solid #ccc;
            border-radius: 3px;
        }
        
        .filter-actions {
            margin-top: 1rem;
            text-align: center;
        }
        
        .apply-filters-btn,
        .clear-filters-btn {
            margin: 0 0.25rem;
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 3px;
            cursor: pointer;
        }
        
        .apply-filters-btn {
            background: #007cba;
            color: white;
        }
        
        .clear-filters-btn {
            background: #666;
            color: white;
        }
        </style>
        
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Toggle filter groups
            document.querySelectorAll('.filter-title').forEach(function(title) {
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
            
            // Clear filters
            document.querySelector('.clear-filters-btn')?.addEventListener('click', function() {
                document.querySelectorAll('.parfume-filters-form input[type="checkbox"]').forEach(function(cb) {
                    cb.checked = false;
                });
                document.querySelectorAll('.parfume-filters-form input[type="text"]').forEach(function(input) {
                    input.value = '';
                });
            });
            
            // Filter search functionality
            document.querySelectorAll('.filter-search').forEach(function(searchInput) {
                searchInput.addEventListener('input', function() {
                    const searchTerm = this.value.toLowerCase();
                    const group = this.closest('.filter-group');
                    const options = group.querySelectorAll('.filter-option');
                    
                    options.forEach(function(option) {
                        const label = option.textContent.toLowerCase();
                        if (label.includes(searchTerm)) {
                            option.style.display = 'block';
                        } else {
                            option.style.display = 'none';
                        }
                    });
                });
            });
        });
        </script>
        <?php
        
        return ob_get_clean();
    }
    
    /**
     * Останалите shortcode методи...
     */
    public function parfume_grid_shortcode($atts) {
        // Implementation here...
        return '';
    }
    
    public function latest_parfumes_shortcode($atts) {
        // Implementation here...
        return '';
    }
    
    public function featured_parfumes_shortcode($atts) {
        // Implementation here...
        return '';
    }
    
    public function top_rated_parfumes_shortcode($atts) {
        // Implementation here...
        return '';
    }
    
    public function all_brands_archive_shortcode($atts) {
        // Implementation here...
        return '';
    }
    
    public function all_notes_archive_shortcode($atts) {
        // Implementation here...
        return '';
    }
    
    public function all_perfumers_archive_shortcode($atts) {
        // Implementation here...
        return '';
    }
}