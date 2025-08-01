<?php
namespace Parfume_Reviews;

/**
 * Shortcodes class - управлява всички shortcodes за парфюми
 * РЕВИЗИРАНА ВЕРСИЯ: Пълна функционалност с всички shortcodes
 * 
 * Файл: includes/class-shortcodes.php
 */
class Shortcodes {
    
    /**
     * Registered shortcodes
     */
    private $shortcodes = array();
    
    /**
     * Cache за shortcode резултати
     */
    private $cache = array();
    
    public function __construct() {
        // Регистрираме всички shortcodes при инициализация
        add_action('init', array($this, 'register_shortcodes'));
        
        // Enqueue scripts за shortcodes
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        
        if (function_exists('parfume_reviews_debug_log')) {
            parfume_reviews_debug_log("Shortcodes class initialized");
        }
    }
    
    /**
     * Регистрира всички shortcodes
     */
    public function register_shortcodes() {
        $shortcodes = array(
            // Основни shortcodes
            'parfume_rating' => 'parfume_rating_shortcode',
            'parfume_details' => 'parfume_details_shortcode',
            'parfume_stores' => 'parfume_stores_shortcode',
            'parfume_comparison_button' => 'parfume_comparison_button_shortcode',
            
            // Grid и списъци
            'parfume_grid' => 'parfume_grid_shortcode',
            'latest_parfumes' => 'latest_parfumes_shortcode',
            'featured_parfumes' => 'featured_parfumes_shortcode',
            'top_rated_parfumes' => 'top_rated_parfumes_shortcode',
            'similar_parfumes' => 'similar_parfumes_shortcode',
            
            // Филтри и търсене
            'parfume_filters' => 'parfume_filters_shortcode',
            'parfume_search' => 'parfume_search_shortcode',
            'parfume_sort' => 'parfume_sort_shortcode',
            
            // Марки и таксономии
            'parfume_brand_products' => 'parfume_brand_products_shortcode',
            'all_brands_archive' => 'all_brands_archive_shortcode',
            'all_notes_archive' => 'all_notes_archive_shortcode',
            'all_perfumers_archive' => 'all_perfumers_archive_shortcode',
            
            // Потребителски функции
            'parfume_recently_viewed' => 'parfume_recently_viewed_shortcode',
            'parfume_favorites' => 'parfume_favorites_shortcode',
            'parfume_wishlist' => 'parfume_wishlist_shortcode',
            
            // Сравняване
            'parfume_comparison' => 'parfume_comparison_shortcode',
            'comparison_table' => 'comparison_table_shortcode',
            'comparison_button' => 'comparison_button_shortcode',
            
            // Статистики и информация
            'parfume_stats' => 'parfume_stats_shortcode',
            'brand_stats' => 'brand_stats_shortcode',
            'perfumer_info' => 'perfumer_info_shortcode',
            
            // Backward compatibility
            'show_parfume_grid' => 'parfume_grid_shortcode',
            'show_latest_parfumes' => 'latest_parfumes_shortcode'
        );
        
        foreach ($shortcodes as $tag => $function) {
            if (!shortcode_exists($tag)) {
                add_shortcode($tag, array($this, $function));
                $this->shortcodes[$tag] = $function;
                
                if (function_exists('parfume_reviews_debug_log')) {
                    parfume_reviews_debug_log("Registered shortcode: [$tag]");
                }
            }
        }
    }
    
    /**
     * Enqueue scripts за shortcodes
     */
    public function enqueue_scripts() {
        // Проверяваме дали има shortcodes на страницата
        if (!$this->has_shortcodes_on_page()) {
            return;
        }
        
        // CSS за shortcodes
        wp_enqueue_style(
            'parfume-shortcodes',
            PARFUME_REVIEWS_PLUGIN_URL . 'assets/css/shortcodes.css',
            array(),
            PARFUME_REVIEWS_VERSION
        );
        
        // JS за shortcodes
        wp_enqueue_script(
            'parfume-shortcodes',
            PARFUME_REVIEWS_PLUGIN_URL . 'assets/js/shortcodes.js',
            array('jquery'),
            PARFUME_REVIEWS_VERSION,
            true
        );
        
        // Локализация
        wp_localize_script('parfume-shortcodes', 'parfumeShortcodes', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('parfume_shortcodes_nonce'),
            'strings' => array(
                'loading' => __('Зареждане...', 'parfume-reviews'),
                'error' => __('Възникна грешка', 'parfume-reviews'),
                'no_results' => __('Няма намерени резултати', 'parfume-reviews'),
                'load_more' => __('Зареди още', 'parfume-reviews')
            )
        ));
    }
    
    // ===== ОСНОВНИ SHORTCODES =====
    
    /**
     * [parfume_rating] - Показва рейтинг на парфюм
     */
    public function parfume_rating_shortcode($atts, $content = null) {
        global $post;
        
        $atts = shortcode_atts(array(
            'post_id' => $post ? $post->ID : 0,
            'show_text' => 'true',
            'show_number' => 'true',
            'size' => 'medium'
        ), $atts);
        
        $post_id = intval($atts['post_id']);
        
        if (!$post_id || get_post_type($post_id) !== 'parfume') {
            return '';
        }
        
        $rating = get_post_meta($post_id, '_rating', true);
        
        if (empty($rating)) {
            return '';
        }
        
        $rating = floatval($rating);
        $size_class = 'rating-' . sanitize_html_class($atts['size']);
        
        ob_start();
        ?>
        <div class="parfume-rating-display <?php echo esc_attr($size_class); ?>">
            <?php if ($atts['show_number'] === 'true'): ?>
                <span class="rating-number"><?php echo esc_html($rating); ?>/10</span>
            <?php endif; ?>
            
            <div class="rating-stars" data-rating="<?php echo esc_attr($rating); ?>">
                <?php for ($i = 1; $i <= 10; $i++): ?>
                    <span class="star <?php echo ($i <= $rating) ? 'filled' : ''; ?>">★</span>
                <?php endfor; ?>
            </div>
            
            <?php if ($atts['show_text'] === 'true'): ?>
                <span class="rating-text"><?php echo $this->get_rating_text($rating); ?></span>
            <?php endif; ?>
        </div>
        <?php
        
        return ob_get_clean();
    }
    
    /**
     * [parfume_details] - Показва детайли за парфюм
     */
    public function parfume_details_shortcode($atts, $content = null) {
        global $post;
        
        $atts = shortcode_atts(array(
            'post_id' => $post ? $post->ID : 0,
            'fields' => 'all', // all, або comma-separated list
            'layout' => 'table' // table, list, grid
        ), $atts);
        
        $post_id = intval($atts['post_id']);
        
        if (!$post_id || get_post_type($post_id) !== 'parfume') {
            return '';
        }
        
        $details = $this->get_parfume_details($post_id);
        $fields_to_show = $this->parse_fields_parameter($atts['fields'], $details);
        
        return $this->render_parfume_details($details, $fields_to_show, $atts['layout']);
    }
    
    /**
     * [parfume_stores] - Показва магазини за парфюм
     */
    public function parfume_stores_shortcode($atts, $content = null) {
        global $post;
        
        $atts = shortcode_atts(array(
            'post_id' => $post ? $post->ID : 0,
            'layout' => 'grid', // grid, list, compact
            'show_prices' => 'true',
            'show_promo' => 'true',
            'limit' => '0'
        ), $atts);
        
        $post_id = intval($atts['post_id']);
        
        if (!$post_id || get_post_type($post_id) !== 'parfume') {
            return '';
        }
        
        $stores = get_post_meta($post_id, '_parfume_stores', true);
        
        if (empty($stores) || !is_array($stores)) {
            return '<div class="no-stores">' . __('Няма налични магазини', 'parfume-reviews') . '</div>';
        }
        
        if ($atts['limit'] > 0) {
            $stores = array_slice($stores, 0, intval($atts['limit']));
        }
        
        return $this->render_stores_list($stores, $atts);
    }
    
    /**
     * [parfume_comparison_button] - Бутон за сравняване
     */
    public function parfume_comparison_button_shortcode($atts, $content = null) {
        global $post;
        
        $atts = shortcode_atts(array(
            'post_id' => $post ? $post->ID : 0,
            'text_add' => __('Добави за сравняване', 'parfume-reviews'),
            'text_remove' => __('Премахни от сравнението', 'parfume-reviews'),
            'style' => 'button' // button, link, icon
        ), $atts);
        
        $post_id = intval($atts['post_id']);
        
        if (!$post_id || get_post_type($post_id) !== 'parfume') {
            return '';
        }
        
        // Делегираме към Comparison класа ако е наличен
        if (class_exists('Parfume_Reviews\\Comparison')) {
            $comparison = new Comparison();
            if (method_exists($comparison, 'comparison_button_shortcode')) {
                return $comparison->comparison_button_shortcode($atts);
            }
        }
        
        // Fallback implementation
        return $this->render_comparison_button_fallback($post_id, $atts);
    }
    
    // ===== GRID И СПИСЪЦИ =====
    
    /**
     * [parfume_grid] - Решетка с парфюми
     */
    public function parfume_grid_shortcode($atts, $content = null) {
        $atts = shortcode_atts(array(
            'count' => '12',
            'columns' => '4',
            'orderby' => 'date',
            'order' => 'DESC',
            'include' => '',
            'exclude' => '',
            'brand' => '',
            'gender' => '',
            'notes' => '',
            'show_rating' => 'true',
            'show_price' => 'true',
            'show_excerpt' => 'false',
            'pagination' => 'false'
        ), $atts);
        
        $query_args = $this->build_parfume_query($atts);
        $parfumes = new \WP_Query($query_args);
        
        if (!$parfumes->have_posts()) {
            return '<div class="no-parfumes">' . __('Няма намерени парфюми', 'parfume-reviews') . '</div>';
        }
        
        return $this->render_parfume_grid($parfumes, $atts);
    }
    
    /**
     * [latest_parfumes] - Най-нови парфюми
     */
    public function latest_parfumes_shortcode($atts, $content = null) {
        $atts = shortcode_atts(array(
            'count' => '6',
            'columns' => '3',
            'show_date' => 'true'
        ), $atts);
        
        $atts['orderby'] = 'date';
        $atts['order'] = 'DESC';
        
        return $this->parfume_grid_shortcode($atts, $content);
    }
    
    /**
     * [featured_parfumes] - Препоръчани парфюми
     */
    public function featured_parfumes_shortcode($atts, $content = null) {
        $atts = shortcode_atts(array(
            'count' => '6',
            'columns' => '3'
        ), $atts);
        
        $atts['meta_key'] = '_featured';
        $atts['meta_value'] = '1';
        
        return $this->parfume_grid_shortcode($atts, $content);
    }
    
    /**
     * [top_rated_parfumes] - Най-високо оценени
     */
    public function top_rated_parfumes_shortcode($atts, $content = null) {
        $atts = shortcode_atts(array(
            'count' => '6',
            'columns' => '3',
            'min_rating' => '7'
        ), $atts);
        
        $atts['orderby'] = 'meta_value_num';
        $atts['meta_key'] = '_rating';
        $atts['order'] = 'DESC';
        
        return $this->parfume_grid_shortcode($atts, $content);
    }
    
    /**
     * [similar_parfumes] - Подобни парфюми
     */
    public function similar_parfumes_shortcode($atts, $content = null) {
        global $post;
        
        $atts = shortcode_atts(array(
            'post_id' => $post ? $post->ID : 0,
            'count' => '4',
            'columns' => '4',
            'method' => 'auto' // auto, brand, notes, gender
        ), $atts);
        
        $post_id = intval($atts['post_id']);
        
        if (!$post_id || get_post_type($post_id) !== 'parfume') {
            return '';
        }
        
        $similar_ids = $this->find_similar_parfumes($post_id, $atts);
        
        if (empty($similar_ids)) {
            return '<div class="no-similar">' . __('Няма намерени подобни парфюми', 'parfume-reviews') . '</div>';
        }
        
        $atts['include'] = implode(',', $similar_ids);
        
        return $this->parfume_grid_shortcode($atts, $content);
    }
    
    // ===== ФИЛТРИ И ТЪРСЕНЕ =====
    
    /**
     * [parfume_filters] - Филтри за парфюми
     */
    public function parfume_filters_shortcode($atts, $content = null) {
        $atts = shortcode_atts(array(
            'filters' => 'brand,gender,notes', // comma-separated
            'style' => 'sidebar', // sidebar, horizontal, dropdown
            'ajax' => 'true',
            'show_count' => 'true'
        ), $atts);
        
        $filters_to_show = explode(',', $atts['filters']);
        $filters_to_show = array_map('trim', $filters_to_show);
        
        return $this->render_parfume_filters($filters_to_show, $atts);
    }
    
    /**
     * [parfume_search] - Търсене на парфюми
     */
    public function parfume_search_shortcode($atts, $content = null) {
        $atts = shortcode_atts(array(
            'placeholder' => __('Търси парфюми...', 'parfume-reviews'),
            'button_text' => __('Търси', 'parfume-reviews'),
            'ajax' => 'true',
            'show_suggestions' => 'true'
        ), $atts);
        
        return $this->render_parfume_search($atts);
    }
    
    /**
     * [parfume_sort] - Сортиране на парфюми
     */
    public function parfume_sort_shortcode($atts, $content = null) {
        $atts = shortcode_atts(array(
            'options' => 'date,rating,price,name', // comma-separated
            'default' => 'date',
            'ajax' => 'true'
        ), $atts);
        
        $sort_options = explode(',', $atts['options']);
        $sort_options = array_map('trim', $sort_options);
        
        return $this->render_parfume_sort($sort_options, $atts);
    }
    
    // ===== МАРКИ И ТАКСОНОМИИ =====
    
    /**
     * [parfume_brand_products] - Продукти от марка
     */
    public function parfume_brand_products_shortcode($atts, $content = null) {
        $atts = shortcode_atts(array(
            'brand' => '',
            'count' => '12',
            'columns' => '4',
            'show_brand_info' => 'true'
        ), $atts);
        
        if (empty($atts['brand'])) {
            return '<div class="error">' . __('Параметърът "brand" е задължителен', 'parfume-reviews') . '</div>';
        }
        
        // Добавяме brand filter към grid атрибутите
        $grid_atts = $atts;
        unset($grid_atts['show_brand_info']);
        
        $output = '';
        
        // Показваме информация за марката ако е поискано
        if ($atts['show_brand_info'] === 'true') {
            $brand_term = get_term_by('slug', $atts['brand'], 'marki');
            if ($brand_term) {
                $output .= $this->render_brand_info($brand_term);
            }
        }
        
        $output .= $this->parfume_grid_shortcode($grid_atts, $content);
        
        return $output;
    }
    
    /**
     * [all_brands_archive] - Всички марки
     */
    public function all_brands_archive_shortcode($atts, $content = null) {
        $atts = shortcode_atts(array(
            'columns' => '4',
            'show_count' => 'true',
            'hide_empty' => 'true',
            'orderby' => 'name',
            'limit' => '0'
        ), $atts);
        
        return $this->render_taxonomy_archive('marki', $atts);
    }
    
    /**
     * [all_notes_archive] - Всички ноти
     */
    public function all_notes_archive_shortcode($atts, $content = null) {
        $atts = shortcode_atts(array(
            'columns' => '6',
            'show_count' => 'true',
            'hide_empty' => 'true',
            'orderby' => 'name',
            'group_by' => '' // group notes by type
        ), $atts);
        
        return $this->render_taxonomy_archive('notes', $atts);
    }
    
    /**
     * [all_perfumers_archive] - Всички парфюмеристи
     */
    public function all_perfumers_archive_shortcode($atts, $content = null) {
        $atts = shortcode_atts(array(
            'columns' => '3',
            'show_count' => 'true',
            'hide_empty' => 'true',
            'orderby' => 'name',
            'show_photo' => 'true'
        ), $atts);
        
        return $this->render_taxonomy_archive('perfumer', $atts);
    }
    
    // ===== ПОТРЕБИТЕЛСКИ ФУНКЦИИ =====
    
    /**
     * [parfume_recently_viewed] - Последно разгледани
     */
    public function parfume_recently_viewed_shortcode($atts, $content = null) {
        $atts = shortcode_atts(array(
            'count' => '5',
            'columns' => '5',
            'title' => __('Последно разгледани', 'parfume-reviews'),
            'show_empty' => 'false'
        ), $atts);
        
        $recently_viewed = $this->get_recently_viewed_parfumes($atts['count']);
        
        if (empty($recently_viewed)) {
            if ($atts['show_empty'] === 'true') {
                return '<div class="no-recently-viewed">' . __('Няма последно разгледани парфюми', 'parfume-reviews') . '</div>';
            }
            return '';
        }
        
        $atts['include'] = implode(',', $recently_viewed);
        
        $output = '';
        if (!empty($atts['title'])) {
            $output .= '<h3 class="recently-viewed-title">' . esc_html($atts['title']) . '</h3>';
        }
        
        $output .= $this->parfume_grid_shortcode($atts, $content);
        
        return $output;
    }
    
    /**
     * [parfume_favorites] - Любими парфюми
     */
    public function parfume_favorites_shortcode($atts, $content = null) {
        $atts = shortcode_atts(array(
            'user_id' => get_current_user_id(),
            'count' => '12',
            'columns' => '4'
        ), $atts);
        
        $user_id = intval($atts['user_id']);
        
        if (!$user_id) {
            return '<div class="login-required">' . __('Трябва да сте влезли в профила си', 'parfume-reviews') . '</div>';
        }
        
        $favorites = get_user_meta($user_id, 'parfume_favorites', true);
        
        if (empty($favorites) || !is_array($favorites)) {
            return '<div class="no-favorites">' . __('Няма любими парфюми', 'parfume-reviews') . '</div>';
        }
        
        $atts['include'] = implode(',', $favorites);
        
        return $this->parfume_grid_shortcode($atts, $content);
    }
    
    /**
     * [parfume_wishlist] - Списък с желания
     */
    public function parfume_wishlist_shortcode($atts, $content = null) {
        $atts = shortcode_atts(array(
            'user_id' => get_current_user_id(),
            'count' => '12',
            'columns' => '4',
            'show_remove_button' => 'true'
        ), $atts);
        
        $user_id = intval($atts['user_id']);
        
        if (!$user_id) {
            return '<div class="login-required">' . __('Трябва да сте влезли в профила си', 'parfume-reviews') . '</div>';
        }
        
        $wishlist = get_user_meta($user_id, 'parfume_wishlist', true);
        
        if (empty($wishlist) || !is_array($wishlist)) {
            return '<div class="no-wishlist">' . __('Списъкът с желания е празен', 'parfume-reviews') . '</div>';
        }
        
        $atts['include'] = implode(',', $wishlist);
        
        return $this->parfume_grid_shortcode($atts, $content);
    }
    
    // ===== СРАВНЯВАНЕ SHORTCODES =====
    
    /**
     * [parfume_comparison] - Пълно сравнение
     */
    public function parfume_comparison_shortcode($atts, $content = null) {
        // Делегираме към Comparison класа
        if (class_exists('Parfume_Reviews\\Comparison')) {
            $comparison = new Comparison();
            if (method_exists($comparison, 'comparison_shortcode')) {
                return $comparison->comparison_shortcode($atts);
            }
        }
        
        return '<div class="comparison-unavailable">' . __('Функцията за сравняване не е налична', 'parfume-reviews') . '</div>';
    }
    
    /**
     * [comparison_table] - Таблица за сравнение
     */
    public function comparison_table_shortcode($atts, $content = null) {
        return $this->parfume_comparison_shortcode($atts, $content);
    }
    
    /**
     * [comparison_button] - Бутон за сравняване
     */
    public function comparison_button_shortcode($atts, $content = null) {
        return $this->parfume_comparison_button_shortcode($atts, $content);
    }
    
    // ===== СТАТИСТИКИ И ИНФОРМАЦИЯ =====
    
    /**
     * [parfume_stats] - Общи статистики
     */
    public function parfume_stats_shortcode($atts, $content = null) {
        $atts = shortcode_atts(array(
            'stats' => 'total,brands,notes,perfumers', // comma-separated
            'layout' => 'grid', // grid, list, inline
            'show_icons' => 'true'
        ), $atts);
        
        $stats_to_show = explode(',', $atts['stats']);
        $stats_to_show = array_map('trim', $stats_to_show);
        
        return $this->render_parfume_stats($stats_to_show, $atts);
    }
    
    /**
     * [brand_stats] - Статистики за марка
     */
    public function brand_stats_shortcode($atts, $content = null) {
        $atts = shortcode_atts(array(
            'brand' => '',
            'stats' => 'products,avg_rating,newest,oldest'
        ), $atts);
        
        if (empty($atts['brand'])) {
            return '<div class="error">' . __('Параметърът "brand" е задължителен', 'parfume-reviews') . '</div>';
        }
        
        $brand_term = get_term_by('slug', $atts['brand'], 'marki');
        
        if (!$brand_term) {
            return '<div class="error">' . __('Марката не е намерена', 'parfume-reviews') . '</div>';
        }
        
        return $this->render_brand_stats($brand_term, $atts);
    }
    
    /**
     * [perfumer_info] - Информация за парфюмерист
     */
    public function perfumer_info_shortcode($atts, $content = null) {
        $atts = shortcode_atts(array(
            'perfumer' => '',
            'show_bio' => 'true',
            'show_products' => 'true',
            'products_count' => '6'
        ), $atts);
        
        if (empty($atts['perfumer'])) {
            return '<div class="error">' . __('Параметърът "perfumer" е задължителен', 'parfume-reviews') . '</div>';
        }
        
        $perfumer_term = get_term_by('slug', $atts['perfumer'], 'perfumer');
        
        if (!$perfumer_term) {
            return '<div class="error">' . __('Парфюмеристът не е намерен', 'parfume-reviews') . '</div>';
        }
        
        return $this->render_perfumer_info($perfumer_term, $atts);
    }
    
    // ===== HELPER METHODS =====
    
    /**
     * Проверява дали има shortcodes на текущата страница
     */
    private function has_shortcodes_on_page() {
        global $post;
        
        if (!$post) {
            return false;
        }
        
        $content = $post->post_content;
        
        foreach (array_keys($this->shortcodes) as $shortcode) {
            if (has_shortcode($content, $shortcode)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Изгражда WP_Query за парфюми
     */
    private function build_parfume_query($atts) {
        $query_args = array(
            'post_type' => 'parfume',
            'post_status' => 'publish',
            'posts_per_page' => intval($atts['count']),
            'orderby' => $atts['orderby'],
            'order' => $atts['order']
        );
        
        // Include/Exclude
        if (!empty($atts['include'])) {
            $include_ids = array_map('intval', explode(',', $atts['include']));
            $query_args['post__in'] = $include_ids;
        }
        
        if (!empty($atts['exclude'])) {
            $exclude_ids = array_map('intval', explode(',', $atts['exclude']));
            $query_args['post__not_in'] = $exclude_ids;
        }
        
        // Taxonomy filters
        $tax_query = array();
        
        if (!empty($atts['brand'])) {
            $tax_query[] = array(
                'taxonomy' => 'marki',
                'field' => 'slug',
                'terms' => $atts['brand']
            );
        }
        
        if (!empty($atts['gender'])) {
            $tax_query[] = array(
                'taxonomy' => 'gender',
                'field' => 'slug',
                'terms' => $atts['gender']
            );
        }
        
        if (!empty($atts['notes'])) {
            $notes = explode(',', $atts['notes']);
            $tax_query[] = array(
                'taxonomy' => 'notes',
                'field' => 'slug',
                'terms' => $notes,
                'operator' => 'IN'
            );
        }
        
        if (!empty($tax_query)) {
            $query_args['tax_query'] = $tax_query;
        }
        
        // Meta query
        $meta_query = array();
        
        if (isset($atts['meta_key']) && isset($atts['meta_value'])) {
            $meta_query[] = array(
                'key' => $atts['meta_key'],
                'value' => $atts['meta_value'],
                'compare' => '='
            );
        }
        
        if (isset($atts['min_rating'])) {
            $meta_query[] = array(
                'key' => '_rating',
                'value' => floatval($atts['min_rating']),
                'type' => 'NUMERIC',
                'compare' => '>='
            );
        }
        
        if (!empty($meta_query)) {
            $query_args['meta_query'] = $meta_query;
            
            if (isset($atts['meta_key'])) {
                $query_args['meta_key'] = $atts['meta_key'];
            }
        }
        
        return $query_args;
    }
    
    /**
     * Рендерира решетка с парфюми
     */
    private function render_parfume_grid($query, $atts) {
        $columns = intval($atts['columns']);
        $grid_class = 'parfume-grid parfume-grid-' . $columns . '-cols';
        
        ob_start();
        ?>
        <div class="<?php echo esc_attr($grid_class); ?>">
            <?php while ($query->have_posts()): $query->the_post(); ?>
                <div class="parfume-grid-item">
                    <?php echo $this->render_parfume_card(get_the_ID(), $atts); ?>
                </div>
            <?php endwhile; ?>
        </div>
        
        <?php if ($atts['pagination'] === 'true' && $query->max_num_pages > 1): ?>
            <div class="parfume-pagination">
                <?php
                echo paginate_links(array(
                    'total' => $query->max_num_pages,
                    'prev_text' => __('« Предишна', 'parfume-reviews'),
                    'next_text' => __('Следваща »', 'parfume-reviews')
                ));
                ?>
            </div>
        <?php endif; ?>
        <?php
        
        wp_reset_postdata();
        
        return ob_get_clean();
    }
    
    /**
     * Рендерира карточка на парфюм
     */
    private function render_parfume_card($post_id, $atts = array()) {
        $defaults = array(
            'show_rating' => 'true',
            'show_price' => 'true',
            'show_excerpt' => 'false'
        );
        
        $atts = array_merge($defaults, $atts);
        
        ob_start();
        ?>
        <div class="parfume-card" data-post-id="<?php echo esc_attr($post_id); ?>">
            <div class="parfume-thumbnail">
                <a href="<?php echo esc_url(get_permalink($post_id)); ?>">
                    <?php if (has_post_thumbnail($post_id)): ?>
                        <?php echo get_the_post_thumbnail($post_id, 'medium'); ?>
                    <?php else: ?>
                        <div class="no-image-placeholder">
                            <span class="dashicons dashicons-format-image"></span>
                        </div>
                    <?php endif; ?>
                </a>
            </div>
            
            <div class="parfume-card-content">
                <h3 class="parfume-title">
                    <a href="<?php echo esc_url(get_permalink($post_id)); ?>">
                        <?php echo esc_html(get_the_title($post_id)); ?>
                    </a>
                </h3>
                
                <?php
                // Марка
                $brands = wp_get_post_terms($post_id, 'marki');
                if (!empty($brands) && !is_wp_error($brands)):
                ?>
                    <div class="parfume-brand">
                        <?php foreach ($brands as $brand): ?>
                            <a href="<?php echo esc_url(get_term_link($brand)); ?>" class="brand-link">
                                <?php echo esc_html($brand->name); ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($atts['show_rating'] === 'true'): ?>
                    <?php
                    $rating = get_post_meta($post_id, '_rating', true);
                    if ($rating):
                    ?>
                        <div class="parfume-rating">
                            <?php echo $this->parfume_rating_shortcode(array('post_id' => $post_id, 'size' => 'small')); ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
                
                <?php if ($atts['show_price'] === 'true'): ?>
                    <?php
                    $price = get_post_meta($post_id, '_price', true);
                    if ($price):
                    ?>
                        <div class="parfume-price">
                            <span class="price-value"><?php echo esc_html($price); ?> лв.</span>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
                
                <?php if ($atts['show_excerpt'] === 'true'): ?>
                    <div class="parfume-excerpt">
                        <?php echo esc_html(get_the_excerpt($post_id)); ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="parfume-card-actions">
                <a href="<?php echo esc_url(get_permalink($post_id)); ?>" class="view-button">
                    <?php _e('Виж детайли', 'parfume-reviews'); ?>
                </a>
            </div>
        </div>
        <?php
        
        return ob_get_clean();
    }
    
    /**
     * Получава текст за рейтинг
     */
    private function get_rating_text($rating) {
        if ($rating >= 9) {
            return __('Изключителен', 'parfume-reviews');
        } elseif ($rating >= 8) {
            return __('Отличен', 'parfume-reviews');
        } elseif ($rating >= 7) {
            return __('Много добър', 'parfume-reviews');
        } elseif ($rating >= 6) {
            return __('Добър', 'parfume-reviews');
        } elseif ($rating >= 5) {
            return __('Среден', 'parfume-reviews');
        } else {
            return __('Слаб', 'parfume-reviews');
        }
    }
    
    /**
     * Получава последно разгледани парфюми
     */
    private function get_recently_viewed_parfumes($count = 5) {
        if (!isset($_SESSION)) {
            session_start();
        }
        
        $recently_viewed = isset($_SESSION['parfume_recently_viewed']) ? $_SESSION['parfume_recently_viewed'] : array();
        
        return array_slice($recently_viewed, 0, $count);
    }
    
    /**
     * Намира подобни парфюми
     */
    private function find_similar_parfumes($post_id, $atts) {
        $method = $atts['method'];
        $count = intval($atts['count']);
        
        $similar_ids = array();
        
        switch ($method) {
            case 'brand':
                $similar_ids = $this->find_similar_by_brand($post_id, $count);
                break;
            case 'notes':
                $similar_ids = $this->find_similar_by_notes($post_id, $count);
                break;
            case 'gender':
                $similar_ids = $this->find_similar_by_gender($post_id, $count);
                break;
            default:
                $similar_ids = $this->find_similar_auto($post_id, $count);
                break;
        }
        
        return $similar_ids;
    }
    
    /**
     * Намира подобни по марка
     */
    private function find_similar_by_brand($post_id, $count) {
        $brands = wp_get_post_terms($post_id, 'marki', array('fields' => 'ids'));
        
        if (empty($brands)) {
            return array();
        }
        
        $query = new \WP_Query(array(
            'post_type' => 'parfume',
            'posts_per_page' => $count + 1, // +1 защото ще изключим текущия пост
            'post__not_in' => array($post_id),
            'tax_query' => array(
                array(
                    'taxonomy' => 'marki',
                    'field' => 'term_id',
                    'terms' => $brands
                )
            )
        ));
        
        return wp_list_pluck($query->posts, 'ID');
    }
    
    /**
     * Намира подобни по ноти
     */
    private function find_similar_by_notes($post_id, $count) {
        $notes = wp_get_post_terms($post_id, 'notes', array('fields' => 'ids'));
        
        if (empty($notes)) {
            return array();
        }
        
        $query = new \WP_Query(array(
            'post_type' => 'parfume',
            'posts_per_page' => $count + 1,
            'post__not_in' => array($post_id),
            'tax_query' => array(
                array(
                    'taxonomy' => 'notes',
                    'field' => 'term_id',
                    'terms' => $notes,
                    'operator' => 'IN'
                )
            )
        ));
        
        return wp_list_pluck($query->posts, 'ID');
    }
    
    /**
     * Намира подобни по пол
     */
    private function find_similar_by_gender($post_id, $count) {
        $genders = wp_get_post_terms($post_id, 'gender', array('fields' => 'ids'));
        
        if (empty($genders)) {
            return array();
        }
        
        $query = new \WP_Query(array(
            'post_type' => 'parfume',
            'posts_per_page' => $count + 1,
            'post__not_in' => array($post_id),
            'tax_query' => array(
                array(
                    'taxonomy' => 'gender',
                    'field' => 'term_id',
                    'terms' => $genders
                )
            )
        ));
        
        return wp_list_pluck($query->posts, 'ID');
    }
    
    /**
     * Намира подобни автоматично (комбинация от критерии)
     */
    private function find_similar_auto($post_id, $count) {
        // Комбинираме резултати от различни методи
        $similar_by_brand = $this->find_similar_by_brand($post_id, $count / 2);
        $similar_by_notes = $this->find_similar_by_notes($post_id, $count / 2);
        
        $combined = array_merge($similar_by_brand, $similar_by_notes);
        $unique_ids = array_unique($combined);
        
        return array_slice($unique_ids, 0, $count);
    }
    
    /**
     * Получава детайли за парфюм
     */
    private function get_parfume_details($post_id) {
        return array(
            'rating' => get_post_meta($post_id, '_rating', true),
            'price' => get_post_meta($post_id, '_price', true),
            'release_year' => get_post_meta($post_id, '_release_year', true),
            'longevity' => get_post_meta($post_id, '_longevity', true),
            'sillage' => get_post_meta($post_id, '_sillage', true),
            'bottle_size' => get_post_meta($post_id, '_bottle_size', true),
            'pros' => get_post_meta($post_id, '_pros', true),
            'cons' => get_post_meta($post_id, '_cons', true),
            'aroma_chart' => get_post_meta($post_id, '_aroma_chart', true)
        );
    }
    
    /**
     * Парсва fields параметъра
     */
    private function parse_fields_parameter($fields, $available_fields) {
        if ($fields === 'all') {
            return array_keys($available_fields);
        }
        
        $requested_fields = explode(',', $fields);
        $requested_fields = array_map('trim', $requested_fields);
        
        return array_intersect($requested_fields, array_keys($available_fields));
    }
    
    /**
     * Рендерира детайли за парфюм
     */
    private function render_parfume_details($details, $fields, $layout) {
        if (empty($fields)) {
            return '';
        }
        
        $labels = array(
            'rating' => __('Рейтинг', 'parfume-reviews'),
            'price' => __('Цена', 'parfume-reviews'),
            'release_year' => __('Година на излизане', 'parfume-reviews'),
            'longevity' => __('Издръжливост', 'parfume-reviews'),
            'sillage' => __('Силаж', 'parfume-reviews'),
            'bottle_size' => __('Размер на бутилката', 'parfume-reviews'),
            'pros' => __('Предимства', 'parfume-reviews'),
            'cons' => __('Недостатъци', 'parfume-reviews'),
            'aroma_chart' => __('Графика на аромата', 'parfume-reviews')
        );
        
        ob_start();
        ?>
        <div class="parfume-details parfume-details-<?php echo esc_attr($layout); ?>">
            <?php foreach ($fields as $field): ?>
                <?php if (!empty($details[$field])): ?>
                    <div class="detail-item detail-<?php echo esc_attr($field); ?>">
                        <span class="detail-label"><?php echo esc_html($labels[$field]); ?>:</span>
                        <span class="detail-value">
                            <?php
                            if ($field === 'rating') {
                                echo $this->parfume_rating_shortcode(array('post_id' => get_the_ID(), 'size' => 'small'));
                            } elseif ($field === 'price') {
                                echo esc_html($details[$field]) . ' лв.';
                            } elseif (in_array($field, array('pros', 'cons'))) {
                                $items = explode("\n", $details[$field]);
                                echo '<ul>';
                                foreach ($items as $item) {
                                    if (trim($item)) {
                                        echo '<li>' . esc_html(trim($item)) . '</li>';
                                    }
                                }
                                echo '</ul>';
                            } elseif ($field === 'aroma_chart') {
                                echo $this->render_aroma_chart($details[$field]);
                            } else {
                                echo esc_html($details[$field]);
                            }
                            ?>
                        </span>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
        <?php
        
        return ob_get_clean();
    }
    
    /**
     * Рендерира графика на аромата
     */
    private function render_aroma_chart($chart_data) {
        if (!is_array($chart_data)) {
            return '';
        }
        
        $chart_fields = array(
            'freshness' => __('Свежест', 'parfume-reviews'),
            'sweetness' => __('Сладост', 'parfume-reviews'),
            'intensity' => __('Интензивност', 'parfume-reviews'),
            'warmth' => __('Топлота', 'parfume-reviews')
        );
        
        ob_start();
        ?>
        <div class="aroma-chart">
            <?php foreach ($chart_fields as $field => $label): ?>
                <?php if (isset($chart_data[$field])): ?>
                    <div class="chart-item">
                        <span class="chart-label"><?php echo esc_html($label); ?></span>
                        <div class="chart-bar">
                            <div class="chart-fill" style="width: <?php echo intval($chart_data[$field]) * 10; ?>%"></div>
                        </div>
                        <span class="chart-value"><?php echo intval($chart_data[$field]); ?>/10</span>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
        <?php
        
        return ob_get_clean();
    }
    
    /**
     * Fallback за comparison button
     */
    private function render_comparison_button_fallback($post_id, $atts) {
        ob_start();
        ?>
        <button class="parfume-comparison-button" 
                data-post-id="<?php echo esc_attr($post_id); ?>"
                data-text-add="<?php echo esc_attr($atts['text_add']); ?>"
                data-text-remove="<?php echo esc_attr($atts['text_remove']); ?>">
            <span class="button-icon">
                <span class="dashicons dashicons-plus-alt"></span>
            </span>
            <span class="button-text"><?php echo esc_html($atts['text_add']); ?></span>
        </button>
        <?php
        
        return ob_get_clean();
    }
    
    /**
     * Получава регистрираните shortcodes
     */
    public function get_registered_shortcodes() {
        return $this->shortcodes;
    }
    
    /**
     * Проверява дали shortcode е регистриран
     */
    public function is_shortcode_registered($shortcode) {
        return isset($this->shortcodes[$shortcode]);
    }
    
    /**
     * Получава статистики за използването на shortcodes
     */
    public function get_shortcode_usage_stats() {
        global $wpdb;
        
        $stats = array();
        
        foreach (array_keys($this->shortcodes) as $shortcode) {
            $count = $wpdb->get_var($wpdb->prepare("
                SELECT COUNT(*) 
                FROM {$wpdb->posts} 
                WHERE post_content LIKE %s 
                AND post_status = 'publish'
            ", '%[' . $shortcode . '%'));
            
            $stats[$shortcode] = intval($count);
        }
        
        return $stats;
    }
    
    /**
     * Изчиства кеша за shortcodes
     */
    public function clear_cache() {
        $this->cache = array();
    }
}