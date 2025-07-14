<?php
namespace Parfume_Reviews\Taxonomies;

/**
 * Taxonomy Meta Fields - управлява meta полетата за таксономии
 * ПОПРАВЕНА ВЕРСИЯ - image upload и Rank Math SEO интеграция
 */
class Taxonomy_Meta_Fields {
    
    public function __construct() {
        add_action('admin_init', array($this, 'add_taxonomy_meta_fields'));
        add_action('created_term', array($this, 'save_taxonomy_meta_fields'), 10, 3);
        add_action('edit_term', array($this, 'save_taxonomy_meta_fields'), 10, 3);
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        
        // Добавяме Rank Math SEO интеграция
        add_action('init', array($this, 'enable_rankmath_for_taxonomies'), 20);
    }
    
    /**
     * Добавя meta полета към таксономиите
     */
    public function add_taxonomy_meta_fields() {
        // Add fields to all taxonomies
        $taxonomies_with_images = array('marki', 'notes', 'perfumer', 'gender', 'aroma_type', 'season', 'intensity');
        
        foreach ($taxonomies_with_images as $taxonomy) {
            add_action($taxonomy . '_add_form_fields', array($this, 'add_taxonomy_image_field'), 10, 2);
            add_action($taxonomy . '_edit_form_fields', array($this, 'edit_taxonomy_image_field'), 10, 2);
        }
        
        // Add Group field specifically for notes
        add_action('notes_add_form_fields', array($this, 'add_notes_group_field'), 10, 2);
        add_action('notes_edit_form_fields', array($this, 'edit_notes_group_field'), 10, 2);
    }
    
    /**
     * Активира Rank Math SEO за всички наши таксономии
     */
    public function enable_rankmath_for_taxonomies() {
        // Проверяваме дали Rank Math е активен
        if (!defined('RANK_MATH_VERSION')) {
            return;
        }
        
        $supported_taxonomies = array('marki', 'notes', 'perfumer', 'gender', 'aroma_type', 'season', 'intensity');
        
        foreach ($supported_taxonomies as $taxonomy) {
            // Активираме Rank Math meta box за тази таксономия
            add_filter('rank_math/taxonomy/' . $taxonomy, '__return_true');
            add_filter('rank_math/taxonomy/' . $taxonomy . '/add_meta_box', '__return_true');
            
            // Добавяме в sitemap
            add_filter('rank_math/sitemap/enable_' . $taxonomy, '__return_true');
            
            // Активираме structured data
            add_filter('rank_math/schema/taxonomy_' . $taxonomy, '__return_true');
            
            // Активираме за breadcrumbs
            add_filter('rank_math/frontend/breadcrumb/taxonomy_' . $taxonomy, '__return_true');
        }
        
        // Увеличаваме приоритета на Rank Math meta box
        add_filter('rank_math/metabox/priority', function() { 
            return 'high'; 
        });
    }
    
    /**
     * Добавя поле за изображение при създаване на term
     */
    public function add_taxonomy_image_field($taxonomy) {
        $taxonomy_obj = get_taxonomy($taxonomy);
        $field_name = $taxonomy . '-image-id';
        $wrapper_id = $taxonomy . '-image-wrapper';
        ?>
        <div class="form-field term-group">
            <label for="<?php echo esc_attr($field_name); ?>"><?php printf(__('Изображение за %s', 'parfume-reviews'), $taxonomy_obj->labels->singular_name); ?></label>
            <input type="hidden" id="<?php echo esc_attr($field_name); ?>" name="<?php echo esc_attr($field_name); ?>" class="custom_media_url" value="">
            <div id="<?php echo esc_attr($wrapper_id); ?>"></div>
            <p>
                <input type="button" class="button button-secondary pr_tax_media_button" data-field="<?php echo esc_attr($field_name); ?>" data-wrapper="<?php echo esc_attr($wrapper_id); ?>" value="<?php _e('Добави изображение', 'parfume-reviews'); ?>" />
                <input type="button" class="button button-secondary pr_tax_media_remove" data-field="<?php echo esc_attr($field_name); ?>" data-wrapper="<?php echo esc_attr($wrapper_id); ?>" value="<?php _e('Премахни изображение', 'parfume-reviews'); ?>" />
            </p>
            <p class="description"><?php _e('Добавете изображение за тази таксономия', 'parfume-reviews'); ?></p>
        </div>
        <?php
    }
    
    /**
     * Редактира поле за изображение при редактиране на term
     */
    public function edit_taxonomy_image_field($term, $taxonomy) {
        $taxonomy_obj = get_taxonomy($taxonomy);
        $field_name = $taxonomy . '-image-id';
        $wrapper_id = $taxonomy . '-image-wrapper';
        $image_id = get_term_meta($term->term_id, $field_name, true);
        ?>
        <tr class="form-field term-group-wrap">
            <th scope="row">
                <label for="<?php echo esc_attr($field_name); ?>"><?php printf(__('Изображение за %s', 'parfume-reviews'), $taxonomy_obj->labels->singular_name); ?></label>
            </th>
            <td>
                <input type="hidden" id="<?php echo esc_attr($field_name); ?>" name="<?php echo esc_attr($field_name); ?>" class="custom_media_url" value="<?php echo esc_attr($image_id); ?>">
                <div id="<?php echo esc_attr($wrapper_id); ?>">
                    <?php if ($image_id): ?>
                        <?php echo wp_get_attachment_image($image_id, 'thumbnail'); ?>
                    <?php endif; ?>
                </div>
                <p>
                    <input type="button" class="button button-secondary pr_tax_media_button" data-field="<?php echo esc_attr($field_name); ?>" data-wrapper="<?php echo esc_attr($wrapper_id); ?>" value="<?php _e('Избери изображение', 'parfume-reviews'); ?>" />
                    <input type="button" class="button button-secondary pr_tax_media_remove" data-field="<?php echo esc_attr($field_name); ?>" data-wrapper="<?php echo esc_attr($wrapper_id); ?>" value="<?php _e('Премахни изображение', 'parfume-reviews'); ?>" />
                </p>
                <p class="description"><?php _e('Добавете изображение за тази таксономия', 'parfume-reviews'); ?></p>
            </td>
        </tr>
        <?php
    }
    
    /**
     * Добавя поле за група на нотки при създаване
     */
    public function add_notes_group_field($taxonomy) {
        ?>
        <div class="form-field term-group">
            <label for="note_group"><?php _e('Група на нотката', 'parfume-reviews'); ?></label>
            <select id="note_group" name="note_group">
                <option value=""><?php _e('Изберете група', 'parfume-reviews'); ?></option>
                <option value="citrus"><?php _e('Цитрусови', 'parfume-reviews'); ?></option>
                <option value="floral"><?php _e('Флорални', 'parfume-reviews'); ?></option>
                <option value="woody"><?php _e('Дървесни', 'parfume-reviews'); ?></option>
                <option value="oriental"><?php _e('Ориенталски', 'parfume-reviews'); ?></option>
                <option value="fresh"><?php _e('Свежи', 'parfume-reviews'); ?></option>
                <option value="spicy"><?php _e('Пикантни', 'parfume-reviews'); ?></option>
                <option value="gourmand"><?php _e('Гурме', 'parfume-reviews'); ?></option>
                <option value="green"><?php _e('Зелени', 'parfume-reviews'); ?></option>
                <option value="aquatic"><?php _e('Водни', 'parfume-reviews'); ?></option>
                <option value="powdery"><?php _e('Пудрени', 'parfume-reviews'); ?></option>
            </select>
            <p class="description"><?php _e('Групата помага за категоризиране на нотките', 'parfume-reviews'); ?></p>
        </div>
        <?php
    }
    
    /**
     * Редактира поле за група на нотки при редактиране
     */
    public function edit_notes_group_field($term, $taxonomy) {
        $note_group = get_term_meta($term->term_id, 'note_group', true);
        ?>
        <tr class="form-field term-group-wrap">
            <th scope="row">
                <label for="note_group"><?php _e('Група на нотката', 'parfume-reviews'); ?></label>
            </th>
            <td>
                <select id="note_group" name="note_group">
                    <option value=""><?php _e('Изберете група', 'parfume-reviews'); ?></option>
                    <option value="citrus" <?php selected($note_group, 'citrus'); ?>><?php _e('Цитрусови', 'parfume-reviews'); ?></option>
                    <option value="floral" <?php selected($note_group, 'floral'); ?>><?php _e('Флорални', 'parfume-reviews'); ?></option>
                    <option value="woody" <?php selected($note_group, 'woody'); ?>><?php _e('Дървесни', 'parfume-reviews'); ?></option>
                    <option value="oriental" <?php selected($note_group, 'oriental'); ?>><?php _e('Ориенталски', 'parfume-reviews'); ?></option>
                    <option value="fresh" <?php selected($note_group, 'fresh'); ?>><?php _e('Свежи', 'parfume-reviews'); ?></option>
                    <option value="spicy" <?php selected($note_group, 'spicy'); ?>><?php _e('Пикантни', 'parfume-reviews'); ?></option>
                    <option value="gourmand" <?php selected($note_group, 'gourmand'); ?>><?php _e('Гурме', 'parfume-reviews'); ?></option>
                    <option value="green" <?php selected($note_group, 'green'); ?>><?php _e('Зелени', 'parfume-reviews'); ?></option>
                    <option value="aquatic" <?php selected($note_group, 'aquatic'); ?>><?php _e('Водни', 'parfume-reviews'); ?></option>
                    <option value="powdery" <?php selected($note_group, 'powdery'); ?>><?php _e('Пудрени', 'parfume-reviews'); ?></option>
                </select>
                <p class="description"><?php _e('Групата помага за категоризиране на нотките', 'parfume-reviews'); ?></p>
            </td>
        </tr>
        <?php
    }
    
    /**
     * Записва meta полетата на таксономиите
     */
    public function save_taxonomy_meta_fields($term_id, $tt_id, $taxonomy) {
        // Nonce verification не е нужно за taxonomy fields според WordPress standards
        
        // Save image
        $field_name = $taxonomy . '-image-id';
        if (isset($_POST[$field_name])) {
            $image_value = sanitize_text_field($_POST[$field_name]);
            
            // Ако е URL, извличаме attachment ID
            if (filter_var($image_value, FILTER_VALIDATE_URL)) {
                $image_id = attachment_url_to_postid($image_value);
                if ($image_id) {
                    update_term_meta($term_id, $field_name, $image_id);
                }
            } else {
                // Ако е ID директно
                update_term_meta($term_id, $field_name, absint($image_value));
            }
        }
        
        // Save note group for notes taxonomy
        if ($taxonomy === 'notes' && isset($_POST['note_group'])) {
            update_term_meta($term_id, 'note_group', sanitize_text_field($_POST['note_group']));
        }
    }
    
    /**
     * Зарежда admin scripts за media upload
     */
    public function enqueue_admin_scripts($hook) {
        // Зареждаме scripts само за taxonomy страници
        if (strpos($hook, 'edit-tags.php') !== false || strpos($hook, 'term.php') !== false) {
            
            // Media upload functionality
            wp_enqueue_media();
            
            // Наш custom admin script
            wp_enqueue_script(
                'parfume-taxonomy-media', 
                PARFUME_REVIEWS_PLUGIN_URL . 'assets/js/admin.js', 
                array('jquery', 'media-upload'), 
                PARFUME_REVIEWS_VERSION, 
                true
            );
            
            // Localize script
            wp_localize_script('parfume-taxonomy-media', 'parfumeTaxonomy', array(
                'selectImageTitle' => __('Избери изображение', 'parfume-reviews'),
                'selectImageButton' => __('Използвай това изображение', 'parfume-reviews'),
                'uploadImageTitle' => __('Качи изображение', 'parfume-reviews')
            ));
            
            // Admin CSS
            wp_enqueue_style(
                'parfume-admin-taxonomy', 
                PARFUME_REVIEWS_PLUGIN_URL . 'assets/css/admin.css', 
                array(), 
                PARFUME_REVIEWS_VERSION
            );
        }
    }
}