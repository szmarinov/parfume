<?php
/**
 * Schema.org и SEO модул
 * 
 * @package Parfume_Catalog
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Parfume_Catalog_Schema {
    
    public function __construct() {
        add_action('wp_head', array($this, 'output_schema'));
        add_filter('wp_title', array($this, 'modify_title'), 10, 2);
        add_action('wp_head', array($this, 'output_meta_tags'));
        add_filter('document_title_parts', array($this, 'modify_document_title'));
    }
    
    /**
     * Генериране на Schema.org markup
     */
    public function output_schema() {
        if (is_singular('parfumes')) {
            $this->output_product_schema();
        } elseif (is_tax(array('tip', 'vid_aromat', 'marki', 'sezon', 'intenzivnost', 'notki'))) {
            $this->output_category_schema();
        } elseif (is_post_type_archive('parfumes')) {
            $this->output_collection_schema();
        }
    }
    
    /**
     * Schema.org за единичен продукт
     */
    private function output_product_schema() {
        global $post;
        
        $product_data = $this->get_product_data($post->ID);
        
        $schema = array(
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => get_the_title(),
            'description' => get_the_excerpt() ?: wp_trim_words(get_the_content(), 20),
            'url' => get_permalink(),
            'sku' => 'parfume-' . $post->ID
        );
        
        // Изображение
        $featured_image = get_the_post_thumbnail_url($post->ID, 'large');
        if ($featured_image) {
            $schema['image'] = $featured_image;
        }
        
        // Марка
        $brand = $this->get_brand_name($post->ID);
        if ($brand) {
            $schema['brand'] = array(
                '@type' => 'Brand',
                'name' => $brand
            );
        }
        
        // Категория
        $categories = $this->get_product_categories($post->ID);
        if (!empty($categories)) {
            $schema['category'] = implode(', ', $categories);
        }
        
        // Рейтинг
        $rating_data = $this->get_product_rating($post->ID);
        if ($rating_data['count'] > 0) {
            $schema['aggregateRating'] = array(
                '@type' => 'AggregateRating',
                'ratingValue' => $rating_data['average'],
                'bestRating' => 5,
                'worstRating' => 1,
                'ratingCount' => $rating_data['count']
            );
        }
        
        // Ревюта
        $reviews = $this->get_schema_reviews($post->ID);
        if (!empty($reviews)) {
            $schema['review'] = $reviews;
        }
        
        // Цена
        $base_price = get_post_meta($post->ID, '_pc_base_price', true);
        if ($base_price) {
            $schema['offers'] = array(
                '@type' => 'Offer',
                'price' => $base_price,
                'priceCurrency' => 'BGN',
                'availability' => 'https://schema.org/InStock',
                'url' => get_permalink()
            );
        }
        
        // Допълнителни свойства за парфюм
        if (!empty($product_data['notes'])) {
            $schema['additionalProperty'] = array();
            
            foreach ($product_data['notes'] as $note_type => $notes) {
                $schema['additionalProperty'][] = array(
                    '@type' => 'PropertyValue',
                    'name' => $note_type,
                    'value' => implode(', ', $notes)
                );
            }
        }
        
        // Интензивност
        if (!empty($product_data['intensity'])) {
            $schema['additionalProperty'][] = array(
                '@type' => 'PropertyValue',
                'name' => 'Интензивност',
                'value' => $product_data['intensity']
            );
        }
        
        // Дълготрайност
        if (!empty($product_data['longevity'])) {
            $schema['additionalProperty'][] = array(
                '@type' => 'PropertyValue',
                'name' => 'Дълготрайност',
                'value' => $product_data['longevity']
            );
        }
        
        echo '<script type="application/ld+json">' . "\n";
        echo wp_json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
        echo '</script>' . "\n";
    }
    
    /**
     * Schema.org за категория/таксономия
     */
    private function output_category_schema() {
        $term = get_queried_object();
        
        $schema = array(
            '@context' => 'https://schema.org',
            '@type' => 'CollectionPage',
            'name' => $term->name,
            'description' => $term->description ?: sprintf(__('Парфюми от категория %s', 'parfume-catalog'), $term->name),
            'url' => get_term_link($term)
        );
        
        echo '<script type="application/ld+json">' . "\n";
        echo wp_json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
        echo '</script>' . "\n";
    }
    
    /**
     * Schema.org за архивна страница
     */
    private function output_collection_schema() {
        $schema = array(
            '@context' => 'https://schema.org',
            '@type' => 'CollectionPage',
            'name' => __('Каталог парфюми', 'parfume-catalog'),
            'description' => __('Пълен каталог с парфюми, ревюта и оценки', 'parfume-catalog'),
            'url' => get_post_type_archive_link('parfumes')
        );
        
        echo '<script type="application/ld+json">' . "\n";
        echo wp_json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
        echo '</script>' . "\n";
    }
    
    /**
     * Модифициране на title
     */
    public function modify_title($title, $sep) {
        if (is_singular('parfumes')) {
            global $post;
            $brand = $this->get_brand_name($post->ID);
            if ($brand) {
                return $brand . ' - ' . get_the_title() . ' ' . $sep . ' ' . get_bloginfo('name');
            }
        }
        
        return $title;
    }
    
    /**
     * Модифициране на document title
     */
    public function modify_document_title($title_parts) {
        if (is_singular('parfumes')) {
            global $post;
            $brand = $this->get_brand_name($post->ID);
            if ($brand) {
                $title_parts['title'] = $brand . ' - ' . get_the_title();
            }
        }
        
        return $title_parts;
    }
    
    /**
     * Генериране на мета тагове
     */
    public function output_meta_tags() {
        if (is_singular('parfumes')) {
            global $post;
            
            // Open Graph тагове
            $featured_image = get_the_post_thumbnail_url($post->ID, 'large');
            $description = get_the_excerpt() ?: wp_trim_words(get_the_content(), 20);
            $brand = $this->get_brand_name($post->ID);
            
            echo '<meta property="og:type" content="product" />' . "\n";
            echo '<meta property="og:title" content="' . esc_attr($brand . ' - ' . get_the_title()) . '" />' . "\n";
            echo '<meta property="og:description" content="' . esc_attr($description) . '" />' . "\n";
            echo '<meta property="og:url" content="' . esc_url(get_permalink()) . '" />' . "\n";
            
            if ($featured_image) {
                echo '<meta property="og:image" content="' . esc_url($featured_image) . '" />' . "\n";
                echo '<meta property="og:image:alt" content="' . esc_attr(get_the_title()) . '" />' . "\n";
            }
            
            // Twitter Card тагове
            echo '<meta name="twitter:card" content="summary_large_image" />' . "\n";
            echo '<meta name="twitter:title" content="' . esc_attr($brand . ' - ' . get_the_title()) . '" />' . "\n";
            echo '<meta name="twitter:description" content="' . esc_attr($description) . '" />' . "\n";
            
            if ($featured_image) {
                echo '<meta name="twitter:image" content="' . esc_url($featured_image) . '" />' . "\n";
            }
            
            // Допълнителни мета тагове
            $categories = $this->get_product_categories($post->ID);
            if (!empty($categories)) {
                echo '<meta name="keywords" content="' . esc_attr(implode(', ', $categories)) . ', парфюм, аромат" />' . "\n";
            }
        }
    }
    
    /**
     * Получаване на данни за продукт
     */
    private function get_product_data($post_id) {
        $data = array();
        
        // Нотки
        $vrhni_notki = wp_get_post_terms($post_id, 'notki', array('fields' => 'names'));
        $sredni_notki = get_post_meta($post_id, '_sredni_notki', true);
        $bazovi_notki = get_post_meta($post_id, '_bazovi_notki', true);
        
        if (!empty($vrhni_notki)) {
            $data['notes']['Връхни нотки'] = $vrhni_notki;
        }
        if (!empty($sredni_notki)) {
            $data['notes']['Средни нотки'] = explode(',', $sredni_notki);
        }
        if (!empty($bazovi_notki)) {
            $data['notes']['Базови нотки'] = explode(',', $bazovi_notki);
        }
        
        // Интензивност
        $intensity_terms = wp_get_post_terms($post_id, 'intenzivnost', array('fields' => 'names'));
        if (!empty($intensity_terms)) {
            $data['intensity'] = implode(', ', $intensity_terms);
        }
        
        // Дълготрайност
        $longevity = get_post_meta($post_id, '_longevity', true);
        if ($longevity) {
            $longevity_labels = array(
                'velmi_slab' => 'Много слаб',
                'slab' => 'Слаб',
                'umeren' => 'Умерен',
                'traen' => 'Траен',
                'izklyuchitelno_traen' => 'Изключително траен'
            );
            $data['longevity'] = $longevity_labels[$longevity] ?? $longevity;
        }
        
        return $data;
    }
    
    /**
     * Получаване на марката на продукт
     */
    private function get_brand_name($post_id) {
        $brands = wp_get_post_terms($post_id, 'marki', array('fields' => 'names'));
        return !empty($brands) ? $brands[0] : '';
    }
    
    /**
     * Получаване на категориите на продукт
     */
    private function get_product_categories($post_id) {
        $categories = array();
        
        $tip_terms = wp_get_post_terms($post_id, 'tip', array('fields' => 'names'));
        $vid_terms = wp_get_post_terms($post_id, 'vid_aromat', array('fields' => 'names'));
        
        return array_merge($tip_terms, $vid_terms);
    }
    
    /**
     * Получаване на рейтинг данни
     */
    private function get_product_rating($post_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'parfume_comments';
        
        $results = $wpdb->get_row($wpdb->prepare(
            "SELECT AVG(rating) as average, COUNT(*) as count 
             FROM {$table_name} 
             WHERE post_id = %d AND status = 'approved' AND rating > 0",
            $post_id
        ));
        
        return array(
            'average' => $results ? round($results->average, 1) : 0,
            'count' => $results ? $results->count : 0
        );
    }
    
    /**
     * Получаване на ревюта за Schema.org
     */
    private function get_schema_reviews($post_id, $limit = 5) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'parfume_comments';
        
        $reviews = $wpdb->get_results($wpdb->prepare(
            "SELECT author_name, comment_content, rating, created_at 
             FROM {$table_name} 
             WHERE post_id = %d AND status = 'approved' AND rating > 0 
             ORDER BY created_at DESC 
             LIMIT %d",
            $post_id, $limit
        ));
        
        $schema_reviews = array();
        
        foreach ($reviews as $review) {
            $schema_reviews[] = array(
                '@type' => 'Review',
                'author' => array(
                    '@type' => 'Person',
                    'name' => $review->author_name
                ),
                'reviewBody' => $review->comment_content,
                'reviewRating' => array(
                    '@type' => 'Rating',
                    'ratingValue' => $review->rating,
                    'bestRating' => 5,
                    'worstRating' => 1
                ),
                'datePublished' => date('c', strtotime($review->created_at))
            );
        }
        
        return $schema_reviews;
    }
}