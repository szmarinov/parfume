<?php
namespace Parfume_Reviews\Admin;

/**
 * Meta Box Optimizer
 * Оптимизира meta boxes за по-бърза работа
 * 
 * Файл: includes/admin/class-meta-box-optimizer.php
 */
class Meta_Box_Optimizer {
    
    private $optimized_meta_boxes = array();
    
    public function __construct() {
        add_action('add_meta_boxes', array($this, 'optimize_meta_boxes'), 1);
        add_action('admin_enqueue_scripts', array($this, 'enqueue_meta_box_scripts'));
        add_action('save_post', array($this, 'save_optimized_meta_boxes'), 1, 2);
        
        // AJAX хендлъри
        add_action('wp_ajax_create_taxonomy_term', array($this, 'ajax_create_taxonomy_term'));
        add_action('wp_ajax_get_term_hierarchy', array($this, 'ajax_get_term_hierarchy'));
    }
    
    /**
     * Оптимизира meta boxes при зареждане
     */
    public function optimize_meta_boxes() {
        global $post_type;
        
        if ($post_type !== 'parfume') {
            return;
        }
        
        // Премахваме стандартните taxonomy meta boxes
        $taxonomies = array('marki', 'notes', 'perfumer', 'gender', 'aroma_type', 'season', 'intensity');
        
        foreach ($taxonomies as $taxonomy) {
            remove_meta_box($taxonomy . 'div', 'parfume', 'side');
            remove_meta_box('tagsdiv-' . $taxonomy, 'parfume', 'side');
        }
        
        // Добавяме оптимизирани meta boxes с по-ниски приоритети
        $this->add_optimized_meta_boxes();
    }
    
    /**
     * Добавя оптимизирани meta boxes
     */
    private function add_optimized_meta_boxes() {
        $taxonomies_config = array(
            'marki' => array(
                'title' => __('Марки', 'parfume-reviews'),
                'priority' => 'high',
                'lazy' => true // Големи таксономии с lazy loading
            ),
            'notes' => array(
                'title' => __('Парфюмни ноти', 'parfume-reviews'),
                'priority' => 'high',
                'lazy' => true
            ),
            'perfumer' => array(
                'title' => __('Парфюмеристи', 'parfume-reviews'),
                'priority' => 'default',
                'lazy' => true
            ),
            'gender' => array(
                'title' => __('Пол', 'parfume-reviews'),
                'priority' => 'default',
                'lazy' => false // Малки таксономии без lazy loading
            ),
            'aroma_type' => array(
                'title' => __('Тип аромат', 'parfume-reviews'),
                'priority' => 'default',
                'lazy' => false
            ),
            'season' => array(
                'title' => __('Сезон', 'parfume-reviews'),
                'priority' => 'low',
                'lazy' => false
            ),
            'intensity' => array(
                'title' => __('Интензивност', 'parfume-reviews'),
                'priority' => 'low',
                'lazy' => false
            )
        );
        
        foreach ($taxonomies_config as $taxonomy => $config) {
            if (!taxonomy_exists($taxonomy)) {
                continue;
            }
            
            add_meta_box(
                'parfume_' . $taxonomy . '_optimized',
                $config['title'],
                array($this, 'render_optimized_meta_box'),
                'parfume',
                'side',
                $config['priority'],
                array(
                    'taxonomy' => $taxonomy,
                    'lazy' => $config['lazy']
                )
            );
            
            $this->optimized_meta_boxes[$taxonomy] = $config;
        }
    }
    
    /**
     * Рендерира оптимизиран meta box
     */
    public function render_optimized_meta_box($post, $metabox) {
        $taxonomy = $metabox['args']['taxonomy'];
        $use_lazy = $metabox['args']['lazy'];
        
        if (!taxonomy_exists($taxonomy)) {
            echo '<p>' . __('Таксономията не съществува.', 'parfume-reviews') . '</p>';
            return;
        }
        
        $taxonomy_obj = get_taxonomy($taxonomy);
        $term_count = wp_count_terms(array('taxonomy' => $taxonomy, 'hide_empty' => false));
        
        // Nonce за security
        wp_nonce_field('parfume_' . $taxonomy . '_meta_box', 'parfume_' . $taxonomy . '_nonce');
        
        echo '<div id="parfume-' . esc_attr($taxonomy) . '-meta-box" class="parfume-optimized-meta-box" data-taxonomy="' . esc_attr($taxonomy) . '">';
        
        if ($use_lazy && $term_count > 30) {
            $this->render_lazy_meta_box($post, $taxonomy, $taxonomy_obj);
        } else {
            $this->render_standard_meta_box($post, $taxonomy, $taxonomy_obj);
        }
        
        echo '</div>';
        
        // Добавяме статистика
        echo '<div class="meta-box-stats">';
        echo '<small>' . sprintf(__('Общо %d термина', 'parfume-reviews'), $term_count) . '</small>';
        echo '</div>';
    }
    
    /**
     * Рендерира lazy loading meta box
     */
    private function render_lazy_meta_box($post, $taxonomy, $taxonomy_obj) {
        $selected_terms = wp_get_object_terms($post->ID, $taxonomy, array('fields' => 'ids'));
        $selected_terms = !is_wp_error($selected_terms) ? $selected_terms : array();
        
        ?>
        <div class="lazy-meta-box">
            <!-- Бързо търсене -->
            <div class="quick-search">
                <input type="search" 
                       class="taxonomy-quick-search" 
                       placeholder="<?php echo esc_attr($taxonomy_obj->labels->search_items); ?>"
                       data-taxonomy="<?php echo esc_attr($taxonomy); ?>"
                       data-post-id="<?php echo $post->ID; ?>">
                <div class="search-results-dropdown"></div>
            </div>
            
            <!-- Най-използвани терми -->
            <?php $this->render_popular_terms_section($taxonomy, $selected_terms); ?>
            
            <!-- Избрани терми -->
            <?php if (!empty($selected_terms)): ?>
                <div class="selected-terms-section">
                    <h4><?php _e('Избрани:', 'parfume-reviews'); ?> <span class="count"><?php echo count($selected_terms); ?></span></h4>
                    <div class="selected-terms-list" id="selected-<?php echo $taxonomy; ?>-terms">
                        <?php $this->render_selected_terms_list($taxonomy, $selected_terms); ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Load more секция -->
            <div class="load-more-section">
                <button type="button" 
                        class="button button-secondary load-all-terms" 
                        data-taxonomy="<?php echo esc_attr($taxonomy); ?>"
                        data-post-id="<?php echo $post->ID; ?>">
                    <?php _e('Покажи всички терми', 'parfume-reviews'); ?>
                </button>
            </div>
            
            <!-- Контейнер за всички терми -->
            <div class="all-terms-section" style="display: none;">
                <div class="terms-loading"><?php _e('Зареждане...', 'parfume-reviews'); ?></div>
                <div class="all-terms-list"></div>
            </div>
            
            <!-- Добавяне на нов терм -->
            <?php if (current_user_can($taxonomy_obj->cap->edit_terms)): ?>
                <div class="add-new-term-section">
                    <details>
                        <summary><?php echo $taxonomy_obj->labels->add_new_item; ?></summary>
                        <div class="add-new-form">
                            <input type="text" 
                                   class="new-term-input" 
                                   placeholder="<?php echo esc_attr($taxonomy_obj->labels->new_item_name); ?>">
                            <button type="button" 
                                    class="button button-primary add-term-btn"
                                    data-taxonomy="<?php echo esc_attr($taxonomy); ?>">
                                <?php _e('Добави', 'parfume-reviews'); ?>
                            </button>
                        </div>
                    </details>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Hidden field за избраните терми -->
        <input type="hidden" 
               name="parfume_<?php echo $taxonomy; ?>_terms" 
               id="parfume-<?php echo $taxonomy; ?>-selected" 
               value="<?php echo esc_attr(implode(',', $selected_terms)); ?>">
        <?php
    }
    
    /**
     * Рендерира стандартен meta box
     */
    private function render_standard_meta_box($post, $taxonomy, $taxonomy_obj) {
        $terms = get_terms(array(
            'taxonomy' => $taxonomy,
            'hide_empty' => false,
            'orderby' => 'name',
            'order' => 'ASC'
        ));
        
        $selected_terms = wp_get_object_terms($post->ID, $taxonomy, array('fields' => 'ids'));
        $selected_terms = !is_wp_error($selected_terms) ? $selected_terms : array();
        
        if (is_wp_error($terms) || empty($terms)) {
            echo '<p>' . __('Няма налични терми.', 'parfume-reviews') . '</p>';
            return;
        }
        
        ?>
        <div class="standard-meta-box">
            <div class="terms-checklist">
                <?php foreach ($terms as $term): ?>
                    <label class="term-option">
                        <input type="checkbox" 
                               name="parfume_<?php echo $taxonomy; ?>_terms[]" 
                               value="<?php echo $term->term_id; ?>"
                               <?php checked(in_array($term->term_id, $selected_terms)); ?>>
                        <?php echo esc_html($term->name); ?>
                        <?php if ($term->count > 0): ?>
                            <span class="term-count">(<?php echo $term->count; ?>)</span>
                        <?php endif; ?>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Рендерира секцията с популярни терми
     */
    private function render_popular_terms_section($taxonomy, $selected_terms) {
        $popular_terms = get_terms(array(
            'taxonomy' => $taxonomy,
            'orderby' => 'count',
            'order' => 'DESC',
            'number' => 8,
            'hide_empty' => false
        ));
        
        if (is_wp_error($popular_terms) || empty($popular_terms)) {
            return;
        }
        
        ?>
        <div class="popular-terms-section">
            <h4><?php _e('Популярни:', 'parfume-reviews'); ?></h4>
            <div class="popular-terms-grid">
                <?php foreach ($popular_terms as $term): ?>
                    <label class="popular-term-option" data-term-id="<?php echo $term->term_id; ?>">
                        <input type="checkbox" 
                               value="<?php echo $term->term_id; ?>"
                               <?php checked(in_array($term->term_id, $selected_terms)); ?>>
                        <span class="term-name"><?php echo esc_html($term->name); ?></span>
                        <span class="term-count">(<?php echo $term->count; ?>)</span>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Рендерира списъка с избрани терми
     */
    private function render_selected_terms_list($taxonomy, $selected_terms) {
        if (empty($selected_terms)) {
            return;
        }
        
        $terms = get_terms(array(
            'taxonomy' => $taxonomy,
            'include' => $selected_terms,
            'hide_empty' => false
        ));
        
        if (is_wp_error($terms)) {
            return;
        }
        
        foreach ($terms as $term) {
            ?>
            <div class="selected-term-item" data-term-id="<?php echo $term->term_id; ?>">
                <span class="term-name"><?php echo esc_html($term->name); ?></span>
                <button type="button" class="remove-term" data-term-id="<?php echo $term->term_id; ?>">×</button>
            </div>
            <?php
        }
    }
    
    /**
     * AJAX: Създава нов терм в таксономия
     */
    public function ajax_create_taxonomy_term() {
        check_ajax_referer('parfume_admin_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $taxonomy = sanitize_text_field($_POST['taxonomy']);
        $term_name = sanitize_text_field($_POST['term_name']);
        
        if (empty($term_name) || !taxonomy_exists($taxonomy)) {
            wp_send_json_error('Invalid data');
        }
        
        $taxonomy_obj = get_taxonomy($taxonomy);
        if (!current_user_can($taxonomy_obj->cap->edit_terms)) {
            wp_send_json_error('Cannot create terms in this taxonomy');
        }
        
        $result = wp_insert_term($term_name, $taxonomy);
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }
        
        $term = get_term($result['term_id'], $taxonomy);
        
        wp_send_json_success(array(
            'term_id' => $term->term_id,
            'term_name' => $term->name,
            'term_html' => '<div class="selected-term-item" data-term-id="' . $term->term_id . '">
                <span class="term-name">' . esc_html($term->name) . '</span>
                <button type="button" class="remove-term" data-term-id="' . $term->term_id . '">×</button>
            </div>'
        ));
    }
    
    /**
     * AJAX: Получава йерархията на терми
     */
    public function ajax_get_term_hierarchy() {
        check_ajax_referer('parfume_admin_nonce', 'nonce');
        
        $taxonomy = sanitize_text_field($_POST['taxonomy']);
        $parent_id = intval($_POST['parent_id']);
        
        if (!taxonomy_exists($taxonomy)) {
            wp_send_json_error('Invalid taxonomy');
        }
        
        $terms = get_terms(array(
            'taxonomy' => $taxonomy,
            'parent' => $parent_id,
            'hide_empty' => false,
            'orderby' => 'name'
        ));
        
        if (is_wp_error($terms)) {
            wp_send_json_error($terms->get_error_message());
        }
        
        wp_send_json_success($terms);
    }
    
    /**
     * Записва оптимизираните meta boxes
     */
    public function save_optimized_meta_boxes($post_id, $post) {
        if ($post->post_type !== 'parfume') {
            return;
        }
        
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        foreach ($this->optimized_meta_boxes as $taxonomy => $config) {
            $nonce_field = 'parfume_' . $taxonomy . '_nonce';
            $terms_field = 'parfume_' . $taxonomy . '_terms';
            
            if (!isset($_POST[$nonce_field]) || !wp_verify_nonce($_POST[$nonce_field], 'parfume_' . $taxonomy . '_meta_box')) {
                continue;
            }
            
            $selected_terms = array();
            
            // За lazy loading meta boxes
            if (isset($_POST[$terms_field]) && is_string($_POST[$terms_field])) {
                $term_ids = explode(',', $_POST[$terms_field]);
                $selected_terms = array_map('intval', array_filter($term_ids));
            }
            // За стандартни meta boxes
            elseif (isset($_POST[$terms_field]) && is_array($_POST[$terms_field])) {
                $selected_terms = array_map('intval', $_POST[$terms_field]);
            }
            
            // Записваме термите
            wp_set_object_terms($post_id, $selected_terms, $taxonomy);
        }
    }
    
    /**
     * Зарежда scripts за meta box оптимизация
     */
    public function enqueue_meta_box_scripts($hook) {
        global $post_type;
        
        if ($post_type !== 'parfume' || !in_array($hook, array('post.php', 'post-new.php'))) {
            return;
        }
        
        wp_enqueue_script(
            'parfume-meta-box-optimizer',
            PARFUME_REVIEWS_PLUGIN_URL . 'assets/js/meta-box-optimizer.js',
            array('jquery', 'jquery-ui-autocomplete'),
            PARFUME_REVIEWS_VERSION,
            true
        );
        
        wp_enqueue_style(
            'parfume-meta-box-optimizer',
            PARFUME_REVIEWS_PLUGIN_URL . 'assets/css/meta-box-optimizer.css',
            array(),
            PARFUME_REVIEWS_VERSION
        );
        
        wp_localize_script('parfume-meta-box-optimizer', 'parfumeMetaBoxOpt', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('parfume_admin_nonce'),
            'strings' => array(
                'loading' => __('Зареждане...', 'parfume-reviews'),
                'search_placeholder' => __('Търси терм...', 'parfume-reviews'),
                'no_results' => __('Няма резултати', 'parfume-reviews'),
                'add_new' => __('Добави нов', 'parfume-reviews'),
                'remove' => __('Премахни', 'parfume-reviews'),
                'confirm_remove' => __('Сигурни ли сте?', 'parfume-reviews'),
                'error' => __('Възникна грешка', 'parfume-reviews')
            )
        ));
    }
}