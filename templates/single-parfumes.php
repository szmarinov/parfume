<?php
/**
 * Single парфюм шаблон
 * 
 * @package Parfume_Catalog
 */

get_header(); ?>

<div class="parfume-single-container">
    <div class="parfume-main-content">
        <!-- Лява колона (70%) -->
        <div class="parfume-left-column">
            <?php while (have_posts()) : the_post(); ?>
                
                <!-- Заглавие и основна информация -->
                <div class="parfume-header">
                    <div class="parfume-image">
                        <?php if (has_post_thumbnail()) : ?>
                            <?php the_post_thumbnail('large', array('class' => 'parfume-featured-image')); ?>
                        <?php else : ?>
                            <div class="parfume-no-image">
                                <i class="parfume-icon-bottle"></i>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="parfume-info">
                        <h1 class="parfume-title"><?php the_title(); ?></h1>
                        
                        <div class="parfume-meta">
                            <?php
                            // Вид аромат
                            $vid_aromat = wp_get_post_terms(get_the_ID(), 'vid-aromat');
                            if (!empty($vid_aromat)) :
                            ?>
                                <span class="parfume-type"><?php echo esc_html($vid_aromat[0]->name); ?></span>
                            <?php endif; ?>
                            
                            <?php
                            // Марка
                            $marki = wp_get_post_terms(get_the_ID(), 'marki');
                            if (!empty($marki)) :
                            ?>
                                <span class="parfume-brand">
                                    <a href="<?php echo get_term_link($marki[0]); ?>"><?php echo esc_html($marki[0]->name); ?></a>
                                </span>
                            <?php endif; ?>
                            
                            <!-- Бутон за сравнение -->
                            <button class="parfume-compare-btn" data-parfume-id="<?php echo get_the_ID(); ?>">
                                <i class="parfume-icon-compare"></i>
                                <span class="compare-text">Добави за сравнение</span>
                            </button>
                        </div>
                        
                        <!-- Основни ароматни нотки -->
                        <div class="parfume-main-notes">
                            <?php
                            $vrhni_notki = wp_get_post_terms(get_the_ID(), 'notki', array('number' => 5));
                            if (!empty($vrhni_notki)) :
                            ?>
                                <div class="main-notes">
                                    <strong>Основни нотки:</strong>
                                    <?php foreach ($vrhni_notki as $index => $note) : ?>
                                        <span class="note-tag"><?php echo esc_html($note->name); ?></span><?php echo ($index < count($vrhni_notki) - 1) ? ', ' : ''; ?>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Подходящ за сезони и време -->
                <div class="parfume-occasions">
                    <?php
                    $sezoni = wp_get_post_terms(get_the_ID(), 'sezon');
                    if (!empty($sezoni)) :
                    ?>
                        <div class="occasions-grid">
                            <?php foreach ($sezoni as $sezon) : ?>
                                <span class="occasion-item season-<?php echo esc_attr($sezon->slug); ?>">
                                    <i class="season-icon season-<?php echo esc_attr($sezon->slug); ?>"></i>
                                    <?php echo esc_html($sezon->name); ?>
                                </span>
                            <?php endforeach; ?>
                            
                            <!-- Ден/Нощ -->
                            <?php
                            $day_night = get_post_meta(get_the_ID(), '_day_night', true);
                            if ($day_night) :
                                $day_night_array = explode(',', $day_night);
                                foreach ($day_night_array as $time) :
                            ?>
                                <span class="occasion-item time-<?php echo esc_attr(trim($time)); ?>">
                                    <i class="time-icon time-<?php echo esc_attr(trim($time)); ?>"></i>
                                    <?php echo esc_html(ucfirst(trim($time))); ?>
                                </span>
                            <?php endforeach; endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Съдържание/Описание -->
                <div class="parfume-content">
                    <?php the_content(); ?>
                </div>
                
                <!-- Състав - Пирамида от нотки -->
                <div class="parfume-composition">
                    <h2>Състав</h2>
                    <div class="notes-pyramid">
                        
                        <!-- Връхни нотки -->
                        <?php
                        $vrhni_notki = wp_get_post_terms(get_the_ID(), 'notki');
                        if (!empty($vrhni_notki)) :
                        ?>
                            <div class="notes-level top-notes">
                                <h3>Връхни нотки</h3>
                                <div class="notes-list">
                                    <?php foreach ($vrhni_notki as $note) : ?>
                                        <?php $note_icon = get_term_meta($note->term_id, 'note_icon', true); ?>
                                        <div class="note-item">
                                            <?php if ($note_icon) : ?>
                                                <img src="<?php echo esc_url($note_icon); ?>" alt="<?php echo esc_attr($note->name); ?>" class="note-icon">
                                            <?php endif; ?>
                                            <span class="note-name"><?php echo esc_html($note->name); ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Средни нотки -->
                        <?php
                        $sredni_notki = get_post_meta(get_the_ID(), '_sredni_notki', true);
                        if ($sredni_notki) :
                            $sredni_notki_array = explode(',', $sredni_notki);
                        ?>
                            <div class="notes-level middle-notes">
                                <h3>Средни нотки</h3>
                                <div class="notes-list">
                                    <?php foreach ($sredni_notki_array as $note_name) : ?>
                                        <div class="note-item">
                                            <span class="note-name"><?php echo esc_html(trim($note_name)); ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Базови нотки -->
                        <?php
                        $bazovi_notki = get_post_meta(get_the_ID(), '_bazovi_notki', true);
                        if ($bazovi_notki) :
                            $bazovi_notki_array = explode(',', $bazovi_notki);
                        ?>
                            <div class="notes-level base-notes">
                                <h3>Базови нотки</h3>
                                <div class="notes-list">
                                    <?php foreach ($bazovi_notki_array as $note_name) : ?>
                                        <div class="note-item">
                                            <span class="note-name"><?php echo esc_html(trim($note_name)); ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Графика на аромата -->
                <div class="parfume-characteristics">
                    <h2>Графика на аромата</h2>
                    <div class="characteristics-grid">
                        
                        <!-- Дълготрайност -->
                        <div class="characteristic-group">
                            <h3>Дълготрайност</h3>
                            <div class="progress-bars">
                                <?php
                                $longevity = get_post_meta(get_the_ID(), '_longevity', true);
                                $longevity_levels = array(
                                    'velmi_slab' => 'Много слаб',
                                    'slab' => 'Слаб', 
                                    'umeren' => 'Умерен',
                                    'traen' => 'Траен',
                                    'izklyuchitelno_traen' => 'Изключително траен'
                                );
                                
                                foreach ($longevity_levels as $key => $label) :
                                    $active = ($longevity === $key) ? 'active' : '';
                                ?>
                                    <div class="progress-bar <?php echo $active; ?>">
                                        <span class="bar-label"><?php echo esc_html($label); ?></span>
                                        <div class="bar-fill"></div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                        <!-- Ароматна следа -->
                        <div class="characteristic-group">
                            <h3>Ароматна следа</h3>
                            <div class="progress-bars">
                                <?php
                                $sillage = get_post_meta(get_the_ID(), '_sillage', true);
                                $sillage_levels = array(
                                    'slaba' => 'Слаба',
                                    'umerena' => 'Умерена',
                                    'silna' => 'Силна',
                                    'ogromna' => 'Огромна'
                                );
                                
                                foreach ($sillage_levels as $key => $label) :
                                    $active = ($sillage === $key) ? 'active' : '';
                                ?>
                                    <div class="progress-bar <?php echo $active; ?>">
                                        <span class="bar-label"><?php echo esc_html($label); ?></span>
                                        <div class="bar-fill"></div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                        <!-- Пол -->
                        <div class="characteristic-group">
                            <h3>Пол</h3>
                            <div class="progress-bars">
                                <?php
                                $tip_terms = wp_get_post_terms(get_the_ID(), 'tip-parfum');
                                $gender_levels = array(
                                    'damski' => 'Дамски',
                                    'mazhki' => 'Мъжки',
                                    'uniseks' => 'Унисекс',
                                    'po-mladi' => 'По-млади',
                                    'po-zreli' => 'По-зрели'
                                );
                                
                                foreach ($gender_levels as $key => $label) :
                                    $active = '';
                                    if (!empty($tip_terms)) {
                                        foreach ($tip_terms as $term) {
                                            if (strpos($term->slug, $key) !== false) {
                                                $active = 'active';
                                                break;
                                            }
                                        }
                                    }
                                ?>
                                    <div class="progress-bar <?php echo $active; ?>">
                                        <span class="bar-label"><?php echo esc_html($label); ?></span>
                                        <div class="bar-fill"></div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                        <!-- Цена -->
                        <div class="characteristic-group">
                            <h3>Цена</h3>
                            <div class="progress-bars">
                                <?php
                                $price_range = get_post_meta(get_the_ID(), '_price_range', true);
                                $price_levels = array(
                                    'prekaleno_skaep' => 'Прекалено скъп',
                                    'skaep' => 'Скъп',
                                    'priemlivaǌa_tsena' => 'Приемлива цена',
                                    'dobra_tsena' => 'Добра цена',
                                    'evtin' => 'Евтин'
                                );
                                
                                foreach ($price_levels as $key => $label) :
                                    $active = ($price_range === $key) ? 'active' : '';
                                ?>
                                    <div class="progress-bar <?php echo $active; ?>">
                                        <span class="bar-label"><?php echo esc_html($label); ?></span>
                                        <div class="bar-fill"></div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Предимства и недостатъци -->
                <?php
                $advantages = get_post_meta(get_the_ID(), '_advantages', true);
                $disadvantages = get_post_meta(get_the_ID(), '_disadvantages', true);
                
                if ($advantages || $disadvantages) :
                ?>
                    <div class="parfume-pros-cons">
                        <h2>Предимства и недостатъци</h2>
                        <div class="pros-cons-grid">
                            
                            <?php if ($advantages) : ?>
                                <div class="advantages">
                                    <h3><i class="icon-plus"></i> Предимства</h3>
                                    <ul class="advantages-list">
                                        <?php
                                        $advantages_array = explode("\n", $advantages);
                                        foreach ($advantages_array as $advantage) :
                                            $advantage = trim($advantage);
                                            if (!empty($advantage)) :
                                        ?>
                                            <li><i class="icon-check"></i> <?php echo esc_html($advantage); ?></li>
                                        <?php endif; endforeach; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($disadvantages) : ?>
                                <div class="disadvantages">
                                    <h3><i class="icon-minus"></i> Недостатъци</h3>
                                    <ul class="disadvantages-list">
                                        <?php
                                        $disadvantages_array = explode("\n", $disadvantages);
                                        foreach ($disadvantages_array as $disadvantage) :
                                            $disadvantage = trim($disadvantage);
                                            if (!empty($disadvantage)) :
                                        ?>
                                            <li><i class="icon-cross"></i> <?php echo esc_html($disadvantage); ?></li>
                                        <?php endif; endforeach; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Подобни аромати -->
                <div class="parfume-similar">
                    <h2>Подобни аромати</h2>
                    <?php
                    // Получаване на подобни парфюми въз основа на нотки
                    $similar_parfumes = $this->get_similar_parfumes(get_the_ID(), 4);
                    if (!empty($similar_parfumes)) :
                    ?>
                        <div class="similar-parfumes-grid">
                            <?php foreach ($similar_parfumes as $similar) : ?>
                                <div class="similar-parfume-item">
                                    <a href="<?php echo get_permalink($similar->ID); ?>">
                                        <?php if (has_post_thumbnail($similar->ID)) : ?>
                                            <?php echo get_the_post_thumbnail($similar->ID, 'medium', array('class' => 'similar-parfume-image')); ?>
                                        <?php else : ?>
                                            <div class="similar-parfume-no-image">
                                                <i class="parfume-icon-bottle"></i>
                                            </div>
                                        <?php endif; ?>
                                        <h4 class="similar-parfume-title"><?php echo esc_html($similar->post_title); ?></h4>
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Наскоро разгледани -->
                <div class="parfume-recently-viewed">
                    <h2>Наскоро разгледани</h2>
                    <div class="recently-viewed-container" id="recently-viewed-parfumes">
                        <!-- Ще се попълни с JavaScript -->
                    </div>
                </div>
                
                <!-- Други парфюми от същата марка -->
                <?php if (!empty($marki)) : ?>
                    <div class="parfume-same-brand">
                        <h2>Други парфюми от <?php echo esc_html($marki[0]->name); ?></h2>
                        <?php
                        $same_brand_parfumes = get_posts(array(
                            'post_type' => 'parfumes',
                            'posts_per_page' => 4,
                            'post__not_in' => array(get_the_ID()),
                            'tax_query' => array(
                                array(
                                    'taxonomy' => 'marki',
                                    'field' => 'term_id',
                                    'terms' => $marki[0]->term_id
                                )
                            )
                        ));
                        
                        if (!empty($same_brand_parfumes)) :
                        ?>
                            <div class="same-brand-parfumes-grid">
                                <?php foreach ($same_brand_parfumes as $brand_parfume) : ?>
                                    <div class="brand-parfume-item">
                                        <a href="<?php echo get_permalink($brand_parfume->ID); ?>">
                                            <?php if (has_post_thumbnail($brand_parfume->ID)) : ?>
                                                <?php echo get_the_post_thumbnail($brand_parfume->ID, 'medium', array('class' => 'brand-parfume-image')); ?>
                                            <?php else : ?>
                                                <div class="brand-parfume-no-image">
                                                    <i class="parfume-icon-bottle"></i>
                                                </div>
                                            <?php endif; ?>
                                            <h4 class="brand-parfume-title"><?php echo esc_html($brand_parfume->post_title); ?></h4>
                                        </a>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                
                <!-- Потребителски мнения и оценка -->
                <div class="parfume-comments-section">
                    <h2>Потребителски мнения и оценка</h2>
                    
                    <!-- Форма за коментар -->
                    <div class="comment-form-container">
                        <?php echo do_shortcode('[parfume_comment_form post_id="' . get_the_ID() . '"]'); ?>
                    </div>
                    
                    <!-- Списък с коментари -->
                    <div class="comments-list-container">
                        <?php echo do_shortcode('[parfume_comments_list post_id="' . get_the_ID() . '"]'); ?>
                    </div>
                </div>
                
            <?php endwhile; ?>
        </div>
        
        <!-- Дясна колона (30%) - Магазини -->
        <div class="parfume-right-column">
            <div class="parfume-stores-sidebar" id="parfume-stores-sidebar">
                <h2>Сравни цените</h2>
                <p class="stores-intro">Купи <strong><?php the_title(); ?></strong> на най-изгодната цена:</p>
                
                <div class="stores-list">
                    <?php
                    $stores = get_post_meta(get_the_ID(), '_parfume_stores', true);
                    if (!empty($stores) && is_array($stores)) :
                        foreach ($stores as $store) :
                            $this->render_store_offer($store);
                        endforeach;
                    else :
                    ?>
                        <div class="no-stores-message">
                            <p>Все още няма добавени магазини за този парфюм.</p>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="price-update-info">
                    <small><i class="icon-clock"></i> Цените ни се актуализират на всеки <?php echo get_option('parfume_catalog_scrape_interval', 12); ?> ч.</small>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Добавяне на текущия парфюм в наскоро разгледани
?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Добавяне в наскоро разгледани
    parfumeCatalog.addToRecentlyViewed({
        id: <?php echo get_the_ID(); ?>,
        title: '<?php echo esc_js(get_the_title()); ?>',
        url: '<?php echo esc_js(get_permalink()); ?>',
        image: '<?php echo esc_js(get_the_post_thumbnail_url(get_the_ID(), 'medium')); ?>'
    });
    
    // Зареждане на наскоро разгледани
    parfumeCatalog.loadRecentlyViewed('recently-viewed-parfumes', 4);
});
</script>

<?php get_footer(); ?>