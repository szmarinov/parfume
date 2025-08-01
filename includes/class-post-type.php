<?php
namespace Parfume_Reviews;

/**
 * Post Type class - управлява регистрацията на parfume post type
 * АКТУАЛИЗИРАН С НОВ STORES SIDEBAR И MOBILE НАСТРОЙКИ
 * ДОБАВЕНИ - Stores Meta Box и Product Scraper функционалности
 * ПОПРАВЕН - Blog post type rewrite rules за /parfiumi/blog/
 * ПЪЛНА ВЕРСИЯ - Всички методи от оригинала + нови поправки
 */
class Post_Type {
    
    /**
     * Instance на Query_Handler
     */
    private $query_handler;
    
    public function __construct() {
        add_action('init', array($this, 'register_post_type'));
        add_action('init', array($this, 'register_blog_post_type'));
        add_action('init', array($this, 'add_rewrite_rules'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_filter('template_include', array($this, 'load_templates'));
        add_filter('body_class', array($this, 'add_body_classes'));
        
        // STORES META BOX ФУНКЦИОНАЛНОСТИ
        add_action('add_meta_boxes', array($this, 'add_stores_meta_box'));
        add_action('save_post', array($this, 'save_stores_meta_box'));
        
        // MOBILE SETTINGS META BOX
        add_action('add_meta_boxes', array($this, 'add_mobile_meta_box'));
        add_action('save_post', array($this, 'save_mobile_meta_box'));
        
        // GENERAL META BOXES - ОРИГИНАЛНИ
        add_action('add_meta_boxes', array($this, 'add_general_meta_boxes'));
        add_action('save_post', array($this, 'save_general_meta_boxes'));
        
        // AJAX хендлъри за stores функционалности
        add_action('wp_ajax_parfume_add_store_to_post', array($this, 'ajax_add_store_to_post'));
        add_action('wp_ajax_parfume_remove_store_from_post', array($this, 'ajax_remove_store_from_post'));
        add_action('wp_ajax_parfume_reorder_stores', array($this, 'ajax_reorder_stores'));
        add_action('wp_ajax_parfume_scrape_store_data', array($this, 'ajax_scrape_store_data'));
        
        // ОРИГИНАЛНИ AJAX HANDLERS
        add_action('wp_ajax_update_store_price', array($this, 'ajax_update_store_price'));
        add_action('wp_ajax_get_store_sizes', array($this, 'ajax_get_store_sizes'));
        add_action('wp_ajax_parfume_get_store_variants', array($this, 'ajax_get_store_variants'));
        add_action('wp_ajax_parfume_refresh_store_data', array($this, 'ajax_refresh_store_data'));
        
        // PRICE COMPARISON AJAX
        add_action('wp_ajax_parfume_compare_prices', array($this, 'ajax_compare_prices'));
        add_action('wp_ajax_nopriv_parfume_compare_prices', array($this, 'ajax_compare_prices'));
        
        // SCRAPER AJAX HANDLERS
        add_action('wp_ajax_parfume_test_scraper', array($this, 'ajax_test_scraper'));
        add_action('wp_ajax_parfume_bulk_scrape', array($this, 'ajax_bulk_scrape'));
        add_action('wp_ajax_parfume_schedule_scrape', array($this, 'ajax_schedule_scrape'));
        
        // PERMALINK HOOKS
        add_filter('post_type_link', array($this, 'custom_post_type_link'), 1, 2);
        add_filter('query_vars', array($this, 'add_query_vars'));
        add_action('parse_request', array($this, 'parse_request'));
        
        // Enqueue admin scripts
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        
        // Инициализираме Query Handler
        if (class_exists('Parfume_Reviews\\Post_Type\\Query_Handler')) {
            $this->query_handler = new \Parfume_Reviews\Post_Type\Query_Handler();
        }
        
        // Debug хук
        if (defined('WP_DEBUG') && WP_DEBUG) {
            add_action('wp_footer', array($this, 'debug_query_info'));
        }
    }
    
    public function register_post_type() {
        $settings = get_option('parfume_reviews_settings', array());
        $slug = !empty($settings['parfume_slug']) ? $settings['parfume_slug'] : 'parfiumi';
        
        $labels = array(
            'name'                  => _x('Parfumes', 'Post Type General Name', 'parfume-reviews'),
            'singular_name'         => _x('Parfume', 'Post Type Singular Name', 'parfume-reviews'),
            'menu_name'            => __('Parfumes', 'parfume-reviews'),
            'name_admin_bar'       => __('Parfume', 'parfume-reviews'),
            'archives'             => __('Parfume Archives', 'parfume-reviews'),
            'all_items'            => __('All Parfumes', 'parfume-reviews'),
            'add_new_item'         => __('Add New Parfume', 'parfume-reviews'),
            'add_new'              => __('Add New', 'parfume-reviews'),
            'new_item'             => __('New Parfume', 'parfume-reviews'),
            'edit_item'            => __('Edit Parfume', 'parfume-reviews'),
            'update_item'          => __('Update Parfume', 'parfume-reviews'),
            'view_item'            => __('View Parfume', 'parfume-reviews'),
            'search_items'         => __('Search Parfumes', 'parfume-reviews'),
        );
        
        $args = array(
            'label'                => __('Parfume', 'parfume-reviews'),
            'description'          => __('Parfume reviews and information', 'parfume-reviews'),
            'labels'               => $labels,
            'supports'             => array('title', 'editor', 'thumbnail', 'author', 'revisions', 
                                           'excerpt', 'comments', 'custom-fields'),
            'taxonomies'           => array('marki', 'gender', 'aroma_type', 'season', 'intensity', 'notes', 'perfumer'),
            'hierarchical'         => false,
            'public'               => true,
            'show_ui'              => true,
            'show_in_menu'         => true,
            'menu_position'        => 5,
            'show_in_admin_bar'    => true,
            'show_in_nav_menus'    => true,
            'can_export'           => true,
            'has_archive'          => true,
            'exclude_from_search'  => false,
            'publicly_queryable'   => true,
            'capability_type'      => 'post',
            'show_in_rest'         => true,
            'menu_icon'            => 'dashicons-admin-appearance',
            'rewrite'              => array(
                'slug'         => $slug,
                'with_front'   => false,
                'hierarchical' => false,
            ),
        );
        
        register_post_type('parfume', $args);
    }
    
    /**
     * Регистрира parfume_blog post type
     * ПОПРАВЕНО: Правилен blog slug за /parfiumi/blog/
     */
    public function register_blog_post_type() {
        $settings = get_option('parfume_reviews_settings', array());
        $parfume_slug = !empty($settings['parfume_slug']) ? $settings['parfume_slug'] : 'parfiumi';
        
        // ПОПРАВЕНО: blog slug трябва да е под parfume_slug
        $blog_slug = $parfume_slug . '/blog';
        
        $labels = array(
            'name'                  => _x('Parfume Blog', 'Post Type General Name', 'parfume-reviews'),
            'singular_name'         => _x('Blog Post', 'Post Type Singular Name', 'parfume-reviews'),
            'menu_name'            => __('Blog Posts', 'parfume-reviews'),
            'name_admin_bar'       => __('Blog Post', 'parfume-reviews'),
            'archives'             => __('Blog Archives', 'parfume-reviews'),
            'all_items'            => __('All Blog Posts', 'parfume-reviews'),
            'add_new_item'         => __('Add New Blog Post', 'parfume-reviews'),
            'add_new'              => __('Add New', 'parfume-reviews'),
            'new_item'             => __('New Blog Post', 'parfume-reviews'),
            'edit_item'            => __('Edit Blog Post', 'parfume-reviews'),
            'update_item'          => __('Update Blog Post', 'parfume-reviews'),
            'view_item'            => __('View Blog Post', 'parfume-reviews'),
            'search_items'         => __('Search Blog Posts', 'parfume-reviews'),
        );
        
        $args = array(
            'label'                => __('Blog Post', 'parfume-reviews'),
            'description'          => __('Blog posts about parfumes', 'parfume-reviews'),
            'labels'               => $labels,
            'supports'             => array('title', 'editor', 'thumbnail', 'excerpt', 'author', 'comments'),
            'hierarchical'         => false,
            'public'               => true,
            'show_ui'              => true,
            'show_in_menu'         => 'edit.php?post_type=parfume',
            'menu_position'        => 5,
            'show_in_admin_bar'    => true,
            'show_in_nav_menus'    => true,
            'can_export'           => true,
            'has_archive'          => true,
            'exclude_from_search'  => false,
            'publicly_queryable'   => true,
            'capability_type'      => 'post',
            'show_in_rest'         => true,
            'rewrite'              => array(
                'slug'         => $blog_slug,
                'with_front'   => false,
                'hierarchical' => false,
            ),
        );
        
        register_post_type('parfume_blog', $args);
    }
    
    /**
     * Добавя custom rewrite rules
     * ПОПРАВЕНО: Специални правила за blog архив и pagination
     */
    public function add_rewrite_rules() {
        $settings = get_option('parfume_reviews_settings', array());
        $parfume_slug = !empty($settings['parfume_slug']) ? $settings['parfume_slug'] : 'parfiumi';
        
        // ПОПРАВЕНО: Blog archive rewrite rules - най-важни първи
        add_rewrite_rule(
            '^' . $parfume_slug . '/blog/?$',
            'index.php?post_type=parfume_blog',
            'top'
        );
        
        // ПОПРАВЕНО: Blog pagination
        add_rewrite_rule(
            '^' . $parfume_slug . '/blog/page/([0-9]+)/?$',
            'index.php?post_type=parfume_blog&paged=$matches[1]',
            'top'
        );
        
        // ПОПРАВЕНО: Single blog posts
        add_rewrite_rule(
            '^' . $parfume_slug . '/blog/([^/]+)/?$',
            'index.php?post_type=parfume_blog&name=$matches[1]',
            'top'
        );
        
        // ЗАПАЗЕНИ: Оригинални parfume rules
        add_rewrite_rule(
            '^' . $parfume_slug . '/([^/]+)/?$',
            'index.php?post_type=parfume&name=$matches[1]',
            'top'
        );
    }
    
    /**
     * Custom post type permalink
     */
    public function custom_post_type_link($post_link, $post) {
        if (!is_object($post) || !isset($post->post_type)) {
            return $post_link;
        }
        
        $settings = get_option('parfume_reviews_settings', array());
        $parfume_slug = !empty($settings['parfume_slug']) ? $settings['parfume_slug'] : 'parfiumi';
        
        if ($post->post_type === 'parfume') {
            return home_url('/' . $parfume_slug . '/' . $post->post_name . '/');
        } elseif ($post->post_type === 'parfume_blog') {
            return home_url('/' . $parfume_slug . '/blog/' . $post->post_name . '/');
        }
        
        return $post_link;
    }
    
    /**
     * Add query vars
     */
    public function add_query_vars($vars) {
        $vars[] = 'parfume_blog_archive';
        return $vars;
    }
    
    /**
     * Parse request
     */
    public function parse_request($wp) {
        // Handle blog archive requests
        if (isset($wp->query_vars['post_type']) && $wp->query_vars['post_type'] === 'parfume_blog') {
            // No additional processing needed - WordPress will handle automatically
        }
    }
    
    public function enqueue_scripts() {
        if (is_singular('parfume') || is_post_type_archive('parfume') || is_singular('parfume_blog') || is_post_type_archive('parfume_blog')) {
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
            
            // Добавяме локализация за AJAX
            wp_localize_script('parfume-reviews-frontend', 'parfume_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('parfume_frontend_nonce')
            ));
        }
    }
    
    public function load_templates($template) {
        if (is_singular('parfume')) {
            $plugin_template = PARFUME_REVIEWS_PLUGIN_DIR . 'templates/single-parfume.php';
            if (file_exists($plugin_template)) {
                return $plugin_template;
            }
        } elseif (is_post_type_archive('parfume')) {
            $plugin_template = PARFUME_REVIEWS_PLUGIN_DIR . 'templates/archive-parfume.php';
            if (file_exists($plugin_template)) {
                return $plugin_template;
            }
        } elseif (is_singular('parfume_blog')) {
            $plugin_template = PARFUME_REVIEWS_PLUGIN_DIR . 'templates/single-parfume-blog.php';
            if (file_exists($plugin_template)) {
                return $plugin_template;
            }
        } elseif (is_post_type_archive('parfume_blog')) {
            $plugin_template = PARFUME_REVIEWS_PLUGIN_DIR . 'templates/archive-parfume-blog.php';
            if (file_exists($plugin_template)) {
                return $plugin_template;
            }
        }
        
        return $template;
    }
    
    public function add_body_classes($classes) {
        if (is_singular('parfume')) {
            $classes[] = 'single-parfume';
        } elseif (is_post_type_archive('parfume')) {
            $classes[] = 'archive-parfume';
        } elseif (is_singular('parfume_blog')) {
            $classes[] = 'single-parfume-blog';
        } elseif (is_post_type_archive('parfume_blog')) {
            $classes[] = 'archive-parfume-blog';
        }
        
        return $classes;
    }
    
    // ==================== GENERAL META BOXES ====================
    
    /**
     * Добавя основните meta boxes за parfume posts
     */
    public function add_general_meta_boxes() {
        add_meta_box(
            'parfume-details',
            __('Parfume Details', 'parfume-reviews'),
            array($this, 'parfume_details_meta_box'),
            'parfume',
            'normal',
            'high'
        );
        
        add_meta_box(
            'parfume-notes',
            __('Scent Notes', 'parfume-reviews'),
            array($this, 'parfume_notes_meta_box'),
            'parfume',
            'normal',
            'high'
        );
        
        add_meta_box(
            'parfume-review',
            __('Review & Rating', 'parfume-reviews'),
            array($this, 'parfume_review_meta_box'),
            'parfume',
            'normal',
            'default'
        );
        
        add_meta_box(
            'parfume-pricing',
            __('Pricing Information', 'parfume-reviews'),
            array($this, 'parfume_pricing_meta_box'),
            'parfume',
            'side',
            'default'
        );
    }
    
    /**
     * Parfume details meta box
     */
    public function parfume_details_meta_box($post) {
        wp_nonce_field('parfume_meta_box', 'parfume_meta_box_nonce');
        
        $price = get_post_meta($post->ID, '_parfume_price', true);
        $size = get_post_meta($post->ID, '_parfume_size', true);
        $brand = get_post_meta($post->ID, '_parfume_brand', true);
        $year = get_post_meta($post->ID, '_parfume_year', true);
        $concentration = get_post_meta($post->ID, '_parfume_concentration', true);
        $availability = get_post_meta($post->ID, '_parfume_availability', true);
        
        ?>
        <table class="form-table">
            <tr>
                <th scope="row"><label for="parfume_price"><?php _e('Price', 'parfume-reviews'); ?></label></th>
                <td>
                    <input type="text" id="parfume_price" name="parfume_price" value="<?php echo esc_attr($price); ?>" class="regular-text">
                    <p class="description"><?php _e('е.g. 120.00 лв.', 'parfume-reviews'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="parfume_size"><?php _e('Size', 'parfume-reviews'); ?></label></th>
                <td>
                    <input type="text" id="parfume_size" name="parfume_size" value="<?php echo esc_attr($size); ?>" class="regular-text">
                    <p class="description"><?php _e('е.g. 50ml, 100ml', 'parfume-reviews'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="parfume_brand"><?php _e('Brand', 'parfume-reviews'); ?></label></th>
                <td>
                    <input type="text" id="parfume_brand" name="parfume_brand" value="<?php echo esc_attr($brand); ?>" class="regular-text">
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="parfume_year"><?php _e('Release Year', 'parfume-reviews'); ?></label></th>
                <td>
                    <input type="number" id="parfume_year" name="parfume_year" value="<?php echo esc_attr($year); ?>" class="regular-text" min="1900" max="<?php echo date('Y'); ?>">
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="parfume_concentration"><?php _e('Concentration', 'parfume-reviews'); ?></label></th>
                <td>
                    <select id="parfume_concentration" name="parfume_concentration">
                        <option value=""><?php _e('Select concentration', 'parfume-reviews'); ?></option>
                        <option value="edt" <?php selected($concentration, 'edt'); ?>><?php _e('Eau de Toilette (EDT)', 'parfume-reviews'); ?></option>
                        <option value="edp" <?php selected($concentration, 'edp'); ?>><?php _e('Eau de Parfum (EDP)', 'parfume-reviews'); ?></option>
                        <option value="edc" <?php selected($concentration, 'edc'); ?>><?php _e('Eau de Cologne (EDC)', 'parfume-reviews'); ?></option>
                        <option value="parfum" <?php selected($concentration, 'parfum'); ?>><?php _e('Parfum', 'parfume-reviews'); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="parfume_availability"><?php _e('Availability', 'parfume-reviews'); ?></label></th>
                <td>
                    <select id="parfume_availability" name="parfume_availability">
                        <option value="in_stock" <?php selected($availability, 'in_stock'); ?>><?php _e('In Stock', 'parfume-reviews'); ?></option>
                        <option value="out_of_stock" <?php selected($availability, 'out_of_stock'); ?>><?php _e('Out of Stock', 'parfume-reviews'); ?></option>
                        <option value="limited" <?php selected($availability, 'limited'); ?>><?php _e('Limited', 'parfume-reviews'); ?></option>
                    </select>
                </td>
            </tr>
        </table>
        <?php
    }
    
    /**
     * Parfume notes meta box
     */
    public function parfume_notes_meta_box($post) {
        $top_notes = get_post_meta($post->ID, '_parfume_top_notes', true);
        $middle_notes = get_post_meta($post->ID, '_parfume_middle_notes', true);
        $base_notes = get_post_meta($post->ID, '_parfume_base_notes', true);
        
        ?>
        <table class="form-table">
            <tr>
                <th scope="row"><label for="parfume_top_notes"><?php _e('Top Notes', 'parfume-reviews'); ?></label></th>
                <td>
                    <textarea id="parfume_top_notes" name="parfume_top_notes" rows="3" cols="50" class="large-text"><?php echo esc_textarea($top_notes); ?></textarea>
                    <p class="description"><?php _e('First impression notes', 'parfume-reviews'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="parfume_middle_notes"><?php _e('Middle Notes', 'parfume-reviews'); ?></label></th>
                <td>
                    <textarea id="parfume_middle_notes" name="parfume_middle_notes" rows="3" cols="50" class="large-text"><?php echo esc_textarea($middle_notes); ?></textarea>
                    <p class="description"><?php _e('Heart of the fragrance', 'parfume-reviews'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="parfume_base_notes"><?php _e('Base Notes', 'parfume-reviews'); ?></label></th>
                <td>
                    <textarea id="parfume_base_notes" name="parfume_base_notes" rows="3" cols="50" class="large-text"><?php echo esc_textarea($base_notes); ?></textarea>
                    <p class="description"><?php _e('Long-lasting foundation notes', 'parfume-reviews'); ?></p>
                </td>
            </tr>
        </table>
        <?php
    }
    
    /**
     * Parfume review meta box
     */
    public function parfume_review_meta_box($post) {
        $rating = get_post_meta($post->ID, '_parfume_rating', true);
        $pros = get_post_meta($post->ID, '_parfume_pros', true);
        $cons = get_post_meta($post->ID, '_parfume_cons', true);
        
        ?>
        <table class="form-table">
            <tr>
                <th scope="row"><label for="parfume_rating"><?php _e('Rating', 'parfume-reviews'); ?></label></th>
                <td>
                    <select id="parfume_rating" name="parfume_rating">
                        <option value=""><?php _e('Select rating', 'parfume-reviews'); ?></option>
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <option value="<?php echo $i; ?>" <?php selected($rating, $i); ?>><?php echo $i; ?> <?php _e('Stars', 'parfume-reviews'); ?></option>
                        <?php endfor; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="parfume_pros"><?php _e('Pros', 'parfume-reviews'); ?></label></th>
                <td>
                    <textarea id="parfume_pros" name="parfume_pros" rows="5" cols="50" class="large-text"><?php echo esc_textarea($pros); ?></textarea>
                    <p class="description"><?php _e('One pro per line', 'parfume-reviews'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="parfume_cons"><?php _e('Cons', 'parfume-reviews'); ?></label></th>
                <td>
                    <textarea id="parfume_cons" name="parfume_cons" rows="5" cols="50" class="large-text"><?php echo esc_textarea($cons); ?></textarea>
                    <p class="description"><?php _e('One con per line', 'parfume-reviews'); ?></p>
                </td>
            </tr>
        </table>
        <?php
    }
    
    /**
     * Parfume pricing meta box
     */
    public function parfume_pricing_meta_box($post) {
        $base_price = get_post_meta($post->ID, '_parfume_base_price', true);
        $sale_price = get_post_meta($post->ID, '_parfume_sale_price', true);
        $currency = get_post_meta($post->ID, '_parfume_currency', true);
        
        if (empty($currency)) {
            $currency = 'BGN';
        }
        
        ?>
        <table class="form-table">
            <tr>
                <th scope="row"><label for="parfume_base_price"><?php _e('Base Price', 'parfume-reviews'); ?></label></th>
                <td>
                    <input type="number" id="parfume_base_price" name="parfume_base_price" value="<?php echo esc_attr($base_price); ?>" step="0.01" class="regular-text">
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="parfume_sale_price"><?php _e('Sale Price', 'parfume-reviews'); ?></label></th>
                <td>
                    <input type="number" id="parfume_sale_price" name="parfume_sale_price" value="<?php echo esc_attr($sale_price); ?>" step="0.01" class="regular-text">
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="parfume_currency"><?php _e('Currency', 'parfume-reviews'); ?></label></th>
                <td>
                    <select id="parfume_currency" name="parfume_currency">
                        <option value="BGN" <?php selected($currency, 'BGN'); ?>>BGN</option>
                        <option value="EUR" <?php selected($currency, 'EUR'); ?>>EUR</option>
                        <option value="USD" <?php selected($currency, 'USD'); ?>>USD</option>
                    </select>
                </td>
            </tr>
        </table>
        <?php
    }
    
    /**
     * Save general meta boxes
     */
    public function save_general_meta_boxes($post_id) {
        if (!isset($_POST['parfume_meta_box_nonce']) || !wp_verify_nonce($_POST['parfume_meta_box_nonce'], 'parfume_meta_box')) {
            return;
        }
        
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        if (get_post_type($post_id) !== 'parfume') {
            return;
        }
        
        $fields = array(
            '_parfume_price' => 'parfume_price',
            '_parfume_size' => 'parfume_size',
            '_parfume_brand' => 'parfume_brand',
            '_parfume_year' => 'parfume_year',
            '_parfume_concentration' => 'parfume_concentration',
            '_parfume_availability' => 'parfume_availability',
            '_parfume_top_notes' => 'parfume_top_notes',
            '_parfume_middle_notes' => 'parfume_middle_notes',
            '_parfume_base_notes' => 'parfume_base_notes',
            '_parfume_rating' => 'parfume_rating',
            '_parfume_pros' => 'parfume_pros',
            '_parfume_cons' => 'parfume_cons',
            '_parfume_base_price' => 'parfume_base_price',
            '_parfume_sale_price' => 'parfume_sale_price',
            '_parfume_currency' => 'parfume_currency',
        );
        
        foreach ($fields as $meta_key => $post_key) {
            if (isset($_POST[$post_key])) {
                $value = sanitize_text_field($_POST[$post_key]);
                if ($meta_key === '_parfume_pros' || $meta_key === '_parfume_cons' || 
                    $meta_key === '_parfume_top_notes' || $meta_key === '_parfume_middle_notes' || 
                    $meta_key === '_parfume_base_notes') {
                    $value = sanitize_textarea_field($_POST[$post_key]);
                }
                update_post_meta($post_id, $meta_key, $value);
            }
        }
    }
    
    // ==================== STORES META BOX ====================
    
    public function add_stores_meta_box() {
        add_meta_box(
            'parfume_stores',
            __('Магазини и цени', 'parfume-reviews'),
            array($this, 'render_stores_meta_box'),
            'parfume',
            'side',
            'high'
        );
    }
    
    public function render_stores_meta_box($post) {
        wp_nonce_field('parfume_stores_meta_box', 'parfume_stores_meta_box_nonce');
        
        $stores = get_post_meta($post->ID, '_parfume_stores', true);
        if (!$stores) {
            $stores = array();
        }
        
        // Get available stores from settings
        $available_stores = $this->get_available_stores();
        
        echo '<div id="parfume-stores-container">';
        echo '<div class="stores-list">';
        
        if (!empty($stores)) {
            foreach ($stores as $index => $store) {
                $this->render_store_item($store, $index);
            }
        }
        
        echo '</div>';
        echo '<div class="add-store-section">';
        echo '<select id="store-selector">';
        echo '<option value="">' . __('Избери магазин', 'parfume-reviews') . '</option>';
        
        foreach ($available_stores as $store_key => $store_data) {
            echo '<option value="' . esc_attr($store_key) . '">' . esc_html($store_data['name']) . '</option>';
        }
        
        echo '</select>';
        echo '<button type="button" id="add-store-btn" class="button">' . __('Добави магазин', 'parfume-reviews') . '</button>';
        echo '</div>';
        echo '</div>';
        
        // Add JavaScript for stores functionality
        $this->stores_meta_box_scripts();
    }
    
    private function render_store_item($store, $index) {
        echo '<div class="store-item" data-index="' . esc_attr($index) . '">';
        echo '<div class="store-header">';
        echo '<h4>' . esc_html($store['name']) . '</h4>';
        echo '<button type="button" class="remove-store button-link-delete">' . __('Премахни', 'parfume-reviews') . '</button>';
        echo '</div>';
        
        echo '<div class="store-fields">';
        echo '<label>' . __('URL адрес:', 'parfume-reviews');
        echo '<input type="url" name="parfume_stores[' . $index . '][url]" value="' . esc_attr($store['url']) . '" />';
        echo '</label>';
        
        echo '<label>' . __('Цена:', 'parfume-reviews');
        echo '<input type="text" name="parfume_stores[' . $index . '][price]" value="' . esc_attr($store['price']) . '" />';
        echo '</label>';
        
        echo '<label>' . __('Валута:', 'parfume-reviews');
        echo '<select name="parfume_stores[' . $index . '][currency]">';
        $currencies = array('BGN' => 'BGN', 'EUR' => 'EUR', 'USD' => 'USD');
        foreach ($currencies as $code => $label) {
            $selected = ($store['currency'] === $code) ? 'selected' : '';
            echo '<option value="' . esc_attr($code) . '" ' . $selected . '>' . esc_html($label) . '</option>';
        }
        echo '</select>';
        echo '</label>';
        
        echo '<label>' . __('Наличност:', 'parfume-reviews');
        echo '<select name="parfume_stores[' . $index . '][availability]">';
        $availability_options = array(
            'in_stock' => __('На склад', 'parfume-reviews'),
            'out_of_stock' => __('Няма на склад', 'parfume-reviews'),
            'limited' => __('Ограничено количество', 'parfume-reviews')
        );
        foreach ($availability_options as $value => $label) {
            $selected = ($store['availability'] === $value) ? 'selected' : '';
            echo '<option value="' . esc_attr($value) . '" ' . $selected . '>' . esc_html($label) . '</option>';
        }
        echo '</select>';
        echo '</label>';
        
        echo '<button type="button" class="scrape-store-data button">' . __('Автоматично попълване', 'parfume-reviews') . '</button>';
        echo '</div>';
        
        // Hidden fields for store metadata
        echo '<input type="hidden" name="parfume_stores[' . $index . '][store_key]" value="' . esc_attr($store['store_key']) . '" />';
        echo '<input type="hidden" name="parfume_stores[' . $index . '][name]" value="' . esc_attr($store['name']) . '" />';
        
        echo '</div>';
    }
    
    private function get_available_stores() {
        return array(
            'parfium' => array(
                'name' => 'Parfium.bg',
                'domain' => 'parfium.bg',
                'selectors' => array(
                    'price' => '.price, .product-price',
                    'availability' => '.availability, .stock-status'
                )
            ),
            'douglas' => array(
                'name' => 'Douglas.bg',
                'domain' => 'douglas.bg',
                'selectors' => array(
                    'price' => '.price, .product-price',
                    'availability' => '.availability, .stock-status'
                )
            ),
            'notino' => array(
                'name' => 'Notino.bg',
                'domain' => 'notino.bg',
                'selectors' => array(
                    'price' => '.price, .product-price',
                    'availability' => '.availability, .stock-status'
                )
            )
        );
    }
    
    private function stores_meta_box_scripts() {
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Add store functionality
            $('#add-store-btn').on('click', function() {
                var selectedStore = $('#store-selector').val();
                if (!selectedStore) return;
                
                var data = {
                    action: 'parfume_add_store_to_post',
                    post_id: <?php echo get_the_ID(); ?>,
                    store_key: selectedStore,
                    nonce: '<?php echo wp_create_nonce('parfume_stores_nonce'); ?>'
                };
                
                $.post(ajaxurl, data, function(response) {
                    if (response.success) {
                        $('.stores-list').append(response.data.html);
                        $('#store-selector').val('');
                    }
                });
            });
            
            // Remove store functionality
            $(document).on('click', '.remove-store', function() {
                $(this).closest('.store-item').remove();
            });
            
            // Scrape store data functionality
            $(document).on('click', '.scrape-store-data', function() {
                var $storeItem = $(this).closest('.store-item');
                var url = $storeItem.find('input[type="url"]').val();
                
                if (!url) {
                    alert('<?php echo esc_js(__('Моля въведете URL адрес на магазина', 'parfume-reviews')); ?>');
                    return;
                }
                
                var data = {
                    action: 'parfume_scrape_store_data',
                    url: url,
                    nonce: '<?php echo wp_create_nonce('parfume_scrape_nonce'); ?>'
                };
                
                $(this).prop('disabled', true).text('<?php echo esc_js(__('Зареждане...', 'parfume-reviews')); ?>');
                
                $.post(ajaxurl, data, function(response) {
                    if (response.success) {
                        if (response.data.price) {
                            $storeItem.find('input[name*="[price]"]').val(response.data.price);
                        }
                        if (response.data.availability) {
                            $storeItem.find('select[name*="[availability]"]').val(response.data.availability);
                        }
                    } else {
                        alert('<?php echo esc_js(__('Грешка при зареждане на данните', 'parfume-reviews')); ?>');
                    }
                }).always(function() {
                    $('.scrape-store-data').prop('disabled', false).text('<?php echo esc_js(__('Автоматично попълване', 'parfume-reviews')); ?>');
                });
            });
        });
        </script>
        <?php
    }
    
    public function save_stores_meta_box($post_id) {
        if (!isset($_POST['parfume_stores_meta_box_nonce']) || 
            !wp_verify_nonce($_POST['parfume_stores_meta_box_nonce'], 'parfume_stores_meta_box')) {
            return;
        }
        
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        if (isset($_POST['parfume_stores']) && is_array($_POST['parfume_stores'])) {
            $stores = array();
            foreach ($_POST['parfume_stores'] as $store) {
                $stores[] = array(
                    'store_key' => sanitize_key($store['store_key']),
                    'name' => sanitize_text_field($store['name']),
                    'url' => esc_url_raw($store['url']),
                    'price' => sanitize_text_field($store['price']),
                    'currency' => sanitize_text_field($store['currency']),
                    'availability' => sanitize_text_field($store['availability'])
                );
            }
            update_post_meta($post_id, '_parfume_stores', $stores);
        } else {
            delete_post_meta($post_id, '_parfume_stores');
        }
    }
    
    // ==================== MOBILE META BOX ====================
    
    public function add_mobile_meta_box() {
        add_meta_box(
            'parfume_mobile_settings',
            __('Mobile настройки', 'parfume-reviews'),
            array($this, 'render_mobile_meta_box'),
            'parfume',
            'side',
            'default'
        );
    }
    
    public function render_mobile_meta_box($post) {
        wp_nonce_field('parfume_mobile_meta_box', 'parfume_mobile_meta_box_nonce');
        
        $mobile_optimized = get_post_meta($post->ID, '_parfume_mobile_optimized', true);
        $mobile_layout = get_post_meta($post->ID, '_parfume_mobile_layout', true);
        $mobile_fixed_stores = get_post_meta($post->ID, '_parfume_mobile_fixed_stores', true);
        
        echo '<p><label>';
        echo '<input type="checkbox" name="parfume_mobile_optimized" value="1" ' . checked($mobile_optimized, '1', false) . ' />';
        echo ' ' . __('Оптимизирано за мобилни устройства', 'parfume-reviews');
        echo '</label></p>';
        
        echo '<p><label>' . __('Mobile layout:', 'parfume-reviews') . '<br>';
        echo '<select name="parfume_mobile_layout">';
        echo '<option value="default"' . selected($mobile_layout, 'default', false) . '>' . __('По подразбиране', 'parfume-reviews') . '</option>';
        echo '<option value="compact"' . selected($mobile_layout, 'compact', false) . '>' . __('Компактен', 'parfume-reviews') . '</option>';
        echo '<option value="detailed"' . selected($mobile_layout, 'detailed', false) . '>' . __('Детайлен', 'parfume-reviews') . '</option>';
        echo '</select>';
        echo '</label></p>';
        
        echo '<p><label>';
        echo '<input type="checkbox" name="parfume_mobile_fixed_stores" value="1" ' . checked($mobile_fixed_stores, '1', false) . ' />';
        echo ' ' . __('Фиксиран sidebar с магазини на мобил', 'parfume-reviews');
        echo '</label></p>';
    }
    
    public function save_mobile_meta_box($post_id) {
        if (!isset($_POST['parfume_mobile_meta_box_nonce']) || 
            !wp_verify_nonce($_POST['parfume_mobile_meta_box_nonce'], 'parfume_mobile_meta_box')) {
            return;
        }
        
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        if (get_post_type($post_id) !== 'parfume') {
            return;
        }
        
        $mobile_optimized = isset($_POST['parfume_mobile_optimized']) ? '1' : '0';
        update_post_meta($post_id, '_parfume_mobile_optimized', $mobile_optimized);
        
        if (isset($_POST['parfume_mobile_layout'])) {
            update_post_meta($post_id, '_parfume_mobile_layout', sanitize_text_field($_POST['parfume_mobile_layout']));
        }
        
        $mobile_fixed_stores = isset($_POST['parfume_mobile_fixed_stores']) ? '1' : '0';
        update_post_meta($post_id, '_parfume_mobile_fixed_stores', $mobile_fixed_stores);
    }
    
    // ==================== AJAX HANDLERS ====================
    
    public function ajax_add_store_to_post() {
        check_ajax_referer('parfume_stores_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_die();
        }
        
        $post_id = intval($_POST['post_id']);
        $store_key = sanitize_key($_POST['store_key']);
        
        $available_stores = $this->get_available_stores();
        
        if (!isset($available_stores[$store_key])) {
            wp_send_json_error('Invalid store');
        }
        
        $stores = get_post_meta($post_id, '_parfume_stores', true);
        if (!$stores) {
            $stores = array();
        }
        
        // Check if store already exists
        foreach ($stores as $store) {
            if ($store['store_key'] === $store_key) {
                wp_send_json_error('Store already exists');
            }
        }
        
        $new_store = array(
            'store_key' => $store_key,
            'name' => $available_stores[$store_key]['name'],
            'url' => '',
            'price' => '',
            'currency' => 'BGN',
            'availability' => 'in_stock'
        );
        
        $stores[] = $new_store;
        update_post_meta($post_id, '_parfume_stores', $stores);
        
        ob_start();
        $this->render_store_item($new_store, count($stores) - 1);
        $html = ob_get_clean();
        
        wp_send_json_success(array('html' => $html));
    }
    
    public function ajax_remove_store_from_post() {
        check_ajax_referer('parfume_stores_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_die();
        }
        
        $post_id = intval($_POST['post_id']);
        $store_index = intval($_POST['store_index']);
        
        $stores = get_post_meta($post_id, '_parfume_stores', true);
        if (isset($stores[$store_index])) {
            unset($stores[$store_index]);
            $stores = array_values($stores); // Reindex array
            update_post_meta($post_id, '_parfume_stores', $stores);
        }
        
        wp_send_json_success();
    }
    
    public function ajax_reorder_stores() {
        check_ajax_referer('parfume_stores_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_die();
        }
        
        $post_id = intval($_POST['post_id']);
        $new_order = array_map('intval', $_POST['new_order']);
        
        $stores = get_post_meta($post_id, '_parfume_stores', true);
        $reordered_stores = array();
        
        foreach ($new_order as $index) {
            if (isset($stores[$index])) {
                $reordered_stores[] = $stores[$index];
            }
        }
        
        update_post_meta($post_id, '_parfume_stores', $reordered_stores);
        wp_send_json_success();
    }
    
    public function ajax_scrape_store_data() {
        check_ajax_referer('parfume_scrape_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_die();
        }
        
        $url = esc_url_raw($_POST['url']);
        
        // Simple scraping logic - can be enhanced
        $response = wp_remote_get($url, array('timeout' => 10));
        
        if (is_wp_error($response)) {
            wp_send_json_error('Failed to fetch URL');
        }
        
        $body = wp_remote_retrieve_body($response);
        
        // Basic price extraction (can be improved with better selectors)
        $price = '';
        $availability = 'in_stock';
        
        // Look for price patterns
        if (preg_match('/(\d+[\.,]\d+)\s*(лв|bgn|eur)/i', $body, $matches)) {
            $price = $matches[1];
        }
        
        // Look for availability patterns
        if (preg_match('/(няма на склад|out of stock|unavailable)/i', $body)) {
            $availability = 'out_of_stock';
        } elseif (preg_match('/(ограничено|limited)/i', $body)) {
            $availability = 'limited';
        }
        
        wp_send_json_success(array(
            'price' => $price,
            'availability' => $availability
        ));
    }
    
    // ОРИГИНАЛНИ AJAX HANDLERS
    
    public function ajax_update_store_price() {
        check_ajax_referer('parfume_reviews_nonce', 'nonce');
        
        $store_id = intval($_POST['store_id']);
        
        // Mock response for price update
        wp_send_json_success(array(
            'price' => '120.00 лв.',
            'last_updated' => current_time('mysql')
        ));
    }
    
    public function ajax_get_store_sizes() {
        check_ajax_referer('parfume_reviews_nonce', 'nonce');
        
        $store_id = intval($_POST['store_id']);
        
        // Mock data for store sizes
        $sizes = array(
            array('size' => '30ml', 'price' => '45.00 лв.'),
            array('size' => '50ml', 'price' => '75.00 лв.'),
            array('size' => '100ml', 'price' => '120.00 лв.'),
        );
        
        wp_send_json_success($sizes);
    }
    
    public function ajax_get_store_variants() {
        check_ajax_referer('parfume_reviews_nonce', 'nonce');
        
        $store_id = intval($_POST['store_id']);
        $product_url = esc_url_raw($_POST['product_url']);
        
        // Mock variants data
        $variants = array(
            array(
                'name' => '30ml EDT',
                'price' => '45.00',
                'availability' => 'in_stock',
                'url' => $product_url . '?size=30ml'
            ),
            array(
                'name' => '50ml EDT',
                'price' => '75.00', 
                'availability' => 'in_stock',
                'url' => $product_url . '?size=50ml'
            ),
            array(
                'name' => '100ml EDT',
                'price' => '120.00',
                'availability' => 'limited',
                'url' => $product_url . '?size=100ml'
            )
        );
        
        wp_send_json_success($variants);
    }
    
    public function ajax_refresh_store_data() {
        check_ajax_referer('parfume_reviews_nonce', 'nonce');
        
        $post_id = intval($_POST['post_id']);
        $store_index = intval($_POST['store_index']);
        
        $stores = get_post_meta($post_id, '_parfume_stores', true);
        
        if (!isset($stores[$store_index])) {
            wp_send_json_error('Store not found');
        }
        
        $store = $stores[$store_index];
        
        // Mock refresh - in real implementation would scrape again
        $updated_data = array(
            'price' => number_format(rand(50, 200), 2) . ' лв.',
            'availability' => rand(0, 1) ? 'in_stock' : 'limited',
            'last_updated' => current_time('mysql')
        );
        
        // Update store data
        $stores[$store_index] = array_merge($store, $updated_data);
        update_post_meta($post_id, '_parfume_stores', $stores);
        
        wp_send_json_success($updated_data);
    }
    
    public function ajax_compare_prices() {
        if (isset($_POST['nonce'])) {
            check_ajax_referer('parfume_reviews_nonce', 'nonce');
        }
        
        $parfume_ids = array_map('intval', $_POST['parfume_ids']);
        
        if (empty($parfume_ids)) {
            wp_send_json_error('No parfumes provided');
        }
        
        $comparison_data = array();
        
        foreach ($parfume_ids as $parfume_id) {
            $stores = get_post_meta($parfume_id, '_parfume_stores', true);
            $comparison_data[$parfume_id] = array(
                'title' => get_the_title($parfume_id),
                'stores' => $stores ?: array()
            );
        }
        
        wp_send_json_success($comparison_data);
    }
    
    public function ajax_test_scraper() {
        check_ajax_referer('parfume_reviews_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die();
        }
        
        $url = esc_url_raw($_POST['test_url']);
        $store_key = sanitize_key($_POST['store_key']);
        
        // Mock scraper test
        $test_result = array(
            'url' => $url,
            'store' => $store_key,
            'found_price' => '89.99 лв.',
            'found_availability' => 'in_stock',
            'scrape_time' => '1.2s',
            'success' => true
        );
        
        wp_send_json_success($test_result);
    }
    
    public function ajax_bulk_scrape() {
        check_ajax_referer('parfume_reviews_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die();
        }
        
        $post_ids = array_map('intval', $_POST['post_ids']);
        $results = array();
        
        foreach ($post_ids as $post_id) {
            // Mock bulk scrape result
            $results[] = array(
                'post_id' => $post_id,
                'title' => get_the_title($post_id),
                'scraped_stores' => rand(1, 3),
                'success' => rand(0, 1) ? true : false
            );
        }
        
        wp_send_json_success($results);
    }
    
    public function ajax_schedule_scrape() {
        check_ajax_referer('parfume_reviews_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die();
        }
        
        $schedule_time = sanitize_text_field($_POST['schedule_time']);
        $post_ids = array_map('intval', $_POST['post_ids']);
        
        // Mock schedule response
        wp_send_json_success(array(
            'scheduled' => count($post_ids),
            'schedule_time' => $schedule_time,
            'message' => sprintf(__('Scheduled scraping for %d parfumes at %s', 'parfume-reviews'), count($post_ids), $schedule_time)
        ));
    }
    
    // ==================== ADMIN SCRIPTS ====================
    
    public function enqueue_admin_scripts($hook) {
        global $post_type;
        
        if ($post_type === 'parfume' && in_array($hook, array('post.php', 'post-new.php'))) {
            wp_enqueue_script(
                'parfume-admin',
                PARFUME_REVIEWS_PLUGIN_URL . 'assets/js/admin.js',
                array('jquery', 'jquery-ui-sortable'),
                PARFUME_REVIEWS_VERSION,
                true
            );
            
            wp_enqueue_style(
                'parfume-admin',
                PARFUME_REVIEWS_PLUGIN_URL . 'assets/css/admin.css',
                array(),
                PARFUME_REVIEWS_VERSION
            );
            
            wp_localize_script('parfume-admin', 'parfume_admin_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('parfume_reviews_nonce')
            ));
        }
    }
    
    // ==================== DEBUG FUNCTIONS ====================
    
    public function debug_query_info() {
        if (is_singular('parfume') || is_post_type_archive('parfume') || is_singular('parfume_blog') || is_post_type_archive('parfume_blog')) {
            global $wp_query;
            
            echo '<!-- Parfume Debug Info -->';
            echo '<!-- Post Type: ' . get_post_type() . ' -->';
            echo '<!-- Is Singular: ' . (is_singular() ? 'yes' : 'no') . ' -->';
            echo '<!-- Is Archive: ' . (is_archive() ? 'yes' : 'no') . ' -->';
            echo '<!-- Query Vars: ' . print_r($wp_query->query_vars, true) . ' -->';
            echo '<!-- End Parfume Debug -->';
        }
    }
}