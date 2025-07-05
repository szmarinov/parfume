<?php
/**
 * SEO Optimization System for Parfume Reviews
 * 
 * Handles:
 * - Schema.org structured data
 * - Meta descriptions and titles
 * - Open Graph tags
 * - Breadcrumbs
 * - XML Sitemap enhancements
 * - Social media sharing
 * 
 * @package Parfume_Reviews
 */

namespace Parfume_Reviews\Utils;

class SEO {
    
    /**
     * Initialize SEO system
     */
    public static function init() {
        // Schema markup
        add_action('wp_head', [__CLASS__, 'add_schema_markup']);
        
        // Meta tags
        add_action('wp_head', [__CLASS__, 'add_meta_tags'], 1);
        
        // Open Graph
        add_action('wp_head', [__CLASS__, 'add_open_graph_tags'], 2);
        
        // Title optimization
        add_filter('wp_title', [__CLASS__, 'optimize_title'], 10, 3);
        add_filter('document_title_parts', [__CLASS__, 'optimize_document_title']);
        
        // Meta descriptions
        add_action('wp_head', [__CLASS__, 'add_meta_description']);
        
        // Breadcrumbs
        add_action('parfume_reviews_breadcrumbs', [__CLASS__, 'display_breadcrumbs']);
        
        // Canonical URLs
        add_action('wp_head', [__CLASS__, 'add_canonical_url']);
        
        // Social sharing
        add_action('wp_footer', [__CLASS__, 'add_social_sharing_scripts']);
        
        // Sitemap integration
        add_action('init', [__CLASS__, 'init_sitemap_integration']);
        
        // Rich snippets
        add_filter('the_content', [__CLASS__, 'add_rich_snippets_to_content']);
    }
    
    /**
     * Add schema.org structured data
     */
    public static function add_schema_markup() {
        global $post;
        
        if (!is_singular('parfume') && !is_tax(['marki', 'notes', 'perfumer', 'gender', 'aroma_type', 'season', 'intensity'])) {
            return;
        }
        
        $schema = [];
        
        if (is_singular('parfume')) {
            $schema = self::get_perfume_schema($post);
        } elseif (is_tax()) {
            $schema = self::get_taxonomy_schema();
        }
        
        if (!empty($schema)) {
            echo '<script type="application/ld+json">' . wp_json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . '</script>' . "\n";
        }
    }
    
    /**
     * Get perfume schema markup
     *
     * @param WP_Post $post
     * @return array
     */
    private static function get_perfume_schema($post) {
        $rating = get_post_meta($post->ID, '_parfume_rating', true);
        $brands = wp_get_post_terms($post->ID, 'marki', ['fields' => 'names']);
        $notes = wp_get_post_terms($post->ID, 'notes', ['fields' => 'names']);
        $perfumers = wp_get_post_terms($post->ID, 'perfumer', ['fields' => 'names']);
        $gender = get_post_meta($post->ID, '_parfume_gender', true);
        $release_year = get_post_meta($post->ID, '_parfume_release_year', true);
        $stores = get_post_meta($post->ID, '_parfume_stores', true);
        
        // Handle WP_Error
        if (is_wp_error($brands)) $brands = [];
        if (is_wp_error($notes)) $notes = [];
        if (is_wp_error($perfumers)) $perfumers = [];
        
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => get_the_title($post->ID),
            'description' => self::get_meta_description($post->ID),
            'url' => get_permalink($post->ID),
            'category' => 'Perfume'
        ];
        
        // Add brand
        if (!empty($brands)) {
            $schema['brand'] = [
                '@type' => 'Brand',
                'name' => $brands[0]
            ];
        }
        
        // Add rating
        if (!empty($rating) && is_numeric($rating)) {
            $schema['aggregateRating'] = [
                '@type' => 'AggregateRating',
                'ratingValue' => floatval($rating),
                'bestRating' => 5,
                'worstRating' => 1,
                'ratingCount' => 1
            ];
        }
        
        // Add image
        if (has_post_thumbnail($post->ID)) {
            $thumbnail_url = get_the_post_thumbnail_url($post->ID, 'full');
            if ($thumbnail_url) {
                $schema['image'] = [
                    '@type' => 'ImageObject',
                    'url' => $thumbnail_url,
                    'caption' => get_the_title($post->ID)
                ];
            }
        }
        
        // Add additional properties
        $additional_properties = [];
        
        if (!empty($gender)) {
            $additional_properties[] = [
                '@type' => 'PropertyValue',
                'name' => 'Target Gender',
                'value' => $gender
            ];
        }
        
        if (!empty($release_year)) {
            $schema['releaseDate'] = $release_year;
        }
        
        if (!empty($notes)) {
            $additional_properties[] = [
                '@type' => 'PropertyValue',
                'name' => 'Fragrance Notes',
                'value' => implode(', ', $notes)
            ];
        }
        
        if (!empty($perfumers)) {
            $schema['creator'] = [
                '@type' => 'Person',
                'name' => $perfumers[0],
                'jobTitle' => 'Perfumer'
            ];
        }
        
        if (!empty($additional_properties)) {
            $schema['additionalProperty'] = $additional_properties;
        }
        
        // Add offers from stores
        if (!empty($stores) && is_array($stores)) {
            $offers = [];
            
            foreach ($stores as $store) {
                if (empty($store['price']) || empty($store['name'])) continue;
                
                // Extract price value
                $price_value = preg_replace('/[^\d.,]/', '', $store['price']);
                $price_value = str_replace(',', '.', $price_value);
                
                if (is_numeric($price_value)) {
                    $offer = [
                        '@type' => 'Offer',
                        'price' => floatval($price_value),
                        'priceCurrency' => 'BGN',
                        'seller' => [
                            '@type' => 'Organization',
                            'name' => $store['name']
                        ],
                        'availability' => 'https://schema.org/InStock'
                    ];
                    
                    if (!empty($store['url'])) {
                        $offer['url'] = $store['url'];
                    }
                    
                    $offers[] = $offer;
                }
            }
            
            if (!empty($offers)) {
                $schema['offers'] = count($offers) === 1 ? $offers[0] : $offers;
            }
        }
        
        return $schema;
    }
    
    /**
     * Get taxonomy schema markup
     *
     * @return array
     */
    private static function get_taxonomy_schema() {
        $term = get_queried_object();
        
        if (!$term) return [];
        
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'CollectionPage',
            'name' => $term->name,
            'description' => $term->description ?: self::get_taxonomy_description($term),
            'url' => get_term_link($term),
        ];
        
        // Add breadcrumbs
        $breadcrumbs = self::get_breadcrumbs();
        if (!empty($breadcrumbs)) {
            $schema['breadcrumb'] = [
                '@type' => 'BreadcrumbList',
                'itemListElement' => $breadcrumbs
            ];
        }
        
        return $schema;
    }
    
    /**
     * Add meta tags
     */
    public static function add_meta_tags() {
        echo '<meta name="robots" content="index, follow, max-snippet:-1, max-image-preview:large, max-video-preview:-1">' . "\n";
        echo '<meta name="viewport" content="width=device-width, initial-scale=1">' . "\n";
        
        if (is_singular('parfume')) {
            global $post;
            $keywords = self::get_parfume_keywords($post->ID);
            if ($keywords) {
                echo '<meta name="keywords" content="' . esc_attr($keywords) . '">' . "\n";
            }
        }
    }
    
    /**
     * Add Open Graph tags
     */
    public static function add_open_graph_tags() {
        global $post;
        
        echo '<meta property="og:site_name" content="' . esc_attr(get_bloginfo('name')) . '">' . "\n";
        echo '<meta property="og:locale" content="' . esc_attr(get_locale()) . '">' . "\n";
        
        if (is_singular('parfume')) {
            echo '<meta property="og:type" content="product">' . "\n";
            echo '<meta property="og:title" content="' . esc_attr(get_the_title($post->ID)) . '">' . "\n";
            echo '<meta property="og:description" content="' . esc_attr(self::get_meta_description($post->ID)) . '">' . "\n";
            echo '<meta property="og:url" content="' . esc_url(get_permalink($post->ID)) . '">' . "\n";
            
            if (has_post_thumbnail($post->ID)) {
                $thumbnail_url = get_the_post_thumbnail_url($post->ID, 'large');
                echo '<meta property="og:image" content="' . esc_url($thumbnail_url) . '">' . "\n";
                echo '<meta property="og:image:width" content="1200">' . "\n";
                echo '<meta property="og:image:height" content="630">' . "\n";
            }
            
            // Product specific tags
            $brands = wp_get_post_terms($post->ID, 'marki', ['fields' => 'names']);
            if (!empty($brands) && !is_wp_error($brands)) {
                echo '<meta property="product:brand" content="' . esc_attr($brands[0]) . '">' . "\n";
            }
            
            $rating = get_post_meta($post->ID, '_parfume_rating', true);
            if (!empty($rating)) {
                echo '<meta property="product:rating:value" content="' . esc_attr($rating) . '">' . "\n";
                echo '<meta property="product:rating:scale" content="5">' . "\n";
            }
            
        } elseif (is_tax()) {
            $term = get_queried_object();
            echo '<meta property="og:type" content="website">' . "\n";
            echo '<meta property="og:title" content="' . esc_attr($term->name) . '">' . "\n";
            echo '<meta property="og:description" content="' . esc_attr(self::get_taxonomy_description($term)) . '">' . "\n";
            echo '<meta property="og:url" content="' . esc_url(get_term_link($term)) . '">' . "\n";
        }
        
        // Twitter Cards
        echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
        echo '<meta name="twitter:site" content="@parfumereview">' . "\n";
    }
    
    /**
     * Optimize page title
     *
     * @param string $title
     * @param string $sep
     * @param string $seplocation
     * @return string
     */
    public static function optimize_title($title, $sep, $seplocation) {
        if (is_singular('parfume')) {
            global $post;
            $brands = wp_get_post_terms($post->ID, 'marki', ['fields' => 'names']);
            $brand_name = (!empty($brands) && !is_wp_error($brands)) ? $brands[0] : '';
            
            if ($brand_name) {
                $title = get_the_title($post->ID) . ' от ' . $brand_name . ' ' . $sep . ' Ревю ';
            }
        } elseif (is_tax()) {
            $term = get_queried_object();
            $taxonomy_obj = get_taxonomy($term->taxonomy);
            $title = $term->name . ' ' . $sep . ' ' . $taxonomy_obj->labels->name . ' ';
        }
        
        return $title;
    }
    
    /**
     * Optimize document title parts
     *
     * @param array $title_parts
     * @return array
     */
    public static function optimize_document_title($title_parts) {
        if (is_singular('parfume')) {
            global $post;
            $brands = wp_get_post_terms($post->ID, 'marki', ['fields' => 'names']);
            $brand_name = (!empty($brands) && !is_wp_error($brands)) ? $brands[0] : '';
            
            if ($brand_name) {
                $title_parts['title'] = get_the_title($post->ID) . ' от ' . $brand_name;
            }
        }
        
        return $title_parts;
    }
    
    /**
     * Add meta description
     */
    public static function add_meta_description() {
        $description = '';
        
        if (is_singular('parfume')) {
            global $post;
            $description = self::get_meta_description($post->ID);
        } elseif (is_tax()) {
            $term = get_queried_object();
            $description = self::get_taxonomy_description($term);
        }
        
        if ($description) {
            echo '<meta name="description" content="' . esc_attr($description) . '">' . "\n";
        }
    }
    
    /**
     * Get meta description for perfume
     *
     * @param int $post_id
     * @return string
     */
    private static function get_meta_description($post_id) {
        // Try excerpt first
        $excerpt = get_the_excerpt($post_id);
        if ($excerpt) {
            return wp_trim_words($excerpt, 25);
        }
        
        // Generate from content and metadata
        $content = get_post_field('post_content', $post_id);
        $content = wp_strip_all_tags($content);
        
        if ($content) {
            return wp_trim_words($content, 25);
        }
        
        // Fallback to generated description
        $brands = wp_get_post_terms($post_id, 'marki', ['fields' => 'names']);
        $notes = wp_get_post_terms($post_id, 'notes', ['fields' => 'names']);
        $rating = get_post_meta($post_id, '_parfume_rating', true);
        
        $brand_name = (!empty($brands) && !is_wp_error($brands)) ? $brands[0] : '';
        $top_notes = (!empty($notes) && !is_wp_error($notes)) ? array_slice($notes, 0, 3) : [];
        
        $description = 'Ревю на парфюма ' . get_the_title($post_id);
        
        if ($brand_name) {
            $description .= ' от ' . $brand_name;
        }
        
        if (!empty($top_notes)) {
            $description .= ' с нотки от ' . implode(', ', $top_notes);
        }
        
        if ($rating) {
            $description .= '. Рейтинг: ' . $rating . '/5 звезди';
        }
        
        $description .= '. Детайлно ревю, цени и къде да купите.';
        
        return $description;
    }
    
    /**
     * Get taxonomy description
     *
     * @param WP_Term $term
     * @return string
     */
    private static function get_taxonomy_description($term) {
        if ($term->description) {
            return wp_trim_words($term->description, 25);
        }
        
        $taxonomy_obj = get_taxonomy($term->taxonomy);
        $type_name = strtolower($taxonomy_obj->labels->singular_name);
        
        $description = sprintf(
            'Разгледайте всички парфюми от %s %s. Открийте ароматите с %s и прочетете детайлни ревюта.',
            $type_name,
            $term->name,
            $term->name
        );
        
        if ($term->count > 0) {
            $description .= sprintf(' Общо %d парфюма.', $term->count);
        }
        
        return $description;
    }
    
    /**
     * Get keywords for perfume
     *
     * @param int $post_id
     * @return string
     */
    private static function get_parfume_keywords($post_id) {
        $keywords = [get_the_title($post_id)];
        
        // Add brand
        $brands = wp_get_post_terms($post_id, 'marki', ['fields' => 'names']);
        if (!empty($brands) && !is_wp_error($brands)) {
            $keywords = array_merge($keywords, $brands);
        }
        
        // Add notes
        $notes = wp_get_post_terms($post_id, 'notes', ['fields' => 'names']);
        if (!empty($notes) && !is_wp_error($notes)) {
            $keywords = array_merge($keywords, array_slice($notes, 0, 5));
        }
        
        // Add perfumer
        $perfumers = wp_get_post_terms($post_id, 'perfumer', ['fields' => 'names']);
        if (!empty($perfumers) && !is_wp_error($perfumers)) {
            $keywords = array_merge($keywords, $perfumers);
        }
        
        // Add common perfume keywords
        $keywords = array_merge($keywords, ['парфюм', 'ревю', 'аромат', 'тестер']);
        
        return implode(', ', array_unique($keywords));
    }
    
    /**
     * Display breadcrumbs
     */
    public static function display_breadcrumbs() {
        $breadcrumbs = self::get_breadcrumbs();
        
        if (empty($breadcrumbs)) {
            return;
        }
        
        echo '<nav class="parfume-breadcrumbs" aria-label="Breadcrumb">';
        echo '<ol class="breadcrumb-list">';
        
        foreach ($breadcrumbs as $index => $crumb) {
            $position = $index + 1;
            echo '<li class="breadcrumb-item" itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">';
            
            if ($crumb['url']) {
                echo '<a href="' . esc_url($crumb['url']) . '" itemprop="item">';
                echo '<span itemprop="name">' . esc_html($crumb['name']) . '</span>';
                echo '</a>';
            } else {
                echo '<span itemprop="name">' . esc_html($crumb['name']) . '</span>';
            }
            
            echo '<meta itemprop="position" content="' . $position . '">';
            echo '</li>';
        }
        
        echo '</ol>';
        echo '</nav>';
    }
    
    /**
     * Get breadcrumbs array
     *
     * @return array
     */
    private static function get_breadcrumbs() {
        $breadcrumbs = [];
        
        // Home
        $breadcrumbs[] = [
            'name' => 'Начало',
            'url' => home_url('/')
        ];
        
        if (is_singular('parfume')) {
            global $post;
            
            // Parfumes archive
            $settings = get_option('parfume_reviews_settings', []);
            $parfume_slug = !empty($settings['parfume_slug']) ? $settings['parfume_slug'] : 'parfiumi';
            
            $breadcrumbs[] = [
                'name' => 'Парфюми',
                'url' => home_url('/' . $parfume_slug . '/')
            ];
            
            // Brand if available
            $brands = wp_get_post_terms($post->ID, 'marki');
            if (!empty($brands) && !is_wp_error($brands)) {
                $brand = $brands[0];
                $breadcrumbs[] = [
                    'name' => $brand->name,
                    'url' => get_term_link($brand)
                ];
            }
            
            // Current perfume
            $breadcrumbs[] = [
                'name' => get_the_title($post->ID),
                'url' => null
            ];
            
        } elseif (is_tax()) {
            $term = get_queried_object();
            $taxonomy_obj = get_taxonomy($term->taxonomy);
            
            // Parfumes archive
            $settings = get_option('parfume_reviews_settings', []);
            $parfume_slug = !empty($settings['parfume_slug']) ? $settings['parfume_slug'] : 'parfiumi';
            
            $breadcrumbs[] = [
                'name' => 'Парфюми',
                'url' => home_url('/' . $parfume_slug . '/')
            ];
            
            // Taxonomy archive
            $breadcrumbs[] = [
                'name' => $taxonomy_obj->labels->name,
                'url' => home_url('/' . $parfume_slug . '/' . $term->taxonomy . '/')
            ];
            
            // Current term
            $breadcrumbs[] = [
                'name' => $term->name,
                'url' => null
            ];
        }
        
        return $breadcrumbs;
    }
    
    /**
     * Add canonical URL
     */
    public static function add_canonical_url() {
        $canonical_url = '';
        
        if (is_singular('parfume')) {
            global $post;
            $canonical_url = get_permalink($post->ID);
        } elseif (is_tax()) {
            $term = get_queried_object();
            $canonical_url = get_term_link($term);
        }
        
        if ($canonical_url && !is_wp_error($canonical_url)) {
            echo '<link rel="canonical" href="' . esc_url($canonical_url) . '">' . "\n";
        }
    }
    
    /**
     * Add social sharing scripts
     */
    public static function add_social_sharing_scripts() {
        if (!is_singular('parfume')) {
            return;
        }
        
        ?>
        <script>
        // Simple social sharing functions
        function shareOnFacebook() {
            window.open('https://www.facebook.com/sharer/sharer.php?u=' + encodeURIComponent(window.location.href), 'facebook-share', 'width=580,height=296');
        }
        
        function shareOnTwitter() {
            var text = document.title + ' - ';
            window.open('https://twitter.com/intent/tweet?text=' + encodeURIComponent(text) + '&url=' + encodeURIComponent(window.location.href), 'twitter-share', 'width=550,height=235');
        }
        
        function shareOnPinterest() {
            var img = document.querySelector('meta[property="og:image"]');
            var imgUrl = img ? img.getAttribute('content') : '';
            var desc = document.querySelector('meta[name="description"]');
            var description = desc ? desc.getAttribute('content') : document.title;
            
            window.open('https://pinterest.com/pin/create/button/?url=' + encodeURIComponent(window.location.href) + '&media=' + encodeURIComponent(imgUrl) + '&description=' + encodeURIComponent(description), 'pinterest-share', 'width=750,height=320');
        }
        </script>
        <?php
    }
    
    /**
     * Initialize sitemap integration
     */
    public static function init_sitemap_integration() {
        // Add to WordPress core sitemaps
        add_filter('wp_sitemaps_post_types', [__CLASS__, 'add_parfume_to_sitemap']);
        add_filter('wp_sitemaps_taxonomies', [__CLASS__, 'add_taxonomies_to_sitemap']);
        
        // Enhance sitemap with additional data
        add_filter('wp_sitemaps_posts_entry', [__CLASS__, 'enhance_sitemap_entry'], 10, 3);
    }
    
    /**
     * Add parfume post type to sitemap
     *
     * @param array $post_types
     * @return array
     */
    public static function add_parfume_to_sitemap($post_types) {
        $post_types[] = 'parfume';
        return $post_types;
    }
    
    /**
     * Add parfume taxonomies to sitemap
     *
     * @param array $taxonomies
     * @return array
     */
    public static function add_taxonomies_to_sitemap($taxonomies) {
        $parfume_taxonomies = ['marki', 'notes', 'perfumer', 'gender', 'aroma_type', 'season', 'intensity'];
        
        foreach ($parfume_taxonomies as $taxonomy) {
            $taxonomies[] = $taxonomy;
        }
        
        return $taxonomies;
    }
    
    /**
     * Enhance sitemap entry with additional data
     *
     * @param array $sitemap_entry
     * @param WP_Post $post
     * @param string $sitemap
     * @return array
     */
    public static function enhance_sitemap_entry($sitemap_entry, $post, $sitemap) {
        if ($post->post_type !== 'parfume') {
            return $sitemap_entry;
        }
        
        // Add image if available
        if (has_post_thumbnail($post->ID)) {
            $thumbnail_url = get_the_post_thumbnail_url($post->ID, 'large');
            if ($thumbnail_url) {
                $sitemap_entry['images'] = [
                    [
                        'loc' => $thumbnail_url,
                        'title' => get_the_title($post->ID),
                        'caption' => get_the_excerpt($post->ID) ?: get_the_title($post->ID)
                    ]
                ];
            }
        }
        
        return $sitemap_entry;
    }
    
    /**
     * Add rich snippets to content
     *
     * @param string $content
     * @return string
     */
    public static function add_rich_snippets_to_content($content) {
        if (!is_singular('parfume')) {
            return $content;
        }
        
        global $post;
        
        $rating = get_post_meta($post->ID, '_parfume_rating', true);
        if (!$rating) {
            return $content;
        }
        
        // Add rating display at the beginning of content
        $rating_html = '<div class="parfume-rating-snippet" itemscope itemtype="https://schema.org/AggregateRating">';
        $rating_html .= '<span class="rating-label">Рейтинг: </span>';
        $rating_html .= '<span class="rating-stars">';
        
        for ($i = 1; $i <= 5; $i++) {
            $class = $i <= round(floatval($rating)) ? 'filled' : '';
            $rating_html .= '<span class="star ' . $class . '">★</span>';
        }
        
        $rating_html .= '</span>';
        $rating_html .= ' <span itemprop="ratingValue">' . number_format(floatval($rating), 1) . '</span>/5';
        $rating_html .= ' (<span itemprop="ratingCount">1</span> ревю)';
        $rating_html .= '<meta itemprop="bestRating" content="5">';
        $rating_html .= '<meta itemprop="worstRating" content="1">';
        $rating_html .= '</div>';
        
        return $rating_html . $content;
    }
}

// Initialize SEO system
SEO::init();