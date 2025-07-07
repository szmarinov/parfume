<?php
/**
 * Модул за сравняване на парфюми
 * 
 * @package Parfume_Catalog
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Parfume_Catalog_Comparison {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_ajax_pc_add_to_comparison', array($this, 'add_to_comparison'));
        add_action('wp_ajax_nopriv_pc_add_to_comparison', array($this, 'add_to_comparison'));
        add_action('wp_ajax_pc_remove_from_comparison', array($this, 'remove_from_comparison'));
        add_action('wp_ajax_nopriv_pc_remove_from_comparison', array($this, 'remove_from_comparison'));
        add_action('wp_ajax_pc_get_comparison_data', array($this, 'get_comparison_data'));
        add_action('wp_ajax_nopriv_pc_get_comparison_data', array($this, 'get_comparison_data'));
        add_action('wp_ajax_pc_search_parfumes', array($this, 'search_parfumes'));
        add_action('wp_ajax_nopriv_pc_search_parfumes', array($this, 'search_parfumes'));
        add_shortcode('pc_comparison_button', array($this, 'comparison_button_shortcode'));
    }
    
    public function init() {
        // Проверка дали функционалността е активна
        $options = get_option('parfume_catalog_settings', array());
        if (empty($options['comparison_enabled'])) {
            return;
        }
        
        // Добавяне на бутони за сравнение в архивните страници
        add_action('pc_after_parfume_excerpt', array($this, 'add_comparison_button'));
        
        // Добавяне на floating comparison widget
        add_action('wp_footer', array($this, 'add_comparison_widget'));
    }
    
    /**
     * Добавяне към сравнение
     */
    public function add_to_comparison() {
        check_ajax_referer('pc_nonce', 'nonce');
        
        $parfume_id = intval($_POST['parfume_id']);
        
        if (!$parfume_id || get_post_type($parfume_id) !== 'parfumes') {
            wp_send_json_error(__('Невалиден парфюм', 'parfume-catalog'));
        }
        
        // Проверка за максимален брой
        $max_items = $this->get_max_comparison_items();
        $current_items = isset($_POST['current_items']) ? intval($_POST['current_items']) : 0;
        
        if ($current_items >= $max_items) {
            wp_send_json_error(sprintf(__('Максималният брой парфюми за сравнение е %d', 'parfume-catalog'), $max_items));
        }
        
        wp_send_json_success(array(
            'message' => __('Парфюмът е добавен за сравнение', 'parfume-catalog'),
            'parfume_id' => $parfume_id,
            'parfume_data' => $this->get_parfume_comparison_data($parfume_id)
        ));
    }
    
    /**
     * Премахване от сравнение
     */
    public function remove_from_comparison() {
        check_ajax_referer('pc_nonce', 'nonce');
        
        $parfume_id = intval($_POST['parfume_id']);
        
        if (!$parfume_id) {
            wp_send_json_error(__('Невалиден парфюм', 'parfume-catalog'));
        }
        
        wp_send_json_success(array(
            'message' => __('Парфюмът е премахнат от сравнение', 'parfume-catalog'),
            'parfume_id' => $parfume_id
        ));
    }
    
    /**
     * Получаване на данни за сравнение
     */
    public function get_comparison_data() {
        check_ajax_referer('pc_nonce', 'nonce');
        
        $parfume_ids = isset($_POST['parfume_ids']) ? array_map('intval', $_POST['parfume_ids']) : array();
        
        if (empty($parfume_ids)) {
            wp_send_json_error(__('Няма избрани парфюми', 'parfume-catalog'));
        }
        
        $comparison_data = array();
        foreach ($parfume_ids as $parfume_id) {
            $comparison_data[] = $this->get_parfume_comparison_data($parfume_id);
        }
        
        wp_send_json_success($comparison_data);
    }
    
    /**
     * Търсене на парфюми
     */
    public function search_parfumes() {
        check_ajax_referer('pc_nonce', 'nonce');
        
        $search_term = sanitize_text_field($_POST['search_term']);
        
        if (strlen($search_term) < 2) {
            wp_send_json_error(__('Въведете поне 2 символа', 'parfume-catalog'));
        }
        
        $query = new WP_Query(array(
            'post_type' => 'parfumes',
            'posts_per_page' => 10,
            's' => $search_term,
            'post_status' => 'publish'
        ));
        
        $results = array();
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $results[] = array(
                    'id' => get_the_ID(),
                    'title' => get_the_title(),
                    'url' => get_permalink(),
                    'image' => get_the_post_thumbnail_url(get_the_ID(), 'thumbnail')
                );
            }
            wp_reset_postdata();
        }
        
        wp_send_json_success($results);
    }
    
    /**
     * Shortcode за бутон за сравнение
     */
    public function comparison_button_shortcode($atts) {
        $atts = shortcode_atts(array(
            'parfume_id' => get_the_ID(),
            'text' => __('Добави за сравнение', 'parfume-catalog'),
            'class' => 'pc-comparison-btn'
        ), $atts);
        
        return $this->get_comparison_button($atts['parfume_id'], $atts['text'], $atts['class']);
    }
    
    /**
     * Добавяне на бутон за сравнение
     */
    public function add_comparison_button() {
        echo $this->get_comparison_button();
    }
    
    /**
     * Генериране на бутон за сравнение
     */
    public function get_comparison_button($parfume_id = null, $text = null, $class = 'pc-comparison-btn') {
        if (!$parfume_id) {
            $parfume_id = get_the_ID();
        }
        
        if (!$text) {
            $text = __('Добави за сравнение', 'parfume-catalog');
        }
        
        if (get_post_type($parfume_id) !== 'parfumes') {
            return '';
        }
        
        ob_start();
        ?>
        <button type="button" 
                class="<?php echo esc_attr($class); ?>" 
                data-parfume-id="<?php echo esc_attr($parfume_id); ?>"
                data-added-text="<?php echo esc_attr(__('Премахни от сравнение', 'parfume-catalog')); ?>"
                data-original-text="<?php echo esc_attr($text); ?>">
            <span class="text"><?php echo esc_html($text); ?></span>
            <span class="icon">+</span>
        </button>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Добавяне на comparison widget
     */
    public function add_comparison_widget() {
        $options = get_option('parfume_catalog_settings', array());
        if (empty($options['comparison_enabled'])) {
            return;
        }
        ?>
        <div id="pc-comparison-widget" class="pc-comparison-widget" style="display: none;">
            <div class="pc-comparison-header">
                <h3><?php _e('Сравнение на парфюми', 'parfume-catalog'); ?></h3>
                <button type="button" class="pc-comparison-close">&times;</button>
            </div>
            
            <div class="pc-comparison-search">
                <input type="text" id="pc-comparison-search" placeholder="<?php _e('Търсене на парфюми...', 'parfume-catalog'); ?>">
                <div id="pc-comparison-search-results"></div>
            </div>
            
            <div class="pc-comparison-content">
                <div class="pc-comparison-empty">
                    <p><?php _e('Добавете парфюми за сравнение', 'parfume-catalog'); ?></p>
                </div>
                
                <div class="pc-comparison-table-container" style="display: none;">
                    <table class="pc-comparison-table">
                        <thead>
                            <tr id="pc-comparison-header-row"></tr>
                        </thead>
                        <tbody id="pc-comparison-body"></tbody>
                    </table>
                </div>
            </div>
            
            <div class="pc-comparison-actions">
                <button type="button" class="button" id="pc-comparison-clear"><?php _e('Изчисти всички', 'parfume-catalog'); ?></button>
                <button type="button" class="button button-primary" id="pc-comparison-compare" style="display: none;">
                    <?php _e('Сравни', 'parfume-catalog'); ?>
                </button>
            </div>
        </div>
        
        <div id="pc-comparison-popup-overlay" class="pc-comparison-popup-overlay" style="display: none;">
            <div class="pc-comparison-popup">
                <div class="pc-comparison-popup-header">
                    <h2><?php _e('Сравнение на парфюми', 'parfume-catalog'); ?></h2>
                    <button type="button" class="pc-comparison-popup-close">&times;</button>
                </div>
                <div class="pc-comparison-popup-content">
                    <!-- Comparison table will be loaded here -->
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Получаване на данни за парфюм за сравнение
     */
    private function get_parfume_comparison_data($parfume_id) {
        $post = get_post($parfume_id);
        
        if (!$post || $post->post_type !== 'parfumes') {
            return false;
        }
        
        // Получаване на характеристики
        $durability = get_post_meta($parfume_id, '_durability', true);
        $sillage = get_post_meta($parfume_id, '_sillage', true);
        $gender = get_post_meta($parfume_id, '_gender', true);
        $price_range = get_post_meta($parfume_id, '_price_range', true);
        
        // Получаване на нотки
        $top_notes = get_post_meta($parfume_id, '_top_notes', true);
        $middle_notes = get_post_meta($parfume_id, '_middle_notes', true);
        $base_notes = get_post_meta($parfume_id, '_base_notes', true);
        
        // Получаване на таксономии
        $brands = wp_get_post_terms($parfume_id, 'marki', array('fields' => 'names'));
        $types = wp_get_post_terms($parfume_id, 'tip', array('fields' => 'names'));
        $aromat_types = wp_get_post_terms($parfume_id, 'vid_aromat', array('fields' => 'names'));
        $seasons = wp_get_post_terms($parfume_id, 'sezon', array('fields' => 'names'));
        $intensities = wp_get_post_terms($parfume_id, 'intenzivnost', array('fields' => 'names'));
        
        // Получаване на средна оценка
        $comments = get_post_meta($parfume_id, '_pc_comments', true);
        $average_rating = 0;
        $total_ratings = 0;
        
        if (!empty($comments) && is_array($comments)) {
            foreach ($comments as $comment) {
                if (!empty($comment['rating'])) {
                    $average_rating += intval($comment['rating']);
                    $total_ratings++;
                }
            }
            
            if ($total_ratings > 0) {
                $average_rating = round($average_rating / $total_ratings, 1);
            }
        }
        
        return array(
            'id' => $parfume_id,
            'title' => $post->post_title,
            'url' => get_permalink($parfume_id),
            'image' => get_the_post_thumbnail_url($parfume_id, 'medium'),
            'brand' => !empty($brands) ? implode(', ', $brands) : '',
            'type' => !empty($types) ? implode(', ', $types) : '',
            'aromat_type' => !empty($aromat_types) ? implode(', ', $aromat_types) : '',
            'seasons' => !empty($seasons) ? implode(', ', $seasons) : '',
            'intensities' => !empty($intensities) ? implode(', ', $intensities) : '',
            'top_notes' => $top_notes ? implode(', ', $top_notes) : '',
            'middle_notes' => $middle_notes ? implode(', ', $middle_notes) : '',
            'base_notes' => $base_notes ? implode(', ', $base_notes) : '',
            'durability' => $durability,
            'sillage' => $sillage,
            'gender' => $gender,
            'price_range' => $price_range,
            'rating' => $average_rating,
            'total_ratings' => $total_ratings
        );
    }
    
    /**
     * Получаване на максимален брой елементи за сравнение
     */
    private function get_max_comparison_items() {
        $options = get_option('parfume_catalog_settings', array());
        return !empty($options['max_comparison_items']) ? intval($options['max_comparison_items']) : 4;
    }
}