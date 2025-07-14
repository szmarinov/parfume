<?php
namespace Parfume_Reviews\Taxonomies;

/**
 * Taxonomy Meta Fields - управлява meta полетата за таксономии
 * РАЗШИРЕНА ВЕРСИЯ - добавени допълнителни мета полета за парфюмеристи
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
        
        // НОВИ ПОЛЕТА - Добавяме специални полета за парфюмеристи
        add_action('perfumer_add_form_fields', array($this, 'add_perfumer_meta_fields'), 10, 2);
        add_action('perfumer_edit_form_fields', array($this, 'edit_perfumer_meta_fields'), 10, 2);
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
            <select name="note_group" id="note_group">
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
     * Редактира поле за група на нотки
     */
    public function edit_notes_group_field($term, $taxonomy) {
        $note_group = get_term_meta($term->term_id, 'note_group', true);
        ?>
        <tr class="form-field term-group-wrap">
            <th scope="row">
                <label for="note_group"><?php _e('Група на нотката', 'parfume-reviews'); ?></label>
            </th>
            <td>
                <select name="note_group" id="note_group">
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
     * НОВА ФУНКЦИЯ - Добавя мета полета за парфюмеристи при създаване
     */
    public function add_perfumer_meta_fields($taxonomy) {
        ?>
        <div class="form-field term-group">
            <label for="perfumer_birth_date"><?php _e('Дата на раждане', 'parfume-reviews'); ?></label>
            <input type="date" name="perfumer_birth_date" id="perfumer_birth_date" value="" />
            <p class="description"><?php _e('Дата на раждане на парфюмериста', 'parfume-reviews'); ?></p>
        </div>
        
        <div class="form-field term-group">
            <label for="perfumer_nationality"><?php _e('Националност', 'parfume-reviews'); ?></label>
            <input type="text" name="perfumer_nationality" id="perfumer_nationality" value="" />
            <p class="description"><?php _e('Националност на парфюмериста', 'parfume-reviews'); ?></p>
        </div>
        
        <div class="form-field term-group">
            <label for="perfumer_education"><?php _e('Образование', 'parfume-reviews'); ?></label>
            <textarea name="perfumer_education" id="perfumer_education" rows="3"></textarea>
            <p class="description"><?php _e('Образование и учебни заведения', 'parfume-reviews'); ?></p>
        </div>
        
        <div class="form-field term-group">
            <label for="perfumer_career_start"><?php _e('Начало на кариерата', 'parfume-reviews'); ?></label>
            <input type="number" name="perfumer_career_start" id="perfumer_career_start" value="" min="1900" max="<?php echo date('Y'); ?>" />
            <p class="description"><?php _e('Година на започване на кариерата', 'parfume-reviews'); ?></p>
        </div>
        
        <div class="form-field term-group">
            <label for="perfumer_signature_style"><?php _e('Характерен стил', 'parfume-reviews'); ?></label>
            <textarea name="perfumer_signature_style" id="perfumer_signature_style" rows="3"></textarea>
            <p class="description"><?php _e('Описание на характерния стил на парфюмериста', 'parfume-reviews'); ?></p>
        </div>
        
        <div class="form-field term-group">
            <label for="perfumer_famous_fragrances"><?php _e('Известни парфюми', 'parfume-reviews'); ?></label>
            <textarea name="perfumer_famous_fragrances" id="perfumer_famous_fragrances" rows="4"></textarea>
            <p class="description"><?php _e('Най-известни парфюми (всеки на нов ред)', 'parfume-reviews'); ?></p>
        </div>
        
        <div class="form-field term-group">
            <label for="perfumer_awards"><?php _e('Награди', 'parfume-reviews'); ?></label>
            <textarea name="perfumer_awards" id="perfumer_awards" rows="3"></textarea>
            <p class="description"><?php _e('Получени награди и признания (всяка на нов ред)', 'parfume-reviews'); ?></p>
        </div>
        
        <div class="form-field term-group">
            <label for="perfumer_website"><?php _e('Уебсайт', 'parfume-reviews'); ?></label>
            <input type="url" name="perfumer_website" id="perfumer_website" value="" />
            <p class="description"><?php _e('Официален уебсайт или портфолио', 'parfume-reviews'); ?></p>
        </div>
        
        <div class="form-field term-group">
            <label for="perfumer_social_media"><?php _e('Социални мрежи', 'parfume-reviews'); ?></label>
            <input type="text" name="perfumer_social_media" id="perfumer_social_media" value="" />
            <p class="description"><?php _e('Линкове към социални мрежи', 'parfume-reviews'); ?></p>
        </div>
        <?php
    }
    
    /**
     * НОВА ФУНКЦИЯ - Редактира мета полета за парфюмеристи
     */
    public function edit_perfumer_meta_fields($term, $taxonomy) {
        $birth_date = get_term_meta($term->term_id, 'perfumer_birth_date', true);
        $nationality = get_term_meta($term->term_id, 'perfumer_nationality', true);
        $education = get_term_meta($term->term_id, 'perfumer_education', true);
        $career_start = get_term_meta($term->term_id, 'perfumer_career_start', true);
        $signature_style = get_term_meta($term->term_id, 'perfumer_signature_style', true);
        $famous_fragrances = get_term_meta($term->term_id, 'perfumer_famous_fragrances', true);
        $awards = get_term_meta($term->term_id, 'perfumer_awards', true);
        $website = get_term_meta($term->term_id, 'perfumer_website', true);
        $social_media = get_term_meta($term->term_id, 'perfumer_social_media', true);
        ?>
        <tr class="form-field term-group-wrap">
            <th scope="row">
                <label for="perfumer_birth_date"><?php _e('Дата на раждане', 'parfume-reviews'); ?></label>
            </th>
            <td>
                <input type="date" name="perfumer_birth_date" id="perfumer_birth_date" value="<?php echo esc_attr($birth_date); ?>" />
                <p class="description"><?php _e('Дата на раждане на парфюмериста', 'parfume-reviews'); ?></p>
            </td>
        </tr>
        
        <tr class="form-field term-group-wrap">
            <th scope="row">
                <label for="perfumer_nationality"><?php _e('Националност', 'parfume-reviews'); ?></label>
            </th>
            <td>
                <input type="text" name="perfumer_nationality" id="perfumer_nationality" value="<?php echo esc_attr($nationality); ?>" />
                <p class="description"><?php _e('Националност на парфюмериста', 'parfume-reviews'); ?></p>
            </td>
        </tr>
        
        <tr class="form-field term-group-wrap">
            <th scope="row">
                <label for="perfumer_education"><?php _e('Образование', 'parfume-reviews'); ?></label>
            </th>
            <td>
                <textarea name="perfumer_education" id="perfumer_education" rows="3"><?php echo esc_textarea($education); ?></textarea>
                <p class="description"><?php _e('Образование и учебни заведения', 'parfume-reviews'); ?></p>
            </td>
        </tr>
        
        <tr class="form-field term-group-wrap">
            <th scope="row">
                <label for="perfumer_career_start"><?php _e('Начало на кариерата', 'parfume-reviews'); ?></label>
            </th>
            <td>
                <input type="number" name="perfumer_career_start" id="perfumer_career_start" value="<?php echo esc_attr($career_start); ?>" min="1900" max="<?php echo date('Y'); ?>" />
                <p class="description"><?php _e('Година на започване на кариерата', 'parfume-reviews'); ?></p>
            </td>
        </tr>
        
        <tr class="form-field term-group-wrap">
            <th scope="row">
                <label for="perfumer_signature_style"><?php _e('Характерен стил', 'parfume-reviews'); ?></label>
            </th>
            <td>
                <textarea name="perfumer_signature_style" id="perfumer_signature_style" rows="3"><?php echo esc_textarea($signature_style); ?></textarea>
                <p class="description"><?php _e('Описание на характерния стил на парфюмериста', 'parfume-reviews'); ?></p>
            </td>
        </tr>
        
        <tr class="form-field term-group-wrap">
            <th scope="row">
                <label for="perfumer_famous_fragrances"><?php _e('Известни парфюми', 'parfume-reviews'); ?></label>
            </th>
            <td>
                <textarea name="perfumer_famous_fragrances" id="perfumer_famous_fragrances" rows="4"><?php echo esc_textarea($famous_fragrances); ?></textarea>
                <p class="description"><?php _e('Най-известни парфюми (всеки на нов ред)', 'parfume-reviews'); ?></p>
            </td>
        </tr>
        
        <tr class="form-field term-group-wrap">
            <th scope="row">
                <label for="perfumer_awards"><?php _e('Награди', 'parfume-reviews'); ?></label>
            </th>
            <td>
                <textarea name="perfumer_awards" id="perfumer_awards" rows="3"><?php echo esc_textarea($awards); ?></textarea>
                <p class="description"><?php _e('Получени награди и признания (всяка на нов ред)', 'parfume-reviews'); ?></p>
            </td>
        </tr>
        
        <tr class="form-field term-group-wrap">
            <th scope="row">
                <label for="perfumer_website"><?php _e('Уебсайт', 'parfume-reviews'); ?></label>
            </th>
            <td>
                <input type="url" name="perfumer_website" id="perfumer_website" value="<?php echo esc_attr($website); ?>" />
                <p class="description"><?php _e('Hivatalен уебсайт или портфолио', 'parfume-reviews'); ?></p>
            </td>
        </tr>
        
        <tr class="form-field term-group-wrap">
            <th scope="row">
                <label for="perfumer_social_media"><?php _e('Социални мрежи', 'parfume-reviews'); ?></label>
            </th>
            <td>
                <input type="text" name="perfumer_social_media" id="perfumer_social_media" value="<?php echo esc_attr($social_media); ?>" />
                <p class="description"><?php _e('Линкове към социални мрежи', 'parfume-reviews'); ?></p>
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
        
        // НОВА ФУНКЦИОНАЛНОСТ - Save perfumer meta fields
        if ($taxonomy === 'perfumer') {
            $perfumer_fields = array(
                'perfumer_birth_date' => 'sanitize_text_field',
                'perfumer_nationality' => 'sanitize_text_field', 
                'perfumer_education' => 'sanitize_textarea_field',
                'perfumer_career_start' => 'absint',
                'perfumer_signature_style' => 'sanitize_textarea_field',
                'perfumer_famous_fragrances' => 'sanitize_textarea_field',
                'perfumer_awards' => 'sanitize_textarea_field',
                'perfumer_website' => 'esc_url_raw',
                'perfumer_social_media' => 'sanitize_text_field'
            );
            
            foreach ($perfumer_fields as $field_name => $sanitize_function) {
                if (isset($_POST[$field_name])) {
                    $field_value = call_user_func($sanitize_function, $_POST[$field_name]);
                    update_term_meta($term_id, $field_name, $field_value);
                }
            }
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