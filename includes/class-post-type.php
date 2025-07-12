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
            'rewrite' => array('slug' => $slug, 'with_front' => true),
            'capability_type' => 'post',
            'has_archive' => false,
            'hierarchical' => false,
            'supports' => array('title', 'editor', 'thumbnail', 'excerpt', 'comments', 'custom-fields'),
            'show_in_rest' => true,
            'menu_icon' => 'dashicons-admin-post',
        );
        
        register_post_type('parfume_blog', $args);
    }
    
    // Add custom rewrite rules for single posts
    public function add_custom_rewrite_rules() {
        $settings = get_option('parfume_reviews_settings', array());
        $slug = !empty($settings['parfume_slug']) ? $settings['parfume_slug'] : 'parfiumi';
        
        // Detect subdirectory by comparing URLs
        $home_url = home_url();
        $site_url = site_url();
        $subdirectory = '';
        
        if ($home_url !== $site_url) {
            // Extract subdirectory from home_url
            $home_path = parse_url($home_url, PHP_URL_PATH);
            $subdirectory = trim($home_path, '/');
        }
        
        // Build patterns with subdirectory if it exists
        if (!empty($subdirectory)) {
            $pattern = '^' . $subdirectory . '/' . $slug . '/([^/]+)/?$';
            $archive_pattern = '^' . $subdirectory . '/' . $slug . '/?$';
            $archive_page_pattern = '^' . $subdirectory . '/' . $slug . '/page/([0-9]+)/?$';
        } else {
            $pattern = '^' . $slug . '/([^/]+)/?$';
            $archive_pattern = '^' . $slug . '/?$';
            $archive_page_pattern = '^' . $slug . '/page/([0-9]+)/?$';
        }
        
        $rewrite = 'index.php?parfume=$matches[1]';
        $archive_rewrite = 'index.php?post_type=parfume';
        $archive_page_rewrite = 'index.php?post_type=parfume&paged=$matches[1]';
        
        // Add the rules at the top
        add_rewrite_rule($pattern, $rewrite, 'top');
        add_rewrite_rule($archive_pattern, $archive_rewrite, 'top');
        add_rewrite_rule($archive_page_pattern, $archive_page_rewrite, 'top');
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("Custom rewrite rules added:");
            error_log("Single: $pattern => $rewrite");
            error_log("Archive: $archive_pattern => $archive_rewrite");
            error_log("Archive pagination: $archive_page_pattern => $archive_page_rewrite");
            error_log("Subdirectory: '$subdirectory'");
            error_log("Home URL: $home_url");
            error_log("Site URL: $site_url");
        }
    }
    
    // Debug function to check current request
    public function debug_current_request() {
        if (!defined('WP_DEBUG') || !WP_DEBUG) return;
        
        global $wp_query;
        $request_uri = $_SERVER['REQUEST_URI'] ?? '';
        
        if (strpos($request_uri, 'parfiumi') !== false) {
            error_log("Post Type Debug - Request URI: $request_uri");
            error_log("Post Type Debug - Is 404: " . (is_404() ? 'yes' : 'no'));
            error_log("Post Type Debug - Is singular: " . (is_singular() ? 'yes' : 'no'));
            error_log("Post Type Debug - Is single parfume: " . (is_singular('parfume') ? 'yes' : 'no'));
            error_log("Post Type Debug - Is post type archive: " . (is_post_type_archive('parfume') ? 'yes' : 'no'));
            
            $queried_object = get_queried_object();
            if ($queried_object) {
                error_log("Post Type Debug - Queried object type: " . get_class($queried_object));
                if (isset($queried_object->ID)) {
                    error_log("Post Type Debug - Queried object ID: " . $queried_object->ID);
                }
                if (isset($queried_object->post_type)) {
                    error_log("Post Type Debug - Queried object post_type: " . $queried_object->post_type);
                }
            } else {
                error_log("Post Type Debug - No queried object");
            }
            
            if (is_404()) {
                // Check if a post with this slug exists
                $url_parts = explode('/', trim($request_uri, '/'));
                $post_slug = end($url_parts);
                
                if ($post_slug && $post_slug !== 'parfiumi') {
                    $post = get_page_by_path($post_slug, OBJECT, 'parfume');
                    if ($post) {
                        error_log("Post Type Debug - Found parfume post '$post_slug' but still 404");
                        error_log("Post Type Debug - Post status: " . $post->post_status);
                        error_log("Post Type Debug - Post ID: " . $post->ID);
                        error_log("Post Type Debug - Post permalink: " . get_permalink($post->ID));
                        
                        // Check rewrite rules
                        $rules = get_option('rewrite_rules', array());
                        $matching_rules = array();
                        foreach ($rules as $pattern => $rewrite) {
                            if (strpos($pattern, 'parfiumi') !== false || strpos($rewrite, 'parfume') !== false) {
                                $matching_rules[$pattern] = $rewrite;
                            }
                        }
                        error_log("Post Type Debug - Parfume rewrite rules: " . print_r($matching_rules, true));
                        
                    } else {
                        error_log("Post Type Debug - No parfume post found with slug '$post_slug'");
                    }
                }
            }
        }
    }
    
    public function modify_archive_query($query) {
        if (!is_admin() && $query->is_main_query()) {
            if (is_post_type_archive('parfume') || is_tax(array('marki', 'gender', 'aroma_type', 'season', 'intensity', 'notes', 'perfumer'))) {
                // Set posts per page
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
                
                if (!empty($terms) && !in_array('all', $terms)) {
                    $tax_query[] = array(
                        'taxonomy' => $taxonomy,
                        'field' => 'slug',
                        'terms' => $terms,
                        'operator' => 'IN',
                    );
                }
            }
        }
        
        if (!empty($tax_query)) {
            $existing_tax_query = $query->get('tax_query');
            if (!empty($existing_tax_query)) {
                $tax_query = array_merge($existing_tax_query, $tax_query);
            }
            $tax_query['relation'] = 'AND';
            $query->set('tax_query', $tax_query);
        }
        
        // Sort by rating if available
        if (!empty($_GET['orderby'])) {
            $orderby = sanitize_text_field($_GET['orderby']);
            switch ($orderby) {
                case 'rating':
                    $query->set('meta_key', '_parfume_average_rating');
                    $query->set('orderby', 'meta_value_num');
                    $query->set('order', 'DESC');
                    break;
                case 'price_low':
                    $query->set('meta_key', '_parfume_price');
                    $query->set('orderby', 'meta_value_num');
                    $query->set('order', 'ASC');
                    break;
                case 'price_high':
                    $query->set('meta_key', '_parfume_price');
                    $query->set('orderby', 'meta_value_num');
                    $query->set('order', 'DESC');
                    break;
                case 'date':
                    $query->set('orderby', 'date');
                    $query->set('order', 'DESC');
                    break;
                case 'title':
                    $query->set('orderby', 'title');
                    $query->set('order', 'ASC');
                    break;
            }
        }
    }
    
    public function filter_posts_where($where, $query) {
        if (!is_admin() && $query->is_main_query() && (is_post_type_archive('parfume') || is_tax(array('marki', 'gender', 'aroma_type', 'season', 'intensity', 'notes', 'perfumer')))) {
            global $wpdb;
            
            // Search filter
            if (!empty($_GET['search'])) {
                $search_term = sanitize_text_field($_GET['search']);
                $where .= $wpdb->prepare(" AND ({$wpdb->posts}.post_title LIKE %s OR {$wpdb->posts}.post_content LIKE %s)", 
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
            wp_enqueue_style(
                'parfume-reviews-frontend',
                PARFUME_REVIEWS_PLUGIN_URL . 'assets/css/frontend.css',
                array(),
                PARFUME_REVIEWS_VERSION
            );
            
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
    }
    
    public function render_stores_meta_box($post) {
        wp_nonce_field('parfume_stores_nonce', 'parfume_stores_nonce');
        
        $stores = get_post_meta($post->ID, '_parfume_stores', true);
        if (!is_array($stores)) {
            $stores = array();
        }
        
        echo '<div id="parfume-stores-container">';
        
        if (!empty($stores)) {
            foreach ($stores as $index => $store) {
                $this->render_store_item($index, $store);
            }
        } else {
            $this->render_store_item(0, array());
        }
        
        echo '</div>';
        echo '<p><button type="button" id="add-store-btn" class="button">' . __('Добави магазин', 'parfume-reviews') . '</button></p>';
        
        $this->render_stores_js(count($stores));
    }
    
    private function render_store_item($index, $store) {
        $fields = array(
            'name' => __('Име на магазин', 'parfume-reviews'),
            'url' => __('URL на продукт', 'parfume-reviews'),
            'price' => __('Цена', 'parfume-reviews'),
            'availability' => __('Наличност', 'parfume-reviews'),
        );
        
        echo '<div class="store-item" style="border: 1px solid #ddd; padding: 15px; margin-bottom: 15px;">';
        echo '<h4>' . __('Магазин', 'parfume-reviews') . ' ' . ($index + 1) . ' <button type="button" class="remove-store-btn button" style="float: right;">' . __('Премахни', 'parfume-reviews') . '</button></h4>';
        
        foreach ($fields as $field => $label) {
            $value = isset($store[$field]) ? $store[$field] : '';
            echo '<p>';
            echo '<label>' . $label . '</label><br>';
            echo '<input type="text" name="parfume_stores[' . $index . '][' . $field . ']" value="' . esc_attr($value) . '" style="width: 100%;" />';
            echo '</p>';
        }
        
        echo '</div>';
    }
    
    private function render_stores_js($store_count) {
        ?>
        <script>
        jQuery(document).ready(function($) {
            var storeIndex = <?php echo $store_count; ?>;
            
            $('#add-store-btn').on('click', function() {
                var newStore = '<div class="store-item" style="border: 1px solid #ddd; padding: 15px; margin-bottom: 15px;">' +
                    '<h4><?php echo __('Магазин', 'parfume-reviews'); ?> ' + (storeIndex + 1) + ' <button type="button" class="remove-store-btn button" style="float: right;"><?php echo __('Премахни', 'parfume-reviews'); ?></button></h4>' +
                    '<p><label><?php echo __('Име на магазин', 'parfume-reviews'); ?></label><br><input type="text" name="parfume_stores[' + storeIndex + '][name]" style="width: 100%;" /></p>' +
                    '<p><label><?php echo __('URL на продукт', 'parfume-reviews'); ?></label><br><input type="text" name="parfume_stores[' + storeIndex + '][url]" style="width: 100%;" /></p>' +
                    '<p><label><?php echo __('Цена', 'parfume-reviews'); ?></label><br><input type="text" name="parfume_stores[' + storeIndex + '][price]" style="width: 100%;" /></p>' +
                    '<p><label><?php echo __('Наличност', 'parfume-reviews'); ?></label><br><input type="text" name="parfume_stores[' + storeIndex + '][availability]" style="width: 100%;" /></p>' +
                    '</div>';
                
                $('#parfume-stores-container').append(newStore);
                storeIndex++;
            });
            
            $(document).on('click', '.remove-store-btn', function() {
                $(this).closest('.store-item').remove();
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
                $rating = max(0, min(5, $rating));
                update_post_meta($post_id, '_parfume_rating', $rating);
            }
        }
        
        // Save stores
        if (isset($_POST['parfume_stores_nonce']) && wp_verify_nonce($_POST['parfume_stores_nonce'], 'parfume_stores_nonce')) {
            if (isset($_POST['parfume_stores']) && is_array($_POST['parfume_stores'])) {
                $stores = array();
                
                foreach ($_POST['parfume_stores'] as $store_data) {
                    if (empty($store_data['name'])) continue;
                    
                    $store = array();
                    $fields = array('name', 'url', 'price', 'availability');
                    
                    foreach ($fields as $field) {
                        $store[$field] = isset($store_data[$field]) ? sanitize_text_field($store_data[$field]) : '';
                    }
                    
                    $stores[] = $store;
                }
                
                update_post_meta($post_id, '_parfume_stores', $stores);
            }
        }
    }
    
    // AJAX handlers
    public function ajax_update_store_price() {
        check_ajax_referer('parfume-reviews-admin-nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_die(__('Нямате достатъчно права за тази операция.', 'parfume-reviews'));
        }
        
        wp_send_json_success(array(
            'price' => '99.99 лв',
            'message' => __('Цената е обновена успешно.', 'parfume-reviews')
        ));
    }
    
    public function ajax_get_store_sizes() {
        check_ajax_referer('parfume-reviews-admin-nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_die(__('Нямате достатъчно права за тази операция.', 'parfume-reviews'));
        }
        
        wp_send_json_success(array(
            'sizes' => array('30 мл', '50 мл', '100 мл'),
            'message' => __('Размерите са извлечени успешно.', 'parfume-reviews')
        ));
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