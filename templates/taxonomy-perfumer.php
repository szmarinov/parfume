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
    </div>
    
    <!-- Perfumers Content -->
    <div class="perfumers-content">
        <?php if (!empty($perfumers_by_letter)): ?>
            <?php foreach ($available_letters as $letter): ?>
                <div class="letter-section" id="letter-<?php echo esc_attr(strtolower($letter)); ?>">
                    <h2 class="letter-heading"><?php echo esc_html($letter); ?></h2>
                    
                    <div class="perfumers-grid">
                        <?php foreach ($perfumers_by_letter[$letter] as $perfumer): ?>
                            <div class="perfumer-item">
                                <a href="<?php echo get_term_link($perfumer); ?>" class="perfumer-link">
                                    <?php
                                    $perfumer_photo = get_term_meta($perfumer->term_id, 'perfumer-image-id', true);
                                    ?>
                                    
                                    <div class="perfumer-photo">
                                        <?php if ($perfumer_photo): ?>
                                            <?php echo wp_get_attachment_image($perfumer_photo, 'thumbnail', false, array('alt' => $perfumer->name)); ?>
                                        <?php else: ?>
                                            <div class="perfumer-avatar">
                                                <span class="perfumer-initials">
                                                    <?php 
                                                    $name_parts = explode(' ', $perfumer->name);
                                                    $initials = '';
                                                    foreach (array_slice($name_parts, 0, 2) as $part) {
                                                        $initials .= mb_substr($part, 0, 1, 'UTF-8');
                                                    }
                                                    echo esc_html(mb_strtoupper($initials, 'UTF-8'));
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
                                            $average_rating = get_term_meta($perfumer->term_id, 'average_rating', true);
                                            if (!empty($average_rating)):
                                            ?>
                                                <span class="average-rating">
                                                    <span class="stars">
                                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                                            <span class="star <?php echo $i <= round($average_rating) ? 'filled' : ''; ?>">★</span>
                                                        <?php endfor; ?>
                                                    </span>
                                                    <span class="rating-number"><?php echo number_format($average_rating, 1); ?></span>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <?php if ($perfumer->description): ?>
                                            <p class="perfumer-description"><?php echo wp_trim_words(esc_html($perfumer->description), 20); ?></p>
                                        <?php endif; ?>
                                        
                                        <!-- Popular perfumes by this perfumer -->
                                        <?php
                                        $popular_perfumes = get_posts(array(
                                            'post_type' => 'parfume',
                                            'posts_per_page' => 3,
                                            'meta_key' => '_parfume_rating',
                                            'orderby' => 'meta_value_num',
                                            'order' => 'DESC',
                                            'tax_query' => array(
                                                array(
                                                    'taxonomy' => 'perfumer',
                                                    'field' => 'term_id',
                                                    'terms' => $perfumer->term_id,
                                                ),
                                            ),
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
    background: #667eea;
    color: white;
}

.letter-link.active:hover {
    background: #5a6fd8;
    transform: scale(1.1);
}

.letter-link.inactive {
    background: #e9ecef;
    color: #6c757d;
    cursor: not-allowed;
}

.letter-link.current {
    background: #4c63d2 !important;
    transform: scale(1.1);
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
    .alphabet-nav-inner {
        gap: 4px;
    }
    
    .letter-link {
        width: 30px;
        height: 30px;
        font-size: 12px;
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
        padding: 15px;
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
        width: 28px;
        height: 28px;
        font-size: 11px;
    }
    
    .stats-grid {
        grid-template-columns: 1fr;
        gap: 15px;
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