<?php
/**
 * Taxonomy helper functions and common functionality
 */

if (!defined('ABSPATH')) {
    exit;
}

class Parfume_Reviews_Taxonomy_Helper {
    
    public function __construct() {
        add_action('init', array($this, 'add_custom_rewrite_rules'), 20);
        add_filter('query_vars', array($this, 'add_query_vars'));
        add_action('parse_request', array($this, 'parse_custom_requests'));
        add_filter('template_include', array($this, 'template_loader'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }
    
    /**
     * Add custom rewrite rules for taxonomy archives
     */
    public function add_custom_rewrite_rules() {
        $settings = get_option('parfume_reviews_settings', array());
        $parfume_slug = !empty($settings['parfume_slug']) ? $settings['parfume_slug'] : 'parfiumi';
        
        $taxonomies = array(
            'marki' => !empty($settings['brands_slug']) ? $settings['brands_slug'] : 'marki',
            'notes' => !empty($settings['notes_slug']) ? $settings['notes_slug'] : 'notes',
            'perfumer' => !empty($settings['perfumers_slug']) ? $settings['perfumers_slug'] : 'parfumers',
            'gender' => !empty($settings['gender_slug']) ? $settings['gender_slug'] : 'gender',
        );
        
        foreach ($taxonomies as $taxonomy => $slug) {
            // Archive page with pagination
            add_rewrite_rule(
                '^' . $parfume_slug . '/' . $slug . '/page/([0-9]+)/?$',
                'index.php?parfume_taxonomy_archive=' . $taxonomy . '&paged=$matches[1]',
                'top'
            );
            
            // Archive page
            add_rewrite_rule(
                '^' . $parfume_slug . '/' . $slug . '/?$',
                'index.php?parfume_taxonomy_archive=' . $taxonomy,
                'top'
            );
            
            // Individual term with pagination
            add_rewrite_rule(
                '^' . $parfume_slug . '/' . $slug . '/([^/]+)/page/([0-9]+)/?$',
                'index.php?' . $taxonomy . '=$matches[1]&paged=$matches[2]',
                'top'
            );
            
            // Individual term
            add_rewrite_rule(
                '^' . $parfume_slug . '/' . $slug . '/([^/]+)/?$',
                'index.php?' . $taxonomy . '=$matches[1]',
                'top'
            );
        }
    }
    
    /**
     * Add query vars
     */
    public function add_query_vars($vars) {
        $vars[] = 'parfume_taxonomy_archive';
        return $vars;
    }
    
    /**
     * Parse custom requests
     */
    public function parse_custom_requests($wp) {
        if (isset($wp->query_vars['parfume_taxonomy_archive'])) {
            $taxonomy = $wp->query_vars['parfume_taxonomy_archive'];
            
            $wp->query_vars['post_type'] = 'parfume';
            $wp->query_vars['posts_per_page'] = 12;
            
            $terms = get_terms(array(
                'taxonomy' => $taxonomy,
                'hide_empty' => false,
                'fields' => 'ids'
            ));
            
            if (!empty($terms) && !is_wp_error($terms)) {
                $wp->query_vars['tax_query'] = array(
                    array(
                        'taxonomy' => $taxonomy,
                        'field' => 'term_id',
                        'terms' => $terms,
                        'operator' => 'IN'
                    )
                );
            }
            
            $wp->query_vars['is_parfume_taxonomy_archive'] = $taxonomy;
        }
    }
    
    /**
     * Template loader for taxonomies
     */
    public function template_loader($template) {
        global $wp_query;
        
        // Custom taxonomy archive
        if (isset($wp_query->query_vars['is_parfume_taxonomy_archive'])) {
            $taxonomy = $wp_query->query_vars['is_parfume_taxonomy_archive'];
            
            $templates = array(
                PARFUME_REVIEWS_TEMPLATES_DIR . 'archive/archive-' . $taxonomy . '.php',
                PARFUME_REVIEWS_TEMPLATES_DIR . 'archive/archive-taxonomy.php',
            );
            
            foreach ($templates as $template_path) {
                if (file_exists($template_path)) {
                    return $template_path;
                }
            }
        }
        
        // Individual taxonomy terms
        $taxonomy_templates = array(
            'marki' => 'taxonomy-marki.php',
            'notes' => 'taxonomy-notes.php',
            'perfumer' => 'taxonomy-perfumer.php',
            'gender' => 'taxonomy-gender.php',
        );
        
        foreach ($taxonomy_templates as $taxonomy => $template_file) {
            if (is_tax($taxonomy)) {
                $plugin_template = PARFUME_REVIEWS_TEMPLATES_DIR . 'single/' . $template_file;
                if (file_exists($plugin_template)) {
                    return $plugin_template;
                }
            }
        }
        
        return $template;
    }
    
    /**
     * Add image field to taxonomy forms
     */
    public function add_taxonomy_image_field($taxonomy) {
        $field_name = $taxonomy . '_image_id';
        $wrapper_id = $taxonomy . '_image_wrapper';
        ?>
        <div class="form-field term-group">
            <label for="<?php echo esc_attr($field_name); ?>"><?php _e('Изображение', 'parfume-reviews'); ?></label>
            <input type="hidden" id="<?php echo esc_attr($field_name); ?>" name="<?php echo esc_attr($field_name); ?>" value="">
            <div id="<?php echo esc_attr($wrapper_id); ?>"></div>
            <p>
                <input type="button" class="button button-secondary tax_media_button" data-field="<?php echo esc_attr($field_name); ?>" data-wrapper="<?php echo esc_attr($wrapper_id); ?>" value="<?php _e('Добави изображение', 'parfume-reviews'); ?>" />
                <input type="button" class="button button-secondary tax_media_remove" data-field="<?php echo esc_attr($field_name); ?>" data-wrapper="<?php echo esc_attr($wrapper_id); ?>" value="<?php _e('Премахни изображение', 'parfume-reviews'); ?>" />
            </p>
        </div>
        <?php
    }
    
    /**
     * Edit taxonomy image field
     */
    public function edit_taxonomy_image_field($term, $taxonomy) {
        $field_name = $taxonomy . '_image_id';
        $wrapper_id = $taxonomy . '_image_wrapper';
        $image_id = get_term_meta($term->term_id, $field_name, true);
        ?>
        <tr class="form-field term-group-wrap">
            <th scope="row">
                <label for="<?php echo esc_attr($field_name); ?>"><?php _e('Изображение', 'parfume-reviews'); ?></label>
            </th>
            <td>
                <input type="hidden" id="<?php echo esc_attr($field_name); ?>" name="<?php echo esc_attr($field_name); ?>" value="<?php echo esc_attr($image_id); ?>">
                <div id="<?php echo esc_attr($wrapper_id); ?>">
                    <?php if ($image_id) {
                        echo wp_get_attachment_image($image_id, 'thumbnail');
                    } ?>
                </div>
                <p>
                    <input type="button" class="button button-secondary tax_media_button" data-field="<?php echo esc_attr($field_name); ?>" data-wrapper="<?php echo esc_attr($wrapper_id); ?>" value="<?php _e('Добави изображение', 'parfume-reviews'); ?>" />
                    <input type="button" class="button button-secondary tax_media_remove" data-field="<?php echo esc_attr($field_name); ?>" data-wrapper="<?php echo esc_attr($wrapper_id); ?>" value="<?php _e('Премахни изображение', 'parfume-reviews'); ?>" />
                </p>
            </td>
        </tr>
        <?php
    }
    
    /**
     * Save taxonomy meta fields
     */
    public function save_taxonomy_meta_fields($term_id, $tt_id, $taxonomy) {
        $field_name = $taxonomy . '_image_id';
        if (isset($_POST[$field_name])) {
            update_term_meta($term_id, $field_name, absint($_POST[$field_name]));
        }
    }
    
    /**
     * Get taxonomy image
     */
    public static function get_taxonomy_image($term_id, $taxonomy, $size = 'thumbnail') {
        $field_name = $taxonomy . '_image_id';
        $image_id = get_term_meta($term_id, $field_name, true);
        
        if ($image_id) {
            return wp_get_attachment_image($image_id, $size);
        }
        
        return '';
    }
    
    /**
     * Get taxonomy image URL
     */
    public static function get_taxonomy_image_url($term_id, $taxonomy, $size = 'thumbnail') {
        $field_name = $taxonomy . '_image_id';
        $image_id = get_term_meta($term_id, $field_name, true);
        
        if ($image_id) {
            return wp_get_attachment_image_url($image_id, $size);
        }
        
        return '';
    }
    
    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'edit-tags.php') !== false || strpos($hook, 'term.php') !== false) {
            wp_enqueue_media();
            wp_enqueue_script('parfume-taxonomy-media', PARFUME_REVIEWS_PLUGIN_URL . 'admin/js/taxonomy-media.js', array('jquery'), PARFUME_REVIEWS_VERSION, true);
        }
    }
}