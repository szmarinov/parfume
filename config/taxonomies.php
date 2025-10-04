<?php
/**
 * Taxonomy Meta Fields Handler
 * Manages custom meta fields for taxonomies (especially notes group field)
 */

if (!defined('ABSPATH')) {
    exit;
}

class Parfume_Taxonomy_Meta {
    
    public function __construct() {
        // Add form fields
        add_action('notes_add_form_fields', [$this, 'add_group_field']);
        add_action('notes_edit_form_fields', [$this, 'edit_group_field']);
        
        // Save meta
        add_action('created_notes', [$this, 'save_group_field']);
        add_action('edited_notes', [$this, 'save_group_field']);
        
        // Add column to taxonomy list
        add_filter('manage_edit-notes_columns', [$this, 'add_group_column']);
        add_filter('manage_notes_custom_column', [$this, 'show_group_column'], 10, 3);
    }
    
    /**
     * Add group field to add term form
     */
    public function add_group_field() {
        ?>
        <div class="form-field term-group-wrap">
            <label for="note-group">Група</label>
            <select name="note_group" id="note-group" class="postform">
                <option value="">Изберете група</option>
                <option value="дървесни">Дървесни</option>
                <option value="цветни">Цветни</option>
                <option value="ориенталски">Ориенталски</option>
                <option value="плодови">Плодови</option>
                <option value="зелени">Зелени</option>
                <option value="гурме">Гурме</option>
                <option value="морски">Морски</option>
                <option value="ароматни">Ароматни</option>
            </select>
            <p class="description">Изберете групата, към която спада нотката</p>
        </div>
        <?php
    }
    
    /**
     * Add group field to edit term form
     */
    public function edit_group_field($term) {
        $group = get_term_meta($term->term_id, 'note_group', true);
        ?>
        <tr class="form-field term-group-wrap">
            <th scope="row">
                <label for="note-group">Група</label>
            </th>
            <td>
                <select name="note_group" id="note-group" class="postform">
                    <option value="">Изберете група</option>
                    <option value="дървесни" <?php selected($group, 'дървесни'); ?>>Дървесни</option>
                    <option value="цветни" <?php selected($group, 'цветни'); ?>>Цветни</option>
                    <option value="ориенталски" <?php selected($group, 'ориенталски'); ?>>Ориенталски</option>
                    <option value="плодови" <?php selected($group, 'плодови'); ?>>Плодови</option>
                    <option value="зелени" <?php selected($group, 'зелени'); ?>>Зелени</option>
                    <option value="гурме" <?php selected($group, 'гурме'); ?>>Гурме</option>
                    <option value="морски" <?php selected($group, 'морски'); ?>>Морски</option>
                    <option value="ароматни" <?php selected($group, 'ароматни'); ?>>Ароматни</option>
                </select>
                <p class="description">Изберете групата, към която спада нотката</p>
            </td>
        </tr>
        <?php
    }
    
    /**
     * Save group field
     */
    public function save_group_field($term_id) {
        if (isset($_POST['note_group'])) {
            update_term_meta($term_id, 'note_group', sanitize_text_field($_POST['note_group']));
        }
    }
    
    /**
     * Add group column to notes list
     */
    public function add_group_column($columns) {
        $columns['group'] = 'Група';
        return $columns;
    }
    
    /**
     * Show group in column
     */
    public function show_group_column($content, $column_name, $term_id) {
        if ($column_name === 'group') {
            $group = get_term_meta($term_id, 'note_group', true);
            return $group ? esc_html($group) : '—';
        }
        return $content;
    }
}

// Initialize
new Parfume_Taxonomy_Meta();