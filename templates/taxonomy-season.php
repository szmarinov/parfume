<?php
/**
 * Template for individual season taxonomy terms
 * Показва само отделните термини от таксономията "season" (напр. Есен, Зима, etc.)
 * ПРЕМАХНАТ е кода който показваше общия архив - сега archive-season.php го прави
 * 
 * Файл: templates/taxonomy-season.php
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header(); 

$current_term = get_queried_object();
$taxonomy_obj = get_taxonomy($current_term->taxonomy);

// Проверяваме дали сме на валиден term от season таксономията
if (!$current_term || $current_term->taxonomy !== 'season') {
    get_template_part('404');
    get_footer();
    return;
}
?>

<div class="parfume-archive season-archive season-<?php echo esc_attr($current_term->slug); ?>">
    <div class="archive-header">
        <h1 class="archive-title">
            <?php echo esc_html($current_term->name); ?>
            <span class="taxonomy-type"><?php _e('парфюми', 'parfume-reviews'); ?></span>
        </h1>
        
        <?php if ($current_term->description): ?>
            <div class="archive-description">
                <?php echo wpautop(esc_html($current_term->description)); ?>
            </div>
        <?php endif; ?>
        
        <div class="archive-meta">
            <span class="taxonomy-label"><?php echo esc_html($taxonomy_obj->labels->singular_name); ?>:</span>
            <span class="perfume-count">
                <?php printf(_n('%d парфюм', '%d парфюма', $current_term->count, 'parfume-reviews'), $current_term->count); ?>
            </span>
        </div>
        
        <!-- Добавяме навигация към общия архив и другите сезони -->
        <div class="season-navigation">
            <div class="back-to-archive">
                <a href="<?php echo esc_url(home_url('/parfiumi/season/')); ?>" class="archive-link">
                    ← <?php _e('Всички сезони', 'parfume-reviews'); ?>
                </a>
            </div>
            
            <div class="other-seasons">
                <h3><?php _e('Други сезони:', 'parfume-reviews'); ?></h3>
                <div class="season-links">
                    <?php
                    $all_seasons = get_terms(array(
                        'taxonomy' => 'season',
                        'hide_empty' => false,
                        'exclude' => array($current_term->term_id)
                    ));
                    
                    if (!empty($all_seasons) && !is_wp_error($all_seasons)):
                        foreach ($all_seasons as $season): ?>
                            <a href="<?php echo esc_url(get_term_link($season)); ?>" 
                               class="season-link season-<?php echo esc_attr($season->slug); ?>">
                                <?php echo esc_html($season->name); ?>
                                <span class="count">(<?php echo $season->count; ?>)</span>
                            </a>
                        <?php endforeach;
                    endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="archive-content">
        <div class="archive-sidebar">
            <?php 
            // Скриваме season филтъра тъй като сме вече на season страница
            echo do_shortcode('[parfume_filters show_season="false"]'); 
            ?>
        </div>

        <div class="archive-main">
            <?php if (have_posts()): ?>
                <div class="parfume-results-header">
                    <span class="results-count">
                        <?php
                        global $wp_query;
                        $total = $wp_query->found_posts;
                        printf(_n('Намерен %d парфюм', 'Намерени %d парфюма', $total, 'parfume-reviews'), $total);
                        ?>
                    </span>
                    
                    <!-- Опции за сортиране -->
                    <div class="sort-options">
                        <label for="parfume-sort"><?php _e('Сортиране:', 'parfume-reviews'); ?></label>
                        <select id="parfume-sort" name="orderby">
                            <option value="date" <?php selected(get_query_var('orderby'), 'date'); ?>><?php _e('Най-нови', 'parfume-reviews'); ?></option>
                            <option value="title" <?php selected(get_query_var('orderby'), 'title'); ?>><?php _e('По име', 'parfume-reviews'); ?></option>
                            <option value="rating" <?php selected(get_query_var('orderby'), 'rating'); ?>><?php _e('По рейтинг', 'parfume-reviews'); ?></option>
                        </select>
                    </div>
                </div>
                
                <div class="parfume-grid">
                    <?php while (have_posts()): the_post(); ?>
                        <div class="parfume-card">
                            <div class="parfume-thumbnail">
                                <a href="<?php the_permalink(); ?>">
                                    <?php if (has_post_thumbnail()): ?>
                                        <?php the_post_thumbnail('medium'); ?>
                                    <?php else: ?>
                                        <div class="no-image">
                                            <span><?php _e('No Image', 'parfume-reviews'); ?></span>
                                        </div>
                                    <?php endif; ?>
                                </a>
                                
                                <?php 
                                // Показваме rating ако има
                                $rating = get_post_meta(get_the_ID(), '_parfume_rating', true);
                                if ($rating): ?>
                                    <div class="parfume-rating">
                                        <div class="rating-stars" data-rating="<?php echo esc_attr($rating); ?>">
                                            <?php
                                            for ($i = 1; $i <= 5; $i++) {
                                                $class = $i <= $rating ? 'star filled' : 'star';
                                                echo '<span class="' . $class . '">★</span>';
                                            }
                                            ?>
                                        </div>
                                        <span class="rating-number"><?php echo esc_html($rating); ?>/5</span>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="parfume-content">
                                <h3 class="parfume-title">
                                    <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                                </h3>
                                
                                <?php 
                                // Показваме марката
                                $brands = get_the_terms(get_the_ID(), 'marki');
                                if ($brands && !is_wp_error($brands)): ?>
                                    <div class="parfume-brand">
                                        <?php foreach ($brands as $brand): ?>
                                            <a href="<?php echo esc_url(get_term_link($brand)); ?>" class="brand-link">
                                                <?php echo esc_html($brand->name); ?>
                                            </a>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="parfume-excerpt">
                                    <?php 
                                    if (has_excerpt()) {
                                        echo wp_trim_words(get_the_excerpt(), 15, '...');
                                    } else {
                                        echo wp_trim_words(get_the_content(), 15, '...');
                                    }
                                    ?>
                                </div>
                                
                                <!-- Показваме основните характеристики -->
                                <div class="parfume-meta">
                                    <?php
                                    // Пол
                                    $genders = get_the_terms(get_the_ID(), 'gender');
                                    if ($genders && !is_wp_error($genders)): ?>
                                        <span class="meta-item gender">
                                            <strong><?php _e('Пол:', 'parfume-reviews'); ?></strong>
                                            <?php 
                                            $gender_names = array();
                                            foreach ($genders as $gender) {
                                                $gender_names[] = $gender->name;
                                            }
                                            echo implode(', ', $gender_names);
                                            ?>
                                        </span>
                                    <?php endif; ?>
                                    
                                    <?php
                                    // Интензивност
                                    $intensities = get_the_terms(get_the_ID(), 'intensity');
                                    if ($intensities && !is_wp_error($intensities)): ?>
                                        <span class="meta-item intensity">
                                            <strong><?php _e('Интензивност:', 'parfume-reviews'); ?></strong>
                                            <?php echo esc_html($intensities[0]->name); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="parfume-actions">
                                    <a href="<?php the_permalink(); ?>" class="read-more">
                                        <?php _e('Прочети повече', 'parfume-reviews'); ?>
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
                
                <!-- Pagination -->
                <div class="archive-pagination">
                    <?php
                    the_posts_pagination(array(
                        'mid_size' => 2,
                        'prev_text' => __('‹ Предишна', 'parfume-reviews'),
                        'next_text' => __('Следваща ›', 'parfume-reviews'),
                        'screen_reader_text' => __('Навигация в страниците', 'parfume-reviews')
                    ));
                    ?>
                </div>
                
            <?php else: ?>
                <div class="no-results">
                    <h2><?php _e('Няма намерени парфюми', 'parfume-reviews'); ?></h2>
                    <p><?php printf(__('За сезон "%s" все още няма добавени парфюми.', 'parfume-reviews'), esc_html($current_term->name)); ?></p>
                    
                    <!-- Предложения за други сезони с парфюми -->
                    <?php
                    $seasons_with_posts = get_terms(array(
                        'taxonomy' => 'season',
                        'hide_empty' => true,
                        'number' => 4
                    ));
                    
                    if (!empty($seasons_with_posts) && !is_wp_error($seasons_with_posts)): ?>
                        <div class="suggested-seasons">
                            <h3><?php _e('Опитайте другите сезони:', 'parfume-reviews'); ?></h3>
                            <div class="season-suggestions">
                                <?php foreach ($seasons_with_posts as $season): ?>
                                    <a href="<?php echo esc_url(get_term_link($season)); ?>" class="season-suggestion">
                                        <strong><?php echo esc_html($season->name); ?></strong>
                                        <span class="count"><?php printf(_n('%d парфюм', '%d парфюма', $season->count, 'parfume-reviews'), $season->count); ?></span>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <div class="back-to-archive">
                        <a href="<?php echo esc_url(home_url('/parfiumi/season/')); ?>" class="button">
                            <?php _e('Всички сезони', 'parfume-reviews'); ?>
                        </a>
                        <a href="<?php echo esc_url(home_url('/parfiumi/')); ?>" class="button button-secondary">
                            <?php _e('Всички парфюми', 'parfume-reviews'); ?>
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
// Сортиране functionality
document.addEventListener('DOMContentLoaded', function() {
    const sortSelect = document.getElementById('parfume-sort');
    if (sortSelect) {
        sortSelect.addEventListener('change', function() {
            const currentUrl = new URL(window.location);
            currentUrl.searchParams.set('orderby', this.value);
            window.location.href = currentUrl.toString();
        });
    }
});
</script>

<?php get_footer(); ?>