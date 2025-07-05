<?php
/**
 * Notes Taxonomy Class
 * 
 * @package Parfume_Reviews
 * @subpackage Taxonomies
 */

namespace Parfume_Reviews\Taxonomies;

use Parfume_Reviews\Utils\Taxonomy_Base;

/**
 * Notes taxonomy class
 */
class Notes extends Taxonomy_Base {
    
    /**
     * Initialize the taxonomy
     */
    public function init() {
        $this->taxonomy = 'notes';
        $this->post_types = array('parfume');
        $this->configure_taxonomy();
        $this->register();
    }
    
    /**
     * Configure taxonomy arguments
     */
    protected function configure_taxonomy() {
        $this->args = array(
            'labels' => array(
                'name' => __('Ароматни нотки', 'parfume-reviews'),
                'singular_name' => __('Ароматна нотка', 'parfume-reviews'),
                'search_items' => __('Търсене в нотките', 'parfume-reviews'),
                'all_items' => __('Всички нотки', 'parfume-reviews'),
                'edit_item' => __('Редактиране на нотка', 'parfume-reviews'),
                'update_item' => __('Обновяване на нотка', 'parfume-reviews'),
                'add_new_item' => __('Добавяне на нова нотка', 'parfume-reviews'),
                'new_item_name' => __('Име на нова нотка', 'parfume-reviews'),
                'menu_name' => __('Ароматни нотки', 'parfume-reviews'),
                'popular_items' => __('Популярни нотки', 'parfume-reviews'),
                'separate_items_with_commas' => __('Разделете нотките със запетаи', 'parfume-reviews'),
                'add_or_remove_items' => __('Добавяне или премахване на нотки', 'parfume-reviews'),
                'choose_from_most_used' => __('Изберете от най-използваните нотки', 'parfume-reviews'),
                'not_found' => __('Няма намерени нотки', 'parfume-reviews'),
                'back_to_items' => __('← Назад към нотките', 'parfume-reviews'),
                'item_updated' => __('Нотката е обновена.', 'parfume-reviews'),
                'item_added' => __('Нотката е добавена.', 'parfume-reviews'),
                'item_deleted' => __('Нотката е изтрита.', 'parfume-reviews'),
            ),
            'hierarchical' => false,
            'show_ui' => true,
            'show_admin_column' => true,
            'query_var' => true,
            'rewrite' => array(
                'slug' => $this->get_taxonomy_slug('notes', 'notes'),
                'with_front' => false,
                'hierarchical' => false
            ),
            'show_in_rest' => true,
            'public' => true,
            'publicly_queryable' => true,
            'show_in_nav_menus' => true,
            'show_tagcloud' => true,
            'meta_box_cb' => 'post_tags_meta_box',
            'show_in_menu' => true,
            'capabilities' => array(
                'manage_terms' => 'manage_categories',
                'edit_terms' => 'edit_categories',
                'delete_terms' => 'delete_categories',
                'assign_terms' => 'edit_posts',
            ),
        );
    }
    
    /**
     * Add taxonomy-specific meta fields
     */
    public function add_taxonomy_meta_fields() {
        // Image field
        add_action($this->taxonomy . '_add_form_fields', array($this, 'add_image_field'), 10, 2);
        add_action($this->taxonomy . '_edit_form_fields', array($this, 'edit_image_field'), 10, 2);
        
        // Note group field
        add_action($this->taxonomy . '_add_form_fields', array($this, 'add_note_group_field'), 10, 2);
        add_action($this->taxonomy . '_edit_form_fields', array($this, 'edit_note_group_field'), 10, 2);
        
        // Save fields
        add_action('created_' . $this->taxonomy, array($this, 'save_meta_fields'), 10, 3);
        add_action('edit_' . $this->taxonomy, array($this, 'save_meta_fields'), 10, 3);
    }
    
    /**
     * Add image field for new terms
     */
    public function add_image_field($taxonomy) {
        $field_name = $this->taxonomy . '-image-id';
        $wrapper_id = $this->taxonomy . '-image-wrapper';
        ?>
        <div class="form-field term-group">
            <label for="<?php echo esc_attr($field_name); ?>"><?php _e('Изображение на нотка', 'parfume-reviews'); ?></label>
            <input type="hidden" id="<?php echo esc_attr($field_name); ?>" name="<?php echo esc_attr($field_name); ?>" class="custom_media_url" value="">
            <div id="<?php echo esc_attr($wrapper_id); ?>"></div>
            <p>
                <input type="button" class="button button-secondary pr_tax_media_button" 
                       data-field="<?php echo esc_attr($field_name); ?>" 
                       data-wrapper="<?php echo esc_attr($wrapper_id); ?>" 
                       value="<?php _e('Добави изображение', 'parfume-reviews'); ?>" />
                <input type="button" class="button button-secondary pr_tax_media_remove" 
                       data-field="<?php echo esc_attr($field_name); ?>" 
                       data-wrapper="<?php echo esc_attr($wrapper_id); ?>" 
                       value="<?php _e('Премахни изображение', 'parfume-reviews'); ?>" />
            </p>
            <p class="description"><?php _e('Изберете изображение, което представя тази ароматна нотка.', 'parfume-reviews'); ?></p>
        </div>
        <?php
    }
    
    /**
     * Edit image field for existing terms
     */
    public function edit_image_field($term, $taxonomy) {
        $field_name = $this->taxonomy . '-image-id';
        $wrapper_id = $this->taxonomy . '-image-wrapper';
        $image_id = get_term_meta($term->term_id, $field_name, true);
        ?>
        <tr class="form-field term-group-wrap">
            <th scope="row">
                <label for="<?php echo esc_attr($field_name); ?>"><?php _e('Изображение на нотка', 'parfume-reviews'); ?></label>
            </th>
            <td>
                <input type="hidden" id="<?php echo esc_attr($field_name); ?>" name="<?php echo esc_attr($field_name); ?>" value="<?php echo esc_attr($image_id); ?>">
                <div id="<?php echo esc_attr($wrapper_id); ?>">
                    <?php if ($image_id) { ?>
                        <?php echo wp_get_attachment_image($image_id, 'thumbnail'); ?>
                    <?php } ?>
                </div>
                <p>
                    <input type="button" class="button button-secondary pr_tax_media_button" 
                           data-field="<?php echo esc_attr($field_name); ?>" 
                           data-wrapper="<?php echo esc_attr($wrapper_id); ?>" 
                           value="<?php _e('Добави изображение', 'parfume-reviews'); ?>" />
                    <input type="button" class="button button-secondary pr_tax_media_remove" 
                           data-field="<?php echo esc_attr($field_name); ?>" 
                           data-wrapper="<?php echo esc_attr($wrapper_id); ?>" 
                           value="<?php _e('Премахни изображение', 'parfume-reviews'); ?>" />
                </p>
                <p class="description"><?php _e('Изберете изображение, което представя тази ароматна нотка.', 'parfume-reviews'); ?></p>
            </td>
        </tr>
        <?php
    }
    
    /**
     * Add note group field for new terms
     */
    public function add_note_group_field($taxonomy) {
        ?>
        <div class="form-field">
            <label for="note_group"><?php _e('Група на нотката', 'parfume-reviews'); ?></label>
            <select name="note_group" id="note_group">
                <option value=""><?php _e('Избери група', 'parfume-reviews'); ?></option>
                <option value="Цитрусови"><?php _e('Цитрусови', 'parfume-reviews'); ?></option>
                <option value="Флорални"><?php _e('Флорални', 'parfume-reviews'); ?></option>
                <option value="Дървесни"><?php _e('Дървесни', 'parfume-reviews'); ?></option>
                <option value="Ориенталски"><?php _e('Ориенталски', 'parfume-reviews'); ?></option>
                <option value="Свежи"><?php _e('Свежи', 'parfume-reviews'); ?></option>
                <option value="Подправки"><?php _e('Подправки', 'parfume-reviews'); ?></option>
                <option value="Сладки"><?php _e('Сладки', 'parfume-reviews'); ?></option>
                <option value="Животински"><?php _e('Животински', 'parfume-reviews'); ?></option>
                <option value="Ароматични"><?php _e('Ароматични', 'parfume-reviews'); ?></option>
                <option value="Балсамови"><?php _e('Балсамови', 'parfume-reviews'); ?></option>
                <option value="Синтетични"><?php _e('Синтетични', 'parfume-reviews'); ?></option>
            </select>
            <p class="description"><?php _e('Изберете към коя група спада тази нотка за по-добра организация.', 'parfume-reviews'); ?></p>
        </div>
        <?php
    }
    
    /**
     * Edit note group field for existing terms
     */
    public function edit_note_group_field($term, $taxonomy) {
        $group = get_term_meta($term->term_id, 'note_group', true);
        ?>
        <tr class="form-field">
            <th scope="row">
                <label for="note_group"><?php _e('Група на нотката', 'parfume-reviews'); ?></label>
            </th>
            <td>
                <select name="note_group" id="note_group">
                    <option value=""><?php _e('Избери група', 'parfume-reviews'); ?></option>
                    <option value="Цитрусови" <?php selected($group, 'Цитрусови'); ?>><?php _e('Цитрусови', 'parfume-reviews'); ?></option>
                    <option value="Флорални" <?php selected($group, 'Флорални'); ?>><?php _e('Флорални', 'parfume-reviews'); ?></option>
                    <option value="Дървесни" <?php selected($group, 'Дървесни'); ?>><?php _e('Дървесни', 'parfume-reviews'); ?></option>
                    <option value="Ориенталски" <?php selected($group, 'Ориенталски'); ?>><?php _e('Ориенталски', 'parfume-reviews'); ?></option>
                    <option value="Свежи" <?php selected($group, 'Свежи'); ?>><?php _e('Свежи', 'parfume-reviews'); ?></option>
                    <option value="Подправки" <?php selected($group, 'Подправки'); ?>><?php _e('Подправки', 'parfume-reviews'); ?></option>
                    <option value="Сладки" <?php selected($group, 'Сладки'); ?>><?php _e('Сладки', 'parfume-reviews'); ?></option>
                    <option value="Животински" <?php selected($group, 'Животински'); ?>><?php _e('Животински', 'parfume-reviews'); ?></option>
                    <option value="Ароматични" <?php selected($group, 'Ароматични'); ?>><?php _e('Ароматични', 'parfume-reviews'); ?></option>
                    <option value="Балсамови" <?php selected($group, 'Балсамови'); ?>><?php _e('Балсамови', 'parfume-reviews'); ?></option>
                    <option value="Синтетични" <?php selected($group, 'Синтетични'); ?>><?php _e('Синтетични', 'parfume-reviews'); ?></option>
                </select>
                <p class="description"><?php _e('Изберете към коя група спада тази нотка за по-добра организация.', 'parfume-reviews'); ?></p>
            </td>
        </tr>
        <?php
    }
    
    /**
     * Save taxonomy meta fields
     */
    public function save_meta_fields($term_id, $tt_id = '', $taxonomy = '') {
        // Save image
        $image_field = $this->taxonomy . '-image-id';
        if (isset($_POST[$image_field])) {
            update_term_meta($term_id, $image_field, absint($_POST[$image_field]));
        }
        
        // Save note group
        if (isset($_POST['note_group'])) {
            update_term_meta($term_id, 'note_group', sanitize_text_field($_POST['note_group']));
        }
    }
    
    /**
     * Add admin columns
     */
    public function add_admin_columns() {
        add_filter('manage_edit-' . $this->taxonomy . '_columns', array($this, 'add_columns'));
        add_filter('manage_' . $this->taxonomy . '_custom_column', array($this, 'populate_columns'), 10, 3);
    }
    
    /**
     * Add custom columns
     */
    public function add_columns($columns) {
        $new_columns = array();
        $new_columns['cb'] = $columns['cb'];
        $new_columns['image'] = __('Изображение', 'parfume-reviews');
        $new_columns['name'] = $columns['name'];
        $new_columns['note_group'] = __('Група', 'parfume-reviews');
        $new_columns['parfumes_count'] = __('Парфюми', 'parfume-reviews');
        $new_columns['slug'] = $columns['slug'];
        
        return $new_columns;
    }
    
    /**
     * Populate custom columns
     */
    public function populate_columns($content, $column_name, $term_id) {
        switch ($column_name) {
            case 'image':
                $image_id = get_term_meta($term_id, $this->taxonomy . '-image-id', true);
                if ($image_id) {
                    $image = wp_get_attachment_image($image_id, array(50, 50));
                    $content = $image;
                } else {
                    $content = '<span style="color: #666;">—</span>';
                }
                break;
                
            case 'note_group':
                $group = get_term_meta($term_id, 'note_group', true);
                if ($group) {
                    $content = '<span class="note-group-badge" style="background: #e1f5fe; color: #01579b; padding: 2px 8px; border-radius: 12px; font-size: 11px; font-weight: 500;">' . esc_html($group) . '</span>';
                } else {
                    $content = '<span style="color: #666;">—</span>';
                }
                break;
                
            case 'parfumes_count':
                $term = get_term($term_id);
                if ($term && !is_wp_error($term)) {
                    $count = $term->count;
                    if ($count > 0) {
                        $link = admin_url('edit.php?post_type=parfume&' . $this->taxonomy . '=' . $term->slug);
                        $content = '<a href="' . esc_url($link) . '">' . $count . '</a>';
                    } else {
                        $content = '0';
                    }
                }
                break;
        }
        
        return $content;
    }
    
    /**
     * Create default terms
     */
    public function create_default_terms() {
        $default_notes = array(
            'Ванилия' => 'Сладки',
            'Бергамот' => 'Цитрусови',
            'Мускус' => 'Животински',
            'Пачули' => 'Дървесни',
            'Жасмин' => 'Флорални',
            'Кедрово дърво' => 'Дървесни',
            'Сандалово дърво' => 'Дървесни',
            'Роза' => 'Флорални',
            'Зърна от тонка' => 'Сладки',
            'Кехлибар' => 'Балсамови',
            'Лавандула' => 'Ароматични',
            'Iso E Super' => 'Синтетични',
            'Лимон' => 'Цитрусови',
            'Портокал' => 'Цитрусови',
            'Грейпфрут' => 'Цитрусови',
            'Иланг-иланг' => 'Флорални',
            'Нерколи' => 'Флорални',
            'Фрезия' => 'Флорални',
            'Канела' => 'Подправки',
            'Карамфил' => 'Подправки',
            'Черен пипер' => 'Подправки',
            'Шафран' => 'Подправки',
            'Какао' => 'Сладки',
            'Кафе' => 'Ароматични',
            'Мед' => 'Сладки',
            'Карамел' => 'Сладки',
            'Праскова' => 'Свежи',
            'Ябълка' => 'Свежи',
            'Морски бриз' => 'Свежи',
            'Мента' => 'Свежи',
            'Евкалипт' => 'Ароматични',
            'Ладан' => 'Балсамови',
            'Мирра' => 'Балсамови',
            'Бензоин' => 'Балсамови',
            'Ветивер' => 'Дървесни',
            'Oud' => 'Дървесни',
            'Магнолия' => 'Флорални',
            'Божур' => 'Флорални',
            'Джинджифил' => 'Подправки',
            'Кардамон' => 'Подправки',
        );
        
        foreach ($default_notes as $note => $group) {
            if (!term_exists($note, $this->taxonomy)) {
                $term = wp_insert_term($note, $this->taxonomy);
                if (!is_wp_error($term) && isset($term['term_id'])) {
                    update_term_meta($term['term_id'], 'note_group', $group);
                }
            }
        }
    }
    
    /**
     * Get notes by group
     */
    public function get_notes_by_group($group = '') {
        $args = array(
            'taxonomy' => $this->taxonomy,
            'hide_empty' => false,
            'orderby' => 'name',
            'order' => 'ASC',
        );
        
        if (!empty($group)) {
            $args['meta_query'] = array(
                array(
                    'key' => 'note_group',
                    'value' => $group,
                    'compare' => '='
                )
            );
        }
        
        return get_terms($args);
    }
    
    /**
     * Get all note groups
     */
    public function get_note_groups() {
        global $wpdb;
        
        $groups = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT DISTINCT meta_value 
                FROM {$wpdb->termmeta} 
                WHERE meta_key = 'note_group' 
                AND meta_value != '' 
                ORDER BY meta_value ASC"
            )
        );
        
        return $groups;
    }
    
    /**
     * Get note statistics
     */
    public function get_notes_stats() {
        $terms = get_terms(array(
            'taxonomy' => $this->taxonomy,
            'hide_empty' => false,
        ));
        
        $stats = array(
            'total' => count($terms),
            'used' => 0,
            'by_group' => array(),
        );
        
        foreach ($terms as $term) {
            if ($term->count > 0) {
                $stats['used']++;
            }
            
            $group = get_term_meta($term->term_id, 'note_group', true);
            if (!empty($group)) {
                if (!isset($stats['by_group'][$group])) {
                    $stats['by_group'][$group] = 0;
                }
                $stats['by_group'][$group]++;
            }
        }
        
        return $stats;
    }
    
    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        if (strpos($hook, 'edit-tags.php') !== false && isset($_GET['taxonomy']) && $_GET['taxonomy'] === $this->taxonomy) {
            wp_enqueue_media();
            wp_enqueue_script(
                'parfume-notes-admin',
                PARFUME_REVIEWS_PLUGIN_URL . 'assets/js/taxonomy-admin.js',
                array('jquery'),
                PARFUME_REVIEWS_VERSION,
                true
            );
        }
    }
}