<?php
/**
 * Template for All Notes archive page (/parfiumi/notes/)
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header(); 
?>

<div class="parfume-archive notes-archive">
    <div class="archive-header">
        <h1 class="archive-title"><?php _e('All Fragrance Notes', 'parfume-reviews'); ?></h1>
        <div class="archive-description">
            <p><?php _e('Explore perfumes by fragrance notes. Discover scents with your favorite aromatic ingredients organized by categories.', 'parfume-reviews'); ?></p>
        </div>
    </div>

    <div class="archive-content">
        <div class="archive-main">
            <?php
            // Get all notes ordered alphabetically
            $all_notes = get_terms(array(
                'taxonomy' => 'notes',
                'hide_empty' => false,
                'orderby' => 'name',
                'order' => 'ASC',
            ));

            // Categorize notes by fragrance families
            $notes_categories = array(
                'Цитрусови' => array(
                    'keywords' => array('бергамот', 'лимон', 'портокал', 'грейпфрут', 'мандарина', 'лайм', 'цитрус'),
                    'icon' => '🍋',
                    'notes' => array()
                ),
                'Флорални' => array(
                    'keywords' => array('роза', 'жасмин', 'лавандула', 'иланг', 'магнолия', 'божур', 'нерколи', 'фрезия'),
                    'icon' => '🌸',
                    'notes' => array()
                ),
                'Дървесни' => array(
                    'keywords' => array('кедър', 'сандал', 'oud', 'ветивер', 'пачули', 'дърво', 'дървесн'),
                    'icon' => '🌳',
                    'notes' => array()
                ),
                'Ориенталски/Сладки' => array(
                    'keywords' => array('ванилия', 'кехлибар', 'мускус', 'тонка', 'бензоин', 'ладан', 'мирра'),
                    'icon' => '🍯',
                    'notes' => array()
                ),
                'Свежи/Водни' => array(
                    'keywords' => array('iso e super', 'морски', 'мента', 'евкалипт', 'озон', 'аквати', 'воден'),
                    'icon' => '💧',
                    'notes' => array()
                ),
                'Подправки' => array(
                    'keywords' => array('канела', 'карамфил', 'пипер', 'джинджифил', 'кардамон', 'шафран'),
                    'icon' => '🌶️',
                    'notes' => array()
                ),
                'Гурме/Сладки' => array(
                    'keywords' => array('какао', 'кафе', 'карамел', 'мед', 'шоколад', 'праскова', 'ябълка'),
                    'icon' => '🍫',
                    'notes' => array()
                ),
                'Животински/Мускусни' => array(
                    'keywords' => array('мускус', 'амбра', 'цибет', 'кастореум', 'животинск'),
                    'icon' => '🦌',
                    'notes' => array()
                ),
                'Други' => array(
                    'keywords' => array(),
                    'icon' => '🌿',
                    'notes' => array()
                )
            );

            if (!empty($all_notes) && !is_wp_error($all_notes)) {
                foreach ($all_notes as $note) {
                    $found_category = false;
                    $note_name_lower = mb_strtolower($note->name, 'UTF-8');
                    
                    foreach ($notes_categories as $category => $data) {
                        if ($category === 'Други') continue; // Skip "Други" for now
                        
                        foreach ($data['keywords'] as $keyword) {
                            if (strpos($note_name_lower, $keyword) !== false) {
                                $notes_categories[$category]['notes'][] = $note;
                                $found_category = true;
                                break 2;
                            }
                        }
                    }
                    
                    // If no category found, add to "Други"
                    if (!$found_category) {
                        $notes_categories['Други']['notes'][] = $note;
                    }
                }
            }
            ?>
            
            <?php if (!empty($all_notes)): ?>
                <div class="notes-overview">
                    <div class="overview-stats">
                        <span class="total-notes"><?php printf(__('%d Total Notes', 'parfume-reviews'), count($all_notes)); ?></span>
                        <span class="categories-count"><?php printf(__('%d Categories', 'parfume-reviews'), count(array_filter($notes_categories, function($cat) { return !empty($cat['notes']); }))); ?></span>
                    </div>
                </div>
                
                <div class="notes-categories">
                    <?php foreach ($notes_categories as $category => $data): ?>
                        <?php if (!empty($data['notes'])): ?>
                            <div class="notes-category">
                                <h2 class="category-heading">
                                    <span class="category-icon"><?php echo $data['icon']; ?></span>
                                    <?php echo esc_html($category); ?>
                                    <span class="category-count">(<?php echo count($data['notes']); ?>)</span>
                                </h2>
                                
                                <div class="notes-grid">
                                    <?php foreach ($data['notes'] as $note): ?>
                                        <div class="note-item">
                                            <a href="<?php echo get_term_link($note); ?>" class="note-link">
                                                <?php 
                                                $note_image_id = get_term_meta($note->term_id, 'notes-image-id', true);
                                                if ($note_image_id): 
                                                ?>
                                                    <div class="note-image">
                                                        <?php echo wp_get_attachment_image($note_image_id, 'thumbnail', false, array('alt' => $note->name)); ?>
                                                    </div>
                                                <?php else: ?>
                                                    <div class="note-image note-placeholder">
                                                        <span class="note-icon"><?php echo $data['icon']; ?></span>
                                                    </div>
                                                <?php endif; ?>
                                                
                                                <div class="note-info">
                                                    <h3 class="note-name"><?php echo esc_html($note->name); ?></h3>
                                                    <span class="note-count">
                                                        <?php printf(_n('%d парфюм', '%d парфюма', $note->count, 'parfume-reviews'), $note->count); ?>
                                                    </span>
                                                    
                                                    <?php if ($note->description): ?>
                                                        <p class="note-description"><?php echo wp_trim_words(esc_html($note->description), 12); ?></p>
                                                    <?php endif; ?>
                                                </div>
                                            </a>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="no-notes"><?php _e('No notes found.', 'parfume-reviews'); ?></p>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.notes-overview {
    background: linear-gradient(135deg, #4CAF50 0%, #45a049 100%);
    border-radius: 12px;
    padding: 25px;
    margin: 30px 0;
    color: white;
    text-align: center;
}

.overview-stats {
    display: flex;
    justify-content: center;
    gap: 40px;
    flex-wrap: wrap;
}

.total-notes, .categories-count {
    font-size: 1.3em;
    font-weight: bold;
    text-shadow: 0 2px 4px rgba(0,0,0,0.3);
}

.notes-categories {
    margin-top: 40px;
}

.notes-category {
    margin-bottom: 50px;
    background: #f8f9fa;
    border-radius: 12px;
    padding: 30px;
}

.category-heading {
    font-size: 2em;
    color: #333;
    border-bottom: 3px solid #4CAF50;
    padding-bottom: 15px;
    margin-bottom: 30px;
    display: flex;
    align-items: center;
    gap: 15px;
}

.category-icon {
    font-size: 1.2em;
}

.category-count {
    font-size: 0.7em;
    color: #666;
    font-weight: normal;
}

.notes-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 20px;
}

.note-item {
    background: white;
    border: 1px solid #dee2e6;
    border-radius: 10px;
    overflow: hidden;
    transition: all 0.3s ease;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.note-item:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(76, 175, 80, 0.3);
    border-color: #4CAF50;
}

.note-link {
    display: block;
    padding: 20px;
    text-decoration: none;
    color: inherit;
    text-align: center;
    height: 100%;
}

.note-image {
    margin-bottom: 15px;
}

.note-image img {
    max-width: 60px;
    max-height: 60px;
    object-fit: contain;
    border-radius: 6px;
}

.note-placeholder {
    background: linear-gradient(135deg, #e8f5e8, #f1f8e9);
    border: 1px solid #c8e6c9;
    width: 60px;
    height: 60px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto;
    border-radius: 50%;
}

.note-icon {
    font-size: 24px;
}

.note-name {
    font-size: 1.1em;
    font-weight: bold;
    margin: 0 0 8px;
    color: #333;
}

.note-count {
    display: block;
    color: #4CAF50;
    font-weight: 500;
    margin-bottom: 10px;
    font-size: 0.9em;
}

.note-description {
    color: #666;
    font-size: 0.85em;
    line-height: 1.4;
    margin: 0;
}

.no-notes {
    text-align: center;
    padding: 60px 20px;
    color: #666;
    font-size: 1.2em;
}

/* Category-specific colors */
.notes-category:nth-child(1) .category-heading { border-bottom-color: #FFC107; }
.notes-category:nth-child(1) .note-item:hover { border-color: #FFC107; box-shadow: 0 10px 25px rgba(255, 193, 7, 0.3); }
.notes-category:nth-child(1) .note-count { color: #FFC107; }

.notes-category:nth-child(2) .category-heading { border-bottom-color: #E91E63; }
.notes-category:nth-child(2) .note-item:hover { border-color: #E91E63; box-shadow: 0 10px 25px rgba(233, 30, 99, 0.3); }
.notes-category:nth-child(2) .note-count { color: #E91E63; }

.notes-category:nth-child(3) .category-heading { border-bottom-color: #8D6E63; }
.notes-category:nth-child(3) .note-item:hover { border-color: #8D6E63; box-shadow: 0 10px 25px rgba(141, 110, 99, 0.3); }
.notes-category:nth-child(3) .note-count { color: #8D6E63; }

.notes-category:nth-child(4) .category-heading { border-bottom-color: #FF9800; }
.notes-category:nth-child(4) .note-item:hover { border-color: #FF9800; box-shadow: 0 10px 25px rgba(255, 152, 0, 0.3); }
.notes-category:nth-child(4) .note-count { color: #FF9800; }

.notes-category:nth-child(5) .category-heading { border-bottom-color: #2196F3; }
.notes-category:nth-child(5) .note-item:hover { border-color: #2196F3; box-shadow: 0 10px 25px rgba(33, 150, 243, 0.3); }
.notes-category:nth-child(5) .note-count { color: #2196F3; }

.notes-category:nth-child(6) .category-heading { border-bottom-color: #F44336; }
.notes-category:nth-child(6) .note-item:hover { border-color: #F44336; box-shadow: 0 10px 25px rgba(244, 67, 54, 0.3); }
.notes-category:nth-child(6) .note-count { color: #F44336; }

.notes-category:nth-child(7) .category-heading { border-bottom-color: #795548; }
.notes-category:nth-child(7) .note-item:hover { border-color: #795548; box-shadow: 0 10px 25px rgba(121, 85, 72, 0.3); }
.notes-category:nth-child(7) .note-count { color: #795548; }

.notes-category:nth-child(8) .category-heading { border-bottom-color: #9C27B0; }
.notes-category:nth-child(8) .note-item:hover { border-color: #9C27B0; box-shadow: 0 10px 25px rgba(156, 39, 176, 0.3); }
.notes-category:nth-child(8) .note-count { color: #9C27B0; }

@media (max-width: 768px) {
    .notes-grid {
        grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
        gap: 15px;
    }
    
    .note-link {
        padding: 15px;
    }
    
    .category-heading {
        font-size: 1.5em;
        flex-direction: column;
        align-items: flex-start;
        gap: 5px;
    }
    
    .notes-category {
        padding: 20px;
    }
    
    .overview-stats {
        gap: 20px;
    }
    
    .total-notes, .categories-count {
        font-size: 1.1em;
    }
}

@media (max-width: 480px) {
    .notes-grid {
        grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
        gap: 12px;
    }
    
    .category-heading {
        font-size: 1.3em;
    }
    
    .note-name {
        font-size: 1em;
    }
    
    .overview-stats {
        flex-direction: column;
        gap: 10px;
    }
}
</style>

<?php get_footer(); ?>