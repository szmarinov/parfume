<?php
namespace Parfume_Reviews;

/**
 * Comparison class - управлява сравняването на парфюми
 * РЕВИЗИРАНА ВЕРСИЯ: Поправени липсващи методи и зависимости
 * 
 * Файл: includes/class-comparison.php
 */
class Comparison {
    
    /**
     * Session key за съхранение на сравняваните парфюми
     */
    const SESSION_KEY = 'parfume_comparison_list';
    
    /**
     * Максимален брой парфюми за сравняване
     */
    const MAX_COMPARISON_ITEMS = 4;
    
    public function __construct() {
        // Основни хукове
        add_action('init', array($this, 'init_session'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        
        // AJAX хукове
        add_action('wp_ajax_add_to_comparison', array($this, 'ajax_add_to_comparison'));
        add_action('wp_ajax_nopriv_add_to_comparison', array($this, 'ajax_add_to_comparison'));
        add_action('wp_ajax_remove_from_comparison', array($this, 'ajax_remove_from_comparison'));
        add_action('wp_ajax_nopriv_remove_from_comparison', array($this, 'ajax_remove_from_comparison'));
        add_action('wp_ajax_get_comparison_data', array($this, 'ajax_get_comparison_data'));
        add_action('wp_ajax_nopriv_get_comparison_data', array($this, 'ajax_get_comparison_data'));
        add_action('wp_ajax_clear_comparison', array($this, 'ajax_clear_comparison'));
        add_action('wp_ajax_nopriv_clear_comparison', array($this, 'ajax_clear_comparison'));
        
        // Shortcode
        add_shortcode('parfume_comparison', array($this, 'comparison_shortcode'));
        add_shortcode('comparison_table', array($this, 'comparison_table_shortcode'));
        add_shortcode('comparison_button', array($this, 'comparison_button_shortcode'));
        
        // Widget за сравняване
        add_action('wp_footer', array($this, 'add_comparison_widget'));
        
        // Rewrite rules за comparison страница
        add_action('init', array($this, 'add_comparison_rewrite_rules'));
        add_filter('query_vars', array($this, 'add_comparison_query_vars'));
        add_action('template_redirect', array($this, 'handle_comparison_page'));
        
        if (function_exists('parfume_reviews_debug_log')) {
            parfume_reviews_debug_log("Comparison class initialized");
        }
    }
    
    /**
     * Инициализира сесията за съхранение на сравнението
     */
    public function init_session() {
        if (!session_id()) {
            session_start();
        }
        
        // Инициализираме масива ако не съществува
        if (!isset($_SESSION[self::SESSION_KEY])) {
            $_SESSION[self::SESSION_KEY] = array();
        }
    }
    
    /**
     * Enqueue scripts и styles за сравнението
     */
    public function enqueue_scripts() {
        // Зареждаме само на парфюмни страници
        if (!function_exists('parfume_reviews_is_parfume_page') || !parfume_reviews_is_parfume_page()) {
            return;
        }
        
        // CSS за сравнението
        wp_enqueue_style(
            'parfume-comparison',
            PARFUME_REVIEWS_PLUGIN_URL . 'assets/css/comparison.css',
            array(),
            PARFUME_REVIEWS_VERSION
        );
        
        // JS за сравнението
        wp_enqueue_script(
            'parfume-comparison',
            PARFUME_REVIEWS_PLUGIN_URL . 'assets/js/comparison.js',
            array('jquery'),
            PARFUME_REVIEWS_VERSION,
            true
        );
        
        // Локализация
        wp_localize_script('parfume-comparison', 'parfumeComparison', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('parfume_comparison_nonce'),
            'max_items' => self::MAX_COMPARISON_ITEMS,
            'strings' => array(
                'added' => __('Добавен за сравняване', 'parfume-reviews'),
                'removed' => __('Премахнат от сравняването', 'parfume-reviews'),
                'max_reached' => sprintf(__('Можете да сравнявате максимум %d парфюма', 'parfume-reviews'), self::MAX_COMPARISON_ITEMS),
                'empty_comparison' => __('Няма парфюми за сравняване', 'parfume-reviews'),
                'compare_now' => __('Сравни сега', 'parfume-reviews'),
                'clear_all' => __('Изчисти всички', 'parfume-reviews'),
                'loading' => __('Зареждане...', 'parfume-reviews'),
                'error' => __('Възникна грешка', 'parfume-reviews')
            )
        ));
        
        if (function_exists('parfume_reviews_debug_log')) {
            parfume_reviews_debug_log("Comparison scripts enqueued");
        }
    }
    
    /**
     * Добавя rewrite rules за comparison страница
     */
    public function add_comparison_rewrite_rules() {
        $settings = get_option('parfume_reviews_settings', array());
        $parfume_slug = !empty($settings['parfume_slug']) ? $settings['parfume_slug'] : 'parfiumi';
        
        add_rewrite_rule(
            "^{$parfume_slug}/sravnyavane/?$",
            'index.php?parfume_comparison_page=1',
            'top'
        );
        
        add_rewrite_rule(
            "^{$parfume_slug}/sravnyavane/([0-9,]+)/?$",
            'index.php?parfume_comparison_page=1&parfume_ids=$matches[1]',
            'top'
        );
    }
    
    /**
     * Добавя query vars за comparison
     */
    public function add_comparison_query_vars($vars) {
        $vars[] = 'parfume_comparison_page';
        $vars[] = 'parfume_ids';
        return $vars;
    }
    
    /**
     * Обработва comparison page requests
     */
    public function handle_comparison_page() {
        if (get_query_var('parfume_comparison_page')) {
            $this->load_comparison_page();
            exit;
        }
    }
    
    /**
     * Зарежда comparison page template
     */
    private function load_comparison_page() {
        // Получаваме ID-ата на парфюмите за сравняване
        $parfume_ids = get_query_var('parfume_ids');
        
        if ($parfume_ids) {
            $ids = explode(',', $parfume_ids);
            $ids = array_map('intval', $ids);
            $ids = array_filter($ids);
            
            // Актуализираме сесията с подадените ID-та
            $_SESSION[self::SESSION_KEY] = $ids;
        }
        
        // Зареждаме template
        $comparison_template = $this->locate_comparison_template();
        
        if ($comparison_template) {
            include $comparison_template;
        } else {
            // Fallback към inline template
            $this->render_inline_comparison_page();
        }
    }
    
    /**
     * Намира comparison template файл
     */
    private function locate_comparison_template() {
        $template_hierarchy = array(
            'parfume-comparison.php',
            'page-parfume-comparison.php',
            'comparison.php'
        );
        
        foreach ($template_hierarchy as $template_name) {
            // Първо проверяваме в темата
            $theme_template = locate_template($template_name);
            if ($theme_template) {
                return $theme_template;
            }
            
            // След това в plugin папката
            $plugin_template = PARFUME_REVIEWS_PLUGIN_DIR . 'templates/' . $template_name;
            if (file_exists($plugin_template)) {
                return $plugin_template;
            }
        }
        
        return false;
    }
    
    /**
     * Рендерира inline comparison page ако няма template
     */
    private function render_inline_comparison_page() {
        get_header();
        
        echo '<div class="comparison-page-container">';
        echo '<div class="comparison-page-content">';
        
        echo '<h1>' . __('Сравняване на парфюми', 'parfume-reviews') . '</h1>';
        
        $comparison_items = $this->get_comparison_items();
        
        if (empty($comparison_items)) {
            echo '<div class="empty-comparison">';
            echo '<p>' . __('Няма парфюми за сравняване.', 'parfume-reviews') . '</p>';
            echo '<a href="' . home_url('/parfiumi/') . '" class="button">' . __('Разгледай парфюми', 'parfume-reviews') . '</a>';
            echo '</div>';
        } else {
            echo $this->render_comparison_table($comparison_items);
        }
        
        echo '</div>';
        echo '</div>';
        
        get_footer();
    }
    
    // ===== AJAX HANDLERS =====
    
    /**
     * AJAX handler за добавяне в сравнението
     */
    public function ajax_add_to_comparison() {
        check_ajax_referer('parfume_comparison_nonce', 'nonce');
        
        $post_id = intval($_POST['post_id']);
        
        if (!$post_id || get_post_type($post_id) !== 'parfume') {
            wp_send_json_error('Невалиден парфюм');
        }
        
        $comparison_items = $this->get_comparison_items();
        
        // Проверяваме дали е вече добавен
        if (in_array($post_id, $comparison_items)) {
            wp_send_json_error('Парфюмът вече е добавен за сравняване');
        }
        
        // Проверяваме максималния брой
        if (count($comparison_items) >= self::MAX_COMPARISON_ITEMS) {
            wp_send_json_error(sprintf('Можете да сравнявате максимум %d парфюма', self::MAX_COMPARISON_ITEMS));
        }
        
        // Добавяме в сравнението
        $comparison_items[] = $post_id;
        $_SESSION[self::SESSION_KEY] = $comparison_items;
        
        $response_data = array(
            'count' => count($comparison_items),
            'items' => $this->get_comparison_items_data($comparison_items),
            'post_id' => $post_id,
            'title' => get_the_title($post_id)
        );
        
        wp_send_json_success($response_data);
    }
    
    /**
     * AJAX handler за премахване от сравнението
     */
    public function ajax_remove_from_comparison() {
        check_ajax_referer('parfume_comparison_nonce', 'nonce');
        
        $post_id = intval($_POST['post_id']);
        
        if (!$post_id) {
            wp_send_json_error('Невалиден ID');
        }
        
        $comparison_items = $this->get_comparison_items();
        $key = array_search($post_id, $comparison_items);
        
        if ($key === false) {
            wp_send_json_error('Парфюмът не е в сравнението');
        }
        
        // Премахваме от сравнението
        unset($comparison_items[$key]);
        $comparison_items = array_values($comparison_items); // Reindex array
        $_SESSION[self::SESSION_KEY] = $comparison_items;
        
        $response_data = array(
            'count' => count($comparison_items),
            'items' => $this->get_comparison_items_data($comparison_items),
            'post_id' => $post_id
        );
        
        wp_send_json_success($response_data);
    }
    
    /**
     * AJAX handler за получаване на данни за сравнението
     */
    public function ajax_get_comparison_data() {
        check_ajax_referer('parfume_comparison_nonce', 'nonce');
        
        $comparison_items = $this->get_comparison_items();
        
        $response_data = array(
            'count' => count($comparison_items),
            'items' => $this->get_comparison_items_data($comparison_items),
            'comparison_url' => $this->get_comparison_url($comparison_items),
            'html' => $this->render_comparison_widget_content($comparison_items)
        );
        
        wp_send_json_success($response_data);
    }
    
    /**
     * AJAX handler за изчистване на сравнението
     */
    public function ajax_clear_comparison() {
        check_ajax_referer('parfume_comparison_nonce', 'nonce');
        
        $_SESSION[self::SESSION_KEY] = array();
        
        $response_data = array(
            'count' => 0,
            'items' => array()
        );
        
        wp_send_json_success($response_data);
    }
    
    // ===== PUBLIC API METHODS =====
    
    /**
     * Получава парфюмите в сравнението
     */
    public function get_comparison_items() {
        if (!isset($_SESSION[self::SESSION_KEY])) {
            return array();
        }
        
        $items = $_SESSION[self::SESSION_KEY];
        
        // Филтрираме невалидни ID-та
        $valid_items = array();
        foreach ($items as $item_id) {
            if (get_post($item_id) && get_post_type($item_id) === 'parfume') {
                $valid_items[] = $item_id;
            }
        }
        
        // Актуализираме сесията ако има промени
        if (count($valid_items) !== count($items)) {
            $_SESSION[self::SESSION_KEY] = $valid_items;
        }
        
        return $valid_items;
    }
    
    /**
     * Получава данни за парфюмите в сравнението
     */
    public function get_comparison_items_data($item_ids = null) {
        if ($item_ids === null) {
            $item_ids = $this->get_comparison_items();
        }
        
        $items_data = array();
        
        foreach ($item_ids as $post_id) {
            $post = get_post($post_id);
            if (!$post) continue;
            
            $items_data[] = array(
                'id' => $post_id,
                'title' => $post->post_title,
                'url' => get_permalink($post_id),
                'thumbnail' => get_the_post_thumbnail_url($post_id, 'medium'),
                'excerpt' => get_the_excerpt($post_id),
                'brand' => $this->get_post_brand($post_id),
                'rating' => get_post_meta($post_id, '_rating', true),
                'price' => get_post_meta($post_id, '_price', true)
            );
        }
        
        return $items_data;
    }
    
    /**
     * Проверява дали парфюм е в сравнението
     */
    public function is_in_comparison($post_id) {
        $comparison_items = $this->get_comparison_items();
        return in_array($post_id, $comparison_items);
    }
    
    /**
     * Получава броя на парфюмите в сравнението
     */
    public function get_comparison_count() {
        return count($this->get_comparison_items());
    }
    
    /**
     * Получава URL за comparison страницата
     */
    public function get_comparison_url($item_ids = null) {
        if ($item_ids === null) {
            $item_ids = $this->get_comparison_items();
        }
        
        $settings = get_option('parfume_reviews_settings', array());
        $parfume_slug = !empty($settings['parfume_slug']) ? $settings['parfume_slug'] : 'parfiumi';
        
        if (empty($item_ids)) {
            return home_url('/' . $parfume_slug . '/sravnyavane/');
        }
        
        $ids_string = implode(',', $item_ids);
        return home_url('/' . $parfume_slug . '/sravnyavane/' . $ids_string . '/');
    }
    
    // ===== RENDERING METHODS =====
    
    /**
     * Рендерира comparison table
     */
    public function render_comparison_table($item_ids = null) {
        if ($item_ids === null) {
            $item_ids = $this->get_comparison_items();
        }
        
        if (empty($item_ids)) {
            return '<div class="empty-comparison">' . __('Няма парфюми за сравняване', 'parfume-reviews') . '</div>';
        }
        
        $items_data = $this->get_comparison_items_data($item_ids);
        
        ob_start();
        ?>
        <div class="parfume-comparison-table-container">
            <div class="comparison-controls">
                <button class="clear-comparison-btn button secondary" data-action="clear">
                    <?php _e('Изчисти всички', 'parfume-reviews'); ?>
                </button>
            </div>
            
            <div class="comparison-table-scroll">
                <table class="parfume-comparison-table">
                    <thead>
                        <tr>
                            <th class="comparison-feature-header"><?php _e('Характеристика', 'parfume-reviews'); ?></th>
                            <?php foreach ($items_data as $item): ?>
                                <th class="comparison-item-header">
                                    <div class="comparison-item-info">
                                        <?php if ($item['thumbnail']): ?>
                                            <img src="<?php echo esc_url($item['thumbnail']); ?>" 
                                                 alt="<?php echo esc_attr($item['title']); ?>" 
                                                 class="parfume-image">
                                        <?php else: ?>
                                            <div class="parfume-image placeholder-image">
                                                <span>📸</span>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <h4 class="parfume-title">
                                            <a href="<?php echo esc_url($item['url']); ?>" target="_blank">
                                                <?php echo esc_html($item['title']); ?>
                                            </a>
                                        </h4>
                                        
                                        <?php if ($item['brand']): ?>
                                            <div class="parfume-brand">
                                                <?php echo esc_html($item['brand']); ?>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <button class="remove-from-comparison" data-post-id="<?php echo esc_attr($item['id']); ?>">
                                            <span class="dashicons dashicons-no-alt"></span>
                                            <?php _e('Премахни', 'parfume-reviews'); ?>
                                        </button>
                                    </div>
                                </th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Рейтинг -->
                        <tr>
                            <td class="feature-name"><strong><?php _e('Рейтинг', 'parfume-reviews'); ?></strong></td>
                            <?php foreach ($items_data as $item): ?>
                                <td class="feature-value">
                                    <?php if ($item['rating']): ?>
                                        <div class="rating-display">
                                            <span class="rating-value"><?php echo esc_html($item['rating']); ?>/10</span>
                                            <div class="rating-stars">
                                                <?php for ($i = 1; $i <= 10; $i++): ?>
                                                    <span class="star <?php echo ($i <= $item['rating']) ? 'filled' : ''; ?>">★</span>
                                                <?php endfor; ?>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <span class="no-data"><?php _e('Няма данни', 'parfume-reviews'); ?></span>
                                    <?php endif; ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                        
                        <!-- Цена -->
                        <tr>
                            <td class="feature-name"><strong><?php _e('Цена', 'parfume-reviews'); ?></strong></td>
                            <?php foreach ($items_data as $item): ?>
                                <td class="feature-value">
                                    <?php if ($item['price']): ?>
                                        <span class="price-value"><?php echo esc_html($item['price']); ?> лв.</span>
                                    <?php else: ?>
                                        <span class="no-data"><?php _e('Няма данни', 'parfume-reviews'); ?></span>
                                    <?php endif; ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                        
                        <!-- Марка -->
                        <tr>
                            <td class="feature-name"><strong><?php _e('Марка', 'parfume-reviews'); ?></strong></td>
                            <?php foreach ($items_data as $item): ?>
                                <td class="feature-value">
                                    <?php if ($item['brand']): ?>
                                        <span class="brand-value"><?php echo esc_html($item['brand']); ?></span>
                                    <?php else: ?>
                                        <span class="no-data"><?php _e('Няма данни', 'parfume-reviews'); ?></span>
                                    <?php endif; ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                        
                        <!-- Допълнителни характеристики -->
                        <?php
                        $additional_fields = array(
                            '_release_year' => __('Година на излизане', 'parfume-reviews'),
                            '_longevity' => __('Издръжливост', 'parfume-reviews'),
                            '_sillage' => __('Силаж', 'parfume-reviews'),
                            '_bottle_size' => __('Размер на бутилката', 'parfume-reviews')
                        );
                        
                        foreach ($additional_fields as $meta_key => $field_label):
                        ?>
                            <tr>
                                <td class="feature-name"><strong><?php echo esc_html($field_label); ?></strong></td>
                                <?php foreach ($item_ids as $post_id): ?>
                                    <td class="feature-value">
                                        <?php 
                                        $meta_value = get_post_meta($post_id, $meta_key, true);
                                        if ($meta_value): 
                                        ?>
                                            <span class="meta-value"><?php echo esc_html($meta_value); ?></span>
                                        <?php else: ?>
                                            <span class="no-data"><?php _e('Няма данни', 'parfume-reviews'); ?></span>
                                        <?php endif; ?>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                        
                        <!-- Таксономии -->
                        <?php
                        $taxonomies = array(
                            'gender' => __('Пол', 'parfume-reviews'),
                            'aroma_type' => __('Тип аромат', 'parfume-reviews'),
                            'season' => __('Сезон', 'parfume-reviews'),
                            'intensity' => __('Интензивност', 'parfume-reviews'),
                            'notes' => __('Нотки', 'parfume-reviews'),
                            'perfumer' => __('Парфюмерист', 'parfume-reviews')
                        );
                        
                        foreach ($taxonomies as $taxonomy => $taxonomy_label):
                        ?>
                            <tr>
                                <td class="feature-name"><strong><?php echo esc_html($taxonomy_label); ?></strong></td>
                                <?php foreach ($item_ids as $post_id): ?>
                                    <td class="feature-value">
                                        <?php 
                                        $terms = wp_get_post_terms($post_id, $taxonomy);
                                        if (!empty($terms) && !is_wp_error($terms)): 
                                        ?>
                                            <div class="taxonomy-terms">
                                                <?php foreach ($terms as $term): ?>
                                                    <span class="term-badge"><?php echo esc_html($term->name); ?></span>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php else: ?>
                                            <span class="no-data"><?php _e('Няма данни', 'parfume-reviews'); ?></span>
                                        <?php endif; ?>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                        
                        <!-- Описание -->
                        <tr>
                            <td class="feature-name"><strong><?php _e('Описание', 'parfume-reviews'); ?></strong></td>
                            <?php foreach ($items_data as $item): ?>
                                <td class="feature-value">
                                    <?php if ($item['excerpt']): ?>
                                        <div class="parfume-excerpt"><?php echo wp_kses_post($item['excerpt']); ?></div>
                                    <?php else: ?>
                                        <span class="no-data"><?php _e('Няма описание', 'parfume-reviews'); ?></span>
                                    <?php endif; ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <?php
        
        return ob_get_clean();
    }
    
    /**
     * Рендерира comparison widget в footer
     */
    public function add_comparison_widget() {
        // Показваме само на парфюмни страници
        if (!function_exists('parfume_reviews_is_parfume_page') || !parfume_reviews_is_parfume_page()) {
            return;
        }
        
        $comparison_items = $this->get_comparison_items();
        ?>
        <div id="parfume-comparison-widget" class="comparison-widget" style="display: none;">
            <div class="widget-header">
                <h4><?php _e('Сравнение на парфюми', 'parfume-reviews'); ?></h4>
                <span class="comparison-count">(<?php echo count($comparison_items); ?>)</span>
                <button class="widget-toggle" aria-label="<?php _e('Скрий/покажи', 'parfume-reviews'); ?>">
                    <span class="dashicons dashicons-arrow-up-alt2"></span>
                </button>
            </div>
            <div class="widget-content">
                <?php echo $this->render_comparison_widget_content($comparison_items); ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Рендерира съдържанието на comparison widget
     */
    public function render_comparison_widget_content($comparison_items) {
        if (empty($comparison_items)) {
            return '<div class="empty-widget">' . __('Няма парфюми за сравняване', 'parfume-reviews') . '</div>';
        }
        
        ob_start();
        ?>
        <div class="comparison-items">
            <?php foreach ($comparison_items as $post_id): ?>
                <div class="comparison-item" data-post-id="<?php echo esc_attr($post_id); ?>">
                    <div class="item-thumbnail">
                        <?php if (has_post_thumbnail($post_id)): ?>
                            <?php echo get_the_post_thumbnail($post_id, 'thumbnail'); ?>
                        <?php else: ?>
                            <div class="placeholder-thumbnail">📸</div>
                        <?php endif; ?>
                    </div>
                    <div class="item-title">
                        <?php echo esc_html(get_the_title($post_id)); ?>
                    </div>
                    <button class="remove-item" data-post-id="<?php echo esc_attr($post_id); ?>" 
                            aria-label="<?php _e('Премахни от сравнението', 'parfume-reviews'); ?>">
                        <span class="dashicons dashicons-no-alt"></span>
                    </button>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div class="widget-actions">
            <a href="<?php echo esc_url($this->get_comparison_url($comparison_items)); ?>" 
               class="compare-button button primary">
                <?php _e('Сравни сега', 'parfume-reviews'); ?>
            </a>
            <button class="clear-all-button button secondary" data-action="clear">
                <?php _e('Изчисти всички', 'parfume-reviews'); ?>
            </button>
        </div>
        <?php
        
        return ob_get_clean();
    }
    
    // ===== SHORTCODES =====
    
    /**
     * Shortcode за цялото сравнение
     */
    public function comparison_shortcode($atts) {
        $atts = shortcode_atts(array(
            'ids' => '',
            'show_controls' => 'true'
        ), $atts);
        
        if (!empty($atts['ids'])) {
            $item_ids = explode(',', $atts['ids']);
            $item_ids = array_map('intval', $item_ids);
            $item_ids = array_filter($item_ids);
        } else {
            $item_ids = $this->get_comparison_items();
        }
        
        return $this->render_comparison_table($item_ids);
    }
    
    /**
     * Shortcode за comparison table
     */
    public function comparison_table_shortcode($atts) {
        return $this->comparison_shortcode($atts);
    }
    
    /**
     * Shortcode за comparison button
     */
    public function comparison_button_shortcode($atts) {
        global $post;
        
        $atts = shortcode_atts(array(
            'post_id' => $post ? $post->ID : 0,
            'text_add' => __('Добави за сравняване', 'parfume-reviews'),
            'text_remove' => __('Премахни от сравнението', 'parfume-reviews'),
            'class' => 'comparison-button'
        ), $atts);
        
        $post_id = intval($atts['post_id']);
        
        if (!$post_id || get_post_type($post_id) !== 'parfume') {
            return '';
        }
        
        $is_in_comparison = $this->is_in_comparison($post_id);
        $button_text = $is_in_comparison ? $atts['text_remove'] : $atts['text_add'];
        $button_class = $atts['class'] . ($is_in_comparison ? ' in-comparison' : '');
        
        ob_start();
        ?>
        <button class="<?php echo esc_attr($button_class); ?>" 
                data-post-id="<?php echo esc_attr($post_id); ?>"
                data-text-add="<?php echo esc_attr($atts['text_add']); ?>"
                data-text-remove="<?php echo esc_attr($atts['text_remove']); ?>">
            <span class="button-icon">
                <?php if ($is_in_comparison): ?>
                    <span class="dashicons dashicons-yes-alt"></span>
                <?php else: ?>
                    <span class="dashicons dashicons-plus-alt"></span>
                <?php endif; ?>
            </span>
            <span class="button-text"><?php echo esc_html($button_text); ?></span>
        </button>
        <?php
        
        return ob_get_clean();
    }
    
    // ===== UTILITY METHODS =====
    
    /**
     * Получава марката на парфюм
     */
    private function get_post_brand($post_id) {
        $brands = wp_get_post_terms($post_id, 'marki');
        
        if (!empty($brands) && !is_wp_error($brands)) {
            return $brands[0]->name;
        }
        
        return '';
    }
    
    /**
     * Получава максималния брой парфюми за сравняване
     */
    public function get_max_comparison_items() {
        return self::MAX_COMPARISON_ITEMS;
    }
    
    /**
     * Проверява дали comparison функционалността е активна
     */
    public function is_comparison_enabled() {
        $settings = get_option('parfume_reviews_settings', array());
        return !isset($settings['disable_comparison']) || !$settings['disable_comparison'];
    }
    
    /**
     * Получава настройките за comparison
     */
    public function get_comparison_settings() {
        $settings = get_option('parfume_reviews_settings', array());
        
        return array(
            'enabled' => $this->is_comparison_enabled(),
            'max_items' => self::MAX_COMPARISON_ITEMS,
            'show_widget' => isset($settings['show_comparison_widget']) ? $settings['show_comparison_widget'] : true,
            'auto_open_widget' => isset($settings['auto_open_comparison_widget']) ? $settings['auto_open_comparison_widget'] : false
        );
    }
}