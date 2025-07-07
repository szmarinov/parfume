<?php
/**
 * Parfume Catalog Schema Module
 * 
 * SEO оптимизация и Schema.org structured data
 * 
 * @package Parfume_Catalog
 * @since 1.0.0
 */

// Предотвратяване на директен достъп
if (!defined('ABSPATH')) {
    exit;
}

class Parfume_Catalog_Schema {

    /**
     * Schema types
     */
    const SCHEMA_PRODUCT = 'Product';
    const SCHEMA_ORGANIZATION = 'Organization';
    const SCHEMA_BREADCRUMB = 'BreadcrumbList';
    const SCHEMA_REVIEW = 'Review';
    const SCHEMA_AGGREGATE_RATING = 'AggregateRating';
    const SCHEMA_OFFER = 'Offer';

    /**
     * Конструктор
     */
    public function __construct() {
        add_action('wp_head', array($this, 'add_schema_markup'), 1);
        add_action('wp_head', array($this, 'add_seo_meta_tags'), 2);
        add_filter('document_title_parts', array($this, 'customize_title'));
        add_filter('the_seo_framework_title_parts', array($this, 'customize_title'));
        add_filter('wpseo_title', array($this, 'yoast_title_filter'));
        add_filter('rank_math/frontend/title', array($this, 'rank_math_title_filter'));
        add_action('wp_head', array($this, 'add_open_graph_tags'), 5);
        add_action('wp_head', array($this, 'add_twitter_cards'), 6);
        add_filter('the_content', array($this, 'add_microdata_to_content'));
        add_action('wp_footer', array($this, 'add_json_ld_schema'));
        add_filter('robots_txt', array($this, 'modify_robots_txt'), 10, 2);
        add_action('init', array($this, 'add_sitemap_support'));
    }

    /**
     * Добавяне на Schema markup в head
     */
    public function add_schema_markup() {
        if (is_singular('parfumes')) {
            $this->add_product_schema();
        } elseif (is_post_type_archive('parfumes') || is_tax(array('parfume_type', 'parfume_vid', 'parfume_marki', 'parfume_season', 'parfume_intensity', 'parfume_notes'))) {
            $this->add_collection_schema();
        } elseif (is_home() || is_front_page()) {
            $this->add_organization_schema();
        }
        
        // Breadcrumb schema за всички страници
        $this->add_breadcrumb_schema();
    }

    /**
     * Product Schema за single парфюм
     */
    private function add_product_schema() {
        global $post;
        
        $product_data = $this->get_product_schema_data($post->ID);
        
        if (empty($product_data)) {
            return;
        }

        ?>
        <script type="application/ld+json">
        <?php echo wp_json_encode($product_data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>
        </script>
        <?php
    }

    /**
     * Получаване на product schema данни
     */
    private function get_product_schema_data($post_id) {
        $post = get_post($post_id);
        if (!$post) {
            return array();
        }

        // Основни данни
        $schema_data = array(
            '@context' => 'https://schema.org',
            '@type' => self::SCHEMA_PRODUCT,
            'name' => get_the_title($post_id),
            'description' => $this->get_meta_description($post_id),
            'url' => get_permalink($post_id),
            'sku' => 'parfume-' . $post_id,
            'category' => 'Fragrances'
        );

        // Изображение
        $thumbnail_id = get_post_thumbnail_id($post_id);
        if ($thumbnail_id) {
            $image_data = wp_get_attachment_image_src($thumbnail_id, 'large');
            if ($image_data) {
                $schema_data['image'] = array(
                    '@type' => 'ImageObject',
                    'url' => $image_data[0],
                    'width' => $image_data[1],
                    'height' => $image_data[2]
                );
            }
        }

        // Марка
        $brand_terms = wp_get_object_terms($post_id, 'parfume_marki');
        if (!empty($brand_terms)) {
            $schema_data['brand'] = array(
                '@type' => 'Brand',
                'name' => $brand_terms[0]->name
            );
        }

        // Вид аромат (концентрация)
        $vid_terms = wp_get_object_terms($post_id, 'parfume_vid');
        if (!empty($vid_terms)) {
            $schema_data['model'] = $vid_terms[0]->name;
        }

        // Година на излизане
        $launch_year = get_post_meta($post_id, '_parfume_launch_year', true);
        if ($launch_year) {
            $schema_data['releaseDate'] = $launch_year . '-01-01';
        }

        // Дополнителни свойства
        $additional_properties = array();
        
        // Концентрация
        $concentration = get_post_meta($post_id, '_parfume_concentration', true);
        if ($concentration) {
            $additional_properties[] = array(
                '@type' => 'PropertyValue',
                'name' => 'Концентрация',
                'value' => $concentration
            );
        }

        // Подходящ за
        $suitable_properties = array();
        if (get_post_meta($post_id, '_parfume_suitable_day', true)) {
            $suitable_properties[] = 'Ден';
        }
        if (get_post_meta($post_id, '_parfume_suitable_night', true)) {
            $suitable_properties[] = 'Нощ';
        }
        if (!empty($suitable_properties)) {
            $additional_properties[] = array(
                '@type' => 'PropertyValue',
                'name' => 'Подходящ за',
                'value' => implode(', ', $suitable_properties)
            );
        }

        // Сезони
        $season_terms = wp_get_object_terms($post_id, 'parfume_season');
        if (!empty($season_terms)) {
            $seasons = wp_list_pluck($season_terms, 'name');
            $additional_properties[] = array(
                '@type' => 'PropertyValue',
                'name' => 'Подходящи сезони',
                'value' => implode(', ', $seasons)
            );
        }

        // Нотки
        $notes = $this->get_parfume_notes($post_id);
        if (!empty($notes)) {
            foreach ($notes as $position => $note_list) {
                if (!empty($note_list)) {
                    $position_name = $this->get_notes_position_name($position);
                    $additional_properties[] = array(
                        '@type' => 'PropertyValue',
                        'name' => $position_name,
                        'value' => implode(', ', $note_list)
                    );
                }
            }
        }

        if (!empty($additional_properties)) {
            $schema_data['additionalProperty'] = $additional_properties;
        }

        // Рейтинг и ревюта
        $rating_data = $this->get_aggregate_rating_data($post_id);
        if (!empty($rating_data)) {
            $schema_data['aggregateRating'] = $rating_data;
        }

        // Ревюта
        $reviews = $this->get_product_reviews($post_id);
        if (!empty($reviews)) {
            $schema_data['review'] = $reviews;
        }

        // Оферти от магазини
        $offers = $this->get_product_offers($post_id);
        if (!empty($offers)) {
            if (count($offers) === 1) {
                $schema_data['offers'] = $offers[0];
            } else {
                $schema_data['offers'] = $offers;
            }
        }

        return $schema_data;
    }

    /**
     * Получаване на aggregate rating данни
     */
    private function get_aggregate_rating_data($post_id) {
        global $wpdb;
        
        $comments_table = $wpdb->prefix . 'parfume_comments';
        
        $rating_stats = $wpdb->get_row($wpdb->prepare(
            "SELECT 
                AVG(rating) as average_rating,
                COUNT(*) as review_count
             FROM $comments_table 
             WHERE post_id = %d AND status = 'approved'",
            $post_id
        ));

        if (!$rating_stats || $rating_stats->review_count == 0) {
            return array();
        }

        return array(
            '@type' => self::SCHEMA_AGGREGATE_RATING,
            'ratingValue' => round(floatval($rating_stats->average_rating), 1),
            'reviewCount' => intval($rating_stats->review_count),
            'bestRating' => 5,
            'worstRating' => 1
        );
    }

    /**
     * Получаване на product reviews
     */
    private function get_product_reviews($post_id, $limit = 5) {
        global $wpdb;
        
        $comments_table = $wpdb->prefix . 'parfume_comments';
        
        $reviews = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $comments_table 
             WHERE post_id = %d AND status = 'approved' 
             ORDER BY created_at DESC 
             LIMIT %d",
            $post_id,
            $limit
        ), ARRAY_A);

        $review_schemas = array();
        
        foreach ($reviews as $review) {
            $review_schemas[] = array(
                '@type' => self::SCHEMA_REVIEW,
                'author' => array(
                    '@type' => 'Person',
                    'name' => $review['author_name']
                ),
                'reviewRating' => array(
                    '@type' => 'Rating',
                    'ratingValue' => intval($review['rating']),
                    'bestRating' => 5,
                    'worstRating' => 1
                ),
                'reviewBody' => $review['comment_text'],
                'datePublished' => date('c', strtotime($review['created_at']))
            );
        }

        return $review_schemas;
    }

    /**
     * Получаване на product offers
     */
    private function get_product_offers($post_id) {
        global $wpdb;
        
        $scraper_table = $wpdb->prefix . 'parfume_scraper_data';
        $stores = Parfume_Catalog_Stores::get_active_stores();
        
        $offers = array();
        $post_stores = get_post_meta($post_id, '_parfume_stores', true);
        
        if (!is_array($post_stores) || empty($post_stores)) {
            return $offers;
        }

        foreach ($post_stores as $store_id => $store_data) {
            if (!isset($stores[$store_id]) || empty($store_data['affiliate_url'])) {
                continue;
            }
            
            $store_info = $stores[$store_id];
            $scraper_data = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $scraper_table WHERE post_id = %d AND store_id = %s AND status = 'success'",
                $post_id,
                $store_id
            ), ARRAY_A);
            
            $offer_data = array(
                '@type' => self::SCHEMA_OFFER,
                'seller' => array(
                    '@type' => self::SCHEMA_ORGANIZATION,
                    'name' => $store_info['name']
                ),
                'url' => $store_data['affiliate_url'],
                'availability' => 'https://schema.org/InStock'
            );

            // Цена от scraper данни
            if ($scraper_data && !empty($scraper_data['price'])) {
                $offer_data['price'] = $scraper_data['price'];
                $offer_data['priceCurrency'] = 'BGN';
                
                if (!empty($scraper_data['availability'])) {
                    $availability_mapping = array(
                        'Наличен' => 'https://schema.org/InStock',
                        'Няма в наличност' => 'https://schema.org/OutOfStock',
                        'По поръчка' => 'https://schema.org/PreOrder'
                    );
                    
                    $availability = $availability_mapping[$scraper_data['availability']] ?? 'https://schema.org/InStock';
                    $offer_data['availability'] = $availability;
                }

                if (!empty($scraper_data['last_scraped'])) {
                    $offer_data['priceValidUntil'] = date('c', strtotime($scraper_data['last_scraped'] . ' +7 days'));
                }
            }

            $offers[] = $offer_data;
        }

        return $offers;
    }

    /**
     * Collection Schema за архивни страници
     */
    private function add_collection_schema() {
        $schema_data = array(
            '@context' => 'https://schema.org',
            '@type' => 'CollectionPage',
            'name' => $this->get_archive_title(),
            'description' => $this->get_archive_description(),
            'url' => $this->get_current_url()
        );

        // Добавяне на organization
        $schema_data['publisher'] = $this->get_organization_data();

        ?>
        <script type="application/ld+json">
        <?php echo wp_json_encode($schema_data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>
        </script>
        <?php
    }

    /**
     * Organization Schema
     */
    private function add_organization_schema() {
        $schema_data = $this->get_organization_data();

        ?>
        <script type="application/ld+json">
        <?php echo wp_json_encode($schema_data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>
        </script>
        <?php
    }

    /**
     * Получаване на organization данни
     */
    private function get_organization_data() {
        return array(
            '@context' => 'https://schema.org',
            '@type' => self::SCHEMA_ORGANIZATION,
            'name' => get_bloginfo('name'),
            'description' => get_bloginfo('description'),
            'url' => home_url(),
            'logo' => array(
                '@type' => 'ImageObject',
                'url' => $this->get_site_logo_url()
            ),
            'sameAs' => $this->get_social_profiles()
        );
    }

    /**
     * Breadcrumb Schema
     */
    private function add_breadcrumb_schema() {
        $breadcrumbs = $this->generate_breadcrumbs();
        
        if (empty($breadcrumbs)) {
            return;
        }

        $schema_data = array(
            '@context' => 'https://schema.org',
            '@type' => self::SCHEMA_BREADCRUMB,
            'itemListElement' => $breadcrumbs
        );

        ?>
        <script type="application/ld+json">
        <?php echo wp_json_encode($schema_data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>
        </script>
        <?php
    }

    /**
     * Генериране на breadcrumbs
     */
    private function generate_breadcrumbs() {
        $breadcrumbs = array();
        $position = 1;

        // Home
        $breadcrumbs[] = array(
            '@type' => 'ListItem',
            'position' => $position++,
            'name' => __('Начало', 'parfume-catalog'),
            'item' => home_url()
        );

        if (is_singular('parfumes')) {
            // Парфюми архив
            $breadcrumbs[] = array(
                '@type' => 'ListItem',
                'position' => $position++,
                'name' => __('Парфюми', 'parfume-catalog'),
                'item' => get_post_type_archive_link('parfumes')
            );

            // Марка ако има
            $brand_terms = wp_get_object_terms(get_the_ID(), 'parfume_marki');
            if (!empty($brand_terms)) {
                $breadcrumbs[] = array(
                    '@type' => 'ListItem',
                    'position' => $position++,
                    'name' => $brand_terms[0]->name,
                    'item' => get_term_link($brand_terms[0])
                );
            }

            // Текущия парфюм
            $breadcrumbs[] = array(
                '@type' => 'ListItem',
                'position' => $position++,
                'name' => get_the_title(),
                'item' => get_permalink()
            );

        } elseif (is_post_type_archive('parfumes')) {
            $breadcrumbs[] = array(
                '@type' => 'ListItem',
                'position' => $position++,
                'name' => __('Парфюми', 'parfume-catalog'),
                'item' => get_post_type_archive_link('parfumes')
            );

        } elseif (is_tax()) {
            $term = get_queried_object();
            $taxonomy = get_taxonomy($term->taxonomy);
            
            // Парфюми архив
            $breadcrumbs[] = array(
                '@type' => 'ListItem',
                'position' => $position++,
                'name' => __('Парфюми', 'parfume-catalog'),
                'item' => get_post_type_archive_link('parfumes')
            );

            // Текущата категория
            $breadcrumbs[] = array(
                '@type' => 'ListItem',
                'position' => $position++,
                'name' => $term->name,
                'item' => get_term_link($term)
            );
        }

        return $breadcrumbs;
    }

    /**
     * SEO meta tags
     */
    public function add_seo_meta_tags() {
        if (is_singular('parfumes')) {
            $this->add_product_meta_tags();
        } elseif (is_post_type_archive('parfumes') || is_tax()) {
            $this->add_archive_meta_tags();
        }
    }

    /**
     * Product meta tags
     */
    private function add_product_meta_tags() {
        global $post;
        
        $meta_description = $this->get_meta_description($post->ID);
        $meta_keywords = $this->get_meta_keywords($post->ID);
        
        if ($meta_description) {
            echo '<meta name="description" content="' . esc_attr($meta_description) . '">' . "\n";
        }
        
        if ($meta_keywords) {
            echo '<meta name="keywords" content="' . esc_attr($meta_keywords) . '">' . "\n";
        }

        // Canonical URL
        echo '<link rel="canonical" href="' . esc_url(get_permalink()) . '">' . "\n";
        
        // Author
        echo '<meta name="author" content="' . esc_attr(get_bloginfo('name')) . '">' . "\n";
        
        // Robots
        echo '<meta name="robots" content="index, follow, max-snippet:-1, max-image-preview:large, max-video-preview:-1">' . "\n";
    }

    /**
     * Archive meta tags
     */
    private function add_archive_meta_tags() {
        $meta_description = $this->get_archive_description();
        
        if ($meta_description) {
            echo '<meta name="description" content="' . esc_attr($meta_description) . '">' . "\n";
        }

        echo '<link rel="canonical" href="' . esc_url($this->get_current_url()) . '">' . "\n";
        echo '<meta name="robots" content="index, follow">' . "\n";
    }

    /**
     * Open Graph tags
     */
    public function add_open_graph_tags() {
        echo '<meta property="og:site_name" content="' . esc_attr(get_bloginfo('name')) . '">' . "\n";
        echo '<meta property="og:locale" content="bg_BG">' . "\n";
        
        if (is_singular('parfumes')) {
            $this->add_product_og_tags();
        } else {
            $this->add_general_og_tags();
        }
    }

    /**
     * Product Open Graph tags
     */
    private function add_product_og_tags() {
        global $post;
        
        echo '<meta property="og:type" content="product">' . "\n";
        echo '<meta property="og:title" content="' . esc_attr(get_the_title()) . '">' . "\n";
        echo '<meta property="og:description" content="' . esc_attr($this->get_meta_description($post->ID)) . '">' . "\n";
        echo '<meta property="og:url" content="' . esc_url(get_permalink()) . '">' . "\n";
        
        $thumbnail_id = get_post_thumbnail_id();
        if ($thumbnail_id) {
            $image_data = wp_get_attachment_image_src($thumbnail_id, 'large');
            if ($image_data) {
                echo '<meta property="og:image" content="' . esc_url($image_data[0]) . '">' . "\n";
                echo '<meta property="og:image:width" content="' . esc_attr($image_data[1]) . '">' . "\n";
                echo '<meta property="og:image:height" content="' . esc_attr($image_data[2]) . '">' . "\n";
            }
        }

        // Product specific OG tags
        $brand_terms = wp_get_object_terms($post->ID, 'parfume_marki');
        if (!empty($brand_terms)) {
            echo '<meta property="product:brand" content="' . esc_attr($brand_terms[0]->name) . '">' . "\n";
        }

        $price_range = $this->get_parfume_price_range($post->ID);
        if ($price_range) {
            echo '<meta property="product:price:amount" content="' . esc_attr($price_range) . '">' . "\n";
            echo '<meta property="product:price:currency" content="BGN">' . "\n";
        }
    }

    /**
     * General Open Graph tags
     */
    private function add_general_og_tags() {
        echo '<meta property="og:type" content="website">' . "\n";
        echo '<meta property="og:title" content="' . esc_attr($this->get_page_title()) . '">' . "\n";
        echo '<meta property="og:description" content="' . esc_attr($this->get_archive_description()) . '">' . "\n";
        echo '<meta property="og:url" content="' . esc_url($this->get_current_url()) . '">' . "\n";
        
        $site_logo = $this->get_site_logo_url();
        if ($site_logo) {
            echo '<meta property="og:image" content="' . esc_url($site_logo) . '">' . "\n";
        }
    }

    /**
     * Twitter Cards
     */
    public function add_twitter_cards() {
        echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
        
        if (is_singular('parfumes')) {
            global $post;
            echo '<meta name="twitter:title" content="' . esc_attr(get_the_title()) . '">' . "\n";
            echo '<meta name="twitter:description" content="' . esc_attr($this->get_meta_description($post->ID)) . '">' . "\n";
            
            $thumbnail_id = get_post_thumbnail_id();
            if ($thumbnail_id) {
                $image_url = wp_get_attachment_image_url($thumbnail_id, 'large');
                if ($image_url) {
                    echo '<meta name="twitter:image" content="' . esc_url($image_url) . '">' . "\n";
                }
            }
        } else {
            echo '<meta name="twitter:title" content="' . esc_attr($this->get_page_title()) . '">' . "\n";
            echo '<meta name="twitter:description" content="' . esc_attr($this->get_archive_description()) . '">' . "\n";
        }
    }

    /**
     * JSON-LD Schema в footer
     */
    public function add_json_ld_schema() {
        // Дополнителни schema данни ако са необходими
        if (is_singular('parfumes')) {
            $this->add_faq_schema();
        }
    }

    /**
     * FAQ Schema ако има въпроси и отговори
     */
    private function add_faq_schema() {
        // Може да се имплементира ако има FAQ секция
    }

    /**
     * Title customization
     */
    public function customize_title($title_parts) {
        if (is_singular('parfumes')) {
            $post = get_queried_object();
            $brand_terms = wp_get_object_terms($post->ID, 'parfume_marki');
            
            if (!empty($brand_terms)) {
                $title_parts['title'] = $brand_terms[0]->name . ' ' . $post->post_title;
            }
            
            $title_parts['site'] = get_bloginfo('name');
            
        } elseif (is_tax()) {
            $term = get_queried_object();
            $taxonomy = get_taxonomy($term->taxonomy);
            
            $title_parts['title'] = $term->name;
            $title_parts['tagline'] = $taxonomy->labels->name . ' - ' . get_bloginfo('name');
        }
        
        return $title_parts;
    }

    /**
     * Yoast SEO title filter
     */
    public function yoast_title_filter($title) {
        if (is_singular('parfumes')) {
            global $post;
            $brand_terms = wp_get_object_terms($post->ID, 'parfume_marki');
            
            if (!empty($brand_terms)) {
                return $brand_terms[0]->name . ' ' . $post->post_title . ' - ' . get_bloginfo('name');
            }
        }
        
        return $title;
    }

    /**
     * Rank Math title filter
     */
    public function rank_math_title_filter($title) {
        return $this->yoast_title_filter($title);
    }

    /**
     * Добавяне на microdata към съдържанието
     */
    public function add_microdata_to_content($content) {
        if (is_singular('parfumes')) {
            return $this->add_product_microdata($content);
        }
        
        return $content;
    }

    /**
     * Product microdata
     */
    private function add_product_microdata($content) {
        // Може да се добави microdata markup към specific елементи
        return $content;
    }

    /**
     * Helper функции
     */
    private function get_meta_description($post_id) {
        $post = get_post($post_id);
        
        // Custom description
        $custom_description = get_post_meta($post_id, '_parfume_meta_description', true);
        if ($custom_description) {
            return $custom_description;
        }
        
        // Auto-generated description
        $excerpt = $post->post_excerpt ?: wp_trim_words($post->post_content, 25);
        $brand = $this->get_parfume_brand_name($post_id);
        
        if ($brand) {
            return sprintf('%s от %s. %s', $post->post_title, $brand, $excerpt);
        }
        
        return $excerpt;
    }

    private function get_meta_keywords($post_id) {
        $keywords = array();
        
        // Brand
        $brand = $this->get_parfume_brand_name($post_id);
        if ($brand) {
            $keywords[] = $brand;
        }
        
        // Type
        $type_terms = wp_get_object_terms($post_id, 'parfume_type');
        if (!empty($type_terms)) {
            $keywords = array_merge($keywords, wp_list_pluck($type_terms, 'name'));
        }
        
        // Notes (top 5)
        $notes = $this->get_parfume_notes($post_id);
        if (!empty($notes['top'])) {
            $keywords = array_merge($keywords, array_slice($notes['top'], 0, 5));
        }
        
        return implode(', ', array_unique($keywords));
    }

    private function get_parfume_brand_name($post_id) {
        $brands = wp_get_object_terms($post_id, 'parfume_marki');
        return !empty($brands) ? $brands[0]->name : '';
    }

    private function get_parfume_notes($post_id) {
        $all_notes = wp_get_object_terms($post_id, 'parfume_notes');
        $notes_by_position = array('top' => array(), 'middle' => array(), 'base' => array());
        
        foreach ($all_notes as $note) {
            $position = get_term_meta($note->term_id, 'note_position', true);
            if (isset($notes_by_position[$position])) {
                $notes_by_position[$position][] = $note->name;
            }
        }
        
        return $notes_by_position;
    }

    private function get_notes_position_name($position) {
        $positions = array(
            'top' => 'Връхни нотки',
            'middle' => 'Средни нотки',
            'base' => 'Базови нотки'
        );
        
        return $positions[$position] ?? $position;
    }

    private function get_parfume_price_range($post_id) {
        global $wpdb;
        
        $scraper_table = $wpdb->prefix . 'parfume_scraper_data';
        $price = $wpdb->get_var($wpdb->prepare(
            "SELECT MIN(price) FROM $scraper_table WHERE post_id = %d AND price IS NOT NULL AND price > 0",
            $post_id
        ));
        
        return $price ? sprintf('%.2f', $price) : '';
    }

    private function get_archive_title() {
        if (is_post_type_archive('parfumes')) {
            return __('Парфюми', 'parfume-catalog');
        } elseif (is_tax()) {
            $term = get_queried_object();
            return $term->name;
        }
        
        return get_the_archive_title();
    }

    private function get_archive_description() {
        if (is_post_type_archive('parfumes')) {
            return __('Открийте най-добрите парфюми с подробни ревюта, сравнения и най-добри цени от различни магазини.', 'parfume-catalog');
        } elseif (is_tax()) {
            $term = get_queried_object();
            return $term->description ?: sprintf(__('Парфюми от категория %s', 'parfume-catalog'), $term->name);
        }
        
        return get_the_archive_description();
    }

    private function get_page_title() {
        if (is_singular()) {
            return get_the_title();
        } elseif (is_archive()) {
            return $this->get_archive_title();
        }
        
        return get_bloginfo('name');
    }

    private function get_current_url() {
        return home_url(add_query_arg(array(), $GLOBALS['wp']->request));
    }

    private function get_site_logo_url() {
        $custom_logo_id = get_theme_mod('custom_logo');
        if ($custom_logo_id) {
            $logo_data = wp_get_attachment_image_src($custom_logo_id, 'full');
            return $logo_data ? $logo_data[0] : '';
        }
        
        return get_template_directory_uri() . '/assets/images/logo.png';
    }

    private function get_social_profiles() {
        // Може да се извлече от theme customizer или plugin settings
        return array();
    }

    /**
     * Robots.txt модификация
     */
    public function modify_robots_txt($output, $public) {
        if ($public) {
            $output .= "\n# Parfume Catalog\n";
            $output .= "Allow: /parfiumi/\n";
            $output .= "Allow: /notes/\n";
            $output .= "Sitemap: " . home_url('sitemap.xml') . "\n";
        }
        
        return $output;
    }

    /**
     * Sitemap поддръжка
     */
    public function add_sitemap_support() {
        // Интеграция с WordPress core sitemaps или Yoast/RankMath
        add_filter('wp_sitemaps_post_types', array($this, 'add_to_sitemap'));
        add_filter('wp_sitemaps_taxonomies', array($this, 'add_taxonomies_to_sitemap'));
    }

    public function add_to_sitemap($post_types) {
        $post_types['parfumes'] = get_post_type_object('parfumes');
        return $post_types;
    }

    public function add_taxonomies_to_sitemap($taxonomies) {
        $parfume_taxonomies = array('parfume_type', 'parfume_vid', 'parfume_marki', 'parfume_season', 'parfume_intensity', 'parfume_notes');
        
        foreach ($parfume_taxonomies as $taxonomy) {
            $taxonomies[$taxonomy] = get_taxonomy($taxonomy);
        }
        
        return $taxonomies;
    }
}