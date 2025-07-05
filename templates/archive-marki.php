<?php
/**
 * Template for All Brands archive page (/parfiumi/marki/)
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header(); 
?>

<div class="parfume-archive brands-archive">
    <div class="archive-header">
        <h1 class="archive-title"><?php _e('All Brands', 'parfume-reviews'); ?></h1>
        <div class="archive-description">
            <p><?php _e('Browse perfumes by brand. Discover your favorite fragrance houses and explore their collections.', 'parfume-reviews'); ?></p>
        </div>
    </div>

    <div class="archive-content">
        <div class="archive-main">
            <?php
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
            $latin_alphabet = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z');
            $cyrillic_alphabet = array('А', 'Б', 'В', 'Г', 'Д', 'Е', 'Ж', 'З', 'И', 'Й', 'К', 'Л', 'М', 'Н', 'О', 'П', 'Р', 'С', 'Т', 'У', 'Ф', 'Х', 'Ц', 'Ч', 'Ш', 'Щ', 'Ъ', 'Ь', 'Ю', 'Я');
            $full_alphabet = array_merge($latin_alphabet, $cyrillic_alphabet, array('#'));
            ?>
            
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
                                            <?php 
                                            $brand_image_id = get_term_meta($brand->term_id, 'marki-image-id', true);
                                            if ($brand_image_id): 
                                            ?>
                                                <div class="brand-logo">
                                                    <?php echo wp_get_attachment_image($brand_image_id, 'thumbnail', false, array('alt' => $brand->name)); ?>
                                                </div>
                                            <?php else: ?>
                                                <div class="brand-logo brand-placeholder">
                                                    <span class="brand-icon">🏷️</span>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <div class="brand-info">
                                                <h3 class="brand-name"><?php echo esc_html($brand->name); ?></h3>
                                                <span class="brand-count">
                                                    <?php printf(_n('%d парфюм', '%d парфюма', $brand->count, 'parfume-reviews'), $brand->count); ?>
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
        </div>
    </div>
</div>

<style>
.alphabet-navigation { 
    background: #f8f9fa; 
    border: 1px solid #dee2e6; 
    border-radius: 8px; 
    padding: 20px; 
    margin: 30px 0; 
}
.alphabet-nav-inner { 
    display: flex; 
    flex-wrap: wrap; 
    gap: 8px; 
    justify-content: center; 
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
.letter-link.inactive { 
    background: #e9ecef; 
    color: #6c757d; 
    cursor: not-allowed; 
}
.letter-heading { 
    font-size: 2.5em; 
    color: #0073aa; 
    border-bottom: 3px solid #0073aa; 
    padding-bottom: 10px; 
    margin-bottom: 30px; 
}
.brands-grid { 
    display: grid; 
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); 
    gap: 20px; 
    margin-bottom: 40px;
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
    max-height: 80px;
    object-fit: contain;
}
.brand-placeholder {
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    width: 80px;
    height: 80px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto;
    border-radius: 4px;
}
.brand-icon {
    font-size: 32px;
}
.brand-name { 
    font-size: 1.2em; 
    font-weight: bold; 
    margin: 0 0 8px; 
    color: #333; 
    text-align: center;
}
.brand-count { 
    display: block; 
    color: #0073aa; 
    font-weight: 500; 
    margin-bottom: 10px; 
    text-align: center;
}
.brand-description { 
    color: #666; 
    font-size: 0.9em; 
    line-height: 1.4; 
    margin: 0; 
    text-align: center;
}
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

<?php get_footer(); ?>