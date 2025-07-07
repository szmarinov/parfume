<?php
/**
 * Архив шаблон за парфюми
 * 
 * @package Parfume_Catalog
 */

get_header(); ?>

<div class="parfume-archive-container">
    
    <!-- Заглавие и филтри -->
    <div class="archive-header">
        <div class="archive-title-section">
            <h1 class="archive-title">
                <?php
                if (is_tax()) {
                    $term = get_queried_object();
                    echo esc_html($term->name);
                } else {
                    echo 'Парфюми';
                }
                ?>
            </h1>
            
            <?php if (is_tax() && !empty(get_queried_object()->description)) : ?>
                <div class="archive-description">
                    <?php echo wpautop(get_queried_object()->description); ?>
                </div>
            <?php endif; ?>
            
            <div class="archive-meta">
                <?php
                global $wp_query;
                $total = $wp_query->found_posts;
                ?>
                <span class="results-count">Намерени <?php echo $total; ?> парфюма</span>
            </div>
        </div>
        
        <!-- Филтри -->
        <div class="archive-filters">
            <?php echo do_shortcode('[parfume_filters]'); ?>
        </div>
        
        <!-- Сортиране -->
        <div class="archive-sorting">
            <select id="parfume-sort" class="sort-select">
                <option value="date_desc" <?php selected(get_query_var('orderby'), 'date'); ?>>Най-нови</option>
                <option value="title_asc" <?php selected(get_query_var('orderby'), 'title'); ?>>По име (А-Я)</option>
                <option value="title_desc">По име (Я-А)</option>
                <option value="rating_desc">По рейтинг</option>
                <option value="random">Случайно</option>
            </select>
        </div>
    </div>
    
    <!-- Резултати -->
    <div class="parfume-results" id="parfume-results">
        
        <?php if (have_posts()) : ?>
            
            <div class="parfume-grid" id="parfume-grid">
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
                            
                            <!-- Марка -->
                            <?php
                            $marki = wp_get_post_terms(get_the_ID(), 'marki');
                            if (!empty($marki)) :
                            ?>
                                <div class="parfume-brand">
                                    <a href="<?php echo get_term_link($marki[0]); ?>"><?php echo esc_html($marki[0]->name); ?></a>
                                </div>
                            <?php endif; ?>
                            
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
                            
                            <!-- Основни нотки -->
                            <?php
                            $notes = wp_get_post_terms(get_the_ID(), 'notki', array('number' => 3));
                            if (!empty($notes)) :
                            ?>
                                <div class="parfume-notes">
                                    <span class="notes-label">Нотки:</span>
                                    <?php
                                    $notes_names = array();
                                    foreach ($notes as $note) {
                                        $notes_names[] = $note->name;
                                    }
                                    echo esc_html(implode(', ', $notes_names));
                                    ?>
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
                            
                            <!-- Цена (ако има) -->
                            <?php
                            $stores = get_post_meta(get_the_ID(), '_parfume_stores', true);
                            $min_price = null;
                            
                            if (!empty($stores) && is_array($stores)) {
                                foreach ($stores as $store) {
                                    if (!empty($store['scraped_price'])) {
                                        $price = floatval(str_replace(',', '.', preg_replace('/[^\d,.]/', '', $store['scraped_price'])));
                                        if ($price > 0 && ($min_price === null || $price < $min_price)) {
                                            $min_price = $price;
                                        }
                                    }
                                }
                            }
                            
                            if ($min_price) :
                            ?>
                                <div class="parfume-price">
                                    <span class="price-label">От:</span>
                                    <span class="price-value"><?php echo number_format($min_price, 2); ?> лв.</span>
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
            
            <!-- Няма резултати -->
            <div class="no-parfumes-found">
                <div class="no-results-icon">
                    <i class="parfume-icon-search"></i>
                </div>
                <h2>Няма намерени парфюми</h2>
                <p>Опитайте да промените филтрите или да търсите с други критерии.</p>
                
                <div class="suggestions">
                    <h3>Предложения:</h3>
                    <ul>
                        <li>Проверете правописа на търсените думи</li>
                        <li>Използвайте по-общи термини</li>
                        <li>Намалете броя на филтрите</li>
                        <li>Разгледайте <a href="<?php echo get_post_type_archive_link('parfumes'); ?>">всички парфюми</a></li>
                    </ul>
                </div>
            </div>
            
        <?php endif; ?>
        
    </div>
    
    <!-- Зареждане индикатор за AJAX -->
    <div class="loading-indicator" id="loading-indicator" style="display: none;">
        <div class="spinner"></div>
        <span>Зареждане...</span>
    </div>
    
</div>

<!-- Популярни марки (ако е главна архивна страница) -->
<?php if (is_post_type_archive('parfumes') && !get_query_var('s')) : ?>
    <div class="popular-brands-section">
        <h2>Популярни марки</h2>
        <div class="brands-grid">
            <?php
            $popular_brands = get_terms(array(
                'taxonomy' => 'marki',
                'orderby' => 'count',
                'order' => 'DESC',
                'number' => 12,
                'hide_empty' => true
            ));
            
            if (!empty($popular_brands)) :
                foreach ($popular_brands as $brand) :
                    $brand_logo = get_term_meta($brand->term_id, 'brand_logo', true);
                ?>
                    <div class="brand-item">
                        <a href="<?php echo get_term_link($brand); ?>">
                            <?php if ($brand_logo) : ?>
                                <img src="<?php echo esc_url($brand_logo); ?>" alt="<?php echo esc_attr($brand->name); ?>" class="brand-logo">
                            <?php else : ?>
                                <div class="brand-name"><?php echo esc_html($brand->name); ?></div>
                            <?php endif; ?>
                            <span class="brand-count"><?php echo $brand->count; ?> парфюма</span>
                        </a>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // AJAX сортиране
    const sortSelect = document.getElementById('parfume-sort');
    if (sortSelect) {
        sortSelect.addEventListener('change', function() {
            parfumeCatalog.filters.applySorting(this.value);
        });
    }
    
    // AJAX филтриране
    parfumeCatalog.filters.init();
    
    // Бутони за сравнение
    parfumeCatalog.comparison.initButtons();
});
</script>

<?php get_footer(); ?>