<?php
/**
 * Parfume Catalog Comparison Module
 * 
 * Система за сравняване на парфюми без регистрация (localStorage базирана)
 * 
 * @package Parfume_Catalog
 * @since 1.0.0
 */

// Предотвратяване на директен достъп
if (!defined('ABSPATH')) {
    exit;
}

class Parfume_Catalog_Comparison {

    /**
     * Comparison критерии по подразбиране
     */
    private $default_criteria = array(
        'name' => array(
            'label' => 'Име',
            'enabled' => true,
            'order' => 1
        ),
        'brand' => array(
            'label' => 'Марка',
            'enabled' => true,
            'order' => 2
        ),
        'type' => array(
            'label' => 'Тип',
            'enabled' => true,
            'order' => 3
        ),
        'concentration' => array(
            'label' => 'Концентрация',
            'enabled' => true,
            'order' => 4
        ),
        'launch_year' => array(
            'label' => 'Година',
            'enabled' => true,
            'order' => 5
        ),
        'top_notes' => array(
            'label' => 'Връхни нотки',
            'enabled' => true,
            'order' => 6
        ),
        'middle_notes' => array(
            'label' => 'Средни нотки',
            'enabled' => true,
            'order' => 7
        ),
        'base_notes' => array(
            'label' => 'Базови нотки',
            'enabled' => true,
            'order' => 8
        ),
        'longevity' => array(
            'label' => 'Дълготрайност',
            'enabled' => true,
            'order' => 9
        ),
        'sillage' => array(
            'label' => 'Ароматна следа',
            'enabled' => true,
            'order' => 10
        ),
        'price_rating' => array(
            'label' => 'Ценова категория',
            'enabled' => true,
            'order' => 11
        ),
        'suitable_seasons' => array(
            'label' => 'Подходящи сезони',
            'enabled' => true,
            'order' => 12
        ),
        'suitable_time' => array(
            'label' => 'Време на носене',
            'enabled' => true,
            'order' => 13
        ),
        'intensity' => array(
            'label' => 'Интензивност',
            'enabled' => true,
            'order' => 14
        ),
        'price_range' => array(
            'label' => 'Ценови диапазон',
            'enabled' => false,
            'order' => 15
        ),
        'rating' => array(
            'label' => 'Рейтинг',
            'enabled' => false,
            'order' => 16
        )
    );

    /**
     * Конструктор
     */
    public function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_comparison_assets'));
        add_action('wp_ajax_parfume_get_comparison_data', array($this, 'ajax_get_comparison_data'));
        add_action('wp_ajax_nopriv_parfume_get_comparison_data', array($this, 'ajax_get_comparison_data'));
        add_action('wp_ajax_parfume_search_for_comparison', array($this, 'ajax_search_for_comparison'));
        add_action('wp_ajax_nopriv_parfume_search_for_comparison', array($this, 'ajax_search_for_comparison'));
        add_action('wp_ajax_parfume_export_comparison', array($this, 'ajax_export_comparison'));
        add_action('wp_ajax_nopriv_parfume_export_comparison', array($this, 'ajax_export_comparison'));
        add_filter('body_class', array($this, 'add_comparison_body_class'));
        add_action('wp_footer', array($this, 'add_comparison_templates'));
        add_shortcode('parfume_comparison_button', array($this, 'comparison_button_shortcode'));
    }

    /**
     * Enqueue на comparison assets
     */
    public function enqueue_comparison_assets() {
        if ($this->should_load_comparison()) {
            wp_enqueue_script(
                'parfume-comparison',
                PARFUME_CATALOG_PLUGIN_URL . 'assets/js/comparison.js',
                array('jquery'),
                PARFUME_CATALOG_VERSION,
                true
            );

            wp_localize_script('parfume-comparison', 'parfume_comparison_config', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('parfume_comparison_nonce'),
                'max_items' => $this->get_max_comparison_items(),
                'enabled_criteria' => $this->get_enabled_criteria(),
                'texts' => array(
                    'add_to_comparison' => __('Добави за сравнение', 'parfume-catalog'),
                    'remove_from_comparison' => __('Премахни от сравнение', 'parfume-catalog'),
                    'compare_button' => __('Сравни', 'parfume-catalog'),
                    'comparison_title' => __('Сравняване на парфюми', 'parfume-catalog'),
                    'max_items_reached' => __('Максималният брой парфюми за сравнение е достигнат', 'parfume-catalog'),
                    'min_items_required' => __('Необходими са поне 2 парфюма за сравнение', 'parfume-catalog'),
                    'remove_item' => __('Премахни', 'parfume-catalog'),
                    'clear_all' => __('Изчисти всички', 'parfume-catalog'),
                    'export_comparison' => __('Експортирай сравнението', 'parfume-catalog'),
                    'search_placeholder' => __('Търси парфюм за добавяне...', 'parfume-catalog'),
                    'no_results' => __('Няма намерени резултати', 'parfume-catalog'),
                    'loading' => __('Зареждане...', 'parfume-catalog'),
                    'error' => __('Възникна грешка', 'parfume-catalog'),
                    'comparison_exported' => __('Сравнението е експортирано успешно', 'parfume-catalog'),
                    'item_added' => __('Парфюмът е добавен за сравнение', 'parfume-catalog'),
                    'item_removed' => __('Парфюмът е премахнат от сравнението', 'parfume-catalog'),
                    'comparison_cleared' => __('Сравнението е изчистено', 'parfume-catalog')
                )
            ));

            // CSS за comparison
            wp_add_inline_style('parfume-catalog-frontend', $this->get_comparison_css());
        }
    }

    /**
     * Проверка дали да се зареди comparison
     */
    private function should_load_comparison() {
        // Зареждане на всички страници с парфюми
        return is_singular('parfumes') || 
               is_post_type_archive('parfumes') || 
               is_tax(array('parfume_type', 'parfume_vid', 'parfume_marki', 'parfume_season', 'parfume_intensity', 'parfume_notes'));
    }

    /**
     * Получаване на максимален брой елементи за сравнение
     */
    private function get_max_comparison_items() {
        $options = get_option('parfume_catalog_options', array());
        return isset($options['comparison_max_items']) ? intval($options['comparison_max_items']) : 4;
    }

    /**
     * Получаване на активни критерии за сравнение
     */
    private function get_enabled_criteria() {
        $settings = get_option('parfume_catalog_comparison_settings', array());
        $criteria = wp_parse_args($settings, $this->default_criteria);
        
        // Филтриране само на активните
        $enabled = array_filter($criteria, function($criterion) {
            return isset($criterion['enabled']) && $criterion['enabled'];
        });
        
        // Сортиране по ред
        uasort($enabled, function($a, $b) {
            return $a['order'] - $b['order'];
        });
        
        return $enabled;
    }

    /**
     * AJAX - Получаване на comparison данни
     */
    public function ajax_get_comparison_data() {
        check_ajax_referer('parfume_comparison_nonce', 'nonce');
        
        $parfume_ids = array_map('intval', $_POST['parfume_ids']);
        
        if (empty($parfume_ids)) {
            wp_send_json_error(__('Няма избрани парфюми', 'parfume-catalog'));
        }
        
        $comparison_data = array();
        
        foreach ($parfume_ids as $parfume_id) {
            $parfume_data = $this->get_parfume_comparison_data($parfume_id);
            if ($parfume_data) {
                $comparison_data[] = $parfume_data;
            }
        }
        
        wp_send_json_success(array(
            'parfumes' => $comparison_data,
            'criteria' => $this->get_enabled_criteria()
        ));
    }

    /**
     * Получаване на comparison данни за парфюм
     */
    private function get_parfume_comparison_data($parfume_id) {
        $post = get_post($parfume_id);
        if (!$post || $post->post_type !== 'parfumes') {
            return false;
        }

        $data = array(
            'id' => $parfume_id,
            'name' => $post->post_title,
            'url' => get_permalink($parfume_id),
            'image' => get_the_post_thumbnail_url($parfume_id, 'medium'),
            'excerpt' => wp_trim_words($post->post_excerpt, 20)
        );

        // Марка
        $brand_terms = wp_get_object_terms($parfume_id, 'parfume_marki');
        $data['brand'] = !empty($brand_terms) ? $brand_terms[0]->name : '';

        // Тип
        $type_terms = wp_get_object_terms($parfume_id, 'parfume_type');
        $data['type'] = !empty($type_terms) ? implode(', ', wp_list_pluck($type_terms, 'name')) : '';

        // Вид аромат
        $vid_terms = wp_get_object_terms($parfume_id, 'parfume_vid');
        $data['concentration'] = !empty($vid_terms) ? $vid_terms[0]->name : '';

        // Година на излизане
        $data['launch_year'] = get_post_meta($parfume_id, '_parfume_launch_year', true);

        // Нотки
        $data['top_notes'] = $this->get_notes_by_position($parfume_id, 'top');
        $data['middle_notes'] = $this->get_notes_by_position($parfume_id, 'middle');
        $data['base_notes'] = $this->get_notes_by_position($parfume_id, 'base');

        // Статистики
        $data['longevity'] = $this->get_stat_display(get_post_meta($parfume_id, '_parfume_longevity', true), 'longevity');
        $data['sillage'] = $this->get_stat_display(get_post_meta($parfume_id, '_parfume_sillage', true), 'sillage');
        $data['price_rating'] = $this->get_stat_display(get_post_meta($parfume_id, '_parfume_price_rating', true), 'price');

        // Сезони
        $season_terms = wp_get_object_terms($parfume_id, 'parfume_season');
        $data['suitable_seasons'] = !empty($season_terms) ? implode(', ', wp_list_pluck($season_terms, 'name')) : '';

        // Време на носене
        $suitable_day = get_post_meta($parfume_id, '_parfume_suitable_day', true);
        $suitable_night = get_post_meta($parfume_id, '_parfume_suitable_night', true);
        $time_suitable = array();
        if ($suitable_day) $time_suitable[] = __('Ден', 'parfume-catalog');
        if ($suitable_night) $time_suitable[] = __('Нощ', 'parfume-catalog');
        $data['suitable_time'] = implode(', ', $time_suitable);

        // Интензивност
        $intensity_terms = wp_get_object_terms($parfume_id, 'parfume_intensity');
        $data['intensity'] = !empty($intensity_terms) ? implode(', ', wp_list_pluck($intensity_terms, 'name')) : '';

        // Ценови диапазон (от stores данни)
        $data['price_range'] = $this->get_price_range($parfume_id);

        // Рейтинг (от comments)
        $data['rating'] = $this->get_average_rating($parfume_id);

        return $data;
    }

    /**
     * Получаване на нотки по позиция
     */
    private function get_notes_by_position($parfume_id, $position) {
        $all_notes = wp_get_object_terms($parfume_id, 'parfume_notes');
        $position_notes = array();
        
        foreach ($all_notes as $note) {
            $note_position = get_term_meta($note->term_id, 'note_position', true);
            if ($note_position === $position) {
                $position_notes[] = $note->name;
            }
        }
        
        return implode(', ', $position_notes);
    }

    /**
     * Получаване на display стойност за статистика
     */
    private function get_stat_display($value, $type) {
        if (empty($value)) {
            return '';
        }

        $labels = array(
            'longevity' => array(
                1 => __('Много слаб', 'parfume-catalog'),
                2 => __('Слаб', 'parfume-catalog'),
                3 => __('Умерен', 'parfume-catalog'),
                4 => __('Траен', 'parfume-catalog'),
                5 => __('Изключително траен', 'parfume-catalog')
            ),
            'sillage' => array(
                1 => __('Слаба', 'parfume-catalog'),
                2 => __('Умерена', 'parfume-catalog'),
                3 => __('Силна', 'parfume-catalog'),
                4 => __('Огромна', 'parfume-catalog')
            ),
            'price' => array(
                1 => __('Прекалено скъп', 'parfume-catalog'),
                2 => __('Скъп', 'parfume-catalog'),
                3 => __('Приемлива цена', 'parfume-catalog'),
                4 => __('Добра цена', 'parfume-catalog'),
                5 => __('Евтин', 'parfume-catalog')
            )
        );

        return isset($labels[$type][$value]) ? $labels[$type][$value] : $value;
    }

    /**
     * Получаване на ценови диапазон
     */
    private function get_price_range($parfume_id) {
        global $wpdb;
        
        $scraper_table = $wpdb->prefix . 'parfume_scraper_data';
        $prices = $wpdb->get_results($wpdb->prepare(
            "SELECT price FROM $scraper_table WHERE post_id = %d AND price IS NOT NULL AND price > 0",
            $parfume_id
        ));
        
        if (empty($prices)) {
            return '';
        }
        
        $price_values = wp_list_pluck($prices, 'price');
        $min_price = min($price_values);
        $max_price = max($price_values);
        
        if ($min_price === $max_price) {
            return sprintf('%.2f лв.', $min_price);
        }
        
        return sprintf('%.2f - %.2f лв.', $min_price, $max_price);
    }

    /**
     * Получаване на среден рейтинг
     */
    private function get_average_rating($parfume_id) {
        global $wpdb;
        
        $comments_table = $wpdb->prefix . 'parfume_comments';
        $average = $wpdb->get_var($wpdb->prepare(
            "SELECT AVG(rating) FROM $comments_table WHERE post_id = %d AND status = 'approved'",
            $parfume_id
        ));
        
        if ($average) {
            return sprintf('%.1f/5', floatval($average));
        }
        
        return '';
    }

    /**
     * AJAX - Търсене за сравнение
     */
    public function ajax_search_for_comparison() {
        check_ajax_referer('parfume_comparison_nonce', 'nonce');
        
        $search_query = sanitize_text_field($_POST['search']);
        
        if (strlen($search_query) < 2) {
            wp_send_json_error(__('Минимум 2 символа за търсене', 'parfume-catalog'));
        }
        
        $query_args = array(
            'post_type' => 'parfumes',
            'post_status' => 'publish',
            'posts_per_page' => 10,
            's' => $search_query,
            'meta_query' => array(
                array(
                    'key' => '_thumbnail_id',
                    'compare' => 'EXISTS'
                )
            )
        );
        
        $search_query = new WP_Query($query_args);
        $results = array();
        
        if ($search_query->have_posts()) {
            while ($search_query->have_posts()) {
                $search_query->the_post();
                
                $brand_terms = wp_get_object_terms(get_the_ID(), 'parfume_marki');
                $brand_name = !empty($brand_terms) ? $brand_terms[0]->name : '';
                
                $results[] = array(
                    'id' => get_the_ID(),
                    'title' => get_the_title(),
                    'brand' => $brand_name,
                    'image' => get_the_post_thumbnail_url(get_the_ID(), 'thumbnail'),
                    'url' => get_permalink()
                );
            }
        }
        
        wp_reset_postdata();
        
        wp_send_json_success($results);
    }

    /**
     * AJAX - Експорт на сравнение
     */
    public function ajax_export_comparison() {
        check_ajax_referer('parfume_comparison_nonce', 'nonce');
        
        $parfume_ids = array_map('intval', $_POST['parfume_ids']);
        $format = sanitize_text_field($_POST['format']); // pdf, csv, print
        
        if (empty($parfume_ids)) {
            wp_send_json_error(__('Няма избрани парфюми', 'parfume-catalog'));
        }
        
        switch ($format) {
            case 'csv':
                $export_data = $this->export_comparison_csv($parfume_ids);
                break;
            case 'print':
                $export_data = $this->export_comparison_print($parfume_ids);
                break;
            default:
                wp_send_json_error(__('Неподдържан формат', 'parfume-catalog'));
        }
        
        wp_send_json_success($export_data);
    }

    /**
     * Експорт в CSV формат
     */
    private function export_comparison_csv($parfume_ids) {
        $comparison_data = array();
        
        foreach ($parfume_ids as $parfume_id) {
            $parfume_data = $this->get_parfume_comparison_data($parfume_id);
            if ($parfume_data) {
                $comparison_data[] = $parfume_data;
            }
        }
        
        if (empty($comparison_data)) {
            return false;
        }
        
        // Генериране на CSV съдържание
        $csv_content = '';
        $criteria = $this->get_enabled_criteria();
        
        // Header ред
        $headers = array(__('Критерий', 'parfume-catalog'));
        foreach ($comparison_data as $parfume) {
            $headers[] = $parfume['name'];
        }
        $csv_content .= implode(',', array_map(array($this, 'csv_escape'), $headers)) . "\n";
        
        // Данни редове
        foreach ($criteria as $key => $criterion) {
            $row = array($criterion['label']);
            foreach ($comparison_data as $parfume) {
                $row[] = isset($parfume[$key]) ? $parfume[$key] : '';
            }
            $csv_content .= implode(',', array_map(array($this, 'csv_escape'), $row)) . "\n";
        }
        
        return array(
            'content' => $csv_content,
            'filename' => 'parfume_comparison_' . date('Y-m-d_H-i-s') . '.csv',
            'mimetype' => 'text/csv'
        );
    }

    /**
     * Експорт за принтиране
     */
    private function export_comparison_print($parfume_ids) {
        $comparison_data = array();
        
        foreach ($parfume_ids as $parfume_id) {
            $parfume_data = $this->get_parfume_comparison_data($parfume_id);
            if ($parfume_data) {
                $comparison_data[] = $parfume_data;
            }
        }
        
        if (empty($comparison_data)) {
            return false;
        }
        
        $criteria = $this->get_enabled_criteria();
        
        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title><?php _e('Сравнение на парфюми', 'parfume-catalog'); ?></title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                .comparison-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                .comparison-table th, .comparison-table td { border: 1px solid #ddd; padding: 10px; text-align: left; }
                .comparison-table th { background-color: #f5f5f5; font-weight: bold; }
                .parfume-image { max-width: 60px; height: auto; }
                .print-header { text-align: center; margin-bottom: 30px; }
                .print-date { color: #666; margin-top: 10px; }
                @media print {
                    body { margin: 0; }
                    .no-print { display: none; }
                }
            </style>
        </head>
        <body>
            <div class="print-header">
                <h1><?php _e('Сравнение на парфюми', 'parfume-catalog'); ?></h1>
                <div class="print-date"><?php echo date('d.m.Y H:i'); ?></div>
            </div>
            
            <table class="comparison-table">
                <thead>
                    <tr>
                        <th><?php _e('Критерий', 'parfume-catalog'); ?></th>
                        <?php foreach ($comparison_data as $parfume): ?>
                            <th>
                                <?php if ($parfume['image']): ?>
                                    <img src="<?php echo esc_url($parfume['image']); ?>" class="parfume-image" alt="<?php echo esc_attr($parfume['name']); ?>" />
                                <?php endif; ?>
                                <div><?php echo esc_html($parfume['name']); ?></div>
                            </th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($criteria as $key => $criterion): ?>
                        <tr>
                            <td><strong><?php echo esc_html($criterion['label']); ?></strong></td>
                            <?php foreach ($comparison_data as $parfume): ?>
                                <td><?php echo esc_html(isset($parfume[$key]) ? $parfume[$key] : '—'); ?></td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <script>
                window.onload = function() {
                    window.print();
                };
            </script>
        </body>
        </html>
        <?php
        
        return array(
            'content' => ob_get_clean(),
            'type' => 'html'
        );
    }

    /**
     * CSV escape функция
     */
    private function csv_escape($value) {
        return '"' . str_replace('"', '""', $value) . '"';
    }

    /**
     * Добавяне на body class
     */
    public function add_comparison_body_class($classes) {
        if ($this->should_load_comparison()) {
            $classes[] = 'parfume-comparison-enabled';
        }
        return $classes;
    }

    /**
     * Добавяне на comparison templates в footer
     */
    public function add_comparison_templates() {
        if ($this->should_load_comparison()) {
            ?>
            <!-- Comparison Button Template -->
            <script type="text/template" id="comparison-button-template">
                <button type="button" class="parfume-comparison-btn" data-parfume-id="{{id}}" data-action="{{action}}">
                    <span class="comparison-icon">{{icon}}</span>
                    <span class="comparison-text">{{text}}</span>
                </button>
            </script>

            <!-- Comparison Table Row Template -->
            <script type="text/template" id="comparison-row-template">
                <tr data-criterion="{{criterion}}">
                    <td class="criterion-label">{{label}}</td>
                    {{#parfumes}}
                    <td class="parfume-data" data-parfume-id="{{id}}">{{value}}</td>
                    {{/parfumes}}
                </tr>
            </script>

            <!-- Search Result Template -->
            <script type="text/template" id="search-result-template">
                <div class="search-result-item" data-parfume-id="{{id}}">
                    <div class="result-image">
                        <img src="{{image}}" alt="{{title}}" />
                    </div>
                    <div class="result-info">
                        <div class="result-title">{{title}}</div>
                        <div class="result-brand">{{brand}}</div>
                    </div>
                    <div class="result-actions">
                        <button type="button" class="button add-to-comparison" data-parfume-id="{{id}}">
                            <?php _e('Добави', 'parfume-catalog'); ?>
                        </button>
                    </div>
                </div>
            </script>
            <?php
        }
    }

    /**
     * Shortcode за comparison бутон
     */
    public function comparison_button_shortcode($atts) {
        $atts = shortcode_atts(array(
            'id' => get_the_ID(),
            'class' => '',
            'text_add' => __('Добави за сравнение', 'parfume-catalog'),
            'text_remove' => __('Премахни от сравнение', 'parfume-catalog')
        ), $atts);

        if (!$this->should_load_comparison()) {
            return '';
        }

        $parfume_id = intval($atts['id']);
        if (!$parfume_id || get_post_type($parfume_id) !== 'parfumes') {
            return '';
        }

        // Проверка дали парфюмът е скрит от сравнение
        $hide_comparison = get_post_meta($parfume_id, '_parfume_hide_comparison', true);
        if ($hide_comparison) {
            return '';
        }

        $class = 'parfume-comparison-btn ' . sanitize_html_class($atts['class']);

        return sprintf(
            '<button type="button" class="%s" data-parfume-id="%d" data-text-add="%s" data-text-remove="%s">
                <span class="comparison-icon"></span>
                <span class="comparison-text">%s</span>
            </button>',
            esc_attr($class),
            $parfume_id,
            esc_attr($atts['text_add']),
            esc_attr($atts['text_remove']),
            esc_html($atts['text_add'])
        );
    }

    /**
     * Получаване на comparison CSS
     */
    private function get_comparison_css() {
        return '
        .parfume-comparison-popup {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            z-index: 10000;
            max-width: 90vw;
            max-height: 90vh;
            overflow: hidden;
        }
        
        .parfume-comparison-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 9999;
        }
        
        .comparison-popup-header {
            background: #f8f9fa;
            padding: 20px;
            border-bottom: 1px solid #dee2e6;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .comparison-close-btn {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #6c757d;
        }
        
        .comparison-popup-content {
            padding: 20px;
            max-height: 70vh;
            overflow-y: auto;
        }
        
        .comparison-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        .comparison-table th,
        .comparison-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #dee2e6;
            vertical-align: top;
        }
        
        .comparison-table th {
            background: #f8f9fa;
            font-weight: 600;
            position: sticky;
            top: 0;
        }
        
        .parfume-comparison-float {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 1000;
            background: #007cba;
            color: white;
            border: none;
            border-radius: 50px;
            padding: 15px 20px;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            transition: all 0.3s ease;
        }
        
        .parfume-comparison-float:hover {
            background: #005a87;
            transform: translateY(-2px);
        }
        
        .parfume-comparison-btn {
            background: #007cba;
            color: white;
            border: none;
            border-radius: 4px;
            padding: 8px 16px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .parfume-comparison-btn:hover {
            background: #005a87;
        }
        
        .parfume-comparison-btn.in-comparison {
            background: #dc3545;
        }
        
        .parfume-comparison-btn.in-comparison:hover {
            background: #c82333;
        }
        
        @media (max-width: 768px) {
            .parfume-comparison-popup {
                width: 95vw;
                height: 95vh;
                max-width: none;
                max-height: none;
                border-radius: 0;
            }
            
            .comparison-table {
                font-size: 14px;
            }
            
            .comparison-table th,
            .comparison-table td {
                padding: 8px;
            }
        }
        ';
    }

    /**
     * Получаване на comparison настройки
     */
    public static function get_comparison_settings() {
        return get_option('parfume_catalog_comparison_settings', array());
    }

    /**
     * Запазване на comparison настройки
     */
    public static function save_comparison_settings($settings) {
        return update_option('parfume_catalog_comparison_settings', $settings);
    }

    /**
     * Получаване на default критерии
     */
    public function get_default_criteria() {
        return $this->default_criteria;
    }
}