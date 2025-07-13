<?php
namespace Parfume_Reviews\Taxonomies;

/**
 * Taxonomy SEO Support - управлява SEO интеграцията за таксономии
 * 📁 Файл: includes/taxonomies/class-taxonomy-seo-support.php
 * ПОПРАВЕНО: Правилни default slug-ове
 */
class Taxonomy_SEO_Support {
    
    public function __construct() {
        // Yoast SEO hooks
        add_action('wpseo_init', array($this, 'add_yoast_support'));
        
        // RankMath hooks
        add_action('rank_math/loaded', array($this, 'add_rankmath_support'));
        
        // Generic SEO hooks
        add_action('wp_head', array($this, 'add_generic_meta_tags'));
        add_action('wp_head', array($this, 'add_structured_data'));
    }
    
    /**
     * Добавя Yoast SEO поддръжка
     */
    public function add_yoast_support() {
        if (class_exists('WPSEO_Options')) {
            add_filter('wpseo_title', array($this, 'modify_yoast_title'));
            add_filter('wpseo_metadesc', array($this, 'modify_yoast_description'));
            add_filter('wpseo_canonical', array($this, 'modify_yoast_canonical'));
            add_action('wpseo_head', array($this, 'add_yoast_og_tags'));
        }
    }
    
    /**
     * Добавя RankMath поддръжка
     */
    public function add_rankmath_support() {
        if (class_exists('RankMath')) {
            add_filter('rank_math/frontend/title', array($this, 'modify_rankmath_title'));
            add_filter('rank_math/frontend/description', array($this, 'modify_rankmath_description'));
            add_filter('rank_math/frontend/canonical', array($this, 'modify_rankmath_canonical'));
            add_action('rank_math/head', array($this, 'add_rankmath_og_tags'));
        }
    }
    
    /**
     * Модифицира Yoast title за taxonomy страници
     */
    public function modify_yoast_title($title) {
        if (is_tax($this->get_supported_taxonomies())) {
            $queried_object = get_queried_object();
            if ($queried_object) {
                // Custom title based on taxonomy
                switch ($queried_object->taxonomy) {
                    case 'marki':
                        return $queried_object->name . ' - ' . __('Парфюми от марката', 'parfume-reviews') . ' | ' . get_bloginfo('name');
                    case 'perfumer':
                        return $queried_object->name . ' - ' . __('Парфюми от парфюмера', 'parfume-reviews') . ' | ' . get_bloginfo('name');
                    case 'notes':
                        return $queried_object->name . ' - ' . __('Парфюми с нотка', 'parfume-reviews') . ' | ' . get_bloginfo('name');
                    default:
                        return $queried_object->name . ' - ' . __('Парфюми', 'parfume-reviews') . ' | ' . get_bloginfo('name');
                }
            }
        }
        
        return $title;
    }
    
    /**
     * Модифицира Yoast description за taxonomy страници
     */
    public function modify_yoast_description($description) {
        if (is_tax($this->get_supported_taxonomies())) {
            $queried_object = get_queried_object();
            if ($queried_object) {
                if (!empty($queried_object->description)) {
                    return $queried_object->description;
                }
                
                // Default descriptions based on taxonomy
                switch ($queried_object->taxonomy) {
                    case 'marki':
                        return sprintf(__('Открийте всички парфюми от марката %s. Прегледайте нашата колекция и намерете вашия следващ любим аромат.', 'parfume-reviews'), $queried_object->name);
                    case 'perfumer':
                        return sprintf(__('Парфюми създадени от %s. Разгледайте уникалните творения на този талантлив парфюмер.', 'parfume-reviews'), $queried_object->name);
                    case 'notes':
                        return sprintf(__('Парфюми с нотка %s. Намерете ароматите, които съдържат тази специална съставка.', 'parfume-reviews'), $queried_object->name);
                    default:
                        return sprintf(__('Разгледайте парфюмите в категория %s.', 'parfume-reviews'), $queried_object->name);
                }
            }
        }
        
        return $description;
    }
    
    /**
     * Модифицира canonical URL за taxonomy страници
     */
    public function modify_yoast_canonical($canonical) {
        if (is_tax($this->get_supported_taxonomies())) {
            $queried_object = get_queried_object();
            if ($queried_object) {
                return $this->get_taxonomy_archive_url($queried_object->taxonomy);
            }
        }
        
        return $canonical;
    }
    
    /**
     * Модифицира RankMath title
     */
    public function modify_rankmath_title($title) {
        return $this->modify_yoast_title($title);
    }
    
    /**
     * Модифицира RankMath description
     */
    public function modify_rankmath_description($description) {
        return $this->modify_yoast_description($description);
    }
    
    /**
     * Модифицира RankMath canonical
     */
    public function modify_rankmath_canonical($canonical) {
        return $this->modify_yoast_canonical($canonical);
    }
    
    /**
     * Добавя generic meta tags за taxonomy страници
     */
    public function add_generic_meta_tags() {
        if (is_tax($this->get_supported_taxonomies())) {
            $queried_object = get_queried_object();
            if ($queried_object) {
                // Only add if no SEO plugin is active
                if (!class_exists('WPSEO_Options') && !class_exists('RankMath')) {
                    $title = $this->modify_yoast_title('');
                    $description = $this->modify_yoast_description('');
                    
                    if ($title) {
                        echo '<title>' . esc_html($title) . '</title>' . "\n";
                    }
                    
                    if ($description) {
                        echo '<meta name="description" content="' . esc_attr($description) . '">' . "\n";
                    }
                }
            }
        }
    }
    
    /**
     * Добавя structured data за taxonomy страници
     */
    public function add_structured_data() {
        if (is_tax($this->get_supported_taxonomies())) {
            $queried_object = get_queried_object();
            if ($queried_object) {
                $this->output_structured_data($queried_object);
            }
        }
    }
    
    /**
     * Добавя Yoast OpenGraph tags за таксономии
     */
    public function add_yoast_og_tags() {
        if (!is_tax($this->get_supported_taxonomies())) {
            return;
        }
        
        $queried_object = get_queried_object();
        if (!$queried_object) {
            return;
        }
        
        // Add taxonomy-specific OpenGraph data
        $this->output_og_tags($queried_object);
    }
    
    /**
     * Добавя RankMath OpenGraph tags за таксономии
     */
    public function add_rankmath_og_tags() {
        if (!is_tax($this->get_supported_taxonomies())) {
            return;
        }
        
        $queried_object = get_queried_object();
        if (!$queried_object) {
            return;
        }
        
        // Add taxonomy-specific OpenGraph data
        $this->output_og_tags($queried_object);
    }
    
    /**
     * Извежда OpenGraph tags за таксономия
     */
    private function output_og_tags($term) {
        // Get term image if available
        $image_id = get_term_meta($term->term_id, $term->taxonomy . '-image-id', true);
        if ($image_id) {
            $image_url = wp_get_attachment_image_url($image_id, 'large');
            if ($image_url) {
                echo '<meta property="og:image" content="' . esc_url($image_url) . '" />' . "\n";
                echo '<meta property="og:image:width" content="1200" />' . "\n";
                echo '<meta property="og:image:height" content="630" />' . "\n";
            }
        }
        
        // Add structured data
        $this->output_structured_data($term);
    }
    
    /**
     * Извежда structured data за таксономия
     */
    private function output_structured_data($term) {
        $structured_data = array(
            '@context' => 'https://schema.org',
            '@type' => 'CollectionPage',
            'name' => $term->name,
            'description' => $term->description,
            'url' => get_term_link($term),
        );
        
        // Add image if available
        $image_id = get_term_meta($term->term_id, $term->taxonomy . '-image-id', true);
        if ($image_id) {
            $image_url = wp_get_attachment_image_url($image_id, 'large');
            if ($image_url) {
                $structured_data['image'] = $image_url;
            }
        }
        
        // Add specific schema based on taxonomy
        switch ($term->taxonomy) {
            case 'marki':
                $structured_data['@type'] = 'Brand';
                break;
            case 'perfumer':
                $structured_data['@type'] = 'Person';
                $structured_data['jobTitle'] = __('Парфюмер', 'parfume-reviews');
                break;
        }
        
        echo '<script type="application/ld+json">' . wp_json_encode($structured_data) . '</script>' . "\n";
    }
    
    /**
     * Получава поддържаните таксономии
     */
    private function get_supported_taxonomies() {
        return array('gender', 'aroma_type', 'marki', 'season', 'intensity', 'notes', 'perfumer');
    }
    
    /**
     * Получава URL за архив на таксономия
     */
    private function get_taxonomy_archive_url($taxonomy) {
        $settings = get_option('parfume_reviews_settings', array());
        $parfume_slug = !empty($settings['parfume_slug']) ? $settings['parfume_slug'] : 'parfiumi';
        
        // ПОПРАВЕНО: Правилни default slug-ове
        $slug_mapping = array(
            'marki' => !empty($settings['brands_slug']) ? $settings['brands_slug'] : 'parfumeri',
            'notes' => !empty($settings['notes_slug']) ? $settings['notes_slug'] : 'notes',
            'perfumer' => !empty($settings['perfumers_slug']) ? $settings['perfumers_slug'] : 'parfumers',
            'gender' => !empty($settings['gender_slug']) ? $settings['gender_slug'] : 'gender',
            'aroma_type' => !empty($settings['aroma_type_slug']) ? $settings['aroma_type_slug'] : 'aroma-type',
            'season' => !empty($settings['season_slug']) ? $settings['season_slug'] : 'season',
            'intensity' => !empty($settings['intensity_slug']) ? $settings['intensity_slug'] : 'intensity',
        );
        
        $taxonomy_slug = isset($slug_mapping[$taxonomy]) ? $slug_mapping[$taxonomy] : $taxonomy;
        return home_url('/' . $parfume_slug . '/' . $taxonomy_slug . '/');
    }
}