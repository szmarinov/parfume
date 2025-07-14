<?php
/**
 * Taxonomy Perfumer Template - Archive page за парфюмеристи
 * 
 * Този файл се зарежда за:
 * - Archive страница с всички парфюмеристи (/parfiumi/parfumeri/)
 * - Single страница на конкретен парфюмерист (/parfiumi/parfumeri/alberto-morillas/)
 * 
 * Файл: templates/taxonomy-perfumer.php
 * ПОПРАВЕНА ВЕРСИЯ
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();

$current_term = get_queried_object();
$is_single_perfumer = $current_term && isset($current_term->name) && !empty($current_term->name);

// Ако е конкретен парфюмерист, показваме single page
if ($is_single_perfumer) {
    $perfumer_image_id = get_term_meta($current_term->term_id, 'perfumer-image-id', true);
    ?>
    <div class="single-perfumer-page perfumer-taxonomy-page">
        <div class="archive-header perfumer-hero">
            <div class="container">
                <nav class="breadcrumb">
                    <a href="<?php echo home_url(); ?>"><?php _e('Начало', 'parfume-reviews'); ?></a>
                    <span class="separator"> › </span>
                    <a href="<?php echo home_url('/parfiumi/'); ?>"><?php _e('Парфюми', 'parfume-reviews'); ?></a>
                    <span class="separator"> › </span>
                    <a href="<?php echo home_url('/parfiumi/parfumeri/'); ?>"><?php _e('Парфюмеристи', 'parfume-reviews'); ?></a>
                    <span class="separator"> › </span>
                    <span class="current"><?php echo esc_html($current_term->name); ?></span>
                </nav>
                
                <div class="perfumer-header">
                    <?php if ($perfumer_image_id): ?>
                        <div class="perfumer-image">
                            <?php echo wp_get_attachment_image($perfumer_image_id, 'medium', false, array('class' => 'perfumer-avatar')); ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="perfumer-info">
                        <h1 class="archive-title perfumer-name"><?php echo esc_html($current_term->name); ?></h1>
                        
                        <?php if (!empty($current_term->description)): ?>
                            <div class="archive-description perfumer-bio">
                                <?php echo wpautop(wp_kses_post($current_term->description)); ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="perfumer-stats">
                            <div class="stat-item">
                                <span class="stat-number"><?php echo $current_term->count; ?></span>
                                <span class="stat-label"><?php echo _n('Парфюм', 'Парфюма', $current_term->count, 'parfume-reviews'); ?></span>
                            </div>
                        </div>
                        <?php
                        // Получаваме мета полетата правилно
                        $birth_year = get_term_meta($current_term->term_id, 'birth_year', true);
                        $nationality = get_term_meta($current_term->term_id, 'nationality', true);
                        $career_start = get_term_meta($current_term->term_id, 'career_start', true);
                        $signature_style = get_term_meta($current_term->term_id, 'signature_style', true);
                        $awards = get_term_meta($current_term->term_id, 'awards', true);
                        ?>
                        
                        <?php if ($nationality || $birth_year || $career_start): ?>
                            <div class="perfumer-metadata">
                                <h3><?php _e('Информация за парфюмериста', 'parfume-reviews'); ?></h3>
                                <div class="metadata-grid">
                                    <?php if ($nationality): ?>
                                        <div class="meta-item">
                                            <span class="meta-label"><?php _e('Националност:', 'parfume-reviews'); ?></span>
                                            <span class="meta-value"><?php echo esc_html($nationality); ?></span>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($birth_year): ?>
                                        <div class="meta-item">
                                            <span class="meta-label"><?php _e('Година на раждане:', 'parfume-reviews'); ?></span>
                                            <span class="meta-value"><?php echo esc_html($birth_year); ?></span>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($career_start): ?>
                                        <div class="meta-item">
                                            <span class="meta-label"><?php _e('Кариера започва:', 'parfume-reviews'); ?></span>
                                            <span class="meta-value"><?php echo esc_html($career_start); ?></span>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($signature_style): ?>
                                        <div class="meta-item">
                                            <span class="meta-label"><?php _e('Стил на творчество:', 'parfume-reviews'); ?></span>
                                            <span class="meta-value"><?php echo esc_html($signature_style); ?></span>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($awards): ?>
                                        <div class="meta-item">
                                            <span class="meta-label"><?php _e('Награди:', 'parfume-reviews'); ?></span>
                                            <span class="meta-value"><?php echo wpautop(esc_html($awards)); ?></span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>						
                    </div>
                </div>
            </div>
        </div>
        
        <div class="archive-content perfumer-content">
            <div class="container">
                <div class="archive-main">
                    <div class="perfumer-perfumes-section">
                        <h2 class="section-title"><?php printf(__('Парфюми от %s', 'parfume-reviews'), esc_html($current_term->name)); ?></h2>
                        
                        <?php
                        // Query за парфюмите на този парфюмерист  
                        $perfumes_query = new WP_Query(array(
                            'post_type' => 'parfume',
                            'posts_per_page' => 12,
                            'paged' => get_query_var('paged'),
                            'tax_query' => array(
                                array(
                                    'taxonomy' => 'perfumer',
                                    'field' => 'slug', 
                                    'terms' => $current_term->slug,
                                ),
                            ),
                            'meta_key' => '_parfume_rating',
                            'orderby' => 'meta_value_num',
                            'order' => 'DESC',
                        ));
                        ?>
                        
                        <?php if ($perfumes_query->have_posts()): ?>
                            <div class="parfume-grid">
                                <?php while ($perfumes_query->have_posts()): $perfumes_query->the_post(); ?>
                                    <div class="parfume-card">
                                        <div class="parfume-image">
                                            <?php if (has_post_thumbnail()): ?>
                                                <a href="<?php the_permalink(); ?>">
                                                    <?php the_post_thumbnail('medium'); ?>
                                                </a>
                                            <?php else: ?>
                                                <a href="<?php the_permalink(); ?>" class="placeholder-image">
                                                    <span class="placeholder-text"><?php _e('Няма изображение', 'parfume-reviews'); ?></span>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div class="parfume-content">
                                            <h3 class="parfume-title">
                                                <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                                            </h3>
                                            
                                            <?php
                                            // Получаваме марката
                                            $brands = get_the_terms(get_the_ID(), 'marki');
                                            if ($brands && !is_wp_error($brands)):
                                                $brand = $brands[0];
                                            ?>
                                                <div class="parfume-brand">
                                                    <a href="<?php echo get_term_link($brand); ?>"><?php echo esc_html($brand->name); ?></a>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <?php
                                            // Рейтинг
                                            $rating = get_post_meta(get_the_ID(), '_parfume_rating', true);
                                            if ($rating):
                                            ?>
                                                <div class="parfume-rating">
                                                    <div class="stars">
                                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                                            <span class="star <?php echo $i <= $rating ? 'filled' : ''; ?>">★</span>
                                                        <?php endfor; ?>
                                                    </div>
                                                    <span class="rating-value">(<?php echo esc_html($rating); ?>/5)</span>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                            
                            <?php
                            // Пагинация
                            if ($perfumes_query->max_num_pages > 1):
                                echo paginate_links(array(
                                    'total' => $perfumes_query->max_num_pages,
                                    'current' => max(1, get_query_var('paged')),
                                    'format' => '?paged=%#%',
                                    'show_all' => false,
                                    'end_size' => 1,
                                    'mid_size' => 2,
                                    'prev_next' => true,
                                    'prev_text' => __('‹ Предишна', 'parfume-reviews'),
                                    'next_text' => __('Следваща ›', 'parfume-reviews'),
                                ));
                            endif;
                            ?>
                            
                        <?php else: ?>
                            <div class="no-parfumes-message">
                                <p><?php _e('Все още няма парфюми от този парфюмерист.', 'parfume-reviews'); ?></p>
                            </div>
                        <?php endif; ?>
                        
                        <?php wp_reset_postdata(); ?>
                    </div>
                    
                    <!-- Други парфюмеристи -->
                    <div class="related-perfumers-section">
                        <h2 class="section-title"><?php _e('Други парфюмеристи', 'parfume-reviews'); ?></h2>
                        
                        <?php
                        // Query други парфюмеристи
                        $other_perfumers = get_terms(array(
                            'taxonomy' => 'perfumer',
                            'hide_empty' => true,
                            'number' => 8,
                            'exclude' => array($current_term->term_id),
                            'orderby' => 'count',
                            'order' => 'DESC'
                        ));
                        
                        if (!empty($other_perfumers) && !is_wp_error($other_perfumers)): ?>
                            <div class="perfumers-archive-grid columns-4">
                                <?php foreach ($other_perfumers as $perfumer): ?>
                                    <div class="perfumer-item">
                                        <h3>
                                            <a href="<?php echo get_term_link($perfumer); ?>">
                                                <?php echo esc_html($perfumer->name); ?>
                                            </a>
                                        </h3>
                                        <span class="count">
                                            <?php printf(_n('%d парфюм', '%d парфюма', $perfumer->count, 'parfume-reviews'), $perfumer->count); ?>
                                        </span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php
} else {
    // Archive page - показваме всички парфюмеристи с търсачка и alphabet navigation
    ?>
    <div class="perfumers-archive-page perfumer-taxonomy-page">
        <div class="archive-header">
            <div class="container">
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
                <!-- Търсачка -->
                <div class="perfumers-search">
                    <div class="search-box">
                        <input type="text" id="perfumer-search" placeholder="<?php _e('Търсете парфюмерист...', 'parfume-reviews'); ?>" />
                        <button type="button" class="search-btn">
                            <span class="search-icon">🔍</span>
                        </button>
                    </div>
                </div>
                
                <?php
                // Взимаме всички парфюмеристи за alphabet navigation
                $all_perfumers = get_terms(array(
                    'taxonomy' => 'perfumer',
                    'hide_empty' => true,
                    'orderby' => 'name',
                    'order' => 'ASC',
                    'number' => 0 // Всички парфюмеристи
                ));
                
                // Групираме по първа буква
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
                
                <!-- Alphabet Navigation -->
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
                
                <!-- Парфюмеристи по букви -->
                <div class="perfumers-content">
                    <?php if (!empty($perfumers_by_letter)): ?>
                        <?php foreach ($available_letters as $letter): ?>
                            <div class="letter-section" id="letter-<?php echo esc_attr(strtolower($letter)); ?>">
                                <h2 class="letter-heading"><?php echo esc_html($letter); ?></h2>
                                
                                <div class="perfumers-archive-grid columns-3">
                                    <?php foreach ($perfumers_by_letter[$letter] as $perfumer): ?>
                                        <div class="perfumer-item" data-perfumer-name="<?php echo esc_attr(strtolower($perfumer->name)); ?>">
                                            <?php 
                                            $perfumer_image_id = get_term_meta($perfumer->term_id, 'perfumer-image-id', true);
                                            if ($perfumer_image_id): 
                                            ?>
                                                <div class="perfumer-image">
                                                    <a href="<?php echo get_term_link($perfumer); ?>">
                                                        <?php echo wp_get_attachment_image($perfumer_image_id, 'thumbnail', false, array('class' => 'perfumer-avatar')); ?>
                                                    </a>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <div class="perfumer-info">
                                                <h3>
                                                    <a href="<?php echo get_term_link($perfumer); ?>">
                                                        <?php echo esc_html($perfumer->name); ?>
                                                    </a>
                                                </h3>
                                                
                                                <?php if (!empty($perfumer->description)): ?>
                                                    <div class="perfumer-description">
                                                        <?php echo wp_trim_words($perfumer->description, 15, '...'); ?>
                                                    </div>
                                                <?php endif; ?>
                                                
                                                <span class="count">
                                                    <?php printf(_n('%d парфюм', '%d парфюма', $perfumer->count, 'parfume-reviews'), $perfumer->count); ?>
                                                </span>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="no-perfumers-message">
                            <p><?php _e('Все още няма добавени парфюмеристи.', 'parfume-reviews'); ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        // Търсачка за парфюмеристи
        $('#perfumer-search').on('input', function() {
            var searchTerm = $(this).val().toLowerCase();
            
            if (searchTerm === '') {
                $('.perfumer-item').show();
                $('.letter-section').show();
            } else {
                $('.perfumer-item').each(function() {
                    var perfumerName = $(this).data('perfumer-name');
                    if (perfumerName.indexOf(searchTerm) !== -1) {
                        $(this).show();
                    } else {
                        $(this).hide();
                    }
                });
                
                // Скриваме секции без резултати
                $('.letter-section').each(function() {
                    var visibleItems = $(this).find('.perfumer-item:visible');
                    if (visibleItems.length === 0) {
                        $(this).hide();
                    } else {
                        $(this).show();
                    }
                });
            }
        });
        
        // Smooth scroll за alphabet навигацията
        $('.letter-link.active').on('click', function(e) {
            e.preventDefault();
            var target = $(this).attr('href');
            if ($(target).length) {
                $('html, body').animate({
                    scrollTop: $(target).offset().top - 100
                }, 500);
            }
        });
    });
    </script>
    
    <?php
}

get_footer();
?>