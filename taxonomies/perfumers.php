<?php
namespace Parfume_Reviews\Taxonomies;

use Parfume_Reviews\Utils\Taxonomy_Base;

class Perfumers extends Taxonomy_Base {
    
    public function init() {
        $this->taxonomy = 'perfumer';
        $this->post_types = array('parfume');
        $this->labels = array(
            'name' => __('Парфюмеристи', 'parfume-reviews'),
            'singular_name' => __('Парфюмерист', 'parfume-reviews'),
            'search_items' => __('Търсене в парфюмеристите', 'parfume-reviews'),
            'all_items' => __('Всички парфюмеристи', 'parfume-reviews'),
            'edit_item' => __('Редактиране на парфюмерист', 'parfume-reviews'),
            'update_item' => __('Обновяване на парфюмерист', 'parfume-reviews'),
            'add_new_item' => __('Добавяне на нов парфюмерист', 'parfume-reviews'),
            'new_item_name' => __('Име на нов парфюмерист', 'parfume-reviews'),
            'menu_name' => __('Парфюмеристи', 'parfume-reviews'),
        );
        
        $this->args = array(
            'hierarchical' => true,
            'show_ui' => true,
            'show_admin_column' => true,
            'query_var' => true,
            'public' => true,
            'publicly_queryable' => true,
            'show_in_rest' => true,
            'meta_box_cb' => 'post_categories_meta_box',
            'show_in_nav_menus' => true,
            'show_tagcloud' => true,
            'rewrite' => array(
                'slug' => $this->get_taxonomy_slug(),
                'with_front' => false,
                'hierarchical' => false
            ),
        );
        
        // Register taxonomy
        $this->register_taxonomy();
        
        // Add hooks
        add_action($this->taxonomy . '_add_form_fields', array($this, 'add_meta_fields'), 10, 1);
        add_action($this->taxonomy . '_edit_form_fields', array($this, 'edit_meta_fields'), 10, 2);
        add_action('created_' . $this->taxonomy, array($this, 'save_meta_fields'), 10, 2);
        add_action('edited_' . $this->taxonomy, array($this, 'save_meta_fields'), 10, 2);
        
        // Admin columns
        add_filter('manage_edit-' . $this->taxonomy . '_columns', array($this, 'admin_columns'));
        add_filter('manage_' . $this->taxonomy . '_custom_column', array($this, 'admin_column_content'), 10, 3);
        
        // Add default terms
        add_action('init', array($this, 'create_default_terms'), 20);
    }
    
    private function get_taxonomy_slug() {
        $settings = get_option('parfume_reviews_settings', array());
        $parfume_slug = !empty($settings['parfume_slug']) ? $settings['parfume_slug'] : 'parfiumi';
        $perfumers_slug = !empty($settings['perfumers_slug']) ? $settings['perfumers_slug'] : 'parfumers';
        
        return $parfume_slug . '/' . $perfumers_slug;
    }
    
    public function add_meta_fields() {
        ?>
        <div class="form-field term-group">
            <label for="perfumer_photo"><?php _e('Снимка на парфюмериста', 'parfume-reviews'); ?></label>
            <input type="hidden" id="perfumer_photo" name="perfumer_photo" value="">
            <div id="perfumer_photo_preview"></div>
            <p>
                <button type="button" class="button perfumer_photo_upload"><?php _e('Качи снимка', 'parfume-reviews'); ?></button>
                <button type="button" class="button perfumer_photo_remove" style="display:none;"><?php _e('Премахни', 'parfume-reviews'); ?></button>
            </p>
        </div>
        
        <div class="form-field">
            <label for="perfumer_nationality"><?php _e('Националност', 'parfume-reviews'); ?></label>
            <select name="perfumer_nationality" id="perfumer_nationality">
                <option value=""><?php _e('Избери националност', 'parfume-reviews'); ?></option>
                <option value="french">Френска</option>
                <option value="italian">Италианска</option>
                <option value="american">Американска</option>
                <option value="british">Британска</option>
                <option value="german">Германска</option>
                <option value="spanish">Испанска</option>
                <option value="swiss">Швейцарска</option>
                <option value="dutch">Холандска</option>
                <option value="other">Друга</option>
            </select>
        </div>
        
        <div class="form-field">
            <label for="perfumer_birth_year"><?php _e('Година на раждане', 'parfume-reviews'); ?></label>
            <input type="number" name="perfumer_birth_year" id="perfumer_birth_year" min="1900" max="<?php echo date('Y'); ?>" placeholder="<?php _e('Година', 'parfume-reviews'); ?>">
        </div>
        
        <div class="form-field">
            <label for="perfumer_career_start"><?php _e('Начало на кариерата', 'parfume-reviews'); ?></label>
            <input type="number" name="perfumer_career_start" id="perfumer_career_start" min="1950" max="<?php echo date('Y'); ?>" placeholder="<?php _e('Година', 'parfume-reviews'); ?>">
        </div>
        
        <div class="form-field">
            <label for="perfumer_style"><?php _e('Стил', 'parfume-reviews'); ?></label>
            <select name="perfumer_style" id="perfumer_style">
                <option value=""><?php _e('Избери стил', 'parfume-reviews'); ?></option>
                <option value="classic">Класически</option>
                <option value="modern">Модерен</option>
                <option value="avant-garde">Авангарден</option>
                <option value="oriental">Ориенталски</option>
                <option value="fresh">Свеж</option>
                <option value="floral">Флорален</option>
                <option value="woody">Дървесен</option>
            </select>
        </div>
        
        <div class="form-field">
            <label for="perfumer_awards"><?php _e('Награди', 'parfume-reviews'); ?></label>
            <textarea name="perfumer_awards" id="perfumer_awards" rows="3" placeholder="<?php _e('Най-важни награди и признания...', 'parfume-reviews'); ?>"></textarea>
        </div>
        <?php
    }
    
    public function edit_meta_fields($term, $taxonomy) {
        $photo_id = get_term_meta($term->term_id, 'perfumer_photo', true);
        $nationality = get_term_meta($term->term_id, 'perfumer_nationality', true);
        $birth_year = get_term_meta($term->term_id, 'perfumer_birth_year', true);
        $career_start = get_term_meta($term->term_id, 'perfumer_career_start', true);
        $style = get_term_meta($term->term_id, 'perfumer_style', true);
        $awards = get_term_meta($term->term_id, 'perfumer_awards', true);
        ?>
        <tr class="form-field">
            <th scope="row">
                <label for="perfumer_photo"><?php _e('Снимка на парфюмериста', 'parfume-reviews'); ?></label>
            </th>
            <td>
                <input type="hidden" id="perfumer_photo" name="perfumer_photo" value="<?php echo esc_attr($photo_id); ?>">
                <div id="perfumer_photo_preview">
                    <?php if ($photo_id): ?>
                        <?php echo wp_get_attachment_image($photo_id, 'thumbnail'); ?>
                    <?php endif; ?>
                </div>
                <p>
                    <button type="button" class="button perfumer_photo_upload"><?php _e('Качи снимка', 'parfume-reviews'); ?></button>
                    <button type="button" class="button perfumer_photo_remove" <?php echo !$photo_id ? 'style="display:none;"' : ''; ?>><?php _e('Премахни', 'parfume-reviews'); ?></button>
                </p>
            </td>
        </tr>
        
        <tr class="form-field">
            <th scope="row">
                <label for="perfumer_nationality"><?php _e('Националност', 'parfume-reviews'); ?></label>
            </th>
            <td>
                <select name="perfumer_nationality" id="perfumer_nationality">
                    <option value=""><?php _e('Избери националност', 'parfume-reviews'); ?></option>
                    <option value="french" <?php selected($nationality, 'french'); ?>>Френска</option>
                    <option value="italian" <?php selected($nationality, 'italian'); ?>>Италианска</option>
                    <option value="american" <?php selected($nationality, 'american'); ?>>Американска</option>
                    <option value="british" <?php selected($nationality, 'british'); ?>>Британска</option>
                    <option value="german" <?php selected($nationality, 'german'); ?>>Германска</option>
                    <option value="spanish" <?php selected($nationality, 'spanish'); ?>>Испанска</option>
                    <option value="swiss" <?php selected($nationality, 'swiss'); ?>>Швейцарска</option>
                    <option value="dutch" <?php selected($nationality, 'dutch'); ?>>Холандска</option>
                    <option value="other" <?php selected($nationality, 'other'); ?>>Друга</option>
                </select>
            </td>
        </tr>
        
        <tr class="form-field">
            <th scope="row">
                <label for="perfumer_birth_year"><?php _e('Година на раждане', 'parfume-reviews'); ?></label>
            </th>
            <td>
                <input type="number" name="perfumer_birth_year" id="perfumer_birth_year" value="<?php echo esc_attr($birth_year); ?>" min="1900" max="<?php echo date('Y'); ?>" placeholder="<?php _e('Година', 'parfume-reviews'); ?>">
            </td>
        </tr>
        
        <tr class="form-field">
            <th scope="row">
                <label for="perfumer_career_start"><?php _e('Начало на кариерата', 'parfume-reviews'); ?></label>
            </th>
            <td>
                <input type="number" name="perfumer_career_start" id="perfumer_career_start" value="<?php echo esc_attr($career_start); ?>" min="1950" max="<?php echo date('Y'); ?>" placeholder="<?php _e('Година', 'parfume-reviews'); ?>">
            </td>
        </tr>
        
        <tr class="form-field">
            <th scope="row">
                <label for="perfumer_style"><?php _e('Стил', 'parfume-reviews'); ?></label>
            </th>
            <td>
                <select name="perfumer_style" id="perfumer_style">
                    <option value=""><?php _e('Избери стил', 'parfume-reviews'); ?></option>
                    <option value="classic" <?php selected($style, 'classic'); ?>>Класически</option>
                    <option value="modern" <?php selected($style, 'modern'); ?>>Модерен</option>
                    <option value="avant-garde" <?php selected($style, 'avant-garde'); ?>>Авангарден</option>
                    <option value="oriental" <?php selected($style, 'oriental'); ?>>Ориенталски</option>
                    <option value="fresh" <?php selected($style, 'fresh'); ?>>Свеж</option>
                    <option value="floral" <?php selected($style, 'floral'); ?>>Флорален</option>
                    <option value="woody" <?php selected($style, 'woody'); ?>>Дървесен</option>
                </select>
            </td>
        </tr>
        
        <tr class="form-field">
            <th scope="row">
                <label for="perfumer_awards"><?php _e('Награди', 'parfume-reviews'); ?></label>
            </th>
            <td>
                <textarea name="perfumer_awards" id="perfumer_awards" rows="3" placeholder="<?php _e('Най-важни награди и признания...', 'parfume-reviews'); ?>"><?php echo esc_textarea($awards); ?></textarea>
            </td>
        </tr>
        
        <script>
        jQuery(document).ready(function($) {
            var frame;
            
            $('.perfumer_photo_upload').on('click', function(e) {
                e.preventDefault();
                
                if (frame) {
                    frame.open();
                    return;
                }
                
                frame = wp.media({
                    title: '<?php _e('Избери снимка', 'parfume-reviews'); ?>',
                    button: {
                        text: '<?php _e('Използвай това изображение', 'parfume-reviews'); ?>'
                    },
                    multiple: false
                });
                
                frame.on('select', function() {
                    var attachment = frame.state().get('selection').first().toJSON();
                    $('#perfumer_photo').val(attachment.id);
                    $('#perfumer_photo_preview').html('<img src="' + attachment.sizes.thumbnail.url + '" style="max-width: 150px;">');
                    $('.perfumer_photo_remove').show();
                });
                
                frame.open();
            });
            
            $('.perfumer_photo_remove').on('click', function(e) {
                e.preventDefault();
                $('#perfumer_photo').val('');
                $('#perfumer_photo_preview').empty();
                $(this).hide();
            });
        });
        </script>
        <?php
    }
    
    public function save_meta_fields($term_id, $taxonomy) {
        if (isset($_POST['perfumer_photo'])) {
            update_term_meta($term_id, 'perfumer_photo', absint($_POST['perfumer_photo']));
        }
        
        if (isset($_POST['perfumer_nationality'])) {
            update_term_meta($term_id, 'perfumer_nationality', sanitize_text_field($_POST['perfumer_nationality']));
        }
        
        if (isset($_POST['perfumer_birth_year'])) {
            update_term_meta($term_id, 'perfumer_birth_year', absint($_POST['perfumer_birth_year']));
        }
        
        if (isset($_POST['perfumer_career_start'])) {
            update_term_meta($term_id, 'perfumer_career_start', absint($_POST['perfumer_career_start']));
        }
        
        if (isset($_POST['perfumer_style'])) {
            update_term_meta($term_id, 'perfumer_style', sanitize_text_field($_POST['perfumer_style']));
        }
        
        if (isset($_POST['perfumer_awards'])) {
            update_term_meta($term_id, 'perfumer_awards', sanitize_textarea_field($_POST['perfumer_awards']));
        }
    }
    
    public function admin_columns($columns) {
        $new_columns = array();
        
        if (isset($columns['cb'])) {
            $new_columns['cb'] = $columns['cb'];
        }
        
        $new_columns['photo'] = __('Снимка', 'parfume-reviews');
        $new_columns['name'] = __('Име', 'parfume-reviews');
        $new_columns['nationality'] = __('Националност', 'parfume-reviews');
        $new_columns['style'] = __('Стил', 'parfume-reviews');
        $new_columns['perfumes'] = __('Парфюми', 'parfume-reviews');
        $new_columns['slug'] = __('Slug', 'parfume-reviews');
        
        return $new_columns;
    }
    
    public function admin_column_content($content, $column_name, $term_id) {
        switch ($column_name) {
            case 'photo':
                $photo_id = get_term_meta($term_id, 'perfumer_photo', true);
                if ($photo_id) {
                    $content = wp_get_attachment_image($photo_id, array(40, 40));
                } else {
                    $content = '<span style="color: #999;">—</span>';
                }
                break;
                
            case 'nationality':
                $nationality = get_term_meta($term_id, 'perfumer_nationality', true);
                $nationalities = array(
                    'french' => '🇫🇷 Френска',
                    'italian' => '🇮🇹 Италианска',
                    'american' => '🇺🇸 Американска',
                    'british' => '🇬🇧 Британска',
                    'german' => '🇩🇪 Германска',
                    'spanish' => '🇪🇸 Испанска',
                    'swiss' => '🇨🇭 Швейцарска',
                    'dutch' => '🇳🇱 Холандска',
                    'other' => '🌍 Друга'
                );
                $content = $nationality && isset($nationalities[$nationality]) ? $nationalities[$nationality] : '<span style="color: #999;">—</span>';
                break;
                
            case 'style':
                $style = get_term_meta($term_id, 'perfumer_style', true);
                $styles = array(
                    'classic' => 'Класически',
                    'modern' => 'Модерен',
                    'avant-garde' => 'Авангарден',
                    'oriental' => 'Ориенталски',
                    'fresh' => 'Свеж',
                    'floral' => 'Флорален',
                    'woody' => 'Дървесен'
                );
                $content = $style && isset($styles[$style]) ? $styles[$style] : '<span style="color: #999;">—</span>';
                break;
                
            case 'perfumes':
                $term = get_term($term_id);
                $content = '<strong>' . $term->count . '</strong>';
                break;
        }
        
        return $content;
    }
    
    public function create_default_terms() {
        $default_perfumers = array(
            'Алберто Морилас' => array('nationality' => 'spanish', 'birth_year' => 1950, 'career_start' => 1970, 'style' => 'classic'),
            'Куентин Биш' => array('nationality' => 'french', 'birth_year' => 1971, 'career_start' => 1991, 'style' => 'modern'),
            'Доминик Ропион' => array('nationality' => 'french', 'birth_year' => 1962, 'career_start' => 1982, 'style' => 'classic'),
            'Оливие Кресп' => array('nationality' => 'french', 'birth_year' => 1955, 'career_start' => 1975, 'style' => 'oriental'),
            'Франсоа Демаши' => array('nationality' => 'french', 'birth_year' => 1958, 'career_start' => 1978, 'style' => 'fresh'),
            'Кристофър Шелдрейк' => array('nationality' => 'british', 'birth_year' => 1962, 'career_start' => 1982, 'style' => 'avant-garde'),
            'Жак Кавалие' => array('nationality' => 'french', 'birth_year' => 1962, 'career_start' => 1982, 'style' => 'floral'),
            'Анок Филибер' => array('nationality' => 'french', 'birth_year' => 1960, 'career_start' => 1980, 'style' => 'woody'),
            'Мишел Жирар' => array('nationality' => 'french', 'birth_year' => 1946, 'career_start' => 1966, 'style' => 'classic'),
            'Пиер Монтале' => array('nationality' => 'french', 'birth_year' => 1952, 'career_start' => 1972, 'style' => 'oriental')
        );
        
        foreach ($default_perfumers as $perfumer_name => $perfumer_data) {
            if (!term_exists($perfumer_name, $this->taxonomy)) {
                $term = wp_insert_term($perfumer_name, $this->taxonomy);
                if (!is_wp_error($term) && isset($term['term_id'])) {
                    foreach ($perfumer_data as $meta_key => $meta_value) {
                        update_term_meta($term['term_id'], 'perfumer_' . $meta_key, $meta_value);
                    }
                }
            }
        }
    }
    
    public function get_statistics() {
        $stats = array();
        
        // Total perfumers
        $total_perfumers = wp_count_terms(array(
            'taxonomy' => $this->taxonomy,
            'hide_empty' => false,
        ));
        
        $stats['total'] = is_wp_error($total_perfumers) ? 0 : $total_perfumers;
        
        // Active perfumers (with perfumes)
        $active_perfumers = wp_count_terms(array(
            'taxonomy' => $this->taxonomy,
            'hide_empty' => true,
        ));
        
        $stats['active'] = is_wp_error($active_perfumers) ? 0 : $active_perfumers;
        
        // Top perfumers by perfume count
        $top_perfumers = get_terms(array(
            'taxonomy' => $this->taxonomy,
            'orderby' => 'count',
            'order' => 'DESC',
            'number' => 5,
            'hide_empty' => true,
        ));
        
        $stats['top_perfumers'] = is_wp_error($top_perfumers) ? array() : $top_perfumers;
        
        return $stats;
    }
}