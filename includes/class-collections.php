<?php
namespace Parfume_Reviews;

class Collections {
    public function __construct() {
        add_action('init', array($this, 'register_post_type'));
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post', array($this, 'save_meta_boxes'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_add_to_collection', array($this, 'add_to_collection'));
        add_action('wp_ajax_remove_from_collection', array($this, 'remove_from_collection'));
        add_action('wp_ajax_create_collection', array($this, 'create_collection'));
        add_action('wp_ajax_delete_collection', array($this, 'delete_collection'));
        add_action('wp_ajax_get_user_collections', array($this, 'get_user_collections'));
        add_shortcode('parfume_collections', array($this, 'collections_shortcode'));
        add_filter('template_include', array($this, 'template_loader'));
    }
    
    public function register_post_type() {
        $labels = array(
            'name' => __('Collections', 'parfume-reviews'),
            'singular_name' => __('Collection', 'parfume-reviews'),
            'menu_name' => __('Collections', 'parfume-reviews'),
            'name_admin_bar' => __('Collection', 'parfume-reviews'),
            'add_new' => __('Add New', 'parfume-reviews'),
            'add_new_item' => __('Add New Collection', 'parfume-reviews'),
            'new_item' => __('New Collection', 'parfume-reviews'),
            'edit_item' => __('Edit Collection', 'parfume-reviews'),
            'view_item' => __('View Collection', 'parfume-reviews'),
            'all_items' => __('All Collections', 'parfume-reviews'),
            'search_items' => __('Search Collections', 'parfume-reviews'),
            'parent_item_colon' => __('Parent Collections:', 'parfume-reviews'),
            'not_found' => __('No collections found.', 'parfume-reviews'),
            'not_found_in_trash' => __('No collections found in Trash.', 'parfume-reviews')
        );
        
        $args = array(
            'labels' => $labels,
            'public' => true,
            'publicly_queryable' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'query_var' => true,
            'rewrite' => array('slug' => 'collections'),
            'capability_type' => 'post',
            'has_archive' => true,
            'hierarchical' => false,
            'menu_position' => 6,
            'supports' => array('title', 'editor', 'thumbnail', 'author', 'custom-fields'),
            'show_in_rest' => true,
            'menu_icon' => 'dashicons-portfolio',
        );
        
        register_post_type('parfume_collection', $args);
    }
    
    public function template_loader($template) {
        if (is_singular('parfume_collection')) {
            $plugin_template = PARFUME_REVIEWS_PLUGIN_DIR . 'templates/single-collection.php';
            if (file_exists($plugin_template)) {
                return $plugin_template;
            }
        } elseif (is_post_type_archive('parfume_collection')) {
            $plugin_template = PARFUME_REVIEWS_PLUGIN_DIR . 'templates/archive-collection.php';
            if (file_exists($plugin_template)) {
                return $plugin_template;
            }
        }
        
        return $template;
    }
    
    public function add_meta_boxes() {
        add_meta_box(
            'collection_parfumes',
            __('Parfumes in Collection', 'parfume-reviews'),
            array($this, 'render_parfumes_meta_box'),
            'parfume_collection',
            'normal',
            'high'
        );
        
        add_meta_box(
            'collection_privacy',
            __('Privacy Settings', 'parfume-reviews'),
            array($this, 'render_privacy_meta_box'),
            'parfume_collection',
            'side',
            'default'
        );
    }
    
    public function render_parfumes_meta_box($post) {
        wp_nonce_field('collection_parfumes_nonce', 'collection_parfumes_nonce');
        
        $parfumes = get_post_meta($post->ID, '_collection_parfumes', true);
        $parfumes = !empty($parfumes) ? (array)$parfumes : array();
        
        // Get all available parfumes
        $all_parfumes = get_posts(array(
            'post_type' => 'parfume',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'orderby' => 'title',
            'order' => 'ASC'
        ));
        
        echo '<div class="collection-parfumes-meta">';
        echo '<p>' . __('Select parfumes to include in this collection:', 'parfume-reviews') . '</p>';
        
        if (!empty($all_parfumes)) {
            echo '<div class="parfume-checklist" style="max-height: 300px; overflow-y: auto; border: 1px solid #ddd; padding: 10px;">';
            foreach ($all_parfumes as $parfume) {
                $checked = in_array($parfume->ID, $parfumes) ? 'checked="checked"' : '';
                echo '<label style="display: block; margin-bottom: 5px;">';
                echo '<input type="checkbox" name="collection_parfumes[]" value="' . esc_attr($parfume->ID) . '" ' . $checked . '> ';
                echo esc_html($parfume->post_title);
                echo '</label>';
            }
            echo '</div>';
        } else {
            echo '<p>' . __('No parfumes found. Please create some parfumes first.', 'parfume-reviews') . '</p>';
        }
        
        echo '</div>';
    }
    
    public function render_privacy_meta_box($post) {
        wp_nonce_field('collection_privacy_nonce', 'collection_privacy_nonce');
        
        $privacy = get_post_meta($post->ID, '_collection_privacy', true);
        $privacy = !empty($privacy) ? $privacy : 'public';
        
        echo '<div class="collection-privacy-meta">';
        echo '<p>' . __('Choose the privacy level for this collection:', 'parfume-reviews') . '</p>';
        
        echo '<label><input type="radio" name="collection_privacy" value="public" ' . checked($privacy, 'public', false) . '> ';
        echo __('Public', 'parfume-reviews') . '</label><br>';
        
        echo '<label><input type="radio" name="collection_privacy" value="private" ' . checked($privacy, 'private', false) . '> ';
        echo __('Private', 'parfume-reviews') . '</label>';
        
        echo '</div>';
    }
    
    public function save_meta_boxes($post_id) {
        // Check if this is an autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        // Check the post type
        if (get_post_type($post_id) !== 'parfume_collection') {
            return;
        }
        
        // Check user permissions
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Save parfumes
        if (isset($_POST['collection_parfumes_nonce']) && wp_verify_nonce($_POST['collection_parfumes_nonce'], 'collection_parfumes_nonce')) {
            if (isset($_POST['collection_parfumes']) && is_array($_POST['collection_parfumes'])) {
                $parfumes = array_map('intval', $_POST['collection_parfumes']);
                update_post_meta($post_id, '_collection_parfumes', $parfumes);
            } else {
                delete_post_meta($post_id, '_collection_parfumes');
            }
        }
        
        // Save privacy
        if (isset($_POST['collection_privacy_nonce']) && wp_verify_nonce($_POST['collection_privacy_nonce'], 'collection_privacy_nonce')) {
            if (isset($_POST['collection_privacy']) && in_array($_POST['collection_privacy'], array('public', 'private'))) {
                update_post_meta($post_id, '_collection_privacy', sanitize_text_field($_POST['collection_privacy']));
            }
        }
    }
    
    public function enqueue_scripts() {
        if (is_singular('parfume')) {
            wp_enqueue_script(
                'parfume-collections',
                PARFUME_REVIEWS_PLUGIN_URL . 'assets/js/collections.js',
                array('jquery'),
                PARFUME_REVIEWS_VERSION,
                true
            );
            
            wp_localize_script('parfume-collections', 'parfumeCollections', array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('parfume-collections-nonce'),
                'mustBeLoggedIn' => __('You must be logged in to manage collections', 'parfume-reviews'),
                'collectionNameRequired' => __('Collection name is required', 'parfume-reviews'),
                'confirmDelete' => __('Are you sure you want to delete this collection?', 'parfume-reviews'),
            ));
        }
    }
    
    public function add_to_collection() {
        check_ajax_referer('parfume-collections-nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(__('You must be logged in to add to collections', 'parfume-reviews'));
        }
        
        if (!isset($_POST['post_id'], $_POST['collection_id'])) {
            wp_send_json_error(__('Invalid data', 'parfume-reviews'));
        }
        
        $post_id = intval($_POST['post_id']);
        $collection_id = intval($_POST['collection_id']);
        
        // Validate post
        if (!get_post($post_id) || get_post_type($post_id) !== 'parfume') {
            wp_send_json_error(__('Invalid parfume', 'parfume-reviews'));
        }
        
        // Check if user owns the collection
        $collection = get_post($collection_id);
        if (!$collection || $collection->post_type !== 'parfume_collection' || intval($collection->post_author) !== get_current_user_id()) {
            wp_send_json_error(__('Invalid collection', 'parfume-reviews'));
        }
        
        $parfumes = get_post_meta($collection_id, '_collection_parfumes', true);
        $parfumes = !empty($parfumes) ? (array)$parfumes : array();
        
        if (!in_array($post_id, $parfumes)) {
            $parfumes[] = $post_id;
            update_post_meta($collection_id, '_collection_parfumes', $parfumes);
            
            wp_send_json_success(__('Added to collection', 'parfume-reviews'));
        } else {
            wp_send_json_error(__('Already in collection', 'parfume-reviews'));
        }
    }
    
    public function remove_from_collection() {
        check_ajax_referer('parfume-collections-nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(__('You must be logged in to remove from collections', 'parfume-reviews'));
        }
        
        if (!isset($_POST['post_id'], $_POST['collection_id'])) {
            wp_send_json_error(__('Invalid data', 'parfume-reviews'));
        }
        
        $post_id = intval($_POST['post_id']);
        $collection_id = intval($_POST['collection_id']);
        
        // Check if user owns the collection
        $collection = get_post($collection_id);
        if (!$collection || $collection->post_type !== 'parfume_collection' || intval($collection->post_author) !== get_current_user_id()) {
            wp_send_json_error(__('Invalid collection', 'parfume-reviews'));
        }
        
        $parfumes = get_post_meta($collection_id, '_collection_parfumes', true);
        $parfumes = !empty($parfumes) ? (array)$parfumes : array();
        
        $key = array_search($post_id, $parfumes);
        if ($key !== false) {
            unset($parfumes[$key]);
            $parfumes = array_values($parfumes); // Reindex array
            update_post_meta($collection_id, '_collection_parfumes', $parfumes);
            
            wp_send_json_success(__('Removed from collection', 'parfume-reviews'));
        } else {
            wp_send_json_error(__('Not found in collection', 'parfume-reviews'));
        }
    }
    
    public function create_collection() {
        check_ajax_referer('parfume-collections-nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(__('You must be logged in to create collections', 'parfume-reviews'));
        }
        
        if (!isset($_POST['name']) || empty(trim($_POST['name']))) {
            wp_send_json_error(__('Collection name is required', 'parfume-reviews'));
        }
        
        $name = sanitize_text_field(trim($_POST['name']));
        $privacy = isset($_POST['privacy']) && in_array($_POST['privacy'], array('public', 'private')) ? sanitize_text_field($_POST['privacy']) : 'public';
        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
        
        $collection_id = wp_insert_post(array(
            'post_title' => $name,
            'post_type' => 'parfume_collection',
            'post_status' => 'publish',
            'post_author' => get_current_user_id(),
        ));
        
        if (is_wp_error($collection_id)) {
            wp_send_json_error($collection_id->get_error_message());
        }
        
        update_post_meta($collection_id, '_collection_privacy', $privacy);
        
        if ($post_id && get_post($post_id) && get_post_type($post_id) === 'parfume') {
            update_post_meta($collection_id, '_collection_parfumes', array($post_id));
        }
        
        wp_send_json_success(array(
            'id' => $collection_id,
            'name' => $name,
            'message' => __('Collection created', 'parfume-reviews'),
        ));
    }
    
    public function delete_collection() {
        check_ajax_referer('parfume-collections-nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(__('You must be logged in to delete collections', 'parfume-reviews'));
        }
        
        if (!isset($_POST['collection_id'])) {
            wp_send_json_error(__('Invalid collection', 'parfume-reviews'));
        }
        
        $collection_id = intval($_POST['collection_id']);
        $collection = get_post($collection_id);
        
        if (!$collection || $collection->post_type !== 'parfume_collection' || intval($collection->post_author) !== get_current_user_id()) {
            wp_send_json_error(__('Invalid collection', 'parfume-reviews'));
        }
        
        $result = wp_delete_post($collection_id, true);
        
        if (!$result) {
            wp_send_json_error(__('Could not delete collection', 'parfume-reviews'));
        }
        
        wp_send_json_success(__('Collection deleted', 'parfume-reviews'));
    }
    
    public function get_user_collections() {
        check_ajax_referer('parfume-collections-nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(__('You must be logged in to view collections', 'parfume-reviews'));
        }
        
        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
        $user_id = get_current_user_id();
        
        $args = array(
            'post_type' => 'parfume_collection',
            'author' => $user_id,
            'posts_per_page' => -1,
            'fields' => 'ids',
        );
        
        $collections = get_posts($args);
        $data = array();
        
        foreach ($collections as $collection_id) {
            $parfumes = get_post_meta($collection_id, '_collection_parfumes', true);
            $parfumes = !empty($parfumes) ? (array)$parfumes : array();
            
            $data[] = array(
                'id' => $collection_id,
                'name' => get_the_title($collection_id),
                'has_parfume' => in_array($post_id, $parfumes),
            );
        }
        
        wp_send_json_success($data);
    }
    
    public function collections_shortcode($atts) {
        if (!is_user_logged_in()) {
            return '<p>' . __('You must be logged in to view your collections.', 'parfume-reviews') . '</p>';
        }
        
        $atts = shortcode_atts(array(
            'user_id' => get_current_user_id(),
        ), $atts);
        
        $user_id = intval($atts['user_id']);
        
        // Security check - users can only view their own collections unless admin
        if ($user_id !== get_current_user_id() && !current_user_can('manage_options')) {
            return '<p>' . __('Access denied.', 'parfume-reviews') . '</p>';
        }
        
        $args = array(
            'post_type' => 'parfume_collection',
            'author' => $user_id,
            'posts_per_page' => -1,
        );
        
        $collections = new \WP_Query($args);
        
        ob_start();
        
        if ($collections->have_posts()):
            ?>
            <div class="parfume-collections">
                <div class="collections-grid">
                    <?php while ($collections->have_posts()): $collections->the_post(); 
                        $parfumes = get_post_meta(get_the_ID(), '_collection_parfumes', true);
                        $parfumes = !empty($parfumes) ? (array)$parfumes : array();
                        $privacy = get_post_meta(get_the_ID(), '_collection_privacy', true);
                        $privacy = !empty($privacy) ? $privacy : 'public';
                    ?>
                        <div class="collection-item">
                            <a href="<?php the_permalink(); ?>">
                                <?php if (has_post_thumbnail()): ?>
                                    <div class="collection-thumbnail">
                                        <?php the_post_thumbnail('medium'); ?>
                                    </div>
                                <?php endif; ?>
                                <h3><?php the_title(); ?></h3>
                                <div class="collection-meta">
                                    <span class="count"><?php echo count($parfumes); ?> <?php _e('items', 'parfume-reviews'); ?></span>
                                    <span class="privacy privacy-<?php echo esc_attr($privacy); ?>"><?php echo ucfirst(__($privacy, 'parfume-reviews')); ?></span>
                                </div>
                                <?php if (get_the_excerpt()): ?>
                                    <div class="collection-excerpt">
                                        <?php the_excerpt(); ?>
                                    </div>
                                <?php endif; ?>
                            </a>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
            <?php
        else:
            ?>
            <p><?php _e('No collections found.', 'parfume-reviews'); ?></p>
            <?php
        endif;
        
        wp_reset_postdata();
        
        return ob_get_clean();
    }
    
    public static function get_collections_dropdown($post_id) {
        if (!is_user_logged_in()) {
            return '';
        }
        
        ob_start();
        ?>
        <div class="parfume-collections-dropdown">
            <button class="collections-toggle">
                <?php _e('Add to Collection', 'parfume-reviews'); ?>
                <span class="dashicons dashicons-arrow-down"></span>
            </button>
            
            <div class="collections-dropdown-content">
                <div class="collections-list"></div>
                
                <div class="create-collection-form">
                    <input type="text" class="new-collection-name" placeholder="<?php esc_attr_e('New collection name', 'parfume-reviews'); ?>" required>
                    <select class="new-collection-privacy">
                        <option value="public"><?php _e('Public', 'parfume-reviews'); ?></option>
                        <option value="private"><?php _e('Private', 'parfume-reviews'); ?></option>
                    </select>
                    <button class="create-collection" data-post-id="<?php echo esc_attr($post_id); ?>">
                        <?php _e('Create', 'parfume-reviews'); ?>
                    </button>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}