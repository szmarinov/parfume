<?php
/**
 * Helper Functions
 * Additional utility functions for the plugin
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * A-Z Navigation for Perfumers/Brands
 * Generates alphabet navigation with scroll functionality
 */
function parfume_az_navigation($taxonomy = 'brand', $show_cyrillic = true) {
    // Latin alphabet
    $latin = range('A', 'Z');
    
    // Cyrillic alphabet
    $cyrillic = ['А', 'Б', 'В', 'Г', 'Д', 'Е', 'Ж', 'З', 'И', 'Й', 'К', 'Л', 'М', 'Н', 'О', 'П', 'Р', 'С', 'Т', 'У', 'Ф', 'Х', 'Ц', 'Ч', 'Ш', 'Щ', 'Ъ', 'Ю', 'Я'];
    
    // Get all terms
    $terms = get_terms([
        'taxonomy' => $taxonomy,
        'hide_empty' => true
    ]);
    
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

/**
 * Format price
 */
function parfume_format_price($price, $currency = 'лв.') {
    return number_format(floatval($price), 2, '.', '') . ' ' . $currency;
}

/**
 * Format date
 */
function parfume_format_date($date, $format = 'd.m.Y') {
    return date_i18n($format, strtotime($date));
}

/**
 * Get rating stars HTML
 */
function parfume_get_rating_stars($rating, $max = 5) {
    $html = '<div class="rating-stars">';
    
    for ($i = 1; $i <= $max; $i++) {
        if ($i <= $rating) {
            $html .= '<span class="star filled">★</span>';
        } else {
            $html .= '<span class="star">☆</span>';
        }
    }
    
    $html .= '</div>';
    return $html;
}

/**
 * Generate breadcrumbs
 */
function parfume_breadcrumbs() {
    if (is_front_page()) {
        return;
    }
    
    $html = '<nav class="parfume-breadcrumbs">';
    $html .= '<a href="' . home_url('/') . '">Начало</a>';
    $html .= '<span class="separator">/</span>';
    
    if (is_post_type_archive('parfume')) {
        $html .= '<span class="current">Парфюми</span>';
    } elseif (is_singular('parfume')) {
        $html .= '<a href="' . get_post_type_archive_link('parfume') . '">Парфюми</a>';
        $html .= '<span class="separator">/</span>';
        $html .= '<span class="current">' . get_the_title() . '</span>';
    } elseif (is_tax()) {
        $term = get_queried_object();
        $html .= '<a href="' . get_post_type_archive_link('parfume') . '">Парфюми</a>';
        $html .= '<span class="separator">/</span>';
        $html .= '<span class="current">' . $term->name . '</span>';
    }
    
    $html .= '</nav>';
    return $html;
}

/**
 * Transliterate Cyrillic to Latin
 */
function parfume_transliterate($text) {
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
 * Get color for note group
 */
function parfume_get_note_group_color($group) {
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
    
    return isset($colors[$group]) ? $colors[$group] : '#999';
}

/**
 * Calculate reading time
 */
function parfume_reading_time($content) {
    $word_count = str_word_count(strip_tags($content));
    $minutes = ceil($word_count / 200); // 200 words per minute
    return $minutes;
}

/**
 * Import notes from JSON
 */
function parfume_import_notes_json($json_file) {
    if (!file_exists($json_file)) {
        return new WP_Error('file_not_found', 'JSON файлът не е намерен');
    }
    
    $json_data = file_get_contents($json_file);
    $notes = json_decode($json_data, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        return new WP_Error('invalid_json', 'Невалиден JSON формат');
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
 */
function parfume_export_notes_json() {
    $terms = get_terms([
        'taxonomy' => 'notes',
        'hide_empty' => false
    ]);
    
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