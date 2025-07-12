<?php
namespace Parfume_Reviews;

class Post_Type {
    public function __construct() {
        add_action('init', array($this, 'register_post_type'));
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
            'not_found_in_trash' => __('No parfumes found in Trash.', 'parfume-reviews')
        );
        
        $args = array(
            'labels' => $labels,
            'public' => true,
            'publicly_queryable' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'query_var' => true,
            'rewrite' => array('slug' => $slug, 'with_front' => false),
            'capability_type' => 'post',
            'has_archive' => true,
            'hierarchical' => false,
            'menu_position' => 5,
            'supports' => array('title', 'editor', 'thumbnail', 'excerpt', 'comments', 'custom-fields'),
            'show_in_rest' => true,
            'menu_icon' => 'dashicons-airplane',
            'taxonomies' => array('marki', 'gender', 'aroma_type', 'season', 'intensity', 'notes', 'perfumer'),
        );
        
        register_post_type('parfume', $args);
    }
    
    public function modify_archive_query($query) {
        if (!is_admin() && $query->is_main_query()) {
            if (is_post_type_archive('parfume') || is_tax(array('marki', 'gender', 'aroma_type', 'season', 'intensity', 'notes', 'perfumer'))) {
                // Add taxonomy filters from GET parameters
                $tax_query = array();
                
                $taxonomies = array('gender', 'aroma_type', 'marki', 'season', 'intensity', 'notes', 'perfumer');
                
                foreach ($taxonomies as $taxonomy) {
                    if (!empty($_GET[$taxonomy])) {
                        $term_slug = sanitize_text_field($_GET[$taxonomy]);
                        $tax_query[] = array(
                            'taxonomy' => $taxonomy,
                            'field' => 'slug',
                            'terms' => $term_slug,
                        );
                    }
                }
                
                if (!empty($tax_query)) {
                    $existing_tax_query = $query->get('tax_query');
                    if (!empty($existing_tax_query)) {
                        $tax_query = array_merge($existing_tax_query, $tax_query);
                    }
                    $tax_query['relation'] = 'AND';
                    $query->set('tax_query', $tax_query);
                }
                
                // Set posts per page
                $query->set('posts_per_page', 12);
                
                // Set default ordering
                if (!$query->get('orderby')) {
                    $query->set('orderby', 'date');
                    $query->set('order', 'DESC');
                }
            }
        }
    }
    
    // Archive shortcodes
    public function all_brands_archive_shortcode($atts) {
        ob_start();
        
        // Get all brands ordered alphabetically
        $all_brands = get_terms(array(
            'taxonomy' => 'marki',
            'hide_empty' => false,
            'orderby' => 'name',
            'order' => 'ASC',
        ));

        // Group brands by first letter
        $brands_by_letter = array();
        $available_letters = array();

        if (!empty($all_brands) && !is_wp_error($all_brands)) {
            foreach ($all_brands as $brand) {
                $first_letter = mb_strtoupper(mb_substr($brand->name, 0, 1, 'UTF-8'), 'UTF-8');
                
                if (preg_match('/[А-Я]/u', $first_letter)) {
                    $letter_key = $first_letter;
                } elseif (preg_match('/[A-Z]/', $first_letter)) {
                    $letter_key = $first_letter;
                } else {
                    $letter_key = '#';
                }
                
                if (!isset($brands_by_letter[$letter_key])) {
                    $brands_by_letter[$letter_key] = array();
                }
                $brands_by_letter[$letter_key][] = $brand;
                
                if (!in_array($letter_key, $available_letters)) {
                    $available_letters[] = $letter_key;
                }
            }
        }

        sort($available_letters);
        $cyrillic_alphabet = array('А', 'Б', 'В', 'Г', 'Д', 'Е', 'Ж', 'З', 'И', 'Й', 'К', 'Л', 'М', 'Н', 'О', 'П', 'Р', 'С', 'Т', 'У', 'Ф', 'Х', 'Ц', 'Ч', 'Ш', 'Щ', 'Ъ', 'Ь', 'Ю', 'Я');
        $latin_alphabet = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z');
        $full_alphabet = array_merge($latin_alphabet, $cyrillic_alphabet, array('#'));
        
        ?>
        <div class="parfume-brands-archive">
            <div class="alphabet-navigation">
                <div class="alphabet-nav-inner">
                    <?php foreach ($full_alphabet as $letter): ?>
                        <?php if (in_array($letter, $available_letters)): ?>
                            <a href="#letter-<?php echo esc_attr(strtolower($letter)); ?>" class="letter-link active">
                                <?php echo esc_html($letter); ?>
                            </a>
                        <?php else: ?>
                            <span class="letter-link inactive">
                                <?php echo esc_html($letter); ?>
                            </span>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="brands-content">
                <?php if (!empty($brands_by_letter)): ?>
                    <?php foreach ($available_letters as $letter): ?>
                        <div class="letter-section" id="letter-<?php echo esc_attr(strtolower($letter)); ?>">
                            <h2 class="letter-heading"><?php echo esc_html($letter); ?></h2>
                            
                            <div class="brands-grid">
                                <?php foreach ($brands_by_letter[$letter] as $brand): ?>
                                    <div class="brand-item">
                                        <a href="<?php echo get_term_link($brand); ?>" class="brand-link">
                                            <div class="brand-info">
                                                <h3 class="brand-name"><?php echo esc_html($brand->name); ?></h3>
                                                <span class="brand-count">
                                                    <?php printf(_n('%d perfume', '%d perfumes', $brand->count, 'parfume-reviews'), $brand->count); ?>
                                                </span>
                                                
                                                <?php if ($brand->description): ?>
                                                    <p class="brand-description"><?php echo wp_trim_words(esc_html($brand->description), 15); ?></p>
                                                <?php endif; ?>
                                            </div>
                                        </a>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="no-brands">No brands found.</p>
                <?php endif; ?>
            </div>
        </div>
        
        <style>
        .alphabet-navigation { background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 8px; padding: 20px; margin: 30px 0; }
        .alphabet-nav-inner { display: flex; flex-wrap: wrap; gap: 8px; justify-content: center; }
        .letter-link { display: inline-flex; align-items: center; justify-content: center; width: 35px; height: 35px; border-radius: 50%; text-decoration: none; font-weight: bold; font-size: 14px; transition: all 0.3s ease; }
        .letter-link.active { background: #0073aa; color: white; }
        .letter-link.inactive { background: #e9ecef; color: #6c757d; cursor: not-allowed; }
        .letter-heading { font-size: 2.5em; color: #0073aa; border-bottom: 3px solid #0073aa; padding-bottom: 10px; margin-bottom: 30px; }
        .brands-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px; }
        .brand-item { background: white; border: 1px solid #dee2e6; border-radius: 8px; overflow: hidden; transition: all 0.3s ease; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .brand-item:hover { transform: translateY(-5px); box-shadow: 0 8px 25px rgba(0,0,0,0.15); border-color: #0073aa; }
        .brand-link { display: block; padding: 20px; text-decoration: none; color: inherit; }
        .brand-name { font-size: 1.2em; font-weight: bold; margin: 0 0 8px; color: #333; }
        .brand-count { display: block; color: #0073aa; font-weight: 500; margin-bottom: 10px; }
        .brand-description { color: #666; font-size: 0.9em; line-height: 1.4; margin: 0; }
        </style>
        
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const letterLinks = document.querySelectorAll('.letter-link.active');
            letterLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    const target = document.querySelector(this.getAttribute('href'));
                    if (target) {
                        target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    }
                });
            });
        });
        </script>
        <?php
        
        return ob_get_clean();
    }
    
    public function all_notes_archive_shortcode($atts) {
        ob_start();
        echo '<div class="parfume-notes-archive">';
        echo '<h1>All Fragrance Notes</h1>';
        
        $all_notes = get_terms(array(
            'taxonomy' => 'notes',
            'hide_empty' => false,
            'orderby' => 'name',
            'order' => 'ASC',
        ));
        
        if (!empty($all_notes) && !is_wp_error($all_notes)) {
            echo '<div class="notes-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 15px;">';
            foreach ($all_notes as $note) {
                echo '<div class="note-item" style="background: white; border: 1px solid #ddd; padding: 15px; border-radius: 5px;">';
                echo '<h3><a href="' . get_term_link($note) . '">' . esc_html($note->name) . '</a></h3>';
                echo '<p>Perfumes: ' . $note->count . '</p>';
                if ($note->description) {
                    echo '<p>' . esc_html($note->description) . '</p>';
                }
                echo '</div>';
            }
            echo '</div>';
        } else {
            echo '<p>No notes found.</p>';
        }
        
        echo '</div>';
        return ob_get_clean();
    }
    
    public function all_perfumers_archive_shortcode($atts) {
        ob_start();
        echo '<div class="parfume-perfumers-archive">';
        echo '<h1>All Perfumers</h1>';
        
        $all_perfumers = get_terms(array(
            'taxonomy' => 'perfumer',
            'hide_empty' => false,
            'orderby' => 'name',
            'order' => 'ASC',
        ));
        
        if (!empty($all_perfumers) && !is_wp_error($all_perfumers)) {
            echo '<div class="perfumers-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px;">';
            foreach ($all_perfumers as $perfumer) {
                echo '<div class="perfumer-item" style="background: white; border: 1px solid #ddd; padding: 20px; border-radius: 5px;">';
                echo '<h3><a href="' . get_term_link($perfumer) . '">' . esc_html($perfumer->name) . '</a></h3>';
                echo '<p>Perfumes: ' . $perfumer->count . '</p>';
                if ($perfumer->description) {
                    echo '<p>' . esc_html($perfumer->description) . '</p>';
                }
                echo '</div>';
            }
            echo '</div>';
        } else {
            echo '<p>No perfumers found.</p>';
        }
        
        echo '</div>';
        return ob_get_clean();
    }
    
    public function filter_posts_where($where, $query) {
        global $wpdb;
        
        if (!is_admin() && $query->is_main_query() && is_post_type_archive('parfume')) {
            // Add search functionality
            if (!empty($_GET['s'])) {
                $search = sanitize_text_field($_GET['s']);
                $where .= $wpdb->prepare(" AND ({$wpdb->posts}.post_title LIKE %s OR {$wpdb->posts}.post_content LIKE %s)", 
                    '%' . $wpdb->esc_like($search) . '%', 
                    '%' . $wpdb->esc_like($search) . '%'
                );
            }
        }
        
        return $where;
    }
    
    public function add_meta_boxes() {
        add_meta_box(
            'parfume_details',
            __('Parfume Details', 'parfume-reviews'),
            array($this, 'render_details_meta_box'),
            'parfume',
            'normal',
            'high'
        );
        
        add_meta_box(
            'parfume_rating',
            __('Rating', 'parfume-reviews'),
            array($this, 'render_rating_meta_box'),
            'parfume',
            'side',
            'default'
        );
        
        add_meta_box(
            'parfume_stores',
            __('Where to Buy', 'parfume-reviews'),
            array($this, 'render_stores_meta_box'),
            'parfume',
            'normal',
            'default'
        );
    }
    
    public function render_details_meta_box($post) {
        wp_nonce_field('parfume_details_nonce', 'parfume_details_nonce');
        
        $gender = get_post_meta($post->ID, '_parfume_gender', true);
        $release_year = get_post_meta($post->ID, '_parfume_release_year', true);
        $longevity = get_post_meta($post->ID, '_parfume_longevity', true);
        $sillage = get_post_meta($post->ID, '_parfume_sillage', true);
        $bottle_size = get_post_meta($post->ID, '_parfume_bottle_size', true);
        
        echo '<table class="form-table"><tbody>';
        
        echo '<tr><th scope="row"><label for="parfume_gender">' . __('Gender', 'parfume-reviews') . '</label></th>';
        echo '<td><input type="text" id="parfume_gender" name="parfume_gender" value="' . esc_attr($gender) . '" class="regular-text" /></td></tr>';
        
        echo '<tr><th scope="row"><label for="parfume_release_year">' . __('Release Year', 'parfume-reviews') . '</label></th>';
        echo '<td><input type="text" id="parfume_release_year" name="parfume_release_year" value="' . esc_attr($release_year) . '" class="regular-text" /></td></tr>';
        
        echo '<tr><th scope="row"><label for="parfume_longevity">' . __('Longevity', 'parfume-reviews') . '</label></th>';
        echo '<td><input type="text" id="parfume_longevity" name="parfume_longevity" value="' . esc_attr($longevity) . '" class="regular-text" /></td></tr>';
        
        echo '<tr><th scope="row"><label for="parfume_sillage">' . __('Sillage', 'parfume-reviews') . '</label></th>';
        echo '<td><input type="text" id="parfume_sillage" name="parfume_sillage" value="' . esc_attr($sillage) . '" class="regular-text" /></td></tr>';
        
        echo '<tr><th scope="row"><label for="parfume_bottle_size">' . __('Bottle Size', 'parfume-reviews') . '</label></th>';
        echo '<td><input type="text" id="parfume_bottle_size" name="parfume_bottle_size" value="' . esc_attr($bottle_size) . '" class="regular-text" /></td></tr>';
        
        echo '</tbody></table>';
    }
    
    public function render_rating_meta_box($post) {
        wp_nonce_field('parfume_rating_nonce', 'parfume_rating_nonce');
        
        $rating = get_post_meta($post->ID, '_parfume_rating', true);
        $rating = !empty($rating) ? floatval($rating) : 0;
        
        echo '<p>';
        echo '<label for="parfume_rating">' . __('Rating (0-5):', 'parfume-reviews') . '</label><br>';
        echo '<input type="number" id="parfume_rating" name="parfume_rating" value="' . esc_attr($rating) . '" min="0" max="5" step="0.1" class="small-text" />';
        echo '</p>';
        
        if ($rating > 0) {
            echo '<div class="rating-preview">';
            echo '<strong>' . __('Preview:', 'parfume-reviews') . '</strong><br>';
            for ($i = 1; $i <= 5; $i++) {
                $class = $i <= round($rating) ? 'filled' : '';
                echo '<span class="star ' . $class . '">★</span>';
            }
            echo ' <span>(' . number_format($rating, 1) . '/5)</span>';
            echo '</div>';
        }
    }
    
    public function render_stores_meta_box($post) {
        wp_nonce_field('parfume_stores_nonce', 'parfume_stores_nonce');
        
        $stores = get_post_meta($post->ID, '_parfume_stores', true);
        $stores = !empty($stores) && is_array($stores) ? $stores : array();
        
        echo '<div id="parfume-stores-container">';
        
        if (!empty($stores)) {
            foreach ($stores as $index => $store) {
                $this->render_store_fields($index, $store);
            }
        }
        
        echo '</div>';
        
        echo '<p>';
        echo '<button type="button" id="add-store" class="button">' . __('Add Store', 'parfume-reviews') . '</button>';
        echo '</p>';
        
        // JavaScript template for new stores
        echo '<script type="text/template" id="store-template">';
        $this->render_store_fields('{{INDEX}}', array());
        echo '</script>';
        
        // Add some basic styling and JavaScript
        echo '<style>
        .store-item { border: 1px solid #ddd; padding: 15px; margin-bottom: 10px; background: #f9f9f9; }
        .store-item .store-header { font-weight: bold; margin-bottom: 10px; }
        .store-item .form-table th { width: 150px; }
        .remove-store { color: #a00; text-decoration: none; float: right; }
        .rating-preview .star { color: #ddd; font-size: 18px; }
        .rating-preview .star.filled { color: #ffb900; }
        </style>';
        
        echo '<script>
        document.addEventListener("DOMContentLoaded", function() {
            let storeIndex = ' . count($stores) . ';
            
            document.getElementById("add-store").addEventListener("click", function() {
                const template = document.getElementById("store-template").innerHTML;
                const html = template.replace(/\{\{INDEX\}\}/g, storeIndex);
                const container = document.getElementById("parfume-stores-container");
                container.insertAdjacentHTML("beforeend", html);
                storeIndex++;
            });
            
            document.addEventListener("click", function(e) {
                if (e.target.classList.contains("remove-store")) {
                    e.preventDefault();
                    if (confirm("' . __('Are you sure you want to remove this store?', 'parfume-reviews') . '")) {
                        e.target.closest(".store-item").remove();
                    }
                }
            });
            
            // Rating preview update
            const ratingInput = document.getElementById("parfume_rating");
            if (ratingInput) {
                ratingInput.addEventListener("input", function() {
                    const rating = parseFloat(this.value) || 0;
                    const preview = document.querySelector(".rating-preview");
                    if (preview) {
                        const stars = preview.querySelectorAll(".star");
                        stars.forEach((star, index) => {
                            star.classList.toggle("filled", index < Math.round(rating));
                        });
                        const ratingText = preview.querySelector("span:last-child");
                        if (ratingText) {
                            ratingText.textContent = "(" + rating.toFixed(1) + "/5)";
                        }
                    }
                });
            }
        });
        </script>';
    }
    
    private function render_store_fields($index, $store = array()) {
        $store = wp_parse_args($store, array(
            'name' => '',
            'logo' => '',
            'url' => '',
            'affiliate_url' => '',
            'affiliate_class' => '',
            'affiliate_rel' => 'nofollow',
            'affiliate_target' => '_blank',
            'affiliate_anchor' => '',
            'promo_code' => '',
            'price' => '',
            'size' => '',
        ));
        
        echo '<div class="store-item">';
        echo '<div class="store-header">';
        echo __('Store', 'parfume-reviews') . ' #' . ($index + 1);
        if ($index !== '{{INDEX}}') {
            echo '<a href="#" class="remove-store">' . __('Remove', 'parfume-reviews') . '</a>';
        }
        echo '</div>';
        
        echo '<table class="form-table"><tbody>';
        
        $fields = array(
            'name' => __('Store Name', 'parfume-reviews'),
            'logo' => __('Logo URL', 'parfume-reviews'),
            'url' => __('Product URL', 'parfume-reviews'),
            'affiliate_url' => __('Affiliate URL', 'parfume-reviews'),
            'affiliate_class' => __('Affiliate CSS Class', 'parfume-reviews'),
            'affiliate_rel' => __('Affiliate Rel Attribute', 'parfume-reviews'),
            'affiliate_target' => __('Affiliate Target', 'parfume-reviews'),
            'affiliate_anchor' => __('Affiliate Link Text', 'parfume-reviews'),
            'promo_code' => __('Promo Code', 'parfume-reviews'),
            'price' => __('Price', 'parfume-reviews'),
            'size' => __('Size', 'parfume-reviews'),
        );
        
        foreach ($fields as $field => $label) {
            echo '<tr>';
            echo '<th scope="row"><label for="store_' . $index . '_' . $field . '">' . $label . '</label></th>';
            echo '<td><input type="text" id="store_' . $index . '_' . $field . '" name="parfume_stores[' . $index . '][' . $field . ']" value="' . esc_attr($store[$field]) . '" class="regular-text" /></td>';
            echo '</tr>';
        }
        
        echo '</tbody></table>';
        echo '</div>';
    }
    
    public function save_meta_boxes($post_id) {
        // Check if this is an autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        // Check the post type
        if (get_post_type($post_id) !== 'parfume') {
            return;
        }
        
        // Check user permissions
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Save details
        if (isset($_POST['parfume_details_nonce']) && wp_verify_nonce($_POST['parfume_details_nonce'], 'parfume_details_nonce')) {
            $fields = array('parfume_gender', 'parfume_release_year', 'parfume_longevity', 'parfume_sillage', 'parfume_bottle_size');
            
            foreach ($fields as $field) {
                if (isset($_POST[$field])) {
                    $value = sanitize_text_field($_POST[$field]);
                    $meta_key = '_' . $field;
                    update_post_meta($post_id, $meta_key, $value);
                }
            }
        }
        
        // Save rating
        if (isset($_POST['parfume_rating_nonce']) && wp_verify_nonce($_POST['parfume_rating_nonce'], 'parfume_rating_nonce')) {
            if (isset($_POST['parfume_rating'])) {
                $rating = floatval($_POST['parfume_rating']);
                $rating = max(0, min(5, $rating)); // Ensure rating is between 0 and 5
                update_post_meta($post_id, '_parfume_rating', $rating);
            }
        }
        
        // Save stores
        if (isset($_POST['parfume_stores_nonce']) && wp_verify_nonce($_POST['parfume_stores_nonce'], 'parfume_stores_nonce')) {
            if (isset($_POST['parfume_stores']) && is_array($_POST['parfume_stores'])) {
                $stores = array();
                
                foreach ($_POST['parfume_stores'] as $store_data) {
                    // Skip empty stores
                    if (empty($store_data['name'])) continue;
                    
                    $store = array();
                    $fields = array('name', 'logo', 'url', 'affiliate_url', 'affiliate_class', 'affiliate_rel', 'affiliate_target', 'affiliate_anchor', 'promo_code', 'price', 'size');
                    
                    foreach ($fields as $field) {
                        if (in_array($field, array('logo', 'url', 'affiliate_url'))) {
                            $store[$field] = !empty($store_data[$field]) ? esc_url_raw($store_data[$field]) : '';
                        } else {
                            $store[$field] = isset($store_data[$field]) ? sanitize_text_field($store_data[$field]) : '';
                        }
                    }
                    
                    $store['last_updated'] = current_time('mysql');
                    $stores[] = $store;
                }
                
                update_post_meta($post_id, '_parfume_stores', $stores);
            } else {
                delete_post_meta($post_id, '_parfume_stores');
            }
        }
    }
    
    public function template_loader($template) {
        $plugin_templates = array(
            'single-parfume.php' => is_singular('parfume'),
            'archive-parfume.php' => is_post_type_archive('parfume'),
            'taxonomy-aroma_type.php' => is_tax('aroma_type'),
            'taxonomy-marki.php' => is_tax('marki'),
            'taxonomy-season.php' => is_tax('season'),
            'taxonomy-intensity.php' => is_tax('intensity'),
            'taxonomy-notes.php' => is_tax('notes'),
            'taxonomy-perfumer.php' => is_tax('perfumer'),
            'taxonomy-gender.php' => is_tax('gender'),
        );
        
        foreach ($plugin_templates as $template_name => $condition) {
            if ($condition) {
                $plugin_template = PARFUME_REVIEWS_PLUGIN_DIR . 'templates/' . $template_name;
                if (file_exists($plugin_template)) {
                    return $plugin_template;
                }
            }
        }
        
        return $template;
    }
    
    public function enqueue_scripts() {
        if (is_singular('parfume') || is_post_type_archive('parfume') || is_tax(array('aroma_type', 'marki', 'season', 'intensity', 'notes', 'perfumer', 'gender'))) {
            wp_enqueue_style(
                'parfume-reviews-frontend',
                PARFUME_REVIEWS_PLUGIN_URL . 'assets/css/frontend.css',
                array(),
                PARFUME_REVIEWS_VERSION
            );
            
            wp_enqueue_script(
                'parfume-reviews-frontend',
                PARFUME_REVIEWS_PLUGIN_URL . 'assets/js/frontend.js',
                array('jquery'),
                PARFUME_REVIEWS_VERSION,
                true
            );
            
            wp_localize_script('parfume-reviews-frontend', 'parfumeReviews', array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('parfume-reviews-nonce'),
                'strings' => array(
                    'loading' => __('Loading...', 'parfume-reviews'),
                    'error' => __('An error occurred', 'parfume-reviews'),
                    'success' => __('Success', 'parfume-reviews'),
                ),
            ));
        }
    }
    
    public function admin_enqueue_scripts($hook) {
        global $post_type;
        
        if (($hook === 'post-new.php' || $hook === 'post.php') && $post_type === 'parfume') {
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
            
            wp_localize_script('parfume-reviews-admin', 'parfumeReviewsAdmin', array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('parfume-reviews-admin-nonce'),
                'strings' => array(
                    'confirmRemove' => __('Are you sure you want to remove this item?', 'parfume-reviews'),
                    'addNew' => __('Add New', 'parfume-reviews'),
                ),
            ));
        }
    }
}