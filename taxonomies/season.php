<?php
/**
 * Season Taxonomy
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
 * Season Taxonomy Class
 */
class Season extends Taxonomy_Base {
    
    /**
     * Taxonomy key
     */
    protected $taxonomy = 'season';
    
    /**
     * Post types
     */
    protected $post_types = array('parfume');
    
    /**
     * Initialize the taxonomy
     */
    public function init() {
        parent::init();
        
        // Additional hooks for seasons
        add_action('season_add_form_fields', array($this, 'add_custom_fields'));
        add_action('season_edit_form_fields', array($this, 'edit_custom_fields'), 10, 2);
        add_action('created_season', array($this, 'save_custom_fields'), 10, 2);
        add_action('edited_season', array($this, 'save_custom_fields'), 10, 2);
        
        // Custom columns
        add_filter('manage_edit-season_columns', array($this, 'custom_columns'));
        add_filter('manage_season_custom_column', array($this, 'custom_column_content'), 10, 3);
        
        // Frontend modifications
        add_filter('body_class', array($this, 'add_season_body_classes'));
        add_action('wp_head', array($this, 'add_season_schema'));
        
        // Weather-based recommendations
        add_action('wp_ajax_get_season_recommendations', array($this, 'ajax_get_season_recommendations'));
        add_action('wp_ajax_nopriv_get_season_recommendations', array($this, 'ajax_get_season_recommendations'));
    }
    
    /**
     * Get taxonomy configuration
     */
    protected function get_config() {
        return array(
            'labels' => array(
                'name' => __('Seasons', 'parfume-reviews'),
                'singular_name' => __('Season', 'parfume-reviews'),
                'search_items' => __('Search Seasons', 'parfume-reviews'),
                'all_items' => __('All Seasons', 'parfume-reviews'),
                'edit_item' => __('Edit Season', 'parfume-reviews'),
                'update_item' => __('Update Season', 'parfume-reviews'),
                'add_new_item' => __('Add New Season', 'parfume-reviews'),
                'new_item_name' => __('New Season Name', 'parfume-reviews'),
                'menu_name' => __('Seasons', 'parfume-reviews'),
                'view_item' => __('View Season', 'parfume-reviews'),
                'popular_items' => __('Popular Seasons', 'parfume-reviews'),
                'separate_items_with_commas' => __('Separate seasons with commas', 'parfume-reviews'),
                'add_or_remove_items' => __('Add or remove seasons', 'parfume-reviews'),
                'choose_from_most_used' => __('Choose from the most used seasons', 'parfume-reviews'),
                'not_found' => __('No seasons found.', 'parfume-reviews'),
            ),
            'args' => array(
                'hierarchical' => false,
                'show_ui' => true,
                'show_admin_column' => true,
                'query_var' => true,
                'rewrite' => array(
                    'slug' => $this->get_rewrite_slug(),
                    'with_front' => false,
                    'hierarchical' => false
                ),
                'show_in_rest' => true,
                'public' => true,
                'publicly_queryable' => true,
                'show_in_nav_menus' => true,
                'show_tagcloud' => true,
                'meta_box_cb' => 'post_tags_meta_box',
                'capabilities' => array(
                    'manage_terms' => 'manage_parfume_seasons',
                    'edit_terms' => 'edit_parfume_seasons',
                    'delete_terms' => 'delete_parfume_seasons',
                    'assign_terms' => 'assign_parfume_seasons',
                ),
            )
        );
    }
    
    /**
     * Get default terms
     */
    protected function get_default_terms() {
        return array(
            'Пролет' => array(
                'description' => 'Свежи и лесни парфюми, идеални за топлите пролетни дни с флорални нотки.',
                'meta' => array(
                    'temperature_range' => '15-25°C',
                    'humidity' => 'Medium',
                    'color_scheme' => '#10b981', // Green
                    'icon' => '🌸',
                    'months' => 'март, април, май',
                    'characteristics' => 'Fresh, floral, light, optimistic',
                    'recommended_notes' => 'Cherry blossom, Green leaves, Fresh grass, Lily of the valley',
                    'avoid_notes' => 'Heavy woods, Strong spices, Intense oud',
                    'application_advice' => 'Light application, focus on pulse points',
                    'occasion' => 'Casual day wear, picnics, walks in nature'
                )
            ),
            'Лято' => array(
                'description' => 'Освежаващи и лещки парфюми с цитрусови и водни нотки за горещите летни дни.',
                'meta' => array(
                    'temperature_range' => '25-35°C',
                    'humidity' => 'High',
                    'color_scheme' => '#fbbf24', // Yellow
                    'icon' => '☀️',
                    'months' => 'юни, юли, август',
                    'characteristics' => 'Aquatic, citrus, refreshing, energizing',
                    'recommended_notes' => 'Bergamot, Lemon, Sea breeze, Cucumber, Mint',
                    'avoid_notes' => 'Heavy vanilla, Amber, Dense florals',
                    'application_advice' => 'Frequent reapplication, avoid over-spraying',
                    'occasion' => 'Beach days, outdoor activities, vacation'
                )
            ),
            'Есен' => array(
                'description' => 'Топли и уютни парфюми с подправки и дървесни нотки за есенната прохлада.',
                'meta' => array(
                    'temperature_range' => '10-20°C',
                    'humidity' => 'Medium',
                    'color_scheme' => '#f59e0b', // Orange
                    'icon' => '🍂',
                    'months' => 'септември, октомври, ноември',
                    'characteristics' => 'Warm, spicy, woody, cozy',
                    'recommended_notes' => 'Cinnamon, Apple, Cedar, Amber, Vanilla',
                    'avoid_notes' => 'Very light citrus, Intense aquatics',
                    'application_advice' => 'Moderate application, layer for depth',
                    'occasion' => 'Office wear, cozy evenings, transitional weather'
                )
            ),
            'Зима' => array(
                'description' => 'Богати и интензивни парфюми с oriental и дълбоки нотки за студените зимни дни.',
                'meta' => array(
                    'temperature_range' => '-5-15°C',
                    'humidity' => 'Low',
                    'color_scheme' => '#3b82f6', // Blue
                    'icon' => '❄️',
                    'months' => 'декември, януари, февруари',
                    'characteristics' => 'Rich, intense, warm, comforting',
                    'recommended_notes' => 'Oud, Vanilla, Sandalwood, Musk, Incense',
                    'avoid_notes' => 'Light florals, Citrus-only compositions',
                    'application_advice' => 'Generous application, focus on clothing and pulse points',
                    'occasion' => 'Evening wear, special occasions, indoor gatherings'
                )
            ),
            'Цялогодишни' => array(
                'description' => 'Универсални парфюми подходящи за всички сезони с балансирани композиции.',
                'meta' => array(
                    'temperature_range' => '5-30°C',
                    'humidity' => 'Variable',
                    'color_scheme' => '#6b7280', // Gray
                    'icon' => '🌍',
                    'months' => 'всички месеци',
                    'characteristics' => 'Balanced, versatile, adaptable, timeless',
                    'recommended_notes' => 'White musk, Ambergris, Soft woods, Clean florals',
                    'avoid_notes' => 'Extremely seasonal-specific notes',
                    'application_advice' => 'Adjust application intensity based on weather',
                    'occasion' => 'Office, daily wear, versatile occasions'
                )
            )
        );
    }
    
    /**
     * Add custom fields to add form
     */
    public function add_custom_fields($taxonomy) {
        ?>
        <div class="form-field term-temperature-range-wrap">
            <label for="season_temperature_range"><?php _e('Temperature Range', 'parfume-reviews'); ?></label>
            <input type="text" name="season_temperature_range" id="season_temperature_range" value="" placeholder="e.g. 15-25°C" />
            <p class="description"><?php _e('Typical temperature range for this season.', 'parfume-reviews'); ?></p>
        </div>
        
        <div class="form-field term-humidity-wrap">
            <label for="season_humidity"><?php _e('Humidity Level', 'parfume-reviews'); ?></label>
            <select name="season_humidity" id="season_humidity">
                <option value=""><?php _e('Select humidity', 'parfume-reviews'); ?></option>
                <option value="Low"><?php _e('Low', 'parfume-reviews'); ?></option>
                <option value="Medium"><?php _e('Medium', 'parfume-reviews'); ?></option>
                <option value="High"><?php _e('High', 'parfume-reviews'); ?></option>
                <option value="Variable"><?php _e('Variable', 'parfume-reviews'); ?></option>
            </select>
            <p class="description"><?php _e('Typical humidity level for this season.', 'parfume-reviews'); ?></p>
        </div>
        
        <div class="form-field term-color-scheme-wrap">
            <label for="season_color_scheme"><?php _e('Color Scheme', 'parfume-reviews'); ?></label>
            <input type="color" name="season_color_scheme" id="season_color_scheme" value="#333333" />
            <p class="description"><?php _e('Representative color for this season.', 'parfume-reviews'); ?></p>
        </div>
        
        <div class="form-field term-icon-wrap">
            <label for="season_icon"><?php _e('Season Icon', 'parfume-reviews'); ?></label>
            <select name="season_icon" id="season_icon">
                <option value=""><?php _e('Select an icon', 'parfume-reviews'); ?></option>
                <option value="🌸">🌸 Spring Blossom</option>
                <option value="☀️">☀️ Summer Sun</option>
                <option value="🍂">🍂 Autumn Leaves</option>
                <option value="❄️">❄️ Winter Snow</option>
                <option value="🌍">🌍 Year-round Globe</option>
                <option value="🌿">🌿 Fresh Green</option>
                <option value="🌺">🌺 Tropical Flower</option>
                <option value="🍃">🍃 Wind Leaves</option>
            </select>
            <p class="description"><?php _e('Visual icon to represent this season.', 'parfume-reviews'); ?></p>
        </div>
        
        <div class="form-field term-months-wrap">
            <label for="season_months"><?php _e('Months', 'parfume-reviews'); ?></label>
            <input type="text" name="season_months" id="season_months" value="" placeholder="e.g. март, април, май" />
            <p class="description"><?php _e('Months that belong to this season.', 'parfume-reviews'); ?></p>
        </div>
        
        <div class="form-field term-characteristics-wrap">
            <label for="season_characteristics"><?php _e('Characteristics', 'parfume-reviews'); ?></label>
            <textarea name="season_characteristics" id="season_characteristics" rows="2" cols="40"></textarea>
            <p class="description"><?php _e('Key characteristics of perfumes for this season.', 'parfume-reviews'); ?></p>
        </div>
        
        <div class="form-field term-recommended-notes-wrap">
            <label for="season_recommended_notes"><?php _e('Recommended Notes', 'parfume-reviews'); ?></label>
            <textarea name="season_recommended_notes" id="season_recommended_notes" rows="3" cols="40"></textarea>
            <p class="description"><?php _e('Fragrance notes that work well in this season.', 'parfume-reviews'); ?></p>
        </div>
        
        <div class="form-field term-avoid-notes-wrap">
            <label for="season_avoid_notes"><?php _e('Notes to Avoid', 'parfume-reviews'); ?></label>
            <textarea name="season_avoid_notes" id="season_avoid_notes" rows="2" cols="40"></textarea>
            <p class="description"><?php _e('Notes that might not work well in this season.', 'parfume-reviews'); ?></p>
        </div>
        
        <div class="form-field term-application-advice-wrap">
            <label for="season_application_advice"><?php _e('Application Advice', 'parfume-reviews'); ?></label>
            <textarea name="season_application_advice" id="season_application_advice" rows="2" cols="40"></textarea>
            <p class="description"><?php _e('How to best apply perfumes in this season.', 'parfume-reviews'); ?></p>
        </div>
        
        <div class="form-field term-occasion-wrap">
            <label for="season_occasion"><?php _e('Best Occasions', 'parfume-reviews'); ?></label>
            <textarea name="season_occasion" id="season_occasion" rows="2" cols="40"></textarea>
            <p class="description"><?php _e('Typical occasions for this season\'s perfumes.', 'parfume-reviews'); ?></p>
        </div>
        <?php
    }
    
    /**
     * Add custom fields to edit form
     */
    public function edit_custom_fields($term, $taxonomy) {
        $temperature_range = get_term_meta($term->term_id, 'season_temperature_range', true);
        $humidity = get_term_meta($term->term_id, 'season_humidity', true);
        $color_scheme = get_term_meta($term->term_id, 'season_color_scheme', true);
        $icon = get_term_meta($term->term_id, 'season_icon', true);
        $months = get_term_meta($term->term_id, 'season_months', true);
        $characteristics = get_term_meta($term->term_id, 'season_characteristics', true);
        $recommended_notes = get_term_meta($term->term_id, 'season_recommended_notes', true);
        $avoid_notes = get_term_meta($term->term_id, 'season_avoid_notes', true);
        $application_advice = get_term_meta($term->term_id, 'season_application_advice', true);
        $occasion = get_term_meta($term->term_id, 'season_occasion', true);
        ?>
        <tr class="form-field term-temperature-range-wrap">
            <th scope="row"><label for="season_temperature_range"><?php _e('Temperature Range', 'parfume-reviews'); ?></label></th>
            <td>
                <input type="text" name="season_temperature_range" id="season_temperature_range" value="<?php echo esc_attr($temperature_range); ?>" placeholder="e.g. 15-25°C" />
                <p class="description"><?php _e('Typical temperature range for this season.', 'parfume-reviews'); ?></p>
            </td>
        </tr>
        
        <tr class="form-field term-humidity-wrap">
            <th scope="row"><label for="season_humidity"><?php _e('Humidity Level', 'parfume-reviews'); ?></label></th>
            <td>
                <select name="season_humidity" id="season_humidity">
                    <option value=""><?php _e('Select humidity', 'parfume-reviews'); ?></option>
                    <option value="Low" <?php selected($humidity, 'Low'); ?>><?php _e('Low', 'parfume-reviews'); ?></option>
                    <option value="Medium" <?php selected($humidity, 'Medium'); ?>><?php _e('Medium', 'parfume-reviews'); ?></option>
                    <option value="High" <?php selected($humidity, 'High'); ?>><?php _e('High', 'parfume-reviews'); ?></option>
                    <option value="Variable" <?php selected($humidity, 'Variable'); ?>><?php _e('Variable', 'parfume-reviews'); ?></option>
                </select>
                <p class="description"><?php _e('Typical humidity level for this season.', 'parfume-reviews'); ?></p>
            </td>
        </tr>
        
        <tr class="form-field term-color-scheme-wrap">
            <th scope="row"><label for="season_color_scheme"><?php _e('Color Scheme', 'parfume-reviews'); ?></label></th>
            <td>
                <input type="color" name="season_color_scheme" id="season_color_scheme" value="<?php echo esc_attr($color_scheme ?: '#333333'); ?>" />
                <p class="description"><?php _e('Representative color for this season.', 'parfume-reviews'); ?></p>
            </td>
        </tr>
        
        <tr class="form-field term-icon-wrap">
            <th scope="row"><label for="season_icon"><?php _e('Season Icon', 'parfume-reviews'); ?></label></th>
            <td>
                <select name="season_icon" id="season_icon">
                    <option value=""><?php _e('Select an icon', 'parfume-reviews'); ?></option>
                    <option value="🌸" <?php selected($icon, '🌸'); ?>>🌸 Spring Blossom</option>
                    <option value="☀️" <?php selected($icon, '☀️'); ?>>☀️ Summer Sun</option>
                    <option value="🍂" <?php selected($icon, '🍂'); ?>>🍂 Autumn Leaves</option>
                    <option value="❄️" <?php selected($icon, '❄️'); ?>>❄️ Winter Snow</option>
                    <option value="🌍" <?php selected($icon, '🌍'); ?>>🌍 Year-round Globe</option>
                    <option value="🌿" <?php selected($icon, '🌿'); ?>>🌿 Fresh Green</option>
                    <option value="🌺" <?php selected($icon, '🌺'); ?>>🌺 Tropical Flower</option>
                    <option value="🍃" <?php selected($icon, '🍃'); ?>>🍃 Wind Leaves</option>
                </select>
                <p class="description"><?php _e('Visual icon to represent this season.', 'parfume-reviews'); ?></p>
            </td>
        </tr>
        
        <tr class="form-field term-months-wrap">
            <th scope="row"><label for="season_months"><?php _e('Months', 'parfume-reviews'); ?></label></th>
            <td>
                <input type="text" name="season_months" id="season_months" value="<?php echo esc_attr($months); ?>" placeholder="e.g. март, април, май" />
                <p class="description"><?php _e('Months that belong to this season.', 'parfume-reviews'); ?></p>
            </td>
        </tr>
        
        <tr class="form-field term-characteristics-wrap">
            <th scope="row"><label for="season_characteristics"><?php _e('Characteristics', 'parfume-reviews'); ?></label></th>
            <td>
                <textarea name="season_characteristics" id="season_characteristics" rows="2" cols="50"><?php echo esc_textarea($characteristics); ?></textarea>
                <p class="description"><?php _e('Key characteristics of perfumes for this season.', 'parfume-reviews'); ?></p>
            </td>
        </tr>
        
        <tr class="form-field term-recommended-notes-wrap">
            <th scope="row"><label for="season_recommended_notes"><?php _e('Recommended Notes', 'parfume-reviews'); ?></label></th>
            <td>
                <textarea name="season_recommended_notes" id="season_recommended_notes" rows="3" cols="50"><?php echo esc_textarea($recommended_notes); ?></textarea>
                <p class="description"><?php _e('Fragrance notes that work well in this season.', 'parfume-reviews'); ?></p>
            </td>
        </tr>
        
        <tr class="form-field term-avoid-notes-wrap">
            <th scope="row"><label for="season_avoid_notes"><?php _e('Notes to Avoid', 'parfume-reviews'); ?></label></th>
            <td>
                <textarea name="season_avoid_notes" id="season_avoid_notes" rows="2" cols="50"><?php echo esc_textarea($avoid_notes); ?></textarea>
                <p class="description"><?php _e('Notes that might not work well in this season.', 'parfume-reviews'); ?></p>
            </td>
        </tr>
        
        <tr class="form-field term-application-advice-wrap">
            <th scope="row"><label for="season_application_advice"><?php _e('Application Advice', 'parfume-reviews'); ?></label></th>
            <td>
                <textarea name="season_application_advice" id="season_application_advice" rows="2" cols="50"><?php echo esc_textarea($application_advice); ?></textarea>
                <p class="description"><?php _e('How to best apply perfumes in this season.', 'parfume-reviews'); ?></p>
            </td>
        </tr>
        
        <tr class="form-field term-occasion-wrap">
            <th scope="row"><label for="season_occasion"><?php _e('Best Occasions', 'parfume-reviews'); ?></label></th>
            <td>
                <textarea name="season_occasion" id="season_occasion" rows="2" cols="50"><?php echo esc_textarea($occasion); ?></textarea>
                <p class="description"><?php _e('Typical occasions for this season\'s perfumes.', 'parfume-reviews'); ?></p>
            </td>
        </tr>
        <?php
    }
    
    /**
     * Save custom fields
     */
    public function save_custom_fields($term_id, $tt_id = '') {
        $fields = array(
            'season_temperature_range' => 'sanitize_text_field',
            'season_humidity' => 'sanitize_text_field',
            'season_color_scheme' => 'sanitize_hex_color',
            'season_icon' => 'sanitize_text_field',
            'season_months' => 'sanitize_text_field',
            'season_characteristics' => 'sanitize_textarea_field',
            'season_recommended_notes' => 'sanitize_textarea_field',
            'season_avoid_notes' => 'sanitize_textarea_field',
            'season_application_advice' => 'sanitize_textarea_field',
            'season_occasion' => 'sanitize_textarea_field'
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
                $new_columns['season_icon'] = __('Icon', 'parfume-reviews');
                $new_columns['temperature'] = __('Temperature', 'parfume-reviews');
                $new_columns['months'] = __('Months', 'parfume-reviews');
                $new_columns['characteristics'] = __('Key Features', 'parfume-reviews');
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
            case 'season_icon':
                $icon = get_term_meta($term_id, 'season_icon', true);
                $color = get_term_meta($term_id, 'season_color_scheme', true);
                
                if ($icon) {
                    $content = '<span style="font-size: 24px;">' . $icon . '</span>';
                } else {
                    $content = '—';
                }
                break;
                
            case 'temperature':
                $temp_range = get_term_meta($term_id, 'season_temperature_range', true);
                $humidity = get_term_meta($term_id, 'season_humidity', true);
                
                if ($temp_range) {
                    $content = $temp_range;
                    if ($humidity) {
                        $content .= '<br><small>' . $humidity . ' humidity</small>';
                    }
                } else {
                    $content = '—';
                }
                break;
                
            case 'months':
                $months = get_term_meta($term_id, 'season_months', true);
                $content = $months ? esc_html($months) : '—';
                break;
                
            case 'characteristics':
                $characteristics = get_term_meta($term_id, 'season_characteristics', true);
                if ($characteristics) {
                    $content = '<span title="' . esc_attr($characteristics) . '">' . 
                              wp_trim_words(esc_html($characteristics), 4) . '</span>';
                } else {
                    $content = '—';
                }
                break;
                
            case 'perfume_count':
                $term = get_term($term_id);
                $count = $term->count;
                $content = '<a href="' . admin_url('edit.php?post_type=parfume&season=' . $term->slug) . '">';
                $content .= sprintf(_n('%d perfume', '%d perfumes', $count, 'parfume-reviews'), $count);
                $content .= '</a>';
                break;
        }
        
        return $content;
    }
    
    /**
     * Add season-specific body classes
     */
    public function add_season_body_classes($classes) {
        if (is_tax('season')) {
            $term = get_queried_object();
            $classes[] = 'parfume-season-archive';
            $classes[] = 'parfume-season-' . $term->slug;
            
            // Add current season class if applicable
            $current_season = $this->get_current_season();
            if ($current_season && $current_season === $term->slug) {
                $classes[] = 'current-season';
            }
        }
        
        return $classes;
    }
    
    /**
     * Add season schema markup
     */
    public function add_season_schema() {
        if (!is_tax('season')) {
            return;
        }
        
        $term = get_queried_object();
        $temperature_range = get_term_meta($term->term_id, 'season_temperature_range', true);
        $months = get_term_meta($term->term_id, 'season_months', true);
        $characteristics = get_term_meta($term->term_id, 'season_characteristics', true);
        
        $schema = array(
            '@context' => 'https://schema.org',
            '@type' => 'CollectionPage',
            'name' => sprintf(__('%s Perfumes', 'parfume-reviews'), $term->name),
            'description' => $term->description ?: $characteristics,
            'url' => get_term_link($term),
            'mainEntity' => array(
                '@type' => 'ItemList',
                'name' => sprintf(__('Perfumes for %s', 'parfume-reviews'), $term->name),
                'numberOfItems' => $term->count
            )
        );
        
        if ($months) {
            $schema['temporalCoverage'] = $months;
        }
        
        echo '<script type="application/ld+json">' . wp_json_encode($schema, JSON_UNESCAPED_UNICODE) . '</script>' . "\n";
    }
    
    /**
     * AJAX handler for season recommendations
     */
    public function ajax_get_season_recommendations() {
        if (!wp_verify_nonce($_POST['nonce'], 'parfume_reviews_nonce')) {
            wp_die('Security check failed');
        }
        
        $current_season = $this->get_current_season();
        $temperature = isset($_POST['temperature']) ? floatval($_POST['temperature']) : null;
        $humidity = isset($_POST['humidity']) ? sanitize_text_field($_POST['humidity']) : null;
        
        $recommendations = $this->get_seasonal_recommendations($current_season, $temperature, $humidity);
        
        wp_send_json_success($recommendations);
    }
    
    /**
     * Get current season based on date
     */
    public function get_current_season() {
        $month = date('n'); // 1-12
        
        if ($month >= 3 && $month <= 5) {
            return 'prolet'; // Spring
        } elseif ($month >= 6 && $month <= 8) {
            return 'lyato'; // Summer
        } elseif ($month >= 9 && $month <= 11) {
            return 'esen'; // Autumn
        } else {
            return 'zima'; // Winter
        }
    }
    
    /**
     * Get seasonal recommendations
     */
    public function get_seasonal_recommendations($season_slug = null, $temperature = null, $humidity = null) {
        if (!$season_slug) {
            $season_slug = $this->get_current_season();
        }
        
        $season_term = get_term_by('slug', $season_slug, 'season');
        if (!$season_term || is_wp_error($season_term)) {
            return array();
        }
        
        $recommendations = array(
            'season' => array(
                'name' => $season_term->name,
                'icon' => get_term_meta($season_term->term_id, 'season_icon', true),
                'characteristics' => get_term_meta($season_term->term_id, 'season_characteristics', true),
                'recommended_notes' => get_term_meta($season_term->term_id, 'season_recommended_notes', true),
                'avoid_notes' => get_term_meta($season_term->term_id, 'season_avoid_notes', true),
                'application_advice' => get_term_meta($season_term->term_id, 'season_application_advice', true)
            ),
            'perfumes' => array(),
            'tips' => array()
        );
        
        // Get recommended perfumes for this season
        $perfumes = get_posts(array(
            'post_type' => 'parfume',
            'posts_per_page' => 8,
            'meta_key' => '_parfume_rating',
            'orderby' => 'meta_value_num',
            'order' => 'DESC',
            'tax_query' => array(
                array(
                    'taxonomy' => 'season',
                    'field' => 'term_id',
                    'terms' => $season_term->term_id
                )
            )
        ));
        
        foreach ($perfumes as $perfume) {
            $rating = get_post_meta($perfume->ID, '_parfume_rating', true);
            $brands = wp_get_post_terms($perfume->ID, 'brands', array('fields' => 'names'));
            
            $recommendations['perfumes'][] = array(
                'id' => $perfume->ID,
                'title' => $perfume->post_title,
                'brand' => !empty($brands) && !is_wp_error($brands) ? $brands[0] : '',
                'rating' => floatval($rating),
                'url' => get_permalink($perfume->ID),
                'thumbnail' => get_the_post_thumbnail_url($perfume->ID, 'medium')
            );
        }
        
        // Add weather-specific tips
        if ($temperature !== null) {
            if ($temperature > 30) {
                $recommendations['tips'][] = __('Very hot weather: Choose light, citrusy fragrances and apply sparingly.', 'parfume-reviews');
            } elseif ($temperature < 0) {
                $recommendations['tips'][] = __('Freezing weather: Rich, warm fragrances will project better and last longer.', 'parfume-reviews');
            }
        }
        
        if ($humidity === 'High') {
            $recommendations['tips'][] = __('High humidity: Fragrances will project more, so apply less than usual.', 'parfume-reviews');
        } elseif ($humidity === 'Low') {
            $recommendations['tips'][] = __('Low humidity: You may need to apply more fragrance as it won\'t project as much.', 'parfume-reviews');
        }
        
        return $recommendations;
    }
    
    /**
     * Get rewrite slug
     */
    private function get_rewrite_slug() {
        $parfume_slug = parfume_get_setting('parfume_slug', 'parfiumi');
        $season_slug = parfume_get_setting('season_slug', 'sezoni');
        
        return $parfume_slug . '/' . $season_slug;
    }
}