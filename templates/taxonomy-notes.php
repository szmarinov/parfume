<?php
/**
 * Template for displaying notes taxonomy archive
 */

get_header(); ?>

<div class="parfume-catalog-archive notes-archive">
    <div class="container">
        <header class="archive-header">
            <h1 class="archive-title">
                <?php single_cat_title('Нотка: '); ?>
            </h1>
            
            <?php if (term_description()) : ?>
                <div class="archive-description">
                    <?php echo term_description(); ?>
                </div>
            <?php endif; ?>
        </header>

        <?php
        $term = get_queried_object();
        $note_group = get_term_meta($term->term_id, 'note_group', true);
        ?>

        <?php if ($note_group) : ?>
            <div class="note-info">
                <p class="note-group">
                    <strong>Група:</strong> 
                    <span class="group-<?php echo sanitize_html_class($note_group); ?>">
                        <?php echo esc_html(ucfirst($note_group)); ?>
                    </span>
                </p>
            </div>
        <?php endif; ?>

        <div class="archive-content">
            <?php if (have_posts()) : ?>
                <div class="products-grid">
                    <?php while (have_posts()) : the_post(); ?>
                        <article class="product-card">
                            <div class="product-image">
                                <a href="<?php the_permalink(); ?>">
                                    <?php if (has_post_thumbnail()) : ?>
                                        <?php the_post_thumbnail('medium'); ?>
                                    <?php else : ?>
                                        <div class="no-image">
                                            <span class="dashicons dashicons-image-alt"></span>
                                        </div>
                                    <?php endif; ?>
                                </a>
                            </div>
                            
                            <div class="product-info">
                                <h2 class="product-title">
                                    <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                                </h2>
                                
                                <?php
                                $brands = get_the_terms(get_the_ID(), 'marki');
                                if ($brands && !is_wp_error($brands)) :
                                ?>
                                    <p class="product-brand">
                                        <?php foreach ($brands as $brand) : ?>
                                            <a href="<?php echo get_term_link($brand); ?>">
                                                <?php echo esc_html($brand->name); ?>
                                            </a>
                                        <?php endforeach; ?>
                                    </p>
                                <?php endif; ?>
                                
                                <?php
                                $types = get_the_terms(get_the_ID(), 'tip');
                                if ($types && !is_wp_error($types)) :
                                ?>
                                    <p class="product-type">
                                        <?php foreach ($types as $type) : ?>
                                            <span class="type-badge"><?php echo esc_html($type->name); ?></span>
                                        <?php endforeach; ?>
                                    </p>
                                <?php endif; ?>
                                
                                <div class="product-actions">
                                    <button class="add-to-comparison" data-product-id="<?php echo get_the_ID(); ?>">
                                        <span class="dashicons dashicons-plus"></span>
                                        Добави за сравнение
                                    </button>
                                </div>
                            </div>
                        </article>
                    <?php endwhile; ?>
                </div>

                <?php
                // Pagination
                the_posts_pagination(array(
                    'mid_size' => 2,
                    'prev_text' => __('← Предишна'),
                    'next_text' => __('Следваща →'),
                ));
                ?>

            <?php else : ?>
                <div class="no-products">
                    <p>Няма парфюми с тази нотка.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php get_footer(); ?>