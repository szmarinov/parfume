<?php
namespace ParfumeReviews\Core;

if (!defined('ABSPATH')) {
    exit;
}

class PostType {
    
    public function __construct() {
        add_action('init', [$this, 'register_post_type']);
        add_action('add_meta_boxes', [$this, 'add_meta_boxes']);
        add_action('save_post', [$this, 'save_meta_boxes']);
        add_filter('template_include', [$this, 'template_loader']);
    }
    
    public function register_post_type() {
        $settings = get_option('parfume_reviews_settings', []);
        $slug = !empty($settings['parfume_slug']) ? $settings['parfume_slug'] : 'parfiumi';
        
        $labels = [
            'name' => __('Parfumes', 'parfume-reviews'),
            'singular_name' => __('Parfume', 'parfume-reviews'),
            'menu_name' => __('Parfumes', 'parfume-reviews'),
            'add_new' => __('Add New', 'parfume-reviews'),
            'add_new_item' => __('Add New Parfume', 'parfume-reviews'),
            'edit_item' => __('Edit Parfume', 'parfume-reviews'),
            'view_item' => __('View Parfume', 'parfume-reviews'),
            'all_items' => __('All Parfumes', 'parfume-reviews'),
            'search_items' => __('Search Parfumes', 'parfume-reviews'),
            'not_found' => __('No parfumes found.', 'parfume-reviews'),
            'not_found_in_trash' => __('No parfumes found in Trash.', 'parfume-reviews')
        ];
        
        $args = [
            'labels' => $labels,
            'public' => true,
            'publicly_queryable' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'query_var' => true,
            'rewrite' => ['slug' => $slug, 'with_front' => false],
            'capability_type' => 'post',
            'has_archive' => true,
            'hierarchical' => false,
            'menu_position' => 5,
            'supports' => ['title', 'editor', 'thumbnail', 'excerpt', 'comments'],
            'show_in_rest' => true,
            'menu_icon' => 'dashicons-airplane',
            'taxonomies' => ['marki', 'gender', 'aroma_type', 'season', 'intensity', 'notes', 'perfumer'],
        ];
        
        register_post_type('parfume', $args);
    }
    
    public function add_meta_boxes() {
        add_meta_box(
            'parfume_rating',
            __('Рейтинг', 'parfume-reviews'),
            [$this, 'render_rating_meta_box'],
            'parfume',
            'side',
            'default'
        );
        
        add_meta_box(
            'parfume_details',
            __('Детайли за парфюма', 'parfume-reviews'),
            [$this, 'render_details_meta_box'],
            'parfume',
            'normal',
            'high'
        );
    }
    
    public function render_rating_meta_box($post) {
        wp_nonce_field('parfume_rating_nonce', 'parfume_rating_nonce');
        
        $rating = get_post_meta($post->ID, '_parfume_rating', true);
        $rating = !empty($rating) ? floatval($rating) : 0;
        
        echo '<p>';
        echo '<label for="parfume_rating">' . __('Рейтинг (0-5):', 'parfume-reviews') . '</label><br>';
        echo '<input type="number" id="parfume_rating" name="parfume_rating" value="' . esc_attr($rating) . '" min="0" max="5" step="0.1" class="small-text" />';
        echo '</p>';
    }
    
    public function render_details_meta_box($post) {
        wp_nonce_field('parfume_details_nonce', 'parfume_details_nonce');
        
        $gender = get_post_meta($post->ID, '_parfume_gender', true);
        $release_year = get_post_meta($post->ID, '_parfume_release_year', true);
        $longevity = get_post_meta($post->ID, '_parfume_longevity', true);
        $sillage = get_post_meta($post->ID, '_parfume_sillage', true);
        
        echo '<table class="form-table"><tbody>';
        
        echo '<tr><th scope="row"><label for="parfume_gender">' . __('Пол', 'parfume-reviews') . '</label></th>';
        echo '<td><input type="text" id="parfume_gender" name="parfume_gender" value="' . esc_attr($gender) . '" class="regular-text" /></td></tr>';
        
        echo '<tr><th scope="row"><label for="parfume_release_year">' . __('Година на издаване', 'parfume-reviews') . '</label></th>';
        echo '<td><input type="text" id="parfume_release_year" name="parfume_release_year" value="' . esc_attr($release_year) . '" class="regular-text" /></td></tr>';
        
        echo '<tr><th scope="row"><label for="parfume_longevity">' . __('Издръжливост', 'parfume-reviews') . '</label></th>';
        echo '<td><input type="text" id="parfume_longevity" name="parfume_longevity" value="' . esc_attr($longevity) . '" class="regular-text" /></td></tr>';
        
        echo '<tr><th scope="row"><label for="parfume_sillage">' . __('Силаж', 'parfume-reviews') . '</label></th>';
        echo '<td><input type="text" id="parfume_sillage" name="parfume_sillage" value="' . esc_attr($sillage) . '" class="regular-text" /></td></tr>';
        
        echo '</tbody></table>';
    }
    
    public function save_meta_boxes($post_id) {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (get_post_type($post_id) !== 'parfume') return;
        if (!current_user_can('edit_post', $post_id)) return;
        
        // Save rating
        if (isset($_POST['parfume_rating_nonce']) && wp_verify_nonce($_POST['parfume_rating_nonce'], 'parfume_rating_nonce')) {
            if (isset($_POST['parfume_rating'])) {
                $rating = floatval($_POST['parfume_rating']);
                $rating = max(0, min(5, $rating));
                update_post_meta($post_id, '_parfume_rating', $rating);
            }
        }
        
        // Save details
        if (isset($_POST['parfume_details_nonce']) && wp_verify_nonce($_POST['parfume_details_nonce'], 'parfume_details_nonce')) {
            $fields = ['parfume_gender', 'parfume_release_year', 'parfume_longevity', 'parfume_sillage'];
            
            foreach ($fields as $field) {
                if (isset($_POST[$field])) {
                    $value = sanitize_text_field($_POST[$field]);
                    update_post_meta($post_id, '_' . $field, $value);
                }
            }
        }
    }
    
    public function template_loader($template) {
        $plugin_templates = [
            'single-parfume.php' => is_singular('parfume'),
            'archive-parfume.php' => is_post_type_archive('parfume'),
        ];
        
        foreach ($plugin_templates as $template_name => $condition) {
            if ($condition) {
                $plugin_template = PARFUME_REVIEWS_PLUGIN_DIR . 'templates/' . $template_name;
                if (file_exists($plugin_template)) {
                    return $plugin_template;
                }
            }
        }
        
        return $template;
    }
}