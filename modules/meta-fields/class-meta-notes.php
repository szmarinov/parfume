<?php
/**
 * Parfume Catalog Meta Notes
 * 
 * Управление на ароматни нотки и състав в мета полетата за парфюми
 * 
 * @package Parfume_Catalog
 * @since 1.0.0
 */

// Предотвратяване на директен достъп
if (!defined('ABSPATH')) {
    exit;
}

class Parfume_Catalog_Meta_Notes {
    
    /**
     * Конструктор
     */
    public function __construct() {
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post', array($this, 'save_meta_fields'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_ajax_parfume_search_notes', array($this, 'ajax_search_notes'));
        add_action('wp_ajax_parfume_get_note_info', array($this, 'ajax_get_note_info'));
        add_action('wp_ajax_parfume_add_note_to_composition', array($this, 'ajax_add_note_to_composition'));
        add_action('wp_ajax_parfume_import_notes_json', array($this, 'ajax_import_notes_json'));
    }
    
    /**
     * Добавяне на мета boxes
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
        
        add_meta_box(
            'parfume_notes_import',
            __('Импорт на нотки', 'parfume-catalog'),
            array($this, 'render_notes_import_meta_box'),
            'parfumes',
            'side',
            'low'
        );
    }
    
    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook) {
        global $post_type;
        
        if (($hook === 'post.php' || $hook === 'post-new.php') && $post_type === 'parfumes') {
            wp_enqueue_script('jquery-ui-autocomplete');
            wp_enqueue_script('jquery-ui-sortable');
            wp_enqueue_script('parfume-meta-notes', 
                PARFUME_CATALOG_PLUGIN_URL . 'assets/js/meta-notes.js', 
                array('jquery', 'jquery-ui-autocomplete', 'jquery-ui-sortable'), 
                PARFUME_CATALOG_VERSION, 
                true
            );
            
            wp_localize_script('parfume-meta-notes', 'parfumeMetaNotes', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('parfume_meta_notes'),
                'texts' => array(
                    'search_placeholder' => __('Търсете нотка...', 'parfume-catalog'),
                    'note_added' => __('Нотката е добавена', 'parfume-catalog'),
                    'note_exists' => __('Тази нотка вече е добавена в този слой', 'parfume-catalog'),
                    'note_not_found' => __('Нотката не е намерена', 'parfume-catalog'),
                    'confirm_remove' => __('Сигурни ли сте, че искате да премахнете тази нотка?', 'parfume-catalog'),
                    'import_success' => __('Нотките са импортирани успешно', 'parfume-catalog'),
                    'import_error' => __('Грешка при импортиране', 'parfume-catalog'),
                    'invalid_json' => __('Невалиден JSON формат', 'parfume-catalog')
                )
            ));
            
            wp_enqueue_style('parfume-meta-notes', 
                PARFUME_CATALOG_PLUGIN_URL . 'assets/css/meta-notes.css', 
                array(), 
                PARFUME_CATALOG_VERSION
            );
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
        ?>
        <div class="notes-composition-container">
            <div class="composition-pyramid">
                <!-- Top Notes -->
                <div class="notes-layer top-notes">
                    <div class="layer-header">
                        <h4>
                            <span class="layer-icon">🌿</span>
                            <?php _e('Връхни нотки', 'parfume-catalog'); ?>
                            <span class="layer-description"><?php _e('(първо усещане, 5-15 мин)', 'parfume-catalog'); ?></span>
                        </h4>
                        <div class="layer-actions">
                            <input type="text" 
                                   class="note-search" 
                                   data-layer="top" 
                                   placeholder="<?php _e('Търсете и добавете нотка...', 'parfume-catalog'); ?>" />
                            <button type="button" class="button button-small add-note-btn" data-layer="top">
                                <span class="dashicons dashicons-plus"></span>
                            </button>
                        </div>
                    </div>
                    <div class="notes-list sortable" id="top-notes-list" data-layer="top">
                        <?php foreach ($top_notes as $index => $note_id): ?>
                            <?php $this->render_note_item($note_id, 'top', $index); ?>
                        <?php endforeach; ?>
                        <?php if (empty($top_notes)): ?>
                            <div class="empty-layer-message">
                                <?php _e('Няма добавени връхни нотки', 'parfume-catalog'); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Heart Notes -->
                <div class="notes-layer heart-notes">
                    <div class="layer-header">
                        <h4>
                            <span class="layer-icon">🌸</span>
                            <?php _e('Средни нотки (сърце)', 'parfume-catalog'); ?>
                            <span class="layer-description"><?php _e('(основен характер, 2-4 часа)', 'parfume-catalog'); ?></span>
                        </h4>
                        <div class="layer-actions">
                            <input type="text" 
                                   class="note-search" 
                                   data-layer="heart" 
                                   placeholder="<?php _e('Търсете и добавете нотка...', 'parfume-catalog'); ?>" />
                            <button type="button" class="button button-small add-note-btn" data-layer="heart">
                                <span class="dashicons dashicons-plus"></span>
                            </button>
                        </div>
                    </div>
                    <div class="notes-list sortable" id="heart-notes-list" data-layer="heart">
                        <?php foreach ($heart_notes as $index => $note_id): ?>
                            <?php $this->render_note_item($note_id, 'heart', $index); ?>
                        <?php endforeach; ?>
                        <?php if (empty($heart_notes)): ?>
                            <div class="empty-layer-message">
                                <?php _e('Няма добавени средни нотки', 'parfume-catalog'); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Base Notes -->
                <div class="notes-layer base-notes">
                    <div class="layer-header">
                        <h4>
                            <span class="layer-icon">🌰</span>
                            <?php _e('Базови нотки', 'parfume-catalog'); ?>
                            <span class="layer-description"><?php _e('(дълготрайни, 6+ часа)', 'parfume-catalog'); ?></span>
                        </h4>
                        <div class="layer-actions">
                            <input type="text" 
                                   class="note-search" 
                                   data-layer="base" 
                                   placeholder="<?php _e('Търсете и добавете нотка...', 'parfume-catalog'); ?>" />
                            <button type="button" class="button button-small add-note-btn" data-layer="base">
                                <span class="dashicons dashicons-plus"></span>
                            </button>
                        </div>
                    </div>
                    <div class="notes-list sortable" id="base-notes-list" data-layer="base">
                        <?php foreach ($base_notes as $index => $note_id): ?>
                            <?php $this->render_note_item($note_id, 'base', $index); ?>
                        <?php endforeach; ?>
                        <?php if (empty($base_notes)): ?>
                            <div class="empty-layer-message">
                                <?php _e('Няма добавени базови нотки', 'parfume-catalog'); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="composition-summary">
                <h4><?php _e('Обобщение на състава', 'parfume-catalog'); ?></h4>
                <div class="summary-stats">
                    <div class="stat-item">
                        <span class="stat-label"><?php _e('Общо нотки:', 'parfume-catalog'); ?></span>
                        <span class="stat-value" id="total-notes-count"><?php echo count($top_notes) + count($heart_notes) + count($base_notes); ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label"><?php _e('Връхни:', 'parfume-catalog'); ?></span>
                        <span class="stat-value" id="top-notes-count"><?php echo count($top_notes); ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label"><?php _e('Средни:', 'parfume-catalog'); ?></span>
                        <span class="stat-value" id="heart-notes-count"><?php echo count($heart_notes); ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label"><?php _e('Базови:', 'parfume-catalog'); ?></span>
                        <span class="stat-value" id="base-notes-count"><?php echo count($base_notes); ?></span>
                    </div>
                </div>
                
                <div class="groups-distribution">
                    <h5><?php _e('Разпределение по групи:', 'parfume-catalog'); ?></h5>
                    <div class="groups-chart" id="groups-chart">
                        <?php $this->render_groups_distribution($top_notes, $heart_notes, $base_notes); ?>
                    </div>
                </div>
            </div>
        </div>
        
        <style>
        .notes-composition-container {
            margin: 15px 0;
        }
        
        .composition-pyramid {
            margin-bottom: 30px;
        }
        
        .notes-layer {
            margin-bottom: 25px;
            border: 1px solid #ddd;
            border-radius: 6px;
            overflow: hidden;
        }
        
        .notes-layer.top-notes {
            border-left: 4px solid #27ae60;
        }
        
        .notes-layer.heart-notes {
            border-left: 4px solid #e91e63;
        }
        
        .notes-layer.base-notes {
            border-left: 4px solid #8b4513;
        }
        
        .layer-header {
            background: #f8f9fa;
            padding: 12px 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #e9ecef;
        }
        
        .layer-header h4 {
            margin: 0;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
        }
        
        .layer-icon {
            font-size: 16px;
        }
        
        .layer-description {
            font-size: 11px;
            color: #666;
            font-weight: normal;
            font-style: italic;
        }
        
        .layer-actions {
            display: flex;
            gap: 5px;
            align-items: center;
        }
        
        .note-search {
            width: 200px;
            padding: 4px 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 12px;
        }
        
        .add-note-btn {
            min-width: 30px;
            height: 30px;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .notes-list {
            padding: 15px;
            min-height: 60px;
            background: #fff;
        }
        
        .notes-list.sortable {
            cursor: move;
        }
        
        .empty-layer-message {
            text-align: center;
            color: #999;
            font-style: italic;
            padding: 20px 0;
        }
        
        .note-item {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 20px;
            padding: 6px 12px;
            margin: 4px;
            cursor: move;
            position: relative;
            transition: all 0.2s;
        }
        
        .note-item:hover {
            background: #f8f9fa;
            border-color: #0073aa;
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .note-icon {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: #f0f0f1;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
        }
        
        .note-name {
            font-size: 12px;
            font-weight: 500;
        }
        
        .note-group-badge {
            background: #0073aa;
            color: #fff;
            font-size: 9px;
            padding: 2px 6px;
            border-radius: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .remove-note {
            background: #dc3545;
            color: #fff;
            border: none;
            border-radius: 50%;
            width: 16px;
            height: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 10px;
            margin-left: 4px;
        }
        
        .remove-note:hover {
            background: #c82333;
        }
        
        .composition-summary {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 6px;
            padding: 20px;
        }
        
        .composition-summary h4 {
            margin: 0 0 15px 0;
            color: #495057;
        }
        
        .summary-stats {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        
        .stat-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 4px;
        }
        
        .stat-label {
            font-size: 11px;
            color: #666;
            text-transform: uppercase;
        }
        
        .stat-value {
            font-size: 20px;
            font-weight: bold;
            color: #0073aa;
        }
        
        .groups-distribution h5 {
            margin: 0 0 10px 0;
            font-size: 13px;
            color: #666;
        }
        
        .groups-chart {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }
        
        .group-item {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 6px 10px;
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 11px;
        }
        
        .group-color {
            width: 12px;
            height: 12px;
            border-radius: 2px;
        }
        
        .group-name {
            font-weight: 500;
        }
        
        .group-count {
            color: #666;
        }
        
        /* Group colors */
        .group-item[data-group="цветни"] .group-color { background: #e91e63; }
        .group-item[data-group="плодови"] .group-color { background: #ff9800; }
        .group-item[data-group="зелени"] .group-color { background: #4caf50; }
        .group-item[data-group="дървесни"] .group-color { background: #8d6e63; }
        .group-item[data-group="ориенталски"] .group-color { background: #9c27b0; }
        .group-item[data-group="ароматни"] .group-color { background: #f44336; }
        .group-item[data-group="гурме"] .group-color { background: #795548; }
        .group-item[data-group="морски"] .group-color { background: #2196f3; }
        
        /* Responsive */
        @media (max-width: 768px) {
            .layer-header {
                flex-direction: column;
                align-items: stretch;
                gap: 10px;
            }
            
            .layer-actions {
                justify-content: stretch;
            }
            
            .note-search {
                flex: 1;
            }
            
            .summary-stats {
                justify-content: center;
            }
        }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            // Initialize sortable for all note lists
            $('.notes-list.sortable').sortable({
                connectWith: '.notes-list.sortable',
                placeholder: 'note-placeholder',
                tolerance: 'pointer',
                update: function(event, ui) {
                    updateNotesData();
                    updateStats();
                }
            });
            
            // Initialize autocomplete for note search
            $('.note-search').autocomplete({
                source: function(request, response) {
                    $.post(parfumeMetaNotes.ajax_url, {
                        action: 'parfume_search_notes',
                        term: request.term,
                        nonce: parfumeMetaNotes.nonce
                    }, function(data) {
                        response(data);
                    });
                },
                minLength: 2,
                select: function(event, ui) {
                    var layer = $(this).data('layer');
                    addNoteToLayer(ui.item.id, layer);
                    $(this).val('');
                    return false;
                }
            });
            
            // Add note button click
            $('.add-note-btn').click(function() {
                var layer = $(this).data('layer');
                var searchInput = $(this).siblings('.note-search');
                var term = searchInput.val().trim();
                
                if (term) {
                    // Try to find and add note by name
                    searchAndAddNote(term, layer);
                    searchInput.val('');
                }
            });
            
            // Remove note
            $(document).on('click', '.remove-note', function() {
                if (confirm(parfumeMetaNotes.texts.confirm_remove)) {
                    $(this).closest('.note-item').fadeOut(300, function() {
                        $(this).remove();
                        updateNotesData();
                        updateStats();
                        checkEmptyStates();
                    });
                }
            });
            
            function addNoteToLayer(noteId, layer) {
                // Check if note already exists in this layer
                var existingNote = $('#' + layer + '-notes-list .note-item[data-note-id="' + noteId + '"]');
                if (existingNote.length > 0) {
                    showMessage(parfumeMetaNotes.texts.note_exists, 'warning');
                    return;
                }
                
                // Get note info and add to layer
                $.post(parfumeMetaNotes.ajax_url, {
                    action: 'parfume_get_note_info',
                    note_id: noteId,
                    nonce: parfumeMetaNotes.nonce
                }, function(response) {
                    if (response.success) {
                        addNoteItem(response.data, layer);
                        showMessage(parfumeMetaNotes.texts.note_added, 'success');
                        updateNotesData();
                        updateStats();
                    } else {
                        showMessage(parfumeMetaNotes.texts.note_not_found, 'error');
                    }
                });
            }
            
            function searchAndAddNote(term, layer) {
                $.post(parfumeMetaNotes.ajax_url, {
                    action: 'parfume_search_notes',
                    term: term,
                    exact: true,
                    nonce: parfumeMetaNotes.nonce
                }, function(data) {
                    if (data.length > 0) {
                        addNoteToLayer(data[0].id, layer);
                    } else {
                        showMessage(parfumeMetaNotes.texts.note_not_found, 'error');
                    }
                });
            }
            
            function addNoteItem(noteData, layer) {
                var noteHtml = '<div class="note-item" data-note-id="' + noteData.id + '">' +
                    '<div class="note-icon"></div>' +
                    '<span class="note-name">' + noteData.name + '</span>' +
                    (noteData.group ? '<span class="note-group-badge">' + noteData.group + '</span>' : '') +
                    '<button type="button" class="remove-note">×</button>' +
                    '</div>';
                
                var $notesList = $('#' + layer + '-notes-list');
                $notesList.find('.empty-layer-message').remove();
                $notesList.append(noteHtml);
            }
            
            function updateNotesData() {
                ['top', 'heart', 'base'].forEach(function(layer) {
                    var noteIds = [];
                    $('#' + layer + '-notes-list .note-item').each(function() {
                        noteIds.push($(this).data('note-id'));
                    });
                    
                    // Update hidden input
                    var inputName = 'parfume_' + (layer === 'heart' ? 'heart' : layer) + '_notes';
                    $('input[name="' + inputName + '"]').remove();
                    
                    noteIds.forEach(function(noteId, index) {
                        $('<input>').attr({
                            type: 'hidden',
                            name: inputName + '[]',
                            value: noteId
                        }).appendTo('#' + layer + '-notes-list');
                    });
                });
            }
            
            function updateStats() {
                var topCount = $('#top-notes-list .note-item').length;
                var heartCount = $('#heart-notes-list .note-item').length;
                var baseCount = $('#base-notes-list .note-item').length;
                var totalCount = topCount + heartCount + baseCount;
                
                $('#top-notes-count').text(topCount);
                $('#heart-notes-count').text(heartCount);
                $('#base-notes-count').text(baseCount);
                $('#total-notes-count').text(totalCount);
                
                updateGroupsChart();
            }
            
            function updateGroupsChart() {
                // This would collect all notes and group them
                // Implementation would analyze note groups and create chart
            }
            
            function checkEmptyStates() {
                ['top', 'heart', 'base'].forEach(function(layer) {
                    var $notesList = $('#' + layer + '-notes-list');
                    if ($notesList.find('.note-item').length === 0) {
                        if ($notesList.find('.empty-layer-message').length === 0) {
                            var message = layer === 'top' ? 'Няма добавени връхни нотки' :
                                         layer === 'heart' ? 'Няма добавени средни нотки' :
                                         'Няма добавени базови нотки';
                            $notesList.append('<div class="empty-layer-message">' + message + '</div>');
                        }
                    }
                });
            }
            
            function showMessage(text, type) {
                var alertClass = type === 'success' ? 'notice-success' :
                                type === 'warning' ? 'notice-warning' : 'notice-error';
                
                $('<div class="notice ' + alertClass + ' is-dismissible"><p>' + text + '</p></div>')
                    .insertAfter('.notes-composition-container')
                    .delay(3000)
                    .fadeOut();
            }
            
            // Initialize
            updateNotesData();
            updateStats();
        });
        </script>
        <?php
    }
    
    /**
     * Render main notes meta box
     */
    public function render_main_notes_meta_box($post) {
        $main_notes = get_post_meta($post->ID, '_parfume_main_notes', true) ?: array();
        ?>
        <div class="main-notes-container">
            <p><?php _e('Изберете до 5 основни нотки, които най-добре характеризират този парфюм:', 'parfume-catalog'); ?></p>
            
            <div class="main-notes-selector">
                <input type="text" 
                       id="main-notes-search" 
                       placeholder="<?php _e('Търсете нотка за добавяне...', 'parfume-catalog'); ?>" 
                       class="widefat" />
            </div>
            
            <div class="selected-main-notes" id="selected-main-notes">
                <?php foreach ($main_notes as $index => $note_id): ?>
                    <?php $this->render_main_note_item($note_id, $index); ?>
                <?php endforeach; ?>
                
                <?php if (empty($main_notes)): ?>
                    <div class="no-main-notes">
                        <p><?php _e('Няма избрани основни нотки', 'parfume-catalog'); ?></p>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="main-notes-help">
                <p class="description">
                    <?php _e('Основните нотки се показват под заглавието във фронтенда и се използват за търсене на подобни парфюми.', 'parfume-catalog'); ?>
                </p>
            </div>
        </div>
        
        <style>
        .main-notes-container {
            padding: 10px 0;
        }
        
        .main-notes-selector {
            margin: 15px 0;
        }
        
        .selected-main-notes {
            min-height: 60px;
            border: 1px dashed #ddd;
            border-radius: 4px;
            padding: 10px;
            margin: 15px 0;
        }
        
        .main-note-item {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #0073aa;
            color: #fff;
            border-radius: 15px;
            padding: 6px 12px;
            margin: 3px;
            font-size: 12px;
            position: relative;
        }
        
        .main-note-icon {
            width: 16px;
            height: 16px;
            border-radius: 50%;
            background: rgba(255,255,255,0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 8px;
        }
        
        .main-note-name {
            font-weight: 500;
        }
        
        .remove-main-note {
            background: rgba(255,255,255,0.2);
            color: #fff;
            border: none;
            border-radius: 50%;
            width: 14px;
            height: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 10px;
            margin-left: 4px;
        }
        
        .remove-main-note:hover {
            background: rgba(255,255,255,0.3);
        }
        
        .no-main-notes {
            text-align: center;
            color: #999;
            font-style: italic;
            padding: 20px 0;
        }
        
        .main-notes-help {
            margin-top: 15px;
            padding-top: 10px;
            border-top: 1px solid #ddd;
        }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            // Initialize autocomplete for main notes
            $('#main-notes-search').autocomplete({
                source: function(request, response) {
                    $.post(parfumeMetaNotes.ajax_url, {
                        action: 'parfume_search_notes',
                        term: request.term,
                        nonce: parfumeMetaNotes.nonce
                    }, function(data) {
                        response(data);
                    });
                },
                minLength: 2,
                select: function(event, ui) {
                    addMainNote(ui.item.id);
                    $(this).val('');
                    return false;
                }
            });
            
            // Remove main note
            $(document).on('click', '.remove-main-note', function() {
                $(this).closest('.main-note-item').fadeOut(300, function() {
                    $(this).remove();
                    updateMainNotesData();
                    checkMainNotesLimit();
                    checkMainNotesEmpty();
                });
            });
            
            function addMainNote(noteId) {
                // Check limit
                if ($('#selected-main-notes .main-note-item').length >= 5) {
                    alert('<?php _e('Можете да изберете максимум 5 основни нотки', 'parfume-catalog'); ?>');
                    return;
                }
                
                // Check if already exists
                if ($('#selected-main-notes .main-note-item[data-note-id="' + noteId + '"]').length > 0) {
                    alert('<?php _e('Тази нотка вече е избрана', 'parfume-catalog'); ?>');
                    return;
                }
                
                // Get note info and add
                $.post(parfumeMetaNotes.ajax_url, {
                    action: 'parfume_get_note_info',
                    note_id: noteId,
                    nonce: parfumeMetaNotes.nonce
                }, function(response) {
                    if (response.success) {
                        addMainNoteItem(response.data);
                        updateMainNotesData();
                        checkMainNotesLimit();
                    }
                });
            }
            
            function addMainNoteItem(noteData) {
                var noteHtml = '<div class="main-note-item" data-note-id="' + noteData.id + '">' +
                    '<div class="main-note-icon"></div>' +
                    '<span class="main-note-name">' + noteData.name + '</span>' +
                    '<button type="button" class="remove-main-note">×</button>' +
                    '</div>';
                
                $('#selected-main-notes .no-main-notes').remove();
                $('#selected-main-notes').append(noteHtml);
            }
            
            function updateMainNotesData() {
                // Remove existing hidden inputs
                $('input[name="parfume_main_notes[]"]').remove();
                
                // Add new hidden inputs
                $('#selected-main-notes .main-note-item').each(function() {
                    var noteId = $(this).data('note-id');
                    $('<input>').attr({
                        type: 'hidden',
                        name: 'parfume_main_notes[]',
                        value: noteId
                    }).appendTo('#selected-main-notes');
                });
            }
            
            function checkMainNotesLimit() {
                var count = $('#selected-main-notes .main-note-item').length;
                if (count >= 5) {
                    $('#main-notes-search').prop('disabled', true)
                        .attr('placeholder', '<?php _e('Достигнат е максималният лимит от 5 нотки', 'parfume-catalog'); ?>');
                } else {
                    $('#main-notes-search').prop('disabled', false)
                        .attr('placeholder', '<?php _e('Търсете нотка за добавяне...', 'parfume-catalog'); ?>');
                }
            }
            
            function checkMainNotesEmpty() {
                if ($('#selected-main-notes .main-note-item').length === 0) {
                    $('#selected-main-notes').append('<div class="no-main-notes"><p><?php _e('Няма избрани основни нотки', 'parfume-catalog'); ?></p></div>');
                }
            }
            
            // Initialize
            updateMainNotesData();
            checkMainNotesLimit();
        });
        </script>
        <?php
    }
    
    /**
     * Render notes import meta box
     */
    public function render_notes_import_meta_box($post) {
        ?>
        <div class="notes-import-container">
            <div class="import-section">
                <h4><?php _e('JSON импорт на нотки', 'parfume-catalog'); ?></h4>
                <p><?php _e('Импортирайте нотки и техните групи от JSON файл:', 'parfume-catalog'); ?></p>
                
                <div class="import-form">
                    <textarea id="notes_json_input" 
                              rows="8" 
                              class="widefat"
                              placeholder='<?php _e('Поставете JSON тук...', 'parfume-catalog'); ?>'>[
    {"note": "Iso E Super", "group": "дървесни"},
    {"note": "Абаносово дърво", "group": "дървесни"},
    {"note": "Абсент", "group": "ароматни"},
    {"note": "Авокадо", "group": "зелени"}
]</textarea>
                    
                    <p class="import-actions">
                        <button type="button" class="button button-primary" id="import_notes_btn">
                            <?php _e('Импортирай нотки', 'parfume-catalog'); ?>
                        </button>
                        <button type="button" class="button" id="validate_json_btn">
                            <?php _e('Валидирай JSON', 'parfume-catalog'); ?>
                        </button>
                    </p>
                    
                    <div id="import_results" style="display: none;"></div>
                </div>

                <div class="import-help">
                    <h5><?php _e('Формат на JSON:', 'parfume-catalog'); ?></h5>
                    <pre>[
    {"note": "Име на нотка", "group": "група"},
    {"note": "Друга нотка", "group": "друга група"}
]</pre>
                    <p><strong><?php _e('Валидни групи:', 'parfume-catalog'); ?></strong><br>
                    дървесни, ароматни, зелени, ориенталски, цветни, гурме, плодови, морски</p>
                </div>
            </div>
            
            <div class="groups-reference">
                <h4><?php _e('Справка за групи', 'parfume-catalog'); ?></h4>
                <?php $this->render_note_groups_reference(); ?>
            </div>
        </div>
        
        <style>
        .notes-import-container {
            padding: 10px 0;
        }
        
        .import-section {
            margin-bottom: 25px;
        }
        
        .import-form {
            margin: 15px 0;
        }
        
        .import-actions {
            margin: 10px 0;
            display: flex;
            gap: 10px;
        }
        
        .import-help {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 4px;
            padding: 15px;
            margin-top: 15px;
        }
        
        .import-help h5 {
            margin: 0 0 10px 0;
            font-size: 13px;
        }
        
        .import-help pre {
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 10px;
            font-size: 11px;
            margin: 10px 0;
        }
        
        .groups-reference h4 {
            margin: 0 0 15px 0;
            font-size: 14px;
            color: #495057;
        }
        
        .note-group {
            margin-bottom: 12px;
            padding: 8px 12px;
            background: #fff;
            border: 1px solid #e9ecef;
            border-radius: 4px;
        }
        
        .note-group h5 {
            margin: 0 0 6px 0;
            font-size: 12px;
            color: #0073aa;
            text-transform: uppercase;
        }
        
        .note-group-notes {
            font-size: 11px;
            color: #666;
            line-height: 1.4;
        }
        
        #import_results {
            margin-top: 10px;
            padding: 10px;
            border-radius: 4px;
        }
        
        #import_results.success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        
        #import_results.error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            // Validate JSON
            $('#validate_json_btn').click(function() {
                var jsonText = $('#notes_json_input').val().trim();
                
                if (!jsonText) {
                    showImportResult('<?php _e('Моля, въведете JSON данни', 'parfume-catalog'); ?>', 'error');
                    return;
                }
                
                try {
                    var data = JSON.parse(jsonText);
                    if (Array.isArray(data)) {
                        showImportResult('<?php _e('JSON формат е валиден! Намерени са ', 'parfume-catalog'); ?>' + data.length + '<?php _e(' нотки.', 'parfume-catalog'); ?>', 'success');
                    } else {
                        showImportResult('<?php _e('JSON трябва да съдържа масив с нотки', 'parfume-catalog'); ?>', 'error');
                    }
                } catch (e) {
                    showImportResult('<?php _e('Невалиден JSON формат: ', 'parfume-catalog'); ?>' + e.message, 'error');
                }
            });
            
            // Import notes
            $('#import_notes_btn').click(function() {
                var jsonText = $('#notes_json_input').val().trim();
                var $btn = $(this);
                
                if (!jsonText) {
                    showImportResult(parfumeMetaNotes.texts.invalid_json, 'error');
                    return;
                }
                
                $btn.prop('disabled', true).text('<?php _e('Импортиране...', 'parfume-catalog'); ?>');
                
                $.post(parfumeMetaNotes.ajax_url, {
                    action: 'parfume_import_notes_json',
                    json_data: jsonText,
                    nonce: parfumeMetaNotes.nonce
                }, function(response) {
                    if (response.success) {
                        showImportResult(response.data.message, 'success');
                        // Refresh autocomplete cache or page
                        location.reload();
                    } else {
                        showImportResult(response.data.message || parfumeMetaNotes.texts.import_error, 'error');
                    }
                }).always(function() {
                    $btn.prop('disabled', false).text('<?php _e('Импортирай нотки', 'parfume-catalog'); ?>');
                });
            });
            
            function showImportResult(message, type) {
                $('#import_results')
                    .removeClass('success error')
                    .addClass(type)
                    .html(message)
                    .show();
            }
        });
        </script>
        <?php
    }
    
    /**
     * Render individual note item
     */
    private function render_note_item($note_id, $layer, $index) {
        $term = get_term($note_id, 'parfume_notes');
        if (!$term || is_wp_error($term)) {
            return;
        }
        
        $note_group = get_term_meta($note_id, 'note_group', true);
        ?>
        <div class="note-item" data-note-id="<?php echo esc_attr($note_id); ?>">
            <div class="note-icon"></div>
            <span class="note-name"><?php echo esc_html($term->name); ?></span>
            <?php if ($note_group): ?>
                <span class="note-group-badge"><?php echo esc_html($note_group); ?></span>
            <?php endif; ?>
            <button type="button" class="remove-note">×</button>
        </div>
        <?php
    }
    
    /**
     * Render main note item
     */
    private function render_main_note_item($note_id, $index) {
        $term = get_term($note_id, 'parfume_notes');
        if (!$term || is_wp_error($term)) {
            return;
        }
        ?>
        <div class="main-note-item" data-note-id="<?php echo esc_attr($note_id); ?>">
            <div class="main-note-icon"></div>
            <span class="main-note-name"><?php echo esc_html($term->name); ?></span>
            <button type="button" class="remove-main-note">×</button>
        </div>
        <?php
    }
    
    /**
     * Render groups distribution
     */
    private function render_groups_distribution($top_notes, $heart_notes, $base_notes) {
        $all_notes = array_merge($top_notes, $heart_notes, $base_notes);
        $groups = array();
        
        foreach ($all_notes as $note_id) {
            $group = get_term_meta($note_id, 'note_group', true);
            if ($group) {
                $groups[$group] = isset($groups[$group]) ? $groups[$group] + 1 : 1;
            }
        }
        
        foreach ($groups as $group => $count) {
            ?>
            <div class="group-item" data-group="<?php echo esc_attr($group); ?>">
                <div class="group-color"></div>
                <span class="group-name"><?php echo esc_html(ucfirst($group)); ?></span>
                <span class="group-count">(<?php echo $count; ?>)</span>
            </div>
            <?php
        }
        
        if (empty($groups)) {
            echo '<p class="no-groups">' . __('Няма нотки за анализ', 'parfume-catalog') . '</p>';
        }
    }
    
    /**
     * Render note groups reference
     */
    private function render_note_groups_reference() {
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
        
        // Check if autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        // Check user permissions
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Check post type
        if (get_post_type($post_id) !== 'parfumes') {
            return;
        }
        
        // Save composition notes
        $this->save_notes_array($post_id, 'parfume_top_notes', '_parfume_top_notes');
        $this->save_notes_array($post_id, 'parfume_heart_notes', '_parfume_heart_notes');
        $this->save_notes_array($post_id, 'parfume_base_notes', '_parfume_base_notes');
        
        // Save main notes
        $this->save_notes_array($post_id, 'parfume_main_notes', '_parfume_main_notes');
    }
    
    /**
     * Helper method to save notes arrays
     */
    private function save_notes_array($post_id, $field_name, $meta_key) {
        if (isset($_POST[$field_name]) && is_array($_POST[$field_name])) {
            $notes = array_map('absint', $_POST[$field_name]);
            $notes = array_filter($notes); // Remove empty values
            update_post_meta($post_id, $meta_key, $notes);
        } else {
            delete_post_meta($post_id, $meta_key);
        }
    }
    
    /**
     * AJAX: Search notes
     */
    public function ajax_search_notes() {
        // Проверка на nonce
        if (!wp_verify_nonce($_POST['nonce'], 'parfume_meta_notes')) {
            wp_die(__('Невалидна заявка', 'parfume-catalog'));
        }
        
        $term = sanitize_text_field($_POST['term']);
        $exact = isset($_POST['exact']) && $_POST['exact'];
        
        $search_args = array(
            'taxonomy' => 'parfume_notes',
            'hide_empty' => false,
            'number' => $exact ? 1 : 10,
            'name__like' => $term
        );
        
        if ($exact) {
            $search_args['name'] = $term;
            unset($search_args['name__like']);
        }
        
        $notes = get_terms($search_args);
        $results = array();
        
        if (!is_wp_error($notes)) {
            foreach ($notes as $note) {
                $results[] = array(
                    'id' => $note->term_id,
                    'label' => $note->name,
                    'value' => $note->name
                );
            }
        }
        
        wp_send_json($results);
    }
    
    /**
     * AJAX: Get note info
     */
    public function ajax_get_note_info() {
        // Проверка на nonce
        if (!wp_verify_nonce($_POST['nonce'], 'parfume_meta_notes')) {
            wp_die(__('Невалидна заявка', 'parfume-catalog'));
        }
        
        $note_id = absint($_POST['note_id']);
        $note = get_term($note_id, 'parfume_notes');
        
        if (!$note || is_wp_error($note)) {
            wp_send_json_error(array('message' => __('Нотката не е намерена', 'parfume-catalog')));
        }
        
        $note_group = get_term_meta($note_id, 'note_group', true);
        
        wp_send_json_success(array(
            'id' => $note->term_id,
            'name' => $note->name,
            'group' => $note_group
        ));
    }
    
    /**
     * AJAX: Add note to composition
     */
    public function ajax_add_note_to_composition() {
        // Проверка на nonce
        if (!wp_verify_nonce($_POST['nonce'], 'parfume_meta_notes')) {
            wp_die(__('Невалидна заявка', 'parfume-catalog'));
        }
        
        // Проверка на права
        if (!current_user_can('edit_posts')) {
            wp_die(__('Нямате права за тази операция', 'parfume-catalog'));
        }
        
        $post_id = absint($_POST['post_id']);
        $note_id = absint($_POST['note_id']);
        $layer = sanitize_text_field($_POST['layer']);
        
        $allowed_layers = array('top', 'heart', 'base');
        if (!in_array($layer, $allowed_layers)) {
            wp_send_json_error(array('message' => __('Невалиден слой', 'parfume-catalog')));
        }
        
        $meta_key = '_parfume_' . ($layer === 'heart' ? 'heart' : $layer) . '_notes';
        $current_notes = get_post_meta($post_id, $meta_key, true) ?: array();
        
        if (!in_array($note_id, $current_notes)) {
            $current_notes[] = $note_id;
            update_post_meta($post_id, $meta_key, $current_notes);
        }
        
        wp_send_json_success(array('message' => __('Нотката е добавена успешно', 'parfume-catalog')));
    }
    
    /**
     * AJAX: Import notes from JSON
     */
    public function ajax_import_notes_json() {
        // Проверка на nonce
        if (!wp_verify_nonce($_POST['nonce'], 'parfume_meta_notes')) {
            wp_die(__('Невалидна заявка', 'parfume-catalog'));
        }
        
        // Проверка на права
        if (!current_user_can('manage_options')) {
            wp_die(__('Нямате права за тази операция', 'parfume-catalog'));
        }
        
        $json_data = sanitize_textarea_field($_POST['json_data']);
        
        try {
            $notes_data = json_decode($json_data, true);
            
            if (!is_array($notes_data)) {
                wp_send_json_error(array('message' => __('JSON трябва да съдържа масив с нотки', 'parfume-catalog')));
            }
            
            $imported_count = 0;
            $skipped_count = 0;
            $valid_groups = array('цветни', 'плодови', 'зелени', 'дървесни', 'ориенталски', 'ароматни', 'гурме', 'морски');
            
            foreach ($notes_data as $note_data) {
                if (!isset($note_data['note']) || !isset($note_data['group'])) {
                    continue;
                }
                
                $note_name = sanitize_text_field($note_data['note']);
                $note_group = sanitize_text_field($note_data['group']);
                
                if (!in_array($note_group, $valid_groups)) {
                    continue;
                }
                
                // Check if note already exists
                $existing_term = get_term_by('name', $note_name, 'parfume_notes');
                
                if (!$existing_term) {
                    // Create new note
                    $term_result = wp_insert_term($note_name, 'parfume_notes');
                    
                    if (!is_wp_error($term_result)) {
                        update_term_meta($term_result['term_id'], 'note_group', $note_group);
                        $imported_count++;
                    }
                } else {
                    // Update existing note group if needed
                    $current_group = get_term_meta($existing_term->term_id, 'note_group', true);
                    if ($current_group !== $note_group) {
                        update_term_meta($existing_term->term_id, 'note_group', $note_group);
                        $imported_count++;
                    } else {
                        $skipped_count++;
                    }
                }
            }
            
            $message = sprintf(
                __('Импортирани: %d нотки. Прескочени: %d нотки.', 'parfume-catalog'),
                $imported_count,
                $skipped_count
            );
            
            wp_send_json_success(array('message' => $message));
            
        } catch (Exception $e) {
            wp_send_json_error(array('message' => __('Невалиден JSON формат: ', 'parfume-catalog') . $e->getMessage()));
        }
    }
    
    /**
     * Static helper methods for external access
     */
    public static function get_parfume_notes($post_id) {
        return array(
            'top_notes' => get_post_meta($post_id, '_parfume_top_notes', true) ?: array(),
            'heart_notes' => get_post_meta($post_id, '_parfume_heart_notes', true) ?: array(),
            'base_notes' => get_post_meta($post_id, '_parfume_base_notes', true) ?: array(),
            'main_notes' => get_post_meta($post_id, '_parfume_main_notes', true) ?: array()
        );
    }
    
    public static function get_parfume_notes_by_layer($post_id, $layer) {
        $meta_key = '_parfume_' . ($layer === 'heart' ? 'heart' : $layer) . '_notes';
        return get_post_meta($post_id, $meta_key, true) ?: array();
    }
    
    public static function get_note_group($note_id) {
        return get_term_meta($note_id, 'note_group', true);
    }
    
    public static function get_notes_by_group($notes_ids) {
        $grouped_notes = array();
        
        foreach ($notes_ids as $note_id) {
            $note = get_term($note_id, 'parfume_notes');
            if ($note && !is_wp_error($note)) {
                $group = self::get_note_group($note_id);
                if (!isset($grouped_notes[$group])) {
                    $grouped_notes[$group] = array();
                }
                $grouped_notes[$group][] = $note;
            }
        }
        
        return $grouped_notes;
    }
    
    public static function has_notes($post_id) {
        $notes = self::get_parfume_notes($post_id);
        return !empty($notes['top_notes']) || !empty($notes['heart_notes']) || !empty($notes['base_notes']);
    }
    
    public static function get_notes_count($post_id) {
        $notes = self::get_parfume_notes($post_id);
        return count($notes['top_notes']) + count($notes['heart_notes']) + count($notes['base_notes']);
    }
}