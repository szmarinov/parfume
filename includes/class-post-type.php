<?php
namespace Parfume_Reviews;

class Post_Type {
    public function __construct() {
        add_action('init', array($this, 'register_post_type'), 0);
        add_action('init', array($this, 'register_blog_post_type'), 0); 
        add_action('init', array($this, 'add_custom_rewrite_rules'), 10);
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post', array($this, 'save_meta_boxes'));
        add_filter('template_include', array($this, 'template_loader'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
        add_action('pre_get_posts', array($this, 'modify_archive_query'));
        add_filter('posts_where', array($this, 'filter_posts_where'), 10, 2);
        
        // Add shortcodes for archive pages
        add_shortcode('all_brands_archive', array($this, 'all_brands_archive_shortcode'));
        add_shortcode('all_notes_archive', array($this, 'all_notes_archive_shortcode'));
        add_shortcode('all_perfumers_archive', array($this, 'all_perfumers_archive_shortcode'));
        
        // AJAX handlers for price updates
        add_action('wp_ajax_update_store_price', array($this, 'ajax_update_store_price'));
        add_action('wp_ajax_get_store_sizes', array($this, 'ajax_get_store_sizes'));
        
        // Debug hooks
        add_action('wp', array($this, 'debug_current_request'));
    }
    
    public function register_post_type() {
        $settings = get_option('parfume_reviews_settings', array());
        $slug = !empty($settings['parfume_slug']) ? $settings['parfume_slug'] : 'parfiumi';
        
        $labels = array(
            'name' => __('Parfumes', 'parfume-reviews'),
            'singular_name' => __('Parfume', 'parfume-reviews'),
            'menu_name' => __('Parfumes', 'parfume-reviews'),
            'name_admin_bar' => __('Parfume', 'parfume-reviews'),
            'add_new' => __('Add New', 'parfume-reviews'),
            'add_new_item' => __('Add New Parfume', 'parfume-reviews'),
            'new_item' => __('New Parfume', 'parfume-reviews'),
            'edit_item' => __('Edit Parfume', 'parfume-reviews'),
            'view_item' => __('View Parfume', 'parfume-reviews'),
            'all_items' => __('All Parfumes', 'parfume-reviews'),
            'search_items' => __('Search Parfumes', 'parfume-reviews'),
            'parent_item_colon' => __('Parent Parfumes:', 'parfume-reviews'),
            'not_found' => __('No parfumes found.', 'parfume-reviews'),
            'not_found_in_trash' => __('No parfumes found in Trash.', 'parfume-reviews')
        );
        
        // Check if we need to adjust for subdirectory
        $with_front = true;
        $site_url = site_url();
        $home_url = home_url();
        
        if ($site_url !== $home_url) {
            $with_front = true;
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("Parfume Post Type: Site is in subdirectory, using with_front = true");
            }
        }
        
        $args = array(
            'labels' => $labels,
            'public' => true,
            'publicly_queryable' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'query_var' => true,
            'rewrite' => array(
                'slug' => $slug, 
                'with_front' => $with_front,
                'feeds' => true,
                'pages' => true
            ),
            'capability_type' => 'post',
            'has_archive' => true,
            'hierarchical' => false,
            'menu_position' => 5,
            'supports' => array('title', 'editor', 'thumbnail', 'excerpt', 'comments', 'custom-fields'),
            'show_in_rest' => true,
            'menu_icon' => 'dashicons-airplane',
            'taxonomies' => array('marki', 'gender', 'aroma_type', 'season', 'intensity', 'notes', 'perfumer'),
        );
        
        register_post_type('parfume', $args);
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("Parfume Post Type registered with slug: $slug, with_front: " . ($with_front ? 'true' : 'false'));
        }
    }
    
    public function register_blog_post_type() {
        $settings = get_option('parfume_reviews_settings', array());
        $slug = !empty($settings['parfume_slug']) ? $settings['parfume_slug'] : 'parfiumi';
        
        $labels = array(
            'name' => __('Blog Posts', 'parfume-reviews'),
            'singular_name' => __('Blog Post', 'parfume-reviews'),
            'menu_name' => __('Blog Posts', 'parfume-reviews'),
            'name_admin_bar' => __('Blog Post', 'parfume-reviews'),
            'add_new' => __('Add New', 'parfume-reviews'),
            'add_new_item' => __('Add New Blog Post', 'parfume-reviews'),
            'new_item' => __('New Blog Post', 'parfume-reviews'),
            'edit_item' => __('Edit Blog Post', 'parfume-reviews'),
            'view_item' => __('View Blog Post', 'parfume-reviews'),
            'all_items' => __('All Blog Posts', 'parfume-reviews'),
            'search_items' => __('Search Blog Posts', 'parfume-reviews'),
            'not_found' => __('No blog posts found.', 'parfume-reviews'),
            'not_found_in_trash' => __('No blog posts found in Trash.', 'parfume-reviews')
        );
        
        $args = array(
            'labels' => $labels,
            'public' => true,
            'publicly_queryable' => true,
            'show_ui' => true,
            'show_in_menu' => 'edit.php?post_type=parfume',
            'query_var' => true,
            'rewrite' => array('slug' => $slug . '/blog'),
            'capability_type' => 'post',
            'has_archive' => true,
            'hierarchical' => false,
            'menu_position' => null,
            'supports' => array('title', 'editor', 'thumbnail', 'excerpt', 'comments', 'author', 'custom-fields'),
            'show_in_rest' => true,
            'taxonomies' => array('category', 'post_tag'),
        );
        
        register_post_type('parfume_blog', $args);
    }
    
    public function add_custom_rewrite_rules() {
        $settings = get_option('parfume_reviews_settings', array());
        $parfume_slug = !empty($settings['parfume_slug']) ? $settings['parfume_slug'] : 'parfiumi';
        
        // Add some custom rewrite rules if needed
        add_rewrite_rule(
            '^' . $parfume_slug . '/([^/]+)/page/?([0-9]{1,})/?$',
            'index.php?post_type=parfume&name=$matches[1]&paged=$matches[2]',
            'top'
        );
        
        // Debug output
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("Custom rewrite rules added for: $parfume_slug");
        }
    }
    
    public function modify_archive_query($query) {
        if (!is_admin() && $query->is_main_query()) {
            if (is_post_type_archive('parfume') || is_tax(array('marki', 'gender', 'aroma_type', 'season', 'intensity', 'notes', 'perfumer'))) {
                $settings = get_option('parfume_reviews_settings', array());
                $per_page = !empty($settings['archive_posts_per_page']) ? intval($settings['archive_posts_per_page']) : 12;
                $query->set('posts_per_page', $per_page);
                
                // Handle filtering and sorting
                $this->handle_query_filters($query);
            }
        }
    }
    
    private function handle_query_filters($query) {
        // Add multiple taxonomy filters from GET parameters
        $tax_query = array();
        $taxonomies = array('gender', 'aroma_type', 'marki', 'season', 'intensity', 'notes', 'perfumer');
        
        foreach ($taxonomies as $taxonomy) {
            if (!empty($_GET[$taxonomy])) {
                $terms = is_array($_GET[$taxonomy]) ? $_GET[$taxonomy] : array($_GET[$taxonomy]);
                $terms = array_map('sanitize_text_field', $terms);
                
                if (!empty($terms)) {
                    $tax_query[] = array(
                        'taxonomy' => $taxonomy,
                        'field' => 'slug',
                        'terms' => $terms,
                        'operator' => 'IN'
                    );
                }
            }
        }
        
        if (!empty($tax_query)) {
            $tax_query['relation'] = 'AND';
            $query->set('tax_query', $tax_query);
        }
        
        // Handle sorting
        if (!empty($_GET['orderby'])) {
            $orderby = sanitize_text_field($_GET['orderby']);
            $order = !empty($_GET['order']) ? sanitize_text_field($_GET['order']) : 'DESC';
            
            switch ($orderby) {
                case 'rating':
                    $query->set('meta_key', '_parfume_rating');
                    $query->set('orderby', 'meta_value_num');
                    $query->set('order', $order);
                    break;
                case 'title':
                    $query->set('orderby', 'title');
                    $query->set('order', $order);
                    break;
                case 'date':
                default:
                    $query->set('orderby', 'date');
                    $query->set('order', $order);
                    break;
            }
        }
    }
    
    public function filter_posts_where($where, $query) {
        global $wpdb;
        
        if (!is_admin() && $query->is_main_query() && (is_post_type_archive('parfume') || is_tax(array('marki', 'gender', 'aroma_type', 'season', 'intensity', 'notes', 'perfumer')))) {
            // Handle search
            if (!empty($_GET['search'])) {
                $search_term = sanitize_text_field($_GET['search']);
                $where .= $wpdb->prepare(" AND (
                    {$wpdb->posts}.post_title LIKE %s 
                    OR {$wpdb->posts}.post_content LIKE %s
                )", 
                    '%' . $search_term . '%', 
                    '%' . $search_term . '%'
                );
            }
            
            // Price range filter
            if (!empty($_GET['price_min']) || !empty($_GET['price_max'])) {
                $price_min = !empty($_GET['price_min']) ? floatval($_GET['price_min']) : 0;
                $price_max = !empty($_GET['price_max']) ? floatval($_GET['price_max']) : 999999;
                
                $where .= $wpdb->prepare(" AND {$wpdb->posts}.ID IN (
                    SELECT post_id FROM {$wpdb->postmeta} 
                    WHERE meta_key = '_parfume_price' 
                    AND CAST(meta_value AS DECIMAL(10,2)) BETWEEN %f AND %f
                )", $price_min, $price_max);
            }
        }
        
        return $where;
    }
    
    public function template_loader($template) {
        if (is_single() && get_post_type() === 'parfume') {
            $plugin_template = PARFUME_REVIEWS_PLUGIN_DIR . 'templates/single-parfume.php';
            if (file_exists($plugin_template)) {
                return $plugin_template;
            }
        }
        
        if (is_single() && get_post_type() === 'parfume_blog') {
            $plugin_template = PARFUME_REVIEWS_PLUGIN_DIR . 'templates/single-parfume-blog.php';
            if (file_exists($plugin_template)) {
                return $plugin_template;
            }
        }
        
        if (is_post_type_archive('parfume')) {
            $plugin_template = PARFUME_REVIEWS_PLUGIN_DIR . 'templates/archive-parfume.php';
            if (file_exists($plugin_template)) {
                return $plugin_template;
            }
        }
        
        // Handle taxonomy templates
        $taxonomy_templates = array(
            'marki' => 'taxonomy-marki.php',
            'notes' => 'taxonomy-notes.php',
            'perfumer' => 'taxonomy-perfumer.php'
        );
        
        foreach ($taxonomy_templates as $taxonomy => $template_file) {
            if (is_tax($taxonomy)) {
                $plugin_template = PARFUME_REVIEWS_PLUGIN_DIR . 'templates/' . $template_file;
                if (file_exists($plugin_template)) {
                    return $plugin_template;
                }
            }
        }
        
        // Generic taxonomy templates
        if (is_tax(array('gender', 'aroma_type', 'season', 'intensity'))) {
            $queried_object = get_queried_object();
            if ($queried_object && isset($queried_object->taxonomy)) {
                $plugin_template = PARFUME_REVIEWS_PLUGIN_DIR . 'templates/taxonomy-' . $queried_object->taxonomy . '.php';
                if (file_exists($plugin_template)) {
                    return $plugin_template;
                }
            }
        }
        
        return $template;
    }
    
    public function enqueue_scripts() {
        if (is_singular('parfume') || is_post_type_archive('parfume') || is_tax(array('marki', 'gender', 'aroma_type', 'season', 'intensity', 'notes', 'perfumer'))) {
            
            // Main frontend CSS
            wp_enqueue_style(
                'parfume-reviews-frontend',
                PARFUME_REVIEWS_PLUGIN_URL . 'assets/css/frontend.css',
                array(),
                PARFUME_REVIEWS_VERSION
            );
            
            // Single parfume specific CSS
            if (is_singular('parfume')) {
                wp_enqueue_style(
                    'parfume-reviews-single',
                    PARFUME_REVIEWS_PLUGIN_URL . 'assets/css/single-parfume.css',
                    array('parfume-reviews-frontend'),
                    PARFUME_REVIEWS_VERSION
                );
            }
            
            wp_enqueue_script(
                'parfume-reviews-frontend',
                PARFUME_REVIEWS_PLUGIN_URL . 'assets/js/frontend.js',
                array('jquery'),
                PARFUME_REVIEWS_VERSION,
                true
            );
            
            wp_localize_script('parfume-reviews-frontend', 'parfumeReviews', array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('parfume-reviews-nonce'),
                'strings' => array(
                    'loading' => __('Зареждане...', 'parfume-reviews'),
                    'error' => __('Възникна грешка', 'parfume-reviews'),
                    'success' => __('Успех', 'parfume-reviews'),
                ),
            ));
        }
    }
    
    public function admin_enqueue_scripts($hook) {
        global $post_type;
        
        if (($hook === 'post-new.php' || $hook === 'post.php') && $post_type === 'parfume') {
            wp_enqueue_style(
                'parfume-reviews-admin',
                PARFUME_REVIEWS_PLUGIN_URL . 'assets/css/admin.css',
                array(),
                PARFUME_REVIEWS_VERSION
            );
            
            wp_enqueue_script(
                'parfume-reviews-admin',
                PARFUME_REVIEWS_PLUGIN_URL . 'assets/js/admin.js',
                array('jquery', 'jquery-ui-sortable'),
                PARFUME_REVIEWS_VERSION,
                true
            );
            
            wp_localize_script('parfume-reviews-admin', 'parfumeReviewsAdmin', array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('parfume-reviews-admin-nonce'),
                'strings' => array(
                    'confirmRemove' => __('Сигурни ли сте, че искате да премахнете този елемент?', 'parfume-reviews'),
                    'addNew' => __('Добави нов', 'parfume-reviews'),
                ),
            ));
        }
    }
    
    public function add_meta_boxes() {
        add_meta_box(
            'parfume_details',
            __('Детайли за парфюма', 'parfume-reviews'),
            array($this, 'render_details_meta_box'),
            'parfume',
            'normal',
            'high'
        );
        
        add_meta_box(
            'parfume_rating',
            __('Рейтинг', 'parfume-reviews'),
            array($this, 'render_rating_meta_box'),
            'parfume',
            'side',
            'default'
        );
        
        add_meta_box(
            'parfume_aroma_chart',
            __('Ароматна диаграма', 'parfume-reviews'),
            array($this, 'render_aroma_chart_meta_box'),
            'parfume',
            'normal',
            'default'
        );
        
        add_meta_box(
            'parfume_pros_cons',
            __('Плюсове и Минуси', 'parfume-reviews'),
            array($this, 'render_pros_cons_meta_box'),
            'parfume',
            'normal',
            'default'
        );
        
        add_meta_box(
            'parfume_stores',
            __('Магазини', 'parfume-reviews'),
            array($this, 'render_stores_meta_box'),
            'parfume',
            'normal',
            'default'
        );
    }
    
    public function render_details_meta_box($post) {
        wp_nonce_field('parfume_details_nonce', 'parfume_details_nonce');
        
        $fields = array(
            'parfume_gender' => __('Пол', 'parfume-reviews'),
            'parfume_release_year' => __('Година на издаване', 'parfume-reviews'),
            'parfume_longevity' => __('Издръжливост', 'parfume-reviews'),
            'parfume_sillage' => __('Projection', 'parfume-reviews'),
            'parfume_bottle_size' => __('Размер на бутилката', 'parfume-reviews'),
        );
        
        echo '<table class="form-table"><tbody>';
        foreach ($fields as $field => $label) {
            $value = get_post_meta($post->ID, '_' . $field, true);
            echo '<tr><th scope="row"><label for="' . $field . '">' . $label . '</label></th>';
            echo '<td><input type="text" id="' . $field . '" name="' . $field . '" value="' . esc_attr($value) . '" class="regular-text" /></td></tr>';
        }
        echo '</tbody></table>';
    }
    
    public function render_rating_meta_box($post) {
        wp_nonce_field('parfume_rating_nonce', 'parfume_rating_nonce');
        
        $rating = get_post_meta($post->ID, '_parfume_rating', true);
        
        echo '<p><label for="parfume_rating">' . __('Рейтинг (0-5)', 'parfume-reviews') . '</label></p>';
        echo '<input type="number" id="parfume_rating" name="parfume_rating" value="' . esc_attr($rating) . '" min="0" max="5" step="0.1" style="width: 100%;" />';
        
        // Rating preview
        echo '<div class="rating-preview" style="margin-top: 10px;">';
        echo '<p><strong>' . __('Преглед:', 'parfume-reviews') . '</strong></p>';
        echo '<div id="rating-stars-preview">';
        for ($i = 1; $i <= 5; $i++) {
            echo '<span class="star">★</span>';
        }
        echo '</div>';
        echo '</div>';
        
        // Add JavaScript for live preview
        ?>
        <script>
        jQuery(document).ready(function($) {
            function updateRatingPreview() {
                var rating = parseFloat($('#parfume_rating').val()) || 0;
                var stars = $('#rating-stars-preview .star');
                
                stars.each(function(index) {
                    var $star = $(this);
                    if (index < Math.floor(rating)) {
                        $star.removeClass('half').addClass('filled');
                    } else if (index < rating) {
                        $star.removeClass('filled').addClass('half');
                    } else {
                        $star.removeClass('filled half');
                    }
                });
            }
            
            $('#parfume_rating').on('input', updateRatingPreview);
            updateRatingPreview(); // Initial load
        });
        </script>
        <?php
    }
    
    public function render_aroma_chart_meta_box($post) {
        wp_nonce_field('parfume_aroma_chart_nonce', 'parfume_aroma_chart_nonce');
        
        $fields = array(
            'parfume_freshness' => __('Свежест', 'parfume-reviews'),
            'parfume_sweetness' => __('Сладост', 'parfume-reviews'),
            'parfume_intensity' => __('Интензивност', 'parfume-reviews'),
            'parfume_warmth' => __('Топлина', 'parfume-reviews'),
        );
        
        echo '<table class="form-table"><tbody>';
        foreach ($fields as $field => $label) {
            $value = get_post_meta($post->ID, '_' . $field, true);
            echo '<tr><th scope="row"><label for="' . $field . '">' . $label . ' (0-10)</label></th>';
            echo '<td><input type="range" id="' . $field . '" name="' . $field . '" value="' . esc_attr($value) . '" min="0" max="10" step="1" />';
            echo '<span class="range-value">' . esc_attr($value) . '</span></td></tr>';
        }
        echo '</tbody></table>';
        
        // Add JavaScript for live value display
        ?>
        <script>
        jQuery(document).ready(function($) {
            $('input[type="range"]').on('input', function() {
                $(this).siblings('.range-value').text($(this).val());
            });
        });
        </script>
        <?php
    }
    
    public function render_pros_cons_meta_box($post) {
        wp_nonce_field('parfume_pros_cons_nonce', 'parfume_pros_cons_nonce');
        
        $pros = get_post_meta($post->ID, '_parfume_pros', true);
        $cons = get_post_meta($post->ID, '_parfume_cons', true);
        
        echo '<div style="display: flex; gap: 20px;">';
        echo '<div style="flex: 1;">';
        echo '<h4>' . __('Плюсове', 'parfume-reviews') . '</h4>';
        echo '<textarea name="parfume_pros" rows="8" style="width: 100%;" placeholder="' . __('Един плюс на ред', 'parfume-reviews') . '">' . esc_textarea($pros) . '</textarea>';
        echo '</div>';
        echo '<div style="flex: 1;">';
        echo '<h4>' . __('Минуси', 'parfume-reviews') . '</h4>';
        echo '<textarea name="parfume_cons" rows="8" style="width: 100%;" placeholder="' . __('Един минус на ред', 'parfume-reviews') . '">' . esc_textarea($cons) . '</textarea>';
        echo '</div>';
        echo '</div>';
    }
    
    public function render_stores_meta_box($post) {
        wp_nonce_field('parfume_stores_nonce', 'parfume_stores_nonce');
        
        $stores = get_post_meta($post->ID, '_parfume_stores', true);
        $stores = !empty($stores) ? $stores : array();
        
        echo '<div id="parfume-stores-container">';
        
        if (!empty($stores)) {
            foreach ($stores as $index => $store) {
                $this->render_store_item($store, $index);
            }
        }
        
        echo '</div>';
        echo '<button type="button" id="add-store-btn" class="button button-secondary">' . __('Добави магазин', 'parfume-reviews') . '</button>';
        
        $this->render_stores_js(count($stores));
    }
    
    private function render_store_item($store, $index) {
        echo '<div class="store-item" style="border: 1px solid #ddd; padding: 15px; margin-bottom: 15px; background: #f9f9f9; border-radius: 5px;">';
        echo '<h4>' . __('Магазин', 'parfume-reviews') . ' ' . ($index + 1) . ' <button type="button" class="remove-store-btn button" style="float: right;">' . __('Премахни', 'parfume-reviews') . '</button></h4>';
        
        echo '<table class="form-table"><tbody>';
        
        // Store Name
        $name = isset($store['name']) ? $store['name'] : '';
        echo '<tr><th scope="row"><label>' . __('Име на магазин', 'parfume-reviews') . '</label></th>';
        echo '<td><input type="text" name="parfume_stores[' . $index . '][name]" value="' . esc_attr($name) . '" class="regular-text" /></td></tr>';
        
        // Store Logo
        $logo = isset($store['logo']) ? $store['logo'] : '';
        echo '<tr><th scope="row"><label>' . __('URL на лого', 'parfume-reviews') . '</label></th>';
        echo '<td><input type="text" name="parfume_stores[' . $index . '][logo]" value="' . esc_attr($logo) . '" class="regular-text" /></td></tr>';
        
        // Product URL
        $url = isset($store['url']) ? $store['url'] : '';
        echo '<tr><th scope="row"><label>' . __('URL на продукт', 'parfume-reviews') . '</label></th>';
        echo '<td><input type="text" name="parfume_stores[' . $index . '][url]" value="' . esc_attr($url) . '" class="regular-text" /></td></tr>';
        
        // Affiliate URL with options
        $affiliate_url = isset($store['affiliate_url']) ? $store['affiliate_url'] : '';
        $affiliate_rel = isset($store['affiliate_rel']) ? $store['affiliate_rel'] : 'nofollow';
        $affiliate_target = isset($store['affiliate_target']) ? $store['affiliate_target'] : '_blank';
        echo '<tr><th scope="row"><label>' . __('Affiliate URL', 'parfume-reviews') . '</label></th>';
        echo '<td>';
        echo '<input type="text" id="store_' . $index . '_affiliate_url" name="parfume_stores[' . $index . '][affiliate_url]" value="' . esc_attr($affiliate_url) . '" class="regular-text">';
        echo '<label style="margin-left: 10px;"><input type="checkbox" name="parfume_stores[' . $index . '][affiliate_rel]" value="nofollow"' . checked($affiliate_rel, 'nofollow', false) . '> nofollow</label>';
        echo '<label style="margin-left: 10px;"><input type="checkbox" name="parfume_stores[' . $index . '][affiliate_target]" value="_blank"' . checked($affiliate_target, '_blank', false) . '> _blank</label>';
        echo '</td></tr>';
        
        // CSS Class
        $affiliate_class = isset($store['affiliate_class']) ? $store['affiliate_class'] : '';
        echo '<tr><th scope="row"><label>' . __('CSS клас', 'parfume-reviews') . '</label></th>';
        echo '<td><input type="text" name="parfume_stores[' . $index . '][affiliate_class]" value="' . esc_attr($affiliate_class) . '" class="regular-text" /></td></tr>';
        
        // Anchor Text
        $affiliate_anchor = isset($store['affiliate_anchor']) ? $store['affiliate_anchor'] : '';
        echo '<tr><th scope="row"><label>' . __('Текст на линка', 'parfume-reviews') . '</label></th>';
        echo '<td><input type="text" name="parfume_stores[' . $index . '][affiliate_anchor]" value="' . esc_attr($affiliate_anchor) . '" class="regular-text" /></td></tr>';
        
        // Promo Code
        $promo_code = isset($store['promo_code']) ? $store['promo_code'] : '';
        echo '<tr><th scope="row"><label>' . __('Промо код', 'parfume-reviews') . '</label></th>';
        echo '<td><input type="text" name="parfume_stores[' . $index . '][promo_code]" value="' . esc_attr($promo_code) . '" class="regular-text" /></td></tr>';
        
        // Promo Text
        $promo_text = isset($store['promo_text']) ? $store['promo_text'] : '';
        echo '<tr><th scope="row"><label>' . __('Промо текст', 'parfume-reviews') . '</label></th>';
        echo '<td><input type="text" name="parfume_stores[' . $index . '][promo_text]" value="' . esc_attr($promo_text) . '" class="regular-text" /></td></tr>';
        
        // Price with update button
        $price = isset($store['price']) ? $store['price'] : '';
        echo '<tr><th scope="row"><label>' . __('Цена', 'parfume-reviews') . '</label></th>';
        echo '<td>';
        echo '<input type="text" name="parfume_stores[' . $index . '][price]" value="' . esc_attr($price) . '" class="regular-text" style="width: 70%;" />';
        echo '<button type="button" class="button update-price-btn" data-index="' . $index . '" style="margin-left: 10px;">' . __('Обнови цена', 'parfume-reviews') . '</button>';
        echo '</td></tr>';
        
        // Size with get sizes button
        $size = isset($store['size']) ? $store['size'] : '';
        echo '<tr><th scope="row"><label>' . __('Размер', 'parfume-reviews') . '</label></th>';
        echo '<td>';
        echo '<input type="text" name="parfume_stores[' . $index . '][size]" value="' . esc_attr($size) . '" class="regular-text" style="width: 70%;" />';
        echo '<button type="button" class="button get-sizes-btn" data-index="' . $index . '" style="margin-left: 10px;">' . __('Извлечи размери', 'parfume-reviews') . '</button>';
        echo '</td></tr>';
        
        // Availability
        $availability = isset($store['availability']) ? $store['availability'] : '';
        echo '<tr><th scope="row"><label>' . __('Наличност', 'parfume-reviews') . '</label></th>';
        echo '<td><input type="text" name="parfume_stores[' . $index . '][availability]" value="' . esc_attr($availability) . '" class="regular-text" /></td></tr>';
        
        // Shipping Cost
        $shipping_cost = isset($store['shipping_cost']) ? $store['shipping_cost'] : '';
        echo '<tr><th scope="row"><label>' . __('Цена на доставка', 'parfume-reviews') . '</label></th>';
        echo '<td><input type="text" name="parfume_stores[' . $index . '][shipping_cost]" value="' . esc_attr($shipping_cost) . '" class="regular-text" /></td></tr>';
        
        echo '</tbody></table>';
        echo '</div>';
    }
    
    private function render_stores_js($store_count) {
        ?>
        <script>
        jQuery(document).ready(function($) {
            var storeIndex = <?php echo $store_count; ?>;
            
            $('#add-store-btn').on('click', function() {
                var newStoreHtml = '<div class="store-item" style="border: 1px solid #ddd; padding: 15px; margin-bottom: 15px; background: #f9f9f9; border-radius: 5px;">' +
                    '<h4><?php echo __('Магазин', 'parfume-reviews'); ?> ' + (storeIndex + 1) + ' <button type="button" class="remove-store-btn button" style="float: right;"><?php echo __('Премахни', 'parfume-reviews'); ?></button></h4>' +
                    '<table class="form-table"><tbody>' +
                    '<tr><th scope="row"><label><?php echo __('Име на магазин', 'parfume-reviews'); ?></label></th>' +
                    '<td><input type="text" name="parfume_stores[' + storeIndex + '][name]" class="regular-text" /></td></tr>' +
                    '<tr><th scope="row"><label><?php echo __('URL на лого', 'parfume-reviews'); ?></label></th>' +
                    '<td><input type="text" name="parfume_stores[' + storeIndex + '][logo]" class="regular-text" /></td></tr>' +
                    '<tr><th scope="row"><label><?php echo __('URL на продукт', 'parfume-reviews'); ?></label></th>' +
                    '<td><input type="text" name="parfume_stores[' + storeIndex + '][url]" class="regular-text" /></td></tr>' +
                    '<tr><th scope="row"><label><?php echo __('Affiliate URL', 'parfume-reviews'); ?></label></th>' +
                    '<td><input type="text" id="store_' + storeIndex + '_affiliate_url" name="parfume_stores[' + storeIndex + '][affiliate_url]" class="regular-text">' +
                    '<label style="margin-left: 10px;"><input type="checkbox" name="parfume_stores[' + storeIndex + '][affiliate_rel]" value="nofollow" checked> nofollow</label>' +
                    '<label style="margin-left: 10px;"><input type="checkbox" name="parfume_stores[' + storeIndex + '][affiliate_target]" value="_blank" checked> _blank</label></td></tr>' +
                    '<tr><th scope="row"><label><?php echo __('CSS клас', 'parfume-reviews'); ?></label></th>' +
                    '<td><input type="text" name="parfume_stores[' + storeIndex + '][affiliate_class]" class="regular-text" /></td></tr>' +
                    '<tr><th scope="row"><label><?php echo __('Текст на линка', 'parfume-reviews'); ?></label></th>' +
                    '<td><input type="text" name="parfume_stores[' + storeIndex + '][affiliate_anchor]" class="regular-text" /></td></tr>' +
                    '<tr><th scope="row"><label><?php echo __('Промо код', 'parfume-reviews'); ?></label></th>' +
                    '<td><input type="text" name="parfume_stores[' + storeIndex + '][promo_code]" class="regular-text" /></td></tr>' +
                    '<tr><th scope="row"><label><?php echo __('Промо текст', 'parfume-reviews'); ?></label></th>' +
                    '<td><input type="text" name="parfume_stores[' + storeIndex + '][promo_text]" class="regular-text" /></td></tr>' +
                    '<tr><th scope="row"><label><?php echo __('Цена', 'parfume-reviews'); ?></label></th>' +
                    '<td><input type="text" name="parfume_stores[' + storeIndex + '][price]" class="regular-text" style="width: 70%;" />' +
                    '<button type="button" class="button update-price-btn" data-index="' + storeIndex + '" style="margin-left: 10px;"><?php echo __('Обнови цена', 'parfume-reviews'); ?></button></td></tr>' +
                    '<tr><th scope="row"><label><?php echo __('Размер', 'parfume-reviews'); ?></label></th>' +
                    '<td><input type="text" name="parfume_stores[' + storeIndex + '][size]" class="regular-text" style="width: 70%;" />' +
                    '<button type="button" class="button get-sizes-btn" data-index="' + storeIndex + '" style="margin-left: 10px;"><?php echo __('Извлечи размери', 'parfume-reviews'); ?></button></td></tr>' +
                    '<tr><th scope="row"><label><?php echo __('Наличност', 'parfume-reviews'); ?></label></th>' +
                    '<td><input type="text" name="parfume_stores[' + storeIndex + '][availability]" class="regular-text" /></td></tr>' +
                    '<tr><th scope="row"><label><?php echo __('Цена на доставка', 'parfume-reviews'); ?></label></th>' +
                    '<td><input type="text" name="parfume_stores[' + storeIndex + '][shipping_cost]" class="regular-text" /></td></tr>' +
                    '</tbody></table></div>';
                
                $('#parfume-stores-container').append(newStoreHtml);
                storeIndex++;
            });
            
            $(document).on('click', '.remove-store-btn', function() {
                if (confirm('<?php echo __('Сигурни ли сте, че искате да премахнете този магазин?', 'parfume-reviews'); ?>')) {
                    $(this).closest('.store-item').remove();
                }
            });
            
            // Handle price update button
            $(document).on('click', '.update-price-btn', function() {
                var button = $(this);
                var index = button.data('index');
                var storeNameInput = $('input[name="parfume_stores[' + index + '][name]"]');
                var priceInput = $('input[name="parfume_stores[' + index + '][price]"]');
                var storeName = storeNameInput.val();
                
                if (!storeName) {
                    alert('<?php echo __('Моля въведете име на магазин първо.', 'parfume-reviews'); ?>');
                    return;
                }
                
                button.prop('disabled', true).text('<?php echo __('Обновява...', 'parfume-reviews'); ?>');
                
                $.ajax({
                    url: parfumeReviewsAdmin.ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'update_store_price',
                        nonce: parfumeReviewsAdmin.nonce,
                        post_id: $('#post_ID').val(),
                        store_index: index,
                        store_name: storeName
                    },
                    success: function(response) {
                        if (response.success && response.data.price) {
                            priceInput.val(response.data.price);
                            alert('<?php echo __('Цената е обновена успешно!', 'parfume-reviews'); ?>');
                        } else {
                            alert('<?php echo __('Грешка при обновяване на цената.', 'parfume-reviews'); ?>');
                        }
                    },
                    error: function() {
                        alert('<?php echo __('Грешка при свързване със сървъра.', 'parfume-reviews'); ?>');
                    },
                    complete: function() {
                        button.prop('disabled', false).text('<?php echo __('Обнови цена', 'parfume-reviews'); ?>');
                    }
                });
            });
            
            // Handle get sizes button
            $(document).on('click', '.get-sizes-btn', function() {
                var button = $(this);
                var index = button.data('index');
                var storeNameInput = $('input[name="parfume_stores[' + index + '][name]"]');
                var sizeInput = $('input[name="parfume_stores[' + index + '][size]"]');
                var storeName = storeNameInput.val();
                
                if (!storeName) {
                    alert('<?php echo __('Моля въведете име на магазин първо.', 'parfume-reviews'); ?>');
                    return;
                }
                
                button.prop('disabled', true).text('<?php echo __('Извлича...', 'parfume-reviews'); ?>');
                
                $.ajax({
                    url: parfumeReviewsAdmin.ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'get_store_sizes',
                        nonce: parfumeReviewsAdmin.nonce,
                        store: storeName
                    },
                    success: function(response) {
                        if (response.success && response.data.length > 0) {
                            var sizes = response.data.join(', ');
                            sizeInput.val(sizes);
                            alert('<?php echo __('Размерите са извлечени успешно!', 'parfume-reviews'); ?>');
                        } else {
                            alert('<?php echo __('Не са намерени размери за този магазин.', 'parfume-reviews'); ?>');
                        }
                    },
                    error: function() {
                        alert('<?php echo __('Грешка при свързване със сървъра.', 'parfume-reviews'); ?>');
                    },
                    complete: function() {
                        button.prop('disabled', false).text('<?php echo __('Извлечи размери', 'parfume-reviews'); ?>');
                    }
                });
            });
        });
        </script>
        <?php
    }
    
    public function save_meta_boxes($post_id) {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (get_post_type($post_id) !== 'parfume') return;
        if (!current_user_can('edit_post', $post_id)) return;
        
        // Save details
        if (isset($_POST['parfume_details_nonce']) && wp_verify_nonce($_POST['parfume_details_nonce'], 'parfume_details_nonce')) {
            $fields = array('parfume_gender', 'parfume_release_year', 'parfume_longevity', 'parfume_sillage', 'parfume_bottle_size');
            
            foreach ($fields as $field) {
                if (isset($_POST[$field])) {
                    $value = sanitize_text_field($_POST[$field]);
                    update_post_meta($post_id, '_' . $field, $value);
                }
            }
        }
        
        // Save rating
        if (isset($_POST['parfume_rating_nonce']) && wp_verify_nonce($_POST['parfume_rating_nonce'], 'parfume_rating_nonce')) {
            if (isset($_POST['parfume_rating'])) {
                $rating = floatval($_POST['parfume_rating']);
                $rating = max(0, min(5, $rating)); // Clamp between 0 and 5
                update_post_meta($post_id, '_parfume_rating', $rating);
            }
        }
        
        // Save aroma chart
        if (isset($_POST['parfume_aroma_chart_nonce']) && wp_verify_nonce($_POST['parfume_aroma_chart_nonce'], 'parfume_aroma_chart_nonce')) {
            $chart_fields = array('parfume_freshness', 'parfume_sweetness', 'parfume_intensity', 'parfume_warmth');
            
            foreach ($chart_fields as $field) {
                if (isset($_POST[$field])) {
                    $value = intval($_POST[$field]);
                    $value = max(0, min(10, $value)); // Clamp between 0 and 10
                    update_post_meta($post_id, '_' . $field, $value);
                }
            }
        }
        
        // Save pros and cons
        if (isset($_POST['parfume_pros_cons_nonce']) && wp_verify_nonce($_POST['parfume_pros_cons_nonce'], 'parfume_pros_cons_nonce')) {
            if (isset($_POST['parfume_pros'])) {
                $pros = sanitize_textarea_field($_POST['parfume_pros']);
                update_post_meta($post_id, '_parfume_pros', $pros);
            }
            
            if (isset($_POST['parfume_cons'])) {
                $cons = sanitize_textarea_field($_POST['parfume_cons']);
                update_post_meta($post_id, '_parfume_cons', $cons);
            }
        }
        
        // Save stores
        if (isset($_POST['parfume_stores_nonce']) && wp_verify_nonce($_POST['parfume_stores_nonce'], 'parfume_stores_nonce')) {
            if (isset($_POST['parfume_stores']) && is_array($_POST['parfume_stores'])) {
                $stores = array();
                
                foreach ($_POST['parfume_stores'] as $store_data) {
                    if (!empty($store_data['name'])) {
                        $store = array();
                        $allowed_fields = array('name', 'logo', 'url', 'affiliate_url', 'affiliate_class', 'affiliate_rel', 'affiliate_target', 'affiliate_anchor', 'promo_code', 'promo_text', 'price', 'size', 'availability', 'shipping_cost');
                        
                        foreach ($allowed_fields as $field) {
                            $store[$field] = isset($store_data[$field]) ? sanitize_text_field($store_data[$field]) : '';
                        }
                        
                        $store['last_updated'] = current_time('mysql');
                        $stores[] = $store;
                    }
                }
                
                update_post_meta($post_id, '_parfume_stores', $stores);
            } else {
                delete_post_meta($post_id, '_parfume_stores');
            }
        }
    }
    
    // AJAX handlers
    public function ajax_update_store_price() {
        check_ajax_referer('parfume-reviews-admin-nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_die(__('Нямате права за тази операция.', 'parfume-reviews'));
        }
        
        $post_id = intval($_POST['post_id']);
        $store_index = intval($_POST['store_index']);
        $store_name = sanitize_text_field($_POST['store_name']);
        
        // Mock price update - in real implementation, this would connect to store APIs
        $mock_prices = array(
            'parfium' => '89.90 лв.',
            'douglas' => '92.50 лв.',
            'notino' => '87.00 лв.',
            'makeup' => '85.90 лв.',
            'strawberrynet' => '79.99 лв.',
        );
        
        $store_key = strtolower($store_name);
        $new_price = isset($mock_prices[$store_key]) ? $mock_prices[$store_key] : (rand(70, 120) . '.90 лв.');
        
        $stores = get_post_meta($post_id, '_parfume_stores', true);
        
        if (is_array($stores) && isset($stores[$store_index])) {
            $stores[$store_index]['price'] = $new_price;
            $stores[$store_index]['last_updated'] = current_time('mysql');
            update_post_meta($post_id, '_parfume_stores', $stores);
            
            wp_send_json_success(array(
                'message' => __('Цената е обновена успешно.', 'parfume-reviews'),
                'price' => $new_price
            ));
        } else {
            wp_send_json_error(__('Магазинът не е намерен.', 'parfume-reviews'));
        }
    }
    
    public function ajax_get_store_sizes() {
        check_ajax_referer('parfume-reviews-nonce', 'nonce');
        
        $store_name = sanitize_text_field($_POST['store']);
        
        // This would typically connect to store APIs to get available sizes
        // For now, return mock data
        $sizes = array(
            'parfium' => array('30ml', '50ml', '100ml'),
            'douglas' => array('30ml', '50ml', '75ml', '100ml'),
            'notino' => array('30ml', '50ml', '100ml', '150ml'),
        );
        
        $store_sizes = isset($sizes[strtolower($store_name)]) ? $sizes[strtolower($store_name)] : array('50ml', '100ml');
        
        wp_send_json_success($store_sizes);
    }
    
    public function debug_current_request() {
        if (defined('WP_DEBUG') && WP_DEBUG && is_admin()) {
            global $wp_query;
            
            if (is_post_type_archive('parfume') || is_tax(array('marki', 'gender', 'aroma_type', 'season', 'intensity', 'notes', 'perfumer'))) {
                error_log("Parfume Query Debug: " . print_r($wp_query->query_vars, true));
            }
        }
    }
    
    // Shortcode handlers
    public function all_brands_archive_shortcode($atts) {
        $atts = shortcode_atts(array(
            'columns' => 4,
            'show_count' => true,
        ), $atts);
        
        $terms = get_terms(array(
            'taxonomy' => 'marki',
            'hide_empty' => false,
        ));
        
        if (empty($terms) || is_wp_error($terms)) {
            return '<p>' . __('Няма намерени марки.', 'parfume-reviews') . '</p>';
        }
        
        $output = '<div class="brands-archive-grid columns-' . intval($atts['columns']) . '">';
        
        foreach ($terms as $term) {
            $output .= '<div class="brand-item">';
            $output .= '<a href="' . get_term_link($term) . '">';
            $output .= '<h3>' . esc_html($term->name) . '</h3>';
            
            if ($atts['show_count']) {
                $output .= '<span class="count">(' . $term->count . ')</span>';
            }
            
            $output .= '</a>';
            $output .= '</div>';
        }
        
        $output .= '</div>';
        
        return $output;
    }
    
    public function all_notes_archive_shortcode($atts) {
        $atts = shortcode_atts(array(
            'columns' => 6,
            'show_count' => true,
        ), $atts);
        
        $terms = get_terms(array(
            'taxonomy' => 'notes',
            'hide_empty' => false,
        ));
        
        if (empty($terms) || is_wp_error($terms)) {
            return '<p>' . __('Няма намерени нотки.', 'parfume-reviews') . '</p>';
        }
        
        $output = '<div class="notes-archive-grid columns-' . intval($atts['columns']) . '">';
        
        foreach ($terms as $term) {
            $output .= '<div class="note-item">';
            $output .= '<a href="' . get_term_link($term) . '">';
            $output .= '<span class="note-name">' . esc_html($term->name) . '</span>';
            
            if ($atts['show_count']) {
                $output .= '<span class="count">(' . $term->count . ')</span>';
            }
            
            $output .= '</a>';
            $output .= '</div>';
        }
        
        $output .= '</div>';
        
        return $output;
    }
    
    public function all_perfumers_archive_shortcode($atts) {
        $atts = shortcode_atts(array(
            'columns' => 3,
            'show_count' => true,
        ), $atts);
        
        $terms = get_terms(array(
            'taxonomy' => 'perfumer',
            'hide_empty' => false,
        ));
        
        if (empty($terms) || is_wp_error($terms)) {
            return '<p>' . __('Няма намерени парфюмери.', 'parfume-reviews') . '</p>';
        }
        
        $output = '<div class="perfumers-archive-grid columns-' . intval($atts['columns']) . '">';
        
        foreach ($terms as $term) {
            $output .= '<div class="perfumer-item">';
            $output .= '<a href="' . get_term_link($term) . '">';
            $output .= '<h3>' . esc_html($term->name) . '</h3>';
            
            if ($atts['show_count']) {
                $output .= '<span class="count">(' . $term->count . ' ' . __('парфюма', 'parfume-reviews') . ')</span>';
            }
            
            $output .= '</a>';
            $output .= '</div>';
        }
        
        $output .= '</div>';
        
        return $output;
    }
}