<?php
namespace Parfume_Reviews;

class Comparison {
    public function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_add_to_comparison', array($this, 'add_to_comparison'));
        add_action('wp_ajax_remove_from_comparison', array($this, 'remove_from_comparison'));
        add_action('wp_ajax_get_comparison_table', array($this, 'get_comparison_table'));
        add_action('wp_ajax_nopriv_get_comparison_table', array($this, 'get_comparison_table'));
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
        
        $comparison = isset($_COOKIE['parfume_comparison']) ? json_decode(stripslashes($_COOKIE['parfume_comparison']), true) : array();
        
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
            
            wp_send_json_success(array(
                'count' => count($comparison),
                'message' => __('Added to comparison', 'parfume-reviews'),
            ));
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
        $comparison = isset($_COOKIE['parfume_comparison']) ? json_decode(stripslashes($_COOKIE['parfume_comparison']), true) : array();
        
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
    
    public function get_comparison_table() {
        check_ajax_referer('parfume-comparison-nonce', 'nonce');
        
        $comparison = isset($_COOKIE['parfume_comparison']) ? json_decode(stripslashes($_COOKIE['parfume_comparison']), true) : array();
        
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
        $data = array();
        
        if ($query->have_posts()) {
            ob_start();
            ?>
            <div class="parfume-comparison-table">
                <table>
                    <thead>
                        <tr>
                            <th><?php _e('Feature', 'parfume-reviews'); ?></th>
                            <?php while ($query->have_posts()): $query->the_post(); ?>
                                <th>
                                    <a href="<?php the_permalink(); ?>">
                                        <?php the_title(); ?>
                                    </a>
                                    <button class="remove-from-comparison" data-post-id="<?php the_ID(); ?>">
                                        <?php _e('Remove', 'parfume-reviews'); ?>
                                    </button>
                                </th>
                            <?php endwhile; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><?php _e('Image', 'parfume-reviews'); ?></td>
                            <?php 
                            wp_reset_postdata();
                            while ($query->have_posts()): $query->the_post(); 
                            ?>
                                <td>
                                    <a href="<?php the_permalink(); ?>">
                                        <?php if (has_post_thumbnail()): ?>
                                            <?php the_post_thumbnail('thumbnail'); ?>
                                        <?php endif; ?>
                                    </a>
                                </td>
                            <?php endwhile; ?>
                        </tr>
                        
                        <tr>
                            <td><?php _e('Rating', 'parfume-reviews'); ?></td>
                            <?php 
                            wp_reset_postdata();
                            while ($query->have_posts()): $query->the_post(); 
                                $rating = get_post_meta(get_the_ID(), '_parfume_rating', true);
                            ?>
                                <td>
                                    <?php if (!empty($rating)): ?>
                                        <div class="rating-stars">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <span class="star <?php echo $i <= round($rating) ? 'filled' : ''; ?>">★</span>
                                            <?php endfor; ?>
                                            <span class="rating-number"><?php echo number_format($rating, 1); ?></span>
                                        </div>
                                    <?php endif; ?>
                                </td>
                            <?php endwhile; ?>
                        </tr>
                        
                        <tr>
                            <td><?php _e('Gender', 'parfume-reviews'); ?></td>
                            <?php 
                            wp_reset_postdata();
                            while ($query->have_posts()): $query->the_post(); 
                                $gender = get_post_meta(get_the_ID(), '_parfume_gender', true);
                            ?>
                                <td><?php echo esc_html($gender); ?></td>
                            <?php endwhile; ?>
                        </tr>
                        
                        <tr>
                            <td><?php _e('Release Year', 'parfume-reviews'); ?></td>
                            <?php 
                            wp_reset_postdata();
                            while ($query->have_posts()): $query->the_post(); 
                                $release_year = get_post_meta(get_the_ID(), '_parfume_release_year', true);
                            ?>
                                <td><?php echo esc_html($release_year); ?></td>
                            <?php endwhile; ?>
                        </tr>
                        
                        <tr>
                            <td><?php _e('Longevity', 'parfume-reviews'); ?></td>
                            <?php 
                            wp_reset_postdata();
                            while ($query->have_posts()): $query->the_post(); 
                                $longevity = get_post_meta(get_the_ID(), '_parfume_longevity', true);
                            ?>
                                <td><?php echo esc_html($longevity); ?></td>
                            <?php endwhile; ?>
                        </tr>
                        
                        <tr>
                            <td><?php _e('Sillage', 'parfume-reviews'); ?></td>
                            <?php 
                            wp_reset_postdata();
                            while ($query->have_posts()): $query->the_post(); 
                                $sillage = get_post_meta(get_the_ID(), '_parfume_sillage', true);
                            ?>
                                <td><?php echo esc_html($sillage); ?></td>
                            <?php endwhile; ?>
                        </tr>
                        
                        <tr>
                            <td><?php _e('Brand', 'parfume-reviews'); ?></td>
                            <?php 
                            wp_reset_postdata();
                            while ($query->have_posts()): $query->the_post(); 
                                $brands = wp_get_post_terms(get_the_ID(), 'marki', array('fields' => 'names'));
                            ?>
                                <td><?php echo !empty($brands) ? esc_html(implode(', ', $brands)) : ''; ?></td>
                            <?php endwhile; ?>
                        </tr>
                        
                        <tr>
                            <td><?php _e('Aroma Type', 'parfume-reviews'); ?></td>
                            <?php 
                            wp_reset_postdata();
                            while ($query->have_posts()): $query->the_post(); 
                                $aroma_types = wp_get_post_terms(get_the_ID(), 'aroma_type', array('fields' => 'names'));
                            ?>
                                <td><?php echo !empty($aroma_types) ? esc_html(implode(', ', $aroma_types)) : ''; ?></td>
                            <?php endwhile; ?>
                        </tr>
                        
                        <tr>
                            <td><?php _e('Top Notes', 'parfume-reviews'); ?></td>
                            <?php 
                            wp_reset_postdata();
                            while ($query->have_posts()): $query->the_post(); 
                                $notes = wp_get_post_terms(get_the_ID(), 'notes', array('fields' => 'names'));
                            ?>
                                <td><?php echo !empty($notes) ? esc_html(implode(', ', array_slice($notes, 0, 3))) : ''; ?></td>
                            <?php endwhile; ?>
                        </tr>
                    </tbody>
                </table>
                
                <div class="comparison-actions">
                    <button id="clear-comparison" class="button">
                        <?php _e('Clear Comparison', 'parfume-reviews'); ?>
                    </button>
                </div>
            </div>
            <?php
            $html = ob_get_clean();
            wp_send_json_success(array('html' => $html));
        } else {
            wp_send_json_error(__('No items to compare', 'parfume-reviews'));
        }
    }
    
    public static function get_comparison_button($post_id) {
        ob_start();
        ?>
        <button class="add-to-comparison" data-post-id="<?php echo esc_attr($post_id); ?>">
            <?php _e('Add to comparison', 'parfume-reviews'); ?>
        </button>
        <?php
        return ob_get_clean();
    }
    
    public static function get_comparison_link() {
        ob_start();
        ?>
        <a href="#" id="show-comparison" class="comparison-link">
            <?php _e('Comparison', 'parfume-reviews'); ?>
            <span class="comparison-count">0</span>
        </a>
        <?php
        return ob_get_clean();
    }
}