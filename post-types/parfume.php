<?php
/**
 * Parfume Post Type
 *
 * @package Parfume_Reviews
 * @subpackage PostTypes
 */

namespace Parfume_Reviews\PostTypes;

use Parfume_Reviews\Utils\Post_Type_Base;
use Parfume_Reviews\Utils\Helpers;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Parfume Post Type Class
 */
class Parfume extends Post_Type_Base {

    /**
     * Post type slug
     *
     * @var string
     */
    protected $post_type = 'parfume';

    /**
     * Initialize the parfume post type
     */
    public function init() {
        // Register the post type
        $this->register();
        
        // Add hooks
        add_action('save_post_' . $this->post_type, array($this, 'save_meta_data'), 10, 3);
        add_filter('manage_' . $this->post_type . '_posts_columns', array($this, 'add_admin_columns'));
        add_action('manage_' . $this->post_type . '_posts_custom_column', array($this, 'admin_column_content'), 10, 2);
        add_filter('manage_edit-' . $this->post_type . '_sortable_columns', array($this, 'sortable_columns'));
        add_action('pre_get_posts', array($this, 'admin_orderby'));
        add_filter('post_row_actions', array($this, 'add_row_actions'), 10, 2);
        
        // Template hooks
        add_filter('single_template', array($this, 'load_single_template'));
        add_filter('archive_template', array($this, 'load_archive_template'));
        
        // Frontend enqueue
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        
        // AJAX handlers
        add_action('wp_ajax_parfume_get_related', array($this, 'ajax_get_related'));
        add_action('wp_ajax_nopriv_parfume_get_related', array($this, 'ajax_get_related'));
    }

    /**
     * Get post type arguments
     *
     * @return array
     */
    protected function get_args() {
        $settings = get_option('parfume_reviews_settings', array());
        $slug = !empty($settings['parfume_slug']) ? $settings['parfume_slug'] : 'parfiumi';

        $labels = array(
            'name'                  => __('Parfumes', 'parfume-reviews'),
            'singular_name'         => __('Parfume', 'parfume-reviews'),
            'menu_name'            => __('Parfumes', 'parfume-reviews'),
            'name_admin_bar'       => __('Parfume', 'parfume-reviews'),
            'add_new'              => __('Add New', 'parfume-reviews'),
            'add_new_item'         => __('Add New Parfume', 'parfume-reviews'),
            'new_item'             => __('New Parfume', 'parfume-reviews'),
            'edit_item'            => __('Edit Parfume', 'parfume-reviews'),
            'view_item'            => __('View Parfume', 'parfume-reviews'),
            'all_items'            => __('All Parfumes', 'parfume-reviews'),
            'search_items'         => __('Search Parfumes', 'parfume-reviews'),
            'parent_item_colon'    => __('Parent Parfumes:', 'parfume-reviews'),
            'not_found'            => __('No parfumes found.', 'parfume-reviews'),
            'not_found_in_trash'   => __('No parfumes found in Trash.', 'parfume-reviews'),
            'featured_image'       => __('Parfume Image', 'parfume-reviews'),
            'set_featured_image'   => __('Set parfume image', 'parfume-reviews'),
            'remove_featured_image' => __('Remove parfume image', 'parfume-reviews'),
            'use_featured_image'   => __('Use as parfume image', 'parfume-reviews'),
            'archives'             => __('Parfume Archives', 'parfume-reviews'),
            'insert_into_item'     => __('Insert into parfume', 'parfume-reviews'),
            'uploaded_to_this_item' => __('Uploaded to this parfume', 'parfume-reviews'),
            'filter_items_list'    => __('Filter parfumes list', 'parfume-reviews'),
            'items_list_navigation' => __('Parfumes list navigation', 'parfume-reviews'),
            'items_list'           => __('Parfumes list', 'parfume-reviews'),
        );

        return array(
            'labels'                => $labels,
            'public'                => true,
            'publicly_queryable'    => true,
            'show_ui'              => true,
            'show_in_menu'         => true,
            'query_var'            => true,
            'rewrite'              => array(
                'slug'       => $slug,
                'with_front' => false,
                'feeds'      => true,
                'pages'      => true,
            ),
            'capability_type'       => 'post',
            'has_archive'          => true,
            'hierarchical'         => false,
            'menu_position'        => 5,
            'menu_icon'            => 'dashicons-airplane',
            'supports'             => array(
                'title',
                'editor',
                'thumbnail',
                'excerpt',
                'comments',
                'custom-fields',
                'revisions',
                'author',
                'page-attributes',
            ),
            'show_in_rest'         => true,
            'rest_base'            => 'parfumes',
            'rest_controller_class' => 'WP_REST_Posts_Controller',
            'taxonomies'           => array(
                'marki',
                'gender', 
                'aroma_type',
                'season',
                'intensity',
                'notes',
                'perfumer',
            ),
            'can_export'           => true,
            'delete_with_user'     => false,
            'map_meta_cap'         => true,
            'archive_template'     => 'archive-parfume.php',
            'single_template'      => 'single-parfume.php',
        );
    }

    /**
     * Add admin columns for parfume post type
     *
     * @param array $columns Existing columns
     * @return array Modified columns
     */
    public function add_admin_columns($columns) {
        $new_columns = array();
        
        foreach ($columns as $key => $value) {
            $new_columns[$key] = $value;
            
            if ($key === 'title') {
                $new_columns['parfume_image'] = __('Image', 'parfume-reviews');
                $new_columns['parfume_brand'] = __('Brand', 'parfume-reviews');
                $new_columns['parfume_rating'] = __('Rating', 'parfume-reviews');
                $new_columns['parfume_gender'] = __('Gender', 'parfume-reviews');
                $new_columns['parfume_year'] = __('Year', 'parfume-reviews');
            }
        }
        
        return $new_columns;
    }

    /**
     * Admin column content
     *
     * @param string $column Column name
     * @param int $post_id Post ID
     */
    public function admin_column_content($column, $post_id) {
        switch ($column) {
            case 'parfume_image':
                if (has_post_thumbnail($post_id)) {
                    echo get_the_post_thumbnail($post_id, array(50, 50));
                } else {
                    echo '<span class="dashicons dashicons-format-image" style="color: #ccc; font-size: 50px;"></span>';
                }
                break;
                
            case 'parfume_brand':
                $brands = wp_get_post_terms($post_id, 'marki', array('fields' => 'names'));
                if (!empty($brands) && !is_wp_error($brands)) {
                    echo esc_html(implode(', ', $brands));
                } else {
                    echo '—';
                }
                break;
                
            case 'parfume_rating':
                $rating = get_post_meta($post_id, '_parfume_rating', true);
                if (!empty($rating)) {
                    echo '<div class="parfume-rating-stars">';
                    for ($i = 1; $i <= 5; $i++) {
                        $class = $i <= round(floatval($rating)) ? 'filled' : '';
                        echo '<span class="star ' . $class . '">★</span>';
                    }
                    echo '<br><small>(' . number_format(floatval($rating), 1) . '/5)</small>';
                    echo '</div>';
                } else {
                    echo '—';
                }
                break;
                
            case 'parfume_gender':
                $gender = get_post_meta($post_id, '_parfume_gender', true);
                if (!empty($gender)) {
                    echo esc_html($gender);
                } else {
                    $genders = wp_get_post_terms($post_id, 'gender', array('fields' => 'names'));
                    if (!empty($genders) && !is_wp_error($genders)) {
                        echo esc_html(implode(', ', $genders));
                    } else {
                        echo '—';
                    }
                }
                break;
                
            case 'parfume_year':
                $year = get_post_meta($post_id, '_parfume_release_year', true);
                echo !empty($year) ? esc_html($year) : '—';
                break;
        }
    }

    /**
     * Make columns sortable
     *
     * @param array $columns Sortable columns
     * @return array Modified sortable columns
     */
    public function sortable_columns($columns) {
        $columns['parfume_rating'] = 'parfume_rating';
        $columns['parfume_year'] = 'parfume_year';
        return $columns;
    }

    /**
     * Handle admin orderby
     *
     * @param WP_Query $query
     */
    public function admin_orderby($query) {
        if (!is_admin() || !$query->is_main_query()) {
            return;
        }

        $orderby = $query->get('orderby');

        if ($orderby === 'parfume_rating') {
            $query->set('meta_key', '_parfume_rating');
            $query->set('orderby', 'meta_value_num');
        } elseif ($orderby === 'parfume_year') {
            $query->set('meta_key', '_parfume_release_year');
            $query->set('orderby', 'meta_value_num');
        }
    }

    /**
     * Add row actions
     *
     * @param array $actions Existing actions
     * @param WP_Post $post Post object
     * @return array Modified actions
     */
    public function add_row_actions($actions, $post) {
        if ($post->post_type === $this->post_type) {
            $actions['duplicate'] = sprintf(
                '<a href="%s" aria-label="%s">%s</a>',
                wp_nonce_url(
                    admin_url('admin.php?action=duplicate_parfume&post_id=' . $post->ID),
                    'duplicate_parfume_' . $post->ID
                ),
                esc_attr__('Duplicate this parfume', 'parfume-reviews'),
                __('Duplicate', 'parfume-reviews')
            );
        }
        return $actions;
    }

    /**
     * Save meta data
     *
     * @param int $post_id Post ID
     * @param WP_Post $post Post object
     * @param bool $update Whether this is an existing post being updated
     */
    public function save_meta_data($post_id, $post, $update) {
        // Check if this is an autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // Check user permissions
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // Define meta fields with their validation rules
        $meta_fields = array(
            '_parfume_rating' => array(
                'type' => 'number',
                'min' => 0,
                'max' => 5,
                'step' => 0.1,
            ),
            '_parfume_gender' => array(
                'type' => 'text',
                'sanitize' => 'sanitize_text_field',
            ),
            '_parfume_release_year' => array(
                'type' => 'text',
                'sanitize' => 'sanitize_text_field',
            ),
            '_parfume_longevity' => array(
                'type' => 'text',
                'sanitize' => 'sanitize_text_field',
            ),
            '_parfume_sillage' => array(
                'type' => 'text',
                'sanitize' => 'sanitize_text_field',
            ),
            '_parfume_bottle_size' => array(
                'type' => 'text',
                'sanitize' => 'sanitize_text_field',
            ),
            '_parfume_freshness' => array(
                'type' => 'number',
                'min' => 0,
                'max' => 10,
                'step' => 1,
            ),
            '_parfume_sweetness' => array(
                'type' => 'number',
                'min' => 0,
                'max' => 10,
                'step' => 1,
            ),
            '_parfume_intensity' => array(
                'type' => 'number',
                'min' => 0,
                'max' => 10,
                'step' => 1,
            ),
            '_parfume_warmth' => array(
                'type' => 'number',
                'min' => 0,
                'max' => 10,
                'step' => 1,
            ),
            '_parfume_pros' => array(
                'type' => 'textarea',
                'sanitize' => 'sanitize_textarea_field',
            ),
            '_parfume_cons' => array(
                'type' => 'textarea',
                'sanitize' => 'sanitize_textarea_field',
            ),
        );

        // Save meta fields
        foreach ($meta_fields as $meta_key => $field_config) {
            if (isset($_POST[str_replace('_parfume_', 'parfume_', $meta_key)])) {
                $field_name = str_replace('_parfume_', 'parfume_', $meta_key);
                $value = $_POST[$field_name];

                // Sanitize based on field type
                if ($field_config['type'] === 'number') {
                    $value = floatval($value);
                    if (isset($field_config['min'])) {
                        $value = max($field_config['min'], $value);
                    }
                    if (isset($field_config['max'])) {
                        $value = min($field_config['max'], $value);
                    }
                } elseif (isset($field_config['sanitize'])) {
                    $value = call_user_func($field_config['sanitize'], $value);
                } else {
                    $value = sanitize_text_field($value);
                }

                update_post_meta($post_id, $meta_key, $value);
            }
        }

        // Save stores data
        if (isset($_POST['parfume_stores']) && is_array($_POST['parfume_stores'])) {
            $stores = array();
            
            foreach ($_POST['parfume_stores'] as $store_data) {
                if (empty($store_data['name'])) {
                    continue;
                }
                
                $store = array();
                $store_fields = array(
                    'name', 'logo', 'url', 'affiliate_url', 'affiliate_class', 
                    'affiliate_rel', 'affiliate_target', 'affiliate_anchor',
                    'promo_code', 'promo_text', 'price', 'size', 
                    'availability', 'shipping_cost'
                );
                
                foreach ($store_fields as $field) {
                    if (in_array($field, array('logo', 'url', 'affiliate_url'))) {
                        $store[$field] = !empty($store_data[$field]) ? esc_url_raw($store_data[$field]) : '';
                    } else {
                        $store[$field] = isset($store_data[$field]) ? sanitize_text_field($store_data[$field]) : '';
                    }
                }
                
                $store['last_updated'] = current_time('mysql');
                $stores[] = $store;
            }
            
            update_post_meta($post_id, '_parfume_stores', $stores);
        }
    }

    /**
     * Load single template
     *
     * @param string $template
     * @return string
     */
    public function load_single_template($template) {
        global $post;
        
        if ($post->post_type === $this->post_type) {
            $plugin_template = PARFUME_REVIEWS_PLUGIN_DIR . 'templates/single-parfume.php';
            if (file_exists($plugin_template)) {
                return $plugin_template;
            }
        }
        
        return $template;
    }

    /**
     * Load archive template
     *
     * @param string $template
     * @return string
     */
    public function load_archive_template($template) {
        if (is_post_type_archive($this->post_type)) {
            $plugin_template = PARFUME_REVIEWS_PLUGIN_DIR . 'templates/archive-parfume.php';
            if (file_exists($plugin_template)) {
                return $plugin_template;
            }
        }
        
        return $template;
    }

    /**
     * Enqueue frontend assets
     */
    public function enqueue_frontend_assets() {
        if (is_singular($this->post_type) || is_post_type_archive($this->post_type)) {
            // CSS
            wp_enqueue_style(
                'parfume-reviews-frontend',
                PARFUME_REVIEWS_PLUGIN_URL . 'frontend/assets/styles/grid.css',
                array(),
                PARFUME_REVIEWS_VERSION
            );
            
            wp_enqueue_style(
                'parfume-reviews-cards',
                PARFUME_REVIEWS_PLUGIN_URL . 'frontend/assets/styles/cards.css',
                array('parfume-reviews-frontend'),
                PARFUME_REVIEWS_VERSION
            );
            
            wp_enqueue_style(
                'parfume-reviews-responsive',
                PARFUME_REVIEWS_PLUGIN_URL . 'frontend/assets/styles/responsive.css',
                array('parfume-reviews-frontend'),
                PARFUME_REVIEWS_VERSION
            );
            
            // JavaScript
            wp_enqueue_script(
                'parfume-reviews-frontend',
                PARFUME_REVIEWS_PLUGIN_URL . 'frontend/assets/scripts/ui.js',
                array('jquery'),
                PARFUME_REVIEWS_VERSION,
                true
            );
            
            wp_localize_script('parfume-reviews-frontend', 'parfumeReviews', array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('parfume_reviews_nonce'),
                'strings' => array(
                    'loading' => __('Loading...', 'parfume-reviews'),
                    'error' => __('An error occurred', 'parfume-reviews'),
                ),
            ));
        }
    }

    /**
     * AJAX: Get related parfumes
     */
    public function ajax_get_related() {
        check_ajax_referer('parfume_reviews_nonce', 'nonce');
        
        $post_id = intval($_POST['post_id']);
        $limit = isset($_POST['limit']) ? intval($_POST['limit']) : 4;
        
        if (!$post_id) {
            wp_send_json_error(__('Invalid post ID', 'parfume-reviews'));
        }
        
        $related = $this->get_related_parfumes($post_id, $limit);
        
        if (empty($related)) {
            wp_send_json_error(__('No related parfumes found', 'parfume-reviews'));
        }
        
        wp_send_json_success($related);
    }

    /**
     * Get related parfumes
     *
     * @param int $post_id
     * @param int $limit
     * @return array
     */
    public function get_related_parfumes($post_id, $limit = 4) {
        // Get current parfume taxonomies
        $brands = wp_get_post_terms($post_id, 'marki', array('fields' => 'ids'));
        $notes = wp_get_post_terms($post_id, 'notes', array('fields' => 'ids'));
        $genders = wp_get_post_terms($post_id, 'gender', array('fields' => 'ids'));
        
        if (is_wp_error($brands)) $brands = array();
        if (is_wp_error($notes)) $notes = array();
        if (is_wp_error($genders)) $genders = array();
        
        $tax_query = array('relation' => 'OR');
        
        if (!empty($brands)) {
            $tax_query[] = array(
                'taxonomy' => 'marki',
                'field' => 'term_id',
                'terms' => $brands,
            );
        }
        
        if (!empty($notes)) {
            $tax_query[] = array(
                'taxonomy' => 'notes',
                'field' => 'term_id',
                'terms' => $notes,
            );
        }
        
        if (!empty($genders)) {
            $tax_query[] = array(
                'taxonomy' => 'gender',
                'field' => 'term_id',
                'terms' => $genders,
            );
        }
        
        if (count($tax_query) === 1) {
            return array(); // No taxonomies found
        }
        
        $args = array(
            'post_type' => $this->post_type,
            'posts_per_page' => $limit,
            'post__not_in' => array($post_id),
            'tax_query' => $tax_query,
            'meta_query' => array(
                array(
                    'key' => '_parfume_rating',
                    'compare' => 'EXISTS',
                ),
            ),
            'orderby' => 'meta_value_num',
            'order' => 'DESC',
        );
        
        $related_query = new \WP_Query($args);
        $related = array();
        
        if ($related_query->have_posts()) {
            while ($related_query->have_posts()) {
                $related_query->the_post();
                
                $parfume_data = array(
                    'id' => get_the_ID(),
                    'title' => get_the_title(),
                    'url' => get_permalink(),
                    'image' => get_the_post_thumbnail_url(get_the_ID(), 'medium'),
                    'rating' => get_post_meta(get_the_ID(), '_parfume_rating', true),
                    'brand' => '',
                );
                
                // Get brand
                $brand_terms = wp_get_post_terms(get_the_ID(), 'marki', array('fields' => 'names'));
                if (!empty($brand_terms) && !is_wp_error($brand_terms)) {
                    $parfume_data['brand'] = $brand_terms[0];
                }
                
                $related[] = $parfume_data;
            }
        }
        
        wp_reset_postdata();
        
        return $related;
    }

    /**
     * Get parfume statistics
     *
     * @return array
     */
    public static function get_statistics() {
        $stats = array();
        
        // Total parfumes
        $parfume_count = wp_count_posts('parfume');
        $stats['total_parfumes'] = $parfume_count->publish ?? 0;
        
        // Average rating
        global $wpdb;
        $avg_rating = $wpdb->get_var(
            "SELECT AVG(CAST(meta_value AS DECIMAL(3,2))) 
             FROM {$wpdb->postmeta} 
             WHERE meta_key = '_parfume_rating' 
             AND meta_value != '' 
             AND meta_value != '0'"
        );
        $stats['average_rating'] = $avg_rating ? round(floatval($avg_rating), 2) : 0;
        
        // Top rated parfume
        $top_rated = $wpdb->get_row(
            "SELECT p.ID, p.post_title, pm.meta_value as rating 
             FROM {$wpdb->posts} p 
             INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id 
             WHERE p.post_type = 'parfume' 
             AND p.post_status = 'publish' 
             AND pm.meta_key = '_parfume_rating' 
             AND pm.meta_value != '' 
             ORDER BY CAST(pm.meta_value AS DECIMAL(3,2)) DESC 
             LIMIT 1"
        );
        
        if ($top_rated) {
            $stats['top_rated'] = array(
                'id' => $top_rated->ID,
                'title' => $top_rated->post_title,
                'rating' => floatval($top_rated->rating),
                'url' => get_permalink($top_rated->ID),
            );
        }
        
        // Recent parfumes (last 30 days)
        $recent_count = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) 
                 FROM {$wpdb->posts} 
                 WHERE post_type = 'parfume' 
                 AND post_status = 'publish' 
                 AND post_date >= %s",
                date('Y-m-d H:i:s', strtotime('-30 days'))
            )
        );
        $stats['recent_parfumes'] = intval($recent_count);
        
        return $stats;
    }
}