<?php
/**
 * Archive template for Perfumer taxonomy
 * Displays all perfumers with alphabet navigation like brands archive
 * 
 * Template: templates/archive-perfumer.php
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header(); 

// Debug информация (само в WP_DEBUG режим)
if (defined('WP_DEBUG') && WP_DEBUG) {
    echo "<!-- PERFUMER ARCHIVE TEMPLATE LOADED -->\n";
}
?>

<div class="parfume-archive perfumers-archive">
    <div class="archive-header">
        <div class="container">
            <!-- Breadcrumb навигация -->
            <nav class="breadcrumb">
                <a href="<?php echo home_url(); ?>"><?php _e('Начало', 'parfume-reviews'); ?></a>
                <span class="separator"> › </span>
                <a href="<?php echo home_url('/parfiumi/'); ?>"><?php _e('Парфюми', 'parfume-reviews'); ?></a>
                <span class="separator"> › </span>
                <span class="current"><?php _e('Парфюмеристи', 'parfume-reviews'); ?></span>
            </nav>
            
            <h1 class="archive-title"><?php _e('Всички Парфюмеристи', 'parfume-reviews'); ?></h1>
            <div class="archive-description">
                <p><?php _e('Открийте парфюми по техните създатели. Разгледайте колекциите на най-известните парфюмеристи в света.', 'parfume-reviews'); ?></p>
            </div>
        </div>
    </div>

    <div class="archive-content">
        <div class="container">
            <div class="archive-main">
                
                <?php
                // Получаваме всички парфюмеристи подредени по азбучен ред
                $all_perfumers = get_terms(array(
                    'taxonomy' => 'perfumer',
                    'hide_empty' => false,
                    'orderby' => 'name',
                    'order' => 'ASC',
                ));

                // Групираме парфюмеристите по първа буква
                $perfumers_by_letter = array();
                $available_letters = array();

                if (!empty($all_perfumers) && !is_wp_error($all_perfumers)) {
                    foreach ($all_perfumers as $perfumer) {
                        $first_letter = mb_strtoupper(mb_substr($perfumer->name, 0, 1, 'UTF-8'), 'UTF-8');
                        
                        if (preg_match('/[А-Я]/u', $first_letter)) {
                            $letter_key = $first_letter;
                        } elseif (preg_match('/[A-Z]/', $first_letter)) {
                            $letter_key = $first_letter;
                        } else {
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

                sort($available_letters);
                $latin_alphabet = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z');
                $cyrillic_alphabet = array('А', 'Б', 'В', 'Г', 'Д', 'Е', 'Ж', 'З', 'И', 'Й', 'К', 'Л', 'М', 'Н', 'О', 'П', 'Р', 'С', 'Т', 'У', 'Ф', 'Х', 'Ц', 'Ч', 'Ш', 'Щ', 'Ъ', 'Ь', 'Ю', 'Я');
                $full_alphabet = array_merge($latin_alphabet, $cyrillic_alphabet, array('#'));
                ?>
                
                <!-- Sorting and Filter Controls -->
                <div class="perfumers-controls">
                    <div class="sort-controls">
                        <label for="perfumer-sort"><?php _e('Сортирай по:', 'parfume-reviews'); ?></label>
                        <select id="perfumer-sort" class="sort-select">
                            <option value="alphabet"><?php _e('Азбучен ред', 'parfume-reviews'); ?></option>
                            <option value="count-desc"><?php _e('Най-много парфюми', 'parfume-reviews'); ?></option>
                            <option value="count-asc"><?php _e('Най-малко парфюми', 'parfume-reviews'); ?></option>
                        </select>
                    </div>
                    
                    <div class="filter-controls">
                        <label for="perfumer-filter"><?php _e('Филтрирай:', 'parfume-reviews'); ?></label>
                        <select id="perfumer-filter" class="filter-select">
                            <option value="all"><?php _e('Всички парфюмеристи', 'parfume-reviews'); ?></option>
                            <option value="popular"><?php _e('Популярни (5+ парфюма)', 'parfume-reviews'); ?></option>
                            <option value="top"><?php _e('Топ (10+ парфюма)', 'parfume-reviews'); ?></option>
                            <option value="legends"><?php _e('Легенди (20+ парфюма)', 'parfume-reviews'); ?></option>
                        </select>
                    </div>
                </div>

                <!-- Alphabet Navigation - същия като в marki -->
                <div class="alphabet-navigation" id="alphabet-nav">
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
                
                <!-- Perfumers Content -->
                <div class="perfumers-content">
                    <?php if (!empty($perfumers_by_letter)): ?>
                        <?php foreach ($available_letters as $letter): ?>
                            <div class="letter-section" id="letter-<?php echo esc_attr(strtolower($letter)); ?>">
                                <h2 class="letter-heading"><?php echo esc_html($letter); ?></h2>
                                
                                <div class="perfumers-grid">
                                    <?php foreach ($perfumers_by_letter[$letter] as $perfumer): 
                                        // Получаваме мета данни
                                        $image_id = get_term_meta($perfumer->term_id, 'perfumer-image-id', true);
                                        $birth_year = get_term_meta($perfumer->term_id, 'birth_year', true);
                                        $nationality = get_term_meta($perfumer->term_id, 'nationality', true);
                                        ?>
                                        <div class="perfumer-item" data-count="<?php echo esc_attr($perfumer->count); ?>" data-letter="<?php echo esc_attr($letter); ?>">
                                            <a href="<?php echo get_term_link($perfumer); ?>" class="perfumer-link">
                                                <?php if ($image_id): ?>
                                                    <div class="perfumer-logo">
                                                        <?php echo wp_get_attachment_image($image_id, 'thumbnail', false, array('alt' => $perfumer->name)); ?>
                                                    </div>
                                                <?php else: ?>
                                                    <div class="perfumer-logo perfumer-placeholder">
                                                        <span class="perfumer-icon">👤</span>
                                                    </div>
                                                <?php endif; ?>
                                                
                                                <div class="perfumer-info">
                                                    <h3 class="perfumer-name"><?php echo esc_html($perfumer->name); ?></h3>
                                                    <span class="perfumer-count">
                                                        <?php printf(_n('%d парфюм', '%d парфюма', $perfumer->count, 'parfume-reviews'), $perfumer->count); ?>
                                                    </span>
                                                    
                                                    <?php if ($nationality || $birth_year): ?>
                                                        <div class="perfumer-meta">
                                                            <?php if ($nationality): ?>
                                                                <span class="nationality"><?php echo esc_html($nationality); ?></span>
                                                            <?php endif; ?>
                                                            <?php if ($birth_year): ?>
                                                                <span class="birth-year"><?php echo esc_html($birth_year); ?></span>
                                                            <?php endif; ?>
                                                        </div>
                                                    <?php endif; ?>
                                                    
                                                    <!-- Perfumer Level Badge -->
                                                    <?php 
                                                    $level_class = '';
                                                    $level_text = '';
                                                    if ($perfumer->count >= 20) {
                                                        $level_class = 'legend';
                                                        $level_text = __('Легенда', 'parfume-reviews');
                                                    } elseif ($perfumer->count >= 10) {
                                                        $level_class = 'top';
                                                        $level_text = __('Топ', 'parfume-reviews');
                                                    } elseif ($perfumer->count >= 5) {
                                                        $level_class = 'popular';
                                                        $level_text = __('Популярен', 'parfume-reviews');
                                                    }
                                                    
                                                    if ($level_text): ?>
                                                        <div class="perfumer-level <?php echo esc_attr($level_class); ?>">
                                                            <?php echo esc_html($level_text); ?>
                                                        </div>
                                                    <?php endif; ?>
                                                    
                                                    <?php if ($perfumer->description): ?>
                                                        <p class="perfumer-description"><?php echo wp_trim_words(esc_html($perfumer->description), 15); ?></p>
                                                    <?php endif; ?>
                                                </div>
                                            </a>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="no-perfumers"><?php _e('Няма намерени парфюмеристи.', 'parfume-reviews'); ?></p>
                    <?php endif; ?>
                </div>

            </div>
        </div>
    </div>
</div>

<style>
/* Основни стилове за archive-perfumer.php - БАЗИРАНИ НА MARKI */
.perfumers-archive {
    background: #fafafa;
    min-height: calc(100vh - 200px);
}

.archive-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 60px 0;
    text-align: center;
    position: relative;
}

.archive-header::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.1);
}

.archive-header .container {
    position: relative;
    z-index: 2;
}

.breadcrumb {
    margin-bottom: 20px;
    font-size: 14px;
}

.breadcrumb a {
    color: rgba(255, 255, 255, 0.8);
    text-decoration: none;
    transition: color 0.3s ease;
}

.breadcrumb a:hover {
    color: white;
    text-decoration: underline;
}

.breadcrumb .separator {
    color: rgba(255, 255, 255, 0.6);
    margin: 0 10px;
}

.breadcrumb .current {
    color: white;
    font-weight: 600;
}

.archive-title {
    font-size: 3rem;
    font-weight: 700;
    margin-bottom: 20px;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
}

.archive-description {
    font-size: 1.1rem;
    max-width: 600px;
    margin: 0 auto;
    line-height: 1.6;
    color: rgba(255, 255, 255, 0.9);
}

.archive-content {
    padding: 50px 0;
}

.container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
}

/* Sorting and Filter Controls */
.perfumers-controls {
    background: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    margin-bottom: 20px;
    display: flex;
    gap: 30px;
    align-items: center;
    flex-wrap: wrap;
}

.sort-controls,
.filter-controls {
    display: flex;
    align-items: center;
    gap: 10px;
}

.sort-controls label,
.filter-controls label {
    font-weight: 600;
    color: #333;
    font-size: 14px;
}

.sort-select,
.filter-select {
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    background: white;
    font-size: 14px;
    min-width: 180px;
    cursor: pointer;
    transition: border-color 0.3s ease;
}

.sort-select:focus,
.filter-select:focus {
    outline: none;
    border-color: #0073aa;
    box-shadow: 0 0 0 2px rgba(0, 115, 170, 0.1);
}

/* Perfumer Level Badges */
.perfumer-level {
    display: inline-block;
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 0.75em;
    font-weight: bold;
    text-transform: uppercase;
    margin-top: 5px;
}

.perfumer-level.legend {
    background: linear-gradient(45deg, #ff6b6b, #ffd93d);
    color: white;
    box-shadow: 0 2px 4px rgba(255, 107, 107, 0.3);
}

.perfumer-level.top {
    background: linear-gradient(45deg, #4ecdc4, #44a08d);
    color: white;
    box-shadow: 0 2px 4px rgba(78, 205, 196, 0.3);
}

.perfumer-level.popular {
    background: linear-gradient(45deg, #667eea, #764ba2);
    color: white;
    box-shadow: 0 2px 4px rgba(102, 126, 234, 0.3);
}

/* Alphabet Navigation - СЪЩИЯ КАТО MARKI */
.alphabet-navigation {
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 20px;
    margin: 30px 0;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
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
    box-shadow: 0 2px 5px rgba(0, 115, 170, 0.3);
}

.letter-link.active:hover {
    background: #005a87;
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0, 115, 170, 0.4);
}

.letter-link.inactive {
    background: #e9ecef;
    color: #6c757d;
    cursor: not-allowed;
}

/* Letter Sections */
.letter-heading {
    font-size: 2.5em;
    color: #0073aa;
    border-bottom: 3px solid #0073aa;
    padding-bottom: 10px;
    margin-bottom: 30px;
    font-weight: 700;
}

/* Perfumers Grid - ПОДОБЕН НА BRANDS */
.perfumers-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 20px;
    margin-bottom: 40px;
}

.perfumer-item {
    background: white;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    overflow: hidden;
    transition: all 0.3s ease;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.perfumer-item:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
    border-color: #0073aa;
}

.perfumer-link {
    display: block;
    padding: 20px;
    text-decoration: none;
    color: inherit;
}

.perfumer-logo {
    text-align: center;
    margin-bottom: 15px;
}

.perfumer-logo img {
    max-width: 80px;
    max-height: 80px;
    object-fit: cover;
    border-radius: 50%;
    border: 3px solid #f0f0f0;
}

.perfumer-placeholder {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: 1px solid #dee2e6;
    width: 80px;
    height: 80px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto;
    border-radius: 50%;
    border: 3px solid #f0f0f0;
}

.perfumer-icon {
    font-size: 32px;
    color: white;
}

.perfumer-name {
    font-size: 1.2em;
    font-weight: bold;
    margin: 0 0 8px;
    color: #333;
    text-align: center;
}

.perfumer-count {
    display: block;
    color: #0073aa;
    font-weight: 500;
    margin-bottom: 10px;
    text-align: center;
    font-size: 0.9em;
}

.perfumer-meta {
    text-align: center;
    margin-bottom: 10px;
    font-size: 0.85em;
    color: #666;
}

.perfumer-meta .nationality,
.perfumer-meta .birth-year {
    display: inline-block;
    background: #f8f9fa;
    padding: 2px 6px;
    border-radius: 3px;
    margin: 2px;
}

.perfumer-description {
    color: #666;
    font-size: 0.9em;
    line-height: 1.4;
    margin: 0;
    text-align: center;
}

.no-perfumers {
    text-align: center;
    padding: 60px 20px;
    background: white;
    border-radius: 8px;
    margin: 40px 0;
    color: #666;
    font-size: 1.2em;
}

/* Responsive Design */
@media (max-width: 1024px) {
    .perfumers-grid {
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        gap: 15px;
    }
    
    .archive-title {
        font-size: 2.5rem;
    }
}

@media (max-width: 768px) {
    .perfumers-controls {
        flex-direction: column;
        align-items: stretch;
        gap: 15px;
    }
    
    .sort-controls,
    .filter-controls {
        justify-content: space-between;
    }
    
    .sort-select,
    .filter-select {
        min-width: 120px;
    }
    
    .archive-title {
        font-size: 2.2rem;
    }
    
    .perfumers-grid {
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 15px;
    }
    
    .perfumer-link {
        padding: 15px;
    }
    
    .alphabet-navigation {
        padding: 15px;
        margin: 20px 0;
    }
    
    .letter-link {
        width: 30px;
        height: 30px;
        font-size: 12px;
    }
    
    .letter-heading {
        font-size: 2rem;
    }
}

@media (max-width: 480px) {
    .archive-header {
        padding: 40px 0;
    }
    
    .archive-title {
        font-size: 1.8rem;
    }
    
    .perfumers-grid {
        grid-template-columns: 1fr;
        gap: 15px;
    }
    
    .alphabet-nav-inner {
        gap: 5px;
    }
    
    .letter-link {
        width: 28px;
        height: 28px;
        font-size: 11px;
    }
    
    .container {
        padding: 0 15px;
    }
}

/* Smooth Scrolling Enhancement */
html {
    scroll-behavior: smooth;
}

/* Hidden state for filtering */
.perfumer-item.filtered-hidden {
    display: none !important;
}

.letter-section.all-hidden {
    display: none;
}

/* Sorted container styles */
.sorted-perfumers-container {
    margin-top: 30px;
}

.sorted-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 20px;
}

/* Loading state */
.perfumers-content.loading {
    opacity: 0.6;
    pointer-events: none;
}

.perfumer-item {
    animation: fadeInUp 0.6s ease-out;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Hover Effects Enhancement */
.perfumer-item:hover .perfumer-name {
    color: #0073aa;
}

.perfumer-item:hover .perfumer-icon {
    transform: scale(1.1);
    transition: transform 0.3s ease;
}

.perfumer-item:hover .perfumer-logo img {
    transform: scale(1.05);
    transition: transform 0.3s ease;
}
</style>

<script>
// JavaScript за алфабетна навигация и филтриране
document.addEventListener('DOMContentLoaded', function() {
    const letterLinks = document.querySelectorAll('.letter-link.active');
    const sortSelect = document.getElementById('perfumer-sort');
    const filterSelect = document.getElementById('perfumer-filter');
    const alphabetNav = document.getElementById('alphabet-nav');
    
    // Alphabet навигация
    letterLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({ 
                    behavior: 'smooth', 
                    block: 'start',
                    inline: 'nearest'
                });
                
                // Highlight effect
                target.style.backgroundColor = '#f0f8ff';
                setTimeout(() => {
                    target.style.backgroundColor = '';
                }, 2000);
            }
        });
    });
    
    // Sorting функционалност
    if (sortSelect) {
        sortSelect.addEventListener('change', function() {
            const sortType = this.value;
            const letterSections = document.querySelectorAll('.letter-section');
            
            // Скриваме alphabet навигацията ако не е азбучно сортиране
            if (sortType === 'alphabet') {
                alphabetNav.style.display = 'block';
                letterSections.forEach(section => section.style.display = 'block');
                sortPerfumersAlphabetically();
            } else {
                alphabetNav.style.display = 'none';
                letterSections.forEach(section => section.style.display = 'none');
                sortPerfumersByCount(sortType);
            }
        });
    }
    
    // Filtering функционалност
    if (filterSelect) {
        filterSelect.addEventListener('change', function() {
            const filterType = this.value;
            filterPerfumers(filterType);
        });
    }
    
    function sortPerfumersAlphabetically() {
        // Показваме всички letter sections
        const letterSections = document.querySelectorAll('.letter-section');
        letterSections.forEach(section => {
            section.style.display = 'block';
        });
        
        // Премахваме динамичния sorted контейнер ако съществува
        const sortedContainer = document.querySelector('.sorted-perfumers-container');
        if (sortedContainer) {
            sortedContainer.remove();
        }
    }
    
    function sortPerfumersByCount(sortType) {
        // Събираме всички perfumer items
        const allPerfumers = Array.from(document.querySelectorAll('.perfumer-item'));
        
        // Сортираме по count
        allPerfumers.sort((a, b) => {
            const countA = parseInt(a.dataset.count) || 0;
            const countB = parseInt(b.dataset.count) || 0;
            
            if (sortType === 'count-desc') {
                return countB - countA; // Desc order
            } else {
                return countA - countB; // Asc order
            }
        });
        
        // Създаваме нов контейнер за sorted резултати
        let sortedContainer = document.querySelector('.sorted-perfumers-container');
        if (!sortedContainer) {
            sortedContainer = document.createElement('div');
            sortedContainer.className = 'sorted-perfumers-container';
            document.querySelector('.perfumers-content').appendChild(sortedContainer);
        }
        
        // Добавяме заглавие
        const title = sortType === 'count-desc' ? 
            'Парфюмеристи по брой парфюми (най-много първо)' : 
            'Парфюмеристи по брой парфюми (най-малко първо)';
            
        sortedContainer.innerHTML = `
            <div class="sorted-section">
                <h2 class="letter-heading">${title}</h2>
                <div class="perfumers-grid sorted-grid"></div>
            </div>
        `;
        
        // Добавяме sorted perfumers
        const sortedGrid = sortedContainer.querySelector('.sorted-grid');
        allPerfumers.forEach(perfumer => {
            sortedGrid.appendChild(perfumer.cloneNode(true));
        });
    }
    
    function filterPerfumers(filterType) {
        const allPerfumers = document.querySelectorAll('.perfumer-item');
        const letterSections = document.querySelectorAll('.letter-section');
        
        allPerfumers.forEach(perfumer => {
            const count = parseInt(perfumer.dataset.count) || 0;
            let shouldShow = true;
            
            switch(filterType) {
                case 'popular':
                    shouldShow = count >= 5;
                    break;
                case 'top':
                    shouldShow = count >= 10;
                    break;
                case 'legends':
                    shouldShow = count >= 20;
                    break;
                case 'all':
                default:
                    shouldShow = true;
                    break;
            }
            
            if (shouldShow) {
                perfumer.classList.remove('filtered-hidden');
            } else {
                perfumer.classList.add('filtered-hidden');
            }
        });
        
        // Скриваме letter sections които нямат видими perfumers
        letterSections.forEach(section => {
            const visiblePerfumers = section.querySelectorAll('.perfumer-item:not(.filtered-hidden)');
            if (visiblePerfumers.length === 0) {
                section.classList.add('all-hidden');
            } else {
                section.classList.remove('all-hidden');
            }
        });
        
        // Също така филтрираме в sorted контейнера ако съществува
        const sortedContainer = document.querySelector('.sorted-perfumers-container');
        if (sortedContainer) {
            const sortedPerfumers = sortedContainer.querySelectorAll('.perfumer-item');
            sortedPerfumers.forEach(perfumer => {
                const count = parseInt(perfumer.dataset.count) || 0;
                let shouldShow = true;
                
                switch(filterType) {
                    case 'popular':
                        shouldShow = count >= 5;
                        break;
                    case 'top':
                        shouldShow = count >= 10;
                        break;
                    case 'legends':
                        shouldShow = count >= 20;
                        break;
                    case 'all':
                    default:
                        shouldShow = true;
                        break;
                }
                
                if (shouldShow) {
                    perfumer.classList.remove('filtered-hidden');
                } else {
                    perfumer.classList.add('filtered-hidden');
                }
            });
        }
    }
    
    // Keyboard навигация
    document.addEventListener('keydown', function(e) {
        if (e.ctrlKey || e.metaKey) {
            const letters = Array.from(letterLinks).map(link => 
                link.textContent.trim().toLowerCase()
            );
            
            const pressedLetter = e.key.toLowerCase();
            if (letters.includes(pressedLetter)) {
                e.preventDefault();
                const targetLink = Array.from(letterLinks).find(link => 
                    link.textContent.trim().toLowerCase() === pressedLetter
                );
                if (targetLink) {
                    targetLink.click();
                }
            }
        }
    });
});
</script>

<?php get_footer(); ?>