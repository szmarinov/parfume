<?php
namespace Parfume_Reviews\Admin;

/**
 * НОВА ОПТИМИЗАЦИЯ - Admin Performance Optimizer
 * Клас за оптимизиране на зареждането на администрацията
 * 
 * Файл: includes/admin/class-performance-optimizer.php
 */
class Performance_Optimizer {
    
    private $cached_terms = array();
    private $lazy_loaded_taxonomies = array();
    
    public function __construct() {
        // Основни hooks за оптимизация
        add_action('admin_init', array($this, 'init_admin_optimizations'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_optimization_scripts'));
        
        // Филтри за оптимизация на meta boxes
        add_filter('get_terms_args', array($this, 'optimize_terms_queries'), 10, 2);
        add_action('wp_ajax_load_taxonomy_terms', array($this, 'ajax_load_taxonomy_terms'));
        add_action('wp_ajax_search_taxonomy_terms', array($this, 'ajax_search_taxonomy_terms'));
        
        // Кеширане на често използвани queries
        add_action('save_post', array($this, 'clear_taxonomy_cache'));
        add_action('created_term', array($this, 'clear_taxonomy_cache'));
        add_action('edit_term', array($this, 'clear_taxonomy_cache'));
        add_action('delete_term', array($this, 'clear_taxonomy_cache'));
    }
    
    /**
     * Инициализира admin оптимизации
     */
    public function init_admin_optimizations() {
        // Само за parfume edit страници
        if (!$this->is_parfume_edit_page()) {
            return;
        }
        
        // Заменяме стандартните meta boxes с оптимизирани версии
        add_action('add_meta_boxes', array($this, 'replace_taxonomy_meta_boxes'), 5);
        
        // Добавяме lazy loading за големи таксономии
        $large_taxonomies = array('marki', 'notes', 'perfumer');
        foreach ($large_taxonomies as $taxonomy) {
            add_filter("wp_terms_checklist_args", array($this, 'optimize_checklist_args'), 10, 2);
        }
    }
    
    /**
     * Проверява дали сме на parfume edit страница
     */
    private function is_parfume_edit_page() {
        global $pagenow, $post_type;
        
        return (
            ($pagenow === 'post.php' || $pagenow === 'post-new.php') && 
            $post_type === 'parfume'
        ) || (
            isset($_GET['post_type']) && $_GET['post_type'] === 'parfume'
        );
    }
    
    /**
     * Заменя стандартните taxonomy meta boxes с оптимизирани
     */
    public function replace_taxonomy_meta_boxes() {
        $taxonomies = array('marki', 'notes', 'perfumer', 'gender', 'aroma_type', 'season', 'intensity');
        
        foreach ($taxonomies as $taxonomy) {
            if (!taxonomy_exists($taxonomy)) {
                continue;
            }
            
            $taxonomy_obj = get_taxonomy($taxonomy);
            
            // Премахваме стандартния meta box
            remove_meta_box($taxonomy . 'div', 'parfume', 'side');
            
            // Добавяме оптимизиран meta box
            add_meta_box(
                $taxonomy . '_optimized_div',
                $taxonomy_obj->labels->name,
                array($this, 'render_optimized_taxonomy_meta_box'),
                'parfume',
                'side',
                'default',
                array('taxonomy' => $taxonomy)
            );
        }
    }
    
    /**
     * Рендерира оптимизиран taxonomy meta box с lazy loading
     */
    public function render_optimized_taxonomy_meta_box($post, $metabox) {
        $taxonomy = $metabox['args']['taxonomy'];
        $taxonomy_obj = get_taxonomy($taxonomy);
        
        if (!$taxonomy_obj) {
            return;
        }
        
        $term_count = wp_count_terms(array('taxonomy' => $taxonomy, 'hide_empty' => false));
        $use_lazy_loading = $term_count > 50; // Lazy loading за над 50 термина
        
        echo '<div id="taxonomy-' . esc_attr($taxonomy) . '" class="categorydiv">';
        
        if ($use_lazy_loading) {
            $this->render_lazy_loading_taxonomy_box($post, $taxonomy, $taxonomy_obj);
        } else {
            $this->render_standard_taxonomy_box($post, $taxonomy, $taxonomy_obj);
        }
        
        echo '</div>';
    }
    
    /**
     * Рендерира lazy loading taxonomy box за големи таксономии
     */
    private function render_lazy_loading_taxonomy_box($post, $taxonomy, $taxonomy_obj) {
        $selected_terms = wp_get_object_terms($post->ID, $taxonomy, array('fields' => 'ids'));
        $selected_terms = !is_wp_error($selected_terms) ? $selected_terms : array();
        
        ?>
        <div class="tabs-panel">
            <!-- Търсене в реално време -->
            <div class="taxonomy-search-wrap">
                <label class="screen-reader-text" for="<?php echo $taxonomy; ?>-search"><?php echo $taxonomy_obj->labels->search_items; ?></label>
                <input type="search" 
                       id="<?php echo $taxonomy; ?>-search" 
                       class="taxonomy-search-input" 
                       placeholder="<?php echo esc_attr($taxonomy_obj->labels->search_items); ?>"
                       data-taxonomy="<?php echo esc_attr($taxonomy); ?>"
                       data-post-id="<?php echo $post->ID; ?>">
                <div class="search-results" id="<?php echo $taxonomy; ?>-search-results"></div>
            </div>
            
            <!-- Най-популярни / скоро използвани терми -->
            <div class="taxonomy-quick-access">
                <h4><?php _e('Често използвани', 'parfume-reviews'); ?></h4>
                <div id="<?php echo $taxonomy; ?>-popular-terms" class="popular-terms-list">
                    <?php $this->render_popular_terms($taxonomy, $selected_terms); ?>
                </div>
            </div>
            
            <!-- Избрани терми -->
            <?php if (!empty($selected_terms)): ?>
            <div class="taxonomy-selected-terms">
                <h4><?php _e('Избрани', 'parfume-reviews'); ?></h4>
                <ul id="<?php echo $taxonomy; ?>-selected-terms" class="categorychecklist">
                    <?php $this->render_selected_terms($taxonomy, $selected_terms); ?>
                </ul>
            </div>
            <?php endif; ?>
            
            <!-- Load more бутон -->
            <div class="taxonomy-load-more">
                <button type="button" 
                        class="button button-secondary load-more-terms" 
                        data-taxonomy="<?php echo esc_attr($taxonomy); ?>"
                        data-post-id="<?php echo $post->ID; ?>"
                        data-page="1">
                    <?php _e('Зареди всички терми', 'parfume-reviews'); ?>
                </button>
            </div>
            
            <!-- Контейнер за всички терми (lazy loaded) -->
            <div id="<?php echo $taxonomy; ?>-all-terms" class="all-terms-container" style="display: none;">
                <ul class="categorychecklist"></ul>
            </div>
            
            <!-- Добавяне на нов терм -->
            <div class="taxonomy-add-new">
                <h4><?php _e('Добави нов', 'parfume-reviews'); ?></h4>
                <div class="new-term-form">
                    <input type="text" 
                           class="new-term-name" 
                           placeholder="<?php echo esc_attr($taxonomy_obj->labels->add_new_item); ?>">
                    <button type="button" 
                            class="button button-primary add-new-term" 
                            data-taxonomy="<?php echo esc_attr($taxonomy); ?>">
                        <?php _e('Добави', 'parfume-reviews'); ?>
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Hidden input за избраните терми -->
        <input type="hidden" 
               name="tax_input[<?php echo $taxonomy; ?>][]" 
               id="<?php echo $taxonomy; ?>-selected-input" 
               value="<?php echo implode(',', $selected_terms); ?>">
        <?php
    }
    
    /**
     * Рендерира стандартен taxonomy box за малки таксономии
     */
    private function render_standard_taxonomy_box($post, $taxonomy, $taxonomy_obj) {
        $terms = $this->get_cached_terms($taxonomy);
        $selected_terms = wp_get_object_terms($post->ID, $taxonomy, array('fields' => 'ids'));
        $selected_terms = !is_wp_error($selected_terms) ? $selected_terms : array();
        
        ?>
        <div class="tabs-panel">
            <ul class="categorychecklist form-no-clear">
                <?php
                foreach ($terms as $term) {
                    $checked = in_array($term->term_id, $selected_terms) ? 'checked="checked"' : '';
                    ?>
                    <li>
                        <label class="selectit">
                            <input type="checkbox" 
                                   name="tax_input[<?php echo $taxonomy; ?>][]" 
                                   value="<?php echo $term->term_id; ?>" 
                                   <?php echo $checked; ?>>
                            <?php echo esc_html($term->name); ?>
                            <?php if ($term->count > 0): ?>
                                <span class="count">(<?php echo $term->count; ?>)</span>
                            <?php endif; ?>
                        </label>
                    </li>
                    <?php
                }
                ?>
            </ul>
        </div>
        <?php
    }
    
    /**
     * Рендерира популярни терми
     */
    private function render_popular_terms($taxonomy, $selected_terms) {
        $cache_key = 'parfume_popular_terms_' . $taxonomy;
        $popular_terms = wp_cache_get($cache_key);
        
        if (false === $popular_terms) {
            $popular_terms = get_terms(array(
                'taxonomy' => $taxonomy,
                'orderby' => 'count',
                'order' => 'DESC',
                'number' => 10,
                'hide_empty' => false
            ));
            
            if (!is_wp_error($popular_terms)) {
                wp_cache_set($cache_key, $popular_terms, '', 3600); // Cache за 1 час
            }
        }
        
        if (!empty($popular_terms) && !is_wp_error($popular_terms)) {
            foreach ($popular_terms as $term) {
                $checked = in_array($term->term_id, $selected_terms) ? 'checked="checked"' : '';
                ?>
                <label class="popular-term">
                    <input type="checkbox" 
                           name="tax_input[<?php echo $taxonomy; ?>][]" 
                           value="<?php echo $term->term_id; ?>" 
                           <?php echo $checked; ?>>
                    <?php echo esc_html($term->name); ?>
                    <span class="count">(<?php echo $term->count; ?>)</span>
                </label>
                <?php
            }
        }
    }
    
    /**
     * Рендерира избрани терми
     */
    private function render_selected_terms($taxonomy, $selected_terms) {
        if (empty($selected_terms)) {
            return;
        }
        
        $terms = get_terms(array(
            'taxonomy' => $taxonomy,
            'include' => $selected_terms,
            'hide_empty' => false
        ));
        
        if (!is_wp_error($terms) && !empty($terms)) {
            foreach ($terms as $term) {
                ?>
                <li>
                    <label class="selectit">
                        <input type="checkbox" 
                               name="tax_input[<?php echo $taxonomy; ?>][]" 
                               value="<?php echo $term->term_id; ?>" 
                               checked="checked">
                        <?php echo esc_html($term->name); ?>
                        <button type="button" class="remove-term" data-term-id="<?php echo $term->term_id; ?>">×</button>
                    </label>
                </li>
                <?php
            }
        }
    }
    
    /**
     * AJAX: Зарежда терми за таксономия
     */
    public function ajax_load_taxonomy_terms() {
        check_ajax_referer('parfume_admin_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_die('Insufficient permissions');
        }
        
        $taxonomy = sanitize_text_field($_POST['taxonomy']);
        $page = intval($_POST['page']);
        $per_page = 50;
        $offset = ($page - 1) * $per_page;
        
        $terms = get_terms(array(
            'taxonomy' => $taxonomy,
            'hide_empty' => false,
            'orderby' => 'name',
            'order' => 'ASC',
            'number' => $per_page,
            'offset' => $offset
        ));
        
        $post_id = intval($_POST['post_id']);
        $selected_terms = wp_get_object_terms($post_id, $taxonomy, array('fields' => 'ids'));
        $selected_terms = !is_wp_error($selected_terms) ? $selected_terms : array();
        
        if (!is_wp_error($terms) && !empty($terms)) {
            $html = '';
            foreach ($terms as $term) {
                $checked = in_array($term->term_id, $selected_terms) ? 'checked="checked"' : '';
                $html .= '<li>';
                $html .= '<label class="selectit">';
                $html .= '<input type="checkbox" name="tax_input[' . $taxonomy . '][]" value="' . $term->term_id . '" ' . $checked . '>';
                $html .= esc_html($term->name);
                if ($term->count > 0) {
                    $html .= ' <span class="count">(' . $term->count . ')</span>';
                }
                $html .= '</label>';
                $html .= '</li>';
            }
            
            wp_send_json_success(array(
                'html' => $html,
                'has_more' => count($terms) === $per_page
            ));
        } else {
            wp_send_json_error('No terms found');
        }
    }
    
    /**
     * AJAX: Търси терми в таксономия
     */
    public function ajax_search_taxonomy_terms() {
        check_ajax_referer('parfume_admin_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_die('Insufficient permissions');
        }
        
        $taxonomy = sanitize_text_field($_POST['taxonomy']);
        $search = sanitize_text_field($_POST['search']);
        
        $terms = get_terms(array(
            'taxonomy' => $taxonomy,
            'name__like' => $search,
            'hide_empty' => false,
            'number' => 20,
            'orderby' => 'name',
            'order' => 'ASC'
        ));
        
        $post_id = intval($_POST['post_id']);
        $selected_terms = wp_get_object_terms($post_id, $taxonomy, array('fields' => 'ids'));
        $selected_terms = !is_wp_error($selected_terms) ? $selected_terms : array();
        
        if (!is_wp_error($terms) && !empty($terms)) {
            $html = '';
            foreach ($terms as $term) {
                $checked = in_array($term->term_id, $selected_terms) ? 'checked="checked"' : '';
                $html .= '<label class="search-result-term">';
                $html .= '<input type="checkbox" name="tax_input[' . $taxonomy . '][]" value="' . $term->term_id . '" ' . $checked . '>';
                $html .= esc_html($term->name);
                $html .= ' <span class="count">(' . $term->count . ')</span>';
                $html .= '</label>';
            }
            
            wp_send_json_success(array('html' => $html));
        } else {
            wp_send_json_error('No matching terms found');
        }
    }
    
    /**
     * Оптимизира queries за терми
     */
    public function optimize_terms_queries($args, $taxonomies) {
        // Само в admin за парфюми
        if (!is_admin() || !$this->is_parfume_edit_page()) {
            return $args;
        }
        
        // Ограничаваме броя терми при първоначално зареждане
        if (!isset($args['number']) && in_array('marki', $taxonomies)) {
            $args['number'] = 20; // Само първите 20
            $args['orderby'] = 'count';
            $args['order'] = 'DESC';
        }
        
        return $args;
    }
    
    /**
     * Получава кеширани терми
     */
    private function get_cached_terms($taxonomy) {
        if (isset($this->cached_terms[$taxonomy])) {
            return $this->cached_terms[$taxonomy];
        }
        
        $cache_key = 'parfume_admin_terms_' . $taxonomy;
        $terms = wp_cache_get($cache_key);
        
        if (false === $terms) {
            $terms = get_terms(array(
                'taxonomy' => $taxonomy,
                'hide_empty' => false,
                'orderby' => 'name',
                'order' => 'ASC'
            ));
            
            if (!is_wp_error($terms)) {
                wp_cache_set($cache_key, $terms, '', 1800); // Cache за 30 минути
            }
        }
        
        $this->cached_terms[$taxonomy] = $terms;
        return $terms;
    }
    
    /**
     * Изчиства кеша на таксономии
     */
    public function clear_taxonomy_cache() {
        $taxonomies = array('marki', 'notes', 'perfumer', 'gender', 'aroma_type', 'season', 'intensity');
        
        foreach ($taxonomies as $taxonomy) {
            wp_cache_delete('parfume_admin_terms_' . $taxonomy);
            wp_cache_delete('parfume_popular_terms_' . $taxonomy);
        }
    }
    
    /**
     * Зарежда admin optimization scripts
     */
    public function enqueue_admin_optimization_scripts($hook) {
        if (!$this->is_parfume_edit_page()) {
            return;
        }
        
        wp_enqueue_script(
            'parfume-admin-optimization',
            PARFUME_REVIEWS_PLUGIN_URL . 'assets/js/admin-optimization.js',
            array('jquery', 'jquery-ui-autocomplete'),
            PARFUME_REVIEWS_VERSION,
            true
        );
        
        wp_enqueue_style(
            'parfume-admin-optimization',
            PARFUME_REVIEWS_PLUGIN_URL . 'assets/css/admin-optimization.css',
            array(),
            PARFUME_REVIEWS_VERSION
        );
        
        wp_localize_script('parfume-admin-optimization', 'parfumeAdminOpt', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('parfume_admin_nonce'),
            'strings' => array(
                'loading' => __('Зареждане...', 'parfume-reviews'),
                'search' => __('Търсене...', 'parfume-reviews'),
                'no_results' => __('Няма резултати', 'parfume-reviews'),
                'load_more' => __('Зареди още', 'parfume-reviews'),
                'add_term' => __('Добави терм', 'parfume-reviews'),
                'confirm_remove' => __('Сигурни ли сте, че искате да премахнете този терм?', 'parfume-reviews')
            )
        ));
    }
    
    /**
     * Оптимизира checklist args
     */
    public function optimize_checklist_args($args, $post_id) {
        if (!$this->is_parfume_edit_page()) {
            return $args;
        }
        
        // Ограничаваме броя показвани терми за големи таксономии
        $args['walker'] = new \Walker_Category_Checklist();
        $args['checked_ontop'] = true; // Избраните терми отгоре
        
        return $args;
    }
}