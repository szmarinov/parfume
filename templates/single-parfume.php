<?php
/**
 * Single Parfume Template
 * 
 * 2-column layout:
 * - Column 1 (70%): Main content
 * - Column 2 (30%): Stores sidebar (sticky on desktop)
 * 
 * @package ParfumeReviews
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

get_header();

while (have_posts()) : the_post();
    
    $post_id = get_the_ID();
    $stores = get_post_meta($post_id, '_parfume_stores', true);
    $characteristics = get_post_meta($post_id, '_parfume_characteristics', true);
    $notes = get_post_meta($post_id, '_parfume_notes', true);
    $pros_cons = get_post_meta($post_id, '_parfume_pros_cons', true);
    
    ?>
    
    <article id="post-<?php the_ID(); ?>" <?php post_class('parfume-single'); ?>>
        
        <div class="parfume-container">
            
            <!-- Main Content Column (70%) -->
            <div class="parfume-main-column">
                
                <!-- Featured Image -->
                <?php if (has_post_thumbnail()) : ?>
                    <div class="parfume-featured-image">
                        <?php the_post_thumbnail('large', ['alt' => get_the_title()]); ?>
                    </div>
                <?php endif; ?>
                
                <!-- Title & Meta -->
                <header class="parfume-header">
                    <h1 class="parfume-title"><?php the_title(); ?></h1>
                    
                    <div class="parfume-meta">
                        <?php
                        // Brand
                        $brands = get_the_terms($post_id, 'brand');
                        if ($brands && !is_wp_error($brands)) {
                            echo '<span class="parfume-brand">' . esc_html($brands[0]->name) . '</span>';
                        }
                        
                        // Gender
                        $genders = get_the_terms($post_id, 'gender');
                        if ($genders && !is_wp_error($genders)) {
                            echo '<span class="parfume-gender">' . esc_html($genders[0]->name) . '</span>';
                        }
                        
                        // Type
                        $types = get_the_terms($post_id, 'type');
                        if ($types && !is_wp_error($types)) {
                            echo '<span class="parfume-type">' . esc_html($types[0]->name) . '</span>';
                        }
                        ?>
                    </div>
                </header>
                
                <!-- Main Description -->
                <div class="parfume-description">
                    <?php the_content(); ?>
                </div>
                
                <!-- Characteristics -->
                <?php if (!empty($characteristics)) : ?>
                    <section class="parfume-characteristics">
                        <h2>Характеристики</h2>
                        <div class="characteristics-grid">
                            <?php if (!empty($characteristics['longevity'])) : ?>
                                <div class="characteristic-item">
                                    <span class="characteristic-label">Дълготрайност:</span>
                                    <span class="characteristic-value"><?php echo esc_html($characteristics['longevity']); ?>/10</span>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($characteristics['sillage'])) : ?>
                                <div class="characteristic-item">
                                    <span class="characteristic-label">Сила:</span>
                                    <span class="characteristic-value"><?php echo esc_html($characteristics['sillage']); ?>/10</span>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($characteristics['price_value'])) : ?>
                                <div class="characteristic-item">
                                    <span class="characteristic-label">Стойност:</span>
                                    <span class="characteristic-value"><?php echo esc_html($characteristics['price_value']); ?>/10</span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </section>
                <?php endif; ?>
                
                <!-- Notes Composition -->
                <?php if (!empty($notes)) : ?>
                    <section class="parfume-composition">
                        <h2>Композиция</h2>
                        <div class="notes-pyramid">
                            
                            <?php if (!empty($notes['top'])) : ?>
                                <div class="notes-layer notes-top">
                                    <h3>Топ ноти</h3>
                                    <div class="notes-list">
                                        <?php
                                        $top_notes = explode(',', $notes['top']);
                                        foreach ($top_notes as $note) {
                                            echo '<span class="note-tag">' . esc_html(trim($note)) . '</span>';
                                        }
                                        ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($notes['middle'])) : ?>
                                <div class="notes-layer notes-middle">
                                    <h3>Средни ноти</h3>
                                    <div class="notes-list">
                                        <?php
                                        $middle_notes = explode(',', $notes['middle']);
                                        foreach ($middle_notes as $note) {
                                            echo '<span class="note-tag">' . esc_html(trim($note)) . '</span>';
                                        }
                                        ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($notes['base'])) : ?>
                                <div class="notes-layer notes-base">
                                    <h3>Базови ноти</h3>
                                    <div class="notes-list">
                                        <?php
                                        $base_notes = explode(',', $notes['base']);
                                        foreach ($base_notes as $note) {
                                            echo '<span class="note-tag">' . esc_html(trim($note)) . '</span>';
                                        }
                                        ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                        </div>
                    </section>
                <?php endif; ?>
                
                <!-- Pros & Cons -->
                <?php if (!empty($pros_cons)) : ?>
                    <section class="parfume-pros-cons">
                        <div class="pros-cons-grid">
                            
                            <?php if (!empty($pros_cons['pros'])) : ?>
                                <div class="pros-section">
                                    <h3>✓ Предимства</h3>
                                    <ul>
                                        <?php
                                        $pros = explode("\n", $pros_cons['pros']);
                                        foreach ($pros as $pro) {
                                            if (trim($pro)) {
                                                echo '<li>' . esc_html(trim($pro)) . '</li>';
                                            }
                                        }
                                        ?>
                                    </ul>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($pros_cons['cons'])) : ?>
                                <div class="cons-section">
                                    <h3>✗ Недостатъци</h3>
                                    <ul>
                                        <?php
                                        $cons = explode("\n", $pros_cons['cons']);
                                        foreach ($cons as $con) {
                                            if (trim($con)) {
                                                echo '<li>' . esc_html(trim($con)) . '</li>';
                                            }
                                        }
                                        ?>
                                    </ul>
                                </div>
                            <?php endif; ?>
                            
                        </div>
                    </section>
                <?php endif; ?>
                
            </div>
            
            <!-- Stores Sidebar Column (30%) -->
            <aside class="parfume-stores-column">
                <div class="stores-sticky-wrapper">
                    <?php 
                    // Load stores column template
                    if (!empty($stores) && is_array($stores)) {
                        include(plugin_dir_path(dirname(__FILE__)) . 'parts/stores-column.php');
                    } else {
                        echo '<p class="no-stores-message">Няма налични магазини за този парфюм.</p>';
                    }
                    ?>
                </div>
            </aside>
            
        </div>
        
    </article>
    
<?php
endwhile;

get_footer();
?>