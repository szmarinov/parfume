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
    const SCHEMA_ARTICLE = 'Article';
    const SCHEMA_COLLECTION = 'CollectionPage';

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
        
        // Hook за blog functionality
        add_action('wp_head', array($this, 'add_blog_schema_markup'));
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
        
        // Breadcrumb schema за всички релевантни страници
        $this->add_breadcrumb_schema();
    }

    /**
     * Product Schema за single парфюм
     */
    private function add_product_schema() {
        global $post;
        
        if (!$post || $post->post_type !== 'parfumes') {
            return;
        }

        $schema_data = array(
            '@context' => 'https://schema.org',
            '@type' => self::SCHEMA_PRODUCT,
            'name' => get_the_title(),
            'description' => $this->get_meta_description($post->ID),
            'url' => get_permalink(),
            'category' => 'Парфюми'
        );

        // Марка
        $brand_terms = wp_get_object_terms($post->ID, 'parfume_marki');
        if (!empty($brand_terms) && !is_wp_error($brand_terms)) {
            $schema_data['brand'] = array(
                '@type' => 'Brand',
                'name' => $brand_terms[0]->name
            );
        }

        // Основно изображение
        $thumbnail_id = get_post_thumbnail_id($post->ID);
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

        // SKU и identifier
        $sku = get_post_meta($post->ID, '_parfume_sku', true);
        if ($sku) {
            $schema_data['sku'] = $sku;
            $schema_data['identifier'] = $sku;
        } else {
            $schema_data['identifier'] = 'parfume-' . $post->ID;
        }

        // Категории и атрибути
        $this->add_product_categories($schema_data, $post->ID);
        $this->add_product_attributes($schema_data, $post->ID);

        // Рейтинг и ревюта
        $rating_data = $this->get_product_rating_data($post->ID);
        if ($rating_data['count'] > 0) {
            $schema_data['aggregateRating'] = array(
                '@type' => self::SCHEMA_AGGREGATE_RATING,
                'ratingValue' => $rating_data['average'],
                'reviewCount' => $rating_data['count'],
                'bestRating' => 5,
                'worstRating' => 1
            );
        }

        // Оферти от магазини
        $offers = $this->get_product_offers($post->ID);
        if (!empty($offers)) {
            if (count($offers) === 1) {
                $schema_data['offers'] = $offers[0];
            } else {
                $schema_data['offers'] = array(
                    '@type' => 'AggregateOffer',
                    'offers' => $offers,
                    'offerCount' => count($offers),
                    'lowPrice' => min(array_column($offers, 'price')),
                    'highPrice' => max(array_column($offers, 'price')),
                    'priceCurrency' => 'BGN'
                );
            }
        }

        // Допълнителни атрибути
        $launch_year = get_post_meta($post->ID, '_parfume_launch_year', true);
        if ($launch_year) {
            $schema_data['releaseDate'] = $launch_year . '-01-01';
        }

        // Publisher/Manufacturer
        $schema_data['manufacturer'] = $this->get_organization_data();

        ?>
        <script type="application/ld+json">
        <?php echo wp_json_encode($schema_data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>
        </script>
        <?php
    }

    /**
     * Добавяне на категории към product schema
     */
    private function add_product_categories(&$schema_data, $post_id) {
        $categories = array();
        
        // Тип парфюм
        $type_terms = wp_get_object_terms($post_id, 'parfume_type');
        if (!empty($type_terms) && !is_wp_error($type_terms)) {
            $categories[] = $type_terms[0]->name;
        }

        // Вид аромат
        $vid_terms = wp_get_object_terms($post_id, 'parfume_vid');
        if (!empty($vid_terms) && !is_wp_error($vid_terms)) {
            $categories[] = $vid_terms[0]->name;
        }

        // Сезон
        $season_terms = wp_get_object_terms($post_id, 'parfume_season');
        if (!empty($season_terms) && !is_wp_error($season_terms)) {
            foreach ($season_terms as $term) {
                $categories[] = $term->name;
            }
        }

        if (!empty($categories)) {
            $schema_data['category'] = implode(', ', array_unique($categories));
        }
    }

    /**
     * Добавяне на атрибути към product schema
     */
    private function add_product_attributes(&$schema_data, $post_id) {
        $additional_properties = array();

        // Нотки
        $notes_data = $this->get_parfume_notes_data($post_id);
        if (!empty($notes_data)) {
            foreach ($notes_data as $group => $notes) {
                if (!empty($notes)) {
                    $additional_properties[] = array(
                        '@type' => 'PropertyValue',
                        'name' => $group,
                        'value' => implode(', ', $notes)
                    );
                }
            }
        }

        // Интензивност
        $intensity_terms = wp_get_object_terms($post_id, 'parfume_intensity');
        if (!empty($intensity_terms) && !is_wp_error($intensity_terms)) {
            $additional_properties[] = array(
                '@type' => 'PropertyValue',
                'name' => 'Интензивност',
                'value' => $intensity_terms[0]->name
            );
        }

        // Дълготрайност
        $longevity = get_post_meta($post_id, '_parfume_longevity', true);
        if ($longevity) {
            $additional_properties[] = array(
                '@type' => 'PropertyValue',
                'name' => 'Дълготрайност',
                'value' => $longevity
            );
        }

        // Ароматна следа
        $sillage = get_post_meta($post_id, '_parfume_sillage', true);
        if ($sillage) {
            $additional_properties[] = array(
                '@type' => 'PropertyValue',
                'name' => 'Ароматна следа',
                'value' => $sillage
            );
        }

        // Подходящост
        $suitable_day = get_post_meta($post_id, '_parfume_suitable_day', true);
        $suitable_night = get_post_meta($post_id, '_parfume_suitable_night', true);
        $suitability = array();
        if ($suitable_day) $suitability[] = 'Ден';
        if ($suitable_night) $suitability[] = 'Нощ';
        
        if (!empty($suitability)) {
            $additional_properties[] = array(
                '@type' => 'PropertyValue',
                'name' => 'Подходящ за',
                'value' => implode(', ', $suitability)
            );
        }

        if (!empty($additional_properties)) {
            $schema_data['additionalProperty'] = $additional_properties;
        }
    }

    /**
     * Извличане на нотки данни
     */
    private function get_parfume_notes_data($post_id) {
        $notes_data = array(
            'Връхни нотки' => array(),
            'Средни нотки' => array(),
            'Базови нотки' => array()
        );

        $top_notes = get_post_meta($post_id, '_parfume_top_notes', true);
        $middle_notes = get_post_meta($post_id, '_parfume_middle_notes', true);
        $base_notes = get_post_meta($post_id, '_parfume_base_notes', true);

        if (is_array($top_notes)) {
            foreach ($top_notes as $note_id) {
                $term = get_term($note_id, 'parfume_notes');
                if ($term && !is_wp_error($term)) {
                    $notes_data['Връхни нотки'][] = $term->name;
                }
            }
        }

        if (is_array($middle_notes)) {
            foreach ($middle_notes as $note_id) {
                $term = get_term($note_id, 'parfume_notes');
                if ($term && !is_wp_error($term)) {
                    $notes_data['Средни нотки'][] = $term->name;
                }
            }
        }

        if (is_array($base_notes)) {
            foreach ($base_notes as $note_id) {
                $term = get_term($note_id, 'parfume_notes');
                if ($term && !is_wp_error($term)) {
                    $notes_data['Базови нотки'][] = $term->name;
                }
            }
        }

        return $notes_data;
    }

    /**
     * Извличане на рейтинг данни
     */
    private function get_product_rating_data($post_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'parfume_comments';
        
        // Проверяваме дали таблицата съществува
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") !== $table_name) {
            return array('average' => 0, 'count' => 0);
        }
        
        $result = $wpdb->get_row($wpdb->prepare(
            "SELECT AVG(rating) as average, COUNT(*) as count 
             FROM {$table_name} 
             WHERE post_id = %d AND status = 'approved' AND rating > 0",
            $post_id
        ));
        
        return array(
            'average' => $result ? round($result->average, 1) : 0,
            'count' => $result ? intval($result->count) : 0
        );
    }

    /**
     * Извличане на оферти от магазини
     */
    private function get_product_offers($post_id) {
        global $wpdb;
        
        $scraper_table = $wpdb->prefix . 'parfume_scraper_data';
        $offers = array();
        
        // Проверяваме дали таблицата съществува
        if ($wpdb->get_var("SHOW TABLES LIKE '$scraper_table'") !== $scraper_table) {
            return $offers;
        }
        
        $post_stores = get_post_meta($post_id, '_parfume_stores', true);
        
        if (!is_array($post_stores) || empty($post_stores)) {
            return $offers;
        }

        foreach ($post_stores as $store_id => $store_data) {
            if (empty($store_data['affiliate_url'])) {
                continue;
            }
            
            // Получаваме информация за магазина
            $store_info = $this->get_store_info($store_id);
            if (!$store_info) {
                continue;
            }
            
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
                $price = floatval($scraper_data['price']);
                if ($price > 0) {
                    $offer_data['price'] = $price;
                    $offer_data['priceCurrency'] = 'BGN';
                }
                
                // Наличност
                if (!empty($scraper_data['availability'])) {
                    $availability_mapping = array(
                        'Наличен' => 'https://schema.org/InStock',
                        'Няма в наличност' => 'https://schema.org/OutOfStock',
                        'По поръчка' => 'https://schema.org/PreOrder'
                    );
                    
                    $availability = $availability_mapping[$scraper_data['availability']] ?? 'https://schema.org/InStock';
                    $offer_data['availability'] = $availability;
                }

                // Валидност на цената
                if (!empty($scraper_data['last_scraped'])) {
                    $offer_data['priceValidUntil'] = date('c', strtotime($scraper_data['last_scraped'] . ' +7 days'));
                }
            }

            // Условие - добавяме само оферти с цена
            if (isset($offer_data['price'])) {
                $offers[] = $offer_data;
            }
        }

        return $offers;
    }

    /**
     * Получаване на информация за магазин
     */
    private function get_store_info($store_id) {
        $stores = get_option('parfume_catalog_stores', array());
        return isset($stores[$store_id]) ? $stores[$store_id] : null;
    }

    /**
     * Collection Schema за архивни страници
     */
    private function add_collection_schema() {
        $schema_data = array(
            '@context' => 'https://schema.org',
            '@type' => self::SCHEMA_COLLECTION,
            'name' => $this->get_archive_title(),
            'description' => $this->get_archive_description(),
            'url' => $this->get_current_url()
        );

        // Добавяне на organization
        $schema_data['publisher'] = $this->get_organization_data();

        // Брой елементи в колекцията
        global $wp_query;
        if (isset($wp_query->found_posts)) {
            $schema_data['numberOfItems'] = $wp_query->found_posts;
        }

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
        $breadcrumbs = $this->get_breadcrumbs();
        
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
     * Получаване на breadcrumbs
     */
    private function get_breadcrumbs() {
        $breadcrumbs = array();
        $position = 1;

        // Начало
        $breadcrumbs[] = array(
            '@type' => 'ListItem',
            'position' => $position++,
            'name' => __('Начало', 'parfume-catalog'),
            'item' => home_url()
        );

        if (is_singular('parfumes')) {
            global $post;
            
            // Парфюми архив
            $breadcrumbs[] = array(
                '@type' => 'ListItem',
                'position' => $position++,
                'name' => __('Парфюми', 'parfume-catalog'),
                'item' => get_post_type_archive_link('parfumes')
            );

            // Марка ако има
            $brand_terms = wp_get_object_terms($post->ID, 'parfume_marki');
            if (!empty($brand_terms) && !is_wp_error($brand_terms)) {
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

        } elseif (is_singular('parfume_blog')) {
            global $post;
            
            // Блог архив
            $breadcrumbs[] = array(
                '@type' => 'ListItem',
                'position' => $position++,
                'name' => __('Блог', 'parfume-catalog'),
                'item' => $this->get_blog_archive_url()
            );

            // Текущата статия
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
     * Blog Schema functionality
     */
    public function add_blog_schema_markup() {
        if (is_singular('parfume_blog')) {
            $this->add_blog_article_schema();
        } elseif ($this->is_blog_archive()) {
            $this->add_blog_collection_schema();
        }
    }

    /**
     * Проверка за blog archive
     */
    private function is_blog_archive() {
        return get_query_var('parfume_blog_archive') || 
               (is_home() && get_option('show_on_front') === 'posts' && get_option('page_for_posts'));
    }

    /**
     * Article Schema за blog постове
     */
    private function add_blog_article_schema() {
        global $post;
        
        if (!$post || $post->post_type !== 'parfume_blog') {
            return;
        }

        $schema_data = array(
            '@context' => 'https://schema.org',
            '@type' => self::SCHEMA_ARTICLE,
            'headline' => get_the_title(),
            'description' => $this->get_blog_post_description($post->ID),
            'url' => get_permalink(),
            'datePublished' => get_the_date('c'),
            'dateModified' => get_the_modified_date('c'),
            'author' => array(
                '@type' => 'Person',
                'name' => get_the_author()
            ),
            'publisher' => array(
                '@type' => self::SCHEMA_ORGANIZATION,
                'name' => get_bloginfo('name'),
                'logo' => array(
                    '@type' => 'ImageObject',
                    'url' => $this->get_site_logo_url()
                )
            )
        );

        // Основно изображение
        $thumbnail_id = get_post_thumbnail_id($post->ID);
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

        // Категория
        $schema_data['articleSection'] = 'Парфюми и ароматни тенденции';

        ?>
        <script type="application/ld+json">
        <?php echo wp_json_encode($schema_data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>
        </script>
        <?php
    }

    /**
     * Collection Schema за блог архив
     */
    private function add_blog_collection_schema() {
        $schema_data = array(
            '@context' => 'https://schema.org',
            '@type' => self::SCHEMA_COLLECTION,
            'name' => __('Блог за парфюми', 'parfume-catalog'),
            'description' => __('Последни статии и новини за парфюми, ароматни тенденции и съвети.', 'parfume-catalog'),
            'url' => $this->get_blog_archive_url()
        );

        // Publisher
        $schema_data['publisher'] = $this->get_organization_data();

        ?>
        <script type="application/ld+json">
        <?php echo wp_json_encode($schema_data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>
        </script>
        <?php
    }

    /**
     * SEO meta tags
     */
    public function add_seo_meta_tags() {
        if (is_singular('parfumes')) {
            $this->add_product_meta_tags();
        } elseif (is_singular('parfume_blog')) {
            $this->add_blog_meta_tags();
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
     * Blog meta tags
     */
    private function add_blog_meta_tags() {
        global $post;
        
        $meta_description = $this->get_blog_post_description($post->ID);
        
        if ($meta_description) {
            echo '<meta name="description" content="' . esc_attr($meta_description) . '">' . "\n";
        }

        echo '<link rel="canonical" href="' . esc_url(get_permalink()) . '">' . "\n";
        echo '<meta name="author" content="' . esc_attr(get_the_author()) . '">' . "\n";
        echo '<meta name="robots" content="index, follow">' . "\n";
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
        if (is_singular('parfumes')) {
            $this->add_product_og_tags();
        } elseif (is_singular('parfume_blog')) {
            $this->add_blog_og_tags();
        } elseif (is_post_type_archive('parfumes') || is_tax()) {
            $this->add_archive_og_tags();
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
        echo '<meta property="og:site_name" content="' . esc_attr(get_bloginfo('name')) . '">' . "\n";

        $thumbnail_url = get_the_post_thumbnail_url($post->ID, 'large');
        if ($thumbnail_url) {
            echo '<meta property="og:image" content="' . esc_url($thumbnail_url) . '">' . "\n";
        }

        // Марка
        $brand_terms = wp_get_object_terms($post->ID, 'parfume_marki');
        if (!empty($brand_terms) && !is_wp_error($brand_terms)) {
            echo '<meta property="product:brand" content="' . esc_attr($brand_terms[0]->name) . '">' . "\n";
        }

        // Цена
        $offers = $this->get_product_offers($post->ID);
        if (!empty($offers)) {
            $prices = array_column($offers, 'price');
            if (!empty($prices)) {
                echo '<meta property="product:price:amount" content="' . esc_attr(min($prices)) . '">' . "\n";
                echo '<meta property="product:price:currency" content="BGN">' . "\n";
            }
        }
    }

    /**
     * Blog Open Graph tags
     */
    private function add_blog_og_tags() {
        global $post;
        
        echo '<meta property="og:type" content="article">' . "\n";
        echo '<meta property="og:title" content="' . esc_attr(get_the_title()) . '">' . "\n";
        echo '<meta property="og:description" content="' . esc_attr($this->get_blog_post_description($post->ID)) . '">' . "\n";
        echo '<meta property="og:url" content="' . esc_url(get_permalink()) . '">' . "\n";
        echo '<meta property="og:site_name" content="' . esc_attr(get_bloginfo('name')) . '">' . "\n";

        $thumbnail_url = get_the_post_thumbnail_url($post->ID, 'large');
        if ($thumbnail_url) {
            echo '<meta property="og:image" content="' . esc_url($thumbnail_url) . '">' . "\n";
        }

        echo '<meta property="article:published_time" content="' . esc_attr(get_the_date('c')) . '">' . "\n";
        echo '<meta property="article:modified_time" content="' . esc_attr(get_the_modified_date('c')) . '">' . "\n";
        echo '<meta property="article:author" content="' . esc_attr(get_the_author()) . '">' . "\n";
    }

    /**
     * Archive Open Graph tags
     */
    private function add_archive_og_tags() {
        echo '<meta property="og:type" content="website">' . "\n";
        echo '<meta property="og:title" content="' . esc_attr($this->get_archive_title()) . '">' . "\n";
        echo '<meta property="og:description" content="' . esc_attr($this->get_archive_description()) . '">' . "\n";
        echo '<meta property="og:url" content="' . esc_url($this->get_current_url()) . '">' . "\n";
        echo '<meta property="og:site_name" content="' . esc_attr(get_bloginfo('name')) . '">' . "\n";
    }

    /**
     * Twitter Cards
     */
    public function add_twitter_cards() {
        echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
        echo '<meta name="twitter:site" content="@' . esc_attr(get_bloginfo('name')) . '">' . "\n";

        if (is_singular('parfumes') || is_singular('parfume_blog')) {
            global $post;
            echo '<meta name="twitter:title" content="' . esc_attr(get_the_title()) . '">' . "\n";
            
            $description = is_singular('parfumes') ? 
                $this->get_meta_description($post->ID) : 
                $this->get_blog_post_description($post->ID);
            echo '<meta name="twitter:description" content="' . esc_attr($description) . '">' . "\n";

            $thumbnail_url = get_the_post_thumbnail_url($post->ID, 'large');
            if ($thumbnail_url) {
                echo '<meta name="twitter:image" content="' . esc_url($thumbnail_url) . '">' . "\n";
            }
        } else {
            echo '<meta name="twitter:title" content="' . esc_attr($this->get_archive_title()) . '">' . "\n";
            echo '<meta name="twitter:description" content="' . esc_attr($this->get_archive_description()) . '">' . "\n";
        }
    }

    /**
     * Title customization
     */
    public function customize_title($title_parts) {
        if (is_singular('parfumes')) {
            global $post;
            $brand_terms = wp_get_object_terms($post->ID, 'parfume_marki');
            
            if (!empty($brand_terms) && !is_wp_error($brand_terms)) {
                $title_parts['title'] = $brand_terms[0]->name . ' ' . $post->post_title;
            }
            
            $title_parts['site'] = get_bloginfo('name');
            
        } elseif (is_singular('parfume_blog')) {
            // Оставяме default поведението за blog постове
            
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
            
            if (!empty($brand_terms) && !is_wp_error($brand_terms)) {
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
     * JSON-LD Schema в footer
     */
    public function add_json_ld_schema() {
        // Допълнителни schema данни могат да се добавят тук
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
            return sprintf('%s от %s. %s', get_the_title($post_id), $brand, $excerpt);
        }
        
        return $excerpt;
    }

    private function get_meta_keywords($post_id) {
        $keywords = array();
        
        // Марка
        $brand_terms = wp_get_object_terms($post_id, 'parfume_marki');
        if (!empty($brand_terms) && !is_wp_error($brand_terms)) {
            $keywords[] = $brand_terms[0]->name;
        }

        // Тип
        $type_terms = wp_get_object_terms($post_id, 'parfume_type');
        if (!empty($type_terms) && !is_wp_error($type_terms)) {
            $keywords[] = $type_terms[0]->name;
        }

        // Топ нотки
        $top_notes = get_post_meta($post_id, '_parfume_top_notes', true);
        if (is_array($top_notes)) {
            foreach (array_slice($top_notes, 0, 5) as $note_id) {
                $term = get_term($note_id, 'parfume_notes');
                if ($term && !is_wp_error($term)) {
                    $keywords[] = $term->name;
                }
            }
        }

        // Общи ключови думи
        $keywords[] = 'парфюм';
        $keywords[] = 'аромат';
        $keywords[] = 'парфюмерия';

        return implode(', ', array_unique($keywords));
    }

    private function get_parfume_brand_name($post_id) {
        $brand_terms = wp_get_object_terms($post_id, 'parfume_marki');
        if (!empty($brand_terms) && !is_wp_error($brand_terms)) {
            return $brand_terms[0]->name;
        }
        return '';
    }

    private function get_archive_title() {
        if (is_post_type_archive('parfumes')) {
            return __('Парфюми', 'parfume-catalog');
        } elseif (is_tax()) {
            $term = get_queried_object();
            $taxonomy = get_taxonomy($term->taxonomy);
            return $term->name . ' - ' . $taxonomy->labels->name;
        } elseif ($this->is_blog_archive()) {
            return __('Блог за парфюми', 'parfume-catalog');
        }
        
        return get_the_archive_title();
    }

    private function get_archive_description() {
        if (is_post_type_archive('parfumes')) {
            return __('Открийте широка гама от парфюми и аромати. Сравнете цени, прочетете отзиви и намерете перфектния аромат за вас.', 'parfume-catalog');
        } elseif (is_tax()) {
            $term = get_queried_object();
            if ($term->description) {
                return $term->description;
            }
            return sprintf(__('Парфюми от категория %s. Открийте най-добрите аромати и сравнете цени от различни магазини.', 'parfume-catalog'), $term->name);
        } elseif ($this->is_blog_archive()) {
            return __('Последни статии и новини за парфюми, ароматни тенденции и съвети за избор на перфектния аромат.', 'parfume-catalog');
        }
        
        return get_the_archive_description();
    }

    private function get_current_url() {
        return (is_ssl() ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    }

    private function get_blog_post_description($post_id) {
        $post = get_post($post_id);
        
        if ($post->post_excerpt) {
            return $post->post_excerpt;
        }
        
        return wp_trim_words($post->post_content, 25);
    }

    private function get_site_logo_url() {
        $custom_logo_id = get_theme_mod('custom_logo');
        if ($custom_logo_id) {
            $logo_data = wp_get_attachment_image_src($custom_logo_id, 'full');
            return $logo_data ? $logo_data[0] : '';
        }
        
        // Fallback logo
        $upload_dir = wp_upload_dir();
        return $upload_dir['baseurl'] . '/parfume-catalog-logo.png';
    }

    private function get_social_profiles() {
        // Може да се извлече от theme customizer или plugin settings
        $options = get_option('parfume_catalog_options', array());
        $profiles = array();
        
        if (!empty($options['facebook_url'])) {
            $profiles[] = $options['facebook_url'];
        }
        if (!empty($options['instagram_url'])) {
            $profiles[] = $options['instagram_url'];
        }
        if (!empty($options['twitter_url'])) {
            $profiles[] = $options['twitter_url'];
        }
        
        return $profiles;
    }

    private function get_blog_archive_url() {
        $options = get_option('parfume_catalog_options', array());
        $archive_slug = isset($options['blog_archive_slug']) ? $options['blog_archive_slug'] : 'blog';
        
        return home_url($archive_slug . '/');
    }

    /**
     * Robots.txt модификация
     */
    public function modify_robots_txt($output, $public) {
        if ($public) {
            $output .= "\n# Parfume Catalog\n";
            $output .= "Allow: /parfiumi/\n";
            $output .= "Allow: /notes/\n";
            $output .= "Allow: /blog/\n";
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
        $post_types['parfume_blog'] = get_post_type_object('parfume_blog');
        return $post_types;
    }

    public function add_taxonomies_to_sitemap($taxonomies) {
        $parfume_taxonomies = array(
            'parfume_type', 
            'parfume_vid', 
            'parfume_marki', 
            'parfume_season', 
            'parfume_intensity', 
            'parfume_notes'
        );
        
        foreach ($parfume_taxonomies as $taxonomy) {
            $tax_object = get_taxonomy($taxonomy);
            if ($tax_object) {
                $taxonomies[$taxonomy] = $tax_object;
            }
        }
        
        return $taxonomies;
    }

    /**
     * Utility функции
     */
    public static function validate_schema_data($schema_data) {
        // Валидация на schema данните
        if (!isset($schema_data['@context']) || !isset($schema_data['@type'])) {
            return false;
        }
        
        return true;
    }

    public static function escape_schema_value($value) {
        if (is_string($value)) {
            return esc_html($value);
        } elseif (is_array($value)) {
            return array_map(array('self', 'escape_schema_value'), $value);
        }
        
        return $value;
    }

    /**
     * Debug функции
     */
    public function debug_schema_output() {
        if (defined('WP_DEBUG') && WP_DEBUG && current_user_can('manage_options')) {
            echo '<!-- Parfume Catalog Schema Debug Mode Active -->' . "\n";
        }
    }
}