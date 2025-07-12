<?php
namespace Parfume_Reviews;

class Comparison {
    public function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_add_to_comparison', array($this, 'add_to_comparison'));
        add_action('wp_ajax_remove_from_comparison', array($this, 'remove_from_comparison'));
        add_action('wp_ajax_get_comparison_table', array($this, 'get_comparison_table'));
        add_action('wp_ajax_nopriv_get_comparison_table', array($this, 'get_comparison_table'));
        add_action('wp_ajax_clear_comparison', array($this, 'clear_comparison'));
        add_action('wp_ajax_nopriv_clear_comparison', array($this, 'clear_comparison'));
    }
    
    public function enqueue_scripts() {
        if (is_singular('parfume') || is_post_type_archive('parfume')) {
            wp_enqueue_script(
                'parfume-comparison',
                PARFUME_REVIEWS_PLUGIN_URL . 'assets/js/comparison.js',
                array('jquery'),
                PARFUME_REVIEWS_VERSION,
                true
            );
            
            wp_localize_script('parfume-comparison', 'parfumeComparison', array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('parfume-comparison-nonce'),
                'maxItems' => 4,
                'addedText' => __('Added to comparison', 'parfume-reviews'),
                'addText' => __('Add to comparison', 'parfume-reviews'),
                'removeText' => __('Remove', 'parfume-reviews'),
                'compareText' => __('Compare', 'parfume-reviews'),
                'emptyText' => __('No items to compare', 'parfume-reviews'),
                'alreadyAddedText' => __('Already added to comparison', 'parfume-reviews'),
            ));
        }
    }
    
    public function add_to_comparison() {
        check_ajax_referer('parfume-comparison-nonce', 'nonce');
        
        if (!isset($_POST['post_id'])) {
            wp_send_json_error(__('Invalid post ID', 'parfume-reviews'));
        }
        
        $post_id = intval($_POST['post_id']);
        
        if (!get_post($post_id)) {
            wp_send_json_error(__('Post not found', 'parfume-reviews'));
        }
        
        $comparison = isset($_COOKIE['parfume_comparison']) ? 
            json_decode(stripslashes($_COOKIE['parfume_comparison']), true) : array();
        
        if (!is_array($comparison)) {
            $comparison = array();
        }
        
        if (count($comparison) >= 4) {
            wp_send_json_error(__('Maximum 4 items can be compared', 'parfume-reviews'));
        }
        
        if (!in_array($post_id, $comparison)) {
            $comparison[] = $post_id;
            $expire = time() + 30 * DAY_IN_SECONDS;
            setcookie('parfume_comparison', json_encode($comparison), $expire, COOKIEPATH, COOKIE_DOMAIN);
            
            // Get post title and URL for response
            $post = get_post($post_id);
            $response_data = array(
                'count' => count($comparison),
                'message' => __('Added to comparison', 'parfume-reviews'),
                'title' => $post ? $post->post_title : '',
                'url' => $post ? get_permalink($post_id) : '',
            );
            
            wp_send_json_success($response_data);
        } else {
            wp_send_json_error(__('Already in comparison', 'parfume-reviews'));
        }
    }
    
    public function remove_from_comparison() {
        check_ajax_referer('parfume-comparison-nonce', 'nonce');
        
        if (!isset($_POST['post_id'])) {
            wp_send_json_error(__('Invalid post ID', 'parfume-reviews'));
        }
        
        $post_id = intval($_POST['post_id']);
        $comparison = isset($_COOKIE['parfume_comparison']) ? 
            json_decode(stripslashes($_COOKIE['parfume_comparison']), true) : array();
        
        if (!is_array($comparison)) {
            $comparison = array();
        }
        
        $key = array_search($post_id, $comparison);
        if ($key !== false) {
            unset($comparison[$key]);
            $comparison = array_values($comparison); // Reindex array
            $expire = time() + 30 * DAY_IN_SECONDS;
            setcookie('parfume_comparison', json_encode($comparison), $expire, COOKIEPATH, COOKIE_DOMAIN);
            
            wp_send_json_success(array(
                'count' => count($comparison),
                'message' => __('Removed from comparison', 'parfume-reviews'),
            ));
        } else {
            wp_send_json_error(__('Not found in comparison', 'parfume-reviews'));
        }
    }
    
    public function clear_comparison() {
        check_ajax_referer('parfume-comparison-nonce', 'nonce');
        
        // Clear the cookie
        setcookie('parfume_comparison', '', time() - 3600, COOKIEPATH, COOKIE_DOMAIN);
        
        wp_send_json_success(array(
            'message' => __('Comparison cleared', 'parfume-reviews'),
        ));
    }
    
    public function get_comparison_table() {
        check_ajax_referer('parfume-comparison-nonce', 'nonce');
        
        $comparison = isset($_COOKIE['parfume_comparison']) ? 
            json_decode(stripslashes($_COOKIE['parfume_comparison']), true) : array();
        
        if (!is_array($comparison) || empty($comparison)) {
            wp_send_json_error(__('No items to compare', 'parfume-reviews'));
        }
        
        $args = array(
            'post_type' => 'parfume',
            'post__in' => $comparison,
            'posts_per_page' => -1,
            'orderby' => 'post__in',
        );
        
        $query = new \WP_Query($args);
        
        if ($query->have_posts()) {
            ob_start();
            ?>
            <div class="comparison-header">
                <h2><?php _e('Сравнение на парфюми', 'parfume-reviews'); ?></h2>
            </div>
            
            <div class="comparison-content">
                <table class="comparison-table">
                    <tbody>
                        <!-- Основна информация -->
                        <tr>
                            <th><?php _e('Парфюм', 'parfume-reviews'); ?></th>
                            <?php while ($query->have_posts()): $query->the_post(); ?>
                                <td>
                                    <div class="parfume-info">
                                        <button class="remove-from-comparison" data-post-id="<?php echo get_the_ID(); ?>" title="<?php _e('Премахни', 'parfume-reviews'); ?>">
                                            ×
                                        </button>
                                        
                                        <?php if (has_post_thumbnail()): ?>
                                            <img src="<?php echo get_the_post_thumbnail_url(get_the_ID(), 'medium'); ?>" 
                                                 alt="<?php the_title(); ?>" 
                                                 class="parfume-image">
                                        <?php else: ?>
                                            <div class="parfume-image placeholder-image">
                                                <span>📸</span>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <h4 class="parfume-title">
                                            <a href="<?php the_permalink(); ?>" target="_blank">
                                                <?php the_title(); ?>
                                            </a>
                                        </h4>
                                        
                                        <?php 
                                        $brands = wp_get_post_terms(get_the_ID(), 'marki');
                                        if (!empty($brands)): 
                                        ?>
                                            <div class="parfume-brand">
                                                <?php echo esc_html($brands[0]->name); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            <?php endwhile; ?>
                        </tr>
                        
                        <!-- Рейтинг -->
                        <tr>
                            <th><?php _e('Рейтинг', 'parfume-reviews'); ?></th>
                            <?php 
                            wp_reset_postdata();
                            while ($query->have_posts()): $query->the_post(); 
                                $rating = get_post_meta(get_the_ID(), '_parfume_rating', true);
                            ?>
                                <td>
                                    <div class="rating-display">
                                        <?php if (!empty($rating)): ?>
                                            <div class="rating-stars">
                                                <?php echo parfume_reviews_get_rating_stars($rating); ?>
                                            </div>
                                            <span class="rating-number"><?php echo number_format(floatval($rating), 1); ?>/5</span>
                                        <?php else: ?>
                                            <span class="no-rating">—</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            <?php endwhile; ?>
                        </tr>
                        
                        <!-- Цена -->
                        <tr>
                            <th><?php _e('Най-добра цена', 'parfume-reviews'); ?></th>
                            <?php 
                            wp_reset_postdata();
                            while ($query->have_posts()): $query->the_post(); 
                                $lowest_price = function_exists('parfume_reviews_get_lowest_price') ? 
                                    parfume_reviews_get_lowest_price(get_the_ID()) : false;
                            ?>
                                <td>
                                    <?php if ($lowest_price): ?>
                                        <div class="price-display">
                                            <?php echo esc_html($lowest_price['price']); ?>
                                        </div>
                                        <div class="store-name">
                                            <?php echo esc_html($lowest_price['store']); ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="metadata-value">
                                            <?php _e('Няма данни за цена', 'parfume-reviews'); ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                            <?php endwhile; ?>
                        </tr>
                        
                        <!-- Пол -->
                        <tr>
                            <th><?php _e('Пол', 'parfume-reviews'); ?></th>
                            <?php 
                            wp_reset_postdata();
                            while ($query->have_posts()): $query->the_post(); 
                                $genders = wp_get_post_terms(get_the_ID(), 'gender', array('fields' => 'names'));
                            ?>
                                <td>
                                    <div class="metadata-value">
                                        <?php echo !empty($genders) ? esc_html(implode(', ', $genders)) : '—'; ?>
                                    </div>
                                </td>
                            <?php endwhile; ?>
                        </tr>
                        
                        <!-- Година на издаване -->
                        <tr>
                            <th><?php _e('Година', 'parfume-reviews'); ?></th>
                            <?php 
                            wp_reset_postdata();
                            while ($query->have_posts()): $query->the_post(); 
                                $release_year = get_post_meta(get_the_ID(), '_parfume_release_year', true);
                            ?>
                                <td>
                                    <div class="metadata-value">
                                        <?php echo !empty($release_year) ? esc_html($release_year) : '—'; ?>
                                    </div>
                                </td>
                            <?php endwhile; ?>
                        </tr>
                        
                        <!-- Тип аромат -->
                        <tr>
                            <th><?php _e('Тип аромат', 'parfume-reviews'); ?></th>
                            <?php 
                            wp_reset_postdata();
                            while ($query->have_posts()): $query->the_post(); 
                                $aroma_types = wp_get_post_terms(get_the_ID(), 'aroma_type', array('fields' => 'names'));
                            ?>
                                <td>
                                    <div class="metadata-value">
                                        <?php echo !empty($aroma_types) ? esc_html(implode(', ', $aroma_types)) : '—'; ?>
                                    </div>
                                </td>
                            <?php endwhile; ?>
                        </tr>
                        
                        <!-- Сезон -->
                        <tr>
                            <th><?php _e('Сезон', 'parfume-reviews'); ?></th>
                            <?php 
                            wp_reset_postdata();
                            while ($query->have_posts()): $query->the_post(); 
                                $seasons = wp_get_post_terms(get_the_ID(), 'season', array('fields' => 'names'));
                            ?>
                                <td>
                                    <div class="metadata-value">
                                        <?php echo !empty($seasons) ? esc_html(implode(', ', $seasons)) : '—'; ?>
                                    </div>
                                </td>
                            <?php endwhile; ?>
                        </tr>
                        
                        <!-- Интензивност -->
                        <tr>
                            <th><?php _e('Интензивност', 'parfume-reviews'); ?></th>
                            <?php 
                            wp_reset_postdata();
                            while ($query->have_posts()): $query->the_post(); 
                                $intensity = wp_get_post_terms(get_the_ID(), 'intensity', array('fields' => 'names'));
                            ?>
                                <td>
                                    <div class="metadata-value">
                                        <?php echo !empty($intensity) ? esc_html(implode(', ', $intensity)) : '—'; ?>
                                    </div>
                                </td>
                            <?php endwhile; ?>
                        </tr>
                        
                        <!-- Трайност -->
                        <tr>
                            <th><?php _e('Трайност', 'parfume-reviews'); ?></th>
                            <?php 
                            wp_reset_postdata();
                            while ($query->have_posts()): $query->the_post(); 
                                $longevity = get_post_meta(get_the_ID(), '_parfume_longevity', true);
                            ?>
                                <td>
                                    <div class="metadata-value">
                                        <?php echo !empty($longevity) ? esc_html($longevity) : '—'; ?>
                                    </div>
                                </td>
                            <?php endwhile; ?>
                        </tr>
                        
                        <!-- Силаж -->
                        <tr>
                            <th><?php _e('Силаж', 'parfume-reviews'); ?></th>
                            <?php 
                            wp_reset_postdata();
                            while ($query->have_posts()): $query->the_post(); 
                                $sillage = get_post_meta(get_the_ID(), '_parfume_sillage', true);
                            ?>
                                <td>
                                    <div class="metadata-value">
                                        <?php echo !empty($sillage) ? esc_html($sillage) : '—'; ?>
                                    </div>
                                </td>
                            <?php endwhile; ?>
                        </tr>
                        
                        <!-- Топ ноти -->
                        <tr>
                            <th><?php _e('Топ ноти', 'parfume-reviews'); ?></th>
                            <?php 
                            wp_reset_postdata();
                            while ($query->have_posts()): $query->the_post(); 
                                $notes = wp_get_post_terms(get_the_ID(), 'notes', array('fields' => 'names'));
                                $top_notes = array_slice($notes, 0, 3); // Първите 3 ноти
                            ?>
                                <td>
                                    <div class="metadata-value">
                                        <?php echo !empty($top_notes) ? esc_html(implode(', ', $top_notes)) : '—'; ?>
                                    </div>
                                </td>
                            <?php endwhile; ?>
                        </tr>
                        
                        <!-- Парфюмьор -->
                        <tr>
                            <th><?php _e('Парфюмьор', 'parfume-reviews'); ?></th>
                            <?php 
                            wp_reset_postdata();
                            while ($query->have_posts()): $query->the_post(); 
                                $perfumers = wp_get_post_terms(get_the_ID(), 'perfumer', array('fields' => 'names'));
                            ?>
                                <td>
                                    <div class="metadata-value">
                                        <?php echo !empty($perfumers) ? esc_html(implode(', ', $perfumers)) : '—'; ?>
                                    </div>
                                </td>
                            <?php endwhile; ?>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <div class="comparison-actions">
                <button id="clear-comparison" class="button">
                    <span class="button-icon">🗑️</span>
                    <?php _e('Изчисти сравнението', 'parfume-reviews'); ?>
                </button>
                
                <button id="print-comparison" class="button" onclick="window.print()">
                    <span class="button-icon">🖨️</span>
                    <?php _e('Принтирай', 'parfume-reviews'); ?>
                </button>
            </div>
            <?php
            $html = ob_get_clean();
            wp_send_json_success(array('html' => $html));
        } else {
            wp_send_json_error(__('Няма парфюми за сравнение', 'parfume-reviews'));
        }
        
        wp_reset_postdata();
    }
    
    public static function get_comparison_button($post_id) {
        ob_start();
        ?>
        <button class="add-to-comparison" data-post-id="<?php echo esc_attr($post_id); ?>">
            <span class="button-icon">⚖</span>
            <?php _e('Add to comparison', 'parfume-reviews'); ?>
        </button>
        <?php
        return ob_get_clean();
    }
    
    public static function get_comparison_link() {
        ob_start();
        ?>
        <a href="#" id="show-comparison" class="comparison-link">
            <span class="link-icon">⚖</span>
            <?php _e('Comparison', 'parfume-reviews'); ?>
            <span class="comparison-count">0</span>
        </a>
        <?php
        return ob_get_clean();
    }
    
    public static function get_comparison_widget() {
        ob_start();
        ?>
        <div class="comparison-widget" style="display: none;">
            <span class="widget-icon">⚖️</span>
            <span class="widget-text"><?php _e('Сравнение', 'parfume-reviews'); ?></span>
            <span class="widget-count">0</span>
            <button class="widget-button"><?php _e('Покажи', 'parfume-reviews'); ?></button>
        </div>
        <?php
        return ob_get_clean();
    }
}