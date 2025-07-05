<?php
namespace Parfume_Reviews\Taxonomies;

use Parfume_Reviews\Utils\Taxonomy_Base;

class Brands extends Taxonomy_Base {
    
    public function init() {
        $this->taxonomy = 'marki';
        $this->post_types = array('parfume');
        $this->labels = array(
            'name' => __('Марки', 'parfume-reviews'),
            'singular_name' => __('Марка', 'parfume-reviews'),
            'search_items' => __('Търсене в марките', 'parfume-reviews'),
            'all_items' => __('Всички марки', 'parfume-reviews'),
            'edit_item' => __('Редактиране на марка', 'parfume-reviews'),
            'update_item' => __('Обновяване на марка', 'parfume-reviews'),
            'add_new_item' => __('Добавяне на нова марка', 'parfume-reviews'),
            'new_item_name' => __('Име на нова марка', 'parfume-reviews'),
            'menu_name' => __('Марки', 'parfume-reviews'),
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
        $brands_slug = !empty($settings['brands_slug']) ? $settings['brands_slug'] : 'marki';
        
        return $parfume_slug . '/' . $brands_slug;
    }
    
    public function add_meta_fields() {
        ?>
        <div class="form-field term-group">
            <label for="brand_logo"><?php _e('Лого на марката', 'parfume-reviews'); ?></label>
            <input type="hidden" id="brand_logo" name="brand_logo" value="">
            <div id="brand_logo_preview"></div>
            <p>
                <button type="button" class="button brand_logo_upload"><?php _e('Качи лого', 'parfume-reviews'); ?></button>
                <button type="button" class="button brand_logo_remove" style="display:none;"><?php _e('Премахни', 'parfume-reviews'); ?></button>
            </p>
        </div>
        
        <div class="form-field">
            <label for="brand_country"><?php _e('Държава', 'parfume-reviews'); ?></label>
            <select name="brand_country" id="brand_country">
                <option value=""><?php _e('Избери държава', 'parfume-reviews'); ?></option>
                <option value="france">Франция</option>
                <option value="italy">Италия</option>
                <option value="usa">САЩ</option>
                <option value="uk">Великобритания</option>
                <option value="germany">Германия</option>
                <option value="uae">ОАЕ</option>
                <option value="spain">Испания</option>
                <option value="other">Друга</option>
            </select>
        </div>
        
        <div class="form-field">
            <label for="brand_founded"><?php _e('Основана', 'parfume-reviews'); ?></label>
            <input type="number" name="brand_founded" id="brand_founded" min="1800" max="<?php echo date('Y'); ?>" placeholder="<?php _e('Година', 'parfume-reviews'); ?>">
        </div>
        
        <div class="form-field">
            <label for="brand_website"><?php _e('Уебсайт', 'parfume-reviews'); ?></label>
            <input type="url" name="brand_website" id="brand_website" placeholder="https://">
        </div>
        <?php
    }
    
    public function edit_meta_fields($term, $taxonomy) {
        $logo_id = get_term_meta($term->term_id, 'brand_logo', true);
        $country = get_term_meta($term->term_id, 'brand_country', true);
        $founded = get_term_meta($term->term_id, 'brand_founded', true);
        $website = get_term_meta($term->term_id, 'brand_website', true);
        ?>
        <tr class="form-field">
            <th scope="row">
                <label for="brand_logo"><?php _e('Лого на марката', 'parfume-reviews'); ?></label>
            </th>
            <td>
                <input type="hidden" id="brand_logo" name="brand_logo" value="<?php echo esc_attr($logo_id); ?>">
                <div id="brand_logo_preview">
                    <?php if ($logo_id): ?>
                        <?php echo wp_get_attachment_image($logo_id, 'thumbnail'); ?>
                    <?php endif; ?>
                </div>
                <p>
                    <button type="button" class="button brand_logo_upload"><?php _e('Качи лого', 'parfume-reviews'); ?></button>
                    <button type="button" class="button brand_logo_remove" <?php echo !$logo_id ? 'style="display:none;"' : ''; ?>><?php _e('Премахни', 'parfume-reviews'); ?></button>
                </p>
            </td>
        </tr>
        
        <tr class="form-field">
            <th scope="row">
                <label for="brand_country"><?php _e('Държава', 'parfume-reviews'); ?></label>
            </th>
            <td>
                <select name="brand_country" id="brand_country">
                    <option value=""><?php _e('Избери държава', 'parfume-reviews'); ?></option>
                    <option value="france" <?php selected($country, 'france'); ?>>Франция</option>
                    <option value="italy" <?php selected($country, 'italy'); ?>>Италия</option>
                    <option value="usa" <?php selected($country, 'usa'); ?>>САЩ</option>
                    <option value="uk" <?php selected($country, 'uk'); ?>>Великобритания</option>
                    <option value="germany" <?php selected($country, 'germany'); ?>>Германия</option>
                    <option value="uae" <?php selected($country, 'uae'); ?>>ОАЕ</option>
                    <option value="spain" <?php selected($country, 'spain'); ?>>Испания</option>
                    <option value="other" <?php selected($country, 'other'); ?>>Друга</option>
                </select>
            </td>
        </tr>
        
        <tr class="form-field">
            <th scope="row">
                <label for="brand_founded"><?php _e('Основана', 'parfume-reviews'); ?></label>
            </th>
            <td>
                <input type="number" name="brand_founded" id="brand_founded" value="<?php echo esc_attr($founded); ?>" min="1800" max="<?php echo date('Y'); ?>" placeholder="<?php _e('Година', 'parfume-reviews'); ?>">
            </td>
        </tr>
        
        <tr class="form-field">
            <th scope="row">
                <label for="brand_website"><?php _e('Уебсайт', 'parfume-reviews'); ?></label>
            </th>
            <td>
                <input type="url" name="brand_website" id="brand_website" value="<?php echo esc_attr($website); ?>" placeholder="https://">
            </td>
        </tr>
        
        <script>
        jQuery(document).ready(function($) {
            var frame;
            
            $('.brand_logo_upload').on('click', function(e) {
                e.preventDefault();
                
                if (frame) {
                    frame.open();
                    return;
                }
                
                frame = wp.media({
                    title: '<?php _e('Избери лого', 'parfume-reviews'); ?>',
                    button: {
                        text: '<?php _e('Използвай това изображение', 'parfume-reviews'); ?>'
                    },
                    multiple: false
                });
                
                frame.on('select', function() {
                    var attachment = frame.state().get('selection').first().toJSON();
                    $('#brand_logo').val(attachment.id);
                    $('#brand_logo_preview').html('<img src="' + attachment.sizes.thumbnail.url + '" style="max-width: 150px;">');
                    $('.brand_logo_remove').show();
                });
                
                frame.open();
            });
            
            $('.brand_logo_remove').on('click', function(e) {
                e.preventDefault();
                $('#brand_logo').val('');
                $('#brand_logo_preview').empty();
                $(this).hide();
            });
        });
        </script>
        <?php
    }
    
    public function save_meta_fields($term_id, $taxonomy) {
        if (isset($_POST['brand_logo'])) {
            update_term_meta($term_id, 'brand_logo', absint($_POST['brand_logo']));
        }
        
        if (isset($_POST['brand_country'])) {
            update_term_meta($term_id, 'brand_country', sanitize_text_field($_POST['brand_country']));
        }
        
        if (isset($_POST['brand_founded'])) {
            update_term_meta($term_id, 'brand_founded', absint($_POST['brand_founded']));
        }
        
        if (isset($_POST['brand_website'])) {
            update_term_meta($term_id, 'brand_website', esc_url_raw($_POST['brand_website']));
        }
    }
    
    public function admin_columns($columns) {
        $new_columns = array();
        
        if (isset($columns['cb'])) {
            $new_columns['cb'] = $columns['cb'];
        }
        
        $new_columns['logo'] = __('Лого', 'parfume-reviews');
        $new_columns['name'] = __('Име', 'parfume-reviews');
        $new_columns['country'] = __('Държава', 'parfume-reviews');
        $new_columns['founded'] = __('Основана', 'parfume-reviews');
        $new_columns['perfumes'] = __('Парфюми', 'parfume-reviews');
        $new_columns['slug'] = __('Slug', 'parfume-reviews');
        
        return $new_columns;
    }
    
    public function admin_column_content($content, $column_name, $term_id) {
        switch ($column_name) {
            case 'logo':
                $logo_id = get_term_meta($term_id, 'brand_logo', true);
                if ($logo_id) {
                    $content = wp_get_attachment_image($logo_id, array(40, 40));
                } else {
                    $content = '<span style="color: #999;">—</span>';
                }
                break;
                
            case 'country':
                $country = get_term_meta($term_id, 'brand_country', true);
                $countries = array(
                    'france' => '🇫🇷 Франция',
                    'italy' => '🇮🇹 Италия',
                    'usa' => '🇺🇸 САЩ',
                    'uk' => '🇬🇧 Великобритания',
                    'germany' => '🇩🇪 Германия',
                    'uae' => '🇦🇪 ОАЕ',
                    'spain' => '🇪🇸 Испания',
                    'other' => '🌍 Друга'
                );
                $content = $country && isset($countries[$country]) ? $countries[$country] : '<span style="color: #999;">—</span>';
                break;
                
            case 'founded':
                $founded = get_term_meta($term_id, 'brand_founded', true);
                $content = $founded ? esc_html($founded) : '<span style="color: #999;">—</span>';
                break;
                
            case 'perfumes':
                $term = get_term($term_id);
                $content = '<strong>' . $term->count . '</strong>';
                break;
        }
        
        return $content;
    }
    
    public function create_default_terms() {
        $default_brands = array(
            'Giorgio Armani' => array('country' => 'italy', 'founded' => 1975),
            'Tom Ford' => array('country' => 'usa', 'founded' => 2006),
            'Rabanne' => array('country' => 'france', 'founded' => 1966),
            'Dior' => array('country' => 'france', 'founded' => 1946),
            'Dolce&Gabbana' => array('country' => 'italy', 'founded' => 1985),
            'Lattafa' => array('country' => 'uae', 'founded' => 1982),
            'Jean Paul Gaultier' => array('country' => 'france', 'founded' => 1976),
            'Versace' => array('country' => 'italy', 'founded' => 1978),
            'Carolina Herrera' => array('country' => 'usa', 'founded' => 1980),
            'Yves Saint Laurent' => array('country' => 'france', 'founded' => 1961),
            'Hugo Boss' => array('country' => 'germany', 'founded' => 1924),
            'Valentino' => array('country' => 'italy', 'founded' => 1960),
            'Bvlgari' => array('country' => 'italy', 'founded' => 1884),
            'Guerlain' => array('country' => 'france', 'founded' => 1828),
            'Xerjoff' => array('country' => 'italy', 'founded' => 2003),
            'Mugler' => array('country' => 'france', 'founded' => 1973),
            'Chanel' => array('country' => 'france', 'founded' => 1910),
            'Creed' => array('country' => 'uk', 'founded' => 1760),
            'Maison Francis Kurkdjian' => array('country' => 'france', 'founded' => 2009),
            'Amouage' => array('country' => 'other', 'founded' => 1983)
        );
        
        foreach ($default_brands as $brand_name => $brand_data) {
            if (!term_exists($brand_name, $this->taxonomy)) {
                $term = wp_insert_term($brand_name, $this->taxonomy);
                if (!is_wp_error($term) && isset($term['term_id'])) {
                    update_term_meta($term['term_id'], 'brand_country', $brand_data['country']);
                    update_term_meta($term['term_id'], 'brand_founded', $brand_data['founded']);
                }
            }
        }
    }
    
    public function get_statistics() {
        $stats = array();
        
        // Total brands
        $total_brands = wp_count_terms(array(
            'taxonomy' => $this->taxonomy,
            'hide_empty' => false,
        ));
        
        $stats['total'] = is_wp_error($total_brands) ? 0 : $total_brands;
        
        // Brands with perfumes
        $with_perfumes = wp_count_terms(array(
            'taxonomy' => $this->taxonomy,
            'hide_empty' => true,
        ));
        
        $stats['with_perfumes'] = is_wp_error($with_perfumes) ? 0 : $with_perfumes;
        
        // Top brands by perfume count
        $top_brands = get_terms(array(
            'taxonomy' => $this->taxonomy,
            'orderby' => 'count',
            'order' => 'DESC',
            'number' => 5,
            'hide_empty' => true,
        ));
        
        $stats['top_brands'] = is_wp_error($top_brands) ? array() : $top_brands;
        
        return $stats;
    }
}