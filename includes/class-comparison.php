<?php
namespace Parfume_Reviews;

/**
 * Comparison class - управлява функционалността за сравнение на парфюми
 * 
 * Файл: includes/class-comparison.php
 * РЕВИЗИРАНА ВЕРСИЯ - ПОДОБРЕНА ФУНКЦИОНАЛНОСТ И SECURITY
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Comparison класа
 * ВАЖНО: Управлява пълната функционалност за сравнение на парфюми
 */
class Comparison {
    
    /**
     * Максимален брой парфюми за сравнение
     * @var int
     */
    private $max_items = 4;
    
    /**
     * Cookie име за съхранение на comparison данни
     * @var string
     */
    private $cookie_name = 'parfume_comparison';
    
    /**
     * Constructor
     * ВАЖНО: Инициализира всички hook-ове и настройки
     */
    public function __construct() {
        $this->init_hooks();
        $this->init_settings();
    }
    
    /**
     * Инициализира всички hook-ове
     * ВАЖНО: Регистрира всички необходими WordPress hook-ове
     */
    private function init_hooks() {
        // Frontend hooks
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_footer', array($this, 'add_comparison_widget'));
        
        // AJAX hooks
        add_action('wp_ajax_add_to_comparison', array($this, 'ajax_add_to_comparison'));
        add_action('wp_ajax_nopriv_add_to_comparison', array($this, 'ajax_add_to_comparison'));
        add_action('wp_ajax_remove_from_comparison', array($this, 'ajax_remove_from_comparison'));
        add_action('wp_ajax_nopriv_remove_from_comparison', array($this, 'ajax_remove_from_comparison'));
        add_action('wp_ajax_get_comparison_table', array($this, 'ajax_get_comparison_table'));
        add_action('wp_ajax_nopriv_get_comparison_table', array($this, 'ajax_get_comparison_table'));
        add_action('wp_ajax_clear_comparison', array($this, 'ajax_clear_comparison'));
        add_action('wp_ajax_nopriv_clear_comparison', array($this, 'ajax_clear_comparison'));
        
        // Shortcode
        add_shortcode('parfume_comparison', array($this, 'comparison_shortcode'));
        
        // Admin hooks
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_admin_settings'));
    }
    
    /**
     * Инициализира настройките
     * ВАЖНО: Зарежда настройки от базата данни
     */
    private function init_settings() {
        $settings = get_option('parfume_reviews_settings', array());
        
        // Максимален брой елементи за сравнение
        if (isset($settings['comparison_max_items']) && is_numeric($settings['comparison_max_items'])) {
            $this->max_items = intval($settings['comparison_max_items']);
        }
        
        // Cookie настройки
        if (isset($settings['comparison_cookie_name']) && !empty($settings['comparison_cookie_name'])) {
            $this->cookie_name = sanitize_text_field($settings['comparison_cookie_name']);
        }
    }
    
    /**
     * РАЗДЕЛ 1: SCRIPTS И STYLES
     */
    
    /**
     * Enqueue scripts и styles
     * ВАЖНО: Зарежда необходимите ресурси само когато е нужно
     */
    public function enqueue_scripts() {
        // Зареждаме само на парфюмни страници
        if (!$this->is_comparison_page()) {
            return;
        }
        
        $plugin_version = defined('PARFUME_REVIEWS_VERSION') ? PARFUME_REVIEWS_VERSION : '1.0.0';
        
        // CSS за comparison
        if (file_exists(PARFUME_REVIEWS_PLUGIN_DIR . 'assets/css/comparison.css')) {
            wp_enqueue_style(
                'parfume-comparison',
                PARFUME_REVIEWS_PLUGIN_URL . 'assets/css/comparison.css',
                array(),
                $plugin_version
            );
        }
        
        // JS за comparison
        if (file_exists(PARFUME_REVIEWS_PLUGIN_DIR . 'assets/js/comparison.js')) {
            wp_enqueue_script(
                'parfume-comparison',
                PARFUME_REVIEWS_PLUGIN_URL . 'assets/js/comparison.js',
                array('jquery'),
                $plugin_version,
                true
            );
            
            // Localize script
            wp_localize_script('parfume-comparison', 'parfumeComparison', array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('parfume-comparison-nonce'),
                'maxItems' => $this->max_items,
                'cookieName' => $this->cookie_name,
                'strings' => array(
                    'addedText' => __('В сравнение', 'parfume-reviews'),
                    'addText' => __('Добави за сравнение', 'parfume-reviews'),
                    'removeText' => __('Премахни', 'parfume-reviews'),
                    'compareText' => __('Сравни', 'parfume-reviews'),
                    'emptyText' => __('Няма елементи за сравнение', 'parfume-reviews'),
                    'alreadyAddedText' => __('Вече е добавен за сравнение', 'parfume-reviews'),
                    'maxItemsText' => sprintf(__('Можете да сравнявате максимум %d парфюма', 'parfume-reviews'), $this->max_items),
                    'addSuccessText' => __('Добавен за сравнение', 'parfume-reviews'),
                    'removeSuccessText' => __('Премахнат от сравнение', 'parfume-reviews'),
                    'clearSuccessText' => __('Изчистени всички за сравнение', 'parfume-reviews'),
                    'loadingText' => __('Зареждане...', 'parfume-reviews'),
                    'errorText' => __('Възникна грешка. Моля опитайте отново.', 'parfume-reviews')
                )
            ));
        }
    }
    
    /**
     * РАЗДЕЛ 2: AJAX HANDLERS
     */
    
    /**
     * AJAX handler за добавяне към сравнение
     * ВАЖНО: Основната функция за добавяне на парфюм в сравнение
     */
    public function ajax_add_to_comparison() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'parfume-comparison-nonce')) {
            wp_send_json_error(__('Грешка в сигурността.', 'parfume-reviews'));
        }
        
        // Validate post ID
        if (!isset($_POST['post_id']) || !is_numeric($_POST['post_id'])) {
            wp_send_json_error(__('Невалиден ID на парфюм.', 'parfume-reviews'));
        }
        
        $post_id = intval($_POST['post_id']);
        
        // Check if post exists and is parfume
        $post = get_post($post_id);
        if (!$post || $post->post_type !== 'parfume') {
            wp_send_json_error(__('Парфюмът не съществува.', 'parfume-reviews'));
        }
        
        // Get current comparison items
        $comparison_items = $this->get_comparison_items();
        
        // Check if already added
        if ($this->is_in_comparison($post_id, $comparison_items)) {
            wp_send_json_error(__('Парфюмът вече е добавен за сравнение.', 'parfume-reviews'));
        }
        
        // Check maximum items
        if (count($comparison_items) >= $this->max_items) {
            wp_send_json_error(sprintf(__('Можете да сравнявате максимум %d парфюма.', 'parfume-reviews'), $this->max_items));
        }
        
        // Add to comparison
        $comparison_items[] = array(
            'id' => $post_id,
            'title' => get_the_title($post_id),
            'url' => get_permalink($post_id),
            'image' => get_the_post_thumbnail_url($post_id, 'thumbnail'),
            'added_time' => current_time('timestamp')
        );
        
        // Save to cookie
        $this->save_comparison_items($comparison_items);
        
        // Return success response
        wp_send_json_success(array(
            'message' => __('Добавен за сравнение успешно!', 'parfume-reviews'),
            'count' => count($comparison_items),
            'items' => $comparison_items
        ));
    }
    
    /**
     * AJAX handler за премахване от сравнение
     * ВАЖНО: Премахва парфюм от сравнението
     */
    public function ajax_remove_from_comparison() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'parfume-comparison-nonce')) {
            wp_send_json_error(__('Грешка в сигурността.', 'parfume-reviews'));
        }
        
        // Validate post ID
        if (!isset($_POST['post_id']) || !is_numeric($_POST['post_id'])) {
            wp_send_json_error(__('Невалиден ID на парфюм.', 'parfume-reviews'));
        }
        
        $post_id = intval($_POST['post_id']);
        
        // Get current comparison items
        $comparison_items = $this->get_comparison_items();
        
        // Remove from comparison
        $comparison_items = array_filter($comparison_items, function($item) use ($post_id) {
            return intval($item['id']) !== $post_id;
        });
        
        // Re-index array
        $comparison_items = array_values($comparison_items);
        
        // Save to cookie
        $this->save_comparison_items($comparison_items);
        
        // Return success response
        wp_send_json_success(array(
            'message' => __('Премахнат от сравнение успешно!', 'parfume-reviews'),
            'count' => count($comparison_items),
            'items' => $comparison_items
        ));
    }
    
    /**
     * AJAX handler за получаване на comparison таблица
     * ВАЖНО: Генерира HTML таблицата за сравнение
     */
    public function ajax_get_comparison_table() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'parfume-comparison-nonce')) {
            wp_send_json_error(__('Грешка в сигурността.', 'parfume-reviews'));
        }
        
        // Get comparison items
        $comparison_items = $this->get_comparison_items();
        
        if (empty($comparison_items)) {
            wp_send_json_error(__('Няма елементи за сравнение.', 'parfume-reviews'));
        }
        
        // Get post IDs
        $post_ids = array_column($comparison_items, 'id');
        
        // Query parfumes
        $args = array(
            'post_type' => 'parfume',
            'post__in' => $post_ids,
            'posts_per_page' => -1,
            'orderby' => 'post__in'
        );
        
        $query = new \WP_Query($args);
        
        if (!$query->have_posts()) {
            wp_send_json_error(__('Няма намерени парфюми за сравнение.', 'parfume-reviews'));
        }
        
        // Generate comparison table HTML
        ob_start();
        $this->render_comparison_table($query);
        $html = ob_get_clean();
        
        wp_reset_postdata();
        
        // Return success response
        wp_send_json_success(array(
            'html' => $html,
            'count' => $query->post_count
        ));
    }
    
    /**
     * AJAX handler за изчистване на сравнението
     * ВАЖНО: Изчиства всички парфюми от сравнението
     */
    public function ajax_clear_comparison() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'parfume-comparison-nonce')) {
            wp_send_json_error(__('Грешка в сигурността.', 'parfume-reviews'));
        }
        
        // Clear comparison
        $this->save_comparison_items(array());
        
        // Return success response
        wp_send_json_success(array(
            'message' => __('Изчистени всички парфюми за сравнение!', 'parfume-reviews'),
            'count' => 0
        ));
    }
    
    /**
     * РАЗДЕЛ 3: COMPARISON LOGIC
     */
    
    /**
     * Получава comparison елементите от cookie
     * ВАЖНО: Основната функция за получаване на comparison данни
     */
    private function get_comparison_items() {
        if (!isset($_COOKIE[$this->cookie_name])) {
            return array();
        }
        
        $items = json_decode(stripslashes($_COOKIE[$this->cookie_name]), true);
        
        if (!is_array($items)) {
            return array();
        }
        
        // Валидираме и почистваме данните
        $validated_items = array();
        foreach ($items as $item) {
            if (isset($item['id']) && is_numeric($item['id']) && get_post($item['id'])) {
                $validated_items[] = array(
                    'id' => intval($item['id']),
                    'title' => isset($item['title']) ? sanitize_text_field($item['title']) : get_the_title($item['id']),
                    'url' => isset($item['url']) ? esc_url($item['url']) : get_permalink($item['id']),
                    'image' => isset($item['image']) ? esc_url($item['image']) : get_the_post_thumbnail_url($item['id'], 'thumbnail'),
                    'added_time' => isset($item['added_time']) ? intval($item['added_time']) : current_time('timestamp')
                );
            }
        }
        
        return $validated_items;
    }
    
    /**
     * Запазва comparison елементите в cookie
     * ВАЖНО: Запазва данните за comparison в браузъра
     */
    private function save_comparison_items($items) {
        $cookie_value = json_encode($items);
        
        // Set cookie за 30 дни
        setcookie(
            $this->cookie_name,
            $cookie_value,
            time() + (30 * DAY_IN_SECONDS),
            COOKIEPATH,
            COOKIE_DOMAIN,
            is_ssl(),
            true // HTTP only
        );
        
        // Set за текущата заявка
        $_COOKIE[$this->cookie_name] = $cookie_value;
    }
    
    /**
     * Проверява дали парфюм е в сравнението
     * ВАЖНО: Helper функция за проверка на наличие
     */
    private function is_in_comparison($post_id, $comparison_items = null) {
        if ($comparison_items === null) {
            $comparison_items = $this->get_comparison_items();
        }
        
        foreach ($comparison_items as $item) {
            if (intval($item['id']) === intval($post_id)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * РАЗДЕЛ 4: FRONTEND DISPLAY
     */
    
    /**
     * Добавя comparison widget в footer
     * ВАЖНО: Floating widget за бърз достъп до сравнението
     */
    public function add_comparison_widget() {
        if (!$this->is_comparison_page()) {
            return;
        }
        
        ?>
        <div id="comparison-widget" class="comparison-widget" style="display: none;">
            <div class="widget-content">
                <div class="widget-header">
                    <span class="widget-title"><?php _e('Сравнение', 'parfume-reviews'); ?></span>
                    <span class="widget-count">0</span>
                </div>
                <div class="widget-actions">
                    <button class="compare-button" disabled><?php _e('Сравни', 'parfume-reviews'); ?></button>
                    <button class="clear-button"><?php _e('Изчисти', 'parfume-reviews'); ?></button>
                </div>
            </div>
        </div>
        
        <!-- Comparison Modal -->
        <div id="comparison-modal" class="comparison-modal" style="display: none;">
            <div class="modal-backdrop"></div>
            <div class="modal-content">
                <div class="modal-header">
                    <h2><?php _e('Сравнение на парфюми', 'parfume-reviews'); ?></h2>
                    <button class="modal-close">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="comparison-loading">
                        <span><?php _e('Зареждане...', 'parfume-reviews'); ?></span>
                    </div>
                    <div class="comparison-table-container"></div>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Рендва comparison таблицата
     * ВАЖНО: Генерира HTML таблицата за сравнение
     */
    private function render_comparison_table($query) {
        ?>
        <div class="comparison-table-wrapper">
            <table class="comparison-table">
                <tbody>
                    <!-- Header row with parfume info -->
                    <tr class="parfume-header-row">
                        <th class="row-label"><?php _e('Парфюм', 'parfume-reviews'); ?></th>
                        <?php while ($query->have_posts()): $query->the_post(); ?>
                            <td>
                                <div class="parfume-info">
                                    <button class="remove-from-comparison" data-post-id="<?php echo get_the_ID(); ?>" title="<?php _e('Премахни', 'parfume-reviews'); ?>">
                                        ×
                                    </button>
                                    
                                    <?php if (has_post_thumbnail()): ?>
                                        <img src="<?php echo get_the_post_thumbnail_url(get_the_ID(), 'medium'); ?>" alt="<?php echo esc_attr(get_the_title()); ?>" class="parfume-image">
                                    <?php endif; ?>
                                    
                                    <h3 class="parfume-title">
                                        <a href="<?php echo get_permalink(); ?>" target="_blank">
                                            <?php echo get_the_title(); ?>
                                        </a>
                                    </h3>
                                    
                                    <?php
                                    $brands = wp_get_post_terms(get_the_ID(), 'marki', array('fields' => 'names'));
                                    if (!empty($brands)):
                                    ?>
                                        <div class="parfume-brand"><?php echo esc_html($brands[0]); ?></div>
                                    <?php endif; ?>
                                </div>
                            </td>
                        <?php endwhile; ?>
                    </tr>
                    
                    <?php
                    // Reset query for comparison rows
                    $query->rewind_posts();
                    
                    // Comparison fields
                    $comparison_fields = $this->get_comparison_fields();
                    
                    foreach ($comparison_fields as $field_key => $field_label):
                    ?>
                        <tr class="comparison-row">
                            <th class="row-label"><?php echo esc_html($field_label); ?></th>
                            <?php while ($query->have_posts()): $query->the_post(); ?>
                                <td>
                                    <?php $this->render_comparison_field($field_key, get_the_ID()); ?>
                                </td>
                            <?php endwhile; ?>
                        </tr>
                        <?php $query->rewind_posts(); ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
    
    /**
     * РАЗДЕЛ 5: COMPARISON FIELDS
     */
    
    /**
     * Получава полетата за сравнение
     * ВАЖНО: Дефинира кои полета да се показват в сравнението
     */
    private function get_comparison_fields() {
        return array(
            'rating' => __('Рейтинг', 'parfume-reviews'),
            'price' => __('Цена', 'parfume-reviews'),
            'gender' => __('Пол', 'parfume-reviews'),
            'aroma_type' => __('Тип аромат', 'parfume-reviews'),
            'season' => __('Сезон', 'parfume-reviews'),
            'intensity' => __('Интензивност', 'parfume-reviews'),
            'longevity' => __('Дълготрайност', 'parfume-reviews'),
            'sillage' => __('Прожекция', 'parfume-reviews'),
            'notes' => __('Нотки', 'parfume-reviews'),
            'release_year' => __('Година на излизане', 'parfume-reviews')
        );
    }
    
    /**
     * Рендва конкретно поле за сравнение
     * ВАЖНО: Форматира и показва данните за всяко поле
     */
    private function render_comparison_field($field_key, $post_id) {
        switch ($field_key) {
            case 'rating':
                $rating = get_post_meta($post_id, '_parfume_rating', true);
                if (!empty($rating)) {
                    echo '<div class="rating-display">';
                    echo '<div class="rating-stars">';
                    for ($i = 1; $i <= 5; $i++) {
                        echo '<span class="star' . ($i <= $rating ? ' filled' : '') . '">★</span>';
                    }
                    echo '</div>';
                    echo '<div class="rating-number">' . esc_html($rating) . '/5</div>';
                    echo '</div>';
                } else {
                    echo '—';
                }
                break;
                
            case 'price':
                $stores = get_post_meta($post_id, '_parfume_stores', true);
                if (!empty($stores) && is_array($stores)) {
                    $lowest_price = null;
                    $store_name = '';
                    
                    foreach ($stores as $store) {
                        if (!empty($store['price']) && is_numeric($store['price'])) {
                            $price = floatval($store['price']);
                            if ($lowest_price === null || $price < $lowest_price) {
                                $lowest_price = $price;
                                $store_name = $store['name'] ?? '';
                            }
                        }
                    }
                    
                    if ($lowest_price !== null) {
                        echo '<div class="price-display">';
                        echo number_format($lowest_price, 2) . ' лв.';
                        if (!empty($store_name)) {
                            echo '<div class="store-name">' . esc_html($store_name) . '</div>';
                        }
                        echo '</div>';
                    } else {
                        echo '—';
                    }
                } else {
                    echo '—';
                }
                break;
                
            case 'gender':
            case 'aroma_type':
            case 'season':
            case 'intensity':
                $terms = wp_get_post_terms($post_id, $field_key, array('fields' => 'names'));
                if (!empty($terms) && !is_wp_error($terms)) {
                    echo '<div class="taxonomy-terms">';
                    echo implode(', ', array_map('esc_html', $terms));
                    echo '</div>';
                } else {
                    echo '—';
                }
                break;
                
            case 'notes':
                $terms = wp_get_post_terms($post_id, 'notes', array('fields' => 'names'));
                if (!empty($terms) && !is_wp_error($terms)) {
                    echo '<div class="notes-list">';
                    foreach (array_slice($terms, 0, 3) as $note) {
                        echo '<span class="note-tag">' . esc_html($note) . '</span>';
                    }
                    if (count($terms) > 3) {
                        echo '<span class="more-notes">+' . (count($terms) - 3) . ' още</span>';
                    }
                    echo '</div>';
                } else {
                    echo '—';
                }
                break;
                
            case 'longevity':
            case 'sillage':
            case 'release_year':
                $value = get_post_meta($post_id, '_parfume_' . $field_key, true);
                echo !empty($value) ? esc_html($value) : '—';
                break;
                
            default:
                echo '—';
                break;
        }
    }
    
    /**
     * RAЗДЕЛ 6: SHORTCODE И UTILITY ФУНКЦИИ
     */
    
    /**
     * Shortcode за показване на comparison
     * ВАЖНО: [parfume_comparison] shortcode
     */
    public function comparison_shortcode($atts) {
        $atts = shortcode_atts(array(
            'style' => 'full', // full, compact, button
            'max_items' => $this->max_items
        ), $atts, 'parfume_comparison');
        
        // Enqueue scripts ако не са заредени
        $this->enqueue_scripts();
        
        ob_start();
        
        switch ($atts['style']) {
            case 'button':
                echo '<button class="parfume-comparison-button" data-max-items="' . esc_attr($atts['max_items']) . '">';
                echo __('Сравни парфюми', 'parfume-reviews');
                echo '</button>';
                break;
                
            case 'compact':
                $comparison_items = $this->get_comparison_items();
                echo '<div class="parfume-comparison-compact">';
                echo '<span class="comparison-count">' . count($comparison_items) . '</span>';
                echo '<button class="compare-button">' . __('Сравни', 'parfume-reviews') . '</button>';
                echo '</div>';
                break;
                
            default: // full
                $comparison_items = $this->get_comparison_items();
                if (!empty($comparison_items)) {
                    $post_ids = array_column($comparison_items, 'id');
                    $query = new \WP_Query(array(
                        'post_type' => 'parfume',
                        'post__in' => $post_ids,
                        'posts_per_page' => -1,
                        'orderby' => 'post__in'
                    ));
                    
                    if ($query->have_posts()) {
                        $this->render_comparison_table($query);
                        wp_reset_postdata();
                    }
                } else {
                    echo '<div class="no-comparison-items">';
                    echo '<p>' . __('Няма добавени парфюми за сравнение.', 'parfume-reviews') . '</p>';
                    echo '</div>';
                }
                break;
        }
        
        return ob_get_clean();
    }
    
    /**
     * Проверява дали сме на страница където е нужно comparison
     * ВАЖНО: Определя къде да се зареждат comparison ресурсите
     */
    private function is_comparison_page() {
        return is_singular('parfume') || 
               is_post_type_archive('parfume') || 
               is_tax(array('marki', 'gender', 'aroma_type', 'season', 'intensity', 'notes', 'perfumer'));
    }
    
    /**
     * РАЗДЕЛ 7: ADMIN ФУНКЦИИ
     */
    
    /**
     * Добавя admin menu за comparison настройки
     */
    public function add_admin_menu() {
        add_submenu_page(
            'edit.php?post_type=parfume',
            __('Настройки за сравнение', 'parfume-reviews'),
            __('Сравнение', 'parfume-reviews'),
            'manage_options',
            'parfume-comparison-settings',
            array($this, 'admin_page')
        );
    }
    
    /**
     * Регистрира admin настройки
     */
    public function register_admin_settings() {
        register_setting('parfume_comparison_settings', 'parfume_reviews_settings');
        
        add_settings_section(
            'comparison_settings',
            __('Настройки за сравнение', 'parfume-reviews'),
            null,
            'parfume_comparison_settings'
        );
        
        add_settings_field(
            'comparison_max_items',
            __('Максимален брой за сравнение', 'parfume-reviews'),
            array($this, 'max_items_field'),
            'parfume_comparison_settings',
            'comparison_settings'
        );
    }
    
    /**
     * Admin страница за настройки
     */
    public function admin_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Настройки за сравнение', 'parfume-reviews'); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('parfume_comparison_settings');
                do_settings_sections('parfume_comparison_settings');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }
    
    /**
     * Max items field за admin
     */
    public function max_items_field() {
        $settings = get_option('parfume_reviews_settings', array());
        $value = $settings['comparison_max_items'] ?? $this->max_items;
        ?>
        <input type="number" name="parfume_reviews_settings[comparison_max_items]" value="<?php echo esc_attr($value); ?>" min="2" max="10" />
        <p class="description"><?php _e('Максималният брой парфюми които могат да се сравняват едновременно (между 2 и 10).', 'parfume-reviews'); ?></p>
        <?php
    }
    
    /**
     * РАЗДЕЛ 8: ПУБЛИЧЕН API
     */
    
    /**
     * Получава броя елементи в сравнението
     */
    public function get_comparison_count() {
        return count($this->get_comparison_items());
    }
    
    /**
     * Получава comparison URL
     */
    public function get_comparison_url() {
        return add_query_arg('parfume_comparison', '1', home_url());
    }
    
    /**
     * Статичен метод за получаване на comparison бутон
     * ВАЖНО: Използва се в template функциите
     */
    public static function get_comparison_button($post_id) {
        if (!$post_id || get_post_type($post_id) !== 'parfume') {
            return '';
        }
        
        $button_text = __('Добави за сравнение', 'parfume-reviews');
        
        return sprintf(
            '<button type="button" class="add-to-comparison" data-post-id="%d" title="%s">
                <span class="button-icon">⚖</span>
                %s
            </button>',
            intval($post_id),
            esc_attr($button_text),
            esc_html($button_text)
        );
    }
}

// End of file