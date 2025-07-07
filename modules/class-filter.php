<?php
/**
 * Модул за филтри и търсене
 * 
 * @package Parfume_Catalog
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class PC_Filters {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_ajax_pc_filter_parfumes', array($this, 'filter_parfumes'));
        add_action('wp_ajax_nopriv_pc_filter_parfumes', array($this, 'filter_parfumes'));
        add_action('wp_ajax_pc_search_parfumes_autocomplete', array($this, 'search_autocomplete'));
        add_action('wp_ajax_nopriv_pc_search_parfumes_autocomplete', array($this, 'search_autocomplete'));
        add_shortcode('pc_filters', array($this, 'filters_shortcode'));
        add_shortcode('pc_search', array($this, 'search_shortcode'));
    }
    
    public function init() {
        // Добавяне на филтри в архивните страници
        add_action('pc_before_archive_content', array($this, 'display_filters'));
        
        // Модификация на основната заявка за архивни страници
        add_action('pre_get_posts', array($this, 'modify_archive_query'));
    }
    
    /**
     * AJAX филтриране на парфюми
     */
    public function filter_parfumes() {
        check_ajax_referer('pc_nonce', 'nonce');
        
        $filters = array();
        
        // Събиране на филтри от POST данни
        if (!empty($_POST['brands'])) {
            $filters['marki'] = array_map('intval', $_POST['brands']);
        }
        
        if (!empty($_POST['types'])) {
            $filters['tip'] = array_map('intval', $_POST['types']);
        }
        
        if (!empty($_POST['concentrations'])) {
            $filters['vid_aromat'] = array_map('intval', $_POST['concentrations']);
        }
        
        if (!empty($_POST['seasons'])) {
            $filters['sezon'] = array_map('intval', $_POST['seasons']);
        }
        
        if (!empty($_POST['intensities'])) {
            $filters['intenzivnost'] = array_map('intval', $_POST['intensities']);
        }
        
        if (!empty($_POST['notes'])) {
            $filters['notes'] = array_map('intval', $_POST['notes']);
        }
        
        // Ценови диапазон
        $price_min = !empty($_POST['price_min']) ? floatval($_POST['price_min']) : 0;
        $price_max = !empty($_POST['price_max']) ? floatval($_POST['price_max']) : 0;
        
        // Рейтинг
        $rating_min = !empty($_POST['rating_min']) ? intval($_POST['rating_min']) : 0;
        
        // Търсене по текст
        $search_text = !empty($_POST['search_text']) ? sanitize_text_field($_POST['search_text']) : '';
        
        // Сортиране
        $orderby = !empty($_POST['orderby']) ? sanitize_text_field($_POST['orderby']) : 'date';
        $order = !empty($_POST['order']) ? sanitize_text_field($_POST['order']) : 'DESC';
        
        // Пагинация
        $page = !empty($_POST['page']) ? intval($_POST['page']) : 1;
        $per_page = !empty($_POST['per_page']) ? intval($_POST['per_page']) : 12;
        
        // Изпълнение на заявката
        $results = $this->execute_filter_query($filters, $price_min, $price_max, $rating_min, $search_text, $orderby, $order, $page, $per_page);
        
        wp_send_json_success($results);
    }
    
    /**
     * Изпълнение на филтрираща заявка
     */
    private function execute_filter_query($filters, $price_min, $price_max, $rating_min, $search_text, $orderby, $order, $page, $per_page) {
        $args = array(
            'post_type' => 'parfumes',
            'post_status' => 'publish',
            'posts_per_page' => $per_page,
            'paged' => $page
        );
        
        // Таксономии
        if (!empty($filters)) {
            $tax_query = array('relation' => 'AND');
            
            foreach ($filters as $taxonomy => $term_ids) {
                if ($taxonomy === 'notes') {
                    // Специална логика за нотки - търси във всички типове нотки
                    $notes_query = array(
                        'relation' => 'OR',
                        array(
                            'key' => '_pc_top_notes',
                            'value' => $term_ids,
                            'compare' => 'IN'
                        ),
                        array(
                            'key' => '_pc_middle_notes',
                            'value' => $term_ids,
                            'compare' => 'IN'
                        ),
                        array(
                            'key' => '_pc_base_notes',
                            'value' => $term_ids,
                            'compare' => 'IN'
                        )
                    );
                    
                    if (!isset($args['meta_query'])) {
                        $args['meta_query'] = array();
                    }
                    $args['meta_query'][] = $notes_query;
                } else {
                    $tax_query[] = array(
                        'taxonomy' => $taxonomy,
                        'field' => 'term_id',
                        'terms' => $term_ids,
                        'operator' => 'IN'
                    );
                }
            }
            
            if (count($tax_query) > 1) {
                $args['tax_query'] = $tax_query;
            }
        }
        
        // Мета заявки
        $meta_query = array();
        
        // Ценови филтър
        if ($price_min > 0 || $price_max > 0) {
            if ($price_min > 0 && $price_max > 0) {
                $meta_query[] = array(
                    'key' => '_pc_min_price',
                    'value' => array($price_min, $price_max),
                    'type' => 'NUMERIC',
                    'compare' => 'BETWEEN'
                );
            } elseif ($price_min > 0) {
                $meta_query[] = array(
                    'key' => '_pc_min_price',
                    'value' => $price_min,
                    'type' => 'NUMERIC',
                    'compare' => '>='
                );
            } elseif ($price_max > 0) {
                $meta_query[] = array(
                    'key' => '_pc_min_price',
                    'value' => $price_max,
                    'type' => 'NUMERIC',
                    'compare' => '<='
                );
            }
        }
        
        // Рейтинг филтър
        if ($rating_min > 0) {
            $meta_query[] = array(
                'key' => '_pc_average_rating',
                'value' => $rating_min,
                'type' => 'NUMERIC',
                'compare' => '>='
            );
        }
        
        if (!empty($meta_query)) {
            $args['meta_query'] = array_merge(isset($args['meta_query']) ? $args['meta_query'] : array(), $meta_query);
        }
        
        // Текстово търсене
        if (!empty($search_text)) {
            $args['s'] = $search_text;
        }
        
        // Сортиране
        switch ($orderby) {
            case 'title':
                $args['orderby'] = 'title';
                break;
            case 'price':
                $args['meta_key'] = '_pc_min_price';
                $args['orderby'] = 'meta_value_num';
                break;
            case 'rating':
                $args['meta_key'] = '_pc_average_rating';
                $args['orderby'] = 'meta_value_num';
                break;
            case 'popularity':
                $args['meta_key'] = '_pc_views_count';
                $args['orderby'] = 'meta_value_num';
                break;
            case 'date':
            default:
                $args['orderby'] = 'date';
                break;
        }
        
        $args['order'] = $order;
        
        // Изпълнение на заявката
        $query = new WP_Query($args);
        
        $results = array(
            'posts' => array(),
            'pagination' => array(
                'current_page' => $page,
                'total_pages' => $query->max_num_pages,
                'total_posts' => $query->found_posts,
                'per_page' => $per_page
            )
        );
        
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $results['posts'][] = $this->format_parfume_data(get_the_ID());
            }
            wp_reset_postdata();
        }
        
        return $results;
    }
    
    /**
     * Форматиране на данни за парфюм
     */
    private function format_parfume_data($post_id) {
        $brands = wp_get_post_terms($post_id, 'marki');
        $types = wp_get_post_terms($post_id, 'tip');
        $concentrations = wp_get_post_terms($post_id, 'vid_aromat');
        
        return array(
            'id' => $post_id,
            'title' => get_the_title($post_id),
            'url' => get_permalink($post_id),
            'image' => get_the_post_thumbnail_url($post_id, 'medium'),
            'excerpt' => get_the_excerpt($post_id),
            'brand' => !empty($brands) ? $brands[0]->name : '',
            'type' => !empty($types) ? $types[0]->name : '',
            'concentration' => !empty($concentrations) ? $concentrations[0]->name : '',
            'rating' => get_post_meta($post_id, '_pc_average_rating', true),
            'reviews_count' => get_post_meta($post_id, '_pc_total_reviews', true),
            'min_price' => get_post_meta($post_id, '_pc_min_price', true),
            'top_notes' => $this->get_parfume_notes($post_id, 'top', 3)
        );
    }
    
    /**
     * Получаване на нотки за парфюм
     */
    private function get_parfume_notes($post_id, $type, $limit = null) {
        $notes = get_post_meta($post_id, '_pc_' . $type . '_notes', true);
        if (empty($notes)) {
            return array();
        }
        
        $note_names = array();
        $count = 0;
        
        foreach ($notes as $note_id) {
            if ($limit && $count >= $limit) {
                break;
            }
            
            $term = get_term($note_id, 'notki');
            if ($term && !is_wp_error($term)) {
                $note_names[] = $term->name;
                $count++;
            }
        }
        
        return $note_names;
    }
    
    /**
     * Автодопълване при търсене
     */
    public function search_autocomplete() {
        check_ajax_referer('pc_nonce', 'nonce');
        
        $search_term = sanitize_text_field($_POST['term']);
        
        if (strlen($search_term) < 2) {
            wp_send_json_error(__('Минимум 2 символа за търсене', 'parfume-catalog'));
        }
        
        $results = array();
        
        // Търсене в парфюми
        $parfumes = $this->search_parfumes($search_term, 5);
        foreach ($parfumes as $parfume) {
            $results[] = array(
                'type' => 'parfume',
                'label' => $parfume->post_title,
                'value' => $parfume->post_title,
                'url' => get_permalink($parfume->ID),
                'image' => get_the_post_thumbnail_url($parfume->ID, 'thumbnail')
            );
        }
        
        // Търсене в марки
        $brands = $this->search_taxonomy_terms('marki', $search_term, 3);
        foreach ($brands as $brand) {
            $results[] = array(
                'type' => 'brand',
                'label' => $brand->name,
                'value' => $brand->name,
                'url' => get_term_link($brand),
                'count' => $brand->count
            );
        }
        
        // Търсене в нотки
        $notes = $this->search_taxonomy_terms('notki', $search_term, 3);
        foreach ($notes as $note) {
            $results[] = array(
                'type' => 'note',
                'label' => $note->name,
                'value' => $note->name,
                'url' => get_term_link($note),
                'count' => $note->count
            );
        }
        
        wp_send_json_success($results);
    }
    
    /**
     * Търсене в парфюми
     */
    private function search_parfumes($search_term, $limit = 10) {
        $args = array(
            'post_type' => 'parfumes',
            'post_status' => 'publish',
            'posts_per_page' => $limit,
            's' => $search_term
        );
        
        $query = new WP_Query($args);
        return $query->posts;
    }
    
    /**
     * Търсене в таксономии
     */
    private function search_taxonomy_terms($taxonomy, $search_term, $limit = 10) {
        $args = array(
            'taxonomy' => $taxonomy,
            'hide_empty' => true,
            'number' => $limit,
            'name__like' => $search_term
        );
        
        return get_terms($args);
    }
    
    /**
     * Показване на филтри
     */
    public function display_filters() {
        if (!is_post_type_archive('parfumes') && !is_tax(array('marki', 'tip', 'vid_aromat', 'sezon', 'intenzivnost', 'notki'))) {
            return;
        }
        
        ?>
        <div class="pc-filters-wrapper" id="pc-filters">
            <div class="pc-filters-header">
                <h3><?php _e('Филтри', 'parfume-catalog'); ?></h3>
                <button class="pc-filters-toggle" id="pc-filters-toggle">
                    <span class="dashicons dashicons-filter"></span>
                    <?php _e('Покажи филтри', 'parfume-catalog'); ?>
                </button>
            </div>
            
            <div class="pc-filters-content" id="pc-filters-content">
                <form id="pc-filters-form" class="pc-filters-form">
                    <?php wp_nonce_field('pc_nonce', 'pc_nonce'); ?>
                    
                    <!-- Търсене -->
                    <div class="pc-filter-group">
                        <label><?php _e('Търсене', 'parfume-catalog'); ?></label>
                        <div class="pc-search-wrapper">
                            <input type="text" 
                                   id="pc-search-input" 
                                   name="search_text" 
                                   placeholder="<?php _e('Търси парфюм, марка или нотка...', 'parfume-catalog'); ?>"
                                   autocomplete="off">
                            <div id="pc-search-autocomplete" class="pc-autocomplete-results"></div>
                        </div>
                    </div>
                    
                    <!-- Марки -->
                    <div class="pc-filter-group">
                        <label><?php _e('Марка', 'parfume-catalog'); ?></label>
                        <div class="pc-filter-options pc-scrollable">
                            <?php $this->display_taxonomy_filter('marki', 'brands[]'); ?>
                        </div>
                    </div>
                    
                    <!-- Тип -->
                    <div class="pc-filter-group">
                        <label><?php _e('Тип', 'parfume-catalog'); ?></label>
                        <div class="pc-filter-options">
                            <?php $this->display_taxonomy_filter('tip', 'types[]'); ?>
                        </div>
                    </div>
                    
                    <!-- Концентрация -->
                    <div class="pc-filter-group">
                        <label><?php _e('Концентрация', 'parfume-catalog'); ?></label>
                        <div class="pc-filter-options">
                            <?php $this->display_taxonomy_filter('vid_aromat', 'concentrations[]'); ?>
                        </div>
                    </div>
                    
                    <!-- Сезон -->
                    <div class="pc-filter-group">
                        <label><?php _e('Сезон', 'parfume-catalog'); ?></label>
                        <div class="pc-filter-options">
                            <?php $this->display_taxonomy_filter('sezon', 'seasons[]'); ?>
                        </div>
                    </div>
                    
                    <!-- Интензивност -->
                    <div class="pc-filter-group">
                        <label><?php _e('Интензивност', 'parfume-catalog'); ?></label>
                        <div class="pc-filter-options">
                            <?php $this->display_taxonomy_filter('intenzivnost', 'intensities[]'); ?>
                        </div>
                    </div>
                    
                    <!-- Нотки -->
                    <div class="pc-filter-group">
                        <label><?php _e('Ароматни нотки', 'parfume-catalog'); ?></label>
                        <div class="pc-filter-search">
                            <input type="text" 
                                   id="pc-notes-search" 
                                   placeholder="<?php _e('Търси нотка...', 'parfume-catalog'); ?>">
                        </div>
                        <div class="pc-filter-options pc-scrollable" id="pc-notes-list">
                            <?php $this->display_taxonomy_filter('notki', 'notes[]', 20); ?>
                        </div>
                    </div>
                    
                    <!-- Ценови диапазон -->
                    <div class="pc-filter-group">
                        <label><?php _e('Цена (лв.)', 'parfume-catalog'); ?></label>
                        <div class="pc-price-range">
                            <input type="number" 
                                   name="price_min" 
                                   placeholder="<?php _e('От', 'parfume-catalog'); ?>" 
                                   min="0">
                            <span>-</span>
                            <input type="number" 
                                   name="price_max" 
                                   placeholder="<?php _e('До', 'parfume-catalog'); ?>" 
                                   min="0">
                        </div>
                    </div>
                    
                    <!-- Рейтинг -->
                    <div class="pc-filter-group">
                        <label><?php _e('Минимален рейтинг', 'parfume-catalog'); ?></label>
                        <div class="pc-rating-filter">
                            <select name="rating_min">
                                <option value=""><?php _e('Всички', 'parfume-catalog'); ?></option>
                                <option value="1">1+ ★</option>
                                <option value="2">2+ ★</option>
                                <option value="3">3+ ★</option>
                                <option value="4">4+ ★</option>
                                <option value="5">5 ★</option>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Сортиране -->
                    <div class="pc-filter-group">
                        <label><?php _e('Сортиране', 'parfume-catalog'); ?></label>
                        <div class="pc-sort-options">
                            <select name="orderby">
                                <option value="date"><?php _e('Най-нови', 'parfume-catalog'); ?></option>
                                <option value="title"><?php _e('По име', 'parfume-catalog'); ?></option>
                                <option value="price"><?php _e('По цена', 'parfume-catalog'); ?></option>
                                <option value="rating"><?php _e('По рейтинг', 'parfume-catalog'); ?></option>
                                <option value="popularity"><?php _e('По популярност', 'parfume-catalog'); ?></option>
                            </select>
                            <select name="order">
                                <option value="DESC"><?php _e('Низходящо', 'parfume-catalog'); ?></option>
                                <option value="ASC"><?php _e('Възходящо', 'parfume-catalog'); ?></option>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Действия -->
                    <div class="pc-filter-actions">
                        <button type="submit" class="pc-btn pc-btn-primary">
                            <?php _e('Приложи филтри', 'parfume-catalog'); ?>
                        </button>
                        <button type="button" id="pc-clear-filters" class="pc-btn pc-btn-secondary">
                            <?php _e('Изчисти', 'parfume-catalog'); ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Резултати -->
        <div class="pc-results-wrapper">
            <div class="pc-results-header">
                <div class="pc-results-count" id="pc-results-count">
                    <!-- Динамично обновяване -->
                </div>
                <div class="pc-results-loading" id="pc-results-loading" style="display:none;">
                    <?php _e('Зареждане...', 'parfume-catalog'); ?>
                </div>
            </div>
            
            <div class="pc-results-content" id="pc-results-content">
                <!-- Динамично съдържание -->
            </div>
            
            <div class="pc-pagination" id="pc-pagination">
                <!-- Динамична пагинация -->
            </div>
        </div>
        <?php
    }
    
    /**
     * Показване на филтър за таксономия
     */
    private function display_taxonomy_filter($taxonomy, $name, $limit = null) {
        $terms = get_terms(array(
            'taxonomy' => $taxonomy,
            'hide_empty' => true,
            'number' => $limit,
            'orderby' => 'name',
            'order' => 'ASC'
        ));
        
        if (empty($terms) || is_wp_error($terms)) {
            return;
        }
        
        foreach ($terms as $term) {
            ?>
            <label class="pc-filter-option">
                <input type="checkbox" 
                       name="<?php echo esc_attr($name); ?>" 
                       value="<?php echo $term->term_id; ?>"
                       data-term-name="<?php echo esc_attr($term->name); ?>">
                <span class="pc-option-label">
                    <?php echo esc_html($term->name); ?>
                    <span class="pc-option-count">(<?php echo $term->count; ?>)</span>
                </span>
            </label>
            <?php
        }
    }
    
    /**
     * Модификация на архивната заявка
     */
    public function modify_archive_query($query) {
        if (is_admin() || !$query->is_main_query()) {
            return;
        }
        
        if (!is_post_type_archive('parfumes') && !is_tax(array('marki', 'tip', 'vid_aromat', 'sezon', 'intenzivnost', 'notki'))) {
            return;
        }
        
        // Увеличаване на броя постове на страница
        $query->set('posts_per_page', 12);
        
        // Проверка за GET параметри за филтриране
        if (!empty($_GET['pc_filter'])) {
            $this->apply_url_filters($query);
        }
    }
    
    /**
     * Прилагане на филтри от URL
     */
    private function apply_url_filters($query) {
        $meta_query = array();
        $tax_query = array();
        
        // Ценови филтър
        if (!empty($_GET['price_min']) || !empty($_GET['price_max'])) {
            $price_min = !empty($_GET['price_min']) ? floatval($_GET['price_min']) : 0;
            $price_max = !empty($_GET['price_max']) ? floatval($_GET['price_max']) : 0;
            
            if ($price_min > 0 && $price_max > 0) {
                $meta_query[] = array(
                    'key' => '_pc_min_price',
                    'value' => array($price_min, $price_max),
                    'type' => 'NUMERIC',
                    'compare' => 'BETWEEN'
                );
            } elseif ($price_min > 0) {
                $meta_query[] = array(
                    'key' => '_pc_min_price',
                    'value' => $price_min,
                    'type' => 'NUMERIC',
                    'compare' => '>='
                );
            } elseif ($price_max > 0) {
                $meta_query[] = array(
                    'key' => '_pc_min_price',
                    'value' => $price_max,
                    'type' => 'NUMERIC',
                    'compare' => '<='
                );
            }
        }
        
        // Рейтинг филтър
        if (!empty($_GET['rating_min'])) {
            $meta_query[] = array(
                'key' => '_pc_average_rating',
                'value' => intval($_GET['rating_min']),
                'type' => 'NUMERIC',
                'compare' => '>='
            );
        }
        
        // Таксономии
        $taxonomies = array('marki', 'tip', 'vid_aromat', 'sezon', 'intenzivnost');
        foreach ($taxonomies as $taxonomy) {
            if (!empty($_GET[$taxonomy])) {
                $terms = array_map('intval', explode(',', $_GET[$taxonomy]));
                $tax_query[] = array(
                    'taxonomy' => $taxonomy,
                    'field' => 'term_id',
                    'terms' => $terms,
                    'operator' => 'IN'
                );
            }
        }
        
        // Прилагане на заявките
        if (!empty($meta_query)) {
            $query->set('meta_query', $meta_query);
        }
        
        if (!empty($tax_query)) {
            $tax_query['relation'] = 'AND';
            $query->set('tax_query', $tax_query);
        }
        
        // Сортиране
        if (!empty($_GET['orderby'])) {
            $orderby = sanitize_text_field($_GET['orderby']);
            $order = !empty($_GET['order']) ? sanitize_text_field($_GET['order']) : 'DESC';
            
            switch ($orderby) {
                case 'title':
                    $query->set('orderby', 'title');
                    break;
                case 'price':
                    $query->set('meta_key', '_pc_min_price');
                    $query->set('orderby', 'meta_value_num');
                    break;
                case 'rating':
                    $query->set('meta_key', '_pc_average_rating');
                    $query->set('orderby', 'meta_value_num');
                    break;
            }
            
            $query->set('order', $order);
        }
    }
    
    /**
     * Шорткод за филтри
     */
    public function filters_shortcode($atts) {
        $atts = shortcode_atts(array(
            'show_search' => 'true',
            'show_price' => 'true',
            'show_rating' => 'true',
            'taxonomies' => 'marki,tip,vid_aromat,sezon,intenzivnost'
        ), $atts);
        
        ob_start();
        $this->display_filters();
        return ob_get_clean();
    }
    
    /**
     * Шорткод за търсене
     */
    public function search_shortcode($atts) {
        $atts = shortcode_atts(array(
            'placeholder' => __('Търси парфюм...', 'parfume-catalog'),
            'autocomplete' => 'true'
        ), $atts);
        
        ob_start();
        ?>
        <div class="pc-search-form">
            <form method="get" action="<?php echo esc_url(home_url('/')); ?>">
                <input type="hidden" name="post_type" value="parfumes">
                <div class="pc-search-wrapper">
                    <input type="text" 
                           name="s" 
                           placeholder="<?php echo esc_attr($atts['placeholder']); ?>"
                           value="<?php echo get_search_query(); ?>"
                           <?php if ($atts['autocomplete'] === 'true'): ?>
                           autocomplete="off"
                           class="pc-search-autocomplete"
                           <?php endif; ?>>
                    <button type="submit" class="pc-search-button">
                        <span class="dashicons dashicons-search"></span>
                    </button>
                    <?php if ($atts['autocomplete'] === 'true'): ?>
                    <div class="pc-autocomplete-results"></div>
                    <?php endif; ?>
                </div>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }
}