<?php
/**
 * Single Parfume Template
 * 
 * Показва детайлна страница за един парфюм
 * 
 * @package Parfume_Catalog
 * @since 1.0.0
 */

// Предотвратяване на директен достъп
if (!defined('ABSPATH')) {
    exit;
}

get_header(); ?>

<div class="parfume-single-container">
    <?php while (have_posts()) : the_post(); ?>
        
        <div class="parfume-single-wrapper">
            <!-- Лява колона - основно съдържание (70%) -->
            <div class="parfume-main-content">
                
                <!-- Заглавна секция с лого и основни данни -->
                <header class="parfume-header">
                    <div class="parfume-header-left">
                        <?php if (has_post_thumbnail()) : ?>
                            <div class="parfume-featured-image">
                                <?php the_post_thumbnail('medium', array('class' => 'parfume-logo')); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="parfume-header-right">
                        <h1 class="parfume-title"><?php the_title(); ?></h1>
                        
                        <div class="parfume-meta-info">
                            <?php 
                            // Вид аромат
                            $vid_terms = get_the_terms(get_the_ID(), 'parfume_vid');
                            if ($vid_terms && !is_wp_error($vid_terms)) :
                                $vid_term = reset($vid_terms);
                                ?>
                                <span class="parfume-type"><?php echo esc_html($vid_term->name); ?></span>
                            <?php endif; ?>
                            
                            <?php 
                            // Марка
                            $marki_terms = get_the_terms(get_the_ID(), 'parfume_marki');
                            if ($marki_terms && !is_wp_error($marki_terms)) :
                                $marki_term = reset($marki_terms);
                                ?>
                                <span class="parfume-brand">
                                    <a href="<?php echo get_term_link($marki_term); ?>"><?php echo esc_html($marki_term->name); ?></a>
                                </span>
                            <?php endif; ?>
                            
                            <!-- Бутон за сравнение -->
                            <button type="button" class="parfume-compare-btn" data-parfume-id="<?php echo get_the_ID(); ?>">
                                <span class="compare-icon">⚖️</span>
                                <span class="compare-text"><?php _e('Добави за сравнение', 'parfume-catalog'); ?></span>
                            </button>
                        </div>
                        
                        <!-- Основни ароматни нотки -->
                        <div class="parfume-main-notes">
                            <?php 
                            $main_notes = get_post_meta(get_the_ID(), '_parfume_main_notes', true);
                            if (!empty($main_notes) && is_array($main_notes)) :
                                ?>
                                <div class="main-notes-list">
                                    <span class="notes-label"><?php _e('Основни нотки:', 'parfume-catalog'); ?></span>
                                    <?php foreach (array_slice($main_notes, 0, 5) as $note_id) : 
                                        $note_term = get_term($note_id, 'parfume_notes');
                                        if ($note_term && !is_wp_error($note_term)) :
                                            ?>
                                            <span class="note-tag"><?php echo esc_html($note_term->name); ?></span>
                                        <?php endif; 
                                    endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Подходящ за сезони/време -->
                        <div class="parfume-suitability">
                            <?php 
                            $basic_info = get_post_meta(get_the_ID(), '_parfume_basic_info', true);
                            if (!empty($basic_info)) :
                                ?>
                                <div class="suitability-icons">
                                    <?php if (!empty($basic_info['seasons'])) : ?>
                                        <?php foreach ($basic_info['seasons'] as $season) : ?>
                                            <span class="season-icon season-<?php echo esc_attr($season); ?>" title="<?php echo esc_attr(ucfirst($season)); ?>">
                                                <?php echo $this->get_season_icon($season); ?>
                                            </span>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($basic_info['time'])) : ?>
                                        <?php foreach ($basic_info['time'] as $time) : ?>
                                            <span class="time-icon time-<?php echo esc_attr($time); ?>" title="<?php echo esc_attr(ucfirst($time)); ?>">
                                                <?php echo $this->get_time_icon($time); ?>
                                            </span>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </header>
                
                <!-- Основно съдържание/описание -->
                <div class="parfume-content">
                    <div class="parfume-description">
                        <?php the_content(); ?>
                    </div>
                </div>
                
                <!-- Състав - пирамида с нотки -->
                <section class="parfume-composition">
                    <h2><?php _e('Състав', 'parfume-catalog'); ?></h2>
                    
                    <div class="notes-pyramid">
                        <?php 
                        $notes_composition = get_post_meta(get_the_ID(), '_parfume_notes_composition', true);
                        $notes_layers = array(
                            'top' => __('Връхни нотки', 'parfume-catalog'),
                            'heart' => __('Средни нотки', 'parfume-catalog'),
                            'base' => __('Базови нотки', 'parfume-catalog')
                        );
                        
                        foreach ($notes_layers as $layer => $layer_title) :
                            if (!empty($notes_composition[$layer])) :
                                ?>
                                <div class="notes-layer notes-<?php echo esc_attr($layer); ?>">
                                    <h3 class="layer-title"><?php echo esc_html($layer_title); ?></h3>
                                    <div class="notes-list">
                                        <?php foreach ($notes_composition[$layer] as $note_id) :
                                            $note_term = get_term($note_id, 'parfume_notes');
                                            if ($note_term && !is_wp_error($note_term)) :
                                                $note_group = get_term_meta($note_id, 'note_group', true);
                                                ?>
                                                <div class="note-item">
                                                    <span class="note-icon"><?php echo $this->get_note_group_icon($note_group); ?></span>
                                                    <span class="note-name"><?php echo esc_html($note_term->name); ?></span>
                                                </div>
                                            <?php endif;
                                        endforeach; ?>
                                    </div>
                                </div>
                            <?php endif;
                        endforeach; ?>
                    </div>
                </section>
                
                <!-- Графика на аромата -->
                <section class="parfume-characteristics">
                    <h2><?php _e('Графика на аромата', 'parfume-catalog'); ?></h2>
                    
                    <div class="characteristics-grid">
                        <!-- Дълготрайност и ароматна следа -->
                        <div class="characteristics-row">
                            <div class="characteristic-item">
                                <h3><?php _e('Дълготрайност', 'parfume-catalog'); ?></h3>
                                <?php $this->render_longevity_bars(); ?>
                            </div>
                            
                            <div class="characteristic-item">
                                <h3><?php _e('Ароматна следа', 'parfume-catalog'); ?></h3>
                                <?php $this->render_sillage_bars(); ?>
                            </div>
                        </div>
                        
                        <!-- Пол и цена -->
                        <div class="characteristics-row">
                            <div class="characteristic-item">
                                <h3><?php _e('Пол', 'parfume-catalog'); ?></h3>
                                <?php $this->render_gender_bars(); ?>
                            </div>
                            
                            <div class="characteristic-item">
                                <h3><?php _e('Цена', 'parfume-catalog'); ?></h3>
                                <?php $this->render_price_bars(); ?>
                            </div>
                        </div>
                    </div>
                </section>
                
                <!-- Предимства и недостатъци -->
                <section class="parfume-pros-cons">
                    <h2><?php _e('Предимства и недостатъци', 'parfume-catalog'); ?></h2>
                    
                    <div class="pros-cons-grid">
                        <?php 
                        $pros_cons = get_post_meta(get_the_ID(), '_parfume_pros_cons', true);
                        ?>
                        
                        <div class="pros-column">
                            <h3><?php _e('Предимства', 'parfume-catalog'); ?></h3>
                            <?php if (!empty($pros_cons['pros'])) : ?>
                                <ul class="pros-list">
                                    <?php foreach ($pros_cons['pros'] as $pro) : ?>
                                        <li class="pro-item">
                                            <span class="pro-icon">✓</span>
                                            <?php echo esc_html($pro); ?>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else : ?>
                                <p class="no-items"><?php _e('Няма добавени предимства.', 'parfume-catalog'); ?></p>
                            <?php endif; ?>
                        </div>
                        
                        <div class="cons-column">
                            <h3><?php _e('Недостатъци', 'parfume-catalog'); ?></h3>
                            <?php if (!empty($pros_cons['cons'])) : ?>
                                <ul class="cons-list">
                                    <?php foreach ($pros_cons['cons'] as $con) : ?>
                                        <li class="con-item">
                                            <span class="con-icon">✗</span>
                                            <?php echo esc_html($con); ?>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else : ?>
                                <p class="no-items"><?php _e('Няма добавени недостатъци.', 'parfume-catalog'); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </section>
                
                <!-- Подобни аромати -->
                <section class="parfume-similar">
                    <h2><?php _e('Подобни аромати', 'parfume-catalog'); ?></h2>
                    
                    <div class="similar-parfumes-grid">
                        <?php 
                        $similar_parfumes = $this->get_similar_parfumes(get_the_ID());
                        if (!empty($similar_parfumes)) :
                            foreach ($similar_parfumes as $similar_parfume) :
                                ?>
                                <div class="similar-parfume-item">
                                    <a href="<?php echo get_permalink($similar_parfume->ID); ?>">
                                        <?php if (has_post_thumbnail($similar_parfume->ID)) : ?>
                                            <?php echo get_the_post_thumbnail($similar_parfume->ID, 'thumbnail', array('class' => 'similar-parfume-image')); ?>
                                        <?php else : ?>
                                            <div class="similar-parfume-placeholder">
                                                <span class="placeholder-icon">🌸</span>
                                            </div>
                                        <?php endif; ?>
                                        <h3 class="similar-parfume-title"><?php echo esc_html($similar_parfume->post_title); ?></h3>
                                    </a>
                                </div>
                            <?php endforeach;
                        else : ?>
                            <p class="no-similar"><?php _e('Няма налични подобни аромати.', 'parfume-catalog'); ?></p>
                        <?php endif; ?>
                    </div>
                </section>
                
                <!-- Наскоро разгледани -->
                <section class="parfume-recently-viewed">
                    <h2><?php _e('Наскоро разгледани', 'parfume-catalog'); ?></h2>
                    
                    <div class="recently-viewed-grid" id="recently-viewed-parfumes">
                        <!-- Зарежда се с JavaScript -->
                    </div>
                </section>
                
                <!-- Други парфюми от марката -->
                <section class="parfume-from-brand">
                    <?php 
                    $brand_terms = get_the_terms(get_the_ID(), 'parfume_marki');
                    if ($brand_terms && !is_wp_error($brand_terms)) :
                        $brand_term = reset($brand_terms);
                        ?>
                        <h2><?php printf(__('Други парфюми от %s', 'parfume-catalog'), esc_html($brand_term->name)); ?></h2>
                        
                        <div class="brand-parfumes-grid">
                            <?php 
                            $brand_parfumes = $this->get_parfumes_from_brand($brand_term->term_id, get_the_ID());
                            if (!empty($brand_parfumes)) :
                                foreach ($brand_parfumes as $brand_parfume) :
                                    ?>
                                    <div class="brand-parfume-item">
                                        <a href="<?php echo get_permalink($brand_parfume->ID); ?>">
                                            <?php if (has_post_thumbnail($brand_parfume->ID)) : ?>
                                                <?php echo get_the_post_thumbnail($brand_parfume->ID, 'thumbnail', array('class' => 'brand-parfume-image')); ?>
                                            <?php else : ?>
                                                <div class="brand-parfume-placeholder">
                                                    <span class="placeholder-icon">💎</span>
                                                </div>
                                            <?php endif; ?>
                                            <h3 class="brand-parfume-title"><?php echo esc_html($brand_parfume->post_title); ?></h3>
                                        </a>
                                    </div>
                                <?php endforeach;
                            else : ?>
                                <p class="no-brand-parfumes"><?php _e('Няма други парфюми от тази марка.', 'parfume-catalog'); ?></p>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </section>
                
                <!-- Потребителски мнения и оценка -->
                <section class="parfume-comments">
                    <h2><?php _e('Потребителски мнения и оценка', 'parfume-catalog'); ?></h2>
                    
                    <?php 
                    // Зареждане на comments модула
                    if (class_exists('Parfume_Catalog_Comments')) {
                        $comments_module = new Parfume_Catalog_Comments();
                        echo $comments_module->render_comments_section(get_the_ID());
                    }
                    ?>
                </section>
                
            </div>
            
            <!-- Дясна колона - магазини (30%) -->
            <aside class="parfume-stores-column">
                <div class="stores-container">
                    <h2 class="stores-title"><?php _e('Сравни цените', 'parfume-catalog'); ?></h2>
                    <p class="stores-subtitle"><?php printf(__('Купи %s на най‑изгодната цена:', 'parfume-catalog'), get_the_title()); ?></p>
                    
                    <div class="stores-list">
                        <?php 
                        // Зареждане на stores данни
                        if (class_exists('Parfume_Catalog_Meta_Stores')) {
                            $stores_data = Parfume_Catalog_Meta_Stores::get_post_stores(get_the_ID());
                            
                            if (!empty($stores_data)) :
                                usort($stores_data, function($a, $b) {
                                    return ($a['order'] ?? 0) <=> ($b['order'] ?? 0);
                                });
                                
                                foreach ($stores_data as $store_data) :
                                    $this->render_store_item($store_data);
                                endforeach;
                            else : ?>
                                <div class="no-stores-message">
                                    <p><?php _e('Няма налични оферти за този парфюм.', 'parfume-catalog'); ?></p>
                                </div>
                            <?php endif;
                        }
                        ?>
                    </div>
                    
                    <div class="price-update-note">
                        <small><?php _e('Цените ни се актуализират на всеки 12 ч.', 'parfume-catalog'); ?></small>
                    </div>
                </div>
            </aside>
            
        </div>
        
    <?php endwhile; ?>
</div>

<?php 
// Добавяне на парфюма към "наскоро разгледани"
$this->add_to_recently_viewed(get_the_ID());

// Schema.org structured data
if (class_exists('Parfume_Catalog_Schema')) {
    $schema_module = new Parfume_Catalog_Schema();
    $schema_module->add_product_schema();
}

get_footer(); 

// Helper методи за template
class Parfume_Single_Template_Helpers {
    
    /**
     * Икони за сезони
     */
    public function get_season_icon($season) {
        $icons = array(
            'spring' => '🌸',
            'summer' => '☀️',
            'autumn' => '🍂',
            'winter' => '❄️'
        );
        
        return $icons[$season] ?? '🌿';
    }
    
    /**
     * Икони за време
     */
    public function get_time_icon($time) {
        $icons = array(
            'day' => '☀️',
            'night' => '🌙'
        );
        
        return $icons[$time] ?? '⏰';
    }
    
    /**
     * Икони за групи нотки
     */
    public function get_note_group_icon($group) {
        $icons = array(
            'цветни' => '🌸',
            'плодови' => '🍎',
            'дървесни' => '🌳',
            'ориенталски' => '🔥',
            'зелени' => '🌿',
            'ароматни' => '🌱',
            'гурме' => '🍰'
        );
        
        return $icons[$group] ?? '🌿';
    }
    
    /**
     * Render характеристични барове
     */
    public function render_longevity_bars() {
        $characteristics = get_post_meta(get_the_ID(), '_parfume_characteristics', true);
        $longevity = $characteristics['longevity'] ?? 3;
        
        $levels = array(
            1 => __('много слаб', 'parfume-catalog'),
            2 => __('слаб', 'parfume-catalog'),
            3 => __('умерен', 'parfume-catalog'),
            4 => __('траен', 'parfume-catalog'),
            5 => __('изключително траен', 'parfume-catalog')
        );
        
        echo '<div class="characteristic-bars longevity-bars">';
        for ($i = 1; $i <= 5; $i++) {
            $active = $i <= $longevity ? 'active' : '';
            echo '<div class="char-bar ' . $active . '" data-level="' . $i . '">';
            echo '<span class="bar-label">' . $levels[$i] . '</span>';
            echo '</div>';
        }
        echo '</div>';
    }
    
    public function render_sillage_bars() {
        $characteristics = get_post_meta(get_the_ID(), '_parfume_characteristics', true);
        $sillage = $characteristics['sillage'] ?? 2;
        
        $levels = array(
            1 => __('слаба', 'parfume-catalog'),
            2 => __('умерена', 'parfume-catalog'),
            3 => __('силна', 'parfume-catalog'),
            4 => __('огромна', 'parfume-catalog')
        );
        
        echo '<div class="characteristic-bars sillage-bars">';
        for ($i = 1; $i <= 4; $i++) {
            $active = $i <= $sillage ? 'active' : '';
            echo '<div class="char-bar ' . $active . '" data-level="' . $i . '">';
            echo '<span class="bar-label">' . $levels[$i] . '</span>';
            echo '</div>';
        }
        echo '</div>';
    }
    
    public function render_gender_bars() {
        $characteristics = get_post_meta(get_the_ID(), '_parfume_characteristics', true);
        $gender = $characteristics['gender'] ?? 3;
        
        $levels = array(
            1 => __('дамски', 'parfume-catalog'),
            2 => __('мъжки', 'parfume-catalog'),
            3 => __('унисекс', 'parfume-catalog'),
            4 => __('по-млади', 'parfume-catalog'),
            5 => __('по-зрели', 'parfume-catalog')
        );
        
        echo '<div class="characteristic-bars gender-bars">';
        for ($i = 1; $i <= 5; $i++) {
            $active = $i <= $gender ? 'active' : '';
            echo '<div class="char-bar ' . $active . '" data-level="' . $i . '">';
            echo '<span class="bar-label">' . $levels[$i] . '</span>';
            echo '</div>';
        }
        echo '</div>';
    }
    
    public function render_price_bars() {
        $characteristics = get_post_meta(get_the_ID(), '_parfume_characteristics', true);
        $price_category = $characteristics['price_category'] ?? 3;
        
        $levels = array(
            1 => __('прекалено скъп', 'parfume-catalog'),
            2 => __('скъп', 'parfume-catalog'),
            3 => __('приемлива цена', 'parfume-catalog'),
            4 => __('добра цена', 'parfume-catalog'),
            5 => __('евтин', 'parfume-catalog')
        );
        
        echo '<div class="characteristic-bars price-bars">';
        for ($i = 1; $i <= 5; $i++) {
            $active = $i <= $price_category ? 'active' : '';
            echo '<div class="char-bar ' . $active . '" data-level="' . $i . '">';
            echo '<span class="bar-label">' . $levels[$i] . '</span>';
            echo '</div>';
        }
        echo '</div>';
    }
    
    /**
     * Render store item
     */
    public function render_store_item($store_data) {
        $store_info = get_option('parfume_catalog_stores', array())[$store_data['store_id']] ?? array();
        $scraped_data = $store_data['scraped_data'] ?? array();
        
        ?>
        <div class="store-item" data-store-id="<?php echo esc_attr($store_data['store_id']); ?>">
            <div class="store-header">
                <?php if (!empty($store_info['logo'])) : ?>
                    <img src="<?php echo esc_url($store_info['logo']); ?>" alt="<?php echo esc_attr($store_info['name']); ?>" class="store-logo">
                <?php endif; ?>
                
                <div class="store-price">
                    <?php if (!empty($scraped_data['price'])) : ?>
                        <?php if (!empty($scraped_data['old_price']) && $scraped_data['old_price'] > $scraped_data['price']) : ?>
                            <span class="old-price"><?php echo number_format($scraped_data['old_price'], 2); ?> лв.</span>
                        <?php endif; ?>
                        <span class="current-price"><?php echo number_format($scraped_data['price'], 2); ?> лв.</span>
                        <?php if (!empty($scraped_data['old_price']) && $scraped_data['old_price'] > $scraped_data['price']) : ?>
                            <span class="discount-percent">
                                <?php 
                                $discount = round((($scraped_data['old_price'] - $scraped_data['price']) / $scraped_data['old_price']) * 100);
                                printf(__('По-изгодно с %d%%', 'parfume-catalog'), $discount);
                                ?>
                            </span>
                        <?php endif; ?>
                    <?php else : ?>
                        <span class="price-unavailable"><?php _e('Цена не е налична', 'parfume-catalog'); ?></span>
                    <?php endif; ?>
                    
                    <button type="button" class="price-info-btn" data-tooltip="<?php _e('Цената се актуализира на всеки 12 час', 'parfume-catalog'); ?>">
                        ℹ️
                    </button>
                </div>
            </div>
            
            <div class="store-details">
                <?php if (!empty($scraped_data['availability']) && $scraped_data['availability'] === 'available') : ?>
                    <span class="availability-status available">
                        <span class="status-icon">✓</span>
                        <?php _e('наличен', 'parfume-catalog'); ?>
                    </span>
                <?php endif; ?>
                
                <?php if (!empty($scraped_data['delivery'])) : ?>
                    <span class="delivery-info">
                        <?php echo esc_html($scraped_data['delivery']['text'] ?? $scraped_data['delivery']); ?>
                    </span>
                <?php endif; ?>
            </div>
            
            <!-- Варианти/размери -->
            <?php if (!empty($scraped_data['variants']) && count($scraped_data['variants']) > 1) : ?>
                <div class="store-variants">
                    <?php foreach ($scraped_data['variants'] as $variant) : ?>
                        <button type="button" class="variant-btn" data-variant='<?php echo esc_attr(wp_json_encode($variant)); ?>'>
                            <span class="variant-size"><?php echo esc_html($variant['ml']); ?> мл.</span>
                            <span class="variant-price"><?php echo esc_html($variant['price']); ?> лв.</span>
                            <?php if (!empty($variant['discount'])) : ?>
                                <span class="variant-discount">%</span>
                            <?php endif; ?>
                        </button>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <div class="store-actions">
                <?php if (!empty($store_data['promo_code'])) : ?>
                    <button type="button" class="promo-code-btn" data-promo-code="<?php echo esc_attr($store_data['promo_code']); ?>" data-promo-url="<?php echo esc_url($store_data['affiliate_url']); ?>">
                        <?php if (!empty($store_data['promo_code_info'])) : ?>
                            <span class="promo-info"><?php echo esc_html($store_data['promo_code_info']); ?></span>
                        <?php endif; ?>
                        <span class="promo-code"><?php echo esc_html($store_data['promo_code']); ?></span>
                        <span class="copy-icon">📋</span>
                    </button>
                    
                    <a href="<?php echo esc_url($store_data['affiliate_url']); ?>" target="_blank" rel="nofollow" class="store-btn secondary">
                        <?php _e('Към магазина', 'parfume-catalog'); ?>
                    </a>
                <?php else : ?>
                    <a href="<?php echo esc_url($store_data['affiliate_url']); ?>" target="_blank" rel="nofollow" class="store-btn primary full-width">
                        <?php _e('Към магазина', 'parfume-catalog'); ?>
                    </a>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Получаване на подобни парфюми
     */
    public function get_similar_parfumes($post_id, $limit = 4) {
        $settings = get_option('parfume_catalog_options', array());
        $similar_count = $settings['similar_parfumes_count'] ?? 4;
        
        // Получаване на нотки от текущия парфюм
        $current_notes = wp_get_post_terms($post_id, 'parfume_notes', array('fields' => 'ids'));
        
        if (empty($current_notes)) {
            return array();
        }
        
        $args = array(
            'post_type' => 'parfumes',
            'posts_per_page' => $similar_count,
            'post__not_in' => array($post_id),
            'tax_query' => array(
                array(
                    'taxonomy' => 'parfume_notes',
                    'field' => 'term_id',
                    'terms' => $current_notes,
                    'operator' => 'IN'
                )
            ),
            'meta_query' => array(
                array(
                    'key' => '_thumbnail_id',
                    'compare' => 'EXISTS'
                )
            )
        );
        
        $similar_query = new WP_Query($args);
        return $similar_query->posts;
    }
    
    /**
     * Получаване на парфюми от същата марка
     */
    public function get_parfumes_from_brand($brand_term_id, $exclude_post_id, $limit = 4) {
        $settings = get_option('parfume_catalog_options', array());
        $brand_count = $settings['brand_parfumes_count'] ?? 4;
        
        $args = array(
            'post_type' => 'parfumes',
            'posts_per_page' => $brand_count,
            'post__not_in' => array($exclude_post_id),
            'tax_query' => array(
                array(
                    'taxonomy' => 'parfume_marki',
                    'field' => 'term_id',
                    'terms' => $brand_term_id
                )
            )
        );
        
        $brand_query = new WP_Query($args);
        return $brand_query->posts;
    }
    
    /**
     * Добавяне към наскоро разгледани
     */
    public function add_to_recently_viewed($post_id) {
        // Това се прави с JavaScript във frontend
        ?>
        <script type="text/javascript">
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof parfumeCatalog !== 'undefined' && parfumeCatalog.addToRecentlyViewed) {
                parfumeCatalog.addToRecentlyViewed(<?php echo json_encode(array(
                    'id' => $post_id,
                    'title' => get_the_title($post_id),
                    'url' => get_permalink($post_id),
                    'image' => get_the_post_thumbnail_url($post_id, 'thumbnail')
                )); ?>);
            }
        });
        </script>
        <?php
    }
}

// Инициализиране на helper класа
$template_helpers = new Parfume_Single_Template_Helpers();
?>