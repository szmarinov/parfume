<?php
namespace Parfume_Reviews\Taxonomies;

/**
 * Taxonomy Meta Fields - управлява meta полетата за таксономии
 * ПОПРАВЕНА ВЕРСИЯ - image upload и WordPress editor
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
     * Скрива оригиналното description поле със CSS
     */
    public function hide_original_description_field() {
        $screen = get_current_screen();
        
        // Проверяваме дали сме на taxonomy страница с editor
        if ($screen && (strpos($screen->id, 'edit-marki') !== false || 
                       strpos($screen->id, 'edit-notes') !== false || 
                       strpos($screen->id, 'edit-perfumer') !== false)) {
            ?>
            <style type="text/css">
                /* Скриваме оригиналното description поле */
                .form-field.term-description-wrap:not(.custom-editor-wrap),
                .term-description-wrap:not(.custom-editor-wrap),
                #tag-description {
                    display: none !important;
                }
                
                /* Показваме само нашия custom editor */
                .custom-editor-wrap {
                    display: block !important;
                }
                
                tr.custom-editor-wrap {
                    display: table-row !important;
                }
                
                /* Поправяме стиловете на TinyMCE редактора */
                .custom-editor-wrap .wp-editor-wrap {
                    border: 1px solid #ddd;
                    background: #fff;
                }
                
                .custom-editor-wrap .wp-editor-tabs {
                    border-bottom: 1px solid #ddd;
                    background: #f1f1f1;
                }
                
                .custom-editor-wrap .wp-switch-editor {
                    background: #f7f7f7;
                    border: 1px solid #ddd;
                    color: #555;
                    cursor: pointer;
                    font-size: 13px;
                    line-height: 16px;
                    margin: 5px 0 0 5px;
                    padding: 3px 8px 4px;
                    position: relative;
                    top: 1px;
                }
                
                .custom-editor-wrap .wp-switch-editor:hover {
                    background-color: #fafafa;
                    color: #333;
                }
                
                .custom-editor-wrap .wp-switch-editor.switch-tmce {
                    border-bottom-color: #f7f7f7;
                }
                
                .custom-editor-wrap .wp-switch-editor.switch-html {
                    border-bottom-color: #f7f7f7;
                }
                
                .custom-editor-wrap .switch-tmce.active {
                    background: #fff;
                    border-bottom-color: #fff;
                    color: #333;
                }
                
                .custom-editor-wrap .switch-html.active {
                    background: #fff;
                    border-bottom-color: #fff;
                    color: #333;
                }
                
                /* Поправяме TinyMCE iframe стиловете */
                .custom-editor-wrap .mce-tinymce {
                    border: none !important;
                }
                
                .custom-editor-wrap .mce-container {
                    border-color: #ddd !important;
                }
                
                .custom-editor-wrap .mce-toolbar {
                    background: #f5f5f5 !important;
                    border-bottom: 1px solid #ddd !important;
                }
                
                .custom-editor-wrap .mce-btn {
                    color: #555 !important;
                    text-shadow: 0 1px 0 #fff !important;
                }
                
                .custom-editor-wrap .mce-btn:hover {
                    background: #fafafa !important;
                    border-color: #999 !important;
                    color: #222 !important;
                }
                
                /* Текстовата област */
                .custom-editor-wrap .wp-editor-area {
                    color: #333 !important;
                    font-family: Consolas, Monaco, monospace !important;
                    font-size: 13px !important;
                    line-height: 150% !important;
                    outline: 0 !important;
                    resize: vertical !important;
                    border: none !important;
                    padding: 10px !important;
                    background: #fff !important;
                }
                
                /* QuickTags бутони */
                .custom-editor-wrap .quicktags-toolbar {
                    background: #f5f5f5;
                    border-bottom: 1px solid #ddd;
                    padding: 0;
                }
                
                .custom-editor-wrap .quicktags-toolbar .button {
                    background: #f7f7f7;
                    border: 1px solid #ddd;
                    color: #555;
                    margin: 2px;
                    padding: 2px 4px;
                    cursor: pointer;
                }
                
                .custom-editor-wrap .quicktags-toolbar .button:hover {
                    background: #fafafa;
                    color: #333;
                }
            </style>
            
            <script type="text/javascript">
            jQuery(document).ready(function($) {
                // Допълнително скриване със JavaScript
                $('#tag-description').closest('.form-field').hide();
                $('.term-description-wrap').not('.custom-editor-wrap').hide();
                
                // Показваме нашия editor
                $('.custom-editor-wrap').show();
                
                // Осигуряваме че TinyMCE се инициализира правилно
                if (typeof tinymce !== 'undefined') {
                    setTimeout(function() {
                        tinymce.EditorManager.execCommand('mceAddEditor', true, $('.custom-editor-wrap .wp-editor-area').attr('id'));
                    }, 100);
                }
            });
            </script>
            <?php
        }
    }
    public function add_editor_to_description_new($taxonomy) {
        ?>
        <div class="form-field term-description-wrap">
            <label for="description"><?php _e('Описание'); ?></label>
            <?php
            wp_editor('', 'description', array(
                'textarea_name' => 'description',
                'media_buttons' => true,
                'textarea_rows' => 8,
                'editor_height' => 250,
                'teeny' => false,
                'quicktags' => true,
                'tinymce' => array(
                    'resize' => true,
                    'wordpress_adv_hidden' => false,
                    'add_unload_trigger' => false,
                    'statusbar' => false,
                    'wp_autoresize_on' => true,
                    // ПРЕМАХНАТ wpfullscreen от plugins
                    'plugins' => 'charmap,colorpicker,hr,lists,media,paste,tabfocus,textcolor,wordpress,wpautoresize,wpeditimage,wpgallery,wplink,wpdialogs'
                )
            ));
            ?>
            <p class="description"><?php _e('Подробно описание с форматиране.'); ?></p>
        </div>
        
        <script type="text/javascript">
        // Скриваме оригиналното description поле
        jQuery(document).ready(function($) {
            $('#tag-description').closest('.form-field').hide();
        });
        </script>
        <?php
    }
    
    /**
     * Добавя WordPress editor за description при редактиране на таксономия
     */
    public function add_editor_to_description($term, $taxonomy) {
        ?>
        <tr class="form-field term-description-wrap custom-editor-wrap">
            <th scope="row"><label for="description"><?php _e('Описание'); ?></label></th>
            <td>
                <?php
                $editor_id = 'description_' . $taxonomy . '_' . $term->term_id;
                wp_editor($term->description, $editor_id, array(
                    'textarea_name' => 'description',
                    'media_buttons' => true,
                    'textarea_rows' => 8,
                    'editor_height' => 250,
                    'teeny' => false,
                    'quicktags' => array('buttons' => 'strong,em,link,block,del,ins,img,ul,ol,li,code,more,close'),
                    'tinymce' => array(
                        'theme' => 'modern',
                        'skin' => 'lightgray',
                        'resize' => true,
                        'browser_spellcheck' => true,
                        'fix_list_elements' => true,
                        'entities' => '38,amp,60,lt,62,gt',
                        'entity_encoding' => 'raw',
                        'keep_styles' => false,
                        'paste_webkit_styles' => 'font-weight font-style color',
                        'paste_remove_spans' => true,
                        'paste_remove_styles' => true,
                        'paste_strip_class_attributes' => 'all',
                        'paste_text_use_dialog' => true,
                        'wpeditimage_disable_captions' => false,
                        'plugins' => 'charmap,colorpicker,hr,lists,media,paste,tabfocus,textcolor,wordpress,wpautoresize,wpeditimage,wpgallery,wplink,wpdialogs,wpview',
                        'toolbar1' => 'bold,italic,strikethrough,bullist,numlist,blockquote,hr,alignleft,aligncenter,alignright,link,unlink,wp_more,spellchecker,wp_adv',
                        'toolbar2' => 'formatselect,underline,alignjustify,forecolor,pastetext,removeformat,charmap,outdent,indent,undo,redo,wp_help',
                        'toolbar3' => '',
                        'toolbar4' => '',
                        'menubar' => false,
                        'wpautop' => true,
                        'indent' => false,
                        'tadv_noautop' => false,
                        'force_br_newlines' => false,
                        'force_p_newlines' => false,
                        'forced_root_block' => 'p',
                        'convert_urls' => false,
                        'remove_script_host' => false,
                        'compress' => false,
                        'relative_urls' => false,
                        'remove_linebreaks' => true,
                        'gecko_spellcheck' => true,
                        'keep_styles' => false,
                        'accessibility_focus' => true,
                        'tabfocus_elements' => 'major-publishing-actions',
                        'media_strict' => false,
                        'paste_remove_styles' => true,
                        'paste_remove_spans' => true,
                        'paste_strip_class_attributes' => 'all',
                        'paste_text_use_dialog' => true,
                        'wpeditimage_disable_captions' => false,
                        'wp_lang_attr' => get_bloginfo('language')
                    )
                ));
                ?>
                <p class="description"><?php _e('Подробно описание с форматиране.'); ?></p>
            </td>
        </tr>
        <?php
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
        // Записваме image ID (не URL!)
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
     * Зарежда admin scripts за media upload и editor
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