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

class PC_Comparison {
    
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
            wp_send_json_error(sprintf(__('Можете да сравнявате максимум %d парфюма', 'parfume-catalog'), $max_items));
        }
        
        $parfume_data = $this->get_parfume_comparison_data($parfume_id);
        
        wp_send_json_success(array(
            'parfume' => $parfume_data,
            'message' => __('Парфюмът е добавен за сравнение', 'parfume-catalog')
        ));
    }
    
    /**
     * Премахване от сравнение
     */
    public function remove_from_comparison() {
        check_ajax_referer('pc_nonce', 'nonce');
        
        $parfume_id = intval($_POST['parfume_id']);
        
        wp_send_json_success(array(
            'parfume_id' => $parfume_id,
            'message' => __('Парфюмът е премахнат от сравнение', 'parfume-catalog')
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
        
        $comparison_table = $this->generate_comparison_table($comparison_data);
        
        wp_send_json_success(array(
            'parfumes' => $comparison_data,
            'table_html' => $comparison_table
        ));
    }
    
    /**
     * Търсене на парфюми за добавяне в сравнение
     */
    public function search_parfumes() {
        check_ajax_referer('pc_nonce', 'nonce');
        
        $search_term = sanitize_text_field($_POST['search_term']);
        
        $args = array(
            'post_type' => 'parfumes',
            'posts_per_page' => 10,
            's' => $search_term,
            'post_status' => 'publish'
        );
        
        $query = new WP_Query($args);
        $results = array();
        
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $results[] = array(
                    'id' => get_the_ID(),
                    'title' => get_the_title(),
                    'url' => get_permalink(),
                    'image' => get_the_post_thumbnail_url(get_the_ID(), 'medium'),
                    'brand' => $this->get_parfume_brand(get_the_ID()),
                    'type' => $this->get_parfume_type(get_the_ID())
                );
            }
            wp_reset_postdata();
        }
        
        wp_send_json_success($results);
    }
    
    /**
     * Получаване на данни за парфюм за сравнение
     */
    private function get_parfume_comparison_data($parfume_id) {
        $criteria = $this->get_comparison_criteria();
        $data = array(
            'id' => $parfume_id,
            'title' => get_the_title($parfume_id),
            'url' => get_permalink($parfume_id),
            'image' => get_the_post_thumbnail_url($parfume_id, 'medium')
        );
        
        foreach ($criteria as $criterion_key => $criterion_label) {
            $data[$criterion_key] = $this->get_parfume_criterion_value($parfume_id, $criterion_key);
        }
        
        return $data;
    }
    
    /**
     * Получаване на стойност за критерий
     */
    private function get_parfume_criterion_value($parfume_id, $criterion) {
        switch ($criterion) {
            case 'brand':
                return $this->get_parfume_brand($parfume_id);
                
            case 'type':
                return $this->get_parfume_type($parfume_id);
                
            case 'concentration':
                return $this->get_parfume_concentration($parfume_id);
                
            case 'top_notes':
                return $this->get_parfume_notes($parfume_id, 'top');
                
            case 'middle_notes':
                return $this->get_parfume_notes($parfume_id, 'middle');
                
            case 'base_notes':
                return $this->get_parfume_notes($parfume_id, 'base');
                
            case 'season':
                return $this->get_parfume_seasons($parfume_id);
                
            case 'intensity':
                return $this->get_parfume_intensity($parfume_id);
                
            case 'longevity':
                return get_post_meta($parfume_id, '_pc_longevity', true);
                
            case 'sillage':
                return get_post_meta($parfume_id, '_pc_sillage', true);
                
            case 'price_range':
                return get_post_meta($parfume_id, '_pc_price_range', true);
                
            case 'gender':
                return $this->get_parfume_gender($parfume_id);
                
            case 'rating':
                return $this->get_parfume_average_rating($parfume_id);
                
            case 'year':
                return get_post_meta($parfume_id, '_pc_release_year', true);
                
            default:
                return get_post_meta($parfume_id, '_pc_' . $criterion, true);
        }
    }
    
    /**
     * Получаване на марката на парфюма
     */
    private function get_parfume_brand($parfume_id) {
        $terms = wp_get_post_terms($parfume_id, 'marki');
        return !empty($terms) ? $terms[0]->name : '';
    }
    
    /**
     * Получаване на типа на парфюма
     */
    private function get_parfume_type($parfume_id) {
        $terms = wp_get_post_terms($parfume_id, 'tip');
        return !empty($terms) ? $terms[0]->name : '';
    }
    
    /**
     * Получаване на концентрацията
     */
    private function get_parfume_concentration($parfume_id) {
        $terms = wp_get_post_terms($parfume_id, 'vid_aromat');
        return !empty($terms) ? $terms[0]->name : '';
    }
    
    /**
     * Получаване на нотки по тип
     */
    private function get_parfume_notes($parfume_id, $type) {
        $notes = get_post_meta($parfume_id, '_pc_' . $type . '_notes', true);
        if (empty($notes)) {
            return array();
        }
        
        $note_names = array();
        foreach ($notes as $note_id) {
            $term = get_term($note_id, 'notki');
            if ($term && !is_wp_error($term)) {
                $note_names[] = $term->name;
            }
        }
        
        return $note_names;
    }
    
    /**
     * Получаване на сезони
     */
    private function get_parfume_seasons($parfume_id) {
        $terms = wp_get_post_terms($parfume_id, 'sezon');
        $seasons = array();
        
        foreach ($terms as $term) {
            $seasons[] = $term->name;
        }
        
        return $seasons;
    }
    
    /**
     * Получаване на интензивност
     */
    private function get_parfume_intensity($parfume_id) {
        $terms = wp_get_post_terms($parfume_id, 'intenzivnost');
        return !empty($terms) ? $terms[0]->name : '';
    }
    
    /**
     * Получаване на пол
     */
    private function get_parfume_gender($parfume_id) {
        $terms = wp_get_post_terms($parfume_id, 'tip');
        $gender_terms = array();
        
        foreach ($terms as $term) {
            if (in_array($term->slug, array('damski', 'mazhki', 'uniseks', 'mladezhi', 'vazrastni'))) {
                $gender_terms[] = $term->name;
            }
        }
        
        return $gender_terms;
    }
    
    /**
     * Получаване на средна оценка
     */
    private function get_parfume_average_rating($parfume_id) {
        // Тази функция ще се имплементира в comments модула
        return get_post_meta($parfume_id, '_pc_average_rating', true);
    }
    
    /**
     * Генериране на таблица за сравнение
     */
    private function generate_comparison_table($parfumes) {
        $criteria = $this->get_comparison_criteria();
        
        ob_start();
        ?>
        <div class="pc-comparison-table-wrapper">
            <table class="pc-comparison-table">
                <thead>
                    <tr>
                        <th class="pc-criteria-column"><?php _e('Критерии', 'parfume-catalog'); ?></th>
                        <?php foreach ($parfumes as $parfume): ?>
                            <th class="pc-parfume-column">
                                <div class="pc-parfume-header">
                                    <?php if ($parfume['image']): ?>
                                        <img src="<?php echo esc_url($parfume['image']); ?>" alt="<?php echo esc_attr($parfume['title']); ?>">
                                    <?php endif; ?>
                                    <h4><?php echo esc_html($parfume['title']); ?></h4>
                                    <button class="pc-remove-from-comparison" data-parfume-id="<?php echo $parfume['id']; ?>">
                                        <span class="dashicons dashicons-no-alt"></span>
                                    </button>
                                </div>
                            </th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($criteria as $criterion_key => $criterion_label): ?>
                        <tr class="pc-criterion-row" data-criterion="<?php echo esc_attr($criterion_key); ?>">
                            <td class="pc-criterion-label">
                                <strong><?php echo esc_html($criterion_label); ?></strong>
                            </td>
                            <?php foreach ($parfumes as $parfume): ?>
                                <td class="pc-criterion-value">
                                    <?php 
                                    $value = $parfume[$criterion_key];
                                    if (is_array($value)) {
                                        echo esc_html(implode(', ', $value));
                                    } else {
                                        echo esc_html($value);
                                    }
                                    ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <div class="pc-comparison-actions">
            <button class="pc-btn pc-btn-secondary" id="pc-clear-comparison">
                <?php _e('Изчисти всички', 'parfume-catalog'); ?>
            </button>
        </div>
        <?php
        
        return ob_get_clean();
    }
    
    /**
     * Добавяне на бутон за сравнение
     */
    public function add_comparison_button($parfume_id = null) {
        if (!$parfume_id) {
            $parfume_id = get_the_ID();
        }
        
        if (!$this->is_comparison_enabled() || get_post_type($parfume_id) !== 'parfumes') {
            return;
        }
        
        echo $this->get_comparison_button_html($parfume_id);
    }
    
    /**
     * HTML за бутон за сравнение
     */
    private function get_comparison_button_html($parfume_id) {
        ob_start();
        ?>
        <button class="pc-comparison-btn" 
                data-parfume-id="<?php echo $parfume_id; ?>"
                data-action="add">
            <span class="pc-btn-icon">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 4v16M3 4v16M13 12h8M3 12h8"/>
                </svg>
            </span>
            <span class="pc-btn-text pc-btn-add"><?php _e('Добави за сравнение', 'parfume-catalog'); ?></span>
            <span class="pc-btn-text pc-btn-remove" style="display:none;"><?php _e('Премахни от сравнение', 'parfume-catalog'); ?></span>
        </button>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Шорткод за бутон за сравнение
     */
    public function comparison_button_shortcode($atts) {
        $atts = shortcode_atts(array(
            'id' => get_the_ID()
        ), $atts);
        
        return $this->get_comparison_button_html($atts['id']);
    }
    
    /**
     * Добавяне на comparison widget
     */
    public function add_comparison_widget() {
        if (!$this->is_comparison_enabled()) {
            return;
        }
        
        ?>
        <div id="pc-comparison-widget" class="pc-comparison-widget" style="display:none;">
            <div class="pc-widget-header">
                <h4><?php _e('Сравни парфюми', 'parfume-catalog'); ?></h4>
                <span class="pc-widget-count">(<span id="pc-comparison-count">0</span>)</span>
                <button class="pc-widget-toggle" id="pc-widget-toggle">
                    <span class="dashicons dashicons-arrow-up-alt2"></span>
                </button>
                <button class="pc-widget-close" id="pc-widget-close">
                    <span class="dashicons dashicons-no-alt"></span>
                </button>
            </div>
            
            <div class="pc-widget-content" id="pc-widget-content">
                <div class="pc-widget-items" id="pc-comparison-items">
                    <!-- Динамично съдържание -->
                </div>
                
                <div class="pc-widget-actions">
                    <div class="pc-widget-search">
                        <input type="text" 
                               id="pc-search-parfumes" 
                               placeholder="<?php _e('Търси парфюм за добавяне...', 'parfume-catalog'); ?>">
                        <div id="pc-search-results" class="pc-search-results"></div>
                    </div>
                    
                    <button class="pc-btn pc-btn-primary" 
                            id="pc-view-comparison"
                            style="display:none;">
                        <?php _e('Виж сравнението', 'parfume-catalog'); ?>
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Comparison modal -->
        <div id="pc-comparison-modal" class="pc-modal" style="display:none;">
            <div class="pc-modal-overlay"></div>
            <div class="pc-modal-content">
                <div class="pc-modal-header">
                    <h2><?php _e('Сравнение на парфюми', 'parfume-catalog'); ?></h2>
                    <button class="pc-modal-close">
                        <span class="dashicons dashicons-no-alt"></span>
                    </button>
                </div>
                <div class="pc-modal-body" id="pc-comparison-modal-body">
                    <!-- Динамично съдържание -->
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Получаване на критерии за сравнение
     */
    private function get_comparison_criteria() {
        $default_criteria = array(
            'brand' => __('Марка', 'parfume-catalog'),
            'concentration' => __('Концентрация', 'parfume-catalog'),
            'top_notes' => __('Връхни нотки', 'parfume-catalog'),
            'middle_notes' => __('Средни нотки', 'parfume-catalog'),
            'base_notes' => __('Базови нотки', 'parfume-catalog'),
            'season' => __('Сезон', 'parfume-catalog'),
            'intensity' => __('Интензивност', 'parfume-catalog'),
            'longevity' => __('Дълготрайност', 'parfume-catalog'),
            'sillage' => __('Ароматна следа', 'parfume-catalog'),
            'gender' => __('Пол', 'parfume-catalog'),
            'price_range' => __('Ценова категория', 'parfume-catalog'),
            'rating' => __('Рейтинг', 'parfume-catalog'),
            'year' => __('Година', 'parfume-catalog')
        );
        
        $options = get_option('pc_comparison_options', array());
        $enabled_criteria = isset($options['criteria']) ? $options['criteria'] : array_keys($default_criteria);
        
        $criteria = array();
        foreach ($enabled_criteria as $key) {
            if (isset($default_criteria[$key])) {
                $criteria[$key] = $default_criteria[$key];
            }
        }
        
        return $criteria;
    }
    
    /**
     * Проверка дали е активирано сравнението
     */
    private function is_comparison_enabled() {
        $options = get_option('pc_comparison_options', array());
        return isset($options['enabled']) ? $options['enabled'] : true;
    }
    
    /**
     * Получаване на максимален брой елементи за сравнение
     */
    private function get_max_comparison_items() {
        $options = get_option('pc_comparison_options', array());
        return isset($options['max_items']) ? intval($options['max_items']) : 4;
    }
}