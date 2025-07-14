<?php
namespace Parfume_Reviews\Taxonomies;

/**
 * Taxonomy SEO Support - управлява SEO интеграция за таксономии
 */
class Taxonomy_SEO_Support {
    
    public function __construct() {
        add_action('init', array($this, 'add_seo_support'));
    }
    
    /**
     * Добавя SEO поддръжка за всички таксономии
     */
    public function add_seo_support() {
        $this->add_yoast_seo_support();
        $this->add_rankmath_support();
    }
    
    /**
     * Добавя Yoast SEO поддръжка
     */
    private function add_yoast_seo_support() {
        if (!class_exists('WPSEO_Options')) {
            return;
        }
        
        add_filter('wpseo_metabox_prio', function() { return 'high'; });
        
        // Enable Yoast for all our taxonomies
        $taxonomies = $this->get_supported_taxonomies();
        foreach ($taxonomies as $taxonomy) {
            add_filter('wpseo_taxonomy_meta_' . $taxonomy, '__return_true');
        }
        
        // Add taxonomy support
        add_filter('wpseo_metabox_prio', '__return_true');
        
        // Enable breadcrumbs for taxonomies
        add_filter('wpseo_breadcrumb_links', array($this, 'add_yoast_breadcrumbs'));
        
        // Add OpenGraph tags for taxonomy pages
        add_action('wpseo_head', array($this, 'add_yoast_og_tags'));
    }
    
    /**
     * Добавя RankMath поддръжка
     */
    private function add_rankmath_support() {
        if (!defined('RANK_MATH_VERSION')) {
            return;
        }
        
        add_filter('rank_math/metabox/priority', function() { return 'high'; });
        
        // Enable RankMath for all our taxonomies
        $taxonomies = $this->get_supported_taxonomies();
        foreach ($taxonomies as $taxonomy) {
            add_filter('rank_math/taxonomy/' . $taxonomy, '__return_true');
            add_filter('rank_math/taxonomy/' . $taxonomy . '/add_meta_box', '__return_true');
            
            // Add to sitemap
            add_filter('rank_math/sitemap/enable_' . $taxonomy, '__return_true');
            
            // Enable structured data
            add_filter('rank_math/schema/taxonomy_' . $taxonomy, '__return_true');
        }
        
        // Add breadcrumbs
        add_filter('rank_math/frontend/breadcrumb/items', array($this, 'add_rankmath_breadcrumbs'));
        
        // Add OpenGraph tags
        add_action('rank_math/head', array($this, 'add_rankmath_og_tags'));
    }
    
    /**
     * Добавя Yoast breadcrumbs за таксономии
     */
    public function add_yoast_breadcrumbs($links) {
        if (!is_tax($this->get_supported_taxonomies())) {
            return $links;
        }
        
        $queried_object = get_queried_object();
        if (!$queried_object || !isset($queried_object->taxonomy)) {
            return $links;
        }
        
        // Add taxonomy archive link
        $taxonomy_obj = get_taxonomy($queried_object->taxonomy);
        if ($taxonomy_obj) {
            $archive_link = array(
                'url' => $this->get_taxonomy_archive_url($queried_object->taxonomy),
                'text' => $taxonomy_obj->labels->name,
            );
            
            // Insert before the last element (current page)
            array_splice($links, -1, 0, array($archive_link));
        }
        
        return $links;
    }
    
    /**
     * Добавя RankMath breadcrumbs за таксономии
     */
    public function add_rankmath_breadcrumbs($crumbs) {
        if (!is_tax($this->get_supported_taxonomies())) {
            return $crumbs;
        }
        
        $queried_object = get_queried_object();
        if (!$queried_object || !isset($queried_object->taxonomy)) {
            return $crumbs;
        }
        
        // Add taxonomy archive link
        $taxonomy_obj = get_taxonomy($queried_object->taxonomy);
        if ($taxonomy_obj) {
            $archive_crumb = array(
                $this->get_taxonomy_archive_url($queried_object->taxonomy),
                $taxonomy_obj->labels->name,
            );
            
            // Insert before the last element (current page)
            array_splice($crumbs, -1, 0, array($archive_crumb));
        }
        
        return $crumbs;
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
        
        $slug_mapping = array(
            'marki' => !empty($settings['brands_slug']) ? $settings['brands_slug'] : 'marki',
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