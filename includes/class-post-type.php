<?php
namespace Parfume_Reviews;

class Post_Type {
    public function __construct() {
        add_action('init', array($this, 'register_post_type'), 0);
        add_action('init', array($this, 'register_blog_post_type'), 0); 
        add_action('init', array($this, 'add_custom_rewrite_rules'), 10);
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post', array($this, 'save_meta_boxes'));
        add_filter('template_include', array($this, 'template_loader'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
        add_action('pre_get_posts', array($this, 'modify_archive_query'));
        add_filter('posts_where', array($this, 'filter_posts_where'), 10, 2);
        
        // Add shortcodes for archive pages
        add_shortcode('all_brands_archive', array($this, 'all_brands_archive_shortcode'));
        add_shortcode('all_notes_archive', array($this, 'all_notes_archive_shortcode'));
        add_shortcode('all_perfumers_archive', array($this, 'all_perfumers_archive_shortcode'));
        
        // AJAX handlers for price updates
        add_action('wp_ajax_update_store_price', array($this, 'ajax_update_store_price'));
        add_action('wp_ajax_get_store_sizes', array($this, 'ajax_get_store_sizes'));
        
        // Debug hooks
        add_action('wp', array($this, 'debug_current_request'));
    }
    
    public function register_post_type() {
        $settings = get_option('parfume_reviews_settings', array());
        $slug = !empty($settings['parfume_slug']) ? $settings['parfume_slug'] : 'parfiumi';
        
        $labels = array(
            'name' => __('Parfumes', 'parfume-reviews'),
            'singular_name' => __('Parfume', 'parfume-reviews'),
            'menu_name' => __('Parfumes', 'parfume-reviews'),
            'name_admin_bar' => __('Parfume', 'parfume-reviews'),
            'add_new' => __('Add New', 'parfume-reviews'),
            'add_new_item' => __('Add New Parfume', 'parfume-reviews'),
            'new_item' => __('New Parfume', 'parfume-reviews'),
            'edit_item' => __('Edit Parfume', 'parfume-reviews'),
            'view_item' => __('View Parfume', 'parfume-reviews'),
            'all_items' => __('All Parfumes', 'parfume-reviews'),
            'search_items' => __('Search Parfumes', 'parfume-reviews'),
            'parent_item_colon' => __('Parent Parfumes:', 'parfume-reviews'),
            'not_found' => __('No parfumes found.', 'parfume-reviews'),
            'not_found_in_trash' => __('No parfumes found in Trash.', 'parfume-reviews'),
        );
        
        $args = array(
            'labels' => $labels,
            'public' => true,
            'publicly_queryable' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'query_var' => true,
            'rewrite' => array(
                'slug' => $slug,
                'with_front' => false,
                'feeds' => true,
                'pages' => true
            ),
            'capability_type' => 'post',
            'has_archive' => true,
            'hierarchical' => false,
            'menu_position' => 20,
            'menu_icon' => 'dashicons-store',
            'supports' => array('title', 'editor', 'thumbnail', 'excerpt', 'custom-fields', 'comments'),
            'show_in_rest' => true,
            'rest_base' => 'parfumes',
        );
        
        register_post_type('parfume', $args);
    }
    
    /**
     * Register blog post type - ПОПРАВЕНА ВЕРСИЯ
     */
    public function register_blog_post_type() {
        $settings = get_option('parfume_reviews_settings', array());
        $parfume_slug = !empty($settings['parfume_slug']) ? $settings['parfume_slug'] : 'parfiumi';
        
        $labels = array(
            'name' => __('Blog Posts', 'parfume-reviews'),
            'singular_name' => __('Blog Post', 'parfume-reviews'),
            'menu_name' => __('Blog', 'parfume-reviews'),
            'add_new' => __('Add New Post', 'parfume-reviews'),
            'add_new_item' => __('Add New Blog Post', 'parfume-reviews'),
            'edit_item' => __('Edit Blog Post', 'parfume-reviews'),
            'new_item' => __('New Blog Post', 'parfume-reviews'),
            'view_item' => __('View Blog Post', 'parfume-reviews'),
            'view_items' => __('View Blog Posts', 'parfume-reviews'),
            'search_items' => __('Search Blog Posts', 'parfume-reviews'),
            'not_found' => __('No blog posts found.', 'parfume-reviews'),
            'not_found_in_trash' => __('No blog posts found in Trash.', 'parfume-reviews'),
            'all_items' => __('All Blog Posts', 'parfume-reviews'),
            'archives' => __('Blog Archives', 'parfume-reviews'),
            'attributes' => __('Blog Attributes', 'parfume-reviews'),
            'insert_into_item' => __('Insert into blog post', 'parfume-reviews'),
            'uploaded_to_this_item' => __('Uploaded to this blog post', 'parfume-reviews'),
        );
        
        $args = array(
            'labels' => $labels,
            'public' => true,
            'publicly_queryable' => true,
            'show_ui' => true,
            'show_in_menu' => 'edit.php?post_type=parfume',
            'show_in_admin_bar' => true,
            'show_in_nav_menus' => true,
            'can_export' => true,
            'query_var' => true,
            'rewrite' => array(
                'slug' => $parfume_slug . '/blog',
                'with_front' => false,
                'feeds' => true,
                'pages' => true,
                'hierarchical' => false
            ),
            'capability_type' => 'post',
            'has_archive' => $parfume_slug . '/blog',
            'hierarchical' => false,
            'menu_position' => null,
            'menu_icon' => 'dashicons-admin-post',
            'supports' => array(
                'title', 
                'editor', 
                'thumbnail', 
                'excerpt', 
                'comments', 
                'author', 
                'custom-fields',
                'revisions',
                'trackbacks',
                'page-attributes'
            ),
            'show_in_rest' => true,
            'rest_base' => 'parfume-blog',
            'taxonomies' => array('category', 'post_tag'),
            'delete_with_user' => false,
        );
        
        register_post_type('parfume_blog', $args);
        
        // Debug log
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("Parfume Blog post type registered with archive: " . $parfume_slug . '/blog');
        }
    }

    /**
     * Add custom rewrite rules - ПОПРАВЕНА ВЕРСИЯ
     */
    public function add_custom_rewrite_rules() {
        $settings = get_option('parfume_reviews_settings', array());
        $parfume_slug = !empty($settings['parfume_slug']) ? $settings['parfume_slug'] : 'parfiumi';
        
        // Blog archive rules - ДОБАВЕНИ!
        add_rewrite_rule(
            '^' . $parfume_slug . '/blog/?$',
            'index.php?post_type=parfume_blog',
            'top'
        );
        
        add_rewrite_rule(
            '^' . $parfume_slug . '/blog/page/?([0-9]{1,})/?$',
            'index.php?post_type=parfume_blog&paged=$matches[1]',
            'top'
        );
        
        // Single blog post rules - ДОБАВЕНИ!
        add_rewrite_rule(
            '^' . $parfume_slug . '/blog/([^/]+)/?$',
            'index.php?post_type=parfume_blog&name=$matches[1]',
            'top'
        );
        
        // Parfume archive pagination rules
        add_rewrite_rule(
            '^' . $parfume_slug . '/([^/]+)/page/?([0-9]{1,})/?$',
            'index.php?post_type=parfume&name=$matches[1]&paged=$matches[2]',
            'top'
        );
        
        // Category and tag rules for blog posts - ДОБАВЕНИ!
        add_rewrite_rule(
            '^' . $parfume_slug . '/blog/category/([^/]+)/?$',
            'index.php?category_name=$matches[1]&post_type=parfume_blog',
            'top'
        );
        
        add_rewrite_rule(
            '^' . $parfume_slug . '/blog/tag/([^/]+)/?$',
            'index.php?tag=$matches[1]&post_type=parfume_blog',
            'top'
        );
        
        // Debug output
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("Custom rewrite rules added for parfume_slug: $parfume_slug");
            error_log("Blog archive URL: /$parfume_slug/blog/");
        }
    }
    
    /**
     * Load custom templates - ПОПРАВЕНА ВЕРСИЯ
     */
    public function template_loader($template) {
        // Single parfume template
        if (is_singular('parfume')) {
            $plugin_template = PARFUME_REVIEWS_PLUGIN_DIR . 'templates/single-parfume.php';
            if (file_exists($plugin_template)) {
                return $plugin_template;
            }
        }
        
        // Single blog post template - ДОБАВЕНО!
        if (is_singular('parfume_blog')) {
            $plugin_template = PARFUME_REVIEWS_PLUGIN_DIR . 'templates/single-parfume-blog.php';
            if (file_exists($plugin_template)) {
                return $plugin_template;
            }
        }
        
        // Blog archive template - ДОБАВЕНО!
        if (is_post_type_archive('parfume_blog')) {
            $plugin_template = PARFUME_REVIEWS_PLUGIN_DIR . 'templates/archive-parfume-blog.php';
            if (file_exists($plugin_template)) {
                return $plugin_template;
            }
        }
        
        // Parfume archive template
        if (is_post_type_archive('parfume')) {
            $plugin_template = PARFUME_REVIEWS_PLUGIN_DIR . 'templates/archive-parfume.php';
            if (file_exists($plugin_template)) {
                return $plugin_template;
            }
        }
        
        // Taxonomy templates
        if (is_tax(array('marki', 'notes', 'perfumer'))) {
            $queried_object = get_queried_object();
            if ($queried_object && isset($queried_object->taxonomy)) {
                $template_files = array(
                    'taxonomy-' . $queried_object->taxonomy . '-' . $queried_object->slug . '.php',
                    'taxonomy-' . $queried_object->taxonomy . '.php',
                    'taxonomy.php'
                );
                
                foreach ($template_files as $template_file) {
                    $plugin_template = PARFUME_REVIEWS_PLUGIN_DIR . 'templates/' . $template_file;
                    if (file_exists($plugin_template)) {
                        return $plugin_template;
                    }
                }
            }
        }
        
        // Generic taxonomy templates
        if (is_tax(array('gender', 'aroma_type', 'season', 'intensity'))) {
            $queried_object = get_queried_object();
            if ($queried_object && isset($queried_object->taxonomy)) {
                $plugin_template = PARFUME_REVIEWS_PLUGIN_DIR . 'templates/taxonomy-' . $queried_object->taxonomy . '.php';
                if (file_exists($plugin_template)) {
                    return $plugin_template;
                }
                
                // Fallback to generic taxonomy template
                $plugin_template = PARFUME_REVIEWS_PLUGIN_DIR . 'templates/taxonomy.php';
                if (file_exists($plugin_template)) {
                    return $plugin_template;
                }
            }
        }
        
        return $template;
    }
    
    public function enqueue_scripts() {
        if (is_singular('parfume') || is_post_type_archive('parfume') || is_tax(array('marki', 'gender', 'aroma_type', 'season', 'intensity', 'notes', 'perfumer'))) {
            
            // Main frontend CSS
            wp_enqueue_style(
                'parfume-reviews-frontend',
                PARFUME_REVIEWS_PLUGIN_URL . 'assets/css/frontend.css',
                array(),
                PARFUME_REVIEWS_VERSION
            );
            
            // Single parfume specific CSS
            if (is_singular('parfume')) {
                wp_enqueue_style(
                    'parfume-reviews-single',
                    PARFUME_REVIEWS_PLUGIN_URL . 'assets/css/single-parfume.css',
                    array('parfume-reviews-frontend'),
                    PARFUME_REVIEWS_VERSION
                );
            }
            
            wp_enqueue_script(
                'parfume-reviews-frontend',
                PARFUME_REVIEWS_PLUGIN_URL . 'assets/js/frontend.js',
                array('jquery'),
                PARFUME_REVIEWS_VERSION,
                true
            );
            
            wp_enqueue_script(
                'parfume-reviews-filters',
                PARFUME_REVIEWS_PLUGIN_URL . 'assets/js/filters.js',
                array('jquery'),
                PARFUME_REVIEWS_VERSION,
                true
            );
            
            // Localize script for AJAX
            wp_localize_script('parfume-reviews-frontend', 'parfumeReviews', array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('parfume_reviews_nonce'),
                'strings' => array(
                    'loading' => __('Loading...', 'parfume-reviews'),
                    'error' => __('An error occurred', 'parfume-reviews'),
                    'success' => __('Success', 'parfume-reviews'),
                )
            ));
        }
        
        // Blog specific styles
        if (is_singular('parfume_blog') || is_post_type_archive('parfume_blog')) {
            wp_enqueue_style(
                'parfume-reviews-blog',
                PARFUME_REVIEWS_PLUGIN_URL . 'assets/css/blog.css',
                array(),
                PARFUME_REVIEWS_VERSION
            );
        }
    }
    
    public function admin_enqueue_scripts($hook) {
        global $post_type;
        
        if ($post_type == 'parfume' || $post_type == 'parfume_blog') {
            wp_enqueue_media();
            
            wp_enqueue_style(
                'parfume-reviews-admin',
                PARFUME_REVIEWS_PLUGIN_URL . 'assets/css/admin.css',
                array(),
                PARFUME_REVIEWS_VERSION
            );
            
            wp_enqueue_script(
                'parfume-reviews-admin',
                PARFUME_REVIEWS_PLUGIN_URL . 'assets/js/admin.js',
                array('jquery', 'jquery-ui-sortable'),
                PARFUME_REVIEWS_VERSION,
                true
            );
            
            wp_localize_script('parfume-reviews-admin', 'parfumeAdmin', array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('parfume_admin_nonce'),
            ));
        }
    }
    
// Добави този метод към class-post-type.php в add_meta_boxes() функцията
    public function add_meta_boxes() {
        add_meta_box(
            'parfume_details',
            __('Parfume Details', 'parfume-reviews'),
            array($this, 'parfume_details_meta_box'),
            'parfume',
            'normal',
            'high'
        );
        
        add_meta_box(
            'parfume_rating',
            __('Rating & Review', 'parfume-reviews'),
            array($this, 'parfume_rating_meta_box'),
            'parfume',
            'normal',
            'high'
        );
        
        add_meta_box(
            'parfume_stores',
            __('Stores & Pricing', 'parfume-reviews'),
            array($this, 'parfume_stores_meta_box'),
            'parfume',
            'normal',
            'high'
        );
        
        // НОВА ГАЛЕРИЯ META BOX
        add_meta_box(
            'parfume_gallery',
            __('Parfume Gallery', 'parfume-reviews'),
            array($this, 'parfume_gallery_meta_box'),
            'parfume',
            'side',
            'high'
        );
        
        add_meta_box(
            'parfume_notes',
            __('Aroma Notes', 'parfume-reviews'),
            array($this, 'parfume_notes_meta_box'),
            'parfume',
            'side',
            'default'
        );
    }

    // НОВА ФУНКЦИЯ ЗА ГАЛЕРИЯ META BOX
    public function parfume_gallery_meta_box($post) {
        $gallery_images = get_post_meta($post->ID, '_parfume_gallery', true);
        if (!is_array($gallery_images)) {
            $gallery_images = array();
        }
        ?>
        <div class="parfume-gallery-container">
            <p class="description"><?php _e('Добавете допълнителни снимки на парфюма. Те ще се показват под главната снимка.', 'parfume-reviews'); ?></p>
            
            <div id="parfume-gallery-images" class="gallery-images">
                <?php foreach ($gallery_images as $index => $image_id): ?>
                    <?php if ($image_id): ?>
                        <div class="gallery-image-item" data-image-id="<?php echo esc_attr($image_id); ?>">
                            <?php echo wp_get_attachment_image($image_id, 'thumbnail'); ?>
                            <div class="gallery-image-actions">
                                <button type="button" class="remove-gallery-image button-secondary">×</button>
                                <span class="drag-handle">⋮⋮</span>
                            </div>
                            <input type="hidden" name="parfume_gallery[]" value="<?php echo esc_attr($image_id); ?>">
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
            
            <div class="gallery-actions">
                <button type="button" id="add-gallery-image" class="button button-secondary">
                    <?php _e('Добави снимка', 'parfume-reviews'); ?>
                </button>
                <button type="button" id="clear-gallery" class="button button-secondary">
                    <?php _e('Изчисти всички', 'parfume-reviews'); ?>
                </button>
            </div>
        </div>
        
        <style>
        .parfume-gallery-container {
            padding: 10px 0;
        }
        
        .gallery-images {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(80px, 1fr));
            gap: 10px;
            margin: 15px 0;
            min-height: 50px;
            border: 2px dashed #ddd;
            padding: 10px;
            border-radius: 6px;
        }
        
        .gallery-image-item {
            position: relative;
            border: 2px solid #ddd;
            border-radius: 6px;
            overflow: hidden;
            cursor: move;
            transition: all 0.3s ease;
        }
        
        .gallery-image-item:hover {
            border-color: #0073aa;
            transform: scale(1.05);
        }
        
        .gallery-image-item img {
            width: 100%;
            height: 80px;
            object-fit: cover;
            display: block;
        }
        
        .gallery-image-actions {
            position: absolute;
            top: 0;
            right: 0;
            background: rgba(0, 0, 0, 0.7);
            padding: 2px;
            border-radius: 0 4px 0 6px;
            display: flex;
            align-items: center;
            gap: 2px;
        }
        
        .remove-gallery-image {
            background: #dc3545;
            color: white;
            border: none;
            width: 18px;
            height: 18px;
            line-height: 1;
            font-size: 12px;
            cursor: pointer;
            border-radius: 2px;
        }
        
        .drag-handle {
            color: white;
            font-size: 8px;
            line-height: 1;
            cursor: move;
        }
        
        .gallery-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }
        
        .gallery-images.sortable-placeholder {
            background: #f0f8ff;
            border-color: #0073aa;
        }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            // Media uploader
            let mediaUploader;
            
            $('#add-gallery-image').click(function(e) {
                e.preventDefault();
                
                if (mediaUploader) {
                    mediaUploader.open();
                    return;
                }
                
                mediaUploader = wp.media({
                    title: '<?php _e('Избери снимки за галерията', 'parfume-reviews'); ?>',
                    button: {
                        text: '<?php _e('Добави към галерията', 'parfume-reviews'); ?>'
                    },
                    multiple: true
                });
                
                mediaUploader.on('select', function() {
                    const attachments = mediaUploader.state().get('selection');
                    
                    attachments.map(function(attachment) {
                        attachment = attachment.toJSON();
                        addImageToGallery(attachment.id, attachment.sizes.thumbnail.url);
                    });
                });
                
                mediaUploader.open();
            });
            
            // Remove image
            $(document).on('click', '.remove-gallery-image', function() {
                $(this).closest('.gallery-image-item').remove();
            });
            
            // Clear all
            $('#clear-gallery').click(function() {
                if (confirm('<?php _e('Сигурни ли сте, че искате да изчистите всички снимки?', 'parfume-reviews'); ?>')) {
                    $('#parfume-gallery-images').empty();
                }
            });
            
            // Make sortable
            $('#parfume-gallery-images').sortable({
                placeholder: 'sortable-placeholder',
                cursor: 'move',
                opacity: 0.8
            });
            
            function addImageToGallery(imageId, thumbnailUrl) {
                const html = `
                    <div class="gallery-image-item" data-image-id="${imageId}">
                        <img src="${thumbnailUrl}" alt="Gallery Image">
                        <div class="gallery-image-actions">
                            <button type="button" class="remove-gallery-image button-secondary">×</button>
                            <span class="drag-handle">⋮⋮</span>
                        </div>
                        <input type="hidden" name="parfume_gallery[]" value="${imageId}">
                    </div>
                `;
                $('#parfume-gallery-images').append(html);
            }
        });
        </script>
        <?php
    }

    // ДОБАВИ ТОВА КЪМ save_meta_boxes() ФУНКЦИЯТА
    // В save_meta_boxes() добави след другите fields:
    
    // Save gallery
    if (isset($_POST['parfume_gallery']) && is_array($_POST['parfume_gallery'])) {
        $gallery_images = array_map('intval', $_POST['parfume_gallery']);
        $gallery_images = array_filter($gallery_images); // Remove empty values
        update_post_meta($post_id, '_parfume_gallery', $gallery_images);
    } else {
        delete_post_meta($post_id, '_parfume_gallery');
    }
    
    public function parfume_details_meta_box($post) {
        wp_nonce_field('parfume_meta_box_nonce', 'parfume_meta_box_nonce_field');
        
        $gender_text = get_post_meta($post->ID, '_parfume_gender_text', true);
        $release_year = get_post_meta($post->ID, '_parfume_release_year', true);
        $longevity = get_post_meta($post->ID, '_parfume_longevity', true);
        $sillage = get_post_meta($post->ID, '_parfume_sillage', true);
        $bottle_size = get_post_meta($post->ID, '_parfume_bottle_size', true);
        $aroma_chart = get_post_meta($post->ID, '_parfume_aroma_chart', true);
        
        if (!is_array($aroma_chart)) {
            $aroma_chart = array(
                'freshness' => 5,
                'sweetness' => 5,
                'intensity' => 5,
                'warmth' => 5
            );
        }
        ?>
        <table class="form-table">
            <tr>
                <th scope="row"><label for="parfume_gender_text"><?php _e('Gender Description', 'parfume-reviews'); ?></label></th>
                <td><input type="text" id="parfume_gender_text" name="parfume_gender_text" value="<?php echo esc_attr($gender_text); ?>" class="regular-text" /></td>
            </tr>
            <tr>
                <th scope="row"><label for="parfume_release_year"><?php _e('Release Year', 'parfume-reviews'); ?></label></th>
                <td><input type="number" id="parfume_release_year" name="parfume_release_year" value="<?php echo esc_attr($release_year); ?>" min="1900" max="<?php echo date('Y'); ?>" /></td>
            </tr>
            <tr>
                <th scope="row"><label for="parfume_longevity"><?php _e('Longevity', 'parfume-reviews'); ?></label></th>
                <td><input type="text" id="parfume_longevity" name="parfume_longevity" value="<?php echo esc_attr($longevity); ?>" class="regular-text" placeholder="e.g. 6-8 hours" /></td>
            </tr>
            <tr>
                <th scope="row"><label for="parfume_sillage"><?php _e('Sillage', 'parfume-reviews'); ?></label></th>
                <td><input type="text" id="parfume_sillage" name="parfume_sillage" value="<?php echo esc_attr($sillage); ?>" class="regular-text" placeholder="e.g. Moderate" /></td>
            </tr>
            <tr>
                <th scope="row"><label for="parfume_bottle_size"><?php _e('Bottle Size', 'parfume-reviews'); ?></label></th>
                <td><input type="text" id="parfume_bottle_size" name="parfume_bottle_size" value="<?php echo esc_attr($bottle_size); ?>" class="regular-text" placeholder="e.g. 100ml" /></td>
            </tr>
        </table>
        
        <h4><?php _e('Aroma Chart', 'parfume-reviews'); ?></h4>
        <table class="form-table">
            <tr>
                <th scope="row"><label for="aroma_freshness"><?php _e('Freshness', 'parfume-reviews'); ?></label></th>
                <td>
                    <input type="range" id="aroma_freshness" name="parfume_aroma_chart[freshness]" value="<?php echo esc_attr($aroma_chart['freshness']); ?>" min="0" max="10" />
                    <span class="range-value"><?php echo esc_html($aroma_chart['freshness']); ?></span>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="aroma_sweetness"><?php _e('Sweetness', 'parfume-reviews'); ?></label></th>
                <td>
                    <input type="range" id="aroma_sweetness" name="parfume_aroma_chart[sweetness]" value="<?php echo esc_attr($aroma_chart['sweetness']); ?>" min="0" max="10" />
                    <span class="range-value"><?php echo esc_html($aroma_chart['sweetness']); ?></span>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="aroma_intensity"><?php _e('Intensity', 'parfume-reviews'); ?></label></th>
                <td>
                    <input type="range" id="aroma_intensity" name="parfume_aroma_chart[intensity]" value="<?php echo esc_attr($aroma_chart['intensity']); ?>" min="0" max="10" />
                    <span class="range-value"><?php echo esc_html($aroma_chart['intensity']); ?></span>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="aroma_warmth"><?php _e('Warmth', 'parfume-reviews'); ?></label></th>
                <td>
                    <input type="range" id="aroma_warmth" name="parfume_aroma_chart[warmth]" value="<?php echo esc_attr($aroma_chart['warmth']); ?>" min="0" max="10" />
                    <span class="range-value"><?php echo esc_html($aroma_chart['warmth']); ?></span>
                </td>
            </tr>
        </table>
        
        <script>
        jQuery(document).ready(function($) {
            $('input[type="range"]').on('input', function() {
                $(this).next('.range-value').text($(this).val());
            });
        });
        </script>
        <?php
    }
    
    public function parfume_rating_meta_box($post) {
        $rating = get_post_meta($post->ID, '_parfume_rating', true);
        $pros = get_post_meta($post->ID, '_parfume_pros', true);
        $cons = get_post_meta($post->ID, '_parfume_cons', true);
        ?>
        <table class="form-table">
            <tr>
                <th scope="row"><label for="parfume_rating"><?php _e('Overall Rating', 'parfume-reviews'); ?></label></th>
                <td>
                    <input type="number" id="parfume_rating" name="parfume_rating" value="<?php echo esc_attr($rating); ?>" min="0" max="5" step="0.1" />
                    <p class="description"><?php _e('Rating from 0 to 5', 'parfume-reviews'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="parfume_pros"><?php _e('Pros', 'parfume-reviews'); ?></label></th>
                <td>
                    <textarea id="parfume_pros" name="parfume_pros" rows="5" cols="50" class="large-text"><?php echo esc_textarea($pros); ?></textarea>
                    <p class="description"><?php _e('One pro per line', 'parfume-reviews'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="parfume_cons"><?php _e('Cons', 'parfume-reviews'); ?></label></th>
                <td>
                    <textarea id="parfume_cons" name="parfume_cons" rows="5" cols="50" class="large-text"><?php echo esc_textarea($cons); ?></textarea>
                    <p class="description"><?php _e('One con per line', 'parfume-reviews'); ?></p>
                </td>
            </tr>
        </table>
        <?php
    }
    
    public function parfume_stores_meta_box($post) {
        $stores = get_post_meta($post->ID, '_parfume_stores', true);
        if (!is_array($stores)) {
            $stores = array();
        }
        
        ?>
        <div id="parfume-stores-container">
            <?php foreach ($stores as $index => $store): ?>
                <?php $this->render_store_row($store, $index); ?>
            <?php endforeach; ?>
        </div>
        
        <p>
            <button type="button" id="add-store" class="button"><?php _e('Add Store', 'parfume-reviews'); ?></button>
        </p>
        
        <script>
        jQuery(document).ready(function($) {
            var storeIndex = <?php echo count($stores); ?>;
            
            $('#add-store').click(function() {
                var html = <?php echo json_encode($this->get_store_row_template()); ?>;
                html = html.replace(/\{INDEX\}/g, storeIndex);
                $('#parfume-stores-container').append(html);
                storeIndex++;
            });
            
            $(document).on('click', '.remove-store', function() {
                $(this).closest('.store-row').remove();
            });
        });
        </script>
        <?php
    }
    
    private function get_store_row_template() {
        ob_start();
        $this->render_store_row(array(), '{INDEX}');
        return ob_get_clean();
    }
    
    private function render_store_row($store, $index) {
        ?>
        <div class="store-row" style="border: 1px solid #ccc; padding: 10px; margin-bottom: 10px;">
            <h4><?php _e('Store', 'parfume-reviews'); ?> #<?php echo $index + 1; ?> 
                <button type="button" class="remove-store button"><?php _e('Remove', 'parfume-reviews'); ?></button>
            </h4>
            <table class="form-table">
                <tr>
                    <th scope="row"><label><?php echo __('Store Name', 'parfume-reviews'); ?></label></th>
                    <td><input type="text" name="parfume_stores[<?php echo $index; ?>][name]" value="<?php echo esc_attr($store['name'] ?? ''); ?>" class="regular-text" /></td>
                </tr>
                <tr>
                    <th scope="row"><label><?php echo __('Store Logo URL', 'parfume-reviews'); ?></label></th>
                    <td><input type="url" name="parfume_stores[<?php echo $index; ?>][logo]" value="<?php echo esc_attr($store['logo'] ?? ''); ?>" class="regular-text" /></td>
                </tr>
                <tr>
                    <th scope="row"><label><?php echo __('Store URL', 'parfume-reviews'); ?></label></th>
                    <td><input type="url" name="parfume_stores[<?php echo $index; ?>][url]" value="<?php echo esc_attr($store['url'] ?? ''); ?>" class="regular-text" /></td>
                </tr>
                <tr>
                    <th scope="row"><label><?php echo __('Affiliate URL', 'parfume-reviews'); ?></label></th>
                    <td><input type="url" name="parfume_stores[<?php echo $index; ?>][affiliate_url]" value="<?php echo esc_attr($store['affiliate_url'] ?? ''); ?>" class="regular-text" /></td>
                </tr>
                <tr>
                    <th scope="row"><label><?php echo __('Price', 'parfume-reviews'); ?></label></th>
                    <td><input type="text" name="parfume_stores[<?php echo $index; ?>][price]" value="<?php echo esc_attr($store['price'] ?? ''); ?>" class="regular-text" /></td>
                </tr>
                <tr>
                    <th scope="row"><label><?php echo __('Size', 'parfume-reviews'); ?></label></th>
                    <td><input type="text" name="parfume_stores[<?php echo $index; ?>][size]" value="<?php echo esc_attr($store['size'] ?? ''); ?>" class="regular-text" /></td>
                </tr>
                <tr>
                    <th scope="row"><label><?php echo __('Availability', 'parfume-reviews'); ?></label></th>
                    <td>
                        <select name="parfume_stores[<?php echo $index; ?>][availability]">
                            <option value="in_stock" <?php selected($store['availability'] ?? '', 'in_stock'); ?>><?php echo __('In Stock', 'parfume-reviews'); ?></option>
                            <option value="out_of_stock" <?php selected($store['availability'] ?? '', 'out_of_stock'); ?>><?php echo __('Out of Stock', 'parfume-reviews'); ?></option>
                        </select>
                    </td>
                </tr>
            </table>
        </div>
        <?php
    }
    
    public function parfume_notes_meta_box($post) {
        echo '<p>' . __('Use the "Notes" taxonomy to add notes to the parfume.', 'parfume-reviews') . '</p>';
        
        $top_notes = get_post_meta($post->ID, '_parfume_top_notes', true);
        $middle_notes = get_post_meta($post->ID, '_parfume_middle_notes', true);
        $base_notes = get_post_meta($post->ID, '_parfume_base_notes', true);
        
        echo '<p><label><strong>' . __('Top Notes:', 'parfume-reviews') . '</strong></label><br>';
        echo '<textarea name="_parfume_top_notes" rows="3" cols="30">' . esc_textarea($top_notes) . '</textarea></p>';
        
        echo '<p><label><strong>' . __('Middle Notes:', 'parfume-reviews') . '</strong></label><br>';
        echo '<textarea name="_parfume_middle_notes" rows="3" cols="30">' . esc_textarea($middle_notes) . '</textarea></p>';
        
        echo '<p><label><strong>' . __('Base Notes:', 'parfume-reviews') . '</strong></label><br>';
        echo '<textarea name="_parfume_base_notes" rows="3" cols="30">' . esc_textarea($base_notes) . '</textarea></p>';
    }
    
    public function save_meta_boxes($post_id) {
        if (!isset($_POST['parfume_meta_box_nonce_field']) || !wp_verify_nonce($_POST['parfume_meta_box_nonce_field'], 'parfume_meta_box_nonce')) {
            return;
        }
        
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Save basic fields
        $fields = array(
            'parfume_gender_text' => '_parfume_gender_text',
            'parfume_release_year' => '_parfume_release_year',
            'parfume_longevity' => '_parfume_longevity',
            'parfume_sillage' => '_parfume_sillage',
            'parfume_bottle_size' => '_parfume_bottle_size',
            'parfume_rating' => '_parfume_rating',
            'parfume_pros' => '_parfume_pros',
            'parfume_cons' => '_parfume_cons',
            '_parfume_top_notes' => '_parfume_top_notes',
            '_parfume_middle_notes' => '_parfume_middle_notes',
            '_parfume_base_notes' => '_parfume_base_notes',
        );
        
        foreach ($fields as $field => $meta_key) {
            if (isset($_POST[$field])) {
                update_post_meta($post_id, $meta_key, sanitize_text_field($_POST[$field]));
            }
        }
        
        // Save aroma chart
        if (isset($_POST['parfume_aroma_chart']) && is_array($_POST['parfume_aroma_chart'])) {
            $aroma_chart = array();
            foreach ($_POST['parfume_aroma_chart'] as $key => $value) {
                $aroma_chart[sanitize_key($key)] = intval($value);
            }
            update_post_meta($post_id, '_parfume_aroma_chart', $aroma_chart);
        }
        
        // Save stores
        if (isset($_POST['parfume_stores']) && is_array($_POST['parfume_stores'])) {
            $stores = array();
            foreach ($_POST['parfume_stores'] as $store) {
                if (!empty($store['name'])) {
                    $stores[] = array(
                        'name' => sanitize_text_field($store['name']),
                        'logo' => esc_url_raw($store['logo']),
                        'url' => esc_url_raw($store['url']),
                        'affiliate_url' => esc_url_raw($store['affiliate_url']),
                        'price' => sanitize_text_field($store['price']),
                        'size' => sanitize_text_field($store['size']),
                        'availability' => sanitize_text_field($store['availability']),
                    );
                }
            }
            update_post_meta($post_id, '_parfume_stores', $stores);
        } else {
            delete_post_meta($post_id, '_parfume_stores');
        }
    }
    
    // ПОПРАВЕНА ВЕРСИЯ - БЕЗ 404 ГРЕШКИ!
    public function modify_archive_query($query) {
        if (!is_admin() && $query->is_main_query()) {
            if (is_post_type_archive('parfume') || is_tax(array('marki', 'gender', 'aroma_type', 'season', 'intensity', 'notes', 'perfumer'))) {
                $settings = get_option('parfume_reviews_settings', array());
                $per_page = !empty($settings['archive_posts_per_page']) ? intval($settings['archive_posts_per_page']) : 12;
                $query->set('posts_per_page', $per_page);
                
                // Handle filtering and sorting
                $this->handle_query_filters($query);
                
                // Handle custom sorting
                $this->handle_query_sorting($query);
            }
        }
    }
    
    // ПОПРАВЕН МЕТОД ЗА ФИЛТРИ - БЕЗ 404!
    private function handle_query_filters($query) {
        // Проверяваме дали има филтърни параметри в URL
        if (empty($_GET)) {
            return;
        }
        
        // Дефинираме поддържаните таксономии
        $supported_taxonomies = array('gender', 'aroma_type', 'marki', 'season', 'intensity', 'notes', 'perfumer');
        
        $tax_query = array();
        $has_filters = false;
        
        foreach ($supported_taxonomies as $taxonomy) {
            if (!empty($_GET[$taxonomy])) {
                $has_filters = true;
                
                // Получаваме стойностите и ги декодираме правилно
                $raw_terms = $_GET[$taxonomy];
                $terms = is_array($raw_terms) ? $raw_terms : array($raw_terms);
                
                // Почистваме и декодираме термините
                $clean_terms = array();
                foreach ($terms as $term) {
                    // Декодираме URL encoding
                    $decoded_term = rawurldecode($term);
                    $decoded_term = sanitize_text_field($decoded_term);
                    
                    if (!empty($decoded_term)) {
                        $clean_terms[] = $decoded_term;
                    }
                }
                
                if (!empty($clean_terms)) {
                    $tax_query[] = array(
                        'taxonomy' => $taxonomy,
                        'field'    => 'slug',
                        'terms'    => $clean_terms,
                        'operator' => 'IN',
                    );
                }
            }
        }
        
        // Проверяваме за ценови филтри
        $meta_query = array();
        $has_meta_filters = false;
        
        if (!empty($_GET['min_price']) || !empty($_GET['max_price'])) {
            $min_price = !empty($_GET['min_price']) ? floatval($_GET['min_price']) : 0;
            $max_price = !empty($_GET['max_price']) ? floatval($_GET['max_price']) : 99999;
            
            if ($min_price > 0 || $max_price < 99999) {
                $has_meta_filters = true;
                $meta_query[] = array(
                    'key' => '_parfume_price',
                    'value' => array($min_price, $max_price),
                    'type' => 'DECIMAL',
                    'compare' => 'BETWEEN',
                );
            }
        }
        
        // Проверяваме за рейтинг филтър
        if (!empty($_GET['min_rating'])) {
            $min_rating = floatval($_GET['min_rating']);
            if ($min_rating > 0) {
                $has_meta_filters = true;
                $meta_query[] = array(
                    'key' => '_parfume_rating',
                    'value' => $min_rating,
                    'type' => 'DECIMAL',
                    'compare' => '>=',
                );
            }
        }
        
        // Прилагаме филтрите към query-то
        if ($has_filters) {
            if (count($tax_query) > 1) {
                $tax_query['relation'] = 'AND';
            }
            $query->set('tax_query', $tax_query);
        }
        
        if ($has_meta_filters) {
            if (count($meta_query) > 1) {
                $meta_query['relation'] = 'AND';
            }
            $query->set('meta_query', $meta_query);
        }
    }
    
    private function handle_query_sorting($query) {
        if (!empty($_GET['orderby'])) {
            $orderby = sanitize_key($_GET['orderby']);
            $order = !empty($_GET['order']) ? sanitize_key($_GET['order']) : 'ASC';
            
            switch ($orderby) {
                case 'title':
                    $query->set('orderby', 'title');
                    $query->set('order', $order);
                    break;
                case 'date':
                    $query->set('orderby', 'date');
                    $query->set('order', $order);
                    break;
                case 'rating':
                    $query->set('meta_key', '_parfume_rating');
                    $query->set('orderby', 'meta_value_num');
                    $query->set('order', $order);
                    break;
                case 'price':
                    $query->set('meta_key', '_parfume_price');
                    $query->set('orderby', 'meta_value_num');
                    $query->set('order', $order);
                    break;
            }
        }
    }
    
    public function filter_posts_where($where, $query) {
        // This can be used for custom WHERE clauses if needed
        return $where;
    }
    
    // AJAX Methods
    public function ajax_update_store_price() {
        check_ajax_referer('parfume_reviews_nonce', 'nonce');
        
        $store_id = intval($_POST['store_id']);
        
        // Here you would implement price checking logic
        // For now, return a mock response
        wp_send_json_success(array(
            'price' => '120.00 лв.',
            'last_updated' => current_time('mysql')
        ));
    }
    
    public function ajax_get_store_sizes() {
        check_ajax_referer('parfume_reviews_nonce', 'nonce');
        
        $store_id = intval($_POST['store_id']);
        
        // Mock data for now
        $sizes = array(
            array('size' => '30ml', 'price' => '45.00 лв.'),
            array('size' => '50ml', 'price' => '75.00 лв.'),
            array('size' => '100ml', 'price' => '120.00 лв.'),
        );
        
        wp_send_json_success($sizes);
    }
    
    public function debug_current_request() {
        if (!current_user_can('manage_options') || !isset($_GET['parfume_debug'])) {
            return;
        }
        
        global $wp_query;
        
        echo '<div style="background: white; padding: 20px; border: 2px solid red; margin: 20px; z-index: 9999; position: relative;">';
        echo '<h3>Parfume Reviews Debug Info</h3>';
        echo '<strong>Current URL:</strong> ' . esc_url($_SERVER['REQUEST_URI']) . '<br>';
        echo '<strong>Query Vars:</strong><pre>' . print_r($wp_query->query_vars, true) . '</pre>';
        echo '<strong>GET Parameters:</strong><pre>' . print_r($_GET, true) . '</pre>';
        echo '<strong>Is 404:</strong> ' . (is_404() ? 'YES' : 'NO') . '<br>';
        echo '<strong>Post Type Archive:</strong> ' . (is_post_type_archive('parfume') ? 'YES' : 'NO') . '<br>';
        echo '<strong>Is Tax:</strong> ' . (is_tax() ? 'YES' : 'NO') . '<br>';
        echo '<strong>Found Posts:</strong> ' . $wp_query->found_posts . '<br>';
        echo '</div>';
    }
    
    // Helper методи за филтри
    public static function build_filter_url($filters = array(), $base_url = '') {
        if (empty($base_url)) {
            if (is_post_type_archive('parfume')) {
                $base_url = get_post_type_archive_link('parfume');
            } elseif (is_tax()) {
                $base_url = get_term_link(get_queried_object());
            } else {
                $base_url = home_url('/parfiumi/');
            }
        }
        
        if (!empty($filters)) {
            $base_url = add_query_arg($filters, $base_url);
        }
        
        return $base_url;
    }
    
    public static function get_active_filters() {
        $active_filters = array();
        $supported_taxonomies = array('gender', 'aroma_type', 'marki', 'season', 'intensity', 'notes', 'perfumer');
        
        foreach ($supported_taxonomies as $taxonomy) {
            if (!empty($_GET[$taxonomy])) {
                $terms = is_array($_GET[$taxonomy]) ? $_GET[$taxonomy] : array($_GET[$taxonomy]);
                $active_filters[$taxonomy] = array_map('sanitize_text_field', $terms);
            }
        }
        
        return $active_filters;
    }
    
    // Shortcode methods
    public function all_brands_archive_shortcode($atts) {
        $atts = shortcode_atts(array(
            'columns' => 4,
            'show_count' => true,
        ), $atts);
        
        $terms = get_terms(array(
            'taxonomy' => 'marki',
            'hide_empty' => false,
        ));
        
        if (empty($terms) || is_wp_error($terms)) {
            return '<p>' . __('No brands found.', 'parfume-reviews') . '</p>';
        }
        
        $output = '<div class="brands-archive-grid columns-' . intval($atts['columns']) . '">';
        
        foreach ($terms as $term) {
            $output .= '<div class="brand-item">';
            $output .= '<a href="' . get_term_link($term) . '">';
            $output .= '<h3>' . esc_html($term->name) . '</h3>';
            
            if ($atts['show_count']) {
                $output .= '<span class="count">(' . $term->count . ')</span>';
            }
            
            $output .= '</a>';
            $output .= '</div>';
        }
        
        $output .= '</div>';
        
        return $output;
    }
    
    public function all_notes_archive_shortcode($atts) {
        $atts = shortcode_atts(array(
            'columns' => 6,
            'show_count' => true,
        ), $atts);
        
        $terms = get_terms(array(
            'taxonomy' => 'notes',
            'hide_empty' => false,
        ));
        
        if (empty($terms) || is_wp_error($terms)) {
            return '<p>' . __('No notes found.', 'parfume-reviews') . '</p>';
        }
        
        $output = '<div class="notes-archive-grid columns-' . intval($atts['columns']) . '">';
        
        foreach ($terms as $term) {
            $output .= '<div class="note-item">';
            $output .= '<a href="' . get_term_link($term) . '">';
            $output .= '<span class="note-name">' . esc_html($term->name) . '</span>';
            
            if ($atts['show_count']) {
                $output .= '<span class="count">(' . $term->count . ')</span>';
            }
            
            $output .= '</a>';
            $output .= '</div>';
        }
        
        $output .= '</div>';
        
        return $output;
    }
    
    public function all_perfumers_archive_shortcode($atts) {
        $atts = shortcode_atts(array(
            'columns' => 3,
            'show_count' => true,
        ), $atts);
        
        $terms = get_terms(array(
            'taxonomy' => 'perfumer',
            'hide_empty' => false,
        ));
        
        if (empty($terms) || is_wp_error($terms)) {
            return '<p>' . __('No perfumers found.', 'parfume-reviews') . '</p>';
        }
        
        $output = '<div class="perfumers-archive-grid columns-' . intval($atts['columns']) . '">';
        
        foreach ($terms as $term) {
            $output .= '<div class="perfumer-item">';
            $output .= '<a href="' . get_term_link($term) . '">';
            $output .= '<h3>' . esc_html($term->name) . '</h3>';
            
            if ($atts['show_count']) {
                $output .= '<span class="count">(' . $term->count . ' ' . __('parfumes', 'parfume-reviews') . ')</span>';
            }
            
            $output .= '</a>';
            $output .= '</div>';
        }
        
        $output .= '</div>';
        
        return $output;
    }
}