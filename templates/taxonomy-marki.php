<?php
get_header();

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
        
        if (!isset($brands_by_letter[$letter_key])) {
            $brands_by_letter[$letter_key] = array();
        }
        $brands_by_letter[$letter_key][] = $brand;
        
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

<div class="parfume-brands-archive">
    <header class="archive-header">
        <h1 class="archive-title"><?php _e('All Brands', 'parfume-reviews'); ?></h1>
        <p class="archive-description"><?php _e('Browse perfume brands alphabetically', 'parfume-reviews'); ?></p>
    </header>
    
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
    
    <!-- Brands Content -->
    <div class="brands-content">
        <?php if (!empty($brands_by_letter)): ?>
            <?php foreach ($available_letters as $letter): ?>
                <div class="letter-section" id="letter-<?php echo esc_attr(strtolower($letter)); ?>">
                    <h2 class="letter-heading"><?php echo esc_html($letter); ?></h2>
                    
                    <div class="brands-grid">
                        <?php foreach ($brands_by_letter[$letter] as $brand): ?>
                            <div class="brand-item">
                                <a href="<?php echo get_term_link($brand); ?>" class="brand-link">
                                    <?php
                                    $brand_logo = get_term_meta($brand->term_id, 'brand-image-id', true);
                                    if ($brand_logo):
                                    ?>
                                        <div class="brand-logo">
                                            <?php echo wp_get_attachment_image($brand_logo, 'thumbnail', false, array('alt' => $brand->name)); ?>
                                        </div>
                                    <?php endif; ?>
                                    
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
            <p class="no-brands"><?php _e('No brands found.', 'parfume-reviews'); ?></p>
        <?php endif; ?>
    </div>
    
    <!-- Back to top button -->
    <button id="back-to-top" class="back-to-top" style="display: none;">
        <span class="dashicons dashicons-arrow-up-alt2"></span>
        <?php _e('Top', 'parfume-reviews'); ?>
    </button>
</div>

<style>
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
    background: #0073aa;
    color: white;
}

.letter-link.active:hover {
    background: #005a87;
    transform: scale(1.1);
}

.letter-link.inactive {
    background: #e9ecef;
    color: #6c757d;
    cursor: not-allowed;
}

/* Letter Sections */
.letter-section {
    margin-bottom: 50px;
}

.letter-heading {
    font-size: 2.5em;
    color: #0073aa;
    border-bottom: 3px solid #0073aa;
    padding-bottom: 10px;
    margin-bottom: 30px;
    scroll-margin-top: 100px;
}

/* Brands Grid */
.brands-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 20px;
}

.brand-item {
    background: white;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    overflow: hidden;
    transition: all 0.3s ease;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.brand-item:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
    border-color: #0073aa;
}

.brand-link {
    display: block;
    padding: 20px;
    text-decoration: none;
    color: inherit;
}

.brand-logo {
    text-align: center;
    margin-bottom: 15px;
}

.brand-logo img {
    max-width: 80px;
    max-height: 60px;
    object-fit: contain;
}

.brand-info {
    text-align: center;
}

.brand-name {
    font-size: 1.2em;
    font-weight: bold;
    margin: 0 0 8px;
    color: #333;
}

.brand-count {
    display: block;
    color: #0073aa;
    font-weight: 500;
    margin-bottom: 10px;
}

.brand-description {
    color: #666;
    font-size: 0.9em;
    line-height: 1.4;
    margin: 0;
}

/* Back to top */
.back-to-top {
    position: fixed;
    bottom: 30px;
    right: 30px;
    background: #0073aa;
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
    background: #005a87;
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
    
    .brands-grid {
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
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
}

@media (max-width: 480px) {
    .brands-grid {
        grid-template-columns: 1fr;
    }
    
    .letter-link {
        width: 28px;
        height: 28px;
        font-size: 11px;
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
                
                // Remove active class from all links
                letterLinks.forEach(link => link.classList.remove('current'));
                
                // Add active class to current letter
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

<style>
.letter-link.current {
    background: #005a87 !important;
    transform: scale(1.1);
}
</style>

<?php
get_footer();
?>