<?php
get_header();

// Get all notes ordered alphabetically
$all_notes = get_terms(array(
    'taxonomy' => 'notes',
    'hide_empty' => false,
    'orderby' => 'name',
    'order' => 'ASC',
));

// Group notes by first letter
$notes_by_letter = array();
$available_letters = array();

if (!empty($all_notes) && !is_wp_error($all_notes)) {
    foreach ($all_notes as $note) {
        $first_letter = mb_strtoupper(mb_substr($note->name, 0, 1, 'UTF-8'), 'UTF-8');
        
        // Handle Cyrillic and Latin letters
        if (preg_match('/[А-Я]/u', $first_letter)) {
            // Cyrillic letter
            $letter_key = $first_letter;
        } elseif (preg_match('/[A-Z]/', $first_letter)) {
            // Latin letter
            $letter_key = $first_letter;
        } else {
            // Numbers or special characters
            $letter_key = '#';
        }
        
        if (!isset($notes_by_letter[$letter_key])) {
            $notes_by_letter[$letter_key] = array();
        }
        $notes_by_letter[$letter_key][] = $note;
        
        if (!in_array($letter_key, $available_letters)) {
            $available_letters[] = $letter_key;
        }
    }
}

// Sort available letters
sort($available_letters);

// Create full alphabet for navigation
$cyrillic_alphabet = array('А', 'Б', 'В', 'Г', 'Д', 'Е', 'Ж', 'З', 'И', 'Й', 'К', 'Л', 'М', 'Н', 'О', 'П', 'Р', 'С', 'Т', 'У', 'Ф', 'Х', 'Ц', 'Ч', 'Ш', 'Щ', 'Ъ', 'Ь', 'Ю', 'Я');
$latin_alphabet = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z');
$full_alphabet = array_merge($latin_alphabet, $cyrillic_alphabet, array('#'));

// Note categories for color coding
function get_note_category($note_name) {
    $citrus = array('Лимон', 'Грейпфрут', 'Бергамот', 'Мандарина', 'Портокал', 'Лайм', 'Юзу', 'Цитрон');
    $floral = array('Роза', 'Жасмин', 'Лавандула', 'Ирис', 'Божур', 'Фрезия', 'Тубероза', 'Иланг-иланг', 'Нероли');
    $woody = array('Кедрово дърво', 'Сандалово дърво', 'Ветивер', 'Пачули', 'Дъбов мъх', 'Гваяково дърво');
    $oriental = array('Ванилия', 'Кехлибар', 'Мускус', 'Амбра', 'Тамян', 'Опопонакс', 'Лабданум');
    $fruity = array('Ябълка', 'Круша', 'Праскова', 'Ананас', 'Малина', 'Касис', 'Череша', 'Смокиня');
    $spicy = array('Пипер', 'Карамфил', 'Канела', 'Джинджифил', 'Кардамон', 'Шафран', 'Кориандър');
    
    if (in_array($note_name, $citrus)) return 'citrus';
    if (in_array($note_name, $floral)) return 'floral';
    if (in_array($note_name, $woody)) return 'woody';
    if (in_array($note_name, $oriental)) return 'oriental';
    if (in_array($note_name, $fruity)) return 'fruity';
    if (in_array($note_name, $spicy)) return 'spicy';
    
    return 'other';
}
?>

<div class="parfume-notes-archive">
    <header class="archive-header">
        <h1 class="archive-title"><?php _e('All Notes', 'parfume-reviews'); ?></h1>
        <p class="archive-description"><?php _e('Discover all fragrance notes used in perfumery', 'parfume-reviews'); ?></p>
    </header>
    
    <!-- Note Categories Legend -->
    <div class="notes-categories-legend">
        <h3><?php _e('Note Categories', 'parfume-reviews'); ?></h3>
        <div class="categories-list">
            <span class="category-item citrus"><?php _e('Citrus', 'parfume-reviews'); ?></span>
            <span class="category-item floral"><?php _e('Floral', 'parfume-reviews'); ?></span>
            <span class="category-item woody"><?php _e('Woody', 'parfume-reviews'); ?></span>
            <span class="category-item oriental"><?php _e('Oriental', 'parfume-reviews'); ?></span>
            <span class="category-item fruity"><?php _e('Fruity', 'parfume-reviews'); ?></span>
            <span class="category-item spicy"><?php _e('Spicy', 'parfume-reviews'); ?></span>
            <span class="category-item other"><?php _e('Other', 'parfume-reviews'); ?></span>
        </div>
    </div>
    
    <!-- Alphabet Navigation -->
    <div class="alphabet-navigation">
        <div class="alphabet-nav-inner">
            <?php foreach ($full_alphabet as $letter): ?>
                <?php if (in_array($letter, $available_letters)): ?>
                    <a href="#letter-<?php echo esc_attr(strtolower($letter)); ?>" class="letter-link active" data-letter="<?php echo esc_attr($letter); ?>">
                        <?php echo esc_html($letter); ?>
                    </a>
                <?php else: ?>
                    <span class="letter-link inactive">
                        <?php echo esc_html($letter); ?>
                    </span>
                <?php endif; ?>
            <?php endforeach; ?>
    </div>
    
    <!-- Notes Content -->
    <div class="notes-content">
        <?php if (!empty($notes_by_letter)): ?>
            <?php foreach ($available_letters as $letter): ?>
                <div class="letter-section" id="letter-<?php echo esc_attr(strtolower($letter)); ?>">
                    <h2 class="letter-heading"><?php echo esc_html($letter); ?></h2>
                    
                    <div class="notes-grid">
                        <?php foreach ($notes_by_letter[$letter] as $note): 
                            $category = get_note_category($note->name);
                        ?>
                            <div class="note-item <?php echo esc_attr($category); ?>">
                                <a href="<?php echo get_term_link($note); ?>" class="note-link">
                                    <div class="note-info">
                                        <h3 class="note-name"><?php echo esc_html($note->name); ?></h3>
                                        <span class="note-count">
                                            <?php printf(_n('%d perfume', '%d perfumes', $note->count, 'parfume-reviews'), $note->count); ?>
                                        </span>
                                        <span class="note-category"><?php echo esc_html(ucfirst($category)); ?></span>
                                        
                                        <?php if ($note->description): ?>
                                            <p class="note-description"><?php echo wp_trim_words(esc_html($note->description), 12); ?></p>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="note-indicator">
                                        <span class="category-dot <?php echo esc_attr($category); ?>"></span>
                                    </div>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="no-notes"><?php _e('No notes found.', 'parfume-reviews'); ?></p>
        <?php endif; ?>
    </div>
    
    <!-- Back to top button -->
    <button id="back-to-top" class="back-to-top" style="display: none;">
        <span class="dashicons dashicons-arrow-up-alt2"></span>
        <?php _e('Top', 'parfume-reviews'); ?>
    </button>
</div>

<style>
/* Note Categories Legend */
.notes-categories-legend {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border: 1px solid #dee2e6;
    border-radius: 12px;
    padding: 20px;
    margin: 30px 0;
    text-align: center;
}

.notes-categories-legend h3 {
    margin: 0 0 15px;
    color: #333;
}

.categories-list {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    justify-content: center;
}

.category-item {
    padding: 8px 16px;
    border-radius: 20px;
    font-size: 0.9em;
    font-weight: 500;
    border: 2px solid transparent;
}

.category-item.citrus { background: #fff3cd; color: #856404; border-color: #ffeaa7; }
.category-item.floral { background: #f8d7da; color: #721c24; border-color: #f5b7b1; }
.category-item.woody { background: #d4e6f1; color: #1b4f72; border-color: #aed6f1; }
.category-item.oriental { background: #fadbd8; color: #943126; border-color: #f1948a; }
.category-item.fruity { background: #d5f4e6; color: #0e6b47; border-color: #82e0aa; }
.category-item.spicy { background: #fbeee6; color: #a04000; border-color: #f8c291; }
.category-item.other { background: #e8e8e8; color: #495057; border-color: #ced4da; }

/* Alphabet Navigation */
.alphabet-navigation {
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 20px;
    margin: 30px 0;
    position: sticky;
    top: 32px;
    z-index: 100;
}

.alphabet-nav-inner {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    justify-content: center;
    align-items: center;
}

.letter-link {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 35px;
    height: 35px;
    border-radius: 50%;
    text-decoration: none;
    font-weight: bold;
    font-size: 14px;
    transition: all 0.3s ease;
}

.letter-link.active {
    background: #28a745;
    color: white;
}

.letter-link.active:hover {
    background: #218838;
    transform: scale(1.1);
}

.letter-link.inactive {
    background: #e9ecef;
    color: #6c757d;
    cursor: not-allowed;
}

.letter-link.current {
    background: #1e7e34 !important;
    transform: scale(1.1);
}

/* Letter Sections */
.letter-section {
    margin-bottom: 50px;
}

.letter-heading {
    font-size: 2.5em;
    color: #28a745;
    border-bottom: 3px solid #28a745;
    padding-bottom: 10px;
    margin-bottom: 30px;
    scroll-margin-top: 100px;
}

/* Notes Grid */
.notes-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
}

.note-item {
    background: white;
    border: 1px solid #dee2e6;
    border-radius: 12px;
    overflow: hidden;
    transition: all 0.3s ease;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    position: relative;
}

.note-item:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
}

.note-item.citrus:hover { border-color: #ffc107; }
.note-item.floral:hover { border-color: #e83e8c; }
.note-item.woody:hover { border-color: #17a2b8; }
.note-item.oriental:hover { border-color: #dc3545; }
.note-item.fruity:hover { border-color: #28a745; }
.note-item.spicy:hover { border-color: #fd7e14; }
.note-item.other:hover { border-color: #6c757d; }

.note-link {
    display: flex;
    align-items: center;
    padding: 20px;
    text-decoration: none;
    color: inherit;
    height: 100%;
}

.note-info {
    flex: 1;
}

.note-name {
    font-size: 1.1em;
    font-weight: bold;
    margin: 0 0 8px;
    color: #333;
}

.note-count {
    display: block;
    color: #28a745;
    font-weight: 500;
    margin-bottom: 5px;
    font-size: 0.9em;
}

.note-category {
    display: inline-block;
    background: #e9ecef;
    color: #495057;
    padding: 2px 8px;
    border-radius: 10px;
    font-size: 0.8em;
    margin-bottom: 10px;
}

.note-description {
    color: #666;
    font-size: 0.9em;
    line-height: 1.4;
    margin: 0;
}

.note-indicator {
    margin-left: 15px;
}

.category-dot {
    width: 20px;
    height: 20px;
    border-radius: 50%;
    display: block;
    border: 3px solid white;
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
}

.category-dot.citrus { background: #ffc107; }
.category-dot.floral { background: #e83e8c; }
.category-dot.woody { background: #17a2b8; }
.category-dot.oriental { background: #dc3545; }
.category-dot.fruity { background: #28a745; }
.category-dot.spicy { background: #fd7e14; }
.category-dot.other { background: #6c757d; }

/* Back to top */
.back-to-top {
    position: fixed;
    bottom: 30px;
    right: 30px;
    background: #28a745;
    color: white;
    border: none;
    border-radius: 50px;
    padding: 12px 20px;
    cursor: pointer;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    transition: all 0.3s ease;
    z-index: 1000;
    display: flex;
    align-items: center;
    gap: 5px;
}

.back-to-top:hover {
    background: #218838;
    transform: translateY(-2px);
}

/* Archive header */
.archive-header {
    text-align: center;
    margin-bottom: 40px;
}

.archive-title {
    font-size: 2.5em;
    margin-bottom: 10px;
    color: #333;
}

.archive-description {
    font-size: 1.1em;
    color: #666;
    margin: 0;
}

/* Responsive */
@media (max-width: 768px) {
    .alphabet-nav-inner {
        gap: 4px;
    }
    
    .letter-link {
        width: 30px;
        height: 30px;
        font-size: 12px;
    }
    
    .notes-grid {
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 15px;
    }
    
    .letter-heading {
        font-size: 2em;
    }
    
    .back-to-top {
        bottom: 20px;
        right: 20px;
        padding: 10px 15px;
    }
    
    .alphabet-navigation {
        position: static;
        padding: 15px;
    }
    
    .categories-list {
        gap: 8px;
    }
    
    .category-item {
        padding: 6px 12px;
        font-size: 0.8em;
    }
}

@media (max-width: 480px) {
    .notes-grid {
        grid-template-columns: 1fr;
    }
    
    .letter-link {
        width: 28px;
        height: 28px;
        font-size: 11px;
    }
    
    .note-link {
        flex-direction: column;
        text-align: center;
    }
    
    .note-indicator {
        margin-left: 0;
        margin-top: 10px;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Smooth scrolling for alphabet navigation
    const letterLinks = document.querySelectorAll('.letter-link.active');
    letterLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
                
                // Update URL without page reload
                history.pushState(null, null, this.getAttribute('href'));
            }
        });
    });
    
    // Back to top functionality
    const backToTopButton = document.getElementById('back-to-top');
    
    window.addEventListener('scroll', function() {
        if (window.pageYOffset > 300) {
            backToTopButton.style.display = 'flex';
        } else {
            backToTopButton.style.display = 'none';
        }
    });
    
    backToTopButton.addEventListener('click', function() {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    });
    
    // Highlight current letter in navigation
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const id = entry.target.id;
                const letter = id.replace('letter-', '').toUpperCase();
                
                // Remove current class from all links
                letterLinks.forEach(link => link.classList.remove('current'));
                
                // Add current class to current letter
                const currentLink = document.querySelector(`[data-letter="${letter}"]`);
                if (currentLink) {
                    currentLink.classList.add('current');
                }
            }
        });
    }, {
        rootMargin: '-100px 0px -50% 0px'
    });
    
    // Observe all letter sections
    document.querySelectorAll('.letter-section').forEach(section => {
        observer.observe(section);
    });
    
    // Handle URL hash on page load
    if (window.location.hash) {
        setTimeout(() => {
            const target = document.querySelector(window.location.hash);
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        }, 100);
    }
});
</script>

<?php
get_footer();
?>
        </div>
    </div>
    
    <!-- Notes Content -->
    <div class="notes-content">
        <?php if (!empty($notes_by_letter)): ?>
            <?php foreach ($available_letters as $letter): ?>
                <div class="letter-section" id="letter-<?php echo esc_attr(strtolower($letter)); ?>">
                    <h2 class="letter-heading"><?php echo esc_html($letter); ?></h2>
                    
                    <div class="notes-grid">
                        <?php foreach ($notes_by_letter[$letter] as $note): 
                            $category = get_note_category($note->name);
                        ?>
                            <div class="note-item <?php echo esc_attr($category); ?>">
                                <a href="<?php echo get_term_link($note); ?>" class="note-link">
                                    <div class="note-info">
                                        <h3 class="note-name"><?php echo esc_html($note->name); ?></h3>
                                        <span class="note-count">
                                            <?php printf(_n('%d perfume', '%d perfumes', $note->count, 'parfume-reviews'), $note->count); ?>
                                        </span>
                                        <span class="note-category"><?php echo esc_html(ucfirst($category)); ?></span>
                                        
                                        <?php if ($note->description): ?>
                                            <p class="note-description"><?php echo wp_trim_words(esc_html($note->description), 12); ?></p>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="note-indicator">
                                        <span class="category-dot <?php echo esc_attr($category); ?>"></span>
                                    </div>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="no-notes"><?php _e('No notes found.', 'parfume-reviews'); ?></p>
        <?php endif; ?>
    </div>
    
    <!-- Back to top button -->
    <button id="back-to-top" class="back-to-top" style="display: none;">
        <span class="dashicons dashicons-arrow-up-alt2"></span>
        <?php _e('Top', 'parfume-reviews'); ?>
    </button>
</div>

<style>
/* Note Categories Legend */
.notes-categories-legend {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border: 1px solid #dee2e6;
    border-radius: 12px;
    padding: 20px;
    margin: 30px 0;
    text-align: center;
}

.notes-categories-legend h3 {
    margin: 0 0 15px;
    color: #333;
}

.categories-list {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    justify-content: center;
}

.category-item {
    padding: 8px 16px;
    border-radius: 20px;
    font-size: 0.9em;
    font-weight: 500;
    border: 2px solid transparent;
}

.category-item.citrus { background: #fff3cd; color: #856404; border-color: #ffeaa7; }
.category-item.floral { background: #f8d7da; color: #721c24; border-color: #f5b7b1; }
.category-item.woody { background: #d4e6f1; color: #1b4f72; border-color: #aed6f1; }
.category-item.oriental { background: #fadbd8; color: #943126; border-color: #f1948a; }
.category-item.fruity { background: #d5f4e6; color: #0e6b47; border-color: #82e0aa; }
.category-item.spicy { background: #fbeee6; color: #a04000; border-color: #f8c291; }
.category-item.other { background: #e8e8e8; color: #495057; border-color: #ced4da; }

/* Alphabet Navigation */
.alphabet-navigation {
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 20px;
    margin: 30px 0;
    position: sticky;
    top: 32px;
    z-index: 100;
}

.alphabet-nav-inner {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    justify-content: center;
    align-items: center;
}

.letter-link {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 35px;
    height: 35px;
    border-radius: 50%;
    text-decoration: none;
    font-weight: bold;
    font-size: 14px;
    transition: all 0.3s ease;
}

.letter-link.active {
    background: #28a745;
    color: white;
}

.letter-link.active:hover {
    background: #218838;
    transform: scale(1.1);
}

.letter-link.inactive {
    background: #e9ecef;
    color: #6c757d;
    cursor: not-allowed;
}

.letter-link.current {
    background: #1e7e34 !important;
    transform: scale(1.1);
}

/* Letter Sections */
.letter-section {
    margin-bottom: 50px;
}

.letter-heading {
    font-size: 2.5em;
    color: #28a745;
    border-bottom: 3px solid #28a745;
    padding-bottom: 10px;
    margin-bottom: 30px;
    scroll-margin-top: 100px;
}

/* Notes Grid */
.notes-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
}

.note-item {
    background: white;
    border: 1px solid #dee2e6;
    border-radius: 12px;
    overflow: hidden;
    transition: all 0.3s ease;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    position: relative;
}

.note-item:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
}

.note-item.citrus:hover { border-color: #ffc107; }
.note-item.floral:hover { border-color: #e83e8c; }
.note-item.woody:hover { border-color: #17a2b8; }
.note-item.oriental:hover { border-color: #dc3545; }
.note-item.fruity:hover { border-color: #28a745; }
.note-item.spicy:hover { border-color: #fd7e14; }
.note-item.other:hover { border-color: #6c757d; }

.note-link {
    display: flex;
    align-items: center;
    padding: 20px;
    text-decoration: none;
    color: inherit;
    height: 100%;
}

.note-info {
    flex: 1;
}

.note-name {
    font-size: 1.1em;
    font-weight: bold;
    margin: 0 0 8px;
    color: #333;
}

.note-count {
    display: block;
    color: #28a745;
    font-weight: 500;
    margin-bottom: 5px;
    font-size: 0.9em;
}

.note-category {
    display: inline-block;
    background: #e9ecef;
    color: #495057;
    padding: 2px 8px;
    border-radius: 10px;
    font-size: 0.8em;
    margin-bottom: 10px;
}

.note-description {
    color: #666;
    font-size: 0.9em;
    line-height: 1.4;
    margin: 0;
}

.note-indicator {
    margin-left: 15px;
}

.category-dot {
    width: 20px;
    height: 20px;
    border-radius: 50%;
    display: block;
    border: 3px solid white;
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
}

.category-dot.citrus { background: #ffc107; }
.category-dot.floral { background: #e83e8c; }
.category-dot.woody { background: #17a2b8; }
.category-dot.oriental { background: #dc3545; }
.category-dot.fruity { background: #28a745; }
.category-dot.spicy { background: #fd7e14; }
.category-dot.other { background: #6c757d; }

/* Back to top */
.back-to-top {
    position: fixed;
    bottom: 30px;
    right: 30px;
    background: #28a745;
    color: white;
    border: none;
    border-radius: 50px;
    padding: 12px 20px;
    cursor: pointer;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    transition: all 0.3s ease;
    z-index: 1000;
    display: flex;
    align-items: center;
    gap: 5px;
}

.back-to-top:hover {
    background: #218838;
    transform: translateY(-2px);
}

/* Archive header */
.archive-header {
    text-align: center;
    margin-bottom: 40px;
}

.archive-title {
    font-size: 2.5em;
    margin-bottom: 10px;
    color: #333;
}

.archive-description {
    font-size: 1.1em;
    color: #666;
    margin: 0;
}

/* Responsive */
@media (max-width: 768px) {
    .alphabet-nav-inner {
        gap: 4px;
    }
    
    .letter-link {
        width: 30px;
        height: 30px;
        font-size: 12px;
    }
    
    .notes-grid {
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 15px;
    }
    
    .letter-heading {
        font-size: 2em;
    }
    
    .back-to-top {
        bottom: 20px;
        right: 20px;
        padding: 10px 15px;
    }
    
    .alphabet-navigation {
        position: static;
        padding: 15px;
    }
    
    .categories-list {
        gap: 8px;
    }
    
    .category-item {
        padding: 6px 12px;
        font-size: 0.8em;
    }
}

@media (max-width: 480px) {
    .notes-grid {
        grid-template-columns: 1fr;
    }
    
    .letter-link {
        width: 28px;
        height: 28px;
        font-size: 11px;
    }
    
    .note-link {
        flex-direction: column;
        text-align: center;
    }
    
    .note-indicator {
        margin-left: 0;
        margin-top: 10px;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Smooth scrolling for alphabet navigation
    const letterLinks = document.querySelectorAll('.letter-link.active');
    letterLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
                
                // Update URL without page reload
                history.pushState(null, null, this.getAttribute('href'));
            }
        });
    });
    
    // Back to top functionality
    const backToTopButton = document.getElementById('back-to-top');
    
    window.addEventListener('scroll', function() {
        if (window.pageYOffset > 300) {
            backToTopButton.style.display = 'flex';
        } else {
            backToTopButton.style.display = 'none';
        }
    });
    
    backToTopButton.addEventListener('click', function() {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    });
    
    // Highlight current letter in navigation
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const id = entry.target.id;
                const letter = id.replace('letter-', '').toUpperCase();
                
                // Remove current class from all links
                letterLinks.forEach(link => link.classList.remove('current'));
                
                // Add current class to current letter
                const currentLink = document.querySelector(`[data-letter="${letter}"]`);
                if (currentLink) {
                    currentLink.classList.add('current');
                }
            }
        });
    }, {
        rootMargin: '-100px 0px -50% 0px'
    });
    
    // Observe all letter sections
    document.querySelectorAll('.letter-section').forEach(section => {
        observer.observe(section);
    });
    
    // Handle URL hash on page load
    if (window.location.hash) {
        setTimeout(() => {
            const target = document.querySelector(window.location.hash);
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        }, 100);
    }
});
</script>

<?php
get_footer();
?>