<?php
/**
 * Admin Components Loader
 * Зарежда всички admin оптимизации и компоненти
 * 
 * Файл: includes/admin/class-admin-loader.php
 */

namespace Parfume_Reviews\Admin;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Основен loader клас за admin компоненти
 */
class Admin_Loader {
    
    private $performance_optimizer;
    private $meta_box_optimizer;
    
    public function __construct() {
        add_action('admin_init', array($this, 'load_admin_components'));
        add_action('admin_notices', array($this, 'show_optimization_notice'));
    }
    
    /**
     * Зарежда всички admin компоненти
     */
    public function load_admin_components() {
        // Зареждаме Performance Optimizer само за parfume страници
        if ($this->should_load_optimizations()) {
            $this->load_performance_optimizer();
            $this->load_meta_box_optimizer();
        }
        
        // Зареждаме общи admin подобрения
        $this->load_general_improvements();
    }
    
    /**
     * Проверява дали трябва да зареди оптимизации
     */
    private function should_load_optimizations() {
        global $pagenow, $post_type;
        
        // Проверяваме дали сме в admin
        if (!is_admin()) {
            return false;
        }
        
        // Проверяваме за parfume edit страници
        $is_parfume_edit = (
            ($pagenow === 'post.php' || $pagenow === 'post-new.php') && 
            $post_type === 'parfume'
        ) || (
            isset($_GET['post_type']) && $_GET['post_type'] === 'parfume'
        );
        
        // Проверяваме за taxonomy страници
        $is_taxonomy_page = (
            $pagenow === 'edit-tags.php' || $pagenow === 'term.php'
        ) && isset($_GET['taxonomy']) && in_array($_GET['taxonomy'], 
            array('marki', 'notes', 'perfumer', 'gender', 'aroma_type', 'season', 'intensity')
        );
        
        return $is_parfume_edit || $is_taxonomy_page;
    }
    
    /**
     * Зарежда Performance Optimizer
     */
    private function load_performance_optimizer() {
        if (!class_exists('\Parfume_Reviews\Admin\Performance_Optimizer')) {
            require_once PARFUME_REVIEWS_PLUGIN_DIR . 'includes/admin/class-performance-optimizer.php';
        }
        
        $this->performance_optimizer = new Performance_Optimizer();
        
        // Debug лог
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Parfume Reviews: Performance Optimizer loaded');
        }
    }
    
    /**
     * Зарежда Meta Box Optimizer
     */
    private function load_meta_box_optimizer() {
        if (!class_exists('\Parfume_Reviews\Admin\Meta_Box_Optimizer')) {
            require_once PARFUME_REVIEWS_PLUGIN_DIR . 'includes/admin/class-meta-box-optimizer.php';
        }
        
        $this->meta_box_optimizer = new Meta_Box_Optimizer();
    }
    
    /**
     * Зарежда общи admin подобрения
     */
    private function load_general_improvements() {
        // Кеширане на често използвани queries
        add_filter('pre_get_terms', array($this, 'cache_admin_term_queries'), 10, 2);
        
        // Оптимизация на admin queries
        add_action('pre_get_posts', array($this, 'optimize_admin_queries'));
        
        // Компресия на admin assets
        add_action('admin_enqueue_scripts', array($this, 'optimize_admin_assets'), 999);
        
        // Дебъг информация за производителност
        if (defined('WP_DEBUG') && WP_DEBUG && current_user_can('manage_options')) {
            add_action('admin_footer', array($this, 'show_performance_debug'));
        }
    }
    
    /**
     * Кешира admin term queries
     */
    public function cache_admin_term_queries($terms, $taxonomies) {
        if (!is_admin()) {
            return $terms;
        }
        
        // Кешираме само за наши таксономии
        $our_taxonomies = array('marki', 'notes', 'perfumer', 'gender', 'aroma_type', 'season', 'intensity');
        $intersection = array_intersect($taxonomies, $our_taxonomies);
        
        if (empty($intersection)) {
            return $terms;
        }
        
        // Генерираме cache key
        $cache_key = 'parfume_admin_terms_' . md5(serialize($taxonomies));
        $cached_terms = wp_cache_get($cache_key);
        
        if (false !== $cached_terms) {
            return $cached_terms;
        }
        
        // Ако няма кеш, ще се върне null и ще се изпълни оригиналния query
        // След това ще кешираме резултата
        add_filter('get_terms', function($terms, $taxonomies_filter, $args) use ($cache_key, $our_taxonomies) {
            $intersection = array_intersect($taxonomies_filter, $our_taxonomies);
            if (!empty($intersection)) {
                wp_cache_set($cache_key, $terms, '', 1800); // 30 минути кеш
            }
            return $terms;
        }, 10, 3);
        
        return $terms;
    }
    
    /**
     * Оптимизира admin queries
     */
    public function optimize_admin_queries($query) {
        if (!is_admin() || !$query->is_main_query()) {
            return;
        }
        
        // Ограничаваме броя posts в admin lists
        if ($query->get('post_type') === 'parfume') {
            $posts_per_page = $query->get('posts_per_page');
            if ($posts_per_page === -1 || $posts_per_page > 50) {
                $query->set('posts_per_page', 20); // Лимит от 20 парфюма на страница
            }
        }
        
        // Не зареждаме ненужни meta fields в admin списъци
        if (is_admin() && isset($_GET['post_type']) && $_GET['post_type'] === 'parfume') {
            $query->set('meta_query', array()); // Почистваме meta queries в списъците
        }
    }
    
    /**
     * Оптимизира admin assets
     */
    public function optimize_admin_assets($hook) {
        // Дефериране на некритични скриптове
        global $wp_scripts;
        
        if (!$this->should_load_optimizations()) {
            return;
        }
        
        // Списък на некритични скриптове за дефериране
        $defer_scripts = array(
            'jquery-ui-autocomplete',
            'suggest',
            'media-upload',
            'thickbox'
        );
        
        foreach ($defer_scripts as $script_handle) {
            if (isset($wp_scripts->registered[$script_handle])) {
                $wp_scripts->registered[$script_handle]->extra['defer'] = true;
            }
        }
        
        // Добавяме preload за критични стилове
        $critical_styles = array(
            'parfume-admin-optimization',
            'admin-bar',
            'dashicons'
        );
        
        foreach ($critical_styles as $style_handle) {
            if (wp_style_is($style_handle, 'enqueued')) {
                echo '<link rel="preload" href="' . esc_url(wp_styles()->registered[$style_handle]->src) . '" as="style">';
            }
        }
    }
    
    /**
     * Показва уведомление за оптимизацията
     */
    public function show_optimization_notice() {
        if (!$this->should_load_optimizations()) {
            return;
        }
        
        $dismissed = get_user_meta(get_current_user_id(), 'parfume_optimization_notice_dismissed', true);
        if ($dismissed) {
            return;
        }
        
        ?>
        <div class="notice notice-info is-dismissible" id="parfume-optimization-notice">
            <p>
                <strong><?php _e('Parfume Reviews', 'parfume-reviews'); ?>:</strong>
                <?php _e('Админ оптимизациите са активни! Таксономиите се зареждат с lazy loading за по-бърза работа.', 'parfume-reviews'); ?>
            </p>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            $('#parfume-optimization-notice').on('click', '.notice-dismiss', function() {
                $.post(ajaxurl, {
                    action: 'dismiss_parfume_optimization_notice',
                    nonce: '<?php echo wp_create_nonce('dismiss_notice'); ?>'
                });
            });
        });
        </script>
        <?php
        
        // AJAX хендлър за dismissing на notice
        add_action('wp_ajax_dismiss_parfume_optimization_notice', function() {
            check_ajax_referer('dismiss_notice', 'nonce');
            update_user_meta(get_current_user_id(), 'parfume_optimization_notice_dismissed', true);
            wp_die();
        });
    }
    
    /**
     * Показва дебъг информация за производителност
     */
    public function show_performance_debug() {
        $memory_usage = memory_get_usage(true);
        $memory_peak = memory_get_peak_usage(true);
        $memory_limit = ini_get('memory_limit');
        
        $execution_time = microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'];
        
        ?>
        <div id="parfume-performance-debug" style="position: fixed; bottom: 20px; right: 20px; background: #000; color: #fff; padding: 10px; border-radius: 5px; font-size: 11px; z-index: 9999; max-width: 300px;">
            <strong>Parfume Reviews Debug</strong><br>
            Memory: <?php echo size_format($memory_usage); ?> / <?php echo $memory_limit; ?><br>
            Peak: <?php echo size_format($memory_peak); ?><br>
            Time: <?php echo number_format($execution_time, 4); ?>s<br>
            Queries: <?php echo get_num_queries(); ?><br>
            <?php if (function_exists('wp_cache_get_cache_stats')): ?>
                Cache hits: <?php echo wp_cache_get_cache_stats()['cache_hits']; ?><br>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * Получава статистики за производителност
     */
    public function get_performance_stats() {
        return array(
            'memory_usage' => memory_get_usage(true),
            'memory_peak' => memory_get_peak_usage(true),
            'execution_time' => microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'],
            'queries_count' => get_num_queries(),
            'cached_terms' => wp_cache_get('parfume_cached_terms_count') ?: 0
        );
    }
    
    /**
     * Изчиства всички кешове
     */
    public function clear_all_caches() {
        // Изчистваме term кешовете
        $taxonomies = array('marki', 'notes', 'perfumer', 'gender', 'aroma_type', 'season', 'intensity');
        
        foreach ($taxonomies as $taxonomy) {
            wp_cache_delete('parfume_admin_terms_' . $taxonomy);
            wp_cache_delete('parfume_popular_terms_' . $taxonomy);
        }
        
        // Изчистваме transients
        delete_transient('parfume_admin_performance_stats');
        
        // Тригърираме WordPress flush
        wp_cache_flush();
        
        return true;
    }
}