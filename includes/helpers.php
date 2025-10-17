<?php
/**
 * Helper Functions - UNIFIED VERSION
 * 
 * Global helper functions for templates
 * Contains all helper functions from both helpers.php and helper-functions.php
 * 
 * IMPORTANT: This file replaces both:
 * - includes/helpers.php (old version)
 * - includes/helpers/helper-functions.php (to be deleted)
 * 
 * @package Parfume_Reviews
 * @since 2.0.0
 * @version 2.0.1-unified
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Mark that helpers are loaded
define('PARFUME_REVIEWS_HELPERS_LOADED', true);

/* ==========================================================================
   RATING & DISPLAY FUNCTIONS
   ========================================================================== */

/**
 * Get rating stars HTML
 * 
 * Converts numeric rating (0-10) to star display (0-5 stars)
 * 
 * @param float $rating Rating value (0-10)
 * @param bool $show_empty Whether to show empty stars
 * @return string HTML for stars
 */
function parfume_reviews_get_rating_stars($rating, $show_empty = true) {
    $rating = floatval($rating);
    $stars_count = $rating / 2; // Convert 0-10 to 0-5
    $full_stars = floor($stars_count);
    $half_star = ($stars_count - $full_stars) >= 0.5;
    $empty_stars = 5 - $full_stars - ($half_star ? 1 : 0);
    
    $html = '<span class="rating-stars">';
    
    // Full stars
    for ($i = 0; $i < $full_stars; $i++) {
        $html .= '<span class="star star-full">★</span>';
    }
    
    // Half star
    if ($half_star) {
        $html .= '<span class="star star-half">★</span>';
    }
    
    // Empty stars
    if ($show_empty) {
        for ($i = 0; $i < $empty_stars; $i++) {
            $html .= '<span class="star star-empty">☆</span>';
        }
    }
    
    $html .= '</span>';
    
    return $html;
}

/**
 * Display parfume card
 * 
 * @param int $post_id Post ID
 */
function parfume_reviews_display_parfume_card($post_id) {
    $template_loader = new \ParfumeReviews\Templates\Loader(
        \ParfumeReviews\Core\Plugin::get_instance()->get_container()
    );
    
    $template_loader->get_template_part('parts/parfume-card', null, ['post_id' => $post_id]);
}

/* ==========================================================================
   PRICE & FORMATTING FUNCTIONS
   ========================================================================== */

/**
 * Format price
 * 
 * @param float $price Price value
 * @param string $currency Currency code (default: BGN)
 * @return string Formatted price
 */
function parfume_reviews_format_price($price, $currency = 'BGN') {
    $price = floatval($price);
    
    $formatted = number_format($price, 2, '.', ' ');
    
    return apply_filters('parfume_reviews_format_price', $formatted . ' ' . $currency, $price, $currency);
}

/**
 * Format date
 * 
 * @param string $date Date string
 * @param string $format Date format (default: d.m.Y)
 * @return string Formatted date
 */
function parfume_reviews_format_date($date, $format = 'd.m.Y') {
    return date_i18n($format, strtotime($date));
}

/* ==========================================================================
   BREADCRUMBS FUNCTIONS
   ========================================================================== */

/**
 * Get breadcrumbs array
 * 
 * @return array Breadcrumb items
 */
function parfume_reviews_get_breadcrumbs() {
    $breadcrumbs = [];
    
    // Home
    $breadcrumbs[] = [
        'title' => __('Начало', 'parfume-reviews'),
        'url' => home_url('/')
    ];
    
    // Parfume archive
    $settings = get_option('parfume_reviews_settings', []);
    $parfume_slug = isset($settings['parfume_slug']) ? $settings['parfume_slug'] : 'parfiumi';
    
    $breadcrumbs[] = [
        'title' => __('Парфюми', 'parfume-reviews'),
        'url' => home_url('/' . $parfume_slug . '/')
    ];
    
    // Current page context
    if (is_singular('parfume')) {
        // Add brand if exists
        $brands = wp_get_post_terms(get_the_ID(), 'marki');
        if (!empty($brands)) {
            $breadcrumbs[] = [
                'title' => $brands[0]->name,
                'url' => get_term_link($brands[0])
            ];
        }
        
        // Current parfume (no URL for current page)
        $breadcrumbs[] = [
            'title' => get_the_title(),
            'url' => ''
        ];
    } elseif (is_tax()) {
        $term = get_queried_object();
        $taxonomy = get_taxonomy($term->taxonomy);
        
        // Add taxonomy archive
        $breadcrumbs[] = [
            'title' => $taxonomy->labels->name,
            'url' => home_url('/' . $parfume_slug . '/' . $term->taxonomy . '/')
        ];
        
        // Current term (no URL for current page)
        $breadcrumbs[] = [
            'title' => $term->name,
            'url' => ''
        ];
    }
    
    return apply_filters('parfume_reviews_breadcrumbs', $breadcrumbs);
}

/**
 * Display breadcrumbs HTML
 * 
 * @return string Breadcrumbs HTML
 */
function parfume_reviews_breadcrumbs() {
    if (is_front_page()) {
        return '';
    }
    
    $breadcrumbs = parfume_reviews_get_breadcrumbs();
    
    if (empty($breadcrumbs)) {
        return '';
    }
    
    $html = '<nav class="parfume-breadcrumbs" aria-label="' . esc_attr__('Навигация', 'parfume-reviews') . '">';
    $html .= '<ol class="breadcrumb-list">';
    
    foreach ($breadcrumbs as $index => $crumb) {
        $is_last = ($index === count($breadcrumbs) - 1);
        
        $html .= '<li class="breadcrumb-item' . ($is_last ? ' active' : '') . '">';
        
        if (!empty($crumb['url']) && !$is_last) {
            $html .= '<a href="' . esc_url($crumb['url']) . '">' . esc_html($crumb['title']) . '</a>';
        } else {
            $html .= '<span>' . esc_html($crumb['title']) . '</span>';
        }
        
        if (!$is_last) {
            $html .= '<span class="separator">/</span>';
        }
        
        $html .= '</li>';
    }
    
    $html .= '</ol>';
    $html .= '</nav>';
    
    return $html;
}

/* ==========================================================================
   A-Z NAVIGATION
   ========================================================================== */

/**
 * A-Z Navigation for Perfumers/Brands
 * Generates alphabet navigation with scroll functionality
 * 
 * @param string $taxonomy Taxonomy name (default: marki)
 * @param bool $show_cyrillic Show Cyrillic alphabet (default: true)
 * @return string HTML for A-Z navigation
 */
function parfume_reviews_az_navigation($taxonomy = 'marki', $show_cyrillic = true) {
    // Latin alphabet
    $latin = range('A', 'Z');
    
    // Cyrillic alphabet
    $cyrillic = ['А', 'Б', 'В', 'Г', 'Д', 'Е', 'Ж', 'З', 'И', 'Й', 'К', 'Л', 'М', 'Н', 'О', 'П', 'Р', 'С', 'Т', 'У', 'Ф', 'Х', 'Ц', 'Ч', 'Ш', 'Щ', 'Ъ', 'Ю', 'Я'];
    
    // Get all terms
    $terms = get_terms([
        'taxonomy' => $taxonomy,
        'hide_empty' => true
    ]);
    
    if (is_wp_error($terms) || empty($terms)) {
        return '';
    }
    
    // Group terms by first letter
    $grouped = [];
    foreach ($terms as $term) {
        $first_letter = mb_strtoupper(mb_substr($term->name, 0, 1));
        if (!isset($grouped[$first_letter])) {
            $grouped[$first_letter] = [];
        }
        $grouped[$first_letter][] = $term;
    }
    
    // Generate navigation HTML
    $html = '<div class="az-navigation">';
    
    // Latin letters
    foreach ($latin as $letter) {
        $active = isset($grouped[$letter]) ? 'active' : 'disabled';
        $html .= sprintf(
            '<a href="#letter-%s" class="az-letter %s" data-letter="%s">%s</a>',
            $letter,
            $active,
            $letter,
            $letter
        );
    }
    
    // Cyrillic letters
    if ($show_cyrillic) {
        $html .= '<span class="az-separator"></span>';
        foreach ($cyrillic as $letter) {
            $active = isset($grouped[$letter]) ? 'active' : 'disabled';
            $html .= sprintf(
                '<a href="#letter-%s" class="az-letter %s" data-letter="%s">%s</a>',
                urlencode($letter),
                $active,
                $letter,
                $letter
            );
        }
    }
    
    $html .= '</div>';
    
    // Generate grouped list HTML
    $html .= '<div class="az-list">';
    
    $all_letters = $show_cyrillic ? array_merge($latin, $cyrillic) : $latin;
    
    foreach ($all_letters as $letter) {
        if (isset($grouped[$letter])) {
            $html .= sprintf('<h2 id="letter-%s" class="az-group-title">%s</h2>', urlencode($letter), $letter);
            $html .= '<div class="az-group-items">';
            
            foreach ($grouped[$letter] as $term) {
                $html .= sprintf(
                    '<div class="az-item"><a href="%s">%s</a></div>',
                    get_term_link($term),
                    esc_html($term->name)
                );
            }
            
            $html .= '</div>';
        }
    }
    
    $html .= '</div>';
    
    // Add JavaScript for smooth scroll
    $html .= '
    <script>
    document.addEventListener("DOMContentLoaded", function() {
        document.querySelectorAll(".az-letter.active").forEach(function(letter) {
            letter.addEventListener("click", function(e) {
                e.preventDefault();
                var target = document.querySelector(this.getAttribute("href"));
                if (target) {
                    target.scrollIntoView({behavior: "smooth", block: "start"});
                }
            });
        });
    });
    </script>
    ';
    
    return $html;
}

/* ==========================================================================
   TEXT UTILITIES
   ========================================================================== */

/**
 * Transliterate Cyrillic to Latin
 * 
 * @param string $text Text to transliterate
 * @return string Transliterated text
 */
function parfume_reviews_transliterate($text) {
    $cyrillic = [
        'а', 'б', 'в', 'г', 'д', 'е', 'ж', 'з', 'и', 'й', 'к', 'л', 'м', 'н', 'о', 'п',
        'р', 'с', 'т', 'у', 'ф', 'х', 'ц', 'ч', 'ш', 'щ', 'ъ', 'ь', 'ю', 'я',
        'А', 'Б', 'В', 'Г', 'Д', 'Е', 'Ж', 'З', 'И', 'Й', 'К', 'Л', 'М', 'Н', 'О', 'П',
        'Р', 'С', 'Т', 'У', 'Ф', 'Х', 'Ц', 'Ч', 'Ш', 'Щ', 'Ъ', 'Ь', 'Ю', 'Я'
    ];
    
    $latin = [
        'a', 'b', 'v', 'g', 'd', 'e', 'zh', 'z', 'i', 'y', 'k', 'l', 'm', 'n', 'o', 'p',
        'r', 's', 't', 'u', 'f', 'h', 'ts', 'ch', 'sh', 'sht', 'a', 'y', 'yu', 'ya',
        'A', 'B', 'V', 'G', 'D', 'E', 'Zh', 'Z', 'I', 'Y', 'K', 'L', 'M', 'N', 'O', 'P',
        'R', 'S', 'T', 'U', 'F', 'H', 'Ts', 'Ch', 'Sh', 'Sht', 'A', 'Y', 'Yu', 'Ya'
    ];
    
    return str_replace($cyrillic, $latin, $text);
}

/**
 * Calculate reading time
 * 
 * @param string $content Content to analyze
 * @return int Reading time in minutes
 */
function parfume_reviews_reading_time($content) {
    $word_count = str_word_count(strip_tags($content));
    $minutes = ceil($word_count / 200); // 200 words per minute
    return $minutes;
}

/* ==========================================================================
   NOTES FUNCTIONS
   ========================================================================== */

/**
 * Get color for note group
 * 
 * @param string $group Note group name
 * @return string Hex color code
 */
function parfume_reviews_get_note_group_color($group) {
    $colors = [
        'дървесни' => '#8B4513',
        'цветни' => '#FF69B4',
        'ориенталски' => '#DAA520',
        'плодови' => '#FF6347',
        'зелени' => '#32CD32',
        'гурме' => '#D2691E',
        'морски' => '#4682B4',
        'ароматни' => '#9370DB'
    ];
    
    return isset($colors[strtolower($group)]) ? $colors[strtolower($group)] : '#999';
}

/**
 * Import notes from JSON
 * 
 * @param string $json_file Path to JSON file
 * @return int|WP_Error Number of imported notes or error
 */
function parfume_reviews_import_notes_json($json_file) {
    if (!file_exists($json_file)) {
        return new WP_Error('file_not_found', __('JSON файлът не е намерен', 'parfume-reviews'));
    }
    
    $json_data = file_get_contents($json_file);
    $notes = json_decode($json_data, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        return new WP_Error('invalid_json', __('Невалиден JSON формат', 'parfume-reviews'));
    }
    
    $imported = 0;
    
    foreach ($notes as $note_data) {
        if (empty($note_data['note']) || empty($note_data['group'])) {
            continue;
        }
        
        // Check if term exists
        $term = term_exists($note_data['note'], 'notes');
        
        if (!$term) {
            // Create new term
            $term = wp_insert_term($note_data['note'], 'notes');
            
            if (!is_wp_error($term)) {
                // Add group meta
                update_term_meta($term['term_id'], 'note_group', $note_data['group']);
                $imported++;
            }
        } else {
            // Update existing term's group
            update_term_meta($term['term_id'], 'note_group', $note_data['group']);
        }
    }
    
    return $imported;
}

/**
 * Export notes to JSON
 * 
 * @return string JSON string
 */
function parfume_reviews_export_notes_json() {
    $terms = get_terms([
        'taxonomy' => 'notes',
        'hide_empty' => false
    ]);
    
    if (is_wp_error($terms)) {
        return json_encode([]);
    }
    
    $notes = [];
    
    foreach ($terms as $term) {
        $group = get_term_meta($term->term_id, 'note_group', true);
        
        $notes[] = [
            'note' => $term->name,
            'group' => $group ?: ''
        ];
    }
    
    return json_encode($notes, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}

/* ==========================================================================
   TEMPLATE PART LOADER
   ========================================================================== */

/**
 * Get template part (replaces get_template_part for plugin templates)
 * 
 * @param string $slug Template slug
 * @param string $name Template name (optional)
 * @param array $args Arguments to pass to template
 */
function parfume_reviews_get_template_part($slug, $name = null, $args = []) {
    $template_loader = new \ParfumeReviews\Templates\Loader(
        \ParfumeReviews\Core\Plugin::get_instance()->get_container()
    );
    
    $template_loader->get_template_part($slug, $name, $args);
}

/* ==========================================================================
   BACKWARDS COMPATIBILITY ALIASES
   ========================================================================== */

/**
 * Short aliases for common functions (backwards compatibility)
 */
if (!function_exists('parfume_format_price')) {
    function parfume_format_price($price, $currency = 'лв.') {
        return parfume_reviews_format_price($price, $currency);
    }
}

if (!function_exists('parfume_get_rating_stars')) {
    function parfume_get_rating_stars($rating, $show_empty = true) {
        return parfume_reviews_get_rating_stars($rating, $show_empty);
    }
}

if (!function_exists('parfume_format_date')) {
    function parfume_format_date($date, $format = 'd.m.Y') {
        return parfume_reviews_format_date($date, $format);
    }
}

if (!function_exists('parfume_breadcrumbs')) {
    function parfume_breadcrumbs() {
        echo parfume_reviews_breadcrumbs();
    }
}