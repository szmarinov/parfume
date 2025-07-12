<?php
/**
 * Single Parfume Template - ЧИСТА СТРУКТУРА БЕЗ ТАБОВЕ
 * Използва h2 заглавия и семантичен HTML
 * Съвместимост с обновения CSS файл
 */

get_header(); ?>

<?php while (have_posts()): the_post(); ?>
    <?php
    // Получаваме всички meta данни
    $post_id = get_the_ID();
    $rating = get_post_meta($post_id, '_parfume_rating', true);
    $price = get_post_meta($post_id, '_parfume_price', true);
    $size = get_post_meta($post_id, '_parfume_size', true);
    $year = get_post_meta($post_id, '_parfume_year', true);
    $concentration = get_post_meta($post_id, '_parfume_concentration', true);
    $availability = get_post_meta($post_id, '_parfume_availability', true);
    $longevity = get_post_meta($post_id, '_parfume_longevity', true);
    $sillage = get_post_meta($post_id, '_parfume_sillage', true);
    $bottle_size = get_post_meta($post_id, '_parfume_bottle_size', true);
    
    // Ароматни нотки от мета полета
    $top_notes = get_post_meta($post_id, '_parfume_top_notes', true);
    $middle_notes = get_post_meta($post_id, '_parfume_middle_notes', true);
    $base_notes = get_post_meta($post_id, '_parfume_base_notes', true);
    
    // Pros/Cons
    $pros = get_post_meta($post_id, '_parfume_pros', true);
    $cons = get_post_meta($post_id, '_parfume_cons', true);
    
    // Магазини
    $stores = get_post_meta($post_id, '_parfume_stores', true);
    if (!is_array($stores)) {
        $stores = array();
    }
    
    // Таксономии
    $brands = wp_get_post_terms($post_id, 'marki');
    $genders = wp_get_post_terms($post_id, 'gender');
    $aroma_types = wp_get_post_terms($post_id, 'aroma_type');
    $seasons = wp_get_post_terms($post_id, 'season');
    $intensities = wp_get_post_terms($post_id, 'intensity');
    $notes = wp_get_post_terms($post_id, 'notes');
    $perfumers = wp_get_post_terms($post_id, 'perfumer');
    
    // Почистваме WP_Error резултати
    $brands = !is_wp_error($brands) ? $brands : array();
    $genders = !is_wp_error($genders) ? $genders : array();
    $aroma_types = !is_wp_error($aroma_types) ? $aroma_types : array();
    $seasons = !is_wp_error($seasons) ? $seasons : array();
    $intensities = !is_wp_error($intensities) ? $intensities : array();
    $notes = !is_wp_error($notes) ? $notes : array();
    $perfumers = !is_wp_error($perfumers) ? $perfumers : array();
    
    // Генерираме списъци за показване
    $brands_list = wp_list_pluck($brands, 'name');
    $genders_list = wp_list_pluck($genders, 'name');
    $aroma_types_list = wp_list_pluck($aroma_types, 'name');
    $seasons_list = wp_list_pluck($seasons, 'name');
    $intensities_list = wp_list_pluck($intensities, 'name');
    ?>

    <div class="parfume-container">
        <div class="parfume-single">
            
            <!-- Главна информация -->
            <header class="parfume-header">
                <div class="parfume-main-info">
                    
                    <!-- Основна снимка -->
                    <?php if (has_post_thumbnail()): ?>
                        <div class="parfume-image">
                            <?php the_post_thumbnail('large', array(
                                'class' => 'main-image', 
                                'onclick' => 'openImageModal(this.src)',
                                'alt' => get_the_title(),
                                'loading' => 'eager'
                            )); ?>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Основни детайли -->
                    <div class="parfume-details">
                        <h1 class="parfume-title"><?php the_title(); ?></h1>
                        
                        <!-- Марка/и -->
                        <?php if (!empty($brands)): ?>
                            <div class="parfume-brand">
                                <?php 
                                $brand_links = array();
                                foreach ($brands as $brand) {
                                    $brand_links[] = '<a href="' . get_term_link($brand) . '">' . esc_html($brand->name) . '</a>';
                                }
                                echo implode(', ', $brand_links);
                                ?>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Рейтинг -->
                        <?php if (!empty($rating)): ?>
                            <div class="parfume-rating">
                                <div class="rating-stars">
                                    <?php 
                                    if (function_exists('parfume_reviews_get_rating_stars')) {
                                        echo parfume_reviews_get_rating_stars($rating);
                                    } else {
                                        // Fallback ако функцията не е заредена
                                        $stars = str_repeat('★', floor($rating / 2)) . str_repeat('☆', 5 - floor($rating / 2));
                                        echo '<span style="color: #ffc107;">' . $stars . '</span>';
                                    }
                                    ?>
                                </div>
                                <span class="rating-number"><?php echo esc_html($rating); ?>/10</span>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Цена -->
                        <?php if (!empty($price)): ?>
                            <div class="parfume-price">
                                <span class="price-value"><?php echo esc_html($price); ?></span>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Бързи действия -->
                        <div class="parfume-actions">
                            <?php
                            // Comparison button
                            if (function_exists('parfume_reviews_get_comparison_button')) {
                                echo parfume_reviews_get_comparison_button($post_id);
                            }
                            
                            // Collections dropdown
                            if (function_exists('parfume_reviews_get_collections_dropdown')) {
                                echo parfume_reviews_get_collections_dropdown($post_id);
                            }
                            ?>
                        </div>
                    </div>
                </div>
                
                <!-- Бърза информационна мрежа -->
                <div class="parfume-quick-info">
                    <?php if (!empty($genders_list)): ?>
                        <div class="info-item">
                            <span class="info-label"><?php _e('Пол', 'parfume-reviews'); ?></span>
                            <span class="info-value"><?php echo esc_html(implode(', ', $genders_list)); ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($year)): ?>
                        <div class="info-item">
                            <span class="info-label"><?php _e('Година', 'parfume-reviews'); ?></span>
                            <span class="info-value"><?php echo esc_html($year); ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($aroma_types_list)): ?>
                        <div class="info-item">
                            <span class="info-label"><?php _e('Тип аромат', 'parfume-reviews'); ?></span>
                            <span class="info-value"><?php echo esc_html(implode(', ', $aroma_types_list)); ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($seasons_list)): ?>
                        <div class="info-item">
                            <span class="info-label"><?php _e('Сезон', 'parfume-reviews'); ?></span>
                            <span class="info-value"><?php echo esc_html(implode(', ', $seasons_list)); ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($intensities_list)): ?>
                        <div class="info-item">
                            <span class="info-label"><?php _e('Интензивност', 'parfume-reviews'); ?></span>
                            <span class="info-value"><?php echo esc_html(implode(', ', $intensities_list)); ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($concentration)): ?>
                        <div class="info-item">
                            <span class="info-label"><?php _e('Концентрация', 'parfume-reviews'); ?></span>
                            <span class="info-value">
                                <?php 
                                // Преобразуваме кратките имена в пълни
                                $concentration_names = array(
                                    'parfum' => __('Parfum (20-40%)', 'parfume-reviews'),
                                    'edp' => __('Eau de Parfum (15-20%)', 'parfume-reviews'),
                                    'edt' => __('Eau de Toilette (5-15%)', 'parfume-reviews'),
                                    'edc' => __('Eau de Cologne (2-4%)', 'parfume-reviews'),
                                    'edv' => __('Eau de Vie (15-25%)', 'parfume-reviews'),
                                );
                                echo esc_html($concentration_names[$concentration] ?? $concentration);
                                ?>
                            </span>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($size)): ?>
                        <div class="info-item">
                            <span class="info-label"><?php _e('Размер', 'parfume-reviews'); ?></span>
                            <span class="info-value"><?php echo esc_html($size); ?></span>
                        </div>
                    <?php endif; ?>
                </div>
            </header>

            <!-- Основно съдържание със секции -->
            <div class="parfume-content">
                
                <!-- СЕКЦИЯ: Описание -->
                <section class="content-section description-section">
                    <h2><?php _e('Описание', 'parfume-reviews'); ?></h2>
                    <div class="section-content">
                        <?php 
                        // Показваме основното съдържание
                        the_content();
                        
                        // Ако няма съдържание, показваме excerpt
                        if (empty(get_the_content()) && has_excerpt()) {
                            echo '<p>' . get_the_excerpt() . '</p>';
                        }
                        ?>
                        
                        <!-- Информация за наличност -->
                        <?php if (!empty($availability)): ?>
                            <div class="availability-info">
                                <strong><?php _e('Статус:', 'parfume-reviews'); ?></strong>
                                <span class="availability-badge status-<?php echo esc_attr($availability); ?>">
                                    <?php 
                                    $availability_labels = array(
                                        'available' => __('Налично', 'parfume-reviews'),
                                        'limited' => __('Ограничено издание', 'parfume-reviews'),
                                        'discontinued' => __('Спряно от производство', 'parfume-reviews'),
                                    );
                                    echo esc_html($availability_labels[$availability] ?? ucfirst($availability));
                                    ?>
                                </span>
                            </div>
                        <?php endif; ?>
                    </div>
                </section>

                <!-- СЕКЦИЯ: Ароматни нотки -->
                <?php if (!empty($top_notes) || !empty($middle_notes) || !empty($base_notes) || !empty($notes)): ?>
                    <section class="content-section notes-section">
                        <h2><?php _e('Ароматни нотки', 'parfume-reviews'); ?></h2>
                        <div class="section-content">
                            
                            <!-- Пирамида от нотки (от мета полета) -->
                            <?php if (!empty($top_notes) || !empty($middle_notes) || !empty($base_notes)): ?>
                                <div class="notes-pyramid">
                                    
                                    <!-- Горни нотки -->
                                    <?php if (!empty($top_notes)): ?>
                                        <div class="notes-layer top-notes">
                                            <h3><?php _e('Горни нотки', 'parfume-reviews'); ?></h3>
                                            <div class="notes-list">
                                                <?php 
                                                $top_notes_array = array_filter(array_map('trim', explode(',', $top_notes)));
                                                foreach($top_notes_array as $note): 
                                                ?>
                                                    <span class="note-item"><?php echo esc_html($note); ?></span>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <!-- Сърцевинни нотки -->
                                    <?php if (!empty($middle_notes)): ?>
                                        <div class="notes-layer middle-notes">
                                            <h3><?php _e('Сърцевинни нотки', 'parfume-reviews'); ?></h3>
                                            <div class="notes-list">
                                                <?php 
                                                $middle_notes_array = array_filter(array_map('trim', explode(',', $middle_notes)));
                                                foreach($middle_notes_array as $note): 
                                                ?>
                                                    <span class="note-item"><?php echo esc_html($note); ?></span>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <!-- Базови нотки -->
                                    <?php if (!empty($base_notes)): ?>
                                        <div class="notes-layer base-notes">
                                            <h3><?php _e('Базови нотки', 'parfume-reviews'); ?></h3>
                                            <div class="notes-list">
                                                <?php 
                                                $base_notes_array = array_filter(array_map('trim', explode(',', $base_notes)));
                                                foreach($base_notes_array as $note): 
                                                ?>
                                                    <span class="note-item"><?php echo esc_html($note); ?></span>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Нотки от таксономия -->
                            <?php if (!empty($notes)): ?>
                                <div class="notes-taxonomy">
                                    <h3><?php _e('Всички ароматни нотки', 'parfume-reviews'); ?></h3>
                                    <div class="notes-grid">
                                        <?php foreach ($notes as $note): ?>
                                            <a href="<?php echo get_term_link($note); ?>" class="note-tag">
                                                <?php echo esc_html($note->name); ?>
                                            </a>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </section>
                <?php endif; ?>

                <!-- СЕКЦИЯ: Парфюмери -->
                <?php if (!empty($perfumers)): ?>
                    <section class="content-section perfumer-section">
                        <h2><?php _e('Парфюмери', 'parfume-reviews'); ?></h2>
                        <div class="section-content">
                            <div class="perfumers-list">
                                <?php foreach ($perfumers as $perfumer): ?>
                                    <div class="perfumer-info">
                                        <div class="perfumer-details">
                                            <h3>
                                                <a href="<?php echo get_term_link($perfumer); ?>">
                                                    <?php echo esc_html($perfumer->name); ?>
                                                </a>
                                            </h3>
                                            <?php if (!empty($perfumer->description)): ?>
                                                <p class="perfumer-description"><?php echo esc_html($perfumer->description); ?></p>
                                            <?php endif; ?>
                                            
                                            <!-- Показваме броя парфюми от този парфюмер -->
                                            <?php if ($perfumer->count > 1): ?>
                                                <p class="perfumer-count">
                                                    <a href="<?php echo get_term_link($perfumer); ?>">
                                                        <?php printf(_n('%d парфюм', '%d парфюма', $perfumer->count, 'parfume-reviews'), $perfumer->count); ?>
                                                    </a>
                                                </p>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <!-- Снимка на парфюмера -->
                                        <?php 
                                        if (function_exists('parfume_reviews_get_perfumer_photo')) {
                                            $perfumer_photo = parfume_reviews_get_perfumer_photo($perfumer->term_id);
                                            if ($perfumer_photo): 
                                            ?>
                                                <div class="perfumer-photo">
                                                    <?php echo $perfumer_photo; ?>
                                                </div>
                                            <?php 
                                            endif;
                                        }
                                        ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </section>
                <?php endif; ?>

                <!-- СЕКЦИЯ: Детайлно ревю -->
                <?php if (!empty($pros) || !empty($cons) || !empty($longevity) || !empty($sillage)): ?>
                    <section class="content-section review-section">
                        <h2><?php _e('Детайлно ревю', 'parfume-reviews'); ?></h2>
                        <div class="section-content">
                            
                            <!-- Performance показатели -->
                            <?php if (!empty($longevity) || !empty($sillage)): ?>
                                <div class="performance-metrics">
                                    <?php if (!empty($longevity)): ?>
                                        <div class="metric-item">
                                            <span class="metric-label"><?php _e('Издръжливост', 'parfume-reviews'); ?></span>
                                            <span class="metric-value"><?php echo esc_html($longevity); ?></span>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($sillage)): ?>
                                        <div class="metric-item">
                                            <span class="metric-label"><?php _e('Силаж', 'parfume-reviews'); ?></span>
                                            <span class="metric-value"><?php echo esc_html($sillage); ?></span>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <!-- Допълнителен показател за размер на бутилката -->
                                    <?php if (!empty($bottle_size)): ?>
                                        <div class="metric-item">
                                            <span class="metric-label"><?php _e('Обем бутилка', 'parfume-reviews'); ?></span>
                                            <span class="metric-value"><?php echo esc_html($bottle_size); ?></span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Предимства и недостатъци -->
                            <?php if (!empty($pros) || !empty($cons)): ?>
                                <div class="pros-cons-container">
                                    
                                    <!-- Предимства -->
                                    <?php if (!empty($pros)): ?>
                                        <div class="pros-section">
                                            <h3><?php _e('Предимства', 'parfume-reviews'); ?></h3>
                                            <ul class="pros-list">
                                                <?php 
                                                $pros_array = array_filter(array_map('trim', explode("\n", $pros)));
                                                foreach($pros_array as $pro): 
                                                ?>
                                                    <li><?php echo esc_html($pro); ?></li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <!-- Недостатъци -->
                                    <?php if (!empty($cons)): ?>
                                        <div class="cons-section">
                                            <h3><?php _e('Недостатъци', 'parfume-reviews'); ?></h3>
                                            <ul class="cons-list">
                                                <?php 
                                                $cons_array = array_filter(array_map('trim', explode("\n", $cons)));
                                                foreach($cons_array as $con): 
                                                ?>
                                                    <li><?php echo esc_html($con); ?></li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Обобщение на рейтинга -->
                            <?php if (!empty($rating)): ?>
                                <div class="rating-summary">
                                    <h3><?php _e('Обща оценка', 'parfume-reviews'); ?></h3>
                                    <div class="rating-display">
                                        <div class="rating-stars-large">
                                            <?php 
                                            if (function_exists('parfume_reviews_get_rating_stars')) {
                                                echo parfume_reviews_get_rating_stars($rating);
                                            }
                                            ?>
                                        </div>
                                        <div class="rating-text">
                                            <span class="rating-number-large"><?php echo esc_html($rating); ?></span>
                                            <span class="rating-scale">/ 10</span>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </section>
                <?php endif; ?>

                <!-- СЕКЦИЯ: Коментари -->
                <?php if (comments_open() || get_comments_number()): ?>
                    <section class="content-section comments-section">
                        <h2><?php _e('Коментари и мнения', 'parfume-reviews'); ?></h2>
                        <div class="section-content">
                            <?php comments_template(); ?>
                        </div>
                    </section>
                <?php endif; ?>
                
                <!-- СЕКЦИЯ: Връзки и навигация -->
                <section class="content-section navigation-section">
                    <h2><?php _e('Още информация', 'parfume-reviews'); ?></h2>
                    <div class="section-content">
                        <div class="post-navigation">
                            <?php
                            // Previous/Next навигация
                            $prev_post = get_previous_post();
                            $next_post = get_next_post();
                            ?>
                            
                            <?php if ($prev_post || $next_post): ?>
                                <div class="parfume-navigation">
                                    <?php if ($prev_post): ?>
                                        <div class="nav-previous">
                                            <a href="<?php echo get_permalink($prev_post); ?>" rel="prev">
                                                <span class="nav-label"><?php _e('Предишен парфюм', 'parfume-reviews'); ?></span>
                                                <span class="nav-title"><?php echo esc_html($prev_post->post_title); ?></span>
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($next_post): ?>
                                        <div class="nav-next">
                                            <a href="<?php echo get_permalink($next_post); ?>" rel="next">
                                                <span class="nav-label"><?php _e('Следващ парфюм', 'parfume-reviews'); ?></span>
                                                <span class="nav-title"><?php echo esc_html($next_post->post_title); ?></span>
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Обратно към архива -->
                            <div class="back-to-archive">
                                <a href="<?php echo get_post_type_archive_link('parfume'); ?>" class="back-link">
                                    <?php _e('← Обратно към всички парфюми', 'parfume-reviews'); ?>
                                </a>
                            </div>
                        </div>
                    </div>
                </section>

            </div>
        </div>

        <!-- Странична лента с магазини -->
        <?php if (!empty($stores)): ?>
            <aside class="parfume-stores-sidebar">
                <div class="stores-container">
                    <h2 class="stores-title"><?php _e('Къде да купя', 'parfume-reviews'); ?></h2>
                    
                    <div class="stores-list">
                        <?php foreach ($stores as $index => $store): ?>
                            <?php if (!empty($store['name'])): ?>
                                <div class="store-item">
                                    <div class="store-header">
                                        <h3 class="store-name"><?php echo esc_html($store['name']); ?></h3>
                                        
                                        <?php if (!empty($store['price']) || !empty($store['size'])): ?>
                                            <div class="store-pricing">
                                                <?php if (!empty($store['price'])): ?>
                                                    <span class="store-price"><?php echo esc_html($store['price']); ?></span>
                                                <?php endif; ?>
                                                <?php if (!empty($store['size'])): ?>
                                                    <span class="store-size"><?php echo esc_html($store['size']); ?></span>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <?php if (!empty($store['url'])): ?>
                                        <div class="store-actions">
                                            <a href="<?php echo esc_url($store['url']); ?>" 
                                               target="_blank" 
                                               rel="noopener noreferrer"
                                               class="store-button">
                                                <?php _e('Виж в магазин', 'parfume-reviews'); ?>
                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <path d="M7 17L17 7M17 7H7M17 7V17"/>
                                                </svg>
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <!-- Последно обновяване на цената -->
                                    <div class="store-meta">
                                        <small class="price-updated">
                                            <?php _e('Последно обновено:', 'parfume-reviews'); ?>
                                            <?php echo human_time_diff(get_the_modified_time('U'), current_time('timestamp')); ?>
                                            <?php _e('назад', 'parfume-reviews'); ?>
                                        </small>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Информация за цените -->
                    <div class="stores-disclaimer">
                        <p><small><?php _e('Цените са информативни и могат да се променят. Моля проверете актуалните цени в съответните магазини.', 'parfume-reviews'); ?></small></p>
                    </div>
                </div>
            </aside>
        <?php endif; ?>
    </div>

    <!-- Image Modal за по-големи снимки -->
    <div id="imageModal" class="image-modal" onclick="closeImageModal()">
        <div class="modal-content">
            <span class="close-button" onclick="closeImageModal()">&times;</span>
            <img id="modalImage" src="" alt="">
        </div>
    </div>

<?php endwhile; ?>

<!-- JavaScript за функционалност -->
<script>
// Image modal функционалност
function openImageModal(imageSrc) {
    const modal = document.getElementById('imageModal');
    const modalImg = document.getElementById('modalImage');
    
    if (modal && modalImg) {
        modal.style.display = 'block';
        modalImg.src = imageSrc;
        modalImg.alt = document.querySelector('.parfume-title').textContent || '';
        document.body.style.overflow = 'hidden';
        
        // Focus управление за accessibility
        modal.focus();
    }
}

function closeImageModal() {
    const modal = document.getElementById('imageModal');
    if (modal) {
        modal.style.display = 'none';
        document.body.style.overflow = '';
    }
}

// Keyboard navigation
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeImageModal();
    }
});

// Smooth scroll за anchor links
document.addEventListener('DOMContentLoaded', function() {
    // Smooth scroll към секции
    const links = document.querySelectorAll('a[href^="#"]');
    
    links.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            
            const targetId = this.getAttribute('href').substring(1);
            const targetElement = document.getElementById(targetId);
            
            if (targetElement) {
                targetElement.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
                
                // Focus management
                targetElement.focus();
            }
        });
    });
    
    // Lazy loading за снимки (ако браузърът не поддържа native loading)
    if ('loading' in HTMLImageElement.prototype === false) {
        const images = document.querySelectorAll('img[loading="lazy"]');
        const imageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.src = img.dataset.src;
                    img.classList.remove('lazy');
                    imageObserver.unobserve(img);
                }
            });
        });
        
        images.forEach(img => imageObserver.observe(img));
    }
    
    // Analytics tracking за external links
    const externalLinks = document.querySelectorAll('a[target="_blank"]');
    externalLinks.forEach(link => {
        link.addEventListener('click', function() {
            // Google Analytics tracking (ако е налично)
            if (typeof gtag !== 'undefined') {
                gtag('event', 'click', {
                    event_category: 'external_link',
                    event_label: this.href
                });
            }
        });
    });
});

// Print функционалност
function printParfume() {
    window.print();
}

// Share функционалност (ако браузърът поддържа Web Share API)
function shareParfume() {
    if (navigator.share) {
        navigator.share({
            title: document.querySelector('.parfume-title').textContent,
            text: document.querySelector('meta[name="description"]')?.content || '',
            url: window.location.href
        });
    } else {
        // Fallback - копиране на URL
        navigator.clipboard.writeText(window.location.href).then(() => {
            alert('<?php _e('Линкът е копиран в клипборда!', 'parfume-reviews'); ?>');
        });
    }
}
</script>

<?php get_footer(); ?>