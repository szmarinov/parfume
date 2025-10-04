<?php
/**
 * Template Part: Composition Pyramid
 * Displays perfume notes in pyramid visualization
 */

if (!defined('ABSPATH')) {
    exit;
}

global $post;

// Get notes from different positions
$top_notes = get_post_meta($post->ID, '_parfume_top_notes', true);
$middle_notes = get_post_meta($post->ID, '_parfume_middle_notes', true);
$base_notes = get_post_meta($post->ID, '_parfume_base_notes', true);

// Check if we have any notes
if (empty($top_notes) && empty($middle_notes) && empty($base_notes)) {
    return;
}
?>

<section class="parfume-composition">
    <h2 class="composition-title">Състав</h2>
    
    <div class="composition-pyramid">
        
        <?php if (!empty($top_notes)) : ?>
        <div class="pyramid-layer pyramid-top">
            <div class="layer-header">
                <span class="layer-icon">▲</span>
                <h3 class="layer-title">Връхни нотки</h3>
            </div>
            <div class="layer-notes">
                <?php 
                $top_notes_array = is_array($top_notes) ? $top_notes : explode(',', $top_notes);
                foreach ($top_notes_array as $note_id) :
                    $note_id = is_numeric($note_id) ? intval($note_id) : $note_id;
                    $term = is_numeric($note_id) ? get_term($note_id, 'notes') : get_term_by('slug', $note_id, 'notes');
                    
                    if ($term && !is_wp_error($term)) :
                        $group = get_term_meta($term->term_id, 'note_group', true);
                        $group_class = $group ? 'group-' . sanitize_html_class($group) : 'group-default';
                        $note_image = get_term_meta($term->term_id, 'note_image', true);
                ?>
                    <span class="note-tag <?php echo esc_attr($group_class); ?>">
                        <?php if ($note_image) : ?>
                            <img src="<?php echo esc_url($note_image); ?>" alt="<?php echo esc_attr($term->name); ?>" class="note-icon">
                        <?php endif; ?>
                        <span class="note-name"><?php echo esc_html($term->name); ?></span>
                        <?php if ($group) : ?>
                            <span class="note-group-badge"><?php echo esc_html($group); ?></span>
                        <?php endif; ?>
                    </span>
                <?php 
                    endif;
                endforeach; 
                ?>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($middle_notes)) : ?>
        <div class="pyramid-layer pyramid-middle">
            <div class="layer-header">
                <span class="layer-icon">◆</span>
                <h3 class="layer-title">Средни нотки</h3>
            </div>
            <div class="layer-notes">
                <?php 
                $middle_notes_array = is_array($middle_notes) ? $middle_notes : explode(',', $middle_notes);
                foreach ($middle_notes_array as $note_id) :
                    $note_id = is_numeric($note_id) ? intval($note_id) : $note_id;
                    $term = is_numeric($note_id) ? get_term($note_id, 'notes') : get_term_by('slug', $note_id, 'notes');
                    
                    if ($term && !is_wp_error($term)) :
                        $group = get_term_meta($term->term_id, 'note_group', true);
                        $group_class = $group ? 'group-' . sanitize_html_class($group) : 'group-default';
                        $note_image = get_term_meta($term->term_id, 'note_image', true);
                ?>
                    <span class="note-tag <?php echo esc_attr($group_class); ?>">
                        <?php if ($note_image) : ?>
                            <img src="<?php echo esc_url($note_image); ?>" alt="<?php echo esc_attr($term->name); ?>" class="note-icon">
                        <?php endif; ?>
                        <span class="note-name"><?php echo esc_html($term->name); ?></span>
                        <?php if ($group) : ?>
                            <span class="note-group-badge"><?php echo esc_html($group); ?></span>
                        <?php endif; ?>
                    </span>
                <?php 
                    endif;
                endforeach; 
                ?>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($base_notes)) : ?>
        <div class="pyramid-layer pyramid-base">
            <div class="layer-header">
                <span class="layer-icon">▼</span>
                <h3 class="layer-title">Базови нотки</h3>
            </div>
            <div class="layer-notes">
                <?php 
                $base_notes_array = is_array($base_notes) ? $base_notes : explode(',', $base_notes);
                foreach ($base_notes_array as $note_id) :
                    $note_id = is_numeric($note_id) ? intval($note_id) : $note_id;
                    $term = is_numeric($note_id) ? get_term($note_id, 'notes') : get_term_by('slug', $note_id, 'notes');
                    
                    if ($term && !is_wp_error($term)) :
                        $group = get_term_meta($term->term_id, 'note_group', true);
                        $group_class = $group ? 'group-' . sanitize_html_class($group) : 'group-default';
                        $note_image = get_term_meta($term->term_id, 'note_image', true);
                ?>
                    <span class="note-tag <?php echo esc_attr($group_class); ?>">
                        <?php if ($note_image) : ?>
                            <img src="<?php echo esc_url($note_image); ?>" alt="<?php echo esc_attr($term->name); ?>" class="note-icon">
                        <?php endif; ?>
                        <span class="note-name"><?php echo esc_html($term->name); ?></span>
                        <?php if ($group) : ?>
                            <span class="note-group-badge"><?php echo esc_html($group); ?></span>
                        <?php endif; ?>
                    </span>
                <?php 
                    endif;
                endforeach; 
                ?>
            </div>
        </div>
        <?php endif; ?>
        
    </div>
</section>

<style>
.parfume-composition {
    margin: 40px 0;
    padding: 30px;
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.composition-title {
    font-size: 28px;
    margin-bottom: 30px;
    text-align: center;
    color: #333;
}

.composition-pyramid {
    max-width: 800px;
    margin: 0 auto;
}

.pyramid-layer {
    margin-bottom: 20px;
    padding: 20px;
    border-radius: 8px;
    transition: transform 0.3s ease;
}

.pyramid-layer:hover {
    transform: translateY(-2px);
}

.pyramid-top {
    background: linear-gradient(135deg, #fff5e6 0%, #ffe4b3 100%);
    border-left: 4px solid #ffb347;
}

.pyramid-middle {
    background: linear-gradient(135deg, #ffe6f0 0%, #ffb3d9 100%);
    border-left: 4px solid #ff69b4;
}

.pyramid-base {
    background: linear-gradient(135deg, #e6f3ff 0%, #b3d9ff 100%);
    border-left: 4px solid #4a90e2;
}

.layer-header {
    display: flex;
    align-items: center;
    margin-bottom: 15px;
}

.layer-icon {
    font-size: 20px;
    margin-right: 10px;
}

.layer-title {
    font-size: 20px;
    font-weight: 600;
    margin: 0;
    color: #333;
}

.layer-notes {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
}

.note-tag {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 14px;
    background: #fff;
    border-radius: 20px;
    font-size: 14px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
}

.note-tag:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
}

.note-icon {
    width: 20px;
    height: 20px;
    border-radius: 50%;
    object-fit: cover;
}

.note-name {
    font-weight: 500;
}

.note-group-badge {
    font-size: 11px;
    padding: 2px 6px;
    background: rgba(0,0,0,0.1);
    border-radius: 10px;
    text-transform: lowercase;
}

/* Group color variations */
.group-дървесни { border-left: 3px solid #8B4513; }
.group-цветни { border-left: 3px solid #FF69B4; }
.group-ориенталски { border-left: 3px solid #DAA520; }
.group-плодови { border-left: 3px solid #FF6347; }
.group-зелени { border-left: 3px solid #32CD32; }
.group-гурме { border-left: 3px solid #D2691E; }
.group-морски { border-left: 3px solid #4682B4; }
.group-ароматни { border-left: 3px solid #9370DB; }

/* Responsive */
@media (max-width: 768px) {
    .parfume-composition {
        padding: 20px 15px;
    }
    
    .composition-title {
        font-size: 24px;
    }
    
    .layer-title {
        font-size: 18px;
    }
    
    .note-tag {
        font-size: 13px;
        padding: 6px 10px;
    }
}
</style>