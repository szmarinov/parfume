<?php
namespace Parfume_Reviews\Taxonomies;

/**
 * Taxonomy Meta Fields - управлява meta полетата за таксономии
 */
class Taxonomy_Meta_Fields {
    
    public function __construct() {
        add_action('admin_init', array($this, 'add_taxonomy_meta_fields'));
        add_action('created_term', array($this, 'save_taxonomy_meta_fields'), 10, 3);
        add_action('edit_term', array($this, 'save_taxonomy_meta_fields'), 10, 3);
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
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
            <label for="note_group"><?php _e('Група нотки', 'parfume-reviews'); ?></label>
            <select name="note_group" id="note_group">
                <option value=""><?php _e('Изберете група', 'parfume-reviews'); ?></option>
                <option value="citrus"><?php _e('Цитрусови', 'parfume-reviews'); ?></option>
                <option value="floral"><?php _e('Цветни', 'parfume-reviews'); ?></option>
                <option value="woody"><?php _e('Дървесни', 'parfume-reviews'); ?></option>
                <option value="oriental"><?php _e('Ориенталски', 'parfume-reviews'); ?></option>
                <option value="fresh"><?php _e('Свежи', 'parfume-reviews'); ?></option>
                <option value="spicy"><?php _e('Пикантни', 'parfume-reviews'); ?></option>
                <option value="fruity"><?php _e('Плодови', 'parfume-reviews'); ?></option>
                <option value="gourmand"><?php _e('Гурме', 'parfume-reviews'); ?></option>
            </select>
            <p class="description"><?php _e('Изберете към коя група принадлежи тази нотка', 'parfume-reviews'); ?></p>
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
                <label for="note_group"><?php _e('Група нотки', 'parfume-reviews'); ?></label>
            </th>
            <td>
                <select name="note_group" id="note_group">
                    <option value=""><?php _e('Изберете група', 'parfume-reviews'); ?></option>
                    <option value="citrus" <?php selected($note_group, 'citrus'); ?>><?php _e('Цитрусови', 'parfume-reviews'); ?></option>
                    <option value="floral" <?php selected($note_group, 'floral'); ?>><?php _e('Цветни', 'parfume-reviews'); ?></option>
                    <option value="woody" <?php selected($note_group, 'woody'); ?>><?php _e('Дървесни', 'parfume-reviews'); ?></option>
                    <option value="oriental" <?php selected($note_group, 'oriental'); ?>><?php _e('Ориенталски', 'parfume-reviews'); ?></option>
                    <option value="fresh" <?php selected($note_group, 'fresh'); ?>><?php _e('Свежи', 'parfume-reviews'); ?></option>
                    <option value="spicy" <?php selected($note_group, 'spicy'); ?>><?php _e('Пикантни', 'parfume-reviews'); ?></option>
                    <option value="fruity" <?php selected($note_group, 'fruity'); ?>><?php _e('Плодови', 'parfume-reviews'); ?></option>
                    <option value="gourmand" <?php selected($note_group, 'gourmand'); ?>><?php _e('Гурме', 'parfume-reviews'); ?></option>
                </select>
                <p class="description"><?php _e('Изберете към коя група принадлежи тази нотка', 'parfume-reviews'); ?></p>
            </td>
        </tr>
        <?php
    }
    
    /**
     * Записва meta полетата на таксономиите
     */
    public function save_taxonomy_meta_fields($term_id, $tt_id, $taxonomy) {
        $field_name = $taxonomy . '-image-id';
        if (isset($_POST[$field_name])) {
            update_term_meta($term_id, $field_name, absint($_POST[$field_name]));
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
        if (strpos($hook, 'edit-tags.php') !== false || strpos($hook, 'term.php') !== false) {
            wp_enqueue_media();
            wp_enqueue_script('parfume-taxonomy-media', PARFUME_REVIEWS_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), PARFUME_REVIEWS_VERSION, true);
        }
    }
}