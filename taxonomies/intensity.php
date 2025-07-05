<?php
namespace Parfume_Reviews\Taxonomies;

use Parfume_Reviews\Utils\Taxonomy_Base;

class Intensity extends Taxonomy_Base {
    
    protected $taxonomy = 'intensity';
    protected $post_types = array('parfume');
    
    public function init() {
        $this->register_taxonomy();
        $this->setup_hooks();
        $this->add_default_terms();
    }
    
    public function get_labels() {
        return array(
            'name' => __('Intensity', 'parfume-reviews'),
            'singular_name' => __('Intensity', 'parfume-reviews'),
            'search_items' => __('Search Intensities', 'parfume-reviews'),
            'all_items' => __('All Intensities', 'parfume-reviews'),
            'edit_item' => __('Edit Intensity', 'parfume-reviews'),
            'update_item' => __('Update Intensity', 'parfume-reviews'),
            'add_new_item' => __('Add New Intensity', 'parfume-reviews'),
            'new_item_name' => __('New Intensity Name', 'parfume-reviews'),
            'menu_name' => __('Intensity', 'parfume-reviews'),
        );
    }
    
    public function get_args() {
        $settings = get_option('parfume_reviews_settings', array());
        $parfume_slug = !empty($settings['parfume_slug']) ? $settings['parfume_slug'] : 'parfiumi';
        $intensity_slug = !empty($settings['intensity_slug']) ? $settings['intensity_slug'] : 'intensity';
        
        return array(
            'labels' => $this->get_labels(),
            'hierarchical' => true,
            'show_ui' => true,
            'show_admin_column' => true,
            'query_var' => true,
            'rewrite' => array(
                'slug' => $parfume_slug . '/' . $intensity_slug,
                'with_front' => false,
                'hierarchical' => false
            ),
            'show_in_rest' => true,
            'public' => true,
            'publicly_queryable' => true,
            'meta_box_cb' => 'post_categories_meta_box',
            'show_in_nav_menus' => true,
            'show_tagcloud' => true,
        );
    }
    
    public function setup_hooks() {
        // Meta fields hooks - Fixed method signatures
        add_action($this->taxonomy . '_add_form_fields', array($this, 'add_meta_fields'));
        add_action($this->taxonomy . '_edit_form_fields', array($this, 'edit_meta_fields'), 10, 2);
        add_action('created_' . $this->taxonomy, array($this, 'save_meta_fields'), 10, 2);
        add_action('edit_' . $this->taxonomy, array($this, 'save_meta_fields'), 10, 2);
        
        // Admin columns
        add_filter('manage_edit-' . $this->taxonomy . '_columns', array($this, 'add_admin_columns'));
        add_filter('manage_' . $this->taxonomy . '_custom_column', array($this, 'render_admin_columns'), 10, 3);
        
        // Admin scripts
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }
    
    // Fixed method signature to match base class
    public function add_meta_fields() {
        $taxonomy = $this->taxonomy;
        ?>
        <div class="form-field term-group">
            <label for="<?php echo $taxonomy; ?>-image-id"><?php _e('Intensity Image', 'parfume-reviews'); ?></label>
            <input type="hidden" id="<?php echo $taxonomy; ?>-image-id" name="<?php echo $taxonomy; ?>-image-id" class="custom_media_url" value="">
            <div id="<?php echo $taxonomy; ?>-image-wrapper"></div>
            <p>
                <input type="button" class="button button-secondary pr_tax_media_button" id="<?php echo $taxonomy; ?>-media-button" data-field="<?php echo $taxonomy; ?>-image-id" data-wrapper="<?php echo $taxonomy; ?>-image-wrapper" value="<?php _e('Add Image', 'parfume-reviews'); ?>" />
                <input type="button" class="button button-secondary pr_tax_media_remove" data-field="<?php echo $taxonomy; ?>-image-id" data-wrapper="<?php echo $taxonomy; ?>-image-wrapper" value="<?php _e('Remove Image', 'parfume-reviews'); ?>" />
            </p>
        </div>
        
        <div class="form-field">
            <label for="intensity_level"><?php _e('Intensity Level (1-10)', 'parfume-reviews'); ?></label>
            <input type="number" name="intensity_level" id="intensity_level" min="1" max="10" step="1" />
            <p><?php _e('Numeric intensity level from 1 (very light) to 10 (very strong).', 'parfume-reviews'); ?></p>
        </div>
        
        <div class="form-field">
            <label for="typical_longevity"><?php _e('Typical Longevity', 'parfume-reviews'); ?></label>
            <select name="typical_longevity" id="typical_longevity">
                <option value=""><?php _e('Select longevity', 'parfume-reviews'); ?></option>
                <option value="1-2 часа"><?php _e('1-2 hours', 'parfume-reviews'); ?></option>
                <option value="2-4 часа"><?php _e('2-4 hours', 'parfume-reviews'); ?></option>
                <option value="4-6 часа"><?php _e('4-6 hours', 'parfume-reviews'); ?></option>
                <option value="6-8 часа"><?php _e('6-8 hours', 'parfume-reviews'); ?></option>
                <option value="8+ часа"><?php _e('8+ hours', 'parfume-reviews'); ?></option>
            </select>
            <p><?php _e('How long this intensity typically lasts on skin.', 'parfume-reviews'); ?></p>
        </div>
        
        <div class="form-field">
            <label for="sillage_level"><?php _e('Typical Sillage', 'parfume-reviews'); ?></label>
            <select name="sillage_level" id="sillage_level">
                <option value=""><?php _e('Select sillage', 'parfume-reviews'); ?></option>
                <option value="Близо до кожата"><?php _e('Close to skin', 'parfume-reviews'); ?></option>
                <option value="Умерен"><?php _e('Moderate', 'parfume-reviews'); ?></option>
                <option value="Силен"><?php _e('Strong', 'parfume-reviews'); ?></option>
                <option value="Много силен"><?php _e('Very strong', 'parfume-reviews'); ?></option>
            </select>
            <p><?php _e('How far this intensity projects from the skin.', 'parfume-reviews'); ?></p>
        </div>
        <?php
    }
    
    // Fixed method signature to match base class
    public function edit_meta_fields($term, $taxonomy) {
        $image_id = get_term_meta($term->term_id, $taxonomy . '-image-id', true);
        $intensity_level = get_term_meta($term->term_id, 'intensity_level', true);
        $typical_longevity = get_term_meta($term->term_id, 'typical_longevity', true);
        $sillage_level = get_term_meta($term->term_id, 'sillage_level', true);
        ?>
        <tr class="form-field term-group-wrap">
            <th scope="row">
                <label for="<?php echo $taxonomy; ?>-image-id"><?php _e('Intensity Image', 'parfume-reviews'); ?></label>
            </th>
            <td>
                <input type="hidden" id="<?php echo $taxonomy; ?>-image-id" name="<?php echo $taxonomy; ?>-image-id" value="<?php echo esc_attr($image_id); ?>">
                <div id="<?php echo $taxonomy; ?>-image-wrapper">
                    <?php if ($image_id) { ?>
                        <?php echo wp_get_attachment_image($image_id, 'thumbnail'); ?>
                    <?php } ?>
                </div>
                <p>
                    <input type="button" class="button button-secondary pr_tax_media_button" data-field="<?php echo $taxonomy; ?>-image-id" data-wrapper="<?php echo $taxonomy; ?>-image-wrapper" value="<?php _e('Add Image', 'parfume-reviews'); ?>" />
                    <input type="button" class="button button-secondary pr_tax_media_remove" data-field="<?php echo $taxonomy; ?>-image-id" data-wrapper="<?php echo $taxonomy; ?>-image-wrapper" value="<?php _e('Remove Image', 'parfume-reviews'); ?>" />
                </p>
            </td>
        </tr>
        
        <tr class="form-field">
            <th scope="row">
                <label for="intensity_level"><?php _e('Intensity Level (1-10)', 'parfume-reviews'); ?></label>
            </th>
            <td>
                <input type="number" name="intensity_level" id="intensity_level" value="<?php echo esc_attr($intensity_level); ?>" min="1" max="10" step="1" />
                <p class="description"><?php _e('Numeric intensity level from 1 (very light) to 10 (very strong).', 'parfume-reviews'); ?></p>
            </td>
        </tr>
        
        <tr class="form-field">
            <th scope="row">
                <label for="typical_longevity"><?php _e('Typical Longevity', 'parfume-reviews'); ?></label>
            </th>
            <td>
                <select name="typical_longevity" id="typical_longevity">
                    <option value=""><?php _e('Select longevity', 'parfume-reviews'); ?></option>
                    <option value="1-2 часа" <?php selected($typical_longevity, '1-2 часа'); ?>><?php _e('1-2 hours', 'parfume-reviews'); ?></option>
                    <option value="2-4 часа" <?php selected($typical_longevity, '2-4 часа'); ?>><?php _e('2-4 hours', 'parfume-reviews'); ?></option>
                    <option value="4-6 часа" <?php selected($typical_longevity, '4-6 часа'); ?>><?php _e('4-6 hours', 'parfume-reviews'); ?></option>
                    <option value="6-8 часа" <?php selected($typical_longevity, '6-8 часа'); ?>><?php _e('6-8 hours', 'parfume-reviews'); ?></option>
                    <option value="8+ часа" <?php selected($typical_longevity, '8+ часа'); ?>><?php _e('8+ hours', 'parfume-reviews'); ?></option>
                </select>
                <p class="description"><?php _e('How long this intensity typically lasts on skin.', 'parfume-reviews'); ?></p>
            </td>
        </tr>
        
        <tr class="form-field">
            <th scope="row">
                <label for="sillage_level"><?php _e('Typical Sillage', 'parfume-reviews'); ?></label>
            </th>
            <td>
                <select name="sillage_level" id="sillage_level">
                    <option value=""><?php _e('Select sillage', 'parfume-reviews'); ?></option>
                    <option value="Близо до кожата" <?php selected($sillage_level, 'Близо до кожата'); ?>><?php _e('Close to skin', 'parfume-reviews'); ?></option>
                    <option value="Умерен" <?php selected($sillage_level, 'Умерен'); ?>><?php _e('Moderate', 'parfume-reviews'); ?></option>
                    <option value="Силен" <?php selected($sillage_level, 'Силен'); ?>><?php _e('Strong', 'parfume-reviews'); ?></option>
                    <option value="Много силен" <?php selected($sillage_level, 'Много силен'); ?>><?php _e('Very strong', 'parfume-reviews'); ?></option>
                </select>
                <p class="description"><?php _e('How far this intensity projects from the skin.', 'parfume-reviews'); ?></p>
            </td>
        </tr>
        <?php
    }
    
    // Fixed method signature to match base class
    public function save_meta_fields($term_id, $taxonomy) {
        // Save image
        if (isset($_POST[$taxonomy . '-image-id'])) {
            update_term_meta($term_id, $taxonomy . '-image-id', absint($_POST[$taxonomy . '-image-id']));
        }
        
        // Save intensity level
        if (isset($_POST['intensity_level'])) {
            $level = intval($_POST['intensity_level']);
            if ($level >= 1 && $level <= 10) {
                update_term_meta($term_id, 'intensity_level', $level);
            }
        }
        
        // Save typical longevity
        if (isset($_POST['typical_longevity'])) {
            update_term_meta($term_id, 'typical_longevity', sanitize_text_field($_POST['typical_longevity']));
        }
        
        // Save sillage level
        if (isset($_POST['sillage_level'])) {
            update_term_meta($term_id, 'sillage_level', sanitize_text_field($_POST['sillage_level']));
        }
    }
    
    public function add_admin_columns($columns) {
        $new_columns = array();
        $new_columns['cb'] = $columns['cb'];
        $new_columns['image'] = __('Image', 'parfume-reviews');
        $new_columns['name'] = $columns['name'];
        $new_columns['intensity_level'] = __('Level', 'parfume-reviews');
        $new_columns['longevity'] = __('Longevity', 'parfume-reviews');
        $new_columns['sillage'] = __('Sillage', 'parfume-reviews');
        $new_columns['posts'] = $columns['posts'];
        
        return $new_columns;
    }
    
    public function render_admin_columns($content, $column_name, $term_id) {
        $taxonomy = $this->taxonomy;
        
        switch ($column_name) {
            case 'image':
                $image_id = get_term_meta($term_id, $taxonomy . '-image-id', true);
                if ($image_id) {
                    echo wp_get_attachment_image($image_id, array(50, 50));
                } else {
                    echo '<span class="dashicons dashicons-format-image" style="font-size: 20px; color: #ddd;"></span>';
                }
                break;
                
            case 'intensity_level':
                $level = get_term_meta($term_id, 'intensity_level', true);
                if ($level) {
                    echo '<strong>' . esc_html($level) . '/10</strong>';
                    echo '<div class="intensity-bar" style="width: 60px; height: 8px; background: #ddd; margin-top: 2px; border-radius: 4px;">';
                    echo '<div style="width: ' . ($level * 10) . '%; height: 100%; background: linear-gradient(90deg, #4CAF50, #FF5722); border-radius: 4px;"></div>';
                    echo '</div>';
                } else {
                    echo '—';
                }
                break;
                
            case 'longevity':
                $longevity = get_term_meta($term_id, 'typical_longevity', true);
                echo $longevity ? esc_html($longevity) : '—';
                break;
                
            case 'sillage':
                $sillage = get_term_meta($term_id, 'sillage_level', true);
                echo $sillage ? esc_html($sillage) : '—';
                break;
        }
        
        return $content;
    }
    
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'edit-tags.php') !== false || strpos($hook, 'term.php') !== false) {
            wp_enqueue_media();
            wp_enqueue_script('parfume-taxonomy-media', PARFUME_REVIEWS_PLUGIN_URL . 'assets/js/taxonomy-media.js', array('jquery'), PARFUME_REVIEWS_VERSION, true);
        }
    }
    
    public function add_default_terms() {
        $default_intensities = array(
            array(
                'name' => 'Силни',
                'description' => 'Парфюми с висока концентрация и силна проекция',
                'meta' => array(
                    'intensity_level' => 8,
                    'typical_longevity' => '8+ часа',
                    'sillage_level' => 'Силен'
                )
            ),
            array(
                'name' => 'Средни',
                'description' => 'Парфюми с умерена концентрация и проекция',
                'meta' => array(
                    'intensity_level' => 5,
                    'typical_longevity' => '4-6 часа',
                    'sillage_level' => 'Умерен'
                )
            ),
            array(
                'name' => 'Леки',
                'description' => 'Парфюми с ниска концентрация, подходящи за дневно носене',
                'meta' => array(
                    'intensity_level' => 3,
                    'typical_longevity' => '2-4 часа',
                    'sillage_level' => 'Близо до кожата'
                )
            ),
        );
        
        foreach ($default_intensities as $intensity) {
            if (!term_exists($intensity['name'], $this->taxonomy)) {
                $term = wp_insert_term($intensity['name'], $this->taxonomy, array(
                    'description' => $intensity['description']
                ));
                
                if (!is_wp_error($term) && isset($intensity['meta'])) {
                    foreach ($intensity['meta'] as $key => $value) {
                        update_term_meta($term['term_id'], $key, $value);
                    }
                }
            }
        }
    }
    
    public function get_statistics() {
        $stats = array(
            'total_intensities' => wp_count_terms(array('taxonomy' => $this->taxonomy, 'hide_empty' => false)),
            'with_images' => 0,
            'average_level' => 0,
            'most_common' => '',
        );
        
        $terms = get_terms(array(
            'taxonomy' => $this->taxonomy,
            'hide_empty' => false,
            'meta_query' => array(
                array(
                    'key' => $this->taxonomy . '-image-id',
                    'compare' => 'EXISTS'
                )
            )
        ));
        
        if (!is_wp_error($terms)) {
            $stats['with_images'] = count($terms);
        }
        
        // Calculate average intensity level
        $all_terms = get_terms(array('taxonomy' => $this->taxonomy, 'hide_empty' => false));
        if (!is_wp_error($all_terms) && !empty($all_terms)) {
            $total_level = 0;
            $count = 0;
            $most_count = 0;
            
            foreach ($all_terms as $term) {
                $level = get_term_meta($term->term_id, 'intensity_level', true);
                if ($level) {
                    $total_level += $level;
                    $count++;
                }
                
                if ($term->count > $most_count) {
                    $most_count = $term->count;
                    $stats['most_common'] = $term->name;
                }
            }
            
            if ($count > 0) {
                $stats['average_level'] = round($total_level / $count, 1);
            }
        }
        
        return $stats;
    }
}