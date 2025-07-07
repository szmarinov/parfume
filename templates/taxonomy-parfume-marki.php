<?php
/**
 * Taxonomy Template for Parfume Brands (Marki)
 * 
 * Template for displaying parfume brands with A-Z navigation
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();

// Get current term
$current_term = get_queried_object();
$is_brand_archive = !$current_term || is_tax('parfume_marki', ''); // Main brands archive

// Get all brands for A-Z navigation
$all_brands = get_terms(array(
    'taxonomy' => 'parfume_marki',
    'hide_empty' => false,
    'orderby' => 'name',
    'order' => 'ASC'
));

// Group brands by first letter
$grouped_brands = array();
$available_letters = array();

foreach ($all_brands as $brand) {
    $first_letter = mb_substr($brand->name, 0, 1, 'UTF-8');
    $first_letter = mb_strtoupper($first_letter, 'UTF-8');
    
    if (!isset($grouped_brands[$first_letter])) {
        $grouped_brands[$first_letter] = array();
    }
    
    $grouped_brands[$first_letter][] = $brand;
    $available_letters[] = $first_letter;
}

$available_letters = array_unique($available_letters);

// Define alphabet arrays
$latin_letters = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z');
$cyrillic_letters = array('А', 'Б', 'В', 'Г', 'Д', 'Е', 'Ж', 'З', 'И', 'Й', 'К', 'Л', 'М', 'Н', 'О', 'П', 'Р', 'С', 'Т', 'У', 'Ф', 'Х', 'Ц', 'Ч', 'Ш', 'Щ', 'Ъ', 'Ы', 'Ь', 'Э', 'Ю', 'Я');

// Get current filter parameters
$current_view = isset($_GET['view']) ? sanitize_text_field($_GET['view']) : 'grid';
$posts_per_page = isset($_GET['per_page']) ? absint($_GET['per_page']) : 12;
$orderby = isset($_GET['orderby']) ? sanitize_text_field($_GET['orderby']) : 'date';
$order = isset($_GET['order']) ? sanitize_text_field($_GET['order']) : 'DESC';

// Get comparison settings
$comparison_settings = Parfume_Admin_Comparison::get_comparison_settings();
?>

<div class="marki-archive-container">
    <?php if ($is_brand_archive): ?>
        <!-- Main brands archive page -->
        <div class="brands-archive-header">
            <h1 class="archive-title"><?php _e('Марки парфюми', 'parfume-catalog'); ?></h1>
            <div class="archive-description">
                <p><?php _e('Разгледайте парфюми от всички налични марки. Използвайте азбучната навигация за бързо намиране.', 'parfume-catalog'); ?></p>
            </div>
            
            <div class="brands-stats">
                <?php printf(__('Общо %d марки с парфюми', 'parfume-catalog'), count($all_brands)); ?>
            </div>
        </div>
        
        <!-- A-Z Navigation -->
        <div class="az-navigation">
            <div class="az-nav-container">
                <div class="az-nav-letters">
                    <!-- Latin letters -->
                    <div class="letter-group latin-letters">
                        <?php foreach ($latin_letters as $letter): ?>
                            <a href="#letter-<?php echo esc_attr($letter); ?>" 
                               class="nav-letter <?php echo in_array($letter, $available_letters) ? 'available' : 'unavailable'; ?>"
                               data-letter="<?php echo esc_attr($letter); ?>">
                                <?php echo esc_html($letter); ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="letter-separator">|</div>
                    
                    <!-- Cyrillic letters -->
                    <div class="letter-group cyrillic-letters">
                        <?php foreach ($cyrillic_letters as $letter): ?>
                            <a href="#letter-<?php echo esc_attr($letter); ?>" 
                               class="nav-letter <?php echo in_array($letter, $available_letters) ? 'available' : 'unavailable'; ?>"
                               data-letter="<?php echo esc_attr($letter); ?>">
                                <?php echo esc_html($letter); ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <div class="az-nav-controls">
                    <button type="button" id="show-all-brands" class="show-all-btn">
                        <?php _e('Покажи всички', 'parfume-catalog'); ?>
                    </button>
                    <button type="button" id="hide-empty-letters" class="toggle-empty-btn">
                        <?php _e('Скрий празни букви', 'parfume-catalog'); ?>
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Brands List -->
        <div class="brands-list-container">
            <?php foreach ($grouped_brands as $letter => $brands): ?>
                <div class="letter-section" id="letter-<?php echo esc_attr($letter); ?>">
                    <h2 class="letter-heading"><?php echo esc_html($letter); ?></h2>
                    
                    <div class="brands-grid">
                        <?php foreach ($brands as $brand): ?>
                            <div class="brand-item">
                                <div class="brand-info">
                                    <h3 class="brand-name">
                                        <a href="<?php echo get_term_link($brand); ?>">
                                            <?php echo esc_html($brand->name); ?>
                                        </a>
                                    </h3>
                                    
                                    <div class="brand-meta">
                                        <span class="brand-count">
                                            <?php printf(_n('%d парфюм', '%d парфюма', $brand->count, 'parfume-catalog'), $brand->count); ?>
                                        </span>
                                        
                                        <?php if ($brand->description): ?>
                                            <div class="brand-description">
                                                <?php echo esc_html(wp_trim_words($brand->description, 15)); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div class="brand-actions">
                                    <a href="<?php echo get_term_link($brand); ?>" class="view-brand-btn">
                                        <?php _e('Виж парфюми', 'parfume-catalog'); ?>
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <!-- No results message template -->
        <div id="no-results-message" class="no-results" style="display: none;">
            <div class="no-results-icon">
                <span class="dashicons dashicons-search"></span>
            </div>
            <h3><?php _e('Няма марки с тази буква', 'parfume-catalog'); ?></h3>
            <p><?php _e('Опитайте с друга буква или покажете всички марки.', 'parfume-catalog'); ?></p>
        </div>
        
    <?php else: ?>
        <!-- Individual brand page -->
        <div class="brand-single-header">
            <div class="brand-header-content">
                <h1 class="brand-title">
                    <?php printf(__('Парфюми от %s', 'parfume-catalog'), $current_term->name); ?>
                </h1>
                
                <?php if ($current_term->description): ?>
                    <div class="brand-description">
                        <?php echo wpautop($current_term->description); ?>
                    </div>
                <?php endif; ?>
                
                <div class="brand-breadcrumb">
                    <a href="<?php echo get_post_type_archive_link('parfumes'); ?>">
                        <?php _e('Всички парфюми', 'parfume-catalog'); ?>
                    </a>
                    <span class="separator">›</span>
                    <a href="<?php echo get_term_link('', 'parfume_marki'); ?>">
                        <?php _e('Марки', 'parfume-catalog'); ?>
                    </a>
                    <span class="separator">›</span>
                    <span class="current"><?php echo esc_html($current_term->name); ?></span>
                </div>
            </div>
            
            <div class="brand-stats">
                <?php
                global $wp_query;
                $total_posts = $wp_query->found_posts;
                ?>
                <span class="results-count">
                    <?php printf(_n('%d парфюм', '%d парфюма', $total_posts, 'parfume-catalog'), $total_posts); ?>
                </span>
            </div>
        </div>
        
        <!-- Brand parfumes with filters and sorting -->
        <div class="brand-parfumes-section">
            <div class="archive-controls">
                <div class="filters-section">
                    <button type="button" id="toggle-filters" class="filters-toggle">
                        <span class="filter-icon">🔍</span>
                        <?php _e('Филтри', 'parfume-catalog'); ?>
                        <span class="toggle-arrow">▼</span>
                    </button>
                    
                    <div class="filters-container" id="filters-container">
                        <form method="get" class="filters-form" id="filters-form">
                            <div class="filters-grid">
                                <!-- Search -->
                                <div class="filter-group">
                                    <label for="search-input"><?php _e('Търсене', 'parfume-catalog'); ?></label>
                                    <input type="text" 
                                           id="search-input" 
                                           name="s" 
                                           value="<?php echo esc_attr(get_search_query()); ?>" 
                                           placeholder="<?php _e('Търсете парфюм...', 'parfume-catalog'); ?>" 
                                           class="filter-input" />
                                </div>
                                
                                <!-- Type Filter -->
                                <div class="filter-group">
                                    <label for="type-filter"><?php _e('Тип', 'parfume-catalog'); ?></label>
                                    <select id="type-filter" name="parfume_type" class="filter-select">
                                        <option value=""><?php _e('Всички типове', 'parfume-catalog'); ?></option>
                                        <?php
                                        $types = get_terms(array(
                                            'taxonomy' => 'parfume_type',
                                            'hide_empty' => true
                                        ));
                                        foreach ($types as $type):
                                        ?>
                                            <option value="<?php echo esc_attr($type->slug); ?>" <?php selected(isset($_GET['parfume_type']) ? $_GET['parfume_type'] : '', $type->slug); ?>>
                                                <?php echo esc_html($type->name); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <!-- Season Filter -->
                                <div class="filter-group">
                                    <label for="season-filter"><?php _e('Сезон', 'parfume-catalog'); ?></label>
                                    <select id="season-filter" name="parfume_season" class="filter-select">
                                        <option value=""><?php _e('Всички сезони', 'parfume-catalog'); ?></option>
                                        <?php
                                        $seasons = get_terms(array(
                                            'taxonomy' => 'parfume_season',
                                            'hide_empty' => true
                                        ));
                                        foreach ($seasons as $season):
                                        ?>
                                            <option value="<?php echo esc_attr($season->slug); ?>" <?php selected(isset($_GET['parfume_season']) ? $_GET['parfume_season'] : '', $season->slug); ?>>
                                                <?php echo esc_html($season->name); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <!-- Price Range Filter -->
                                <div class="filter-group">
                                    <label for="price-filter"><?php _e('Ценова категория', 'parfume-catalog'); ?></label>
                                    <select id="price-filter" name="price_range" class="filter-select">
                                        <option value=""><?php _e('Всички цени', 'parfume-catalog'); ?></option>
                                        <option value="1-2" <?php selected(isset($_GET['price_range']) ? $_GET['price_range'] : '', '1-2'); ?>><?php _e('Евтини (1-2)', 'parfume-catalog'); ?></option>
                                        <option value="2-3" <?php selected(isset($_GET['price_range']) ? $_GET['price_range'] : '', '2-3'); ?>><?php _e('Добра цена (2-3)', 'parfume-catalog'); ?></option>
                                        <option value="3-4" <?php selected(isset($_GET['price_range']) ? $_GET['price_range'] : '', '3-4'); ?>><?php _e('Средни (3-4)', 'parfume-catalog'); ?></option>
                                        <option value="4-5" <?php selected(isset($_GET['price_range']) ? $_GET['price_range'] : '', '4-5'); ?>><?php _e('Скъпи (4-5)', 'parfume-catalog'); ?></option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="filters-actions">
                                <button type="submit" class="apply-filters-btn">
                                    <?php _e('Приложи филтри', 'parfume-catalog'); ?>
                                </button>
                                <button type="button" id="clear-filters" class="clear-filters-btn">
                                    <?php _e('Изчисти', 'parfume-catalog'); ?>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <div class="sorting-section">
                    <div class="view-controls">
                        <button type="button" 
                                class="view-btn <?php echo $current_view === 'grid' ? 'active' : ''; ?>" 
                                data-view="grid" 
                                title="<?php _e('Мрежов изглед', 'parfume-catalog'); ?>">
                            <span class="dashicons dashicons-grid-view"></span>
                        </button>
                        <button type="button" 
                                class="view-btn <?php echo $current_view === 'list' ? 'active' : ''; ?>" 
                                data-view="list"
                                title="<?php _e('Списъчен изглед', 'parfume-catalog'); ?>">
                            <span class="dashicons dashicons-list-view"></span>
                        </button>
                    </div>
                    
                    <div class="per-page-control">
                        <label for="per-page-select"><?php _e('Показвай:', 'parfume-catalog'); ?></label>
                        <select id="per-page-select" name="per_page">
                            <option value="12" <?php selected($posts_per_page, 12); ?>>12</option>
                            <option value="24" <?php selected($posts_per_page, 24); ?>>24</option>
                            <option value="36" <?php selected($posts_per_page, 36); ?>>36</option>
                            <option value="48" <?php selected($posts_per_page, 48); ?>>48</option>
                        </select>
                    </div>
                    
                    <div class="sort-controls">
                        <label for="orderby-select"><?php _e('Подреди по:', 'parfume-catalog'); ?></label>
                        <select id="orderby-select" name="orderby">
                            <option value="date" <?php selected($orderby, 'date'); ?>><?php _e('Дата', 'parfume-catalog'); ?></option>
                            <option value="title" <?php selected($orderby, 'title'); ?>><?php _e('Име', 'parfume-catalog'); ?></option>
                            <option value="rating" <?php selected($orderby, 'rating'); ?>><?php _e('Рейтинг', 'parfume-catalog'); ?></option>
                            <option value="popularity" <?php selected($orderby, 'popularity'); ?>><?php _e('Популярност', 'parfume-catalog'); ?></option>
                        </select>
                        
                        <select id="order-select" name="order">
                            <option value="DESC" <?php selected($order, 'DESC'); ?>><?php _e('Низходящо', 'parfume-catalog'); ?></option>
                            <option value="ASC" <?php selected($order, 'ASC'); ?>><?php _e('Възходящо', 'parfume-catalog'); ?></option>
                        </select>
                    </div>
                </div>
            </div>
            
            <!-- Parfumes Grid -->
            <?php if (have_posts()): ?>
                <div class="parfumes-grid <?php echo esc_attr($current_view); ?>-view" id="parfumes-grid">
                    <?php while (have_posts()): the_post(); ?>
                        <?php $this->render_parfume_item($current_view); ?>
                    <?php endwhile; ?>
                </div>
                
                <div class="pagination-container">
                    <?php
                    echo paginate_links(array(
                        'total' => $wp_query->max_num_pages,
                        'current' => max(1, get_query_var('paged')),
                        'format' => '?paged=%#%',
                        'show_all' => false,
                        'end_size' => 3,
                        'mid_size' => 3,
                        'prev_next' => true,
                        'prev_text' => __('‹ Предишна', 'parfume-catalog'),
                        'next_text' => __('Следваща ›', 'parfume-catalog'),
                        'add_args' => array_filter($_GET, function($key) {
                            return $key !== 'paged';
                        }, ARRAY_FILTER_USE_KEY)
                    ));
                    ?>
                </div>
                
            <?php else: ?>
                <div class="no-parfumes-found">
                    <div class="no-results-icon">
                        <span class="dashicons dashicons-search"></span>
                    </div>
                    <h3><?php _e('Няма намерени парфюми', 'parfume-catalog'); ?></h3>
                    <p><?php printf(__('Няма парфюми от марката %s, които отговарят на избраните филтри.', 'parfume-catalog'), $current_term->name); ?></p>
                    <a href="<?php echo get_term_link($current_term); ?>" class="button-primary">
                        <?php _e('Виж всички от марката', 'parfume-catalog'); ?>
                    </a>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<script>
jQuery(document).ready(function($) {
    <?php if ($is_brand_archive): ?>
    // A-Z Navigation functionality
    $('.nav-letter.available').click(function(e) {
        e.preventDefault();
        
        var letter = $(this).data('letter');
        var target = $('#letter-' + letter);
        
        if (target.length) {
            // Hide all sections
            $('.letter-section').hide();
            $('#no-results-message').hide();
            
            // Show target section
            target.show();
            
            // Smooth scroll to target
            $('html, body').animate({
                scrollTop: target.offset().top - 100
            }, 500);
            
            // Update active navigation
            $('.nav-letter').removeClass('active');
            $(this).addClass('active');
        }
    });
    
    // Unavailable letters
    $('.nav-letter.unavailable').click(function(e) {
        e.preventDefault();
        
        // Hide all sections and show no results
        $('.letter-section').hide();
        $('#no-results-message').show();
        
        // Scroll to no results message
        $('html, body').animate({
            scrollTop: $('#no-results-message').offset().top - 100
        }, 500);
        
        // Update active navigation
        $('.nav-letter').removeClass('active');
        $(this).addClass('active');
        
        // Show tooltip
        var letter = $(this).data('letter');
        $(this).attr('title', '<?php _e('Няма марки с буква', 'parfume-catalog'); ?> ' + letter);
    });
    
    // Show all brands
    $('#show-all-brands').click(function() {
        $('.letter-section').show();
        $('#no-results-message').hide();
        $('.nav-letter').removeClass('active');
        
        // Scroll to top of brands list
        $('html, body').animate({
            scrollTop: $('.brands-list-container').offset().top - 100
        }, 500);
    });
    
    // Toggle empty letters
    $('#hide-empty-letters').click(function() {
        var isHidden = $('.nav-letter.unavailable').is(':hidden');
        
        if (isHidden) {
            $('.nav-letter.unavailable').show();
            $(this).text('<?php _e('Скрий празни букви', 'parfume-catalog'); ?>');
        } else {
            $('.nav-letter.unavailable').hide();
            $(this).text('<?php _e('Покажи всички букви', 'parfume-catalog'); ?>');
        }
    });
    
    // Keyboard navigation
    $(document).keydown(function(e) {
        if (e.altKey) {
            var currentActive = $('.nav-letter.active');
            var allLetters = $('.nav-letter.available');
            var currentIndex = allLetters.index(currentActive);
            
            if (e.which === 37) { // Left arrow
                e.preventDefault();
                var prevIndex = currentIndex > 0 ? currentIndex - 1 : allLetters.length - 1;
                allLetters.eq(prevIndex).click();
            } else if (e.which === 39) { // Right arrow
                e.preventDefault();
                var nextIndex = currentIndex < allLetters.length - 1 ? currentIndex + 1 : 0;
                allLetters.eq(nextIndex).click();
            }
        }
    });
    
    <?php else: ?>
    // Individual brand page functionality
    
    // Toggle filters
    $('#toggle-filters').click(function() {
        $('#filters-container').slideToggle();
        $('.toggle-arrow').text($('#filters-container').is(':visible') ? '▲' : '▼');
    });
    
    // View controls
    $('.view-btn').click(function() {
        var view = $(this).data('view');
        $('.view-btn').removeClass('active');
        $(this).addClass('active');
        
        $('#parfumes-grid').removeClass('grid-view list-view').addClass(view + '-view');
        
        // Update URL
        var url = new URL(window.location);
        url.searchParams.set('view', view);
        window.history.replaceState({}, '', url);
    });
    
    // Per page and sorting controls
    $('#per-page-select, #orderby-select, #order-select').change(function() {
        var url = new URL(window.location);
        var name = $(this).attr('name');
        var value = $(this).val();
        
        url.searchParams.set(name, value);
        url.searchParams.delete('paged'); // Reset to first page
        
        window.location.href = url.toString();
    });
    
    // Clear filters
    $('#clear-filters').click(function() {
        $('#filters-form')[0].reset();
        $('#filters-form').submit();
    });
    
    // Auto-submit filters on change
    $('.filter-select').change(function() {
        $('#filters-form').submit();
    });
    
    <?php endif; ?>
    
    // Comparison functionality
    <?php if ($comparison_settings['enabled']): ?>
    var comparisonItems = JSON.parse(localStorage.getItem('parfumeComparison') || '[]');
    var maxItems = <?php echo $comparison_settings['max_items']; ?>;
    
    function updateComparisonButtons() {
        $('.comparison-btn').each(function() {
            var parfumeId = $(this).data('parfume-id');
            var isInComparison = comparisonItems.some(function(item) {
                return item.id == parfumeId;
            });
            
            if (isInComparison) {
                $(this).addClass('in-comparison')
                       .find('.comparison-text')
                       .text('<?php echo esc_js($comparison_settings['texts']['remove']); ?>');
            } else {
                $(this).removeClass('in-comparison')
                       .find('.comparison-text')
                       .text('<?php echo esc_js($comparison_settings['texts']['add']); ?>');
            }
        });
    }
    
    // Add/remove from comparison
    $(document).on('click', '.comparison-btn', function() {
        var parfumeId = parseInt($(this).data('parfume-id'));
        var parfumeTitle = $(this).data('parfume-title');
        var parfumeImage = $(this).data('parfume-image');
        
        var existingIndex = comparisonItems.findIndex(function(item) {
            return item.id === parfumeId;
        });
        
        if (existingIndex !== -1) {
            // Remove from comparison
            comparisonItems.splice(existingIndex, 1);
        } else {
            // Add to comparison
            if (comparisonItems.length >= maxItems) {
                alert('<?php echo esc_js($comparison_settings['texts']['max_reached']); ?>');
                return;
            }
            
            comparisonItems.push({
                id: parfumeId,
                title: parfumeTitle,
                image: parfumeImage
            });
        }
        
        localStorage.setItem('parfumeComparison', JSON.stringify(comparisonItems));
        updateComparisonButtons();
    });
    
    // Initialize comparison
    updateComparisonButtons();
    <?php endif; ?>
});
</script>

<?php
// Helper method for rendering parfume items (same as in archive template)
function render_parfume_item($view) {
    $post_id = get_the_ID();
    $parfume_stats = Parfume_Meta_Stats::get_public_stats($post_id);
    $parfume_basic = Parfume_Meta_Basic::get_parfume_info($post_id);
    $main_notes = Parfume_Meta_Notes::get_formatted_notes(get_post_meta($post_id, '_parfume_main_notes', true) ?: array());
    
    // Get taxonomies
    $parfume_marki = get_the_terms($post_id, 'parfume_marki');
    $parfume_type = get_the_terms($post_id, 'parfume_type');
    
    $comparison_settings = Parfume_Admin_Comparison::get_comparison_settings();
    ?>
    <div class="parfume-item <?php echo esc_attr($view); ?>-item">
        <div class="parfume-image-container">
            <a href="<?php the_permalink(); ?>">
                <?php if (has_post_thumbnail()): ?>
                    <?php the_post_thumbnail('medium', array('class' => 'parfume-item-image')); ?>
                <?php else: ?>
                    <div class="parfume-placeholder">
                        <span class="dashicons dashicons-format-image"></span>
                    </div>
                <?php endif; ?>
            </a>
            
            <?php if ($comparison_settings['enabled']): ?>
                <button type="button" 
                        class="comparison-btn" 
                        data-parfume-id="<?php echo $post_id; ?>"
                        data-parfume-title="<?php echo esc_attr(get_the_title()); ?>"
                        data-parfume-image="<?php echo esc_url(get_the_post_thumbnail_url($post_id, 'thumbnail')); ?>"
                        title="<?php echo esc_attr($comparison_settings['texts']['add']); ?>">
                    <span class="comparison-icon">⚖️</span>
                    <span class="comparison-text"><?php echo esc_html($comparison_settings['texts']['add']); ?></span>
                </button>
            <?php endif; ?>
            
            <?php if ($parfume_basic['limited_edition']): ?>
                <span class="limited-badge"><?php _e('Лимитирано', 'parfume-catalog'); ?></span>
            <?php endif; ?>
            
            <?php if ($parfume_basic['discontinued']): ?>
                <span class="discontinued-badge"><?php _e('Спрян', 'parfume-catalog'); ?></span>
            <?php endif; ?>
        </div>
        
        <div class="parfume-item-content">
            <h3 class="parfume-item-title">
                <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
            </h3>
            
            <div class="parfume-item-meta">
                <?php if ($parfume_type): ?>
                    <div class="parfume-type">
                        <?php echo esc_html($parfume_type[0]->name); ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <?php if ($parfume_stats['total_reviews'] > 0): ?>
                <div class="parfume-rating">
                    <div class="stars-rating">
                        <?php echo Parfume_Meta_Stats::get_formatted_rating($post_id)['stars_html']; ?>
                    </div>
                    <span class="rating-count">
                        (<?php echo $parfume_stats['total_reviews']; ?>)
                    </span>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($main_notes) && $view === 'list'): ?>
                <div class="parfume-notes">
                    <strong><?php _e('Основни нотки:', 'parfume-catalog'); ?></strong>
                    <?php
                    $note_names = array_slice(array_column($main_notes, 'name'), 0, 3);
                    echo esc_html(implode(', ', $note_names));
                    if (count($main_notes) > 3) {
                        echo '...';
                    }
                    ?>
                </div>
            <?php endif; ?>
            
            <?php if ($view === 'list' && $parfume_basic['description_short']): ?>
                <div class="parfume-description">
                    <?php echo esc_html(wp_trim_words($parfume_basic['description_short'], 20)); ?>
                </div>
            <?php endif; ?>
            
            <div class="parfume-item-footer">
                <?php if (!empty($parfume_basic['suitable_for'])): ?>
                    <div class="suitable-icons">
                        <?php
                        $suitable_icons = array(
                            'spring' => '🌸',
                            'summer' => '☀️', 
                            'autumn' => '🍂',
                            'winter' => '❄️',
                            'day' => '🌅',
                            'night' => '🌙'
                        );
                        
                        foreach (array_slice($parfume_basic['suitable_for'], 0, 3) as $suitable):
                            if (isset($suitable_icons[$suitable])):
                        ?>
                            <span class="suitable-icon" title="<?php echo esc_attr(Parfume_Meta_Basic::get_suitable_for_labels()[$suitable] ?? ''); ?>">
                                <?php echo $suitable_icons[$suitable]; ?>
                            </span>
                        <?php 
                            endif;
                        endforeach; 
                        ?>
                    </div>
                <?php endif; ?>
                
                <a href="<?php the_permalink(); ?>" class="view-parfume-btn">
                    <?php _e('Виж детайли', 'parfume-catalog'); ?>
                </a>
            </div>
        </div>
    </div>
    <?php
}

get_footer();
?>

<style>
.marki-archive-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

/* Brands Archive Styles */
.brands-archive-header {
    margin-bottom: 40px;
    text-align: center;
}

.brands-archive-header .archive-title {
    margin: 0 0 15px 0;
    font-size: 36px;
    color: #333;
}

.brands-archive-header .archive-description {
    margin-bottom: 15px;
    color: #666;
    font-size: 16px;
}

.brands-stats {
    font-size: 14px;
    color: #999;
}

/* A-Z Navigation */
.az-navigation {
    background: #f9f9f9;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 40px;
}

.az-nav-container {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 20px;
}

.az-nav-letters {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 5px;
}

.letter-group {
    display: flex;
    flex-wrap: wrap;
    gap: 3px;
}

.letter-separator {
    margin: 0 10px;
    font-size: 18px;
    color: #ccc;
}

.nav-letter {
    display: inline-block;
    width: 30px;
    height: 30px;
    line-height: 30px;
    text-align: center;
    text-decoration: none;
    border-radius: 4px;
    transition: all 0.3s ease;
    font-weight: 500;
    font-size: 14px;
}

.nav-letter.available {
    background: #fff;
    color: #0073aa;
    border: 1px solid #0073aa;
}

.nav-letter.available:hover,
.nav-letter.available.active {
    background: #0073aa;
    color: white;
}

.nav-letter.unavailable {
    background: #f0f0f0;
    color: #ccc;
    border: 1px solid #e0e0e0;
    cursor: not-allowed;
}

.az-nav-controls {
    display: flex;
    gap: 10px;
}

.show-all-btn,
.toggle-empty-btn {
    background: #0073aa;
    color: white;
    border: none;
    padding: 8px 15px;
    border-radius: 4px;
    cursor: pointer;
    font-size: 13px;
}

.show-all-btn:hover,
.toggle-empty-btn:hover {
    background: #005a87;
}

/* Brands List */
.brands-list-container {
    margin-bottom: 40px;
}

.letter-section {
    margin-bottom: 50px;
}

.letter-heading {
    font-size: 28px;
    color: #0073aa;
    margin: 0 0 20px 0;
    padding: 10px 0;
    border-bottom: 2px solid #0073aa;
    display: inline-block;
    min-width: 50px;
}

.brands-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
}

.brand-item {
    background: white;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
    transition: box-shadow 0.3s ease;
}

.brand-item:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.brand-name {
    margin: 0 0 10px 0;
    font-size: 18px;
}

.brand-name a {
    color: #333;
    text-decoration: none;
}

.brand-name a:hover {
    color: #0073aa;
}

.brand-count {
    font-size: 13px;
    color: #666;
    font-weight: 500;
}

.brand-description {
    margin-top: 8px;
    font-size: 13px;
    color: #555;
    line-height: 1.4;
}

.brand-actions {
    margin-top: 15px;
    text-align: right;
}

.view-brand-btn {
    background: #0073aa;
    color: white;
    padding: 8px 15px;
    border-radius: 4px;
    text-decoration: none;
    font-size: 13px;
    font-weight: 500;
}

.view-brand-btn:hover {
    background: #005a87;
    color: white;
    text-decoration: none;
}

/* Individual Brand Page */
.brand-single-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 2px solid #eee;
}

.brand-title {
    margin: 0 0 15px 0;
    font-size: 32px;
    line-height: 1.2;
}

.brand-description {
    margin-bottom: 15px;
    color: #666;
}

.brand-breadcrumb {
    font-size: 14px;
    color: #666;
}

.brand-breadcrumb a {
    color: #0073aa;
    text-decoration: none;
}

.brand-breadcrumb .separator {
    margin: 0 8px;
}

.brand-breadcrumb .current {
    font-weight: 500;
    color: #333;
}

.brand-parfumes-section {
    margin-top: 20px;
}

/* Common styles (filters, sorting, parfume items) */
.archive-controls {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 30px;
    gap: 20px;
}

.filters-toggle {
    background: #0073aa;
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 4px;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 14px;
}

.filters-container {
    background: #f9f9f9;
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 20px;
    margin-top: 15px;
}

.filters-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 20px;
}

.filter-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: 500;
    color: #555;
}

.filter-input,
.filter-select {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
}

.sorting-section {
    display: flex;
    align-items: center;
    gap: 20px;
    flex-wrap: wrap;
}

.view-controls {
    display: flex;
    gap: 5px;
}

.view-btn {
    background: #f0f0f0;
    border: 1px solid #ddd;
    padding: 8px 12px;
    cursor: pointer;
    border-radius: 4px;
}

.view-btn.active {
    background: #0073aa;
    color: white;
    border-color: #0073aa;
}

.parfumes-grid {
    display: grid;
    gap: 30px;
    margin-bottom: 40px;
}

.parfumes-grid.grid-view {
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
}

.parfumes-grid.list-view {
    grid-template-columns: 1fr;
}

.parfume-item {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 8px;
    overflow: hidden;
    transition: box-shadow 0.3s ease;
}

.parfume-item:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.parfume-item.list-item {
    display: grid;
    grid-template-columns: 200px 1fr;
    gap: 20px;
}

.parfume-image-container {
    position: relative;
}

.parfume-item-image {
    width: 100%;
    height: 250px;
    object-fit: cover;
}

.parfume-placeholder {
    width: 100%;
    height: 250px;
    background: #f0f0f0;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 48px;
    color: #ccc;
}

.comparison-btn {
    position: absolute;
    top: 10px;
    right: 10px;
    background: rgba(0, 115, 170, 0.9);
    color: white;
    border: none;
    padding: 5px 10px;
    border-radius: 4px;
    cursor: pointer;
    font-size: 12px;
    display: flex;
    align-items: center;
    gap: 4px;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.parfume-item:hover .comparison-btn {
    opacity: 1;
}

.comparison-btn.in-comparison {
    background: #46b450;
    opacity: 1;
}

.no-results {
    text-align: center;
    padding: 60px 20px;
    background: #f9f9f9;
    border-radius: 8px;
}

.no-results-icon {
    font-size: 64px;
    color: #ccc;
    margin-bottom: 20px;
}

.no-parfumes-found h3,
.no-results h3 {
    margin: 0 0 10px 0;
    color: #333;
}

.pagination-container {
    display: flex;
    justify-content: center;
    margin-top: 40px;
}

@media (max-width: 768px) {
    .az-nav-container {
        flex-direction: column;
        align-items: stretch;
    }
    
    .az-nav-letters {
        justify-content: center;
    }
    
    .letter-group {
        justify-content: center;
    }
    
    .nav-letter {
        width: 25px;
        height: 25px;
        line-height: 25px;
        font-size: 12px;
    }
    
    .brands-grid {
        grid-template-columns: 1fr;
    }
    
    .brand-single-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 15px;
    }
    
    .archive-controls {
        flex-direction: column;
        align-items: stretch;
    }
    
    .sorting-section {
        justify-content: center;
    }
    
    .parfumes-grid.grid-view {
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    }
    
    .parfume-item.list-item {
        grid-template-columns: 120px 1fr;
        gap: 15px;
    }
}
</style>