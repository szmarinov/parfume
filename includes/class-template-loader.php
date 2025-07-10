<?php
/**
 * Parfume Catalog Template Loader Class
 * 
 * Управлява зареждането на шаблони за фронтенда
 * 
 * @package Parfume_Catalog
 * @since 1.0.0
 */

// Предотвратяване на директен достъп
if (!defined('ABSPATH')) {
    exit;
}

class Parfume_Catalog_Template_Loader {

    /**
     * Конструктор
     */
    public function __construct() {
        add_filter('template_include', array($this, 'template_include'));
        add_filter('single_template', array($this, 'single_template'));
        add_filter('archive_template', array($this, 'archive_template'));
        add_filter('taxonomy_template', array($this, 'taxonomy_template'));
        add_action('wp_head', array($this, 'add_template_variables'));
        add_action('wp_footer', array($this, 'add_comparison_popup'));
        add_action('wp', array($this, 'track_recently_viewed'));
    }

    /**
     * Главна функция за зареждане на шаблони
     */
    public function template_include($template) {
        // Проверка за нашите post types и таксономии
        if (is_singular('parfumes')) {
            return $this->get_template('single-parfumes.php', $template);
        }

        if (is_singular('parfume_blog')) {
            return $this->get_template('blog-templates/single-blog.php', $template);
        }

        if (is_post_type_archive('parfumes')) {
            return $this->get_template('archive-parfumes.php', $template);
        }

        if (is_post_type_archive('parfume_blog')) {
            return $this->get_template('blog-templates/archive-blog.php', $template);
        }

        if (is_tax('parfume_type') || is_tax('parfume_vid') || is_tax('parfume_marki') || 
            is_tax('parfume_season') || is_tax('parfume_intensity') || is_tax('parfume_notes')) {
            return $this->get_taxonomy_template($template);
        }

        return $template;
    }

    /**
     * Single шаблони
     */
    public function single_template($template) {
        global $post;

        if ($post->post_type == 'parfumes') {
            $custom_template = $this->locate_template('single-parfumes.php');
            if ($custom_template) {
                return $custom_template;
            }
        }

        if ($post->post_type == 'parfume_blog') {
            $custom_template = $this->locate_template('blog-templates/single-blog.php');
            if ($custom_template) {
                return $custom_template;
            }
        }

        return $template;
    }

    /**
     * Archive шаблони
     */
    public function archive_template($template) {
        if (is_post_type_archive('parfumes')) {
            $custom_template = $this->locate_template('archive-parfumes.php');
            if ($custom_template) {
                return $custom_template;
            }
        }

        if (is_post_type_archive('parfume_blog')) {
            $custom_template = $this->locate_template('blog-templates/archive-blog.php');
            if ($custom_template) {
                return $custom_template;
            }
        }

        return $template;
    }

    /**
     * Taxonomy шаблони
     */
    public function taxonomy_template($template) {
        return $this->get_taxonomy_template($template);
    }

    /**
     * Получаване на правилния taxonomy шаблон
     */
    private function get_taxonomy_template($template) {
        $taxonomy = get_query_var('taxonomy');

        switch ($taxonomy) {
            case 'parfume_type':
                return $this->get_template('taxonomy-parfume-type.php', $template);
            
            case 'parfume_vid':
                return $this->get_template('taxonomy-parfume-vid.php', $template);
            
            case 'parfume_marki':
                return $this->get_template('taxonomy-parfume-marki.php', $template);
            
            case 'parfume_season':
                return $this->get_template('taxonomy-parfume-season.php', $template);
            
            case 'parfume_intensity':
                return $this->get_template('taxonomy-parfume-intensity.php', $template);
            
            case 'parfume_notes':
                return $this->get_template('taxonomy-parfume-notes.php', $template);
        }

        return $template;
    }

    /**
     * Търсене за template файл
     */
    private function get_template($template_name, $default_template) {
        $custom_template = $this->locate_template($template_name);
        
        if ($custom_template) {
            return $custom_template;
        }

        return $default_template;
    }

    /**
     * Локализиране на template файл
     */
    private function locate_template($template_name) {
        // Първо търси в активната тема
        $theme_template = locate_template(array(
            'parfume-catalog/' . $template_name,
            $template_name
        ));

        if ($theme_template) {
            return $theme_template;
        }

        // След това в плъгина
        $plugin_template = PARFUME_CATALOG_PLUGIN_DIR . 'templates/' . $template_name;
        
        if (file_exists($plugin_template)) {
            return $plugin_template;
        }

        return false;
    }

    /**
     * Добавяне на template променливи в head
     */
    public function add_template_variables() {
        if (is_singular('parfumes') || is_post_type_archive('parfumes') || 
            is_tax('parfume_type') || is_tax('parfume_vid') || is_tax('parfume_marki') || 
            is_tax('parfume_season') || is_tax('parfume_intensity') || is_tax('parfume_notes')) {
            
            // Настройки от плъгина
            $options = get_option('parfume_catalog_options', array());
            
            echo '<script type="text/javascript">';
            echo 'var parfume_catalog_config = ' . json_encode(array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('parfume_catalog_nonce'),
                'plugin_url' => PARFUME_CATALOG_PLUGIN_URL,
                'is_mobile' => wp_is_mobile(),
                'comparison_max_items' => isset($options['comparison_max_items']) ? $options['comparison_max_items'] : 4,
                'mobile_fixed_panel' => isset($options['mobile_fixed_panel']) ? $options['mobile_fixed_panel'] : 1,
                'mobile_show_x' => isset($options['mobile_show_x']) ? $options['mobile_show_x'] : 0,
                'mobile_z_index' => isset($options['mobile_z_index']) ? $options['mobile_z_index'] : 9999,
                'mobile_offset' => isset($options['mobile_offset']) ? $options['mobile_offset'] : 0,
                'strings' => array(
                    'add_to_comparison' => __('Добави за сравнение', 'parfume-catalog'),
                    'remove_from_comparison' => __('Премахни от сравнение', 'parfume-catalog'),
                    'comparison_max_reached' => __('Достигнат е максималният брой парфюми за сравнение', 'parfume-catalog'),
                    'comparison_min_required' => __('Необходими са поне 2 парфюма за сравнение', 'parfume-catalog'),
                    'copied_to_clipboard' => __('Копирано в клипборда', 'parfume-catalog'),
                    'copy_failed' => __('Неуспешно копиране', 'parfume-catalog'),
                    'loading' => __('Зареждане...', 'parfume-catalog'),
                    'error' => __('Възникна грешка', 'parfume-catalog')
                )
            )) . ';';
            echo '</script>';
        }
    }

    /**
     * Добавяне на comparison popup в footer
     */
    public function add_comparison_popup() {
        if (is_singular('parfumes') || is_post_type_archive('parfumes') || 
            is_tax('parfume_type') || is_tax('parfume_vid') || is_tax('parfume_marki') || 
            is_tax('parfume_season') || is_tax('parfume_intensity') || is_tax('parfume_notes')) {
            
            $this->render_comparison_popup();
        }
    }

    /**
     * Рендериране на comparison popup
     */
    private function render_comparison_popup() {
        ?>
        <div id="parfume-comparison-popup" class="parfume-comparison-popup" style="display: none;">
            <div class="comparison-popup-header">
                <h3><?php _e('Сравняване на парфюми', 'parfume-catalog'); ?></h3>
                <button type="button" class="comparison-close-btn" aria-label="<?php _e('Затвори', 'parfume-catalog'); ?>">
                    <span>&times;</span>
                </button>
            </div>
            
            <div class="comparison-popup-content">
                <div class="comparison-search">
                    <input type="text" id="comparison-search-input" placeholder="<?php _e('Търси парфюм за добавяне...', 'parfume-catalog'); ?>" />
                    <div id="comparison-search-results"></div>
                </div>
                
                <div class="comparison-table-container">
                    <table class="comparison-table">
                        <thead>
                            <tr id="comparison-table-header">
                                <th><?php _e('Характеристика', 'parfume-catalog'); ?></th>
                            </tr>
                        </thead>
                        <tbody id="comparison-table-body">
                            <!-- Динамично съдържание -->
                        </tbody>
                    </table>
                </div>
                
                <div class="comparison-actions">
                    <button type="button" class="button clear-all-comparison">
                        <?php _e('Изчисти всички', 'parfume-catalog'); ?>
                    </button>
                    <button type="button" class="button button-primary export-comparison">
                        <?php _e('Експортирай сравнението', 'parfume-catalog'); ?>
                    </button>
                </div>
            </div>
        </div>
        
        <div id="parfume-comparison-overlay" class="parfume-comparison-overlay" style="display: none;"></div>
        
        <!-- Floating comparison button -->
        <div id="parfume-comparison-float" class="parfume-comparison-float" style="display: none;">
            <button type="button" class="comparison-float-btn">
                <span class="comparison-count">0</span>
                <span class="comparison-text"><?php _e('Сравни', 'parfume-catalog'); ?></span>
            </button>
        </div>
        <?php
    }

    /**
     * Проследяване на наскоро разгледани парфюми
     */
    public function track_recently_viewed() {
        if (is_singular('parfumes')) {
            global $post;
            
            // Получаване на текущия списък от cookies
            $recently_viewed = array();
            if (isset($_COOKIE['parfume_recently_viewed'])) {
                $recently_viewed = json_decode(stripslashes($_COOKIE['parfume_recently_viewed']), true);
                if (!is_array($recently_viewed)) {
                    $recently_viewed = array();
                }
            }

            // Добавяне на текущия парфюм (ако не е вече в списъка)
            $current_id = $post->ID;
            $recently_viewed = array_filter($recently_viewed, function($id) use ($current_id) {
                return $id != $current_id;
            });

            // Добавяне в началото на списъка
            array_unshift($recently_viewed, $current_id);

            // Ограничаване до максимум 10 парфюма
            $recently_viewed = array_slice($recently_viewed, 0, 10);

            // Запазване в cookie за 30 дни
            setcookie(
                'parfume_recently_viewed', 
                json_encode($recently_viewed), 
                time() + (30 * 24 * 60 * 60), // 30 дни
                COOKIEPATH, 
                COOKIE_DOMAIN,
                is_ssl(),
                true // HttpOnly
            );
        }
    }

    /**
     * Получаване на наскоро разгледани парфюми
     */
    public static function get_recently_viewed_parfumes($limit = 4, $exclude_current = true) {
        $recently_viewed = array();
        
        if (isset($_COOKIE['parfume_recently_viewed'])) {
            $recently_viewed = json_decode(stripslashes($_COOKIE['parfume_recently_viewed']), true);
            if (!is_array($recently_viewed)) {
                $recently_viewed = array();
            }
        }

        // Изключване на текущия парфюм ако е необходимо
        if ($exclude_current && is_singular('parfumes')) {
            global $post;
            $recently_viewed = array_filter($recently_viewed, function($id) use ($post) {
                return $id != $post->ID;
            });
        }

        // Ограничаване на резултата
        $recently_viewed = array_slice($recently_viewed, 0, $limit);

        // Проверка дали постовете все още съществуват
        $valid_posts = array();
        foreach ($recently_viewed as $post_id) {
            if (get_post_status($post_id) === 'publish') {
                $valid_posts[] = $post_id;
            }
        }

        return $valid_posts;
    }

    /**
     * Получаване на подобни парфюми по нотки
     */
    public static function get_similar_parfumes($post_id, $limit = 4) {
        // Получаване на нотките на текущия парфюм
        $current_notes = wp_get_object_terms($post_id, 'parfume_notes', array('fields' => 'ids'));
        
        if (empty($current_notes)) {
            return array();
        }

        // Търсене на парфюми със сходни нотки
        $similar_query = new WP_Query(array(
            'post_type' => 'parfumes',
            'post_status' => 'publish',
            'posts_per_page' => $limit * 2, // Вземаме повече за филтриране
            'post__not_in' => array($post_id),
            'tax_query' => array(
                array(
                    'taxonomy' => 'parfume_notes',
                    'field' => 'term_id',
                    'terms' => $current_notes,
                    'operator' => 'IN'
                )
            ),
            'meta_query' => array(
                array(
                    'key' => '_thumbnail_id',
                    'compare' => 'EXISTS'
                )
            )
        ));

        $similar_posts = array();
        $similarity_scores = array();

        // Изчисляване на similarity score
        foreach ($similar_query->posts as $similar_post) {
            $similar_notes = wp_get_object_terms($similar_post->ID, 'parfume_notes', array('fields' => 'ids'));
            $common_notes = array_intersect($current_notes, $similar_notes);
            $similarity_score = count($common_notes) / count($current_notes);
            
            $similarity_scores[$similar_post->ID] = $similarity_score;
            $similar_posts[] = $similar_post;
        }

        // Сортиране по similarity score
        usort($similar_posts, function($a, $b) use ($similarity_scores) {
            return $similarity_scores[$b->ID] <=> $similarity_scores[$a->ID];
        });

        wp_reset_postdata();

        return array_slice($similar_posts, 0, $limit);
    }

    /**
     * Получаване на парфюми от същата марка
     */
    public static function get_brand_parfumes($post_id, $limit = 4) {
        // Получаване на марката на текущия парфюм
        $brands = wp_get_object_terms($post_id, 'parfume_marki', array('fields' => 'ids'));
        
        if (empty($brands)) {
            return array();
        }

        $brand_query = new WP_Query(array(
            'post_type' => 'parfumes',
            'post_status' => 'publish',
            'posts_per_page' => $limit,
            'post__not_in' => array($post_id),
            'tax_query' => array(
                array(
                    'taxonomy' => 'parfume_marki',
                    'field' => 'term_id',
                    'terms' => $brands[0]
                )
            ),
            'meta_query' => array(
                array(
                    'key' => '_thumbnail_id',
                    'compare' => 'EXISTS'
                )
            ),
            'orderby' => 'date',
            'order' => 'DESC'
        ));

        $brand_posts = $brand_query->posts;
        wp_reset_postdata();

        return $brand_posts;
    }

    /**
     * Рендериране на progress bar
     */
    public static function render_progress_bar($value, $max_value, $labels = array()) {
        if (empty($value) || $value < 1) {
            return '';
        }

        $percentage = ($value / $max_value) * 100;
        $label = isset($labels[$value - 1]) ? $labels[$value - 1] : $value;

        ob_start();
        ?>
        <div class="parfume-progress-bar" data-value="<?php echo esc_attr($value); ?>" data-max="<?php echo esc_attr($max_value); ?>">
            <div class="progress-bars">
                <?php for ($i = 1; $i <= $max_value; $i++): ?>
                    <span class="progress-bar-item <?php echo ($i <= $value) ? 'active' : ''; ?>"></span>
                <?php endfor; ?>
            </div>
            <span class="progress-label"><?php echo esc_html($label); ?></span>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Рендериране на сезонни икони
     */
    public static function render_season_icons($post_id) {
        $seasons = wp_get_object_terms($post_id, 'parfume_season');
        $suitable_day = get_post_meta($post_id, '_parfume_suitable_day', true);
        $suitable_night = get_post_meta($post_id, '_parfume_suitable_night', true);

        ob_start();
        ?>
        <div class="parfume-season-icons">
            <?php foreach ($seasons as $season): ?>
                <span class="season-icon season-<?php echo esc_attr($season->slug); ?>" title="<?php echo esc_attr($season->name); ?>">
                    <img src="<?php echo PARFUME_CATALOG_PLUGIN_URL; ?>assets/images/season-<?php echo esc_attr($season->slug); ?>.svg" alt="<?php echo esc_attr($season->name); ?>" />
                </span>
            <?php endforeach; ?>
            
            <?php if ($suitable_day): ?>
                <span class="time-icon time-day" title="<?php _e('Подходящ за ден', 'parfume-catalog'); ?>">
                    <img src="<?php echo PARFUME_CATALOG_PLUGIN_URL; ?>assets/images/day-icon.svg" alt="<?php _e('Ден', 'parfume-catalog'); ?>" />
                </span>
            <?php endif; ?>
            
            <?php if ($suitable_night): ?>
                <span class="time-icon time-night" title="<?php _e('Подходящ за нощ', 'parfume-catalog'); ?>">
                    <img src="<?php echo PARFUME_CATALOG_PLUGIN_URL; ?>assets/images/night-icon.svg" alt="<?php _e('Нощ', 'parfume-catalog'); ?>" />
                </span>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Получаване на марката на парфюм
     */
    public static function get_parfume_brand($post_id) {
        $brands = wp_get_object_terms($post_id, 'parfume_marki');
        return !empty($brands) ? $brands[0] : null;
    }

    /**
     * Получаване на вида аромат
     */
    public static function get_parfume_type($post_id) {
        $types = wp_get_object_terms($post_id, 'parfume_vid');
        return !empty($types) ? $types[0] : null;
    }

    /**
     * Проверка дали template съществува в темата
     */
    public static function template_exists_in_theme($template_name) {
        $theme_template = locate_template(array(
            'parfume-catalog/' . $template_name,
            $template_name
        ));
        
        return !empty($theme_template);
    }
}