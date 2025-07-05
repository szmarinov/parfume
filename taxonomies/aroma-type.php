<?php
/**
 * Aroma Type Taxonomy
 * 
 * @package Parfume_Reviews
 * @subpackage Taxonomies
 */

namespace Parfume_Reviews\Taxonomies;

use Parfume_Reviews\Utils\Taxonomy_Base;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Aroma Type Taxonomy Class
 */
class Aroma_Type extends Taxonomy_Base {
    
    /**
     * Taxonomy key
     */
    protected $taxonomy = 'aroma_type';
    
    /**
     * Post types
     */
    protected $post_types = array('parfume');
    
    /**
     * Initialize the taxonomy
     */
    public function init() {
        parent::init();
        
        // Additional hooks for aroma types
        add_action('aroma_type_add_form_fields', array($this, 'add_custom_fields'));
        add_action('aroma_type_edit_form_fields', array($this, 'edit_custom_fields'), 10, 2);
        add_action('created_aroma_type', array($this, 'save_custom_fields'), 10, 2);
        add_action('edited_aroma_type', array($this, 'save_custom_fields'), 10, 2);
        
        // Custom columns
        add_filter('manage_edit-aroma_type_columns', array($this, 'custom_columns'));
        add_filter('manage_aroma_type_custom_column', array($this, 'custom_column_content'), 10, 3);
        
        // AJAX handlers
        add_action('wp_ajax_get_aroma_type_info', array($this, 'ajax_get_aroma_type_info'));
        add_action('wp_ajax_nopriv_get_aroma_type_info', array($this, 'ajax_get_aroma_type_info'));
    }
    
    /**
     * Get taxonomy configuration
     */
    protected function get_config() {
        return array(
            'labels' => array(
                'name' => __('Aroma Types', 'parfume-reviews'),
                'singular_name' => __('Aroma Type', 'parfume-reviews'),
                'search_items' => __('Search Aroma Types', 'parfume-reviews'),
                'all_items' => __('All Aroma Types', 'parfume-reviews'),
                'edit_item' => __('Edit Aroma Type', 'parfume-reviews'),
                'update_item' => __('Update Aroma Type', 'parfume-reviews'),
                'add_new_item' => __('Add New Aroma Type', 'parfume-reviews'),
                'new_item_name' => __('New Aroma Type Name', 'parfume-reviews'),
                'menu_name' => __('Aroma Types', 'parfume-reviews'),
                'view_item' => __('View Aroma Type', 'parfume-reviews'),
                'popular_items' => __('Popular Aroma Types', 'parfume-reviews'),
                'separate_items_with_commas' => __('Separate aroma types with commas', 'parfume-reviews'),
                'add_or_remove_items' => __('Add or remove aroma types', 'parfume-reviews'),
                'choose_from_most_used' => __('Choose from the most used aroma types', 'parfume-reviews'),
                'not_found' => __('No aroma types found.', 'parfume-reviews'),
            ),
            'args' => array(
                'hierarchical' => true,
                'show_ui' => true,
                'show_admin_column' => true,
                'query_var' => true,
                'rewrite' => array(
                    'slug' => $this->get_rewrite_slug(),
                    'with_front' => false,
                    'hierarchical' => true
                ),
                'show_in_rest' => true,
                'public' => true,
                'publicly_queryable' => true,
                'show_in_nav_menus' => true,
                'show_tagcloud' => true,
                'meta_box_cb' => 'post_categories_meta_box',
                'capabilities' => array(
                    'manage_terms' => 'manage_parfume_aroma_types',
                    'edit_terms' => 'edit_parfume_aroma_types',
                    'delete_terms' => 'delete_parfume_aroma_types',
                    'assign_terms' => 'assign_parfume_aroma_types',
                ),
            )
        );
    }
    
    /**
     * Get default terms
     */
    protected function get_default_terms() {
        return array(
            'Тоалетна вода (EDT)' => array(
                'description' => 'Най-лекият тип парфюм с концентрация 5-15% ароматни масла. Идеален за ежедневието.',
                'meta' => array(
                    'concentration_min' => 5,
                    'concentration_max' => 15,
                    'longevity_hours' => '2-4',
                    'sillage' => 'Light',
                    'price_range' => 'Budget to Mid-range',
                    'best_for' => 'Daily wear, office, casual',
                    'application_tips' => 'Apply liberally, reapply during the day'
                )
            ),
            'Парфюмна вода (EDP)' => array(
                'description' => 'Средна концентрация 15-20% ароматни масла. Най-популярният тип парфюм.',
                'meta' => array(
                    'concentration_min' => 15,
                    'concentration_max' => 20,
                    'longevity_hours' => '4-6',
                    'sillage' => 'Moderate',
                    'price_range' => 'Mid-range to High-end',
                    'best_for' => 'Versatile, day to evening wear',
                    'application_tips' => 'Apply to pulse points, moderate spraying'
                )
            ),
            'Парфюм (Parfum/Extrait)' => array(
                'description' => 'Най-високата концентрация 20-40% ароматни масла. Луксозен и дълготраен.',
                'meta' => array(
                    'concentration_min' => 20,
                    'concentration_max' => 40,
                    'longevity_hours' => '6-12',
                    'sillage' => 'Strong',
                    'price_range' => 'Luxury to Ultra-luxury',
                    'best_for' => 'Special occasions, evening wear',
                    'application_tips' => 'Use sparingly, small dabs on pulse points'
                )
            ),
            'Парфюмен елексир' => array(
                'description' => 'Ултра концентрирана версия с 25-35% ароматни масла. Изключително интензивен.',
                'meta' => array(
                    'concentration_min' => 25,
                    'concentration_max' => 35,
                    'longevity_hours' => '8-16',
                    'sillage' => 'Very Strong',
                    'price_range' => 'Ultra-luxury',
                    'best_for' => 'Cold weather, special events',
                    'application_tips' => 'Minimal application, one spray is enough'
                )
            ),
            'Одеколон (EDC)' => array(
                'description' => 'Най-лекият тип с концентрация 2-5%. Традиционен и освежаващ.',
                'meta' => array(
                    'concentration_min' => 2,
                    'concentration_max' => 5,
                    'longevity_hours' => '1-2',
                    'sillage' => 'Very Light',
                    'price_range' => 'Budget',
                    'best_for' => 'Quick refresh, hot weather',
                    'application_tips' => 'Apply generously, frequent reapplication needed'
                )
            ),
            'Парфюмна мъгла (Body Mist)' => array(
                'description' => 'Много лека формула за тялото с концентрация 1-3%. Освежаваща и лека.',
                'meta' => array(
                    'concentration_min' => 1,
                    'concentration_max' => 3,
                    'longevity_hours' => '0.5-1',
                    'sillage' => 'Minimal',
                    'price_range' => 'Budget',
                    'best_for' => 'Light freshening, layering',
                    'application_tips' => 'Spray all over body, reapply frequently'
                )
            )
        );
    }
    
    /**
     * Add custom fields to add form
     */
    public function add_custom_fields($taxonomy) {
        ?>
        <div class="form-field term-concentration-wrap">
            <label><?php _e('Concentration Range', 'parfume-reviews'); ?></label>
            <div style="display: flex; gap: 10px; align-items: center;">
                <input type="number" name="aroma_concentration_min" placeholder="Min %" min="0" max="100" step="0.1" style="width: 80px;" />
                <span>-</span>
                <input type="number" name="aroma_concentration_max" placeholder="Max %" min="0" max="100" step="0.1" style="width: 80px;" />
                <span>%</span>
            </div>
            <p class="description"><?php _e('Typical concentration range of aromatic oils.', 'parfume-reviews'); ?></p>
        </div>
        
        <div class="form-field term-longevity-wrap">
            <label for="aroma_longevity_hours"><?php _e('Longevity', 'parfume-reviews'); ?></label>
            <input type="text" name="aroma_longevity_hours" id="aroma_longevity_hours" value="" placeholder="e.g. 4-6 hours" />
            <p class="description"><?php _e('How long this type typically lasts on skin.', 'parfume-reviews'); ?></p>
        </div>
        
        <div class="form-field term-sillage-wrap">
            <label for="aroma_sillage"><?php _e('Sillage', 'parfume-reviews'); ?></label>
            <select name="aroma_sillage" id="aroma_sillage">
                <option value=""><?php _e('Select sillage', 'parfume-reviews'); ?></option>
                <option value="Minimal"><?php _e('Minimal', 'parfume-reviews'); ?></option>
                <option value="Light"><?php _e('Light', 'parfume-reviews'); ?></option>
                <option value="Moderate"><?php _e('Moderate', 'parfume-reviews'); ?></option>
                <option value="Strong"><?php _e('Strong', 'parfume-reviews'); ?></option>
                <option value="Very Strong"><?php _e('Very Strong', 'parfume-reviews'); ?></option>
            </select>
            <p class="description"><?php _e('Typical projection strength of this aroma type.', 'parfume-reviews'); ?></p>
        </div>
        
        <div class="form-field term-price-range-wrap">
            <label for="aroma_price_range"><?php _e('Price Range', 'parfume-reviews'); ?></label>
            <select name="aroma_price_range" id="aroma_price_range">
                <option value=""><?php _e('Select price range', 'parfume-reviews'); ?></option>
                <option value="Budget"><?php _e('Budget (под 50 лв.)', 'parfume-reviews'); ?></option>
                <option value="Mid-range"><?php _e('Mid-range (50-150 лв.)', 'parfume-reviews'); ?></option>
                <option value="High-end"><?php _e('High-end (150-300 лв.)', 'parfume-reviews'); ?></option>
                <option value="Luxury"><?php _e('Luxury (300-500 лв.)', 'parfume-reviews'); ?></option>
                <option value="Ultra-luxury"><?php _e('Ultra-luxury (над 500 лв.)', 'parfume-reviews'); ?></option>
            </select>
            <p class="description"><?php _e('Typical price range for this aroma type.', 'parfume-reviews'); ?></p>
        </div>
        
        <div class="form-field term-best-for-wrap">
            <label for="aroma_best_for"><?php _e('Best For', 'parfume-reviews'); ?></label>
            <textarea name="aroma_best_for" id="aroma_best_for" rows="2" cols="40"></textarea>
            <p class="description"><?php _e('Best occasions and situations for this aroma type.', 'parfume-reviews'); ?></p>
        </div>
        
        <div class="form-field term-application-tips-wrap">
            <label for="aroma_application_tips"><?php _e('Application Tips', 'parfume-reviews'); ?></label>
            <textarea name="aroma_application_tips" id="aroma_application_tips" rows="3" cols="40"></textarea>
            <p class="description"><?php _e('Tips for proper application of this aroma type.', 'parfume-reviews'); ?></p>
        </div>
        
        <div class="form-field term-season-preference-wrap">
            <label for="aroma_season_preference"><?php _e('Season Preference', 'parfume-reviews'); ?></label>
            <select name="aroma_season_preference" id="aroma_season_preference">
                <option value=""><?php _e('All seasons', 'parfume-reviews'); ?></option>
                <option value="spring"><?php _e('Spring', 'parfume-reviews'); ?></option>
                <option value="summer"><?php _e('Summer', 'parfume-reviews'); ?></option>
                <option value="autumn"><?php _e('Autumn', 'parfume-reviews'); ?></option>
                <option value="winter"><?php _e('Winter', 'parfume-reviews'); ?></option>
                <option value="warm"><?php _e('Warm seasons', 'parfume-reviews'); ?></option>
                <option value="cold"><?php _e('Cold seasons', 'parfume-reviews'); ?></option>
            </select>
            <p class="description"><?php _e('Best season for this aroma type.', 'parfume-reviews'); ?></p>
        </div>
        <?php
    }
    
    /**
     * Add custom fields to edit form
     */
    public function edit_custom_fields($term, $taxonomy) {
        $concentration_min = get_term_meta($term->term_id, 'aroma_concentration_min', true);
        $concentration_max = get_term_meta($term->term_id, 'aroma_concentration_max', true);
        $longevity_hours = get_term_meta($term->term_id, 'aroma_longevity_hours', true);
        $sillage = get_term_meta($term->term_id, 'aroma_sillage', true);
        $price_range = get_term_meta($term->term_id, 'aroma_price_range', true);
        $best_for = get_term_meta($term->term_id, 'aroma_best_for', true);
        $application_tips = get_term_meta($term->term_id, 'aroma_application_tips', true);
        $season_preference = get_term_meta($term->term_id, 'aroma_season_preference', true);
        ?>
        <tr class="form-field term-concentration-wrap">
            <th scope="row"><label><?php _e('Concentration Range', 'parfume-reviews'); ?></label></th>
            <td>
                <div style="display: flex; gap: 10px; align-items: center;">
                    <input type="number" name="aroma_concentration_min" value="<?php echo esc_attr($concentration_min); ?>" placeholder="Min %" min="0" max="100" step="0.1" style="width: 80px;" />
                    <span>-</span>
                    <input type="number" name="aroma_concentration_max" value="<?php echo esc_attr($concentration_max); ?>" placeholder="Max %" min="0" max="100" step="0.1" style="width: 80px;" />
                    <span>%</span>
                </div>
                <p class="description"><?php _e('Typical concentration range of aromatic oils.', 'parfume-reviews'); ?></p>
            </td>
        </tr>
        
        <tr class="form-field term-longevity-wrap">
            <th scope="row"><label for="aroma_longevity_hours"><?php _e('Longevity', 'parfume-reviews'); ?></label></th>
            <td>
                <input type="text" name="aroma_longevity_hours" id="aroma_longevity_hours" value="<?php echo esc_attr($longevity_hours); ?>" placeholder="e.g. 4-6 hours" />
                <p class="description"><?php _e('How long this type typically lasts on skin.', 'parfume-reviews'); ?></p>
            </td>
        </tr>
        
        <tr class="form-field term-sillage-wrap">
            <th scope="row"><label for="aroma_sillage"><?php _e('Sillage', 'parfume-reviews'); ?></label></th>
            <td>
                <select name="aroma_sillage" id="aroma_sillage">
                    <option value=""><?php _e('Select sillage', 'parfume-reviews'); ?></option>
                    <option value="Minimal" <?php selected($sillage, 'Minimal'); ?>><?php _e('Minimal', 'parfume-reviews'); ?></option>
                    <option value="Light" <?php selected($sillage, 'Light'); ?>><?php _e('Light', 'parfume-reviews'); ?></option>
                    <option value="Moderate" <?php selected($sillage, 'Moderate'); ?>><?php _e('Moderate', 'parfume-reviews'); ?></option>
                    <option value="Strong" <?php selected($sillage, 'Strong'); ?>><?php _e('Strong', 'parfume-reviews'); ?></option>
                    <option value="Very Strong" <?php selected($sillage, 'Very Strong'); ?>><?php _e('Very Strong', 'parfume-reviews'); ?></option>
                </select>
                <p class="description"><?php _e('Typical projection strength of this aroma type.', 'parfume-reviews'); ?></p>
            </td>
        </tr>
        
        <tr class="form-field term-price-range-wrap">
            <th scope="row"><label for="aroma_price_range"><?php _e('Price Range', 'parfume-reviews'); ?></label></th>
            <td>
                <select name="aroma_price_range" id="aroma_price_range">
                    <option value=""><?php _e('Select price range', 'parfume-reviews'); ?></option>
                    <option value="Budget" <?php selected($price_range, 'Budget'); ?>><?php _e('Budget (под 50 лв.)', 'parfume-reviews'); ?></option>
                    <option value="Mid-range" <?php selected($price_range, 'Mid-range'); ?>><?php _e('Mid-range (50-150 лв.)', 'parfume-reviews'); ?></option>
                    <option value="High-end" <?php selected($price_range, 'High-end'); ?>><?php _e('High-end (150-300 лв.)', 'parfume-reviews'); ?></option>
                    <option value="Luxury" <?php selected($price_range, 'Luxury'); ?>><?php _e('Luxury (300-500 лв.)', 'parfume-reviews'); ?></option>
                    <option value="Ultra-luxury" <?php selected($price_range, 'Ultra-luxury'); ?>><?php _e('Ultra-luxury (над 500 лв.)', 'parfume-reviews'); ?></option>
                </select>
                <p class="description"><?php _e('Typical price range for this aroma type.', 'parfume-reviews'); ?></p>
            </td>
        </tr>
        
        <tr class="form-field term-best-for-wrap">
            <th scope="row"><label for="aroma_best_for"><?php _e('Best For', 'parfume-reviews'); ?></label></th>
            <td>
                <textarea name="aroma_best_for" id="aroma_best_for" rows="2" cols="50"><?php echo esc_textarea($best_for); ?></textarea>
                <p class="description"><?php _e('Best occasions and situations for this aroma type.', 'parfume-reviews'); ?></p>
            </td>
        </tr>
        
        <tr class="form-field term-application-tips-wrap">
            <th scope="row"><label for="aroma_application_tips"><?php _e('Application Tips', 'parfume-reviews'); ?></label></th>
            <td>
                <textarea name="aroma_application_tips" id="aroma_application_tips" rows="3" cols="50"><?php echo esc_textarea($application_tips); ?></textarea>
                <p class="description"><?php _e('Tips for proper application of this aroma type.', 'parfume-reviews'); ?></p>
            </td>
        </tr>
        
        <tr class="form-field term-season-preference-wrap">
            <th scope="row"><label for="aroma_season_preference"><?php _e('Season Preference', 'parfume-reviews'); ?></label></th>
            <td>
                <select name="aroma_season_preference" id="aroma_season_preference">
                    <option value=""><?php _e('All seasons', 'parfume-reviews'); ?></option>
                    <option value="spring" <?php selected($season_preference, 'spring'); ?>><?php _e('Spring', 'parfume-reviews'); ?></option>
                    <option value="summer" <?php selected($season_preference, 'summer'); ?>><?php _e('Summer', 'parfume-reviews'); ?></option>
                    <option value="autumn" <?php selected($season_preference, 'autumn'); ?>><?php _e('Autumn', 'parfume-reviews'); ?></option>
                    <option value="winter" <?php selected($season_preference, 'winter'); ?>><?php _e('Winter', 'parfume-reviews'); ?></option>
                    <option value="warm" <?php selected($season_preference, 'warm'); ?>><?php _e('Warm seasons', 'parfume-reviews'); ?></option>
                    <option value="cold" <?php selected($season_preference, 'cold'); ?>><?php _e('Cold seasons', 'parfume-reviews'); ?></option>
                </select>
                <p class="description"><?php _e('Best season for this aroma type.', 'parfume-reviews'); ?></p>
            </td>
        </tr>
        <?php
    }
    
    /**
     * Save custom fields
     */
    public function save_custom_fields($term_id, $tt_id = '') {
        $fields = array(
            'aroma_concentration_min' => 'floatval',
            'aroma_concentration_max' => 'floatval',
            'aroma_longevity_hours' => 'sanitize_text_field',
            'aroma_sillage' => 'sanitize_text_field',
            'aroma_price_range' => 'sanitize_text_field',
            'aroma_best_for' => 'sanitize_textarea_field',
            'aroma_application_tips' => 'sanitize_textarea_field',
            'aroma_season_preference' => 'sanitize_text_field'
        );
        
        foreach ($fields as $field => $sanitize_function) {
            if (isset($_POST[$field])) {
                $value = call_user_func($sanitize_function, $_POST[$field]);
                update_term_meta($term_id, $field, $value);
            }
        }
    }
    
    /**
     * Add custom columns to admin table
     */
    public function custom_columns($columns) {
        $new_columns = array();
        
        foreach ($columns as $key => $value) {
            $new_columns[$key] = $value;
            
            if ($key === 'name') {
                $new_columns['concentration'] = __('Concentration', 'parfume-reviews');
                $new_columns['longevity'] = __('Longevity', 'parfume-reviews');
                $new_columns['sillage'] = __('Sillage', 'parfume-reviews');
                $new_columns['price_range'] = __('Price Range', 'parfume-reviews');
                $new_columns['perfume_count'] = __('Perfumes', 'parfume-reviews');
            }
        }
        
        return $new_columns;
    }
    
    /**
     * Display custom column content
     */
    public function custom_column_content($content, $column_name, $term_id) {
        switch ($column_name) {
            case 'concentration':
                $min = get_term_meta($term_id, 'aroma_concentration_min', true);
                $max = get_term_meta($term_id, 'aroma_concentration_max', true);
                
                if ($min && $max) {
                    $content = $min . '%-' . $max . '%';
                } elseif ($min) {
                    $content = $min . '%+';
                } else {
                    $content = '—';
                }
                break;
                
            case 'longevity':
                $longevity = get_term_meta($term_id, 'aroma_longevity_hours', true);
                $content = $longevity ? esc_html($longevity) : '—';
                break;
                
            case 'sillage':
                $sillage = get_term_meta($term_id, 'aroma_sillage', true);
                if ($sillage) {
                    $sillage_colors = array(
                        'Minimal' => '#6b7280',
                        'Light' => '#10b981',
                        'Moderate' => '#f59e0b',
                        'Strong' => '#ef4444',
                        'Very Strong' => '#7c2d12'
                    );
                    $color = isset($sillage_colors[$sillage]) ? $sillage_colors[$sillage] : '#333';
                    $content = '<span style="color: ' . $color . '; font-weight: bold;">' . esc_html($sillage) . '</span>';
                } else {
                    $content = '—';
                }
                break;
                
            case 'price_range':
                $price_range = get_term_meta($term_id, 'aroma_price_range', true);
                if ($price_range) {
                    $range_colors = array(
                        'Budget' => '#10b981',
                        'Mid-range' => '#f59e0b',
                        'High-end' => '#ef4444',
                        'Luxury' => '#8b5cf6',
                        'Ultra-luxury' => '#1f2937'
                    );
                    $color = isset($range_colors[$price_range]) ? $range_colors[$price_range] : '#333';
                    $content = '<span style="color: ' . $color . '; font-weight: bold;">' . esc_html($price_range) . '</span>';
                } else {
                    $content = '—';
                }
                break;
                
            case 'perfume_count':
                $term = get_term($term_id);
                $count = $term->count;
                $content = '<a href="' . admin_url('edit.php?post_type=parfume&aroma_type=' . $term->slug) . '">';
                $content .= sprintf(_n('%d perfume', '%d perfumes', $count, 'parfume-reviews'), $count);
                $content .= '</a>';
                break;
        }
        
        return $content;
    }
    
    /**
     * AJAX handler to get aroma type info
     */
    public function ajax_get_aroma_type_info() {
        if (!wp_verify_nonce($_POST['nonce'], 'parfume_reviews_nonce')) {
            wp_die('Security check failed');
        }
        
        $aroma_type_id = intval($_POST['aroma_type_id']);
        $term = get_term($aroma_type_id, 'aroma_type');
        
        if (is_wp_error($term)) {
            wp_send_json_error('Invalid aroma type');
        }
        
        $response = array(
            'id' => $term->term_id,
            'name' => $term->name,
            'description' => $term->description,
            'concentration_min' => get_term_meta($term->term_id, 'aroma_concentration_min', true),
            'concentration_max' => get_term_meta($term->term_id, 'aroma_concentration_max', true),
            'longevity_hours' => get_term_meta($term->term_id, 'aroma_longevity_hours', true),
            'sillage' => get_term_meta($term->term_id, 'aroma_sillage', true),
            'price_range' => get_term_meta($term->term_id, 'aroma_price_range', true),
            'best_for' => get_term_meta($term->term_id, 'aroma_best_for', true),
            'application_tips' => get_term_meta($term->term_id, 'aroma_application_tips', true),
            'season_preference' => get_term_meta($term->term_id, 'aroma_season_preference', true),
            'perfume_count' => $term->count,
            'url' => get_term_link($term)
        );
        
        wp_send_json_success($response);
    }
    
    /**
     * Get aroma type comparison data
     */
    public function get_aroma_type_comparison($term_ids) {
        $comparison = array();
        
        foreach ($term_ids as $term_id) {
            $term = get_term($term_id, 'aroma_type');
            if (is_wp_error($term)) {
                continue;
            }
            
            $comparison[] = array(
                'name' => $term->name,
                'concentration_min' => get_term_meta($term->term_id, 'aroma_concentration_min', true),
                'concentration_max' => get_term_meta($term->term_id, 'aroma_concentration_max', true),
                'longevity_hours' => get_term_meta($term->term_id, 'aroma_longevity_hours', true),
                'sillage' => get_term_meta($term->term_id, 'aroma_sillage', true),
                'price_range' => get_term_meta($term->term_id, 'aroma_price_range', true),
                'best_for' => get_term_meta($term->term_id, 'aroma_best_for', true)
            );
        }
        
        return $comparison;
    }
    
    /**
     * Get rewrite slug
     */
    private function get_rewrite_slug() {
        $parfume_slug = parfume_get_setting('parfume_slug', 'parfiumi');
        $aroma_type_slug = parfume_get_setting('aroma_type_slug', 'tipove-aromati');
        
        return $parfume_slug . '/' . $aroma_type_slug;
    }
}