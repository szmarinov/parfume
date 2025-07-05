<?php
/**
 * Export Logic
 * 
 * @package ParfumeReviews
 * @subpackage Admin\ImportExport
 */

namespace ParfumeReviews\Admin\ImportExport;

if (!defined('ABSPATH')) {
    exit;
}

class Export {
    
    public function __construct() {
        add_action('admin_init', array($this, 'handle_export'));
        add_action('wp_ajax_export_perfumes', array($this, 'ajax_export_perfumes'));
    }
    
    /**
     * Handle export request
     */
    public function handle_export() {
        if (!isset($_GET['parfume_export']) || !wp_verify_nonce($_GET['_wpnonce'], 'parfume_export')) {
            return;
        }
        
        if (!current_user_can('edit_posts')) {
            wp_die(__('Insufficient permissions.', 'parfume-reviews'));
        }
        
        $export_type = sanitize_text_field($_GET['parfume_export']);
        
        switch ($export_type) {
            case 'all':
                $this->export_all_perfumes();
                break;
                
            case 'selected':
                $this->export_selected_perfumes();
                break;
                
            case 'by_brand':
                $this->export_by_brand();
                break;
                
            default:
                $this->export_all_perfumes();
                break;
        }
    }
    
    /**
     * Export all perfumes
     */
    public function export_all_perfumes() {
        $args = array(
            'post_type' => 'parfume',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'orderby' => 'date',
            'order' => 'DESC',
        );
        
        $perfumes = $this->get_perfumes_data($args);
        $this->send_json_download($perfumes, 'parfume-export-all');
    }
    
    /**
     * Export selected perfumes
     */
    public function export_selected_perfumes() {
        $post_ids = isset($_GET['post_ids']) ? array_map('intval', $_GET['post_ids']) : array();
        
        if (empty($post_ids)) {
            wp_die(__('No perfumes selected for export.', 'parfume-reviews'));
        }
        
        $args = array(
            'post_type' => 'parfume',
            'post__in' => $post_ids,
            'posts_per_page' => -1,
            'orderby' => 'date',
            'order' => 'DESC',
        );
        
        $perfumes = $this->get_perfumes_data($args);
        $this->send_json_download($perfumes, 'parfume-export-selected');
    }
    
    /**
     * Export by brand
     */
    public function export_by_brand() {
        $brand_slug = isset($_GET['brand']) ? sanitize_text_field($_GET['brand']) : '';
        
        if (empty($brand_slug)) {
            wp_die(__('No brand specified for export.', 'parfume-reviews'));
        }
        
        $args = array(
            'post_type' => 'parfume',
            'posts_per_page' => -1,
            'tax_query' => array(
                array(
                    'taxonomy' => 'marki',
                    'field' => 'slug',
                    'terms' => $brand_slug,
                ),
            ),
        );
        
        $perfumes = $this->get_perfumes_data($args);
        $brand_name = str_replace('-', '_', $brand_slug);
        $this->send_json_download($perfumes, "parfume-export-{$brand_name}");
    }
    
    /**
     * Get perfumes data for export
     */
    private function get_perfumes_data($args) {
        $query = new \WP_Query($args);
        $data = array();
        
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $post_id = get_the_ID();
                
                $item = array(
                    'title' => get_the_title(),
                    'content' => get_the_content(),
                    'excerpt' => get_the_excerpt(),
                    'rating' => floatval(get_post_meta($post_id, '_parfume_rating', true)),
                    'gender_text' => get_post_meta($post_id, '_parfume_gender', true),
                    'release_year' => get_post_meta($post_id, '_parfume_release_year', true),
                    'longevity' => get_post_meta($post_id, '_parfume_longevity', true),
                    'sillage' => get_post_meta($post_id, '_parfume_sillage', true),
                    'bottle_size' => get_post_meta($post_id, '_parfume_bottle_size', true),
                    'aroma_chart' => array(
                        'freshness' => intval(get_post_meta($post_id, '_parfume_freshness', true)),
                        'sweetness' => intval(get_post_meta($post_id, '_parfume_sweetness', true)),
                        'intensity' => intval(get_post_meta($post_id, '_parfume_intensity', true)),
                        'warmth' => intval(get_post_meta($post_id, '_parfume_warmth', true)),
                    ),
                    'pros' => get_post_meta($post_id, '_parfume_pros', true),
                    'cons' => get_post_meta($post_id, '_parfume_cons', true),
                );
                
                // Add featured image
                if (has_post_thumbnail($post_id)) {
                    $item['featured_image'] = get_the_post_thumbnail_url($post_id, 'full');
                }
                
                // Add taxonomies
                $this->add_taxonomy_data($item, $post_id);
                
                // Add stores
                $stores = get_post_meta($post_id, '_parfume_stores', true);
                if (!empty($stores) && is_array($stores)) {
                    $item['stores'] = $stores;
                }
                
                $data[] = $item;
            }
        }
        
        wp_reset_postdata();
        return $data;
    }
    
    /**
     * Add taxonomy data to export item
     */
    private function add_taxonomy_data(&$item, $post_id) {
        $taxonomies = array('gender', 'aroma_type', 'marki', 'season', 'intensity', 'notes', 'perfumer');
        
        foreach ($taxonomies as $taxonomy) {
            if (taxonomy_exists($taxonomy)) {
                $terms = wp_get_post_terms($post_id, $taxonomy, array('fields' => 'names'));
                if (!empty($terms) && !is_wp_error($terms)) {
                    $key = ($taxonomy === 'marki') ? 'brand' : $taxonomy;
                    $item[$key] = $terms;
                }
            }
        }
    }
    
    /**
     * Send JSON file for download
     */
    private function send_json_download($data, $filename_prefix) {
        $filename = $filename_prefix . '-' . date('Y-m-d-H-i-s') . '.json';
        
        // Set headers
        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        // Output JSON
        echo wp_json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    /**
     * AJAX export handler
     */
    public function ajax_export_perfumes() {
        check_ajax_referer('parfume_export_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(__('Insufficient permissions.', 'parfume-reviews'));
        }
        
        $export_type = sanitize_text_field($_POST['export_type']);
        $options = isset($_POST['options']) ? $_POST['options'] : array();
        
        try {
            switch ($export_type) {
                case 'all':
                    $data = $this->get_perfumes_data(array(
                        'post_type' => 'parfume',
                        'posts_per_page' => -1,
                        'post_status' => 'publish',
                    ));
                    break;
                    
                case 'filtered':
                    $data = $this->get_filtered_export_data($options);
                    break;
                    
                default:
                    throw new \Exception(__('Invalid export type.', 'parfume-reviews'));
            }
            
            wp_send_json_success(array(
                'data' => $data,
                'count' => count($data),
                'download_url' => $this->create_temp_download_link($data, $export_type),
            ));
            
        } catch (\Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }
    
    /**
     * Get filtered export data
     */
    private function get_filtered_export_data($options) {
        $args = array(
            'post_type' => 'parfume',
            'posts_per_page' => -1,
            'post_status' => 'publish',
        );
        
        // Add taxonomy filters
        if (!empty($options['taxonomies'])) {
            $tax_query = array('relation' => 'AND');
            
            foreach ($options['taxonomies'] as $taxonomy => $terms) {
                if (!empty($terms)) {
                    $tax_query[] = array(
                        'taxonomy' => $taxonomy,
                        'field' => 'slug',
                        'terms' => $terms,
                    );
                }
            }
            
            if (count($tax_query) > 1) {
                $args['tax_query'] = $tax_query;
            }
        }
        
        // Add date filters
        if (!empty($options['date_from']) || !empty($options['date_to'])) {
            $date_query = array();
            
            if (!empty($options['date_from'])) {
                $date_query['after'] = $options['date_from'];
            }
            
            if (!empty($options['date_to'])) {
                $date_query['before'] = $options['date_to'];
            }
            
            $args['date_query'] = array($date_query);
        }
        
        return $this->get_perfumes_data($args);
    }
    
    /**
     * Create temporary download link
     */
    private function create_temp_download_link($data, $type) {
        // Store data temporarily and return download URL
        $temp_key = wp_generate_password(32, false);
        set_transient('parfume_export_' . $temp_key, $data, 3600); // 1 hour
        
        return add_query_arg(array(
            'action' => 'download_temp_export',
            'key' => $temp_key,
            'nonce' => wp_create_nonce('download_export_' . $temp_key),
        ), admin_url('admin-ajax.php'));
    }
}