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
            'not_found_in_trash' => __('No parfumes found in Trash.', 'parfume-reviews'),
        );
        
        $args = array(
            'labels' => $labels,
            'public' => true,
            'publicly_queryable' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'query_var' => true,
            'rewrite' => array(
                'slug' => $slug,
                'with_front' => false,
                'feeds' => true,
                'pages' => true
            ),
            'capability_type' => 'post',
            'has_archive' => true,
            'hierarchical' => false,
            'menu_position' => 20,
            'menu_icon' => 'dashicons-store',
            'supports' => array('title', 'editor', 'thumbnail', 'excerpt', 'custom-fields', 'comments'),
            'show_in_rest' => true,
            'rest_base' => 'parfumes',
        );
        
        register_post_type('parfume', $args);
    }
    
    public function register_blog_post_type() {
        $settings = get_option('parfume_reviews_settings', array());
        $slug = !empty($settings['parfume_slug']) ? $settings['parfume_slug'] : 'parfiumi';
        
        $labels = array(
            'name' => __('Blog Posts', 'parfume-reviews'),
            'singular_name' => __('Blog Post', 'parfume-reviews'),
            'menu_name' => __('Blog', 'parfume-reviews'),
            'add_new' => __('Add New Post', 'parfume-reviews'),
            'add_new_item' => __('Add New Blog Post', 'parfume-reviews'),
            'edit_item' => __('Edit Blog Post', 'parfume-reviews'),
            'new_item' => __('New Blog Post', 'parfume-reviews'),
            'view_item' => __('View Blog Post', 'parfume-reviews'),
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
    
    // ПОПРАВЕНА ВЕРСИЯ - БЕЗ 404 ГРЕШКИ!
    public function modify_archive_query($query) {
        if (!is_admin() && $query->is_main_query()) {
            if (is_post_type_archive('parfume') || is_tax(array('marki', 'gender', 'aroma_type', 'season', 'intensity', 'notes', 'perfumer'))) {
                $settings = get_option('parfume_reviews_settings', array());
                $per_page = !empty($settings['archive_posts_per_page']) ? intval($settings['archive_posts_per_page']) : 12;
                $query->set('posts_per_page', $per_page);
                
                // Handle filtering and sorting
                $this->handle_query_filters($query);
                
                // Handle custom sorting
                $this->handle_query_sorting($query);
            }
        }
    }
    
    // ПОПРАВЕН МЕТОД ЗА ФИЛТРИ - БЕЗ 404!
    private function handle_query_filters($query) {
        // Проверяваме дали има филтърни параметри в URL
        if (empty($_GET)) {
            return;
        }
        
        // Дефинираме поддържаните таксономии
        $supported_taxonomies = array('gender', 'aroma_type', 'marki', 'season', 'intensity', 'notes', 'perfumer');
        
        $tax_query = array();
        $has_filters = false;
        
        foreach ($supported_taxonomies as $taxonomy) {
            if (!empty($_GET[$taxonomy])) {
                $has_filters = true;
                
                // Получаваме стойностите и ги декодираме правилно
                $raw_terms = $_GET[$taxonomy];
                $terms = is_array($raw_terms) ? $raw_terms : array($raw_terms);
                
                // Почистваме и декодираме термините
                $clean_terms = array();
                foreach ($terms as $term) {
                    // Декодираме URL encoding
                    $decoded_term = rawurldecode($term);
                    $decoded_term = sanitize_text_field($decoded_term);
                    
                    if (!empty($decoded_term)) {
                        $clean_terms[] = $decoded_term;
                    }
                }
                
                if (!empty($clean_terms)) {
                    $tax_query[] = array(
                        'taxonomy' => $taxonomy,
                        'field'    => 'slug',
                        'terms'    => $clean_terms,
                        'operator' => 'IN',
                    );
                }
            }
        }
        
        // Проверяваме за ценови филтри
        $meta_query = array();
        $has_meta_filters = false;
        
        if (!empty($_GET['min_price']) || !empty($_GET['max_price'])) {
            $min_price = !empty($_GET['min_price']) ? floatval($_GET['min_price']) : 0;
            $max_price = !empty($_GET['max_price']) ? floatval($_GET['max_price']) : 999999;
            
            if ($min_price > 0 || $max_price < 999999) {
                $has_meta_filters = true;
                $meta_query[] = array(
                    'key'     => '_parfume_min_price',
                    'value'   => array($min_price, $max_price),
                    'type'    => 'NUMERIC',
                    'compare' => 'BETWEEN',
                );
            }
        }
        
        // Проверяваме за рейтинг филтър
        if (!empty($_GET['min_rating'])) {
            $min_rating = floatval($_GET['min_rating']);
            if ($min_rating > 0) {
                $has_meta_filters = true;
                $meta_query[] = array(
                    'key'     => '_parfume_rating',
                    'value'   => $min_rating,
                    'type'    => 'NUMERIC',
                    'compare' => '>=',
                );
            }
        }
        
        // Прилагаме филтрите към query-то
        if ($has_filters && !empty($tax_query)) {
            if (count($tax_query) > 1) {
                $tax_query['relation'] = 'AND';
            }
            $query->set('tax_query', $tax_query);
        }
        
        if ($has_meta_filters && !empty($meta_query)) {
            if (count($meta_query) > 1) {
                $meta_query['relation'] = 'AND';
            }
            $query->set('meta_query', $meta_query);
        }
        
        // Debug информация
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Parfume Filter Debug: ' . print_r(array(
                'GET_params' => $_GET,
                'tax_query' => $tax_query,
                'meta_query' => $meta_query,
                'has_filters' => $has_filters,
                'has_meta_filters' => $has_meta_filters
            ), true));
        }
    }
    
    private function handle_query_sorting($query) {
        if (!empty($_GET['orderby'])) {
            $orderby = sanitize_text_field($_GET['orderby']);
            $order = !empty($_GET['order']) ? sanitize_text_field($_GET['order']) : 'DESC';
            
            switch ($orderby) {
                case 'title':
                    $query->set('orderby', 'title');
                    $query->set('order', $order);
                    break;
                    
                case 'date':
                    $query->set('orderby', 'date');
                    $query->set('order', $order);
                    break;
                    
                case 'rating':
                    $query->set('orderby', 'meta_value_num');
                    $query->set('meta_key', '_parfume_rating');
                    $query->set('order', $order);
                    break;
                    
                case 'price':
                    $query->set('orderby', 'meta_value_num');
                    $query->set('meta_key', '_parfume_min_price');
                    $query->set('order', $order);
                    break;
                    
                case 'popularity':
                    $query->set('orderby', 'meta_value_num');
                    $query->set('meta_key', '_parfume_views');
                    $query->set('order', $order);
                    break;
                    
                default:
                    // Default sorting
                    $query->set('orderby', 'date');
                    $query->set('order', 'DESC');
                    break;
            }
        }
    }
    
    public function filter_posts_where($where, $query) {
        if (!is_admin() && $query->is_main_query()) {
            if (is_post_type_archive('parfume') || is_tax(array('marki', 'gender', 'aroma_type', 'season', 'intensity', 'notes', 'perfumer'))) {
                // Add custom WHERE conditions if needed
            }
        }
        
        return $where;
    }
    
    // Debug метод за текущата заявка
    public function debug_current_request() {
        if (!current_user_can('manage_options') || !isset($_GET['debug_query'])) {
            return;
        }
        
        global $wp_query;
        
        echo '<div style="background: #f0f0f0; padding: 20px; margin: 20px; border: 1px solid #ddd;">';
        echo '<h3>Debug Information</h3>';
        echo '<strong>Current URL:</strong> ' . esc_url($_SERVER['REQUEST_URI']) . '<br>';
        echo '<strong>Query Vars:</strong><pre>' . print_r($wp_query->query_vars, true) . '</pre>';
        echo '<strong>GET Parameters:</strong><pre>' . print_r($_GET, true) . '</pre>';
        echo '<strong>Is 404:</strong> ' . (is_404() ? 'YES' : 'NO') . '<br>';
        echo '<strong>Post Type Archive:</strong> ' . (is_post_type_archive('parfume') ? 'YES' : 'NO') . '<br>';
        echo '<strong>Is Tax:</strong> ' . (is_tax() ? 'YES' : 'NO') . '<br>';
        echo '<strong>Found Posts:</strong> ' . $wp_query->found_posts . '<br>';
        echo '</div>';
    }
    
    // Helper методи за филтри
    public static function build_filter_url($filters = array(), $base_url = '') {
        if (empty($base_url)) {
            if (is_post_type_archive('parfume')) {
                $base_url = get_post_type_archive_link('parfume');
            } elseif (is_tax()) {
                $base_url = get_term_link(get_queried_object());
            } else {
                $base_url = home_url('/parfiumi/');
            }
        }
        
        if (!empty($filters)) {
            $base_url = add_query_arg($filters, $base_url);
        }
        
        return $base_url;
    }
    
    public static function get_active_filters() {
        $active_filters = array();
        $supported_taxonomies = array('gender', 'aroma_type', 'marki', 'season', 'intensity', 'notes', 'perfumer');
        
        foreach ($supported_taxonomies as $taxonomy) {
            if (!empty($_GET[$taxonomy])) {
                $terms = is_array($_GET[$taxonomy]) ? $_GET[$taxonomy] : array($_GET[$taxonomy]);
                foreach ($terms as $term_slug) {
                    $term_slug = rawurldecode(sanitize_text_field($term_slug));
                    $term = get_term_by('slug', $term_slug, $taxonomy);
                    if ($term) {
                        $active_filters[] = array(
                            'taxonomy' => $taxonomy,
                            'term_slug' => $term_slug,
                            'term_name' => $term->name,
                            'remove_url' => self::build_remove_filter_url($taxonomy, $term_slug)
                        );
                    }
                }
            }
        }
        
        return $active_filters;
    }
    
    private static function build_remove_filter_url($taxonomy, $term_slug) {
        $current_url = $_SERVER['REQUEST_URI'];
        $parsed_url = parse_url($current_url);
        
        if (!empty($parsed_url['query'])) {
            parse_str($parsed_url['query'], $query_params);
            
            if (isset($query_params[$taxonomy])) {
                if (is_array($query_params[$taxonomy])) {
                    $query_params[$taxonomy] = array_diff($query_params[$taxonomy], array($term_slug));
                    if (empty($query_params[$taxonomy])) {
                        unset($query_params[$taxonomy]);
                    }
                } else {
                    unset($query_params[$taxonomy]);
                }
            }
            
            $new_query = http_build_query($query_params);
            $new_url = $parsed_url['path'] . (!empty($new_query) ? '?' . $new_query : '');
            
            return home_url($new_url);
        }
        
        return $parsed_url['path'];
    }
    
    public function template_loader($template) {
        if (is_singular('parfume')) {
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
        
        // Taxonomy templates
        if (is_tax(array('marki', 'notes', 'perfumer'))) {
            $queried_object = get_queried_object();
            if ($queried_object && isset($queried_object->taxonomy)) {
                $template_files = array(
                    'taxonomy-' . $queried_object->taxonomy . '-' . $queried_object->slug . '.php',
                    'taxonomy-' . $queried_object->taxonomy . '.php',
                    'taxonomy.php'
                );
                
                foreach ($template_files as $template_file) {
                    $plugin_template = PARFUME_REVIEWS_PLUGIN_DIR . 'templates/' . $template_file;
                    if (file_exists($plugin_template)) {
                        return $plugin_template;
                    }
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
                array('jquery'),
                PARFUME_REVIEWS_VERSION,
                true
            );
            
            wp_localize_script('parfume-reviews-admin', 'parfumeAdmin', array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('parfume-admin-nonce'),
            ));
        }
    }
    
    public function add_meta_boxes() {
        add_meta_box(
            'parfume_details',
            __('Детайли за парфюма', 'parfume-reviews'),
            array($this, 'parfume_details_meta_box'),
            'parfume',
            'normal',
            'high'
        );
        
        add_meta_box(
            'parfume_stores',
            __('Магазини и цени', 'parfume-reviews'),
            array($this, 'parfume_stores_meta_box'),
            'parfume',
            'normal',
            'high'
        );
        
        add_meta_box(
            'parfume_notes',
            __('Ноти на парфюма', 'parfume-reviews'),
            array($this, 'parfume_notes_meta_box'),
            'parfume',
            'side',
            'default'
        );
    }
    
    public function parfume_details_meta_box($post) {
        wp_nonce_field('parfume_details_nonce', 'parfume_details_nonce');
        
        $fields = array(
            '_parfume_description' => __('Описание', 'parfume-reviews'),
            '_parfume_release_year' => __('Година на излизане', 'parfume-reviews'),
            '_parfume_rating' => __('Рейтинг', 'parfume-reviews'),
            '_parfume_longevity' => __('Трайност', 'parfume-reviews'),
            '_parfume_sillage' => __('Силаж', 'parfume-reviews'),
            '_parfume_bottle_size' => __('Размер на бутилката', 'parfume-reviews'),
        );
        
        echo '<table class="form-table">';
        foreach ($fields as $field => $label) {
            $value = get_post_meta($post->ID, $field, true);
            echo '<tr>';
            echo '<th scope="row"><label for="' . $field . '">' . $label . '</label></th>';
            echo '<td>';
            
            if ($field === '_parfume_description') {
                echo '<textarea id="' . $field . '" name="' . $field . '" rows="4" cols="50" class="large-text">' . esc_textarea($value) . '</textarea>';
            } elseif ($field === '_parfume_rating') {
                echo '<input type="number" id="' . $field . '" name="' . $field . '" value="' . esc_attr($value) . '" min="0" max="5" step="0.1" class="small-text" />';
                echo '<span class="description"> (0-5)</span>';
            } else {
                echo '<input type="text" id="' . $field . '" name="' . $field . '" value="' . esc_attr($value) . '" class="regular-text" />';
            }
            
            echo '</td>';
            echo '</tr>';
        }
        echo '</table>';
    }
    
    public function parfume_stores_meta_box($post) {
        wp_nonce_field('parfume_stores_nonce', 'parfume_stores_nonce');
        
        $stores = get_post_meta($post->ID, '_parfume_stores', true);
        if (!is_array($stores)) {
            $stores = array();
        }
        
        echo '<div id="parfume-stores-container">';
        
        foreach ($stores as $index => $store) {
            $this->render_store_item($index, $store);
        }
        
        echo '</div>';
        
        echo '<p><button type="button" id="add-store" class="button">' . __('Добави магазин', 'parfume-reviews') . '</button></p>';
        
        // JavaScript for adding/removing stores
        ?>
        <script>
        jQuery(document).ready(function($) {
            var storeIndex = <?php echo count($stores); ?>;
            
            $('#add-store').on('click', function() {
                var newStore = '<div class="store-item" data-index="' + storeIndex + '">' +
                    '<div class="store-header">' +
                    '<strong><?php echo __('Магазин', 'parfume-reviews'); ?> ' + (storeIndex + 1) + '</strong>' +
                    '<a href="#" class="remove-store" style="float: right;"><?php echo __('Премахни', 'parfume-reviews'); ?></a>' +
                    '</div>' +
                    '<table class="form-table">' +
                    '<tr><th scope="row"><label><?php echo __('Име на магазина', 'parfume-reviews'); ?></label></th>' +
                    '<td><input type="text" name="parfume_stores[' + storeIndex + '][name]" class="regular-text" /></td></tr>' +
                    '<tr><th scope="row"><label><?php echo __('URL на магазина', 'parfume-reviews'); ?></label></th>' +
                    '<td><input type="url" name="parfume_stores[' + storeIndex + '][url]" class="regular-text" /></td></tr>' +
                    '<tr><th scope="row"><label><?php echo __('Affiliate URL', 'parfume-reviews'); ?></label></th>' +
                    '<td><input type="url" name="parfume_stores[' + storeIndex + '][affiliate_url]" class="regular-text" /></td></tr>' +
                    '<tr><th scope="row"><label><?php echo __('Цена', 'parfume-reviews'); ?></label></th>' +
                    '<td><input type="text" name="parfume_stores[' + storeIndex + '][price]" class="regular-text" /></td></tr>' +
                    '<tr><th scope="row"><label><?php echo __('Размер', 'parfume-reviews'); ?></label></th>' +
                    '<td><input type="text" name="parfume_stores[' + storeIndex + '][size]" class="regular-text" /></td></tr>' +
                    '<tr><th scope="row"><label><?php echo __('Наличност', 'parfume-reviews'); ?></label></th>' +
                    '<td><select name="parfume_stores[' + storeIndex + '][availability]"><option value="in_stock"><?php echo __('В наличност', 'parfume-reviews'); ?></option><option value="out_of_stock"><?php echo __('Няма в наличност', 'parfume-reviews'); ?></option></select></td></tr>' +
                    '</table>' +
                    '</div>';
                
                $('#parfume-stores-container').append(newStore);
                storeIndex++;
            });
            
            $(document).on('click', '.remove-store', function(e) {
                e.preventDefault();
                $(this).closest('.store-item').remove();
            });
        });
        </script>
        <?php
    }
    
    private function render_store_item($index, $store) {
        ?>
        <div class="store-item" data-index="<?php echo $index; ?>">
            <div class="store-header">
                <strong><?php echo __('Магазин', 'parfume-reviews'); ?> <?php echo $index + 1; ?></strong>
                <a href="#" class="remove-store" style="float: right;"><?php echo __('Премахни', 'parfume-reviews'); ?></a>
            </div>
            <table class="form-table">
                <tr>
                    <th scope="row"><label><?php echo __('Име на магазина', 'parfume-reviews'); ?></label></th>
                    <td><input type="text" name="parfume_stores[<?php echo $index; ?>][name]" value="<?php echo esc_attr($store['name'] ?? ''); ?>" class="regular-text" /></td>
                </tr>
                <tr>
                    <th scope="row"><label><?php echo __('URL на магазина', 'parfume-reviews'); ?></label></th>
                    <td><input type="url" name="parfume_stores[<?php echo $index; ?>][url]" value="<?php echo esc_attr($store['url'] ?? ''); ?>" class="regular-text" /></td>
                </tr>
                <tr>
                    <th scope="row"><label><?php echo __('Affiliate URL', 'parfume-reviews'); ?></label></th>
                    <td><input type="url" name="parfume_stores[<?php echo $index; ?>][affiliate_url]" value="<?php echo esc_attr($store['affiliate_url'] ?? ''); ?>" class="regular-text" /></td>
                </tr>
                <tr>
                    <th scope="row"><label><?php echo __('Цена', 'parfume-reviews'); ?></label></th>
                    <td><input type="text" name="parfume_stores[<?php echo $index; ?>][price]" value="<?php echo esc_attr($store['price'] ?? ''); ?>" class="regular-text" /></td>
                </tr>
                <tr>
                    <th scope="row"><label><?php echo __('Размер', 'parfume-reviews'); ?></label></th>
                    <td><input type="text" name="parfume_stores[<?php echo $index; ?>][size]" value="<?php echo esc_attr($store['size'] ?? ''); ?>" class="regular-text" /></td>
                </tr>
                <tr>
                    <th scope="row"><label><?php echo __('Наличност', 'parfume-reviews'); ?></label></th>
                    <td>
                        <select name="parfume_stores[<?php echo $index; ?>][availability]">
                            <option value="in_stock" <?php selected($store['availability'] ?? '', 'in_stock'); ?>><?php echo __('В наличност', 'parfume-reviews'); ?></option>
                            <option value="out_of_stock" <?php selected($store['availability'] ?? '', 'out_of_stock'); ?>><?php echo __('Няма в наличност', 'parfume-reviews'); ?></option>
                        </select>
                    </td>
                </tr>
            </table>
        </div>
        <?php
    }
    
    public function parfume_notes_meta_box($post) {
        echo '<p>' . __('Използвайте таксономията "Ноти" за да добавите ноти към парфюма.', 'parfume-reviews') . '</p>';
        
        $top_notes = get_post_meta($post->ID, '_parfume_top_notes', true);
        $middle_notes = get_post_meta($post->ID, '_parfume_middle_notes', true);
        $base_notes = get_post_meta($post->ID, '_parfume_base_notes', true);
        
        echo '<p><label><strong>' . __('Топ ноти:', 'parfume-reviews') . '</strong></label><br>';
        echo '<textarea name="_parfume_top_notes" rows="3" cols="30">' . esc_textarea($top_notes) . '</textarea></p>';
        
        echo '<p><label><strong>' . __('Средни ноти:', 'parfume-reviews') . '</strong></label><br>';
        echo '<textarea name="_parfume_middle_notes" rows="3" cols="30">' . esc_textarea($middle_notes) . '</textarea></p>';
        
        echo '<p><label><strong>' . __('Базови ноти:', 'parfume-reviews') . '</strong></label><br>';
        echo '<textarea name="_parfume_base_notes" rows="3" cols="30">' . esc_textarea($base_notes) . '</textarea></p>';
    }
    
    public function save_meta_boxes($post_id) {
        if (!wp_verify_nonce($_POST['parfume_details_nonce'] ?? '', 'parfume_details_nonce')) {
            return;
        }
        
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Save parfume details
        $fields = array(
            '_parfume_description',
            '_parfume_release_year',
            '_parfume_rating',
            '_parfume_longevity',
            '_parfume_sillage',
            '_parfume_bottle_size',
            '_parfume_top_notes',
            '_parfume_middle_notes',
            '_parfume_base_notes'
        );
        
        foreach ($fields as $field) {
            if (isset($_POST[$field])) {
                update_post_meta($post_id, $field, sanitize_text_field($_POST[$field]));
            }
        }
        
        // Save stores
        if (isset($_POST['parfume_stores']) && is_array($_POST['parfume_stores'])) {
            $stores = array();
            foreach ($_POST['parfume_stores'] as $store_data) {
                if (!empty($store_data['name'])) {
                    $stores[] = array(
                        'name' => sanitize_text_field($store_data['name']),
                        'url' => esc_url_raw($store_data['url']),
                        'affiliate_url' => esc_url_raw($store_data['affiliate_url']),
                        'price' => sanitize_text_field($store_data['price']),
                        'size' => sanitize_text_field($store_data['size']),
                        'availability' => sanitize_text_field($store_data['availability']),
                    );
                }
            }
            update_post_meta($post_id, '_parfume_stores', $stores);
        }
    }
    
    public function ajax_update_store_price() {
        check_ajax_referer('parfume-admin-nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $post_id = intval($_POST['post_id']);
        $store_index = intval($_POST['store_index']);
        
        // Here you would implement price fetching logic
        wp_send_json_success(array('price' => '50.00 лв'));
    }
    
    public function ajax_get_store_sizes() {
        check_ajax_referer('parfume-admin-nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        // Here you would implement size fetching logic
        wp_send_json_success(array('sizes' => array('50ml', '100ml', '150ml')));
    }
    
    // Shortcode methods
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