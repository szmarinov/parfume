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

class Parfume_Catalog_Filters {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function __construct() {
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
            $filters['notki'] = array_map('intval', $_POST['notes']);
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
        
        // Страница
        $paged = !empty($_POST['paged']) ? intval($_POST['paged']) : 1;
        
        // Създаване на WP заявка
        $args = array(
            'post_type' => 'parfumes',
            'post_status' => 'publish',
            'posts_per_page' => 12,
            'paged' => $paged,
            'orderby' => $orderby,
            'order' => $order
        );
        
        // Добавяне на таксономични филтри
        if (!empty($filters)) {
            $tax_query = array('relation' => 'AND');
            
            foreach ($filters as $taxonomy => $term_ids) {
                $tax_query[] = array(
                    'taxonomy' => $taxonomy,
                    'field' => 'term_id',
                    'terms' => $term_ids,
                    'operator' => 'IN'
                );
            }
            
            $args['tax_query'] = $tax_query;
        }
        
        // Мета заявки за цена и рейтинг
        $meta_query = array();
        
        if ($price_min > 0 || $price_max > 0) {
            $price_query = array(
                'key' => '_pc_base_price',
                'type' => 'NUMERIC',
                'compare' => 'BETWEEN'
            );
            
            if ($price_min > 0 && $price_max > 0) {
                $price_query['value'] = array($price_min, $price_max);
            } elseif ($price_min > 0) {
                $price_query['value'] = $price_min;
                $price_query['compare'] = '>=';
            } elseif ($price_max > 0) {
                $price_query['value'] = $price_max;
                $price_query['compare'] = '<=';
            }
            
            $meta_query[] = $price_query;
        }
        
        if ($rating_min > 0) {
            $meta_query[] = array(
                'key' => '_pc_average_rating',
                'value' => $rating_min,
                'type' => 'NUMERIC',
                'compare' => '>='
            );
        }
        
        if (!empty($meta_query)) {
            $args['meta_query'] = $meta_query;
        }
        
        // Търсене по текст
        if (!empty($search_text)) {
            $args['s'] = $search_text;
        }
        
        // Изпълнение на заявката
        $query = new WP_Query($args);
        
        // Подготовка на резултата
        ob_start();
        
        if ($query->have_posts()) {
            echo '<div class="pc-results-grid">';
            
            while ($query->have_posts()) {
                $query->the_post();
                $this->render_parfume_card(get_the_ID());
            }
            
            echo '</div>';
            
            // Пагинация
            if ($query->max_num_pages > 1) {
                echo '<div class="pc-pagination-wrapper">';
                echo paginate_links(array(
                    'total' => $query->max_num_pages,
                    'current' => $paged,
                    'format' => '?paged=%#%',
                    'add_args' => false,
                    'prev_text' => '&laquo;',
                    'next_text' => '&raquo;'
                ));
                echo '</div>';
            }
        } else {
            echo '<div class="pc-no-results">';
            echo '<p>' . __('Няма намерени парфюми с избраните филтри.', 'parfume-catalog') . '</p>';
            echo '</div>';
        }
        
        wp_reset_postdata();
        
        $content = ob_get_clean();
        
        wp_send_json_success(array(
            'content' => $content,
            'found_posts' => $query->found_posts,
            'max_num_pages' => $query->max_num_pages,
            'current_page' => $paged
        ));
    }
    
    /**
     * Рендериране на карточка за парфюм
     */
    private function render_parfume_card($post_id) {
        $featured_image = get_the_post_thumbnail_url($post_id, 'medium');
        $brands = wp_get_post_terms($post_id, 'marki');
        $brand_name = !empty($brands) ? $brands[0]->name : '';
        $base_price = get_post_meta($post_id, '_pc_base_price', true);
        $rating = get_post_meta($post_id, '_pc_average_rating', true);
        
        ?>
        <div class="pc-parfume-card" data-post-id="<?php echo $post_id; ?>">
            <div class="pc-card-image">
                <?php if ($featured_image): ?>
                    <img src="<?php echo esc_url($featured_image); ?>" alt="<?php echo esc_attr(get_the_title($post_id)); ?>">
                <?php else: ?>
                    <div class="pc-no-image">
                        <span class="dashicons dashicons-format-image"></span>
                    </div>
                <?php endif; ?>
                
                <div class="pc-card-actions">
                    <button class="pc-add-to-compare" data-post-id="<?php echo $post_id; ?>" title="<?php _e('Добави за сравнение', 'parfume-catalog'); ?>">
                        <span class="dashicons dashicons-plus-alt"></span>
                    </button>
                </div>
            </div>
            
            <div class="pc-card-content">
                <h3 class="pc-card-title">
                    <a href="<?php echo get_permalink($post_id); ?>"><?php echo get_the_title($post_id); ?></a>
                </h3>
                
                <?php if ($brand_name): ?>
                    <div class="pc-card-brand"><?php echo esc_html($brand_name); ?></div>
                <?php endif; ?>
                
                <?php if ($rating): ?>
                    <div class="pc-card-rating">
                        <?php $this->display_stars($rating); ?>
                        <span class="pc-rating-value">(<?php echo number_format($rating, 1); ?>)</span>
                    </div>
                <?php endif; ?>
                
                <?php if ($base_price): ?>
                    <div class="pc-card-price">
                        <span class="pc-price"><?php echo number_format($base_price, 2); ?> лв.</span>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * AJAX автоматично допълване при търсене
     */
    public function search_autocomplete() {
        if (empty($_POST['term'])) {
            wp_send_json_error('No search term provided');
        }
        
        $search_term = sanitize_text_field($_POST['term']);
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
                        <label><?php _e('Нотки', 'parfume-catalog'); ?></label>
                        <div class="pc-filter-options pc-scrollable">
                            <?php $this->display_taxonomy_filter('notki', 'notes[]', 20); ?>
                        </div>
                        <button type="button" class="pc-show-all-notes" data-taxonomy="notki">
                            <?php _e('Покажи всички нотки', 'parfume-catalog'); ?>
                        </button>
                    </div>
                    
                    <!-- Ценови диапазон -->
                    <div class="pc-filter-group">
                        <label><?php _e('Цена (лв.)', 'parfume-catalog'); ?></label>
                        <div class="pc-price-range">
                            <input type="number" name="price_min" placeholder="<?php _e('От', 'parfume-catalog'); ?>" min="0" step="0.01">
                            <span>-</span>
                            <input type="number" name="price_max" placeholder="<?php _e('До', 'parfume-catalog'); ?>" min="0" step="0.01">
                        </div>
                        <div class="pc-price-slider">
                            <div id="pc-price-range-slider"></div>
                        </div>
                    </div>
                    
                    <!-- Рейтинг -->
                    <div class="pc-filter-group">
                        <label><?php _e('Минимален рейтинг', 'parfume-catalog'); ?></label>
                        <div class="pc-rating-filter">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <label class="pc-rating-option">
                                    <input type="radio" name="rating_min" value="<?php echo $i; ?>">
                                    <div class="pc-stars">
                                        <?php $this->display_stars($i); ?>
                                        <span><?php echo $i; ?>+ <?php _e('звезди', 'parfume-catalog'); ?></span>
                                    </div>
                                </label>
                            <?php endfor; ?>
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
     * Показване на звезди за рейтинг
     */
    private function display_stars($rating) {
        $full_stars = floor($rating);
        $half_star = ($rating - $full_stars) >= 0.5;
        $empty_stars = 5 - $full_stars - ($half_star ? 1 : 0);
        
        echo '<div class="pc-stars">';
        
        // Пълни звезди
        for ($i = 0; $i < $full_stars; $i++) {
            echo '<span class="pc-star pc-star-full">★</span>';
        }
        
        // Половин звезда
        if ($half_star) {
            echo '<span class="pc-star pc-star-half">★</span>';
        }
        
        // Празни звезди
        for ($i = 0; $i < $empty_stars; $i++) {
            echo '<span class="pc-star pc-star-empty">☆</span>';
        }
        
        echo '</div>';
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
            $price_max = !empty($_GET['price_max']) ? floatval($_GET['price_max']) : PHP_INT_MAX;
            
            $meta_query[] = array(
                'key' => '_pc_base_price',
                'value' => array($price_min, $price_max),
                'type' => 'NUMERIC',
                'compare' => 'BETWEEN'
            );
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
        
        if (!empty($meta_query)) {
            $query->set('meta_query', $meta_query);
        }
        
        // Таксономични филтри
        $taxonomies = array('marki', 'tip', 'vid_aromat', 'sezon', 'intenzivnost', 'notki');
        
        foreach ($taxonomies as $taxonomy) {
            if (!empty($_GET[$taxonomy])) {
                $terms = array_map('intval', (array) $_GET[$taxonomy]);
                
                $tax_query[] = array(
                    'taxonomy' => $taxonomy,
                    'field' => 'term_id',
                    'terms' => $terms,
                    'operator' => 'IN'
                );
            }
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
                case 'price':
                    $query->set('meta_key', '_pc_base_price');
                    $query->set('orderby', 'meta_value_num');
                    break;
                case 'rating':
                    $query->set('meta_key', '_pc_average_rating');
                    $query->set('orderby', 'meta_value_num');
                    break;
                case 'popularity':
                    $query->set('meta_key', '_pc_view_count');
                    $query->set('orderby', 'meta_value_num');
                    break;
                default:
                    $query->set('orderby', $orderby);
            }
            
            $query->set('order', $order);
        }
    }
    
    /**
     * Shortcode за филтри
     */
    public function filters_shortcode($atts) {
        $atts = shortcode_atts(array(
            'show' => 'all', // all, search, price, brands, etc.
            'limit' => 10,
            'columns' => 1
        ), $atts);
        
        ob_start();
        $this->display_filters();
        return ob_get_clean();
    }
    
    /**
     * Shortcode за търсене
     */
    public function search_shortcode($atts) {
        $atts = shortcode_atts(array(
            'placeholder' => 'Търси парфюми...',
            'autocomplete' => 'true'
        ), $atts);
        
        ob_start();
        ?>
        <div class="pc-search-shortcode">
            <div class="pc-search-wrapper">
                <input type="text" 
                       id="pc-search-shortcode-input" 
                       name="search_text" 
                       placeholder="<?php echo esc_attr($atts['placeholder']); ?>"
                       autocomplete="off">
                <?php if ($atts['autocomplete'] === 'true'): ?>
                    <div id="pc-search-shortcode-autocomplete" class="pc-autocomplete-results"></div>
                <?php endif; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}