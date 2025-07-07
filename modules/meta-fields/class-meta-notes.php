<?php
/**
 * Notes Meta Fields Class
 * 
 * Handles fragrance notes and composition meta fields for parfumes
 */

if (!defined('ABSPATH')) {
    exit;
}

class Parfume_Meta_Notes {
    
    public function __construct() {
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post', array($this, 'save_meta_fields'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_parfume_search_notes', array($this, 'search_notes'));
        add_action('wp_ajax_parfume_get_note_info', array($this, 'get_note_info'));
    }
    
    /**
     * Add meta boxes
     */
    public function add_meta_boxes() {
        add_meta_box(
            'parfume_notes_composition',
            __('Състав на аромата', 'parfume-catalog'),
            array($this, 'render_notes_composition_meta_box'),
            'parfumes',
            'normal',
            'high'
        );
        
        add_meta_box(
            'parfume_main_notes',
            __('Основни ароматни нотки', 'parfume-catalog'),
            array($this, 'render_main_notes_meta_box'),
            'parfumes',
            'side',
            'default'
        );
    }
    
    /**
     * Enqueue admin scripts
     */
    public function enqueue_scripts($hook) {
        global $post_type;
        
        if (($hook === 'post.php' || $hook === 'post-new.php') && $post_type === 'parfumes') {
            wp_enqueue_script('jquery-ui-autocomplete');
            wp_enqueue_script('jquery-ui-sortable');
        }
    }
    
    /**
     * Render notes composition meta box
     */
    public function render_notes_composition_meta_box($post) {
        wp_nonce_field('parfume_notes_meta_nonce', 'parfume_notes_meta_nonce_field');
        
        // Get saved values
        $top_notes = get_post_meta($post->ID, '_parfume_top_notes', true) ?: array();
        $heart_notes = get_post_meta($post->ID, '_parfume_heart_notes', true) ?: array();
        $base_notes = get_post_meta($post->ID, '_parfume_base_notes', true) ?: array();
        
        if (!is_array($top_notes)) $top_notes = array();
        if (!is_array($heart_notes)) $heart_notes = array();
        if (!is_array($base_notes)) $base_notes = array();
        ?>
        <div class="notes-composition-container">
            <div class="composition-pyramid">
                <div class="pyramid-section top-notes">
                    <div class="section-header">
                        <h4><?php _e('Връхни нотки', 'parfume-catalog'); ?></h4>
                        <p class="description"><?php _e('Първите впечатления от аромата (5-15 минути)', 'parfume-catalog'); ?></p>
                    </div>
                    
                    <div class="notes-list" id="top-notes-list">
                        <?php foreach ($top_notes as $note_id): ?>
                            <?php $this->render_note_item('top', $note_id); ?>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="add-note-section">
                        <input type="text" 
                               class="note-search" 
                               placeholder="<?php _e('Търсете и добавете нотка...', 'parfume-catalog'); ?>" 
                               data-note-type="top" />
                        <button type="button" class="button add-note-btn" data-note-type="top">
                            <?php _e('Добави', 'parfume-catalog'); ?>
                        </button>
                    </div>
                </div>
                
                <div class="pyramid-section heart-notes">
                    <div class="section-header">
                        <h4><?php _e('Средни нотки (Сърце)', 'parfume-catalog'); ?></h4>
                        <p class="description"><?php _e('Основният характер на аромата (2-4 часа)', 'parfume-catalog'); ?></p>
                    </div>
                    
                    <div class="notes-list" id="heart-notes-list">
                        <?php foreach ($heart_notes as $note_id): ?>
                            <?php $this->render_note_item('heart', $note_id); ?>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="add-note-section">
                        <input type="text" 
                               class="note-search" 
                               placeholder="<?php _e('Търсете и добавете нотка...', 'parfume-catalog'); ?>" 
                               data-note-type="heart" />
                        <button type="button" class="button add-note-btn" data-note-type="heart">
                            <?php _e('Добави', 'parfume-catalog'); ?>
                        </button>
                    </div>
                </div>
                
                <div class="pyramid-section base-notes">
                    <div class="section-header">
                        <h4><?php _e('Базови нотки', 'parfume-catalog'); ?></h4>
                        <p class="description"><?php _e('Дълготрайната основа на аромата (6+ часа)', 'parfume-catalog'); ?></p>
                    </div>
                    
                    <div class="notes-list" id="base-notes-list">
                        <?php foreach ($base_notes as $note_id): ?>
                            <?php $this->render_note_item('base', $note_id); ?>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="add-note-section">
                        <input type="text" 
                               class="note-search" 
                               placeholder="<?php _e('Търсете и добавете нотка...', 'parfume-catalog'); ?>" 
                               data-note-type="base" />
                        <button type="button" class="button add-note-btn" data-note-type="base">
                            <?php _e('Добави', 'parfume-catalog'); ?>
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="notes-helper">
                <h4><?php _e('Помощник за избор на нотки', 'parfume-catalog'); ?></h4>
                <div class="note-groups">
                    <?php $this->render_note_groups(); ?>
                </div>
            </div>
        </div>
        
        <style>
        .notes-composition-container {
            margin-top: 15px;
        }
        
        .composition-pyramid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 25px;
            margin-bottom: 30px;
        }
        
        .pyramid-section {
            border: 2px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            background: #fafafa;
            position: relative;
        }
        
        .pyramid-section.top-notes {
            border-color: #ffeb3b;
            background: #fffde7;
        }
        
        .pyramid-section.heart-notes {
            border-color: #ff5722;
            background: #fff3e0;
        }
        
        .pyramid-section.base-notes {
            border-color: #795548;
            background: #efebe9;
        }
        
        .section-header h4 {
            margin: 0 0 5px 0;
            color: #333;
        }
        
        .section-header .description {
            margin: 0 0 15px 0;
            font-size: 13px;
            color: #666;
            font-style: italic;
        }
        
        .notes-list {
            min-height: 60px;
            margin-bottom: 15px;
            padding: 10px;
            border: 1px dashed #ccc;
            border-radius: 4px;
            background: #fff;
        }
        
        .note-item {
            display: inline-flex;
            align-items: center;
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 20px;
            padding: 5px 12px;
            margin: 3px;
            cursor: move;
            position: relative;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .note-item:hover {
            border-color: #0073aa;
            box-shadow: 0 2px 5px rgba(0,0,0,0.15);
        }
        
        .note-icon {
            width: 16px;
            height: 16px;
            margin-right: 6px;
            border-radius: 50%;
            background-size: cover;
            background-position: center;
            background-color: #f0f0f0;
            flex-shrink: 0;
        }
        
        .note-name {
            font-size: 13px;
            font-weight: 500;
            margin-right: 8px;
        }
        
        .note-group-badge {
            font-size: 10px;
            background: #0073aa;
            color: white;
            padding: 2px 6px;
            border-radius: 10px;
            margin-right: 6px;
        }
        
        .remove-note {
            background: #dc3232;
            color: white;
            border: none;
            border-radius: 50%;
            width: 18px;
            height: 18px;
            font-size: 12px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .remove-note:hover {
            background: #a02622;
        }
        
        .add-note-section {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        
        .note-search {
            flex: 1;
            max-width: 300px;
        }
        
        .notes-helper {
            background: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 20px;
        }
        
        .notes-helper h4 {
            margin: 0 0 15px 0;
        }
        
        .note-groups {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
        }
        
        .note-group {
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 10px;
        }
        
        .note-group h5 {
            margin: 0 0 8px 0;
            font-size: 13px;
            color: #0073aa;
        }
        
        .note-group-notes {
            font-size: 11px;
            color: #666;
            line-height: 1.4;
        }
        
        .sortable-placeholder {
            border: 2px dashed #0073aa;
            background: rgba(0, 115, 170, 0.1);
            height: 30px;
            margin: 3px;
            border-radius: 15px;
        }
        
        .ui-autocomplete {
            max-height: 200px;
            overflow-y: auto;
            z-index: 10000;
        }
        
        .ui-autocomplete .ui-menu-item .ui-state-focus {
            background: #0073aa;
            color: white;
        }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            // Make notes lists sortable
            $('.notes-list').sortable({
                connectWith: '.notes-list',
                placeholder: 'sortable-placeholder',
                tolerance: 'pointer',
                update: function(event, ui) {
                    updateHiddenFields();
                }
            });
            
            // Autocomplete for note search
            $('.note-search').autocomplete({
                source: function(request, response) {
                    $.post(ajaxurl, {
                        action: 'parfume_search_notes',
                        nonce: '<?php echo wp_create_nonce('parfume_notes_action'); ?>',
                        term: request.term
                    }, function(data) {
                        response(data.data || []);
                    });
                },
                minLength: 2,
                select: function(event, ui) {
                    var noteType = $(this).data('note-type');
                    addNoteToList(noteType, ui.item.id, ui.item.label, ui.item.group, ui.item.icon);
                    $(this).val('');
                    return false;
                }
            });
            
            // Add note button
            $('.add-note-btn').click(function() {
                var noteType = $(this).data('note-type');
                var searchInput = $(this).siblings('.note-search');
                var term = searchInput.val().trim();
                
                if (term) {
                    // Try to find exact match or create new note
                    $.post(ajaxurl, {
                        action: 'parfume_search_notes',
                        nonce: '<?php echo wp_create_nonce('parfume_notes_action'); ?>',
                        term: term,
                        exact: true
                    }, function(data) {
                        if (data.data && data.data.length > 0) {
                            var note = data.data[0];
                            addNoteToList(noteType, note.id, note.label, note.group, note.icon);
                        }
                        searchInput.val('');
                    });
                }
            });
            
            // Remove note
            $(document).on('click', '.remove-note', function() {
                $(this).closest('.note-item').remove();
                updateHiddenFields();
            });
            
            function addNoteToList(noteType, noteId, noteName, noteGroup, noteIcon) {
                var listId = noteType + '-notes-list';
                var existingNote = $('#' + listId + ' .note-item[data-note-id="' + noteId + '"]');
                
                if (existingNote.length > 0) {
                    return; // Note already exists
                }
                
                var iconHtml = noteIcon ? '<div class="note-icon" style="background-image: url(' + noteIcon + ');"></div>' : '<div class="note-icon"></div>';
                var groupBadge = noteGroup ? '<span class="note-group-badge">' + noteGroup + '</span>' : '';
                
                var noteHtml = '<div class="note-item" data-note-id="' + noteId + '">' +
                    iconHtml +
                    '<span class="note-name">' + noteName + '</span>' +
                    groupBadge +
                    '<button type="button" class="remove-note">×</button>' +
                    '</div>';
                
                $('#' + listId).append(noteHtml);
                updateHiddenFields();
            }
            
            function updateHiddenFields() {
                // Update top notes
                var topNotes = [];
                $('#top-notes-list .note-item').each(function() {
                    topNotes.push($(this).data('note-id'));
                });
                updateOrCreateHiddenField('parfume_top_notes', topNotes);
                
                // Update heart notes
                var heartNotes = [];
                $('#heart-notes-list .note-item').each(function() {
                    heartNotes.push($(this).data('note-id'));
                });
                updateOrCreateHiddenField('parfume_heart_notes', heartNotes);
                
                // Update base notes
                var baseNotes = [];
                $('#base-notes-list .note-item').each(function() {
                    baseNotes.push($(this).data('note-id'));
                });
                updateOrCreateHiddenField('parfume_base_notes', baseNotes);
            }
            
            function updateOrCreateHiddenField(name, values) {
                // Remove existing hidden fields
                $('input[name="' + name + '[]"]').remove();
                
                // Add new hidden fields
                values.forEach(function(value) {
                    $('<input>').attr({
                        type: 'hidden',
                        name: name + '[]',
                        value: value
                    }).appendTo('.notes-composition-container');
                });
            }
            
            // Initialize hidden fields
            updateHiddenFields();
        });
        </script>
        <?php
    }
    
    /**
     * Render main notes meta box
     */
    public function render_main_notes_meta_box($post) {
        $main_notes = get_post_meta($post->ID, '_parfume_main_notes', true) ?: array();
        
        if (!is_array($main_notes)) {
            $main_notes = array();
        }
        ?>
        <div class="main-notes-container">
            <p class="description"><?php _e('Изберете 3-5 основни нотки, които най-добре характеризират аромата', 'parfume-catalog'); ?></p>
            
            <div class="main-notes-list" id="main-notes-list">
                <?php foreach ($main_notes as $note_id): ?>
                    <?php $this->render_note_item('main', $note_id); ?>
                <?php endforeach; ?>
            </div>
            
            <div class="add-main-note">
                <input type="text" 
                       id="main-note-search" 
                       class="note-search widefat" 
                       placeholder="<?php _e('Търсете основна нотка...', 'parfume-catalog'); ?>" 
                       data-note-type="main" />
            </div>
        </div>
        
        <style>
        .main-notes-container {
            padding: 10px 0;
        }
        
        .main-notes-list {
            min-height: 80px;
            margin: 15px 0;
            padding: 10px;
            border: 1px dashed #ccc;
            border-radius: 4px;
            background: #fafafa;
        }
        
        .main-notes-list .note-item {
            margin-bottom: 8px;
            width: 100%;
            justify-content: space-between;
            border-radius: 4px;
        }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            // Main notes autocomplete
            $('#main-note-search').autocomplete({
                source: function(request, response) {
                    $.post(ajaxurl, {
                        action: 'parfume_search_notes',
                        nonce: '<?php echo wp_create_nonce('parfume_notes_action'); ?>',
                        term: request.term
                    }, function(data) {
                        response(data.data || []);
                    });
                },
                minLength: 2,
                select: function(event, ui) {
                    addMainNote(ui.item.id, ui.item.label, ui.item.group, ui.item.icon);
                    $(this).val('');
                    return false;
                }
            });
            
            function addMainNote(noteId, noteName, noteGroup, noteIcon) {
                var existingNote = $('#main-notes-list .note-item[data-note-id="' + noteId + '"]');
                
                if (existingNote.length > 0) {
                    return;
                }
                
                // Limit to 5 main notes
                if ($('#main-notes-list .note-item').length >= 5) {
                    alert('<?php _e('Можете да добавите максимум 5 основни нотки', 'parfume-catalog'); ?>');
                    return;
                }
                
                var iconHtml = noteIcon ? '<div class="note-icon" style="background-image: url(' + noteIcon + ');"></div>' : '<div class="note-icon"></div>';
                var groupBadge = noteGroup ? '<span class="note-group-badge">' + noteGroup + '</span>' : '';
                
                var noteHtml = '<div class="note-item" data-note-id="' + noteId + '">' +
                    iconHtml +
                    '<span class="note-name">' + noteName + '</span>' +
                    groupBadge +
                    '<button type="button" class="remove-note">×</button>' +
                    '</div>';
                
                $('#main-notes-list').append(noteHtml);
                updateMainNotesField();
            }
            
            // Remove main note
            $(document).on('click', '#main-notes-list .remove-note', function() {
                $(this).closest('.note-item').remove();
                updateMainNotesField();
            });
            
            function updateMainNotesField() {
                var mainNotes = [];
                $('#main-notes-list .note-item').each(function() {
                    mainNotes.push($(this).data('note-id'));
                });
                
                // Remove existing hidden fields
                $('input[name="parfume_main_notes[]"]').remove();
                
                // Add new hidden fields
                mainNotes.forEach(function(value) {
                    $('<input>').attr({
                        type: 'hidden',
                        name: 'parfume_main_notes[]',
                        value: value
                    }).appendTo('.main-notes-container');
                });
            }
            
            // Initialize main notes field
            updateMainNotesField();
        });
        </script>
        <?php
    }
    
    /**
     * Render individual note item
     */
    private function render_note_item($type, $note_id) {
        $term = get_term($note_id, 'parfume_notes');
        if (!$term || is_wp_error($term)) {
            return;
        }
        
        $note_group = get_term_meta($note_id, 'note_group', true);
        $note_icon = get_term_meta($note_id, 'note_icon', true);
        
        $icon_html = '';
        if ($note_icon) {
            $icon_url = wp_get_attachment_image_url($note_icon, 'thumbnail');
            if ($icon_url) {
                $icon_html = '<div class="note-icon" style="background-image: url(' . esc_url($icon_url) . ');"></div>';
            }
        }
        
        if (!$icon_html) {
            $icon_html = '<div class="note-icon"></div>';
        }
        
        $group_badge = $note_group ? '<span class="note-group-badge">' . esc_html($note_group) . '</span>' : '';
        ?>
        <div class="note-item" data-note-id="<?php echo esc_attr($note_id); ?>">
            <?php echo $icon_html; ?>
            <span class="note-name"><?php echo esc_html($term->name); ?></span>
            <?php echo $group_badge; ?>
            <button type="button" class="remove-note">×</button>
        </div>
        <?php
    }
    
    /**
     * Render note groups helper
     */
    private function render_note_groups() {
        $note_groups = array(
            'цветни' => array('Роза', 'Жасмин', 'Лилия', 'Иланг-иланг', 'Лавандула'),
            'плодови' => array('Бергамот', 'Лимон', 'Портокал', 'Ябълка', 'Праскова'),
            'зелени' => array('Базилик', 'Мента', 'Листа от смокиня', 'Петрушка', 'Розмарин'),
            'дървесни' => array('Сандалово дърво', 'Кедър', 'Ветивер', 'Пачули', 'Агарово дърво'),
            'ориенталски' => array('Ванилия', 'Амбра', 'Мускус', 'Тамян', 'Мирра'),
            'ароматни' => array('Анис', 'Канела', 'Карамфил', 'Джинджифил', 'Черен пипер'),
            'гурме' => array('Шоколад', 'Карамел', 'Мед', 'Кафе', 'Бадем'),
            'морски' => array('Морска сол', 'Водорасли', 'Морски бриз', 'Озон', 'Амбра гриз')
        );
        
        foreach ($note_groups as $group_name => $notes) {
            ?>
            <div class="note-group">
                <h5><?php echo esc_html(ucfirst($group_name)); ?></h5>
                <div class="note-group-notes">
                    <?php echo esc_html(implode(', ', array_slice($notes, 0, 5))); ?>
                    <?php if (count($notes) > 5): ?>
                        <span style="color: #999;">... (+<?php echo count($notes) - 5; ?>)</span>
                    <?php endif; ?>
                </div>
            </div>
            <?php
        }
    }
    
    /**
     * Save meta fields
     */
    public function save_meta_fields($post_id) {
        // Check if nonce is valid
        if (!isset($_POST['parfume_notes_meta_nonce_field']) || 
            !wp_verify_nonce($_POST['parfume_notes_meta_nonce_field'], 'parfume_notes_meta_nonce')) {
            return;
        }
        
        // Check if user has permission
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Check if autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        // Check post type
        if (get_post_type($post_id) !== 'parfumes') {
            return;
        }
        
        // Save notes composition
        $note_fields = array(
            'parfume_top_notes',
            'parfume_heart_notes',
            'parfume_base_notes',
            'parfume_main_notes'
        );
        
        foreach ($note_fields as $field) {
            if (isset($_POST[$field])) {
                $notes = array_map('absint', $_POST[$field]);
                $notes = array_filter($notes); // Remove empty values
                update_post_meta($post_id, '_' . $field, $notes);
            } else {
                update_post_meta($post_id, '_' . $field, array());
            }
        }
        
        // Update post terms based on all notes
        $all_notes = array();
        foreach ($note_fields as $field) {
            if (isset($_POST[$field])) {
                $all_notes = array_merge($all_notes, array_map('absint', $_POST[$field]));
            }
        }
        
        $all_notes = array_unique($all_notes);
        wp_set_post_terms($post_id, $all_notes, 'parfume_notes');
    }
    
    /**
     * Search notes via AJAX
     */
    public function search_notes() {
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => __('Недостатъчни права', 'parfume-catalog')));
        }
        
        check_ajax_referer('parfume_notes_action', 'nonce');
        
        $term = sanitize_text_field($_POST['term']);
        $exact = isset($_POST['exact']) ? (bool)$_POST['exact'] : false;
        
        $args = array(
            'taxonomy' => 'parfume_notes',
            'hide_empty' => false,
            'number' => 20
        );
        
        if ($exact) {
            $args['name'] = $term;
        } else {
            $args['name__like'] = $term;
        }
        
        $terms = get_terms($args);
        $results = array();
        
        foreach ($terms as $term_obj) {
            $note_group = get_term_meta($term_obj->term_id, 'note_group', true);
            $note_icon = get_term_meta($term_obj->term_id, 'note_icon', true);
            $icon_url = '';
            
            if ($note_icon) {
                $icon_url = wp_get_attachment_image_url($note_icon, 'thumbnail');
            }
            
            $results[] = array(
                'id' => $term_obj->term_id,
                'label' => $term_obj->name,
                'value' => $term_obj->name,
                'group' => $note_group,
                'icon' => $icon_url
            );
        }
        
        wp_send_json_success($results);
    }
    
    /**
     * Get note info via AJAX
     */
    public function get_note_info() {
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => __('Недостатъчни права', 'parfume-catalog')));
        }
        
        check_ajax_referer('parfume_notes_action', 'nonce');
        
        $note_id = absint($_POST['note_id']);
        $term = get_term($note_id, 'parfume_notes');
        
        if (!$term || is_wp_error($term)) {
            wp_send_json_error(array('message' => __('Нотката не е намерена', 'parfume-catalog')));
        }
        
        $note_group = get_term_meta($note_id, 'note_group', true);
        $note_icon = get_term_meta($note_id, 'note_icon', true);
        $icon_url = '';
        
        if ($note_icon) {
            $icon_url = wp_get_attachment_image_url($note_icon, 'thumbnail');
        }
        
        wp_send_json_success(array(
            'id' => $term->term_id,
            'name' => $term->name,
            'description' => $term->description,
            'group' => $note_group,
            'icon' => $icon_url
        ));
    }
    
    /**
     * Get parfume notes composition
     */
    public static function get_notes_composition($post_id) {
        return array(
            'top_notes' => get_post_meta($post_id, '_parfume_top_notes', true) ?: array(),
            'heart_notes' => get_post_meta($post_id, '_parfume_heart_notes', true) ?: array(),
            'base_notes' => get_post_meta($post_id, '_parfume_base_notes', true) ?: array(),
            'main_notes' => get_post_meta($post_id, '_parfume_main_notes', true) ?: array()
        );
    }
    
    /**
     * Get formatted notes for display
     */
    public static function get_formatted_notes($note_ids) {
        if (empty($note_ids) || !is_array($note_ids)) {
            return array();
        }
        
        $formatted_notes = array();
        
        foreach ($note_ids as $note_id) {
            $term = get_term($note_id, 'parfume_notes');
            if ($term && !is_wp_error($term)) {
                $note_group = get_term_meta($note_id, 'note_group', true);
                $note_icon = get_term_meta($note_id, 'note_icon', true);
                $icon_url = '';
                
                if ($note_icon) {
                    $icon_url = wp_get_attachment_image_url($note_icon, 'thumbnail');
                }
                
                $formatted_notes[] = array(
                    'id' => $term->term_id,
                    'name' => $term->name,
                    'slug' => $term->slug,
                    'group' => $note_group,
                    'icon_url' => $icon_url,
                    'link' => get_term_link($term)
                );
            }
        }
        
        return $formatted_notes;
    }
    
    /**
     * Get notes by group
     */
    public static function get_notes_by_group($note_ids) {
        $notes = self::get_formatted_notes($note_ids);
        $grouped_notes = array();
        
        foreach ($notes as $note) {
            $group = $note['group'] ?: __('Други', 'parfume-catalog');
            if (!isset($grouped_notes[$group])) {
                $grouped_notes[$group] = array();
            }
            $grouped_notes[$group][] = $note;
        }
        
        return $grouped_notes;
    }
    
    /**
     * Check if parfume has notes
     */
    public static function has_notes($post_id) {
        $composition = self::get_notes_composition($post_id);
        return !empty($composition['top_notes']) || 
               !empty($composition['heart_notes']) || 
               !empty($composition['base_notes']);
    }
    
    /**
     * Get all notes for parfume
     */
    public static function get_all_parfume_notes($post_id) {
        $composition = self::get_notes_composition($post_id);
        $all_notes = array_merge(
            $composition['top_notes'],
            $composition['heart_notes'],
            $composition['base_notes']
        );
        
        return array_unique($all_notes);
    }
}

// Initialize the notes meta fields
new Parfume_Meta_Notes();