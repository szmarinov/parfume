<?php
/**
 * Gender/Categories Taxonomy
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
 * Gender/Categories Taxonomy Class
 */
class Gender extends Taxonomy_Base {
    
    /**
     * Taxonomy key
     */
    protected $taxonomy = 'gender';
    
    /**
     * Post types
     */
    protected $post_types = array('parfume');
    
    /**
     * Initialize the taxonomy
     */
    public function init() {
        parent::init();
        
        // Additional hooks for gender categories
        add_action('gender_add_form_fields', array($this, 'add_custom_fields'));
        add_action('gender_edit_form_fields', array($this, 'edit_custom_fields'), 10, 2);
        add_action('created_gender', array($this, 'save_custom_fields'), 10, 2);
        add_action('edited_gender', array($this, 'save_custom_fields'), 10, 2);
        
        // Custom columns
        add_filter('manage_edit-gender_columns', array($this, 'custom_columns'));
        add_filter('manage_gender_custom_column', array($this, 'custom_column_content'), 10, 3);
        
        // Frontend modifications
        add_filter('body_class', array($this, 'add_body_classes'));
        add_action('wp_head', array($this, 'add_category_schema'));
    }
    
    /**
     * Get taxonomy configuration
     */
    protected function get_config() {
        return array(
            'labels' => array(
                'name' => __('Categories', 'parfume-reviews'),
                'singular_name' => __('Category', 'parfume-reviews'),
                'search_items' => __('Search Categories', 'parfume-reviews'),
                'all_items' => __('All Categories', 'parfume-reviews'),
                'edit_item' => __('Edit Category', 'parfume-reviews'),
                'update_item' => __('Update Category', 'parfume-reviews'),
                'add_new_item' => __('Add New Category', 'parfume-reviews'),
                'new_item_name' => __('New Category Name', 'parfume-reviews'),
                'menu_name' => __('Categories', 'parfume-reviews'),
                'view_item' => __('View Category', 'parfume-reviews'),
                'popular_items' => __('Popular Categories', 'parfume-reviews'),
                'separate_items_with_commas' => __('Separate categories with commas', 'parfume-reviews'),
                'add_or_remove_items' => __('Add or remove categories', 'parfume-reviews'),
                'choose_from_most_used' => __('Choose from the most used categories', 'parfume-reviews'),
                'not_found' => __('No categories found.', 'parfume-reviews'),
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
                    'manage_terms' => 'manage_parfume_categories',
                    'edit_terms' => 'edit_parfume_categories',
                    'delete_terms' => 'delete_parfume_categories',
                    'assign_terms' => 'assign_parfume_categories',
                ),
            )
        );
    }
    
    /**
     * Get default terms
     */
    protected function get_default_terms() {
        return array(
            'Мъжки парфюми' => array(
                'description' => 'Парфюми създадени специално за мъже с по-силни и мускусни аромати.',
                'meta' => array(
                    'color_scheme' => '#1e3a8a', // Blue
                    'icon' => 'mars',
                    'target_audience' => 'men',
                    'characteristics' => 'Strong, woody, spicy, musky',
                    'popular_notes' => 'Bergamot, Cedar, Musk, Vetiver'
                )
            ),
            'Дамски парфюми' => array(
                'description' => 'Елегантни и женствени парфюми с флорални и сладки нотки.',
                'meta' => array(
                    'color_scheme' => '#be185d', // Pink
                    'icon' => 'venus',
                    'target_audience' => 'women',
                    'characteristics' => 'Floral, sweet, elegant, romantic',
                    'popular_notes' => 'Rose, Jasmine, Vanilla, Peach'
                )
            ),
            'Унисекс парфюми' => array(
                'description' => 'Парфюми подходящи както за мъже, така и за жени.',
                'meta' => array(
                    'color_scheme' => '#059669', // Green
                    'icon' => 'genderless',
                    'target_audience' => 'unisex',
                    'characteristics' => 'Balanced, fresh, versatile, clean',
                    'popular_notes' => 'Citrus, White Musk, Ambergris, Tea'
                )
            ),
            'Арабски парфюми' => array(
                'description' => 'Традиционни арабски парфюми с oud, амбър и екзотични подправки.',
                'meta' => array(
                    'color_scheme' => '#b45309', // Amber
                    'icon' => 'moon-stars',
                    'target_audience' => 'unisex',
                    'characteristics' => 'Rich, exotic, opulent, traditional',
                    'popular_notes' => 'Oud, Amber, Rose, Saffron'
                )
            ),
            'Луксозни парфюми' => array(
                'description' => 'Висококачествени парфюми от престижни марки с изискани аромати.',
                'meta' => array(
                    'color_scheme' => '#7c2d12', // Brown
                    'icon' => 'crown',
                    'target_audience' => 'luxury',
                    'characteristics' => 'Exclusive, sophisticated, premium, rare',
                    'popular_notes' => 'Rare Oud, Iris, Ambergris, Fine Woods'
                )
            ),
            'Нишови парфюми' => array(
                'description' => 'Артистични и уникални парфюми от независими нишови марки.',
                'meta' => array(
                    'color_scheme' => '#4c1d95', // Purple
                    'icon' => 'palette',
                    'target_audience' => 'niche',
                    'characteristics' => 'Artistic, unique, unconventional, bold',
                    'popular_notes' => 'Unusual florals, Synthetic molecules, Rare ingredients'
                )
            )
        );
    }
    
    /**
     * Add custom fields to add form
     */
    public function add_custom_fields($taxonomy) {
        ?>
        <div class="form-field term-color-scheme-wrap">
            <label for="gender_color_scheme"><?php _e('Color Scheme', 'parfume-reviews'); ?></label>
            <input type="color" name="gender_color_scheme" id="gender_color_scheme" value="#333333" />
            <p class="description"><?php _e('Primary color for this category in the UI.', 'parfume-reviews'); ?></p>
        </div>
        
        <div class="form-field term-icon-wrap">
            <label for="gender_icon"><?php _e('Icon', 'parfume-reviews'); ?></label>
            <select name="gender_icon" id="gender_icon">
                <option value=""><?php _e('Select an icon', 'parfume-reviews'); ?></option>
                <option value="mars">♂ Mars (Male)</option>
                <option value="venus">♀ Venus (Female)</option>
                <option value="genderless">⚲ Genderless (Unisex)</option>
                <option value="crown">♔ Crown (Luxury)</option>
                <option value="moon-stars">☪ Moon & Stars (Arabic)</option>
                <option value="palette">🎨 Palette (Niche)</option>
                <option value="heart">♥ Heart (Romantic)</option>
                <option value="star">★ Star (Premium)</option>
            </select>
            <p class="description"><?php _e('Icon to represent this category.', 'parfume-reviews'); ?></p>
        </div>
        
        <div class="form-field term-target-audience-wrap">
            <label for="gender_target_audience"><?php _e('Target Audience', 'parfume-reviews'); ?></label>
            <select name="gender_target_audience" id="gender_target_audience">
                <option value=""><?php _e('Select audience', 'parfume-reviews'); ?></option>
                <option value="men"><?php _e('Men', 'parfume-reviews'); ?></option>
                <option value="women"><?php _e('Women', 'parfume-reviews'); ?></option>
                <option value="unisex"><?php _e('Unisex', 'parfume-reviews'); ?></option>
                <option value="luxury"><?php _e('Luxury Market', 'parfume-reviews'); ?></option>
                <option value="niche"><?php _e('Niche Market', 'parfume-reviews'); ?></option>
                <option value="mainstream"><?php _e('Mainstream', 'parfume-reviews'); ?></option>
            </select>
            <p class="description"><?php _e('Primary target audience for this category.', 'parfume-reviews'); ?></p>
        </div>
        
        <div class="form-field term-characteristics-wrap">
            <label for="gender_characteristics"><?php _e('Characteristics', 'parfume-reviews'); ?></label>
            <textarea name="gender_characteristics" id="gender_characteristics" rows="3" cols="40"></textarea>
            <p class="description"><?php _e('Typical characteristics of perfumes in this category.', 'parfume-reviews'); ?></p>
        </div>
        
        <div class="form-field term-popular-notes-wrap">
            <label for="gender_popular_notes"><?php _e('Popular Notes', 'parfume-reviews'); ?></label>
            <textarea name="gender_popular_notes" id="gender_popular_notes" rows="2" cols="40"></textarea>
            <p class="description"><?php _e('Most common notes found in this category.', 'parfume-reviews'); ?></p>
        </div>
        
        <div class="form-field term-season-preference-wrap">
            <label for="gender_season_preference"><?php _e('Season Preference', 'parfume-reviews'); ?></label>
            <select name="gender_season_preference" id="gender_season_preference">
                <option value=""><?php _e('All seasons', 'parfume-reviews'); ?></option>
                <option value="spring"><?php _e('Spring', 'parfume-reviews'); ?></option>
                <option value="summer"><?php _e('Summer', 'parfume-reviews'); ?></option>
                <option value="autumn"><?php _e('Autumn', 'parfume-reviews'); ?></option>
                <option value="winter"><?php _e('Winter', 'parfume-reviews'); ?></option>
                <option value="warm"><?php _e('Warm seasons', 'parfume-reviews'); ?></option>
                <option value="cold"><?php _e('Cold seasons', 'parfume-reviews'); ?></option>
            </select>
            <p class="description"><?php _e('Best season for this category of perfumes.', 'parfume-reviews'); ?></p>
        </div>
        <?php
    }
    
    /**
     * Add custom fields to edit form
     */
    public function edit_custom_fields($term, $taxonomy) {
        $color_scheme = get_term_meta($term->term_id, 'gender_color_scheme', true);
        $icon = get_term_meta($term->term_id, 'gender_icon', true);
        $target_audience = get_term_meta($term->term_id, 'gender_target_audience', true);
        $characteristics = get_term_meta($term->term_id, 'gender_characteristics', true);
        $popular_notes = get_term_meta($term->term_id, 'gender_popular_notes', true);
        $season_preference = get_term_meta($term->term_id, 'gender_season_preference', true);
        ?>
        <tr class="form-field term-color-scheme-wrap">
            <th scope="row"><label for="gender_color_scheme"><?php _e('Color Scheme', 'parfume-reviews'); ?></label></th>
            <td>
                <input type="color" name="gender_color_scheme" id="gender_color_scheme" value="<?php echo esc_attr($color_scheme ?: '#333333'); ?>" />
                <p class="description"><?php _e('Primary color for this category in the UI.', 'parfume-reviews'); ?></p>
            </td>
        </tr>
        
        <tr class="form-field term-icon-wrap">
            <th scope="row"><label for="gender_icon"><?php _e('Icon', 'parfume-reviews'); ?></label></th>
            <td>
                <select name="gender_icon" id="gender_icon">
                    <option value=""><?php _e('Select an icon', 'parfume-reviews'); ?></option>
                    <option value="mars" <?php selected($icon, 'mars'); ?>>♂ Mars (Male)</option>
                    <option value="venus" <?php selected($icon, 'venus'); ?>>♀ Venus (Female)</option>
                    <option value="genderless" <?php selected($icon, 'genderless'); ?>>⚲ Genderless (Unisex)</option>
                    <option value="crown" <?php selected($icon, 'crown'); ?>>♔ Crown (Luxury)</option>
                    <option value="moon-stars" <?php selected($icon, 'moon-stars'); ?>>☪ Moon & Stars (Arabic)</option>
                    <option value="palette" <?php selected($icon, 'palette'); ?>>🎨 Palette (Niche)</option>
                    <option value="heart" <?php selected($icon, 'heart'); ?>>♥ Heart (Romantic)</option>
                    <option value="star" <?php selected($icon, 'star'); ?>>★ Star (Premium)</option>
                </select>
                <p class="description"><?php _e('Icon to represent this category.', 'parfume-reviews'); ?></p>
            </td>
        </tr>
        
        <tr class="form-field term-target-audience-wrap">
            <th scope="row"><label for="gender_target_audience"><?php _e('Target Audience', 'parfume-reviews'); ?></label></th>
            <td>
                <select name="gender_target_audience" id="gender_target_audience">
                    <option value=""><?php _e('Select audience', 'parfume-reviews'); ?></option>
                    <option value="men" <?php selected($target_audience, 'men'); ?>><?php _e('Men', 'parfume-reviews'); ?></option>
                    <option value="women" <?php selected($target_audience, 'women'); ?>><?php _e('Women', 'parfume-reviews'); ?></option>
                    <option value="unisex" <?php selected($target_audience, 'unisex'); ?>><?php _e('Unisex', 'parfume-reviews'); ?></option>
                    <option value="luxury" <?php selected($target_audience, 'luxury'); ?>><?php _e('Luxury Market', 'parfume-reviews'); ?></option>
                    <option value="niche" <?php selected($target_audience, 'niche'); ?>><?php _e('Niche Market', 'parfume-reviews'); ?></option>
                    <option value="mainstream" <?php selected($target_audience, 'mainstream'); ?>><?php _e('Mainstream', 'parfume-reviews'); ?></option>
                </select>
                <p class="description"><?php _e('Primary target audience for this category.', 'parfume-reviews'); ?></p>
            </td>
        </tr>
        
        <tr class="form-field term-characteristics-wrap">
            <th scope="row"><label for="gender_characteristics"><?php _e('Characteristics', 'parfume-reviews'); ?></label></th>
            <td>
                <textarea name="gender_characteristics" id="gender_characteristics" rows="3" cols="50"><?php echo esc_textarea($characteristics); ?></textarea>
                <p class="description"><?php _e('Typical characteristics of perfumes in this category.', 'parfume-reviews'); ?></p>
            </td>
        </tr>
        
        <tr class="form-field term-popular-notes-wrap">
            <th scope="row"><label for="gender_popular_notes"><?php _e('Popular Notes', 'parfume-reviews'); ?></label></th>
            <td>
                <textarea name="gender_popular_notes" id="gender_popular_notes" rows="2" cols="50"><?php echo esc_textarea($popular_notes); ?></textarea>
                <p class="description"><?php _e('Most common notes found in this category.', 'parfume-reviews'); ?></p>
            </td>
        </tr>
        
        <tr class="form-field term-season-preference-wrap">
            <th scope="row"><label for="gender_season_preference"><?php _e('Season Preference', 'parfume-reviews'); ?></label></th>
            <td>
                <select name="gender_season_preference" id="gender_season_preference">
                    <option value=""><?php _e('All seasons', 'parfume-reviews'); ?></option>
                    <option value="spring" <?php selected($season_preference, 'spring'); ?>><?php _e('Spring', 'parfume-reviews'); ?></option>
                    <option value="summer" <?php selected($season_preference, 'summer'); ?>><?php _e('Summer', 'parfume-reviews'); ?></option>
                    <option value="autumn" <?php selected($season_preference, 'autumn'); ?>><?php _e('Autumn', 'parfume-reviews'); ?></option>
                    <option value="winter" <?php selected($season_preference, 'winter'); ?>><?php _e('Winter', 'parfume-reviews'); ?></option>
                    <option value="warm" <?php selected($season_preference, 'warm'); ?>><?php _e('Warm seasons', 'parfume-reviews'); ?></option>
                    <option value="cold" <?php selected($season_preference, 'cold'); ?>><?php _e('Cold seasons', 'parfume-reviews'); ?></option>
                </select>
                <p class="description"><?php _e('Best season for this category of perfumes.', 'parfume-reviews'); ?></p>
            </td>
        </tr>
        <?php
    }
    
    /**
     * Save custom fields
     */
    public function save_custom_fields($term_id, $tt_id = '') {
        $fields = array(
            'gender_color_scheme' => 'sanitize_hex_color',
            'gender_icon' => 'sanitize_text_field',
            'gender_target_audience' => 'sanitize_text_field',
            'gender_characteristics' => 'sanitize_textarea_field',
            'gender_popular_notes' => 'sanitize_textarea_field',
            'gender_season_preference' => 'sanitize_text_field'
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
                $new_columns['category_icon'] = __('Icon', 'parfume-reviews');
                $new_columns['target_audience'] = __('Audience', 'parfume-reviews');
                $new_columns['characteristics'] = __('Characteristics', 'parfume-reviews');
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
            case 'category_icon':
                $icon = get_term_meta($term_id, 'gender_icon', true);
                $color = get_term_meta($term_id, 'gender_color_scheme', true);
                
                if ($icon) {
                    $icon_map = array(
                        'mars' => '♂',
                        'venus' => '♀',
                        'genderless' => '⚲',
                        'crown' => '♔',
                        'moon-stars' => '☪',
                        'palette' => '🎨',
                        'heart' => '♥',
                        'star' => '★'
                    );
                    
                    $icon_char = isset($icon_map[$icon]) ? $icon_map[$icon] : '○';
                    $content = '<span style="color: ' . esc_attr($color ?: '#333') . '; font-size: 20px;">' . $icon_char . '</span>';
                } else {
                    $content = '—';
                }
                break;
                
            case 'target_audience':
                $audience = get_term_meta($term_id, 'gender_target_audience', true);
                $content = $audience ? ucfirst(esc_html($audience)) : '—';
                break;
                
            case 'characteristics':
                $characteristics = get_term_meta($term_id, 'gender_characteristics', true);
                if ($characteristics) {
                    $content = '<span title="' . esc_attr($characteristics) . '">' . 
                              wp_trim_words(esc_html($characteristics), 6) . '</span>';
                } else {
                    $content = '—';
                }
                break;
                
            case 'perfume_count':
                $term = get_term($term_id);
                $count = $term->count;
                $content = '<a href="' . admin_url('edit.php?post_type=parfume&gender=' . $term->slug) . '">';
                $content .= sprintf(_n('%d perfume', '%d perfumes', $count, 'parfume-reviews'), $count);
                $content .= '</a>';
                break;
        }
        
        return $content;
    }
    
    /**
     * Add body classes for category pages
     */
    public function add_body_classes($classes) {
        if (is_tax('gender')) {
            $term = get_queried_object();
            $classes[] = 'parfume-category-archive';
            $classes[] = 'parfume-category-' . $term->slug;
            
            $target_audience = get_term_meta($term->term_id, 'gender_target_audience', true);
            if ($target_audience) {
                $classes[] = 'parfume-audience-' . $target_audience;
            }
        }
        
        return $classes;
    }
    
    /**
     * Add schema markup for category pages
     */
    public function add_category_schema() {
        if (!is_tax('gender')) {
            return;
        }
        
        $term = get_queried_object();
        $characteristics = get_term_meta($term->term_id, 'gender_characteristics', true);
        $popular_notes = get_term_meta($term->term_id, 'gender_popular_notes', true);
        
        $schema = array(
            '@context' => 'https://schema.org',
            '@type' => 'CollectionPage',
            'name' => $term->name,
            'description' => $term->description ?: $characteristics,
            'url' => get_term_link($term),
            'mainEntity' => array(
                '@type' => 'ItemList',
                'name' => sprintf(__('%s Perfumes', 'parfume-reviews'), $term->name),
                'numberOfItems' => $term->count
            )
        );
        
        if ($popular_notes) {
            $schema['keywords'] = $popular_notes;
        }
        
        echo '<script type="application/ld+json">' . wp_json_encode($schema, JSON_UNESCAPED_UNICODE) . '</script>' . "\n";
    }
    
    /**
     * Get category statistics
     */
    public function get_category_stats($term_id) {
        $perfumes = get_posts(array(
            'post_type' => 'parfume',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'tax_query' => array(
                array(
                    'taxonomy' => 'gender',
                    'field' => 'term_id',
                    'terms' => $term_id
                )
            )
        ));
        
        $total_perfumes = count($perfumes);
        $total_rating = 0;
        $rated_perfumes = 0;
        $brands = array();
        $notes = array();
        
        foreach ($perfumes as $perfume_id) {
            // Rating statistics
            $rating = get_post_meta($perfume_id, '_parfume_rating', true);
            if ($rating && is_numeric($rating)) {
                $total_rating += floatval($rating);
                $rated_perfumes++;
            }
            
            // Brand distribution
            $perfume_brands = wp_get_post_terms($perfume_id, 'brands', array('fields' => 'names'));
            if (!is_wp_error($perfume_brands)) {
                foreach ($perfume_brands as $brand) {
                    $brands[$brand] = isset($brands[$brand]) ? $brands[$brand] + 1 : 1;
                }
            }
            
            // Popular notes
            $perfume_notes = wp_get_post_terms($perfume_id, 'notes', array('fields' => 'names'));
            if (!is_wp_error($perfume_notes)) {
                foreach ($perfume_notes as $note) {
                    $notes[$note] = isset($notes[$note]) ? $notes[$note] + 1 : 1;
                }
            }
        }
        
        // Sort by popularity
        arsort($brands);
        arsort($notes);
        
        return array(
            'total_perfumes' => $total_perfumes,
            'average_rating' => $rated_perfumes > 0 ? round($total_rating / $rated_perfumes, 2) : 0,
            'rated_perfumes' => $rated_perfumes,
            'top_brands' => array_slice($brands, 0, 5, true),
            'popular_notes' => array_slice($notes, 0, 10, true),
            'brand_diversity' => count($brands),
            'note_diversity' => count($notes)
        );
    }
    
    /**
     * Get rewrite slug
     */
    private function get_rewrite_slug() {
        $parfume_slug = parfume_get_setting('parfume_slug', 'parfiumi');
        $gender_slug = parfume_get_setting('gender_slug', 'kategorii');
        
        return $parfume_slug . '/' . $gender_slug;
    }
}