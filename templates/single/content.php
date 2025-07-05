<?php
/**
 * Single Parfume Content
 * 
 * @package ParfumeReviews
 * @subpackage Templates\Single
 */

namespace ParfumeReviews\Templates\Single;

if (!defined('ABSPATH')) {
    exit;
}

class Content {
    
    public function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
    }
    
    /**
     * Render single parfume content
     */
    public static function render() {
        global $post;
        
        if ($post->post_type !== 'parfume') {
            return;
        }
        
        $meta_data = self::get_meta_data($post->ID);
        $taxonomy_data = self::get_taxonomy_data($post->ID);
        
        ob_start();
        ?>
        <div class="parfume-single-content">
            <?php self::render_tabs($post->ID, $meta_data, $taxonomy_data); ?>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Render content tabs
     */
    private static function render_tabs($post_id, $meta_data, $taxonomy_data) {
        ?>
        <div class="parfume-tabs">
            <ul class="tabs-nav">
                <li><a href="#description" class="active"><?php _e('Description', 'parfume-reviews'); ?></a></li>
                <li><a href="#notes"><?php _e('Notes', 'parfume-reviews'); ?></a></li>
                <?php if (!empty($taxonomy_data['perfumer'])): ?>
                    <li><a href="#perfumer"><?php _e('Perfumer', 'parfume-reviews'); ?></a></li>
                <?php endif; ?>
                <?php if (array_sum($meta_data['aroma_chart']) > 0): ?>
                    <li><a href="#aroma-chart"><?php _e('Aroma Chart', 'parfume-reviews'); ?></a></li>
                <?php endif; ?>
                <?php if (!empty($meta_data['pros']) || !empty($meta_data['cons'])): ?>
                    <li><a href="#pros-cons"><?php _e('Pros & Cons', 'parfume-reviews'); ?></a></li>
                <?php endif; ?>
                <li><a href="#reviews"><?php _e('Reviews', 'parfume-reviews'); ?></a></li>
            </ul>
            
            <div class="tabs-content">
                <?php self::render_description_tab($post_id); ?>
                <?php self::render_notes_tab($post_id, $taxonomy_data); ?>
                <?php if (!empty($taxonomy_data['perfumer'])): ?>
                    <?php self::render_perfumer_tab($post_id, $taxonomy_data['perfumer']); ?>
                <?php endif; ?>
                <?php if (array_sum($meta_data['aroma_chart']) > 0): ?>
                    <?php self::render_aroma_chart_tab($meta_data['aroma_chart']); ?>
                <?php endif; ?>
                <?php if (!empty($meta_data['pros']) || !empty($meta_data['cons'])): ?>
                    <?php self::render_pros_cons_tab($meta_data); ?>
                <?php endif; ?>
                <?php self::render_reviews_tab($post_id); ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render description tab
     */
    private static function render_description_tab($post_id) {
        ?>
        <div id="description" class="tab-panel">
            <div class="parfume-description">
                <?php 
                $content = get_post_field('post_content', $post_id);
                echo apply_filters('the_content', $content);
                ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render notes tab
     */
    private static function render_notes_tab($post_id, $taxonomy_data) {
        ?>
        <div id="notes" class="tab-panel">
            <?php if (!empty($taxonomy_data['notes'])): ?>
                <div class="notes-pyramid">
                    <h3><?php _e('Fragrance Pyramid', 'parfume-reviews'); ?></h3>
                    
                    <?php
                    $notes_count = count($taxonomy_data['notes']);
                    $top_notes = array_slice($taxonomy_data['notes'], 0, ceil($notes_count / 3));
                    $middle_notes = array_slice($taxonomy_data['notes'], ceil($notes_count / 3), ceil($notes_count / 3));
                    $base_notes = array_slice($taxonomy_data['notes'], ceil($notes_count * 2 / 3));
                    ?>
                    
                    <div class="pyramid-level top-notes">
                        <h4><?php _e('Top Notes', 'parfume-reviews'); ?></h4>
                        <ul>
                            <?php foreach ($top_notes as $note): ?>
                                <li><a href="<?php echo get_term_link($note); ?>"><?php echo esc_html($note->name); ?></a></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    
                    <?php if (!empty($middle_notes)): ?>
                        <div class="pyramid-level middle-notes">
                            <h4><?php _e('Middle Notes', 'parfume-reviews'); ?></h4>
                            <ul>
                                <?php foreach ($middle_notes as $note): ?>
                                    <li><a href="<?php echo get_term_link($note); ?>"><?php echo esc_html($note->name); ?></a></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($base_notes)): ?>
                        <div class="pyramid-level base-notes">
                            <h4><?php _e('Base Notes', 'parfume-reviews'); ?></h4>
                            <ul>
                                <?php foreach ($base_notes as $note): ?>
                                    <li><a href="<?php echo get_term_link($note); ?>"><?php echo esc_html($note->name); ?></a></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>