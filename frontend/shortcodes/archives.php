<?php
/**
 * Archives Shortcodes
 * 
 * @package Parfume_Reviews
 * @subpackage Frontend\Shortcodes
 * @since 1.0.0
 */

namespace Parfume_Reviews\Frontend\Shortcodes;

use Parfume_Reviews\Utils\Base_Classes\Shortcode_Base;
use Parfume_Reviews\Utils\Helpers;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Archives shortcodes handler
 */
class Archives extends Shortcode_Base {
    
    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct();
        $this->register_shortcodes();
    }
    
    /**
     * Register all archive shortcodes
     */
    protected function register_shortcodes() {
        add_shortcode('all_brands_archive', array($this, 'all_brands_archive'));
        add_shortcode('all_notes_archive', array($this, 'all_notes_archive'));
        add_shortcode('all_perfumers_archive', array($this, 'all_perfumers_archive'));
        add_shortcode('parfume_latest', array($this, 'latest_perfumes'));
        add_shortcode('parfume_featured', array($this, 'featured_perfumes'));
        add_shortcode('brand_showcase', array($this, 'brand_showcase'));
        add_shortcode('notes_by_category', array($this, 'notes_by_category'));
        add_shortcode('perfumer_spotlight', array($this, 'perfumer_spotlight'));
        add_shortcode('seasonal_perfumes', array($this, 'seasonal_perfumes'));
    }
    
    /**
     * All brands archive shortcode
     */
    public function all_brands_archive($atts) {
        $atts = $this->parse_attributes($atts, array(
            'columns' => 3,
            'show_count' => true,
            'show_image' => true,
            'orderby' => 'name',
            'order' => 'ASC',
            'hide_empty' => false,
            'show_alphabet' => true,
        ));
        
        ob_start();
        
        try {
            // Get all brands
            $brands = get_terms(array(
                'taxonomy' => 'marki',
                'hide_empty' => $this->parse_bool($atts['hide_empty']),
                'orderby' => sanitize_text_field($atts['orderby']),
                'order' => sanitize_text_field($atts['order']),
            ));
            
            if (is_wp_error($brands) || empty($brands)) {
                return $this->render_empty_message(__('No brands found.', 'parfume-reviews'));
            }
            
            // Group brands by letter if alphabet navigation is enabled
            $brands_by_letter = array();
            $available_letters = array();
            
            if ($this->parse_bool($atts['show_alphabet'])) {
                foreach ($brands as $brand) {
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
                
                sort($available_letters);
            }
            
            $this->render_brands_archive($brands, $brands_by_letter, $available_letters, $atts);
            
        } catch (Exception $e) {
            if (WP_DEBUG) {
                return $this->render_error_message('Error loading brands: ' . $e->getMessage());
            }
            return $this->render_empty_message(__('Unable to load brands at this time.', 'parfume-reviews'));
        }
        
        return ob_get_clean();
    }
    
    /**
     * All notes archive shortcode
     */
    public function all_notes_archive($atts) {
        $atts = $this->parse_attributes($atts, array(
            'columns' => 4,
            'show_count' => true,
            'show_group' => true,
            'orderby' => 'name',
            'order' => 'ASC',
            'hide_empty' => false,
            'group_by' => 'category', // category, name, none
        ));
        
        ob_start();
        
        try {
            // Get all notes
            $notes = get_terms(array(
                'taxonomy' => 'notes',
                'hide_empty' => $this->parse_bool($atts['hide_empty']),
                'orderby' => sanitize_text_field($atts['orderby']),
                'order' => sanitize_text_field($atts['order']),
            ));
            
            if (is_wp_error($notes) || empty($notes)) {
                return $this->render_empty_message(__('No notes found.', 'parfume-reviews'));
            }
            
            $this->render_notes_archive($notes, $atts);
            
        } catch (Exception $e) {
            if (WP_DEBUG) {
                return $this->render_error_message('Error loading notes: ' . $e->getMessage());
            }
            return $this->render_empty_message(__('Unable to load notes at this time.', 'parfume-reviews'));
        }
        
        return ob_get_clean();
    }
    
    /**
     * All perfumers archive shortcode
     */
    public function all_perfumers_archive($atts) {
        $atts = $this->parse_attributes($atts, array(
            'columns' => 3,
            'show_count' => true,
            'show_image' => true,
            'show_bio' => true,
            'orderby' => 'name',
            'order' => 'ASC',
            'hide_empty' => false,
            'show_alphabet' => true,
        ));
        
        ob_start();
        
        try {
            // Get all perfumers
            $perfumers = get_terms(array(
                'taxonomy' => 'perfumer',
                'hide_empty' => $this->parse_bool($atts['hide_empty']),
                'orderby' => sanitize_text_field($atts['orderby']),
                'order' => sanitize_text_field($atts['order']),
            ));
            
            if (is_wp_error($perfumers) || empty($perfumers)) {
                return $this->render_empty_message(__('No perfumers found.', 'parfume-reviews'));
            }
            
            $this->render_perfumers_archive($perfumers, $atts);
            
        } catch (Exception $e) {
            if (WP_DEBUG) {
                return $this->render_error_message('Error loading perfumers: ' . $e->getMessage());
            }
            return $this->render_empty_message(__('Unable to load perfumers at this time.', 'parfume-reviews'));
        }
        
        return ob_get_clean();
    }
    
    /**
     * Latest perfumes shortcode
     */
    public function latest_perfumes($atts) {
        $atts = $this->parse_attributes($atts, array(
            'limit' => 8,
            'columns' => 4,
            'show_excerpt' => true,
            'show_rating' => true,
            'show_price' => true,
            'show_brand' => true,
            'orderby' => 'date',
            'order' => 'DESC',
        ));
        
        ob_start();
        
        try {
            $args = array(
                'post_type' => 'parfume',
                'posts_per_page' => intval($atts['limit']),
                'post_status' => 'publish',
                'orderby' => sanitize_text_field($atts['orderby']),
                'order' => sanitize_text_field($atts['order']),
            );
            
            $perfumes = new \WP_Query($args);
            
            if (!$perfumes->have_posts()) {
                return $this->render_empty_message(__('No perfumes found.', 'parfume-reviews'));
            }
            
            $this->render_perfume_grid($perfumes, $atts, 'latest-perfumes');
            wp_reset_postdata();
            
        } catch (Exception $e) {
            if (WP_DEBUG) {
                return $this->render_error_message('Error loading latest perfumes: ' . $e->getMessage());
            }
            return $this->render_empty_message(__('Unable to load perfumes at this time.', 'parfume-reviews'));
        }
        
        return ob_get_clean();
    }
    
    /**
     * Featured perfumes shortcode
     */
    public function featured_perfumes($atts) {
        $atts = $this->parse_attributes($atts, array(
            'limit' => 6,
            'columns' => 3,
            'show_excerpt' => true,
            'show_rating' => true,
            'show_price' => true,
            'show_brand' => true,
            'meta_key' => '_parfume_featured',
            'meta_value' => '1',
        ));
        
        ob_start();
        
        try {
            $args = array(
                'post_type' => 'parfume',
                'posts_per_page' => intval($atts['limit']),
                'post_status' => 'publish',
                'meta_query' => array(
                    array(
                        'key' => sanitize_text_field($atts['meta_key']),
                        'value' => sanitize_text_field($atts['meta_value']),
                        'compare' => '=',
                    ),
                ),
                'orderby' => 'menu_order',
                'order' => 'ASC',
            );
            
            $perfumes = new \WP_Query($args);
            
            if (!$perfumes->have_posts()) {
                return $this->render_empty_message(__('No featured perfumes found.', 'parfume-reviews'));
            }
            
            $this->render_perfume_grid($perfumes, $atts, 'featured-perfumes');
            wp_reset_postdata();
            
        } catch (Exception $e) {
            if (WP_DEBUG) {
                return $this->render_error_message('Error loading featured perfumes: ' . $e->getMessage());
            }
            return $this->render_empty_message(__('Unable to load featured perfumes at this time.', 'parfume-reviews'));
        }
        
        return ob_get_clean();
    }
    
    /**
     * Brand showcase shortcode
     */
    public function brand_showcase($atts) {
        $atts = $this->parse_attributes($atts, array(
            'brands' => '',
            'limit' => 6,
            'columns' => 3,
            'show_logo' => true,
            'show_count' => true,
            'show_description' => true,
        ));
        
        ob_start();
        
        try {
            $brand_slugs = array_filter(array_map('trim', explode(',', $atts['brands'])));
            
            if (empty($brand_slugs)) {
                return $this->render_empty_message(__('No brands specified.', 'parfume-reviews'));
            }
            
            $brands = get_terms(array(
                'taxonomy' => 'marki',
                'slug' => $brand_slugs,
                'hide_empty' => false,
            ));
            
            if (is_wp_error($brands) || empty($brands)) {
                return $this->render_empty_message(__('No brands found.', 'parfume-reviews'));
            }
            
            $this->render_brand_showcase($brands, $atts);
            
        } catch (Exception $e) {
            if (WP_DEBUG) {
                return $this->render_error_message('Error loading brand showcase: ' . $e->getMessage());
            }
            return $this->render_empty_message(__('Unable to load brand showcase at this time.', 'parfume-reviews'));
        }
        
        return ob_get_clean();
    }
    
    /**
     * Notes by category shortcode
     */
    public function notes_by_category($atts) {
        $atts = $this->parse_attributes($atts, array(
            'category' => '',
            'limit' => 12,
            'columns' => 4,
            'show_count' => true,
            'orderby' => 'count',
            'order' => 'DESC',
        ));
        
        ob_start();
        
        try {
            $args = array(
                'taxonomy' => 'notes',
                'hide_empty' => true,
                'orderby' => sanitize_text_field($atts['orderby']),
                'order' => sanitize_text_field($atts['order']),
                'number' => intval($atts['limit']),
            );
            
            if (!empty($atts['category'])) {
                $args['meta_query'] = array(
                    array(
                        'key' => 'note_group',
                        'value' => sanitize_text_field($atts['category']),
                        'compare' => '=',
                    ),
                );
            }
            
            $notes = get_terms($args);
            
            if (is_wp_error($notes) || empty($notes)) {
                return $this->render_empty_message(__('No notes found in this category.', 'parfume-reviews'));
            }
            
            $this->render_notes_by_category($notes, $atts);
            
        } catch (Exception $e) {
            if (WP_DEBUG) {
                return $this->render_error_message('Error loading notes by category: ' . $e->getMessage());
            }
            return $this->render_empty_message(__('Unable to load notes at this time.', 'parfume-reviews'));
        }
        
        return ob_get_clean();
    }
    
    /**
     * Perfumer spotlight shortcode
     */
    public function perfumer_spotlight($atts) {
        $atts = $this->parse_attributes($atts, array(
            'perfumer' => '',
            'show_bio' => true,
            'show_perfumes' => true,
            'perfumes_limit' => 6,
            'perfumes_columns' => 3,
        ));
        
        ob_start();
        
        try {
            if (empty($atts['perfumer'])) {
                return $this->render_empty_message(__('No perfumer specified.', 'parfume-reviews'));
            }
            
            $perfumer = get_term_by('slug', sanitize_text_field($atts['perfumer']), 'perfumer');
            
            if (!$perfumer || is_wp_error($perfumer)) {
                return $this->render_empty_message(__('Perfumer not found.', 'parfume-reviews'));
            }
            
            $this->render_perfumer_spotlight($perfumer, $atts);
            
        } catch (Exception $e) {
            if (WP_DEBUG) {
                return $this->render_error_message('Error loading perfumer spotlight: ' . $e->getMessage());
            }
            return $this->render_empty_message(__('Unable to load perfumer information at this time.', 'parfume-reviews'));
        }
        
        return ob_get_clean();
    }
    
    /**
     * Seasonal perfumes shortcode
     */
    public function seasonal_perfumes($atts) {
        $atts = $this->parse_attributes($atts, array(
            'season' => 'current',
            'limit' => 8,
            'columns' => 4,
            'show_rating' => true,
            'show_brand' => true,
            'orderby' => 'menu_order',
            'order' => 'ASC',
        ));
        
        ob_start();
        
        try {
            $season = $this->get_season($atts['season']);
            
            if (!$season) {
                return $this->render_empty_message(__('Invalid season specified.', 'parfume-reviews'));
            }
            
            $args = array(
                'post_type' => 'parfume',
                'posts_per_page' => intval($atts['limit']),
                'post_status' => 'publish',
                'tax_query' => array(
                    array(
                        'taxonomy' => 'season',
                        'field' => 'slug',
                        'terms' => $season,
                    ),
                ),
                'orderby' => sanitize_text_field($atts['orderby']),
                'order' => sanitize_text_field($atts['order']),
            );
            
            $perfumes = new \WP_Query($args);
            
            if (!$perfumes->have_posts()) {
                return $this->render_empty_message(sprintf(__('No perfumes found for %s season.', 'parfume-reviews'), $season));
            }
            
            $this->render_perfume_grid($perfumes, $atts, 'seasonal-perfumes');
            wp_reset_postdata();
            
        } catch (Exception $e) {
            if (WP_DEBUG) {
                return $this->render_error_message('Error loading seasonal perfumes: ' . $e->getMessage());
            }
            return $this->render_empty_message(__('Unable to load seasonal perfumes at this time.', 'parfume-reviews'));
        }
        
        return ob_get_clean();
    }
    
    /**
     * Render brands archive
     */
    private function render_brands_archive($brands, $brands_by_letter, $available_letters, $atts) {
        ?>
        <div class="parfume-brands-archive">
            <div class="archive-header">
                <h2 class="archive-title"><?php _e('All Brands', 'parfume-reviews'); ?></h2>
                <div class="archive-stats">
                    <span class="total-brands"><?php printf(__('%d Total Brands', 'parfume-reviews'), count($brands)); ?></span>
                </div>
            </div>
            
            <?php if ($this->parse_bool($atts['show_alphabet']) && !empty($available_letters)): ?>
                <div class="alphabet-navigation">
                    <?php 
                    $full_alphabet = array_merge(range('A', 'Z'), array('А', 'Б', 'В', 'Г', 'Д', 'Е', 'Ж', 'З', 'И', 'Й', 'К', 'Л', 'М', 'Н', 'О', 'П', 'Р', 'С', 'Т', 'У', 'Ф', 'Х', 'Ц', 'Ч', 'Ш', 'Щ', 'Ъ', 'Ь', 'Ю', 'Я'), array('#'));
                    
                    foreach ($full_alphabet as $letter): 
                        $is_available = in_array($letter, $available_letters);
                        $class = $is_available ? 'letter-link active' : 'letter-link inactive';
                    ?>
                        <?php if ($is_available): ?>
                            <a href="#letter-<?php echo esc_attr(strtolower($letter)); ?>" class="<?php echo esc_attr($class); ?>">
                                <?php echo esc_html($letter); ?>
                            </a>
                        <?php else: ?>
                            <span class="<?php echo esc_attr($class); ?>">
                                <?php echo esc_html($letter); ?>
                            </span>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
                
                <?php foreach ($available_letters as $letter): ?>
                    <div class="letter-section" id="letter-<?php echo esc_attr(strtolower($letter)); ?>">
                        <h3 class="letter-heading"><?php echo esc_html($letter); ?></h3>
                        <div class="brands-grid columns-<?php echo esc_attr($atts['columns']); ?>">
                            <?php foreach ($brands_by_letter[$letter] as $brand): ?>
                                <?php $this->render_brand_card($brand, $atts); ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="brands-grid columns-<?php echo esc_attr($atts['columns']); ?>">
                    <?php foreach ($brands as $brand): ?>
                        <?php $this->render_brand_card($brand, $atts); ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * Render notes archive
     */
    private function render_notes_archive($notes, $atts) {
        ?>
        <div class="parfume-notes-archive">
            <div class="archive-header">
                <h2 class="archive-title"><?php _e('All Fragrance Notes', 'parfume-reviews'); ?></h2>
                <div class="archive-stats">
                    <span class="total-notes"><?php printf(__('%d Total Notes', 'parfume-reviews'), count($notes)); ?></span>
                </div>
            </div>
            
            <?php if ($atts['group_by'] === 'category'): ?>
                <?php $this->render_notes_by_groups($notes, $atts); ?>
            <?php else: ?>
                <div class="notes-grid columns-<?php echo esc_attr($atts['columns']); ?>">
                    <?php foreach ($notes as $note): ?>
                        <?php $this->render_note_card($note, $atts); ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * Render notes by groups
     */
    private function render_notes_by_groups($notes, $atts) {
        $notes_by_group = array();
        
        foreach ($notes as $note) {
            $group = get_term_meta($note->term_id, 'note_group', true);
            if (empty($group)) {
                $group = __('Other', 'parfume-reviews');
            }
            
            if (!isset($notes_by_group[$group])) {
                $notes_by_group[$group] = array();
            }
            $notes_by_group[$group][] = $note;
        }
        
        ksort($notes_by_group);
        
        foreach ($notes_by_group as $group => $group_notes):
        ?>
            <div class="notes-group">
                <h3 class="group-title"><?php echo esc_html($group); ?></h3>
                <div class="notes-grid columns-<?php echo esc_attr($atts['columns']); ?>">
                    <?php foreach ($group_notes as $note): ?>
                        <?php $this->render_note_card($note, $atts); ?>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php
        endforeach;
    }
    
    /**
     * Render perfumers archive
     */
    private function render_perfumers_archive($perfumers, $atts) {
        ?>
        <div class="parfume-perfumers-archive">
            <div class="archive-header">
                <h2 class="archive-title"><?php _e('All Perfumers', 'parfume-reviews'); ?></h2>
                <div class="archive-stats">
                    <span class="total-perfumers"><?php printf(__('%d Total Perfumers', 'parfume-reviews'), count($perfumers)); ?></span>
                </div>
            </div>
            
            <div class="perfumers-grid columns-<?php echo esc_attr($atts['columns']); ?>">
                <?php foreach ($perfumers as $perfumer): ?>
                    <?php $this->render_perfumer_card($perfumer, $atts); ?>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render brand card
     */
    private function render_brand_card($brand, $atts) {
        $brand_image_id = get_term_meta($brand->term_id, 'marki-image-id', true);
        ?>
        <div class="brand-card">
            <a href="<?php echo esc_url(get_term_link($brand)); ?>" class="brand-link">
                <?php if ($this->parse_bool($atts['show_image']) && $brand_image_id): ?>
                    <div class="brand-image">
                        <?php echo wp_get_attachment_image($brand_image_id, 'thumbnail', false, array('alt' => $brand->name)); ?>
                    </div>
                <?php endif; ?>
                
                <div class="brand-info">
                    <h4 class="brand-name"><?php echo esc_html($brand->name); ?></h4>
                    
                    <?php if ($this->parse_bool($atts['show_count'])): ?>
                        <span class="brand-count">
                            <?php printf(_n('%d perfume', '%d perfumes', $brand->count, 'parfume-reviews'), $brand->count); ?>
                        </span>
                    <?php endif; ?>
                    
                    <?php if ($brand->description): ?>
                        <p class="brand-description"><?php echo wp_trim_words(esc_html($brand->description), 15); ?></p>
                    <?php endif; ?>
                </div>
            </a>
        </div>
        <?php
    }
    
    /**
     * Render note card
     */
    private function render_note_card($note, $atts) {
        $note_image_id = get_term_meta($note->term_id, 'notes-image-id', true);
        $note_group = get_term_meta($note->term_id, 'note_group', true);
        ?>
        <div class="note-card">
            <a href="<?php echo esc_url(get_term_link($note)); ?>" class="note-link">
                <?php if ($note_image_id): ?>
                    <div class="note-image">
                        <?php echo wp_get_attachment_image($note_image_id, 'thumbnail', false, array('alt' => $note->name)); ?>
                    </div>
                <?php endif; ?>
                
                <div class="note-info">
                    <h4 class="note-name"><?php echo esc_html($note->name); ?></h4>
                    
                    <?php if ($this->parse_bool($atts['show_count'])): ?>
                        <span class="note-count">
                            <?php printf(_n('%d perfume', '%d perfumes', $note->count, 'parfume-reviews'), $note->count); ?>
                        </span>
                    <?php endif; ?>
                    
                    <?php if ($this->parse_bool($atts['show_group']) && $note_group): ?>
                        <span class="note-group"><?php echo esc_html($note_group); ?></span>
                    <?php endif; ?>
                    
                    <?php if ($note->description): ?>
                        <p class="note-description"><?php echo wp_trim_words(esc_html($note->description), 12); ?></p>
                    <?php endif; ?>
                </div>
            </a>
        </div>
        <?php
    }
    
    /**
     * Render perfumer card
     */
    private function render_perfumer_card($perfumer, $atts) {
        $perfumer_image_id = get_term_meta($perfumer->term_id, 'perfumer-image-id', true);
        ?>
        <div class="perfumer-card">
            <a href="<?php echo esc_url(get_term_link($perfumer)); ?>" class="perfumer-link">
                <?php if ($this->parse_bool($atts['show_image']) && $perfumer_image_id): ?>
                    <div class="perfumer-image">
                        <?php echo wp_get_attachment_image($perfumer_image_id, 'thumbnail', false, array('alt' => $perfumer->name)); ?>
                    </div>
                <?php endif; ?>
                
                <div class="perfumer-info">
                    <h4 class="perfumer-name"><?php echo esc_html($perfumer->name); ?></h4>
                    
                    <?php if ($this->parse_bool($atts['show_count'])): ?>
                        <span class="perfumer-count">
                            <?php printf(_n('%d creation', '%d creations', $perfumer->count, 'parfume-reviews'), $perfumer->count); ?>
                        </span>
                    <?php endif; ?>
                    
                    <?php if ($this->parse_bool($atts['show_bio']) && $perfumer->description): ?>
                        <p class="perfumer-bio"><?php echo wp_trim_words(esc_html($perfumer->description), 20); ?></p>
                    <?php endif; ?>
                </div>
            </a>
        </div>
        <?php
    }
    
    /**
     * Render perfume grid
     */
    private function render_perfume_grid($perfumes, $atts, $wrapper_class = '') {
        ?>
        <div class="parfume-grid <?php echo esc_attr($wrapper_class); ?> columns-<?php echo esc_attr($atts['columns']); ?>">
            <?php while ($perfumes->have_posts()): $perfumes->the_post(); ?>
                <div class="parfume-card">
                    <a href="<?php the_permalink(); ?>" class="parfume-link">
                        <?php if (has_post_thumbnail()): ?>
                            <div class="parfume-image">
                                <?php the_post_thumbnail('medium'); ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="parfume-info">
                            <h4 class="parfume-title"><?php the_title(); ?></h4>
                            
                            <?php if ($this->parse_bool($atts['show_brand'])): ?>
                                <?php 
                                $brands = wp_get_post_terms(get_the_ID(), 'marki', array('fields' => 'names'));
                                if (!empty($brands) && !is_wp_error($brands)): 
                                ?>
                                    <span class="parfume-brand"><?php echo esc_html($brands[0]); ?></span>
                                <?php endif; ?>
                            <?php endif; ?>
                            
                            <?php if ($this->parse_bool($atts['show_rating'])): ?>
                                <?php 
                                $rating = get_post_meta(get_the_ID(), '_parfume_rating', true);
                                if (!empty($rating)): 
                                ?>
                                    <div class="parfume-rating">
                                        <?php echo Helpers::get_rating_stars($rating); ?>
                                        <span class="rating-number"><?php echo number_format(floatval($rating), 1); ?></span>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>
                            
                            <?php if ($this->parse_bool($atts['show_price'])): ?>
                                <?php 
                                $price = Helpers::get_lowest_price(get_the_ID());
                                if ($price): 
                                ?>
                                    <div class="parfume-price">
                                        <span class="price-label"><?php _e('from', 'parfume-reviews'); ?></span>
                                        <span class="price-value"><?php echo esc_html($price['price']); ?></span>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>
                            
                            <?php if ($this->parse_bool($atts['show_excerpt'])): ?>
                                <p class="parfume-excerpt"><?php echo wp_trim_words(get_the_excerpt(), 15); ?></p>
                            <?php endif; ?>
                        </div>
                    </a>
                </div>
            <?php endwhile; ?>
        </div>
        <?php
    }
    
    /**
     * Get season based on parameter
     */
    private function get_season($season_param) {
        if ($season_param === 'current') {
            $month = date('n');
            
            if (in_array($month, array(12, 1, 2))) {
                return 'winter';
            } elseif (in_array($month, array(3, 4, 5))) {
                return 'spring';
            } elseif (in_array($month, array(6, 7, 8))) {
                return 'summer';
            } else {
                return 'autumn';
            }
        }
        
        $valid_seasons = array('spring', 'summer', 'autumn', 'winter');
        
        return in_array($season_param, $valid_seasons) ? $season_param : false;
    }
    
    /**
     * Render brand showcase
     */
    private function render_brand_showcase($brands, $atts) {
        ?>
        <div class="brand-showcase">
            <div class="brands-grid columns-<?php echo esc_attr($atts['columns']); ?>">
                <?php foreach ($brands as $brand): ?>
                    <?php $this->render_brand_card($brand, $atts); ?>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render notes by category
     */
    private function render_notes_by_category($notes, $atts) {
        ?>
        <div class="notes-by-category">
            <div class="notes-grid columns-<?php echo esc_attr($atts['columns']); ?>">
                <?php foreach ($notes as $note): ?>
                    <?php $this->render_note_card($note, $atts); ?>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render perfumer spotlight
     */
    private function render_perfumer_spotlight($perfumer, $atts) {
        $perfumer_image_id = get_term_meta($perfumer->term_id, 'perfumer-image-id', true);
        ?>
        <div class="perfumer-spotlight">
            <div class="perfumer-header">
                <?php if ($perfumer_image_id): ?>
                    <div class="perfumer-image">
                        <?php echo wp_get_attachment_image($perfumer_image_id, 'medium', false, array('alt' => $perfumer->name)); ?>
                    </div>
                <?php endif; ?>
                
                <div class="perfumer-details">
                    <h3 class="perfumer-name"><?php echo esc_html($perfumer->name); ?></h3>
                    
                    <?php if ($this->parse_bool($atts['show_bio']) && $perfumer->description): ?>
                        <div class="perfumer-bio">
                            <?php echo wpautop(esc_html($perfumer->description)); ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if ($this->parse_bool($atts['show_perfumes'])): ?>
                <?php
                $perfumes_args = array(
                    'post_type' => 'parfume',
                    'posts_per_page' => intval($atts['perfumes_limit']),
                    'post_status' => 'publish',
                    'tax_query' => array(
                        array(
                            'taxonomy' => 'perfumer',
                            'field' => 'term_id',
                            'terms' => $perfumer->term_id,
                        ),
                    ),
                    'orderby' => 'menu_order',
                    'order' => 'ASC',
                );
                
                $perfumes = new \WP_Query($perfumes_args);
                
                if ($perfumes->have_posts()):
                ?>
                    <div class="perfumer-perfumes">
                        <h4><?php printf(__('Creations by %s', 'parfume-reviews'), esc_html($perfumer->name)); ?></h4>
                        
                        <div class="parfume-grid columns-<?php echo esc_attr($atts['perfumes_columns']); ?>">
                            <?php while ($perfumes->have_posts()): $perfumes->the_post(); ?>
                                <div class="parfume-card">
                                    <a href="<?php the_permalink(); ?>" class="parfume-link">
                                        <?php if (has_post_thumbnail()): ?>
                                            <div class="parfume-image">
                                                <?php the_post_thumbnail('medium'); ?>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <div class="parfume-info">
                                            <h5 class="parfume-title"><?php the_title(); ?></h5>
                                            
                                            <?php 
                                            $rating = get_post_meta(get_the_ID(), '_parfume_rating', true);
                                            if (!empty($rating)): 
                                            ?>
                                                <div class="parfume-rating">
                                                    <?php echo Helpers::get_rating_stars($rating); ?>
                                                    <span class="rating-number"><?php echo number_format(floatval($rating), 1); ?></span>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </a>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    </div>
                    <?php 
                    wp_reset_postdata();
                endif;
                ?>
            <?php endif; ?>
        </div>
        <?php
    }
}

// Initialize the class
new Archives();