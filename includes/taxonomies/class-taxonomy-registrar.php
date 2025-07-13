<?php
namespace Parfume_Reviews\Taxonomies;

/**
 * Taxonomy Registrar - отговаря за регистрацията на всички таксономии
 * 📁 Файл: includes/taxonomies/class-taxonomy-registrar.php
 * ПОПРАВЕНО: brands_slug default от 'marki' на 'parfumeri'
 */
class Taxonomy_Registrar {
    
    public function __construct() {
        add_action('init', array($this, 'register_taxonomies'), 0);
    }
    
    /**
     * Регистрира всички таксономии
     */
    public function register_taxonomies() {
        $settings = get_option('parfume_reviews_settings', array());
        $parfume_slug = !empty($settings['parfume_slug']) ? $settings['parfume_slug'] : 'parfiumi';
        
        $this->register_gender_taxonomy($settings, $parfume_slug);
        $this->register_aroma_type_taxonomy($settings, $parfume_slug);
        $this->register_brands_taxonomy($settings, $parfume_slug);
        $this->register_season_taxonomy($settings, $parfume_slug);
        $this->register_intensity_taxonomy($settings, $parfume_slug);
        $this->register_notes_taxonomy($settings, $parfume_slug);
        $this->register_perfumer_taxonomy($settings, $parfume_slug);
    }
    
    /**
     * Регистрира Gender taxonomy
     */
    private function register_gender_taxonomy($settings, $parfume_slug) {
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
            'show_in_nav_menus' => true,
            'show_tagcloud' => true,
        ));
        
        $this->add_default_gender_terms();
    }
    
    /**
     * Регистрира Aroma Type taxonomy
     */
    private function register_aroma_type_taxonomy($settings, $parfume_slug) {
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
            'show_in_nav_menus' => true,
            'show_tagcloud' => true,
        ));
        
        $this->add_default_aroma_type_terms();
    }
    
    /**
     * Регистрира Brands taxonomy
     */
    private function register_brands_taxonomy($settings, $parfume_slug) {
        // ПОПРАВЕНО: brands_slug default от 'marki' на 'parfumeri'
        $brands_slug = !empty($settings['brands_slug']) ? $settings['brands_slug'] : 'parfumeri';
        
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
            'show_in_nav_menus' => true,
            'show_tagcloud' => true,
        ));
        
        $this->add_default_brand_terms();
    }
    
    /**
     * Регистрира Season taxonomy
     */
    private function register_season_taxonomy($settings, $parfume_slug) {
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
            'show_in_nav_menus' => true,
            'show_tagcloud' => true,
        ));
        
        $this->add_default_season_terms();
    }
    
    /**
     * Регистрира Intensity taxonomy
     */
    private function register_intensity_taxonomy($settings, $parfume_slug) {
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
            'show_in_nav_menus' => true,
            'show_tagcloud' => true,
        ));
        
        $this->add_default_intensity_terms();
    }
    
    /**
     * Регистрира Notes taxonomy
     */
    private function register_notes_taxonomy($settings, $parfume_slug) {
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
            'show_in_nav_menus' => true,
            'show_tagcloud' => true,
        ));
        
        $this->add_default_notes_terms();
    }
    
    /**
     * Регистрира Perfumer taxonomy
     */
    private function register_perfumer_taxonomy($settings, $parfume_slug) {
        $perfumers_slug = !empty($settings['perfumers_slug']) ? $settings['perfumers_slug'] : 'parfumers';
        
        $perfumer_labels = array(
            'name' => __('Парфюмери', 'parfume-reviews'),
            'singular_name' => __('Парфюмер', 'parfume-reviews'),
            'menu_name' => __('Парфюмери', 'parfume-reviews'),
        );
        
        register_taxonomy('perfumer', 'parfume', array(
            'labels' => $perfumer_labels,
            'hierarchical' => false,
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
            'meta_box_cb' => 'post_tags_meta_box',
            'show_in_nav_menus' => true,
            'show_tagcloud' => true,
        ));
        
        $this->add_default_perfumer_terms();
    }
    
    /**
     * Добавя default terms за Gender
     */
    private function add_default_gender_terms() {
        $default_genders = array(
            'unisex' => __('Унисекс', 'parfume-reviews'),
            'men' => __('Мъжки', 'parfume-reviews'),
            'women' => __('Дамски', 'parfume-reviews'),
        );
        
        foreach ($default_genders as $slug => $name) {
            if (!term_exists($slug, 'gender')) {
                wp_insert_term($name, 'gender', array('slug' => $slug));
            }
        }
    }
    
    /**
     * Добавя default terms за Aroma Type
     */
    private function add_default_aroma_type_terms() {
        $default_aroma_types = array(
            'floral' => __('Флорални', 'parfume-reviews'),
            'oriental' => __('Ориенталски', 'parfume-reviews'),
            'woody' => __('Дървесни', 'parfume-reviews'),
            'fresh' => __('Свежи', 'parfume-reviews'),
            'citrus' => __('Цитрусови', 'parfume-reviews'),
            'fruity' => __('Плодови', 'parfume-reviews'),
            'spicy' => __('Пикантни', 'parfume-reviews'),
            'aquatic' => __('Водни', 'parfume-reviews'),
        );
        
        foreach ($default_aroma_types as $slug => $name) {
            if (!term_exists($slug, 'aroma_type')) {
                wp_insert_term($name, 'aroma_type', array('slug' => $slug));
            }
        }
    }
    
    /**
     * Добавя default terms за Brands
     */
    private function add_default_brand_terms() {
        $default_brands = array(
            'chanel' => 'Chanel',
            'dior' => 'Dior',
            'ysl' => 'Yves Saint Laurent',
            'versace' => 'Versace',
            'armani' => 'Giorgio Armani',
            'gucci' => 'Gucci',
            'prada' => 'Prada',
            'tom-ford' => 'Tom Ford',
        );
        
        foreach ($default_brands as $slug => $name) {
            if (!term_exists($slug, 'marki')) {
                wp_insert_term($name, 'marki', array('slug' => $slug));
            }
        }
    }
    
    /**
     * Добавя default terms за Season
     */
    private function add_default_season_terms() {
        $default_seasons = array(
            'spring' => __('Пролет', 'parfume-reviews'),
            'summer' => __('Лято', 'parfume-reviews'),
            'autumn' => __('Есен', 'parfume-reviews'),
            'winter' => __('Зима', 'parfume-reviews'),
            'all-year' => __('Целогодишно', 'parfume-reviews'),
        );
        
        foreach ($default_seasons as $slug => $name) {
            if (!term_exists($slug, 'season')) {
                wp_insert_term($name, 'season', array('slug' => $slug));
            }
        }
    }
    
    /**
     * Добавя default terms за Intensity
     */
    private function add_default_intensity_terms() {
        $default_intensities = array(
            'light' => __('Лека', 'parfume-reviews'),
            'moderate' => __('Умерена', 'parfume-reviews'),
            'strong' => __('Силна', 'parfume-reviews'),
            'very-strong' => __('Много силна', 'parfume-reviews'),
        );
        
        foreach ($default_intensities as $slug => $name) {
            if (!term_exists($slug, 'intensity')) {
                wp_insert_term($name, 'intensity', array('slug' => $slug));
            }
        }
    }
    
    /**
     * Добавя default terms за Notes
     */
    private function add_default_notes_terms() {
        $default_notes = array(
            'rose' => __('Роза', 'parfume-reviews'),
            'jasmine' => __('Жасмин', 'parfume-reviews'),
            'vanilla' => __('Ванилия', 'parfume-reviews'),
            'sandalwood' => __('Сандалово дърво', 'parfume-reviews'),
            'bergamot' => __('Бергамот', 'parfume-reviews'),
            'lavender' => __('Лавандула', 'parfume-reviews'),
            'musk' => __('Мускус', 'parfume-reviews'),
            'amber' => __('Амбра', 'parfume-reviews'),
        );
        
        foreach ($default_notes as $slug => $name) {
            if (!term_exists($slug, 'notes')) {
                wp_insert_term($name, 'notes', array('slug' => $slug));
            }
        }
    }
    
    /**
     * Добавя default terms за Perfumer
     */
    private function add_default_perfumer_terms() {
        $default_perfumers = array(
            'jacques-polge' => 'Jacques Polge',
            'olivier-polge' => 'Olivier Polge',
            'alberto-morillas' => 'Alberto Morillas',
            'francois-demachy' => 'François Demachy',
            'jean-claude-ellena' => 'Jean-Claude Ellena',
            'thierry-wasser' => 'Thierry Wasser',
        );
        
        foreach ($default_perfumers as $slug => $name) {
            if (!term_exists($slug, 'perfumer')) {
                wp_insert_term($name, 'perfumer', array('slug' => $slug));
            }
        }
    }
}