<?php
get_header();

// Get all perfumers ordered alphabetically
$all_perfumers = get_terms(array(
    'taxonomy' => 'perfumer',
    'hide_empty' => false,
    'orderby' => 'name',
    'order' => 'ASC',
));

// Group perfumers by first letter
$perfumers_by_letter = array();
$available_letters = array();

if (!empty($all_perfumers) && !is_wp_error($all_perfumers)) {
    foreach ($all_perfumers as $perfumer) {
        $first_letter = mb_strtoupper(mb_substr($perfumer->name, 0, 1, 'UTF-8'), 'UTF-8');
        
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
        
        if (!isset($perfumers_by_letter[$letter_key])) {
            $perfumers_by_letter[$letter_key] = array();
        }
        $perfumers_by_letter[$letter_key][] = $perfumer;
        
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
?>

<div class="parfume-perfumers-archive">
    <header class="archive-header">
        <h1 class="archive-title"><?php _e('All Perfumers', 'parfume-reviews'); ?></h1>
        <p class="archive-description"><?php _e('Meet the master perfumers behind your favorite fragrances', 'parfume-reviews'); ?></p>
    </header>
    
    <!-- Statistics Banner -->
    <div class="perfumers-stats">
        <div class="stats-grid">
            <div class="stat-item">
                <span class="stat-number"><?php echo count($all_perfumers); ?></span>
                <span class="stat-label"><?php _e('Total Perfumers', 'parfume-reviews'); ?></span>
            </div>
            <div class="stat-item">
                <span class="stat-number"><?php echo count($available_letters); ?></span>
                <span class="stat-label"><?php _e('Letters Covered', 'parfume-reviews'); ?></span>
            </div>
            <div class="stat-item">
                <?php 
                $total_perfumes = 0;
                foreach ($all_perfumers as $perfumer) {
                    $total_perfumes += $perfumer->count;
                }
                ?>
                <span class="stat-number"><?php echo $total_perfumes; ?></span>
                <span class="stat-label"><?php _e('Total Perfumes', 'parfume-reviews'); ?></span>
            </div>
        </div>
    </div>
    
    <!-- Enhanced Alphabet Navigation -->
    <div class="alphabet-navigation">
        <div class="alphabet-nav-header">
            <h3><?php _e('Quick Navigation', 'parfume-reviews'); ?></h3>
            <p><?php _e('Jump to perfumers by letter', 'parfume-reviews'); ?></p>
        </div>
        
        <div class="alphabet-nav-inner">
            <!-- Latin Letters -->
            <div class="alphabet-section">
                <span class="alphabet-section-label"><?php _e('A-Z', 'parfume-reviews'); ?></span>
                <div class="alphabet-letters">
                    <?php foreach ($latin_alphabet as $letter): ?>
                        <?php if (in_array($letter, $available_letters)): ?>
                            <a href="#letter-<?php echo esc_attr(strtolower($letter)); ?>" 
                               class="letter-link active" 
                               data-letter="<?php echo esc_attr($letter); ?>"
                               title="<?php printf(__('%d perfumers starting with %s', 'parfume-reviews'), count($perfumers_by_letter[$letter]), $letter); ?>">
                                <?php echo esc_html($letter); ?>
                                <span class="letter-count"><?php echo count($perfumers_by_letter[$letter]); ?></span>
                            </a>
                        <?php else: ?>
                            <span class="letter-link inactive" title="<?php _e('No perfumers', 'parfume-reviews'); ?>">
                                <?php echo esc_html($letter); ?>
                            </span>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Cyrillic Letters -->
            <div class="alphabet-section">
                <span class="alphabet-section-label"><?php _e('А-Я', 'parfume-reviews'); ?></span>
                <div class="alphabet-letters">
                    <?php foreach ($cyrillic_alphabet as $letter): ?>
                        <?php if (in_array($letter, $available_letters)): ?>
                            <a href="#letter-<?php echo esc_attr(strtolower($letter)); ?>" 
                               class="letter-link active" 
                               data-letter="<?php echo esc_attr($letter); ?>"
                               title="<?php printf(__('%d perfumers starting with %s', 'parfume-reviews'), count($perfumers_by_letter[$letter]), $letter); ?>">
                                <?php echo esc_html($letter); ?>
                                <span class="letter-count"><?php echo count($perfumers_by_letter[$letter]); ?></span>
                            </a>
                        <?php else: ?>
                            <span class="letter-link inactive" title="<?php _e('No perfumers', 'parfume-reviews'); ?>">
                                <?php echo esc_html($letter); ?>
                            </span>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Numbers/Special -->
            <?php if (in_array('#', $available_letters)): ?>
                <div class="alphabet-section">
                    <span class="alphabet-section-label"><?php _e('Other', 'parfume-reviews'); ?></span>
                    <div class="alphabet-letters">
                        <a href="#letter-other" 
                           class="letter-link active special" 
                           data-letter="#"
                           title="<?php printf(__('%d perfumers with numbers/symbols', 'parfume-reviews'), count($perfumers_by_letter['#'])); ?>">
                            #
                            <span class="letter-count"><?php echo count($perfumers_by_letter['#']); ?></span>
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Quick jump buttons -->
        <div class="alphabet-quick-actions">
            <button class="scroll-to-top" onclick="window.scrollTo({top: 0, behavior: 'smooth'})">
                <span class="dashicons dashicons-arrow-up-alt2"></span>
                <?php _e('Top', 'parfume-reviews'); ?>
            </button>
            <button class="toggle-all-letters" onclick="toggleAllLetters()">
                <span class="dashicons dashicons-visibility"></span>
                <?php _e('Show All', 'parfume-reviews'); ?>
            </button>
        </div>
    </div>
    
    <!-- Perfumers by Letter -->
    <div class="perfumers-content">
        <?php if (!empty($perfumers_by_letter)): ?>
            <?php foreach ($available_letters as $letter): ?>
                <div class="letter-section" id="letter-<?php echo esc_attr(strtolower($letter) === '#' ? 'other' : strtolower($letter)); ?>">
                    <h2 class="letter-heading"><?php echo esc_html($letter === '#' ? __('Numbers & Symbols', 'parfume-reviews') : $letter); ?></h2>
                    <div class="perfumers-grid">
                        <?php foreach ($perfumers_by_letter[$letter] as $perfumer): ?>
                            <div class="perfumer-item">
                                <a href="<?php echo get_term_link($perfumer); ?>" class="perfumer-link">
                                    <div class="perfumer-photo">
                                        <?php 
                                        $perfumer_photo = get_term_meta($perfumer->term_id, 'perfumer_photo', true);
                                        if (!empty($perfumer_photo)): ?>
                                            <img src="<?php echo esc_url($perfumer_photo); ?>" alt="<?php echo esc_attr($perfumer->name); ?>">
                                        <?php else: ?>
                                            <div class="perfumer-avatar">
                                                <span class="perfumer-initials">
                                                    <?php 
                                                    $name_parts = explode(' ', $perfumer->name);
                                                    echo esc_html(substr($name_parts[0], 0, 1));
                                                    if (isset($name_parts[1])) {
                                                        echo esc_html(substr($name_parts[1], 0, 1));
                                                    }
                                                    ?>
                                                </span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="perfumer-info">
                                        <h3 class="perfumer-name"><?php echo esc_html($perfumer->name); ?></h3>
                                        
                                        <div class="perfumer-stats">
                                            <span class="perfume-count">
                                                <?php printf(_n('%d perfume', '%d perfumes', $perfumer->count, 'parfume-reviews'), $perfumer->count); ?>
                                            </span>
                                            
                                            <?php
                                            // Calculate average rating for this perfumer
                                            $perfumer_perfumes = get_posts(array(
                                                'post_type' => 'parfume',
                                                'tax_query' => array(
                                                    array(
                                                        'taxonomy' => 'perfumer',
                                                        'field' => 'term_id',
                                                        'terms' => $perfumer->term_id,
                                                    ),
                                                ),
                                                'posts_per_page' => -1,
                                                'fields' => 'ids'
                                            ));
                                            
                                            $total_rating = 0;
                                            $rated_count = 0;
                                            
                                            foreach ($perfumer_perfumes as $perfume_id) {
                                                $rating = get_post_meta($perfume_id, '_parfume_rating', true);
                                                if (!empty($rating) && is_numeric($rating)) {
                                                    $total_rating += floatval($rating);
                                                    $rated_count++;
                                                }
                                            }
                                            
                                            if ($rated_count > 0):
                                                $average_rating = $total_rating / $rated_count;
                                            ?>
                                                <div class="average-rating">
                                                    <div class="stars">
                                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                                            <span class="star <?php echo $i <= $average_rating ? 'filled' : ''; ?>">★</span>
                                                        <?php endfor; ?>
                                                    </div>
                                                    <span class="rating-number"><?php echo number_format($average_rating, 1); ?></span>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <?php if (!empty($perfumer->description)): ?>
                                            <div class="perfumer-description">
                                                <?php echo esc_html(wp_trim_words($perfumer->description, 15)); ?>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <?php
                                        // Get most popular perfumes for this perfumer
                                        $popular_perfumes = get_posts(array(
                                            'post_type' => 'parfume',
                                            'tax_query' => array(
                                                array(
                                                    'taxonomy' => 'perfumer',
                                                    'field' => 'term_id',
                                                    'terms' => $perfumer->term_id,
                                                ),
                                            ),
                                            'posts_per_page' => 3,
                                            'meta_key' => '_parfume_rating',
                                            'orderby' => 'meta_value_num',
                                            'order' => 'DESC',
                                            'meta_query' => array(
                                                array(
                                                    'key' => '_parfume_rating',
                                                    'value' => '',
                                                    'compare' => '!='
                                                )
                                            )
                                        ));
                                        
                                        if (!empty($popular_perfumes)): 
                                        ?>
                                            <div class="popular-perfumes">
                                                <span class="popular-label"><?php _e('Popular:', 'parfume-reviews'); ?></span>
                                                <?php 
                                                $perfume_names = array();
                                                foreach ($popular_perfumes as $perfume) {
                                                    $perfume_names[] = $perfume->post_title;
                                                }
                                                echo esc_html(implode(', ', $perfume_names));
                                                ?>
                                            </div>
                                        <?php 
                                        endif;
                                        wp_reset_postdata();
                                        ?>
                                    </div>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="no-perfumers"><?php _e('No perfumers found.', 'parfume-reviews'); ?></p>
        <?php endif; ?>
    </div>
    
    <!-- Back to top button -->
    <button id="back-to-top" class="back-to-top" style="display: none;">
        <span class="dashicons dashicons-arrow-up-alt2"></span>
        <?php _e('Top', 'parfume-reviews'); ?>
    </button>
</div>

<style>
/* ========================================
   ENHANCED ALPHABET NAVIGATION
   ======================================== */

.alphabet-navigation {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border: 2px solid #dee2e6;
    border-radius: 15px;
    padding: 25px;
    margin: 30px 0;
    position: sticky;
    top: 32px;
    z-index: 100;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
    backdrop-filter: blur(10px);
}

.alphabet-nav-header {
    text-align: center;
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 2px solid #dee2e6;
}

.alphabet-nav-header h3 {
    margin: 0 0 5px 0;
    color: #333;
    font-size: 1.4em;
    font-weight: 600;
}

.alphabet-nav-header p {
    margin: 0;
    color: #666;
    font-size: 0.9em;
}

.alphabet-nav-inner {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.alphabet-section {
    display: flex;
    align-items: center;
    gap: 15px;
    flex-wrap: wrap;
}

.alphabet-section-label {
    font-weight: bold;
    color: #667eea;
    font-size: 1.1em;
    min-width: 40px;
    text-align: center;
    background: white;
    padding: 8px 12px;
    border-radius: 8px;
    border: 2px solid #667eea;
    flex-shrink: 0;
}

.alphabet-letters {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    flex: 1;
}

.letter-link {
    position: relative;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex-direction: column;
    width: 45px;
    height: 45px;
    border-radius: 8px;
    text-decoration: none;
    font-weight: bold;
    font-size: 16px;
    transition: all 0.3s ease;
    text-align: center;
    border: 2px solid transparent;
}

.letter-link.active {
    background: #667eea;
    color: white;
    box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);
}

.letter-link.active:hover {
    background: #5a67d8;
    transform: translateY(-2px) scale(1.05);
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
}

.letter-link.inactive {
    background: #f1f3f4;
    color: #9ca3af;
    cursor: not-allowed;
    opacity: 0.6;
}

.letter-link.special {
    background: #28a745;
}

.letter-link.special:hover {
    background: #218838;
}

.letter-link.current {
    background: #dc3545 !important;
    transform: scale(1.1);
    box-shadow: 0 4px 15px rgba(220, 53, 69, 0.4);
}

.letter-count {
    position: absolute;
    top: -8px;
    right: -8px;
    background: #ffc107;
    color: #333;
    font-size: 10px;
    font-weight: bold;
    padding: 2px 6px;
    border-radius: 12px;
    min-width: 16px;
    height: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
}

.alphabet-quick-actions {
    display: flex;
    justify-content: center;
    gap: 15px;
    margin-top: 20px;
    padding-top: 15px;
    border-top: 1px solid #dee2e6;
}

.scroll-to-top,
.toggle-all-letters {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 15px;
    background: #667eea;
    color: white;
    border: none;
    border-radius: 20px;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);
}

.scroll-to-top:hover,
.toggle-all-letters:hover {
    background: #5a67d8;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
}

/* Perfumers Stats */
.perfumers-stats {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 12px;
    padding: 30px;
    margin: 30px 0;
    color: white;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 30px;
    text-align: center;
}

.stat-item {
    display: flex;
    flex-direction: column;
    align-items: center;
}

.stat-number {
    font-size: 2.5em;
    font-weight: bold;
    margin-bottom: 5px;
    text-shadow: 0 2px 4px rgba(0,0,0,0.3);
}

.stat-label {
    font-size: 1.1em;
    opacity: 0.9;
}

/* Letter Sections */
.letter-section {
    margin-bottom: 50px;
}

.letter-heading {
    font-size: 2.5em;
    color: #667eea;
    border-bottom: 3px solid #667eea;
    padding-bottom: 10px;
    margin-bottom: 30px;
    scroll-margin-top: 100px;
}

/* Perfumers Grid */
.perfumers-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 25px;
}

.perfumer-item {
    background: white;
    border: 1px solid #dee2e6;
    border-radius: 15px;
    overflow: hidden;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.perfumer-item:hover {
    transform: translateY(-8px);
    box-shadow: 0 12px 35px rgba(102, 126, 234, 0.3);
    border-color: #667eea;
}

.perfumer-link {
    display: block;
    padding: 25px;
    text-decoration: none;
    color: inherit;
    height: 100%;
}

.perfumer-photo {
    text-align: center;
    margin-bottom: 20px;
}

.perfumer-photo img {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    object-fit: cover;
    border: 4px solid #667eea;
}

.perfumer-avatar {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea, #764ba2);
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto;
    border: 4px solid #f8f9fa;
    box-shadow: 0 4px 15px rgba(0,0,0,0.2);
}

.perfumer-initials {
    color: white;
    font-size: 1.5em;
    font-weight: bold;
    text-shadow: 0 2px 4px rgba(0,0,0,0.3);
}

.perfumer-info {
    text-align: center;
}

.perfumer-name {
    font-size: 1.3em;
    font-weight: bold;
    margin: 0 0 12px;
    color: #333;
}

.perfumer-stats {
    margin-bottom: 15px;
}

.perfume-count {
    display: block;
    color: #667eea;
    font-weight: 500;
    margin-bottom: 8px;
}

.average-rating {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 5px;
}

.stars {
    color: #ffc107;
}

.star.filled {
    color: #ffc107;
}

.star {
    color: #e9ecef;
}

.rating-number {
    font-weight: bold;
    color: #666;
}

.perfumer-description {
    color: #666;
    font-size: 0.9em;
    line-height: 1.5;
    margin: 0 0 15px;
}

.popular-perfumes {
    background: #f8f9fa;
    padding: 10px;
    border-radius: 8px;
    font-size: 0.85em;
    margin-top: 15px;
}

.popular-label {
    font-weight: bold;
    color: #667eea;
    display: block;
    margin-bottom: 5px;
}

/* Back to top */
.back-to-top {
    position: fixed;
    bottom: 30px;
    right: 30px;
    background: #667eea;
    color: white;
    border: none;
    border-radius: 50px;
    padding: 12px 20px;
    cursor: pointer;
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
    transition: all 0.3s ease;
    z-index: 1000;
    display: flex;
    align-items: center;
    gap: 5px;
}

.back-to-top:hover {
    background: #5a6fd8;
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
    .alphabet-section {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }
    
    .alphabet-section-label {
        align-self: center;
    }
    
    .letter-link {
        width: 35px;
        height: 35px;
        font-size: 14px;
    }
    
    .letter-count {
        top: -6px;
        right: -6px;
        font-size: 9px;
        min-width: 14px;
        height: 14px;
    }
    
    .perfumers-grid {
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 20px;
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
        padding: 20px;
    }
    
    .stats-grid {
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 20px;
    }
    
    .stat-number {
        font-size: 2em;
    }
    
    .perfumers-stats {
        padding: 20px;
    }
}

@media (max-width: 480px) {
    .perfumers-grid {
        grid-template-columns: 1fr;
    }
    
    .letter-link {
        width: 30px;
        height: 30px;
        font-size: 12px;
    }
    
    .stats-grid {
        grid-template-columns: 1fr;
        gap: 15px;
    }
    
    .alphabet-quick-actions {
        flex-direction: column;
        gap: 10px;
    }
}
</style>

<script>
// Fix for initSocialWidgets error
window.initSocialWidgets = function() {
    console.log('Social widgets initialized');
};

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
                
                // Highlight current letter
                document.querySelectorAll('.letter-link').forEach(l => l.classList.remove('current'));
                this.classList.add('current');
            }
        });
    });
    
    // Back to top functionality
    const backToTopButton = document.getElementById('back-to-top');
    
    if (backToTopButton) {
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
    }
    
    // Highlight current section while scrolling
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const sectionId = entry.target.id;
                const letter = sectionId.replace('letter-', '').toUpperCase();
                
                // Remove current class from all letters
                document.querySelectorAll('.letter-link').forEach(l => l.classList.remove('current'));
                
                // Add current class to the active letter
                const activeLink = document.querySelector(`[data-letter="${letter}"]`) || 
                                 document.querySelector(`[data-letter="#"]`);
                if (activeLink) {
                    activeLink.classList.add('current');
                }
            }
        });
    }, {
        rootMargin: '-20% 0px -70% 0px'
    });
    
    // Observe all letter sections
    document.querySelectorAll('.letter-section').forEach(section => {
        observer.observe(section);
    });
});

// Toggle function for showing all letters
function toggleAllLetters() {
    const inactiveLetters = document.querySelectorAll('.letter-link.inactive');
    const button = document.querySelector('.toggle-all-letters');
    
    if (inactiveLetters.length > 0 && button) {
        inactiveLetters.forEach(letter => {
            if (letter.style.display === 'none') {
                letter.style.display = 'inline-flex';
                button.innerHTML = '<span class="dashicons dashicons-hidden"></span>Hide Empty';
            } else {
                letter.style.display = 'none';
                button.innerHTML = '<span class="dashicons dashicons-visibility"></span>Show All';
            }
        });
    }
}
</script>

<?php get_footer(); ?>