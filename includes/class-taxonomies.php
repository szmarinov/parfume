<?php
namespace Parfume_Reviews;

class Taxonomies {
    public function __construct() {
        add_action('init', array($this, 'register_taxonomies'), 0);
        add_action('admin_init', array($this, 'add_taxonomy_meta_fields'));
        add_action('created_term', array($this, 'save_taxonomy_meta_fields'), 10, 3);
        add_action('edit_term', array($this, 'save_taxonomy_meta_fields'), 10, 3);
        add_filter('template_include', array($this, 'template_loader'));
        
        // Add custom rewrite rules and query vars
        add_action('init', array($this, 'add_custom_rewrite_rules'), 20);
        add_filter('query_vars', array($this, 'add_query_vars'));
        add_action('parse_request', array($this, 'parse_custom_requests'));
        
        // Add Yoast SEO and RankMath support
        add_action('init', array($this, 'add_seo_support'));
        
        // Fix taxonomy image upload
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }
    
    public function register_taxonomies() {
        $settings = get_option('parfume_reviews_settings', array());
        
        // Get the base parfume slug
        $parfume_slug = !empty($settings['parfume_slug']) ? $settings['parfume_slug'] : 'parfiumi';
        
        // Gender taxonomy
        $gender_slug = !empty($settings['gender_slug']) ? $settings['gender_slug'] : 'gender';
        
        $gender_labels = array(
            'name' => __('Категории', 'parfume-reviews'),
            'singular_name' => __('Категория', 'parfume-reviews'),
            'search_items' => __('Търсене в категориите', 'parfume-reviews'),
            'all_items' => __('Всички категории', 'parfume-reviews'),
            'edit_item' => __('Редактиране на категория', 'parfume-reviews'),
            'update_item' => __('Обновяване на категория', 'parfume-reviews'),
            'add_new_item' => __('Добавяне на нова категория', 'parfume-reviews'),
            'new_item_name' => __('Име на нова категория', 'parfume-reviews'),
            'menu_name' => __('Категории', 'parfume-reviews'),
        );
        
        register_taxonomy('gender', 'parfume', array(
            'labels' => $gender_labels,
            'hierarchical' => true,
            'show_ui' => true,
            'show_admin_column' => true,
            'query_var' => true,
            'rewrite' => array(
                'slug' => $parfume_slug . '/' . $gender_slug,
                'with_front' => false,
                'hierarchical' => false
            ),
            'show_in_rest' => true,
            'public' => true,
            'publicly_queryable' => true,
            'meta_box_cb' => 'post_categories_meta_box',
            // SEO support
            'show_in_nav_menus' => true,
            'show_tagcloud' => true,
        ));
        
        // Add default terms including new ones
        $default_genders = array(
            'Мъжки парфюми',
            'Дамски парфюми', 
            'Арабски парфюми',
            'Луксозни парфюми',
            'Нишови парфюми'
        );
        
        foreach ($default_genders as $gender) {
            if (!term_exists($gender, 'gender')) {
                wp_insert_term($gender, 'gender');
            }
        }
        
        // Aroma Type taxonomy
        $aroma_type_slug = !empty($settings['aroma_type_slug']) ? $settings['aroma_type_slug'] : 'aroma-type';
        
        $aroma_type_labels = array(
            'name' => __('Видове аромати', 'parfume-reviews'),
            'singular_name' => __('Вид арома', 'parfume-reviews'),
            'search_items' => __('Търсене във видовете аромати', 'parfume-reviews'),
            'all_items' => __('Всички видове аромати', 'parfume-reviews'),
            'edit_item' => __('Редактиране на вид арома', 'parfume-reviews'),
            'update_item' => __('Обновяване на вид арома', 'parfume-reviews'),
            'add_new_item' => __('Добавяне на нов вид арома', 'parfume-reviews'),
            'new_item_name' => __('Име на нов вид арома', 'parfume-reviews'),
            'menu_name' => __('Видове аромати', 'parfume-reviews'),
        );
        
        register_taxonomy('aroma_type', 'parfume', array(
            'labels' => $aroma_type_labels,
            'hierarchical' => true,
            'show_ui' => true,
            'show_admin_column' => true,
            'query_var' => true,
            'rewrite' => array(
                'slug' => $parfume_slug . '/' . $aroma_type_slug,
                'with_front' => false,
                'hierarchical' => false
            ),
            'show_in_rest' => true,
            'public' => true,
            'publicly_queryable' => true,
            'meta_box_cb' => 'post_categories_meta_box',
            // SEO support
            'show_in_nav_menus' => true,
            'show_tagcloud' => true,
        ));
        
        // Add default terms
        $default_aroma_types = array(
            'Тоалетна вода',
            'Парфюмна вода',
            'Парфюм',
            'Парфюмен елексир'
        );
        
        foreach ($default_aroma_types as $type) {
            if (!term_exists($type, 'aroma_type')) {
                wp_insert_term($type, 'aroma_type');
            }
        }
        
        // Brands taxonomy
        $brands_slug = !empty($settings['brands_slug']) ? $settings['brands_slug'] : 'marki';
        
        $brands_labels = array(
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
        
        register_taxonomy('marki', 'parfume', array(
            'labels' => $brands_labels,
            'hierarchical' => true,
            'show_ui' => true,
            'show_admin_column' => true,
            'query_var' => true,
            'rewrite' => array(
                'slug' => $parfume_slug . '/' . $brands_slug,
                'with_front' => false,
                'hierarchical' => false
            ),
            'show_in_rest' => true,
            'public' => true,
            'publicly_queryable' => true,
            'meta_box_cb' => 'post_categories_meta_box',
            // SEO support
            'show_in_nav_menus' => true,
            'show_tagcloud' => true,
        ));
        
        // Add default brands
        $default_brands = array(
            'Giorgio Armani', 'Tom Ford', 'Rabanne', 'Dior', 'Dolce&Gabbana', 'Lattafa', 
            'Jean Paul Gaultier', 'Versace', 'Carolina Herrera', 'Yves Saint Laurent',
            'Hugo Boss', 'Valentino', 'Bvlgari', 'Guerlain', 'Xerjoff', 'Mugler',
            'Chanel', 'Creed', 'Maison Francis Kurkdjian', 'Amouage'
        );
        
        foreach ($default_brands as $brand) {
            if (!term_exists($brand, 'marki')) {
                wp_insert_term($brand, 'marki');
            }
        }
        
        // Season taxonomy
        $season_slug = !empty($settings['season_slug']) ? $settings['season_slug'] : 'season';
        
        $season_labels = array(
            'name' => __('Сезони', 'parfume-reviews'),
            'singular_name' => __('Сезон', 'parfume-reviews'),
            'menu_name' => __('Сезони', 'parfume-reviews'),
        );
        
        register_taxonomy('season', 'parfume', array(
            'labels' => $season_labels,
            'hierarchical' => true,
            'show_ui' => true,
            'show_admin_column' => true,
            'query_var' => true,
            'rewrite' => array(
                'slug' => $parfume_slug . '/' . $season_slug,
                'with_front' => false,
                'hierarchical' => false
            ),
            'show_in_rest' => true,
            'public' => true,
            'publicly_queryable' => true,
            'meta_box_cb' => 'post_categories_meta_box',
            // SEO support
            'show_in_nav_menus' => true,
            'show_tagcloud' => true,
        ));
        
        // Add default seasons
        $default_seasons = array('Пролет', 'Лято', 'Есен', 'Зима');
        
        foreach ($default_seasons as $season) {
            if (!term_exists($season, 'season')) {
                wp_insert_term($season, 'season');
            }
        }
        
        // Intensity taxonomy
        $intensity_slug = !empty($settings['intensity_slug']) ? $settings['intensity_slug'] : 'intensity';
        
        $intensity_labels = array(
            'name' => __('Интензивност', 'parfume-reviews'),
            'singular_name' => __('Интензивност', 'parfume-reviews'),
            'menu_name' => __('Интензивност', 'parfume-reviews'),
        );
        
        register_taxonomy('intensity', 'parfume', array(
            'labels' => $intensity_labels,
            'hierarchical' => true,
            'show_ui' => true,
            'show_admin_column' => true,
            'query_var' => true,
            'rewrite' => array(
                'slug' => $parfume_slug . '/' . $intensity_slug,
                'with_front' => false,
                'hierarchical' => false
            ),
            'show_in_rest' => true,
            'public' => true,
            'publicly_queryable' => true,
            'meta_box_cb' => 'post_categories_meta_box',
            // SEO support
            'show_in_nav_menus' => true,
            'show_tagcloud' => true,
        ));
        
        // Add default intensities
        $default_intensities = array('Силни', 'Средни', 'Леки');
        
        foreach ($default_intensities as $intensity) {
            if (!term_exists($intensity, 'intensity')) {
                wp_insert_term($intensity, 'intensity');
            }
        }
        
        // Notes taxonomy with Group field
        $notes_slug = !empty($settings['notes_slug']) ? $settings['notes_slug'] : 'notes';
        
        $notes_labels = array(
            'name' => __('Ароматни нотки', 'parfume-reviews'),
            'singular_name' => __('Ароматна нотка', 'parfume-reviews'),
            'menu_name' => __('Ароматни нотки', 'parfume-reviews'),
        );
        
        register_taxonomy('notes', 'parfume', array(
            'labels' => $notes_labels,
            'hierarchical' => true,
            'show_ui' => true,
            'show_admin_column' => true,
            'query_var' => true,
            'rewrite' => array(
                'slug' => $parfume_slug . '/' . $notes_slug,
                'with_front' => false,
                'hierarchical' => false
            ),
            'show_in_rest' => true,
            'public' => true,
            'publicly_queryable' => true,
            'meta_box_cb' => 'post_categories_meta_box',
            // SEO support
            'show_in_nav_menus' => true,
            'show_tagcloud' => true,
        ));
        
        // Add default notes with groups
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
            'Шафран' => 'Подправки'
        );
        
        foreach ($default_notes as $note => $group) {
            if (!term_exists($note, 'notes')) {
                $term = wp_insert_term($note, 'notes');
                if (!is_wp_error($term)) {
                    update_term_meta($term['term_id'], 'note_group', $group);
                }
            }
        }
        
        // Perfumer taxonomy
        $perfumers_slug = !empty($settings['perfumers_slug']) ? $settings['perfumers_slug'] : 'parfumers';
        
        $perfumer_labels = array(
            'name' => __('Парфюмеристи', 'parfume-reviews'),
            'singular_name' => __('Парфюмерист', 'parfume-reviews'),
            'menu_name' => __('Парфюмеристи', 'parfume-reviews'),
        );
        
        register_taxonomy('perfumer', 'parfume', array(
            'labels' => $perfumer_labels,
            'hierarchical' => true,
            'show_ui' => true,
            'show_admin_column' => true,
            'query_var' => true,
            'rewrite' => array(
                'slug' => $parfume_slug . '/' . $perfumers_slug,
                'with_front' => false,
                'hierarchical' => false
            ),
            'show_in_rest' => true,
            'public' => true,
            'publicly_queryable' => true,
            'meta_box_cb' => 'post_categories_meta_box',
            // SEO support
            'show_in_nav_menus' => true,
            'show_tagcloud' => true,
        ));
        
        // Add default perfumers
        $default_perfumers = array(
            'Алберто Морилас', 'Куентин Биш', 'Доминик Ропион', 'Оливие Кресп',
            'Франсоа Демаши', 'Кристофър Шелдрейк', 'Жак Кавалие', 'Анок Филибер',
            'Мишел Жирар', 'Пиер Монтале'
        );
        
        foreach ($default_perfumers as $perfumer) {
            if (!term_exists($perfumer, 'perfumer')) {
                wp_insert_term($perfumer, 'perfumer');
            }
        }
    }
    
    public function add_seo_support() {
        // Add Yoast SEO support
        if (class_exists('WPSEO_Options')) {
            add_filter('wpseo_metabox_prio', function() { return 'high'; });
            
            // Enable Yoast for all our taxonomies
            $taxonomies = array('gender', 'aroma_type', 'marki', 'season', 'intensity', 'notes', 'perfumer');
            foreach ($taxonomies as $taxonomy) {
                add_filter('wpseo_taxonomy_meta_' . $taxonomy, '__return_true');
            }
            
            // Add taxonomy support in options
            add_filter('wpseo_option_titles-tax-' . $taxonomy, function($value) {
                return array(
                    'title' => '%%term_title%% %%page%% %%sep%% %%sitename%%',
                    'metadesc' => '%%term_description%%',
                    'noindex' => false,
                    'display-metabox' => true
                );
            });
        }
        
        // Add RankMath support
        if (defined('RANK_MATH_VERSION')) {
            add_filter('rank_math/metabox/priority', function() { return 'high'; });
            
            // Enable RankMath for all our taxonomies
            $taxonomies = array('gender', 'aroma_type', 'marki', 'season', 'intensity', 'notes', 'perfumer');
            foreach ($taxonomies as $taxonomy) {
                add_filter('rank_math/taxonomy/' . $taxonomy, '__return_true');
                add_filter('rank_math/taxonomy/' . $taxonomy . '/add_meta_box', '__return_true');
                
                // Add to sitemap
                add_filter('rank_math/sitemap/enable_' . $taxonomy, '__return_true');
                
                // Enable structured data
                add_filter('rank_math/schema/taxonomy_' . $taxonomy, '__return_true');
            }
        }
    }
    
    public function add_custom_rewrite_rules() {
        $settings = get_option('parfume_reviews_settings', array());
        $parfume_slug = !empty($settings['parfume_slug']) ? $settings['parfume_slug'] : 'parfiumi';
        
        // Define taxonomy slugs
        $taxonomies = array(
            'marki' => !empty($settings['brands_slug']) ? $settings['brands_slug'] : 'marki',
            'notes' => !empty($settings['notes_slug']) ? $settings['notes_slug'] : 'notes',
            'perfumer' => !empty($settings['perfumers_slug']) ? $settings['perfumers_slug'] : 'parfumers',
            'gender' => !empty($settings['gender_slug']) ? $settings['gender_slug'] : 'gender',
            'aroma_type' => !empty($settings['aroma_type_slug']) ? $settings['aroma_type_slug'] : 'aroma-type',
            'season' => !empty($settings['season_slug']) ? $settings['season_slug'] : 'season',
            'intensity' => !empty($settings['intensity_slug']) ? $settings['intensity_slug'] : 'intensity',
        );
        
        foreach ($taxonomies as $taxonomy => $slug) {
            // Individual term page with pagination
            add_rewrite_rule(
                '^' . $parfume_slug . '/' . $slug . '/([^/]+)/page/([0-9]+)/?$',
                'index.php?' . $taxonomy . '=$matches[1]&paged=$matches[2]',
                'top'
            );
            
            // Individual term page
            add_rewrite_rule(
                '^' . $parfume_slug . '/' . $slug . '/([^/]+)/?$',
                'index.php?' . $taxonomy . '=$matches[1]',
                'top'
            );
            
            // Archive page with pagination
            $query_with_pagination = 'index.php?parfume_taxonomy_archive=' . $taxonomy . '&paged=$matches[1]';
            add_rewrite_rule(
                '^' . $parfume_slug . '/' . $slug . '/page/([0-9]+)/?$',
                $query_with_pagination,
                'top'
            );
            
            // Archive page rule
            $query_archive = 'index.php?parfume_taxonomy_archive=' . $taxonomy;
            add_rewrite_rule(
                '^' . $parfume_slug . '/' . $slug . '/?$',
                $query_archive,
                'top'
            );
        }
    }
    
    public function add_query_vars($vars) {
        $vars[] = 'parfume_taxonomy_archive';
        return $vars;
    }
    
    public function parse_custom_requests($wp) {
        if (isset($wp->query_vars['parfume_taxonomy_archive'])) {
            $taxonomy = $wp->query_vars['parfume_taxonomy_archive'];
            
            // Set the main query to show all posts from this taxonomy
            $wp->query_vars['post_type'] = 'parfume';
            $wp->query_vars['posts_per_page'] = 12;
            
            // Get all terms from this taxonomy
            $terms = get_terms(array(
                'taxonomy' => $taxonomy,
                'hide_empty' => false,
                'fields' => 'ids'
            ));
            
            if (!empty($terms) && !is_wp_error($terms)) {
                $wp->query_vars['tax_query'] = array(
                    array(
                        'taxonomy' => $taxonomy,
                        'field' => 'term_id',
                        'terms' => $terms,
                        'operator' => 'IN'
                    )
                );
            }
            
            // Set a flag so we know this is a taxonomy archive
            $wp->query_vars['is_parfume_taxonomy_archive'] = $taxonomy;
        }
    }
    
    public function template_loader($template) {
        global $wp_query;
        
        // Check if this is our custom taxonomy archive
        if (isset($wp_query->query_vars['is_parfume_taxonomy_archive'])) {
            $taxonomy = $wp_query->query_vars['is_parfume_taxonomy_archive'];
            
            // Load the appropriate archive template
            if ($taxonomy === 'marki') {
                $plugin_template = PARFUME_REVIEWS_PLUGIN_DIR . 'templates/archive-marki.php';
                if (file_exists($plugin_template)) {
                    return $plugin_template;
                }
            } elseif ($taxonomy === 'notes') {
                $plugin_template = PARFUME_REVIEWS_PLUGIN_DIR . 'templates/archive-notes.php';
                if (file_exists($plugin_template)) {
                    return $plugin_template;
                }
            }
            
            // Fallback to generic taxonomy archive
            $plugin_template = PARFUME_REVIEWS_PLUGIN_DIR . 'templates/archive-taxonomy.php';
            if (file_exists($plugin_template)) {
                return $plugin_template;
            }
        }
        
        // Handle individual taxonomy terms
        if (is_tax('marki')) {
            $plugin_template = PARFUME_REVIEWS_PLUGIN_DIR . 'templates/taxonomy-marki.php';
            if (file_exists($plugin_template)) {
                return $plugin_template;
            }
        } elseif (is_tax('notes')) {
            $plugin_template = PARFUME_REVIEWS_PLUGIN_DIR . 'templates/taxonomy-notes.php';
            if (file_exists($plugin_template)) {
                return $plugin_template;
            }
        } elseif (is_tax('perfumer')) {
            $plugin_template = PARFUME_REVIEWS_PLUGIN_DIR . 'templates/taxonomy-perfumer.php';
            if (file_exists($plugin_template)) {
                return $plugin_template;
            }
        } elseif (is_tax('gender')) {
            $plugin_template = PARFUME_REVIEWS_PLUGIN_DIR . 'templates/taxonomy-gender.php';
            if (file_exists($plugin_template)) {
                return $plugin_template;
            }
        } elseif (is_tax('aroma_type')) {
            $plugin_template = PARFUME_REVIEWS_PLUGIN_DIR . 'templates/taxonomy-aroma_type.php';
            if (file_exists($plugin_template)) {
                return $plugin_template;
            }
        } elseif (is_tax('season')) {
            $plugin_template = PARFUME_REVIEWS_PLUGIN_DIR . 'templates/taxonomy-season.php';
            if (file_exists($plugin_template)) {
                return $plugin_template;
            }
        } elseif (is_tax('intensity')) {
            $plugin_template = PARFUME_REVIEWS_PLUGIN_DIR . 'templates/taxonomy-intensity.php';
            if (file_exists($plugin_template)) {
                return $plugin_template;
            }
        }
        
        return $template;
    }
    
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
                <input type="hidden" id="<?php echo esc_attr($field_name); ?>" name="<?php echo esc_attr($field_name); ?>" value="<?php echo esc_attr($image_id); ?>">
                <div id="<?php echo esc_attr($wrapper_id); ?>">
                    <?php if ($image_id) { ?>
                        <?php echo wp_get_attachment_image($image_id, 'thumbnail'); ?>
                    <?php } ?>
                </div>
                <p>
                    <input type="button" class="button button-secondary pr_tax_media_button" data-field="<?php echo esc_attr($field_name); ?>" data-wrapper="<?php echo esc_attr($wrapper_id); ?>" value="<?php _e('Добави изображение', 'parfume-reviews'); ?>" />
                    <input type="button" class="button button-secondary pr_tax_media_remove" data-field="<?php echo esc_attr($field_name); ?>" data-wrapper="<?php echo esc_attr($wrapper_id); ?>" value="<?php _e('Премахни изображение', 'parfume-reviews'); ?>" />
                </p>
            </td>
        </tr>
        <?php
    }
    
    public function add_notes_group_field($taxonomy) {
        ?>
        <div class="form-field">
            <label for="note_group"><?php _e('Група', 'parfume-reviews'); ?></label>
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
            <p><?php _e('Изберете към коя група спада тази нотка за по-добра организация.', 'parfume-reviews'); ?></p>
        </div>
        <?php
    }
    
    public function edit_notes_group_field($term, $taxonomy) {
        $group = get_term_meta($term->term_id, 'note_group', true);
        ?>
        <tr class="form-field">
            <th scope="row">
                <label for="note_group"><?php _e('Група', 'parfume-reviews'); ?></label>
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
    
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'edit-tags.php') !== false || strpos($hook, 'term.php') !== false) {
            wp_enqueue_media();
            wp_enqueue_script('parfume-taxonomy-media', PARFUME_REVIEWS_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), PARFUME_REVIEWS_VERSION, true);
        }
    }
}