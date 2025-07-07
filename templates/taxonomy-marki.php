<?php
/**
 * Taxonomy шаблон за марки с азбучна навигация
 * 
 * @package Parfume_Catalog
 */

get_header(); 

// Проверка дали е архивна страница за всички марки
$is_brands_archive = (is_tax('marki') && !get_queried_object()->term_id) || 
                     (isset($_GET['brands_archive']) && $_GET['brands_archive'] === '1');

?>

<div class="marki-archive-container">
    
    <?php if ($is_brands_archive) : ?>
        
        <!-- Азбучна навигация за всички марки -->
        <div class="brands-archive-header">
            <h1>Парфюмни марки</h1>
            <p>Разгледайте всички налични марки парфюми, подредени по азбучен ред</p>
        </div>
        
        <!-- Азбучна навигация -->
        <div class="alphabet-navigation">
            <?php
            // Получаване на всички марки
            $all_brands = get_terms(array(
                'taxonomy' => 'marki',
                'hide_empty' => false,
                'orderby' => 'name',
                'order' => 'ASC'
            ));
            
            // Създаване на азбучни групи
            $alphabet_groups = array();
            $latin_letters = range('A', 'Z');
            $cyrillic_letters = array('А', 'Б', 'В', 'Г', 'Д', 'Е', 'Ж', 'З', 'И', 'Й', 'К', 'Л', 'М', 'Н', 'О', 'П', 'Р', 'С', 'Т', 'У', 'Ф', 'Х', 'Ц', 'Ч', 'Ш', 'Щ', 'Ъ', 'Ы', 'Ь', 'Э', 'Ю', 'Я');
            
            // Групиране на марките по първа буква
            foreach ($all_brands as $brand) {
                $first_letter = mb_strtoupper(mb_substr($brand->name, 0, 1, 'UTF-8'), 'UTF-8');
                
                if (!isset($alphabet_groups[$first_letter])) {
                    $alphabet_groups[$first_letter] = array();
                }
                $alphabet_groups[$first_letter][] = $brand;
            }
            
            // Навигационни букви
            $all_letters = array_merge($latin_letters, $cyrillic_letters);
            ?>
            
            <div class="alphabet-nav">
                <?php foreach ($all_letters as $letter) : ?>
                    <?php $has_brands = isset($alphabet_groups[$letter]) && !empty($alphabet_groups[$letter]); ?>
                    <a href="#letter-<?php echo esc_attr($letter); ?>" 
                       class="alphabet-link <?php echo $has_brands ? 'has-brands' : 'no-brands'; ?>"
                       <?php if (!$has_brands) : ?>title="Няма марки с буква <?php echo esc_attr($letter); ?>"<?php endif; ?>>
                        <?php echo esc_html($letter); ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Списък с марки по азбучен ред -->
        <div class="brands-alphabetical-list">
            <?php if (!empty($alphabet_groups)) : ?>
                
                <?php foreach ($all_letters as $letter) : ?>
                    <?php if (isset($alphabet_groups[$letter]) && !empty($alphabet_groups[$letter])) : ?>
                        
                        <div class="letter-section" id="letter-<?php echo esc_attr($letter); ?>">
                            <h2 class="letter-heading"><?php echo esc_html($letter); ?></h2>
                            
                            <div class="brands-grid">
                                <?php foreach ($alphabet_groups[$letter] as $brand) : ?>
                                    <?php
                                    $brand_logo = get_term_meta($brand->term_id, 'brand_logo', true);
                                    $brand_description = $brand->description;
                                    ?>
                                    <div class="brand-card">
                                        <a href="<?php echo get_term_link($brand); ?>" class="brand-link">
                                            
                                            <?php if ($brand_logo) : ?>
                                                <div class="brand-logo">
                                                    <img src="<?php echo esc_url($brand_logo); ?>" 
                                                         alt="<?php echo esc_attr($brand->name); ?>">
                                                </div>
                                            <?php endif; ?>
                                            
                                            <div class="brand-info">
                                                <h3 class="brand-name"><?php echo esc_html($brand->name); ?></h3>
                                                
                                                <?php if ($brand_description) : ?>
                                                    <p class="brand-description"><?php echo esc_html(wp_trim_words($brand_description, 15)); ?></p>
                                                <?php endif; ?>
                                                
                                                <div class="brand-stats">
                                                    <span class="parfumes-count"><?php echo $brand->count; ?> парфюма</span>
                                                </div>
                                            </div>
                                            
                                        </a>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                    <?php endif; ?>
                <?php endforeach; ?>
                
            <?php else : ?>
                
                <div class="no-brands-message">
                    <p>Все още няма добавени марки.</p>
                </div>
                
            <?php endif; ?>
        </div>
        
    <?php else : ?>
        
        <!-- Единична марка - показване на парфюми -->
        <?php $current_brand = get_queried_object(); ?>
        
        <div class="single-brand-header">
            <div class="brand-info-section">
                
                <?php
                $brand_logo = get_term_meta($current_brand->term_id, 'brand_logo', true);
                if ($brand_logo) :
                ?>
                    <div class="brand-logo-large">
                        <img src="<?php echo esc_url($brand_logo); ?>" alt="<?php echo esc_attr($current_brand->name); ?>">
                    </div>
                <?php endif; ?>
                
                <div class="brand-details">
                    <h1 class="brand-title"><?php echo esc_html($current_brand->name); ?></h1>
                    
                    <?php if ($current_brand->description) : ?>
                        <div class="brand-description">
                            <?php echo wpautop($current_brand->description); ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="brand-stats">
                        <span class="total-parfumes"><?php echo $current_brand->count; ?> парфюма от тази марка</span>
                    </div>
                </div>
            </div>
            
            <!-- Връзка към всички марки -->
            <div class="back-to-brands">
                <a href="<?php echo add_query_arg('brands_archive', '1', get_term_link(get_terms(array('taxonomy' => 'marki', 'number' => 1))[0])); ?>" class="btn-back">
                    <i class="arrow-left"></i> Всички марки
                </a>
            </div>
        </div>
        
        <!-- Филтри за парфюмите на марката -->
        <div class="brand-filters">
            <?php echo do_shortcode('[parfume_filters brand_specific="true"]'); ?>
        </div>
        
        <!-- Парфюми от марката -->
        <div class="brand-parfumes">
            <?php if (have_posts()) : ?>
                
                <div class="parfume-grid">
                    <?php while (have_posts()) : the_post(); ?>
                        
                        <div class="parfume-card" data-parfume-id="<?php echo get_the_ID(); ?>">
                            
                            <!-- Изображение -->
                            <div class="parfume-card-image">
                                <a href="<?php the_permalink(); ?>">
                                    <?php if (has_post_thumbnail()) : ?>
                                        <?php the_post_thumbnail('medium', array('class' => 'parfume-thumbnail')); ?>
                                    <?php else : ?>
                                        <div class="parfume-no-image">
                                            <i class="parfume-icon-bottle"></i>
                                        </div>
                                    <?php endif; ?>
                                </a>
                                
                                <!-- Бутон за сравнение -->
                                <button class="parfume-compare-btn" data-parfume-id="<?php echo get_the_ID(); ?>" title="Добави за сравнение">
                                    <i class="parfume-icon-compare"></i>
                                </button>
                            </div>
                            
                            <!-- Съдържание -->
                            <div class="parfume-card-content">
                                
                                <!-- Заглавие -->
                                <h2 class="parfume-card-title">
                                    <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                                </h2>
                                
                                <!-- Тип/Вид -->
                                <div class="parfume-meta">
                                    <?php
                                    $tip_terms = wp_get_post_terms(get_the_ID(), 'tip-parfum');
                                    $vid_terms = wp_get_post_terms(get_the_ID(), 'vid-aromat');
                                    
                                    if (!empty($tip_terms)) :
                                    ?>
                                        <span class="parfume-type"><?php echo esc_html($tip_terms[0]->name); ?></span>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($vid_terms)) : ?>
                                        <span class="parfume-concentration"><?php echo esc_html($vid_terms[0]->name); ?></span>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Година на издаване -->
                                <?php
                                $release_year = get_post_meta(get_the_ID(), '_release_year', true);
                                if ($release_year) :
                                ?>
                                    <div class="parfume-year">
                                        <i class="icon-calendar"></i> <?php echo esc_html($release_year); ?>
                                    </div>
                                <?php endif; ?>
                                
                                <!-- Кратко описание -->
                                <?php if (has_excerpt()) : ?>
                                    <div class="parfume-excerpt">
                                        <?php echo wp_trim_words(get_the_excerpt(), 20); ?>
                                    </div>
                                <?php endif; ?>
                                
                                <!-- Рейтинг -->
                                <?php
                                $rating_data = Parfume_Catalog_Comments::get_parfume_rating(get_the_ID());
                                if ($rating_data['count'] > 0) :
                                ?>
                                    <div class="parfume-rating">
                                        <div class="rating-stars">
                                            <?php for ($i = 1; $i <= 5; $i++) : ?>
                                                <span class="star <?php echo ($i <= $rating_data['average']) ? 'filled' : ''; ?>">★</span>
                                            <?php endfor; ?>
                                        </div>
                                        <span class="rating-text"><?php echo number_format($rating_data['average'], 1); ?> (<?php echo $rating_data['count']; ?>)</span>
                                    </div>
                                <?php endif; ?>
                                
                            </div>
                            
                            <!-- Действия -->
                            <div class="parfume-card-actions">
                                <a href="<?php the_permalink(); ?>" class="btn btn-primary">Виж детайли</a>
                            </div>
                            
                        </div>
                        
                    <?php endwhile; ?>
                </div>
                
                <!-- Пагинация -->
                <div class="parfume-pagination">
                    <?php
                    echo paginate_links(array(
                        'prev_text' => '<i class="arrow-left"></i> Предишна',
                        'next_text' => 'Следваща <i class="arrow-right"></i>',
                        'mid_size' => 2
                    ));
                    ?>
                </div>
                
            <?php else : ?>
                
                <div class="no-parfumes-found">
                    <div class="no-results-icon">
                        <i class="parfume-icon-search"></i>
                    </div>
                    <h2>Няма парфюми от тази марка</h2>
                    <p>Все още няма добавени парфюми от марката <?php echo esc_html($current_brand->name); ?>.</p>
                </div>
                
            <?php endif; ?>
        </div>
        
    <?php endif; ?>
    
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Плавно скролиране към букви
    const alphabetLinks = document.querySelectorAll('.alphabet-link.has-brands');
    
    alphabetLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            
            const targetId = this.getAttribute('href').substring(1);
            const targetElement = document.getElementById(targetId);
            
            if (targetElement) {
                targetElement.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
                
                // Подчертаване на активната буква
                alphabetLinks.forEach(l => l.classList.remove('active'));
                this.classList.add('active');
            }
        });
    });
    
    // Автоматично маркиране на активната буква при скролиране
    const letterSections = document.querySelectorAll('.letter-section');
    
    if (letterSections.length > 0) {
        const observerOptions = {
            root: null,
            rootMargin: '-20% 0px -70% 0px',
            threshold: 0.1
        };
        
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const letter = entry.target.id.replace('letter-', '');
                    
                    alphabetLinks.forEach(link => {
                        link.classList.remove('active');
                        if (link.getAttribute('href') === '#' + entry.target.id) {
                            link.classList.add('active');
                        }
                    });
                }
            });
        }, observerOptions);
        
        letterSections.forEach(section => {
            observer.observe(section);
        });
    }
    
    // Инициализация на сравнение
    if (typeof parfumeCatalog !== 'undefined') {
        parfumeCatalog.comparison.initButtons();
    }
});
</script>

<?php get_footer(); ?>