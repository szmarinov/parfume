<?php
/**
 * Data Validation
 * 
 * @package ParfumeReviews
 * @subpackage Admin\ImportExport
 */

namespace ParfumeReviews\Admin\ImportExport;

if (!defined('ABSPATH')) {
    exit;
}

class Validation {
    
    /**
     * Validate import data
     */
    public static function validate_import_data($data) {
        $errors = array();
        $warnings = array();
        
        if (!is_array($data)) {
            $errors[] = __('Data must be an array of perfume objects.', 'parfume-reviews');
            return array('errors' => $errors, 'warnings' => $warnings);
        }
        
        foreach ($data as $index => $item) {
            $item_errors = self::validate_perfume_item($item, $index);
            $errors = array_merge($errors, $item_errors);
            
            $item_warnings = self::validate_perfume_warnings($item, $index);
            $warnings = array_merge($warnings, $item_warnings);
        }
        
        return array('errors' => $errors, 'warnings' => $warnings);
    }
    
    /**
     * Validate individual perfume item
     */
    private static function validate_perfume_item($item, $index) {
        $errors = array();
        
        // Required fields
        if (empty($item['title'])) {
            $errors[] = sprintf(__('Item %d: Title is required.', 'parfume-reviews'), $index + 1);
        }
        
        // Validate title format
        if (!empty($item['title']) && !is_string($item['title'])) {
            $errors[] = sprintf(__('Item %d: Title must be a string.', 'parfume-reviews'), $index + 1);
        }
        
        // Validate rating
        if (isset($item['rating'])) {
            if (!is_numeric($item['rating']) || $item['rating'] < 0 || $item['rating'] > 5) {
                $errors[] = sprintf(__('Item %d: Rating must be a number between 0 and 5.', 'parfume-reviews'), $index + 1);
            }
        }
        
        // Validate year
        if (!empty($item['release_year'])) {
            $year = intval($item['release_year']);
            if ($year < 1800 || $year > date('Y') + 5) {
                $errors[] = sprintf(__('Item %d: Release year must be between 1800 and %d.', 'parfume-reviews'), $index + 1, date('Y') + 5);
            }
        }
        
        // Validate URLs
        $url_fields = array('featured_image');
        foreach ($url_fields as $field) {
            if (!empty($item[$field]) && !filter_var($item[$field], FILTER_VALIDATE_URL)) {
                $errors[] = sprintf(__('Item %d: %s must be a valid URL.', 'parfume-reviews'), $index + 1, $field);
            }
        }
        
        // Validate aroma chart
        if (isset($item['aroma_chart']) && is_array($item['aroma_chart'])) {
            $chart_fields = array('freshness', 'sweetness', 'intensity', 'warmth');
            foreach ($chart_fields as $field) {
                if (isset($item['aroma_chart'][$field])) {
                    $value = intval($item['aroma_chart'][$field]);
                    if ($value < 0 || $value > 10) {
                        $errors[] = sprintf(__('Item %d: Aroma chart %s must be between 0 and 10.', 'parfume-reviews'), $index + 1, $field);
                    }
                }
            }
        }
        
        // Validate stores
        if (isset($item['stores']) && is_array($item['stores'])) {
            foreach ($item['stores'] as $store_index => $store) {
                $store_errors = self::validate_store_data($store, $index, $store_index);
                $errors = array_merge($errors, $store_errors);
            }
        }
        
        return $errors;
    }
    
    /**
     * Validate store data
     */
    private static function validate_store_data($store, $item_index, $store_index) {
        $errors = array();
        
        if (empty($store['name'])) {
            $errors[] = sprintf(__('Item %d, Store %d: Store name is required.', 'parfume-reviews'), $item_index + 1, $store_index + 1);
        }
        
        // Validate URLs
        $url_fields = array('logo', 'url', 'affiliate_url');
        foreach ($url_fields as $field) {
            if (!empty($store[$field]) && !filter_var($store[$field], FILTER_VALIDATE_URL)) {
                $errors[] = sprintf(__('Item %d, Store %d: %s must be a valid URL.', 'parfume-reviews'), $item_index + 1, $store_index + 1, $field);
            }
        }
        
        return $errors;
    }
    
    /**
     * Validate perfume warnings (non-critical issues)
     */
    private static function validate_perfume_warnings($item, $index) {
        $warnings = array();
        
        // Missing recommended fields
        $recommended_fields = array('content', 'excerpt', 'brand', 'notes');
        foreach ($recommended_fields as $field) {
            if (empty($item[$field])) {
                $warnings[] = sprintf(__('Item %d: Missing recommended field "%s".', 'parfume-reviews'), $index + 1, $field);
            }
        }
        
        // Check for potential duplicates
        if (!empty($item['title'])) {
            $existing = get_page_by_title($item['title'], OBJECT, 'parfume');
            if ($existing) {
                $warnings[] = sprintf(__('Item %d: A perfume with title "%s" already exists and will be updated.', 'parfume-reviews'), $index + 1, $item['title']);
            }
        }
        
        // Check image accessibility
        if (!empty($item['featured_image'])) {
            $response = wp_remote_head($item['featured_image']);
            if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
                $warnings[] = sprintf(__('Item %d: Featured image URL may not be accessible.', 'parfume-reviews'), $index + 1);
            }
        }
        
        return $warnings;
    }
    
    /**
     * Sanitize import data
     */
    public static function sanitize_import_data($data) {
        if (!is_array($data)) {
            return array();
        }
        
        $sanitized = array();
        
        foreach ($data as $item) {
            $sanitized_item = self::sanitize_perfume_item($item);
            if (!empty($sanitized_item)) {
                $sanitized[] = $sanitized_item;
            }
        }
        
        return $sanitized;
    }
    
    /**
     * Sanitize individual perfume item
     */
    private static function sanitize_perfume_item($item) {
        if (!is_array($item) || empty($item['title'])) {
            return false;
        }
        
        $sanitized = array();
        
        // Text fields
        $text_fields = array('title', 'gender_text', 'release_year', 'longevity', 'sillage', 'bottle_size');
        foreach ($text_fields as $field) {
            if (isset($item[$field])) {
                $sanitized[$field] = sanitize_text_field($item[$field]);
            }
        }
        
        // Rich text fields
        $rich_text_fields = array('content', 'pros', 'cons');
        foreach ($rich_text_fields as $field) {
            if (isset($item[$field])) {
                $sanitized[$field] = wp_kses_post($item[$field]);
            }
        }
        
        // Excerpt
        if (isset($item['excerpt'])) {
            $sanitized['excerpt'] = sanitize_textarea_field($item['excerpt']);
        }
        
        // Numeric fields
        if (isset($item['rating'])) {
            $sanitized['rating'] = max(0, min(5, floatval($item['rating'])));
        }
        
        // URL fields
        if (isset($item['featured_image'])) {
            $sanitized['featured_image'] = esc_url_raw($item['featured_image']);
        }
        
        // Aroma chart
        if (isset($item['aroma_chart']) && is_array($item['aroma_chart'])) {
            $sanitized['aroma_chart'] = array();
            $chart_fields = array('freshness', 'sweetness', 'intensity', 'warmth');
            
            foreach ($chart_fields as $field) {
                if (isset($item['aroma_chart'][$field])) {
                    $sanitized['aroma_chart'][$field] = max(0, min(10, intval($item['aroma_chart'][$field])));
                }
            }
        }
        
        // Taxonomies
        $taxonomy_fields = array('gender', 'brand', 'aroma_type', 'season', 'intensity', 'notes', 'perfumer');
        foreach ($taxonomy_fields as $field) {
            if (isset($item[$field]) && is_array($item[$field])) {
                $sanitized[$field] = array_map('sanitize_text_field', $item[$field]);
            }
        }
        
        // Stores
        if (isset($item['stores']) && is_array($item['stores'])) {
            $sanitized['stores'] = array();
            foreach ($item['stores'] as $store) {
                $sanitized_store = self::sanitize_store_data($store);
                if (!empty($sanitized_store)) {
                    $sanitized['stores'][] = $sanitized_store;
                }
            }
        }
        
        return $sanitized;
    }
    
    /**
     * Sanitize store data
     */
    private static function sanitize_store_data($store) {
        if (!is_array($store) || empty($store['name'])) {
            return false;
        }
        
        $sanitized = array();
        
        // Text fields
        $text_fields = array('name', 'affiliate_class', 'affiliate_rel', 'affiliate_target', 'affiliate_anchor', 'promo_code', 'promo_text', 'price', 'size', 'availability', 'shipping_cost');
        foreach ($text_fields as $field) {
            if (isset($store[$field])) {
                $sanitized[$field] = sanitize_text_field($store[$field]);
            }
        }
        
        // URL fields
        $url_fields = array('logo', 'url', 'affiliate_url');
        foreach ($url_fields as $field) {
            if (isset($store[$field])) {
                $sanitized[$field] = esc_url_raw($store[$field]);
            }
        }
        
        // Set default values
        $sanitized['affiliate_rel'] = !empty($sanitized['affiliate_rel']) ? $sanitized['affiliate_rel'] : 'nofollow';
        $sanitized['affiliate_target'] = !empty($sanitized['affiliate_target']) ? $sanitized['affiliate_target'] : '_blank';
        $sanitized['last_updated'] = current_time('mysql');
        
        return $sanitized;
    }
}