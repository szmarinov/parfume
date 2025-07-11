<?php
/**
 * Archive Parfumes Template
 * 
 * Показва архивна страница с всички парфюми
 * 
 * @package Parfume_Catalog
 * @since 1.0.0
 */

// Предотвратяване на директен достъп
if (!defined('ABSPATH')) {
    exit;
}

get_header(); ?>

<div class="parfume-archive-container">
    
    <!-- Archive Header -->
    <header class="parfume-archive-header">
        <h1 class="archive-title">
            <?php 
            if (is_search()) {
                printf(__('Търсене за: %s', 'parfume-catalog'), '<span>' . get_search_query() . '</span>');
            } else {
                echo __('Всички парфюми', 'parfume-catalog');
            }
            ?>
        </h1>
        
        <?php if (is_search() && have_posts()) : ?>
            <p class="archive-description">
                <?php printf(__('Намерени %d резултата', 'parfume-catalog'), $wp_query->found_posts); ?>
            </p>
        <?php else : ?>
            <p class="archive-description">
                <?php _e('Разгледайте нашата колекция от парфюми и намерете перфектния аромат за вас.', 'parfume-catalog'); ?>
            </p>
        <?php endif; ?>
    </header>

    <!-- Filters Section -->
    <?php if (class_exists('Parfume_Catalog_Filters')) : ?>
        <section class="parfume-filters-section">
            <?php 
            $filters = new Parfume_Catalog_Filters();
            echo $filters->render_filters_form();
            ?>
        </section>
    <?php endif; ?>

    <!-- Results Info Bar -->
    <div class="results-info-bar">
        <div class="results-count">
            <?php if (have_posts()) : ?>
                <?php
                $paged = max(1, get_query_var('paged'));
                $posts_per_page = get_query_var('posts_per_page');
                $total_posts = $wp_query->found_posts;
                $start = ($paged - 1) * $posts_per_page + 1;
                $end = min($paged * $posts_per_page, $total_posts);
                
                printf(__('Показване %d-%d от %d парфюми', 'parfume-catalog'), $start, $end, $total_posts);
                ?>
            <?php else : ?>
                <?php _e('Няма намерени парфюми', 'parfume-catalog'); ?>
            <?php endif; ?>
        </div>
        
        <div class="results-controls">
            <!-- View Mode Toggle -->
            <div class="view-mode-toggle">
                <button type="button" class="view-mode-btn active" data-view="grid" title="<?php _e('Мрежа', 'parfume-catalog'); ?>">
                    <span class="dashicons dashicons-grid-view"></span>
                </button>
                <button type="button" class="view-mode-btn" data-view="list" title="<?php _e('Списък', 'parfume-catalog'); ?>">
                    <span class="dashicons dashicons-list-view"></span>
                </button>
            </div>
            
            <!-- Sort Options -->
            <div class="sort-options">
                <label for="sort-select"><?php _e('Подреди по:', 'parfume-catalog'); ?></label>
                <select id="sort-select" name="orderby">
                    <option value="date" <?php selected(get_query_var('orderby'), 'date'); ?>><?php _e('Най-нови', 'parfume-catalog'); ?></option>
                    <option value="title" <?php selected(get_query_var('orderby'), 'title'); ?>><?php _e('Име А-Я', 'parfume-catalog'); ?></option>
                    <option value="title_desc" <?php selected(get_query_var('orderby'), 'title_desc'); ?>><?php _e('Име Я-А', 'parfume-catalog'); ?></option>
                    <option value="rating" <?php selected(get_query_var('orderby'), 'rating'); ?>><?php _e('Рейтинг', 'parfume-catalog'); ?></option>
                    <option value="popularity" <?php selected(get_query_var('orderby'), 'popularity'); ?>><?php _e('Популярност', 'parfume-catalog'); ?></option>
                </select>
            </div>
            
            <!-- Posts Per Page -->
            <div class="posts-per-page">
                <label for="posts-per-page-select"><?php _e('Покажи:', 'parfume-catalog'); ?></label>
                <select id="posts-per-page-select" name="posts_per_page">
                    <option value="12" <?php selected(get_query_var('posts_per_page'), 12); ?>>12</option>
                    <option value="24" <?php selected(get_query_var('posts_per_page'), 24); ?>>24</option>
                    <option value="48" <?php selected(get_query_var('posts_per_page'), 48); ?>>48</option>
                    <option value="-1" <?php selected(get_query_var('posts_per_page'), -1); ?>><?php _e('Всички', 'parfume-catalog'); ?></option>
                </select>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <main class="parfume-archive-main">
        
        <?php if (have_posts()) : ?>
            
            <!-- Parfumes Grid -->
            <div class="parfume-grid" id="parfume-grid">
                <?php while (have_posts()) : the_post(); ?>
                    
                    <article id="post-<?php the_ID(); ?>" <?php post_class('parfume-card'); ?> data-parfume-id="<?php the_ID(); ?>">
                        
                        <!-- Featured Image -->
                        <div class="parfume-card-image-container">
                            <a href="<?php the_permalink(); ?>">
                                <?php if (has_post_thumbnail()) : ?>
                                    <?php the_post_thumbnail('medium', array(
                                        'class' => 'parfume-card-image',
                                        'loading' => 'lazy'
                                    )); ?>
                                <?php else : ?>
                                    <div class="parfume-card-placeholder">
                                        <span class="placeholder-icon">🌸</span>
                                    </div>
                                <?php endif; ?>
                            </a>
                            
                            <!-- Quick Actions Overlay -->
                            <div class="card-overlay">
                                <button type="button" class="parfume-compare-btn" data-parfume-id="<?php the_ID(); ?>" title="<?php _e('Добави за сравнение', 'parfume-catalog'); ?>">
                                    <span class="compare-icon">⚖️</span>
                                </button>
                                
                                <?php if (class_exists('Parfume_Catalog_Meta_Stores') && Parfume_Catalog_Meta_Stores::has_stores(get_the_ID())) : ?>
                                    <a href="<?php the_permalink(); ?>#stores" class="quick-buy-btn" title="<?php _e('Виж цени', 'parfume-catalog'); ?>">
                                        <span class="buy-icon">🛒</span>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Card Content -->
                        <div class="parfume-card-content">
                            
                            <!-- Title -->
                            <h2 class="parfume-card-title">
                                <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                            </h2>
                            
                            <!-- Meta Information -->
                            <div class="parfume-card-meta">
                                <?php 
                                // Марка
                                $marki_terms = get_the_terms(get_the_ID(), 'parfume_marki');
                                if ($marki_terms && !is_wp_error($marki_terms)) :
                                    $marki_term = reset($marki_terms);
                                    ?>
                                    <span class="parfume-card-brand">
                                        <a href="<?php echo get_term_link($marki_term); ?>"><?php echo esc_html($marki_term->name); ?></a>
                                    </span>
                                <?php endif; ?>
                                
                                <?php 
                                // Вид аромат
                                $vid_terms = get_the_terms(get_the_ID(), 'parfume_vid');
                                if ($vid_terms && !is_wp_error($vid_terms)) :
                                    $vid_term = reset($vid_terms);
                                    ?>
                                    <span class="parfume-card-type">
                                        <a href="<?php echo get_term_link($vid_term); ?>"><?php echo esc_html($vid_term->name); ?></a>
                                    </span>
                                <?php endif; ?>
                                
                                <?php 
                                // Година
                                $basic_info = get_post_meta(get_the_ID(), '_parfume_basic_info', true);
                                if (!empty($basic_info['year'])) :
                                    ?>
                                    <span class="parfume-card-year"><?php echo esc_html($basic_info['year']); ?></span>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Rating -->
                            <?php if (class_exists('Parfume_Catalog_Comments')) :
                                $average_rating = Parfume_Catalog_Comments::get_average_rating(get_the_ID());
                                $rating_count = Parfume_Catalog_Comments::get_rating_count(get_the_ID());
                                
                                if ($average_rating > 0) :
                                    ?>
                                    <div class="parfume-card-rating">
                                        <div class="rating-stars">
                                            <?php 
                                            for ($i = 1; $i <= 5; $i++) {
                                                if ($i <= $average_rating) {
                                                    echo '<span class="star filled">★</span>';
                                                } elseif ($i - 0.5 <= $average_rating) {
                                                    echo '<span class="star half">★</span>';
                                                } else {
                                                    echo '<span class="star empty">☆</span>';
                                                }
                                            }
                                            ?>
                                        </div>
                                        <span class="rating-count">(<?php echo $rating_count; ?>)</span>
                                    </div>
                                <?php endif;
                            endif; ?>
                            
                            <!-- Price Range -->
                            <?php 
                            $price_range = $this->get_parfume_price_range(get_the_ID());
                            if ($price_range) :
                                ?>
                                <div class="parfume-card-price">
                                    <?php if ($price_range['min'] === $price_range['max']) : ?>
                                        <span class="price"><?php echo $price_range['min']; ?> лв.</span>
                                    <?php else : ?>
                                        <span class="price-range"><?php echo $price_range['min']; ?> - <?php echo $price_range['max']; ?> лв.</span>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Excerpt -->
                            <div class="parfume-card-excerpt">
                                <?php 
                                if (has_excerpt()) {
                                    echo wp_trim_words(get_the_excerpt(), 20, '...');
                                } else {
                                    echo wp_trim_words(get_the_content(), 20, '...');
                                }
                                ?>
                            </div>
                            
                            <!-- Main Notes Preview -->
                            <?php 
                            $main_notes = get_post_meta(get_the_ID(), '_parfume_main_notes', true);
                            if (!empty($main_notes) && is_array($main_notes)) :
                                ?>
                                <div class="parfume-card-notes">
                                    <span class="notes-label"><?php _e('Основни нотки:', 'parfume-catalog'); ?></span>
                                    <div class="notes-preview">
                                        <?php 
                                        $displayed_notes = 0;
                                        foreach (array_slice($main_notes, 0, 3) as $note_id) :
                                            $note_term = get_term($note_id, 'parfume_notes');
                                            if ($note_term && !is_wp_error($note_term)) :
                                                $displayed_notes++;
                                                ?>
                                                <span class="note-preview"><?php echo esc_html($note_term->name); ?></span>
                                            <?php endif;
                                        endforeach;
                                        
                                        $total_notes = count($main_notes);
                                        if ($total_notes > 3) :
                                            ?>
                                            <span class="notes-more">+<?php echo ($total_notes - 3); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Card Actions -->
                            <div class="parfume-card-actions">
                                <a href="<?php the_permalink(); ?>" class="parfume-read-more">
                                    <?php _e('Виж детайли', 'parfume-catalog'); ?>
                                    <span class="read-more-icon">→</span>
                                </a>
                                
                                <button type="button" class="parfume-card-compare" data-parfume-id="<?php the_ID(); ?>">
                                    <span class="compare-text"><?php _e('Сравни', 'parfume-catalog'); ?></span>
                                </button>
                            </div>
                        </div>
                        
                    </article>
                    
                <?php endwhile; ?>
            </div>
            
            <!-- Load More Button (for AJAX pagination) -->
            <?php if ($wp_query->max_num_pages > 1) : ?>
                <div class="load-more-container">
                    <button type="button" class="load-more-btn" 
                            data-page="<?php echo max(1, get_query_var('paged')); ?>" 
                            data-max-pages="<?php echo $wp_query->max_num_pages; ?>"
                            data-container="#parfume-grid"
                            data-query-vars="<?php echo esc_attr(json_encode($wp_query->query_vars)); ?>">
                        <?php _e('Зареди още парфюми', 'parfume-catalog'); ?>
                        <span class="load-more-spinner" style="display: none;"></span>
                    </button>
                </div>
            <?php endif; ?>
            
            <!-- Standard Pagination (fallback) -->
            <nav class="parfume-pagination" role="navigation" aria-label="<?php _e('Навигация по страници', 'parfume-catalog'); ?>">
                <?php
                echo paginate_links(array(
                    'total' => $wp_query->max_num_pages,
                    'current' => max(1, get_query_var('paged')),
                    'format' => '?paged=%#%',
                    'show_all' => false,
                    'end_size' => 1,
                    'mid_size' => 2,
                    'prev_next' => true,
                    'prev_text' => '<span aria-hidden="true">&laquo;</span> ' . __('Предишна', 'parfume-catalog'),
                    'next_text' => __('Следваща', 'parfume-catalog') . ' <span aria-hidden="true">&raquo;</span>',
                    'add_args' => false,
                    'add_fragment' => '',
                    'type' => 'list'
                ));
                ?>
            </nav>
            
        <?php else : ?>
            
            <!-- No Results -->
            <div class="no-results">
                <div class="no-results-icon">🔍</div>
                <h2><?php _e('Няма намерени парфюми', 'parfume-catalog'); ?></h2>
                
                <?php if (is_search()) : ?>
                    <p><?php _e('Няма парфюми, които да отговарят на вашето търсене. Моля, опитайте с други ключови думи.', 'parfume-catalog'); ?></p>
                    
                    <!-- Search Suggestions -->
                    <div class="search-suggestions">
                        <h3><?php _e('Предложения за търсене:', 'parfume-catalog'); ?></h3>
                        <ul class="suggestions-list">
                            <li><?php _e('Опитайте по-общи ключови думи', 'parfume-catalog'); ?></li>
                            <li><?php _e('Проверете правописа', 'parfume-catalog'); ?></li>
                            <li><?php _e('Използвайте синоними или свързани думи', 'parfume-catalog'); ?></li>
                        </ul>
                    </div>
                    
                    <!-- Alternative Search -->
                    <div class="alternative-search">
                        <h3><?php _e('Ново търсене:', 'parfume-catalog'); ?></h3>
                        <?php get_search_form(); ?>
                    </div>
                    
                <?php else : ?>
                    <p><?php _e('Все още няма добавени парфюми в каталога.', 'parfume-catalog'); ?></p>
                <?php endif; ?>
                
                <!-- Popular Categories -->
                <?php 
                $popular_categories = get_terms(array(
                    'taxonomy' => 'parfume_type',
                    'number' => 6,
                    'orderby' => 'count',
                    'order' => 'DESC',
                    'hide_empty' => true
                ));
                
                if ($popular_categories && !is_wp_error($popular_categories)) :
                    ?>
                    <div class="popular-categories">
                        <h3><?php _e('Популярни категории:', 'parfume-catalog'); ?></h3>
                        <div class="categories-grid">
                            <?php foreach ($popular_categories as $category) : ?>
                                <a href="<?php echo get_term_link($category); ?>" class="category-link">
                                    <span class="category-name"><?php echo esc_html($category->name); ?></span>
                                    <span class="category-count">(<?php echo $category->count; ?>)</span>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            
        <?php endif; ?>
    </main>
    
    <!-- Sidebar (if needed) -->
    <?php if (is_active_sidebar('parfume-archive-sidebar')) : ?>
        <aside class="parfume-archive-sidebar">
            <?php dynamic_sidebar('parfume-archive-sidebar'); ?>
        </aside>
    <?php endif; ?>
    
</div>

<?php 
// Schema.org structured data за archive page
if (class_exists('Parfume_Catalog_Schema')) {
    $schema_module = new Parfume_Catalog_Schema();
    $schema_module->add_collection_page_schema();
}

get_footer(); 

// Helper класа за template функции
class Parfume_Archive_Template_Helpers {
    
    /**
     * Получаване на ценови диапазон за парфюм
     */
    public function get_parfume_price_range($post_id) {
        if (!class_exists('Parfume_Catalog_Scraper')) {
            return null;
        }
        
        $scraped_data = Parfume_Catalog_Scraper::get_scraped_data($post_id);
        
        if (empty($scraped_data)) {
            return null;
        }
        
        $prices = array();
        
        foreach ($scraped_data as $store_data) {
            if (!empty($store_data['scraped_data']) && is_array($store_data['scraped_data'])) {
                $data = $store_data['scraped_data'];
                
                if (!empty($data['price']) && is_numeric($data['price'])) {
                    $prices[] = floatval($data['price']);
                }
                
                // Цени от варианти
                if (!empty($data['variants']) && is_array($data['variants'])) {
                    foreach ($data['variants'] as $variant) {
                        if (!empty($variant['price']) && is_numeric($variant['price'])) {
                            $prices[] = floatval($variant['price']);
                        }
                    }
                }
            }
        }
        
        if (empty($prices)) {
            return null;
        }
        
        return array(
            'min' => number_format(min($prices), 2),
            'max' => number_format(max($prices), 2)
        );
    }
    
    /**
     * Получаване на популярни марки
     */
    public function get_popular_brands($limit = 10) {
        return get_terms(array(
            'taxonomy' => 'parfume_marki',
            'number' => $limit,
            'orderby' => 'count',
            'order' => 'DESC',
            'hide_empty' => true
        ));
    }
    
    /**
     * Получаване на популярни нотки
     */
    public function get_popular_notes($limit = 15) {
        return get_terms(array(
            'taxonomy' => 'parfume_notes',
            'number' => $limit,
            'orderby' => 'count',
            'order' => 'DESC',
            'hide_empty' => true
        ));
    }
    
    /**
     * Форматиране на рейтинг звезди
     */
    public function render_rating_stars($rating, $show_number = true) {
        $output = '<div class="rating-stars">';
        
        for ($i = 1; $i <= 5; $i++) {
            if ($i <= $rating) {
                $output .= '<span class="star filled">★</span>';
            } elseif ($i - 0.5 <= $rating) {
                $output .= '<span class="star half">★</span>';
            } else {
                $output .= '<span class="star empty">☆</span>';
            }
        }
        
        if ($show_number) {
            $output .= '<span class="rating-number">(' . number_format($rating, 1) . ')</span>';
        }
        
        $output .= '</div>';
        
        return $output;
    }
    
    /**
     * Получаване на сезонни икони
     */
    public function get_season_icons($seasons) {
        if (!is_array($seasons)) {
            return '';
        }
        
        $icons = array(
            'spring' => '🌸',
            'summer' => '☀️',
            'autumn' => '🍂',
            'winter' => '❄️'
        );
        
        $output = '<div class="season-icons">';
        foreach ($seasons as $season) {
            if (isset($icons[$season])) {
                $output .= '<span class="season-icon season-' . esc_attr($season) . '" title="' . esc_attr(ucfirst($season)) . '">' . $icons[$season] . '</span>';
            }
        }
        $output .= '</div>';
        
        return $output;
    }
    
    /**
     * Получаване на времеви икони
     */
    public function get_time_icons($times) {
        if (!is_array($times)) {
            return '';
        }
        
        $icons = array(
            'day' => '☀️',
            'night' => '🌙'
        );
        
        $output = '<div class="time-icons">';
        foreach ($times as $time) {
            if (isset($icons[$time])) {
                $output .= '<span class="time-icon time-' . esc_attr($time) . '" title="' . esc_attr(ucfirst($time)) . '">' . $icons[$time] . '</span>';
            }
        }
        $output .= '</div>';
        
        return $output;
    }
    
    /**
     * Рендериране на характеристични индикатори
     */
    public function render_characteristic_indicators($post_id) {
        $basic_info = get_post_meta($post_id, '_parfume_basic_info', true);
        $output = '';
        
        if (!empty($basic_info)) {
            $output .= '<div class="parfume-characteristics-preview">';
            
            // Сезони
            if (!empty($basic_info['seasons'])) {
                $output .= $this->get_season_icons($basic_info['seasons']);
            }
            
            // Време
            if (!empty($basic_info['time'])) {
                $output .= $this->get_time_icons($basic_info['time']);
            }
            
            // Интензивност
            if (!empty($basic_info['intensity'])) {
                $intensity_icons = array(
                    'light' => '🌸',
                    'medium' => '🌺',
                    'strong' => '🌹',
                    'intense' => '🔥'
                );
                
                if (isset($intensity_icons[$basic_info['intensity']])) {
                    $output .= '<span class="intensity-icon" title="' . ucfirst($basic_info['intensity']) . '">' . $intensity_icons[$basic_info['intensity']] . '</span>';
                }
            }
            
            $output .= '</div>';
        }
        
        return $output;
    }
    
    /**
     * Получаване на препоръчани парфюми
     */
    public function get_recommended_parfumes($limit = 4) {
        $args = array(
            'post_type' => 'parfumes',
            'posts_per_page' => $limit,
            'meta_query' => array(
                array(
                    'key' => '_parfume_featured',
                    'value' => '1',
                    'compare' => '='
                )
            ),
            'orderby' => 'rand'
        );
        
        $query = new WP_Query($args);
        return $query->posts;
    }
    
    /**
     * Показване на препоръчани парфюми секция
     */
    public function render_recommended_parfumes() {
        $recommended = $this->get_recommended_parfumes();
        
        if (empty($recommended)) {
            return '';
        }
        
        $output = '<section class="recommended-parfumes">';
        $output .= '<h3>' . __('Препоръчани парфюми', 'parfume-catalog') . '</h3>';
        $output .= '<div class="recommended-grid">';
        
        foreach ($recommended as $parfume) {
            $output .= '<div class="recommended-item">';
            $output .= '<a href="' . get_permalink($parfume->ID) . '">';
            
            if (has_post_thumbnail($parfume->ID)) {
                $output .= get_the_post_thumbnail($parfume->ID, 'thumbnail', array('class' => 'recommended-image'));
            } else {
                $output .= '<div class="recommended-placeholder"><span>🌸</span></div>';
            }
            
            $output .= '<h4 class="recommended-title">' . esc_html($parfume->post_title) . '</h4>';
            $output .= '</a>';
            $output .= '</div>';
        }
        
        $output .= '</div>';
        $output .= '</section>';
        
        return $output;
    }
    
    /**
     * Получаване на статистики за архива
     */
    public function get_archive_stats() {
        global $wpdb;
        
        $stats = array();
        
        // Общо парфюми
        $stats['total_parfumes'] = wp_count_posts('parfumes')->publish;
        
        // Общо марки
        $stats['total_brands'] = wp_count_terms('parfume_marki');
        
        // Общо нотки
        $stats['total_notes'] = wp_count_terms('parfume_notes');
        
        // Най-популярна марка
        $popular_brand = get_terms(array(
            'taxonomy' => 'parfume_marki',
            'number' => 1,
            'orderby' => 'count',
            'order' => 'DESC'
        ));
        
        $stats['popular_brand'] = !empty($popular_brand) ? $popular_brand[0]->name : '';
        
        return $stats;
    }
    
    /**
     * Рендериране на статистики секция
     */
    public function render_archive_stats() {
        $stats = $this->get_archive_stats();
        
        $output = '<section class="archive-stats">';
        $output .= '<div class="stats-grid">';
        
        $output .= '<div class="stat-item">';
        $output .= '<span class="stat-number">' . number_format($stats['total_parfumes']) . '</span>';
        $output .= '<span class="stat-label">' . __('Парфюми', 'parfume-catalog') . '</span>';
        $output .= '</div>';
        
        $output .= '<div class="stat-item">';
        $output .= '<span class="stat-number">' . number_format($stats['total_brands']) . '</span>';
        $output .= '<span class="stat-label">' . __('Марки', 'parfume-catalog') . '</span>';
        $output .= '</div>';
        
        $output .= '<div class="stat-item">';
        $output .= '<span class="stat-number">' . number_format($stats['total_notes']) . '</span>';
        $output .= '<span class="stat-label">' . __('Нотки', 'parfume-catalog') . '</span>';
        $output .= '</div>';
        
        if (!empty($stats['popular_brand'])) {
            $output .= '<div class="stat-item">';
            $output .= '<span class="stat-highlight">' . esc_html($stats['popular_brand']) . '</span>';
            $output .= '<span class="stat-label">' . __('Топ марка', 'parfume-catalog') . '</span>';
            $output .= '</div>';
        }
        
        $output .= '</div>';
        $output .= '</section>';
        
        return $output;
    }
    
    /**
     * Търсене suggestions
     */
    public function get_search_suggestions($query, $limit = 5) {
        global $wpdb;
        
        $suggestions = array();
        
        // Търсене в заглавия на парфюми
        $parfume_suggestions = $wpdb->get_results($wpdb->prepare("
            SELECT ID, post_title 
            FROM {$wpdb->posts} 
            WHERE post_type = 'parfumes' 
            AND post_status = 'publish' 
            AND post_title LIKE %s 
            LIMIT %d
        ", '%' . $wpdb->esc_like($query) . '%', $limit));
        
        foreach ($parfume_suggestions as $suggestion) {
            $suggestions[] = array(
                'type' => 'parfume',
                'title' => $suggestion->post_title,
                'url' => get_permalink($suggestion->ID)
            );
        }
        
        // Търсене в марки
        $brand_suggestions = get_terms(array(
            'taxonomy' => 'parfume_marki',
            'name__like' => $query,
            'number' => $limit - count($suggestions),
            'hide_empty' => true
        ));
        
        foreach ($brand_suggestions as $brand) {
            $suggestions[] = array(
                'type' => 'brand',
                'title' => $brand->name,
                'url' => get_term_link($brand)
            );
        }
        
        return $suggestions;
    }
    
    /**
     * Рендериране на search suggestions
     */
    public function render_search_suggestions($query) {
        $suggestions = $this->get_search_suggestions($query);
        
        if (empty($suggestions)) {
            return '';
        }
        
        $output = '<div class="search-suggestions-live">';
        $output .= '<h4>' . __('Възможно имахте предвид:', 'parfume-catalog') . '</h4>';
        $output .= '<ul class="suggestions-list">';
        
        foreach ($suggestions as $suggestion) {
            $type_label = $suggestion['type'] === 'parfume' ? __('Парфюм', 'parfume-catalog') : __('Марка', 'parfume-catalog');
            
            $output .= '<li class="suggestion-item">';
            $output .= '<a href="' . esc_url($suggestion['url']) . '">';
            $output .= '<span class="suggestion-title">' . esc_html($suggestion['title']) . '</span>';
            $output .= '<span class="suggestion-type">' . $type_label . '</span>';
            $output .= '</a>';
            $output .= '</li>';
        }
        
        $output .= '</ul>';
        $output .= '</div>';
        
        return $output;
    }
    
    /**
     * Проверка дали парфюмът е "нов"
     */
    public function is_new_parfume($post_id, $days = 30) {
        $post_date = get_the_date('U', $post_id);
        $current_time = current_time('timestamp');
        $days_difference = ($current_time - $post_date) / (24 * 60 * 60);
        
        return $days_difference <= $days;
    }
    
    /**
     * Проверка дали парфюмът е "популярен"
     */
    public function is_popular_parfume($post_id) {
        $views = get_post_meta($post_id, '_parfume_views', true);
        $comments_count = get_comments_number($post_id);
        
        // Считаме парфюма за популярен ако има над 100 гледания или над 5 коментара
        return ($views > 100) || ($comments_count > 5);
    }
    
    /**
     * Проверка дали парфюмът има отстъпка
     */
    public function has_discount($post_id) {
        if (!class_exists('Parfume_Catalog_Scraper')) {
            return false;
        }
        
        $scraped_data = Parfume_Catalog_Scraper::get_scraped_data($post_id);
        
        foreach ($scraped_data as $store_data) {
            if (!empty($store_data['scraped_data']) && is_array($store_data['scraped_data'])) {
                $data = $store_data['scraped_data'];
                
                if (!empty($data['old_price']) && !empty($data['price'])) {
                    if (floatval($data['old_price']) > floatval($data['price'])) {
                        return true;
                    }
                }
            }
        }
        
        return false;
    }
    
    /**
     * Получаване на процент отстъпка
     */
    public function get_discount_percentage($post_id) {
        if (!class_exists('Parfume_Catalog_Scraper')) {
            return 0;
        }
        
        $scraped_data = Parfume_Catalog_Scraper::get_scraped_data($post_id);
        $max_discount = 0;
        
        foreach ($scraped_data as $store_data) {
            if (!empty($store_data['scraped_data']) && is_array($store_data['scraped_data'])) {
                $data = $store_data['scraped_data'];
                
                if (!empty($data['old_price']) && !empty($data['price'])) {
                    $old_price = floatval($data['old_price']);
                    $new_price = floatval($data['price']);
                    
                    if ($old_price > $new_price) {
                        $discount = round((($old_price - $new_price) / $old_price) * 100);
                        $max_discount = max($max_discount, $discount);
                    }
                }
            }
        }
        
        return $max_discount;
    }
    
    /**
     * Рендериране на badges
     */
    public function render_parfume_badges($post_id) {
        $badges = array();
        
        // Нов парфюм
        if ($this->is_new_parfume($post_id)) {
            $badges[] = '<span class="badge badge-new">' . __('Нов', 'parfume-catalog') . '</span>';
        }
        
        // Популярен парфюм
        if ($this->is_popular_parfume($post_id)) {
            $badges[] = '<span class="badge badge-popular">' . __('Популярен', 'parfume-catalog') . '</span>';
        }
        
        // Отстъпка
        if ($this->has_discount($post_id)) {
            $discount = $this->get_discount_percentage($post_id);
            $badges[] = '<span class="badge badge-discount">-' . $discount . '%</span>';
        }
        
        // Featured парфюм
        if (get_post_meta($post_id, '_parfume_featured', true)) {
            $badges[] = '<span class="badge badge-featured">' . __('Препоръчан', 'parfume-catalog') . '</span>';
        }
        
        if (empty($badges)) {
            return '';
        }
        
        return '<div class="parfume-badges">' . implode('', $badges) . '</div>';
    }
    
    /**
     * Получаване на related парфюми по нотки
     */
    public function get_related_by_notes($post_id, $limit = 4) {
        $notes = wp_get_post_terms($post_id, 'parfume_notes', array('fields' => 'ids'));
        
        if (empty($notes)) {
            return array();
        }
        
        $args = array(
            'post_type' => 'parfumes',
            'posts_per_page' => $limit,
            'post__not_in' => array($post_id),
            'tax_query' => array(
                array(
                    'taxonomy' => 'parfume_notes',
                    'field' => 'term_id',
                    'terms' => $notes,
                    'operator' => 'IN'
                )
            ),
            'orderby' => 'rand'
        );
        
        $query = new WP_Query($args);
        return $query->posts;
    }
    
    /**
     * Форматиране на кратко описание
     */
    public function get_short_description($post_id, $length = 20) {
        $excerpt = get_the_excerpt($post_id);
        
        if (empty($excerpt)) {
            $content = get_the_content(null, false, $post_id);
            $excerpt = wp_strip_all_tags($content);
        }
        
        return wp_trim_words($excerpt, $length, '...');
    }
    
    /**
     * Получаване на цветово кодиране за интензивност
     */
    public function get_intensity_color($intensity) {
        $colors = array(
            'light' => '#e8f5e8',
            'medium' => '#fff3cd', 
            'strong' => '#f8d7da',
            'intense' => '#d1ecf1'
        );
        
        return isset($colors[$intensity]) ? $colors[$intensity] : '#f8f9fa';
    }
    
    /**
     * Рендериране на quick preview tooltip
     */
    public function render_quick_preview($post_id) {
        $basic_info = get_post_meta($post_id, '_parfume_basic_info', true);
        $main_notes = get_post_meta($post_id, '_parfume_main_notes', true);
        
        $output = '<div class="quick-preview-tooltip" style="display: none;">';
        
        // Основна информация
        if (!empty($basic_info['year'])) {
            $output .= '<div class="preview-year"><strong>' . __('Година:', 'parfume-catalog') . '</strong> ' . esc_html($basic_info['year']) . '</div>';
        }
        
        if (!empty($basic_info['perfumer'])) {
            $output .= '<div class="preview-perfumer"><strong>' . __('Парфюмерист:', 'parfume-catalog') . '</strong> ' . esc_html($basic_info['perfumer']) . '</div>';
        }
        
        // Характеристики
        $characteristics = get_post_meta($post_id, '_parfume_characteristics', true);
        if (!empty($characteristics)) {
            if (!empty($characteristics['longevity'])) {
                $longevity_labels = array(1 => 'Много слаб', 2 => 'Слаб', 3 => 'Умерен', 4 => 'Траен', 5 => 'Изключително траен');
                $output .= '<div class="preview-longevity"><strong>' . __('Дълготрайност:', 'parfume-catalog') . '</strong> ' . $longevity_labels[$characteristics['longevity']] . '</div>';
            }
            
            if (!empty($characteristics['sillage'])) {
                $sillage_labels = array(1 => 'Слаба', 2 => 'Умерена', 3 => 'Силна', 4 => 'Огромна');
                $output .= '<div class="preview-sillage"><strong>' . __('Ароматна следа:', 'parfume-catalog') . '</strong> ' . $sillage_labels[$characteristics['sillage']] . '</div>';
            }
        }
        
        // Основни нотки
        if (!empty($main_notes) && is_array($main_notes)) {
            $notes_names = array();
            foreach (array_slice($main_notes, 0, 3) as $note_id) {
                $note_term = get_term($note_id, 'parfume_notes');
                if ($note_term && !is_wp_error($note_term)) {
                    $notes_names[] = $note_term->name;
                }
            }
            
            if (!empty($notes_names)) {
                $output .= '<div class="preview-notes"><strong>' . __('Основни нотки:', 'parfume-catalog') . '</strong> ' . implode(', ', $notes_names) . '</div>';
            }
        }
        
        $output .= '</div>';
        
        return $output;
    }
}

// Инициализиране на helper класа
$archive_helpers = new Parfume_Archive_Template_Helpers();
?>