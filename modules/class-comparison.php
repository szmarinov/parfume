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
        ),
        'advantages' => array(
            'label' => 'Предимства',
            'enabled' => false,
            'order' => 17
        ),
        'disadvantages' => array(
            'label' => 'Недостатъци',
            'enabled' => false,
            'order' => 18
        )
    );

    /**
     * Конструктор
     */
    public function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        add_action('wp_ajax_parfume_get_comparison_data', array($this, 'ajax_get_comparison_data'));
        add_action('wp_ajax_nopriv_parfume_get_comparison_data', array($this, 'ajax_get_comparison_data'));
        add_action('wp_ajax_parfume_export_comparison', array($this, 'ajax_export_comparison'));
        add_action('wp_ajax_nopriv_parfume_export_comparison', array($this, 'ajax_export_comparison'));
        add_action('wp_footer', array($this, 'add_comparison_popup'));
        add_action('wp_footer', array($this, 'add_comparison_templates'));
        add_filter('body_class', array($this, 'add_comparison_body_class'));
        add_action('wp_head', array($this, 'add_comparison_styles'));
    }

    /**
     * Enqueue frontend assets
     */
    public function enqueue_frontend_assets() {
        if (!$this->should_load_comparison()) {
            return;
        }

        wp_enqueue_script(
            'parfume-comparison',
            PARFUME_CATALOG_PLUGIN_URL . 'assets/js/comparison.js',
            array('jquery'),
            PARFUME_CATALOG_VERSION,
            true
        );

        wp_enqueue_style(
            'parfume-comparison',
            PARFUME_CATALOG_PLUGIN_URL . 'assets/css/comparison.css',
            array(),
            PARFUME_CATALOG_VERSION
        );

        $settings = get_option('parfume_catalog_comparison_settings', array());
        
        wp_localize_script('parfume-comparison', 'parfumeComparison', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('parfume_comparison_nonce'),
            'maxItems' => intval($settings['max_items'] ?? 4),
            'enabled' => (bool) ($settings['enabled'] ?? true),
            'autoShowPopup' => (bool) ($settings['auto_show_popup'] ?? true),
            'enableUndo' => (bool) ($settings['enable_undo'] ?? true),
            'enableSearch' => (bool) ($settings['enable_search'] ?? true),
            'strings' => array(
                'addToComparison' => __('Добави за сравнение', 'parfume-catalog'),
                'removeFromComparison' => __('Премахни от сравнение', 'parfume-catalog'),
                'compare' => __('Сравни', 'parfume-catalog'),
                'comparing' => __('Сравняване...', 'parfume-catalog'),
                'maxItemsReached' => sprintf(__('Максимум %d парфюма могат да се сравняват едновременно', 'parfume-catalog'), intval($settings['max_items'] ?? 4)),
                'itemAdded' => __('Парфюмът е добавен за сравнение', 'parfume-catalog'),
                'itemRemoved' => __('Парфюмът е премахнат от сравнението', 'parfume-catalog'),
                'undo' => __('Отмени', 'parfume-catalog'),
                'clearAll' => __('Изчисти всички', 'parfume-catalog'),
                'exportPdf' => __('Експортирай като PDF', 'parfume-catalog'),
                'exportCsv' => __('Експортирай като CSV', 'parfume-catalog'),
                'print' => __('Принтирай', 'parfume-catalog'),
                'share' => __('Сподели', 'parfume-catalog'),
                'close' => __('Затвори', 'parfume-catalog'),
                'loading' => __('Зареждане...', 'parfume-catalog'),
                'error' => __('Възникна грешка', 'parfume-catalog'),
                'noParfumesSelected' => __('Няма избрани парфюми за сравнение', 'parfume-catalog'),
                'searchPlaceholder' => __('Търси парфюм за добавяне...', 'parfume-catalog'),
                'noResults' => __('Няма намерени резултати', 'parfume-catalog'),
                'confirmClearAll' => __('Сигурни ли сте, че искате да изчистите всички парфюми от сравнението?', 'parfume-catalog')
            )
        ));
    }

    /**
     * Проверка дали да се зареди comparison
     */
    private function should_load_comparison() {
        $settings = get_option('parfume_catalog_comparison_settings', array());
        
        if (!($settings['enabled'] ?? true)) {
            return false;
        }

        // Зареждай на всички parfume страници
        return is_singular('parfumes') || 
               is_post_type_archive('parfumes') || 
               is_tax('parfume_type') || 
               is_tax('parfume_vid') || 
               is_tax('parfume_marki') || 
               is_tax('parfume_season') || 
               is_tax('parfume_intensity') || 
               is_tax('parfume_notes');
    }

    /**
     * Добавяне на comparison popup в footer
     */
    public function add_comparison_popup() {
        if (!$this->should_load_comparison()) {
            return;
        }

        $settings = get_option('parfume_catalog_comparison_settings', array());
        $enabled_criteria = $this->get_enabled_criteria();
        ?>
        <div id="parfume-comparison-popup" class="parfume-comparison-popup" style="display: none;">
            <div class="comparison-popup-header">
                <h3><?php _e('Сравнение на парфюми', 'parfume-catalog'); ?></h3>
                <div class="comparison-controls">
                    <?php if ($settings['enable_search'] ?? true): ?>
                        <div class="comparison-search">
                            <input type="text" 
                                   id="comparison-search" 
                                   placeholder="<?php _e('Търси парфюм за добавяне...', 'parfume-catalog'); ?>" />
                            <div id="comparison-search-results" class="search-results"></div>
                        </div>
                    <?php endif; ?>
                    
                    <div class="comparison-actions">
                        <button type="button" id="comparison-clear-all" class="btn btn-secondary">
                            <?php _e('Изчисти всички', 'parfume-catalog'); ?>
                        </button>
                        
                        <div class="comparison-export-dropdown">
                            <button type="button" class="btn btn-secondary dropdown-toggle" id="comparison-export">
                                <?php _e('Експортирай', 'parfume-catalog'); ?>
                                <span class="caret"></span>
                            </button>
                            <ul class="dropdown-menu">
                                <li><a href="#" id="export-pdf"><?php _e('PDF файл', 'parfume-catalog'); ?></a></li>
                                <li><a href="#" id="export-csv"><?php _e('CSV файл', 'parfume-catalog'); ?></a></li>
                                <li><a href="#" id="export-print"><?php _e('Принтирай', 'parfume-catalog'); ?></a></li>
                            </ul>
                        </div>
                        
                        <button type="button" id="comparison-share" class="btn btn-secondary">
                            <?php _e('Сподели', 'parfume-catalog'); ?>
                        </button>
                        
                        <button type="button" class="comparison-close btn btn-close">
                            <span>&times;</span>
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="comparison-popup-body">
                <div id="comparison-loading" class="comparison-loading" style="display: none;">
                    <div class="loading-spinner"></div>
                    <p><?php _e('Зареждане на данни за сравнение...', 'parfume-catalog'); ?></p>
                </div>
                
                <div id="comparison-empty" class="comparison-empty">
                    <div class="empty-state">
                        <span class="empty-icon">🌸</span>
                        <h4><?php _e('Няма избрани парфюми', 'parfume-catalog'); ?></h4>
                        <p><?php _e('Започнете да добавяте парфюми за сравнение като кликнете върху бутона "Добави за сравнение" при всеки парфюм.', 'parfume-catalog'); ?></p>
                    </div>
                </div>
                
                <div id="comparison-table-container" class="comparison-table-container" style="display: none;">
                    <div class="table-responsive">
                        <table class="comparison-table" id="comparison-table">
                            <thead>
                                <tr id="comparison-table-header">
                                    <th class="criteria-column"><?php _e('Критерий', 'parfume-catalog'); ?></th>
                                    <!-- Parfume columns will be added dynamically -->
                                </tr>
                            </thead>
                            <tbody id="comparison-table-body">
                                <!-- Rows will be added dynamically -->
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <div id="comparison-error" class="comparison-error" style="display: none;">
                    <div class="error-state">
                        <span class="error-icon">⚠️</span>
                        <h4><?php _e('Възникна грешка', 'parfume-catalog'); ?></h4>
                        <p id="comparison-error-message"></p>
                        <button type="button" id="comparison-retry" class="btn btn-primary">
                            <?php _e('Опитай отново', 'parfume-catalog'); ?>
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="comparison-popup-footer">
                <div class="comparison-info">
                    <span id="comparison-count">0</span> / <?php echo intval($settings['max_items'] ?? 4); ?> парфюма
                </div>
                
                <?php if ($settings['enable_undo'] ?? true): ?>
                    <div id="comparison-undo" class="comparison-undo" style="display: none;">
                        <span class="undo-message"></span>
                        <button type="button" class="undo-btn"><?php _e('Отмени', 'parfume-catalog'); ?></button>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Comparison Overlay -->
        <div id="comparison-overlay" class="comparison-overlay" style="display: none;"></div>

        <!-- Floating Comparison Button -->
        <div id="comparison-floating-btn" class="comparison-floating-btn" style="display: none;">
            <button type="button" class="floating-btn">
                <span class="btn-icon">⚖️</span>
                <span class="btn-text"><?php _e('Сравни', 'parfume-catalog'); ?></span>
                <span class="btn-count">0</span>
            </button>
        </div>
        <?php
    }

    /**
     * Получаване на максимален брой items за сравнение
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
        
        $parfume_ids = array_map('intval', $_POST['parfume_ids'] ?? array());
        
        if (empty($parfume_ids)) {
            wp_send_json_error(__('Няма избрани парфюми', 'parfume-catalog'));
        }

        $max_items = $this->get_max_comparison_items();
        if (count($parfume_ids) > $max_items) {
            wp_send_json_error(sprintf(__('Максимум %d парфюма могат да се сравняват едновременно', 'parfume-catalog'), $max_items));
        }
        
        $comparison_data = array();
        
        foreach ($parfume_ids as $parfume_id) {
            $parfume_data = $this->get_parfume_comparison_data($parfume_id);
            if ($parfume_data) {
                $comparison_data[] = $parfume_data;
            }
        }
        
        if (empty($comparison_data)) {
            wp_send_json_error(__('Не са намерени валидни парфюми за сравнение', 'parfume-catalog'));
        }
        
        wp_send_json_success(array(
            'parfumes' => $comparison_data,
            'criteria' => $this->get_enabled_criteria(),
            'count' => count($comparison_data)
        ));
    }

    /**
     * AJAX - Търсене на парфюми за добавяне в сравнението
     */
    public function ajax_search_parfumes() {
        check_ajax_referer('parfume_comparison_nonce', 'nonce');
        
        $search_term = sanitize_text_field($_POST['search'] ?? '');
        
        if (strlen($search_term) < 2) {
            wp_send_json_error(__('Моля, въведете поне 2 символа', 'parfume-catalog'));
        }
        
        $query_args = array(
            'post_type' => 'parfumes',
            'post_status' => 'publish',
            'posts_per_page' => 10,
            's' => $search_term,
            'meta_query' => array(
                'relation' => 'OR',
                array(
                    'key' => '_parfume_name',
                    'value' => $search_term,
                    'compare' => 'LIKE'
                )
            ),
            'tax_query' => array(
                'relation' => 'OR',
                array(
                    'taxonomy' => 'parfume_marki',
                    'field' => 'name',
                    'terms' => $search_term,
                    'operator' => 'LIKE'
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
                    'url' => get_permalink(),
                    'image' => get_the_post_thumbnail_url(get_the_ID(), 'thumbnail'),
                    'excerpt' => wp_trim_words(get_the_excerpt(), 15)
                );
            }
            wp_reset_postdata();
        }
        
        wp_send_json_success($results);
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
        $data['brand'] = !empty($brand_terms) ? $brand_terms[0]->name : '—';

        // Тип
        $type_terms = wp_get_object_terms($parfume_id, 'parfume_type');
        $data['type'] = !empty($type_terms) ? implode(', ', wp_list_pluck($type_terms, 'name')) : '—';

        // Вид аромат (Концентрация)
        $vid_terms = wp_get_object_terms($parfume_id, 'parfume_vid');
        $data['concentration'] = !empty($vid_terms) ? implode(', ', wp_list_pluck($vid_terms, 'name')) : '—';

        // Година на издаване
        $data['launch_year'] = get_post_meta($parfume_id, '_parfume_launch_year', true) ?: '—';

        // Нотки
        $top_notes = get_post_meta($parfume_id, '_parfume_top_notes', true);
        $data['top_notes'] = is_array($top_notes) ? implode(', ', $top_notes) : ($top_notes ?: '—');

        $middle_notes = get_post_meta($parfume_id, '_parfume_middle_notes', true);
        $data['middle_notes'] = is_array($middle_notes) ? implode(', ', $middle_notes) : ($middle_notes ?: '—');

        $base_notes = get_post_meta($parfume_id, '_parfume_base_notes', true);
        $data['base_notes'] = is_array($base_notes) ? implode(', ', $base_notes) : ($base_notes ?: '—');

        // Статистики
        $longevity = get_post_meta($parfume_id, '_parfume_longevity', true);
        $data['longevity'] = $this->format_rating_display($longevity, array('много слаб', 'слаб', 'умерен', 'траен', 'изключително траен'));

        $sillage = get_post_meta($parfume_id, '_parfume_sillage', true);
        $data['sillage'] = $this->format_rating_display($sillage, array('слаба', 'умерена', 'силна', 'огромна'));

        $price_rating = get_post_meta($parfume_id, '_parfume_price_rating', true);
        $data['price_rating'] = $this->format_rating_display($price_rating, array('прекалено скъп', 'скъп', 'приемлива цена', 'добра цена', 'евтин'));

        // Подходящи сезони
        $season_terms = wp_get_object_terms($parfume_id, 'parfume_season');
        $data['suitable_seasons'] = !empty($season_terms) ? implode(', ', wp_list_pluck($season_terms, 'name')) : '—';

        // Време на носене
        $day_suitable = get_post_meta($parfume_id, '_parfume_day_suitable', true);
        $night_suitable = get_post_meta($parfume_id, '_parfume_night_suitable', true);
        
        $suitable_times = array();
        if ($day_suitable) $suitable_times[] = 'Ден';
        if ($night_suitable) $suitable_times[] = 'Нощ';
        $data['suitable_time'] = !empty($suitable_times) ? implode(', ', $suitable_times) : '—';

        // Интензивност
        $intensity_terms = wp_get_object_terms($parfume_id, 'parfume_intensity');
        $data['intensity'] = !empty($intensity_terms) ? implode(', ', wp_list_pluck($intensity_terms, 'name')) : '—';

        // Ценови диапазон (от stores данни)
        $stores_data = get_post_meta($parfume_id, '_parfume_stores', true);
        $price_range = $this->calculate_price_range($stores_data);
        $data['price_range'] = $price_range ?: '—';

        // Рейтинг (средна оценка от коментари)
        $data['rating'] = $this->get_average_rating($parfume_id);

        // Предимства и недостатъци
        $advantages = get_post_meta($parfume_id, '_parfume_advantages', true);
        $data['advantages'] = is_array($advantages) ? implode(', ', $advantages) : ($advantages ?: '—');

        $disadvantages = get_post_meta($parfume_id, '_parfume_disadvantages', true);
        $data['disadvantages'] = is_array($disadvantages) ? implode(', ', $disadvantages) : ($disadvantages ?: '—');

        return $data;
    }

    /**
     * Форматиране на rating за показване
     */
    private function format_rating_display($rating, $labels) {
        if (!$rating || !is_numeric($rating)) {
            return '—';
        }
        
        $index = intval($rating) - 1;
        if ($index >= 0 && $index < count($labels)) {
            return $labels[$index];
        }
        
        return '—';
    }

    /**
     * Изчисляване на ценов диапазон от stores данни
     */
    private function calculate_price_range($stores_data) {
        if (!is_array($stores_data) || empty($stores_data)) {
            return null;
        }

        $prices = array();
        
        // Използваме scraper класа за получаване на цени
        if (class_exists('Parfume_Catalog_Scraper')) {
            $scraper = new Parfume_Catalog_Scraper();
            
            foreach ($stores_data as $store_id => $store_data) {
                if (!empty($store_data['product_url'])) {
                    $scraped_data = $scraper->get_scraped_data($store_data['post_id'], $store_id);
                    
                    if ($scraped_data && !empty($scraped_data['data']['price'])) {
                        $prices[] = floatval($scraped_data['data']['price']);
                    }
                }
            }
        }
        
        if (empty($prices)) {
            return null;
        }
        
        $min_price = min($prices);
        $max_price = max($prices);
        
        if ($min_price == $max_price) {
            return $min_price . ' лв.';
        }
        
        return $min_price . ' - ' . $max_price . ' лв.';
    }

    /**
     * Получаване на средна оценка
     */
    private function get_average_rating($parfume_id) {
        global $wpdb;
        
        $comments_table = $wpdb->prefix . 'parfume_comments';
        
        $average = $wpdb->get_var($wpdb->prepare("
            SELECT AVG(rating) 
            FROM $comments_table 
            WHERE post_id = %d AND status = 'approved' AND rating > 0
        ", $parfume_id));
        
        if ($average) {
            return round($average, 1) . '/5 ⭐';
        }
        
        return '—';
    }

    /**
     * AJAX - Експортиране на comparison
     */
    public function ajax_export_comparison() {
        check_ajax_referer('parfume_comparison_nonce', 'nonce');
        
        $parfume_ids = array_map('intval', $_POST['parfume_ids'] ?? array());
        $format = sanitize_text_field($_POST['format'] ?? 'csv');
        
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
        
        if (empty($comparison_data)) {
            wp_send_json_error(__('Не са намерени данни за експорт', 'parfume-catalog'));
        }
        
        $criteria = $this->get_enabled_criteria();
        
        switch ($format) {
            case 'csv':
                $export_data = $this->export_to_csv($comparison_data, $criteria);
                break;
            case 'pdf':
                $export_data = $this->export_to_pdf($comparison_data, $criteria);
                break;
            case 'print':
                $export_data = $this->export_to_print($comparison_data, $criteria);
                break;
            default:
                wp_send_json_error(__('Неподдържан формат за експорт', 'parfume-catalog'));
        }
        
        wp_send_json_success($export_data);
    }

    /**
     * Експорт към CSV
     */
    private function export_to_csv($comparison_data, $criteria) {
        $csv_data = array();
        
        // Header row
        $header = array(__('Критерий', 'parfume-catalog'));
        foreach ($comparison_data as $parfume) {
            $header[] = $parfume['name'];
        }
        $csv_data[] = $header;
        
        // Data rows
        foreach ($criteria as $key => $criterion) {
            $row = array($criterion['label']);
            foreach ($comparison_data as $parfume) {
                $row[] = isset($parfume[$key]) ? $parfume[$key] : '—';
            }
            $csv_data[] = $row;
        }
        
        // Generate CSV content
        $csv_content = '';
        foreach ($csv_data as $row) {
            $csv_content .= implode(',', array_map(array($this, 'csv_escape'), $row)) . "\n";
        }
        
        return array(
            'content' => $csv_content,
            'filename' => 'parfume-comparison-' . date('Y-m-d-H-i-s') . '.csv',
            'type' => 'text/csv'
        );
    }

    /**
     * Експорт към PDF (HTML за PDF конвертация)
     */
    private function export_to_pdf($comparison_data, $criteria) {
        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title><?php _e('Сравнение на парфюми', 'parfume-catalog'); ?></title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                th { background-color: #f2f2f2; font-weight: bold; }
                .parfume-image { max-width: 60px; height: auto; }
                h1 { color: #333; text-align: center; }
                .comparison-info { margin-bottom: 20px; text-align: center; color: #666; }
            </style>
        </head>
        <body>
            <h1><?php _e('Сравнение на парфюми', 'parfume-catalog'); ?></h1>
            <div class="comparison-info">
                <?php printf(__('Генерирано на %s', 'parfume-catalog'), date('d.m.Y H:i')); ?>
            </div>
            
            <table>
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
        </body>
        </html>
        <?php
        
        return array(
            'content' => ob_get_clean(),
            'type' => 'html'
        );
    }

    /**
     * Експорт за принтиране
     */
    private function export_to_print($comparison_data, $criteria) {
        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title><?php _e('Сравнение на парфюми', 'parfume-catalog'); ?></title>
            <style>
                body { font-family: Arial, sans-serif; margin: 0; padding: 20px; }
                table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
                th { background-color: #f8f9fa; font-weight: bold; }
                .parfume-image { max-width: 80px; height: auto; }
                h1 { color: #333; text-align: center; margin-bottom: 30px; }
                .comparison-info { margin-bottom: 20px; text-align: center; color: #666; font-size: 14px; }
                @media print {
                    body { margin: 0; }
                    .no-print { display: none; }
                }
            </style>
        </head>
        <body>
            <h1><?php _e('Сравнение на парфюми', 'parfume-catalog'); ?></h1>
            <div class="comparison-info">
                <?php printf(__('Генерирано на %s от %s', 'parfume-catalog'), date('d.m.Y H:i'), home_url()); ?>
            </div>
            
            <table>
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
        // Remove HTML tags and decode entities
        $value = html_entity_decode(strip_tags($value), ENT_QUOTES, 'UTF-8');
        
        // Escape quotes and wrap in quotes if necessary
        if (strpos($value, ',') !== false || strpos($value, '"') !== false || strpos($value, "\n") !== false) {
            $value = '"' . str_replace('"', '""', $value) . '"';
        }
        
        return $value;
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
                    <td class="criterion-label"><strong>{{label}}</strong></td>
                    {{#parfumes}}
                    <td class="parfume-data">{{value}}</td>
                    {{/parfumes}}
                </tr>
            </script>

            <!-- Comparison Table Header Template -->
            <script type="text/template" id="comparison-header-template">
                <th class="criteria-column"><?php _e('Критерий', 'parfume-catalog'); ?></th>
                {{#parfumes}}
                <th class="parfume-column" data-parfume-id="{{id}}">
                    <div class="parfume-header">
                        {{#image}}
                        <img src="{{image}}" alt="{{name}}" class="parfume-thumb" />
                        {{/image}}
                        <div class="parfume-name">{{name}}</div>
                        <button type="button" class="remove-parfume" data-parfume-id="{{id}}" title="<?php _e('Премахни', 'parfume-catalog'); ?>">
                            <span>&times;</span>
                        </button>
                    </div>
                </th>
                {{/parfumes}}
            </script>

            <!-- Search Result Template -->
            <script type="text/template" id="search-result-template">
                <div class="search-result-item" data-parfume-id="{{id}}">
                    {{#image}}
                    <img src="{{image}}" alt="{{title}}" class="result-thumb" />
                    {{/image}}
                    <div class="result-content">
                        <div class="result-title">{{title}}</div>
                        {{#brand}}
                        <div class="result-brand">{{brand}}</div>
                        {{/brand}}
                        {{#excerpt}}
                        <div class="result-excerpt">{{excerpt}}</div>
                        {{/excerpt}}
                    </div>
                    <button type="button" class="add-to-comparison" data-parfume-id="{{id}}">
                        <?php _e('Добави', 'parfume-catalog'); ?>
                    </button>
                </div>
            </script>
            <?php
        }
    }

    /**
     * Добавяне на inline стилове
     */
    public function add_comparison_styles() {
        if (!$this->should_load_comparison()) {
            return;
        }

        echo '<style type="text/css">
        .parfume-comparison-popup {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 90vw;
            max-width: 1200px;
            height: 80vh;
            max-height: 600px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            z-index: 10001;
            display: flex;
            flex-direction: column;
        }
        
        .comparison-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 10000;
        }
        
        .comparison-popup-header {
            padding: 20px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .comparison-popup-body {
            flex: 1;
            overflow: auto;
            padding: 20px;
        }
        
        .comparison-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        .comparison-table th,
        .comparison-table td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: left;
            vertical-align: top;
        }
        
        .comparison-table th {
            background: #f8f9fa;
            font-weight: 600;
        }
        
        .parfume-thumb {
            width: 50px;
            height: auto;
            border-radius: 4px;
            margin-bottom: 8px;
        }
        
        .comparison-floating-btn {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 1000;
        }
        
        .floating-btn {
            background: #007cba;
            color: #fff;
            border: none;
            border-radius: 50px;
            padding: 12px 20px;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .floating-btn:hover {
            background: #005a87;
            transform: translateY(-2px);
        }
        
        .btn-count {
            background: #dc3545;
            color: #fff;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: 600;
        }
        
        .parfume-comparison-btn {
            background: #007cba;
            color: #fff;
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
        
        .comparison-empty,
        .comparison-error {
            text-align: center;
            padding: 40px 20px;
        }
        
        .empty-icon,
        .error-icon {
            font-size: 48px;
            margin-bottom: 16px;
            display: block;
        }
        
        .loading-spinner {
            border: 3px solid #f3f3f3;
            border-top: 3px solid #007cba;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto 16px;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
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
        </style>';
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

    /**
     * Shortcode за показване на comparison бутон
     */
    public function comparison_button_shortcode($atts) {
        $atts = shortcode_atts(array(
            'parfume_id' => get_the_ID(),
            'text' => __('Добави за сравнение', 'parfume-catalog'),
            'class' => 'parfume-comparison-btn'
        ), $atts);

        if (!$this->should_load_comparison()) {
            return '';
        }

        return sprintf(
            '<button type="button" class="%s" data-parfume-id="%d" data-action="add">%s</button>',
            esc_attr($atts['class']),
            intval($atts['parfume_id']),
            esc_html($atts['text'])
        );
    }

    /**
     * Helper функция за добавяне на comparison бутон в темплейти
     */
    public static function render_comparison_button($parfume_id = null, $args = array()) {
        if (!$parfume_id) {
            $parfume_id = get_the_ID();
        }

        $defaults = array(
            'text' => __('Добави за сравнение', 'parfume-catalog'),
            'class' => 'parfume-comparison-btn',
            'echo' => true
        );

        $args = wp_parse_args($args, $defaults);

        $button = sprintf(
            '<button type="button" class="%s" data-parfume-id="%d" data-action="add">%s</button>',
            esc_attr($args['class']),
            intval($parfume_id),
            esc_html($args['text'])
        );

        if ($args['echo']) {
            echo $button;
        } else {
            return $button;
        }
    }
}

// Initialize the comparison module
new Parfume_Catalog_Comparison();

// Register shortcode
add_shortcode('parfume_comparison_button', array('Parfume_Catalog_Comparison', 'comparison_button_shortcode'));