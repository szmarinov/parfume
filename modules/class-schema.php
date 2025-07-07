<?php
/**
 * Schema.org и SEO модул
 * 
 * @package Parfume_Catalog
 */

if (!defined('ABSPATH')) {
    exit;
}

class Parfume_Catalog_Schema {
    
    /**
     * Инициализация
     */
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
        } elseif (is_tax(array('tip-parfum', 'vid-aromat', 'marki', 'sezon', 'intenzivnost', 'notki'))) {
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
            'category' => $this->get_product_categories($post->ID),
            'brand' => array(
                '@type' => 'Brand',
                'name' => $this->get_brand_name($post->ID)
            )
        );
        
        // Добавяне на изображение
        $featured_image = get_the_post_thumbnail_url($post->ID, 'large');
        if ($featured_image) {
            $schema['image'] = $featured_image;
        }
        
        // Добавяне на рейтинг ако има коментари
        $rating_data = $this->get_product_rating($post->ID);
        if ($rating_data['count'] > 0) {
            $schema['aggregateRating'] = array(
                '@type' => 'AggregateRating',
                'ratingValue' => $rating_data['average'],
                'reviewCount' => $rating_data['count'],
                'bestRating' => 5,
                'worstRating' => 1
            );
        }
        
        // Добавяне на оферти от магазини
        $offers = $this->get_product_offers($post->ID);
        if (!empty($offers)) {
            $schema['offers'] = $offers;
        }
        
        // Добавяне на допълнителни свойства
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
        
        // Добавяне на интензивност и трайност
        if (!empty($product_data['intensity'])) {
            $schema['additionalProperty'][] = array(
                '@type' => 'PropertyValue',
                'name' => 'Интензивност',
                'value' => $product_data['intensity']
            );
        }
        
        if (!empty($product_data['longevity'])) {
            $schema['additionalProperty'][] = array(
                '@type' => 'PropertyValue',
                'name' => 'Дълготрайност',
                'value' => $product_data['longevity']
            );
        }
        
        echo '<script type="application/ld+json">' . wp_json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '</script>' . "\n";
    }
    
    /**
     * Schema.org за категории
     */
    private function output_category_schema() {
        $term = get_queried_object();
        
        $schema = array(
            '@context' => 'https://schema.org',
            '@type' => 'CollectionPage',
            'name' => $term->name,
            'description' => $term->description ?: 'Колекция от парфюми - ' . $term->name,
            'url' => get_term_link($term),
            'mainEntity' => array(
                '@type' => 'ItemList',
                'numberOfItems' => $term->count,
                'itemListElement' => $this->get_category_products($term)
            )
        );
        
        echo '<script type="application/ld+json">' . wp_json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '</script>' . "\n";
    }
    
    /**
     * Schema.org за архивна страница
     */
    private function output_collection_schema() {
        $schema = array(
            '@context' => 'https://schema.org',
            '@type' => 'CollectionPage',
            'name' => 'Парфюми',
            'description' => 'Каталог с парфюми и ароматни води',
            'url' => get_post_type_archive_link('parfumes'),
            'mainEntity' => array(
                '@type' => 'ItemList',
                'itemListElement' => $this->get_archive_products()
            )
        );
        
        echo '<script type="application/ld+json">' . wp_json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '</script>' . "\n";
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
        
        $tip_terms = wp_get_post_terms($post_id, 'tip-parfum', array('fields' => 'names'));
        $vid_terms = wp_get_post_terms($post_id, 'vid-aromat', array('fields' => 'names'));
        
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
            'count' => $results ? (int)$results->count : 0
        );
    }
    
    /**
     * Получаване на оферти от магазини
     */
    private function get_product_offers($post_id) {
        $stores = get_post_meta($post_id, '_parfume_stores', true);
        $offers = array();
        
        if (!empty($stores) && is_array($stores)) {
            foreach ($stores as $store) {
                if (!empty($store['scraped_price'])) {
                    $offer = array(
                        '@type' => 'Offer',
                        'price' => floatval(str_replace(',', '.', preg_replace('/[^\d,.]/', '', $store['scraped_price']))),
                        'priceCurrency' => 'BGN',
                        'availability' => $this->get_availability_schema($store['scraped_availability'] ?? ''),
                        'url' => $store['affiliate_url'] ?? '',
                        'seller' => array(
                            '@type' => 'Organization',
                            'name' => $store['store_name'] ?? ''
                        )
                    );
                    
                    // Добавяне на валидност на офертата
                    $offer['validThrough'] = date('Y-m-d', strtotime('+30 days'));
                    
                    $offers[] = $offer;
                }
            }
        }
        
        return $offers;
    }
    
    /**
     * Конвертиране на статус наличност към Schema.org формат
     */
    private function get_availability_schema($availability) {
        $availability_lower = strtolower($availability);
        
        if (strpos($availability_lower, 'наличен') !== false || strpos($availability_lower, 'в наличност') !== false) {
            return 'https://schema.org/InStock';
        } elseif (strpos($availability_lower, 'изчерпан') !== false || strpos($availability_lower, 'няма наличност') !== false) {
            return 'https://schema.org/OutOfStock';
        } elseif (strpos($availability_lower, 'под поръчка') !== false) {
            return 'https://schema.org/PreOrder';
        }
        
        return 'https://schema.org/InStock'; // По подразбиране
    }
    
    /**
     * Получаване на продукти за категория
     */
    private function get_category_products($term) {
        $products = get_posts(array(
            'post_type' => 'parfumes',
            'posts_per_page' => 10,
            'tax_query' => array(
                array(
                    'taxonomy' => $term->taxonomy,
                    'field' => 'term_id',
                    'terms' => $term->term_id
                )
            )
        ));
        
        $items = array();
        foreach ($products as $index => $product) {
            $items[] = array(
                '@type' => 'ListItem',
                'position' => $index + 1,
                'item' => array(
                    '@type' => 'Product',
                    'name' => $product->post_title,
                    'url' => get_permalink($product->ID)
                )
            );
        }
        
        return $items;
    }
    
    /**
     * Получаване на продукти за архивна страница
     */
    private function get_archive_products() {
        global $wp_query;
        
        $items = array();
        if ($wp_query->have_posts()) {
            $position = 1;
            while ($wp_query->have_posts()) {
                $wp_query->the_post();
                $items[] = array(
                    '@type' => 'ListItem',
                    'position' => $position,
                    'item' => array(
                        '@type' => 'Product',
                        'name' => get_the_title(),
                        'url' => get_permalink()
                    )
                );
                $position++;
            }
            wp_reset_postdata();
        }
        
        return $items;
    }
    
    /**
     * Модифициране на заглавието
     */
    public function modify_title($title, $sep) {
        if (is_singular('parfumes')) {
            global $post;
            $brand = $this->get_brand_name($post->ID);
            if ($brand) {
                $title = $brand . ' - ' . get_the_title() . ' ' . $sep . ' ' . get_bloginfo('name');
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
}

// Инициализация на модула
new Parfume_Catalog_Schema();