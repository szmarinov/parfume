<?php
/**
 * Taxonomy Perfumer Template - Archive и Single page за парфюмеристи
 * 
 * Този файл се зарежда за:
 * - Archive страница с всички парфюмеристи (/parfiumi/parfumeri/)
 * - Single страница на конкретен парфюмерист (/parfiumi/parfumeri/alberto-morillas/)
 * 
 * Файл: templates/taxonomy-perfumer.php
 * ПЪЛНА ВЕРСИЯ - показва всички мета полета
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();

$current_term = get_queried_object();
$is_single_perfumer = $current_term && isset($current_term->name) && !empty($current_term->name);

// Ако е конкретен парфюмерист, показваме single page
if ($is_single_perfumer) {
    // Получаваме всички мета полета
    $perfumer_image_id = get_term_meta($current_term->term_id, 'perfumer-image-id', true);
    $birth_date = get_term_meta($current_term->term_id, 'perfumer_birth_date', true);
    $nationality = get_term_meta($current_term->term_id, 'perfumer_nationality', true);
    $education = get_term_meta($current_term->term_id, 'perfumer_education', true);
    $career_start = get_term_meta($current_term->term_id, 'perfumer_career_start', true);
    $signature_style = get_term_meta($current_term->term_id, 'perfumer_signature_style', true);
    $famous_fragrances = get_term_meta($current_term->term_id, 'perfumer_famous_fragrances', true);
    $awards = get_term_meta($current_term->term_id, 'perfumer_awards', true);
    $website = get_term_meta($current_term->term_id, 'perfumer_website', true);
    $social_media = get_term_meta($current_term->term_id, 'perfumer_social_media', true);
    
    // Получаваме парфюмите на този парфюмерист
    $perfumer_perfumes = get_posts(array(
        'post_type' => 'parfume',
        'posts_per_page' => -1,
        'tax_query' => array(
            array(
                'taxonomy' => 'perfumer',
                'field' => 'term_id',
                'terms' => $current_term->term_id
            )
        ),
        'orderby' => 'title',
        'order' => 'ASC'
    ));
    ?>
    
    <div class="single-perfumer-page perfumer-taxonomy-page">
        <div class="container">
            
            <!-- Breadcrumb Navigation -->
            <nav class="breadcrumb">
                <a href="<?php echo home_url(); ?>"><?php _e('Начало', 'parfume-reviews'); ?></a>
                <span class="separator"> › </span>
                <a href="<?php echo home_url('/parfiumi/'); ?>"><?php _e('Парфюми', 'parfume-reviews'); ?></a>
                <span class="separator"> › </span>
                <a href="<?php echo home_url('/parfiumi/parfumeri/'); ?>"><?php _e('Парфюмеристи', 'parfume-reviews'); ?></a>
                <span class="separator"> › </span>
                <span class="current"><?php echo esc_html($current_term->name); ?></span>
            </nav>
            
            <!-- Perfumer Header -->
            <div class="perfumer-header">
                <div class="perfumer-photo">
                    <?php if ($perfumer_image_id): ?>
                        <div class="perfumer-image">
                            <?php echo wp_get_attachment_image($perfumer_image_id, 'medium', false, array('class' => 'perfumer-avatar')); ?>
                        </div>
                    <?php else: ?>
                        <div class="perfumer-avatar">
                            <span class="perfumer-initials">
                                <?php
                                $name_parts = explode(' ', $current_term->name);
                                $initials = '';
                                foreach ($name_parts as $part) {
                                    if (!empty($part)) {
                                        $initials .= mb_substr($part, 0, 1);
                                        if (strlen($initials) >= 2) break;
                                    }
                                }
                                echo esc_html($initials);
                                ?>
                            </span>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="perfumer-info">
                    <h1 class="perfumer-name"><?php echo esc_html($current_term->name); ?></h1>
                    
                    <?php if (!empty($current_term->description)): ?>
                        <div class="perfumer-description">
                            <?php echo wpautop(esc_html($current_term->description)); ?>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Perfumer Statistics -->
                    <div class="perfumer-stats">
                        <div class="stat-item">
                            <span class="stat-number"><?php echo count($perfumer_perfumes); ?></span>
                            <span class="stat-label"><?php _e('Парфюми', 'parfume-reviews'); ?></span>
                        </div>
                        
                        <?php if (!empty($career_start)): ?>
                            <div class="stat-item">
                                <span class="stat-number"><?php echo esc_html($career_start); ?></span>
                                <span class="stat-label"><?php _e('Кариера от', 'parfume-reviews'); ?></span>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($nationality)): ?>
                            <div class="stat-item">
                                <span class="stat-number"><?php echo esc_html($nationality); ?></span>
                                <span class="stat-label"><?php _e('Националност', 'parfume-reviews'); ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Social Links -->
                    <div class="perfumer-links">
                        <?php if (!empty($website)): ?>
                            <a href="<?php echo esc_url($website); ?>" target="_blank" rel="noopener" class="perfumer-website">
                                <span class="dashicons dashicons-admin-site"></span>
                                <?php _e('Уебсайт', 'parfume-reviews'); ?>
                            </a>
                        <?php endif; ?>
                        
                        <?php if (!empty($social_media)): ?>
                            <a href="<?php echo esc_url($social_media); ?>" target="_blank" rel="noopener" class="perfumer-social">
                                <span class="dashicons dashicons-share"></span>
                                <?php _e('Социални мрежи', 'parfume-reviews'); ?>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Perfumer Content Sections -->
            <div class="perfumer-content">
                
                <!-- Biography Section -->
                    <h2><?php _e('Биография', 'parfume-reviews'); ?></h2>
                    
                    <div class="biography-grid">
                        <!-- Personal Info -->
                        <div class="bio-section personal-info">
                            <h3><?php _e('Лична информация', 'parfume-reviews'); ?></h3>
                            
                            <?php if (!empty($birth_date)): ?>
                                <div class="meta-item">
                                    <span class="meta-label"><?php _e('Дата на раждане:', 'parfume-reviews'); ?></span>
                                    <span class="meta-value">
                                        <?php 
                                        $date_obj = DateTime::createFromFormat('Y-m-d', $birth_date);
                                        if ($date_obj) {
                                            echo $date_obj->format('d.m.Y');
                                            
                                            // Изчисляваме възрастта
                                            $today = new DateTime();
                                            $age = $today->diff($date_obj)->y;
                                            echo ' (' . sprintf(__('%d години', 'parfume-reviews'), $age) . ')';
                                        } else {
                                            echo esc_html($birth_date);
                                        }
                                        ?>
                                    </span>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($nationality)): ?>
                                <div class="meta-item">
                                    <span class="meta-label"><?php _e('Националност:', 'parfume-reviews'); ?></span>
                                    <span class="meta-value"><?php echo esc_html($nationality); ?></span>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($career_start)): ?>
                                <div class="meta-item">
                                    <span class="meta-label"><?php _e('Кариера започната:', 'parfume-reviews'); ?></span>
                                    <span class="meta-value">
                                        <?php 
                                        echo esc_html($career_start) . ' г.';
                                        $experience_years = date('Y') - intval($career_start);
                                        if ($experience_years > 0) {
                                            echo ' (' . sprintf(__('%d години опит', 'parfume-reviews'), $experience_years) . ')';
                                        }
                                        ?>
                                    </span>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Education -->
                        <?php if (!empty($education)): ?>
                            <div class="bio-section education">
                                <h3><?php _e('Образование', 'parfume-reviews'); ?></h3>
                                <div class="education-content">
                                    <?php echo wpautop(esc_html($education)); ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Signature Style -->
                        <?php if (!empty($signature_style)): ?>
                            <div class="bio-section signature-style">
                                <h3><?php _e('Характерен стил', 'parfume-reviews'); ?></h3>
                                <div class="style-content">
                                    <?php echo wpautop(esc_html($signature_style)); ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Perfumes Section -->
                <div class="perfumes-section">
                    <h2><?php _e('Парфюми от ' . esc_html($current_term->name), 'parfume-reviews'); ?></h2>
                    
                    <?php if (!empty($perfumer_perfumes)): ?>
                        <div class="perfumes-grid">
                            <?php foreach ($perfumer_perfumes as $perfume): ?>
                                <div class="perfume-card">
                                    <a href="<?php echo get_permalink($perfume->ID); ?>" class="perfume-link">
                                        <div class="perfume-image">
                                            <?php 
                                            $image = get_the_post_thumbnail($perfume->ID, 'medium');
                                            if ($image) {
                                                echo $image;
                                            } else {
                                                echo '<div class="no-image"><span class="dashicons dashicons-format-image"></span></div>';
                                            }
                                            ?>
                                        </div>
                                        
                                        <div class="perfume-info">
                                            <h3 class="perfume-title"><?php echo esc_html($perfume->post_title); ?></h3>
                                            
                                            <?php 
                                            // Получаваме марката
                                            $brands = wp_get_post_terms($perfume->ID, 'marki');
                                            if (!empty($brands) && !is_wp_error($brands)): 
                                            ?>
                                                <div class="perfume-brand"><?php echo esc_html($brands[0]->name); ?></div>
                                            <?php endif; ?>
                                            
                                            <?php 
                                            // Получаваме рейтинга
                                            $rating = get_post_meta($perfume->ID, '_parfume_rating', true);
                                            if (!empty($rating)): 
                                            ?>
                                                <div class="perfume-rating">
                                                    <?php 
                                                    for ($i = 1; $i <= 5; $i++) {
                                                        if ($i <= $rating) {
                                                            echo '<span class="star filled">★</span>';
                                                        } elseif ($i - 0.5 <= $rating) {
                                                            echo '<span class="star half">★</span>';
                                                        } else {
                                                            echo '<span class="star">☆</span>';
                                                        }
                                                    }
                                                    ?>
                                                    <span class="rating-number"><?php echo esc_html($rating); ?></span>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="no-perfumes">
                            <p><?php _e('Все още няма добавени парфюми за този парфюмерист.', 'parfume-reviews'); ?></p>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Awards Section -->
                <?php if (!empty($awards)): ?>
                    <div class="awards-section">
                        <h2><?php _e('Награди и признания', 'parfume-reviews'); ?></h2>
                        
                        <div class="awards-list">
                            <?php 
                            $awards_list = explode("\n", $awards);
                            foreach ($awards_list as $award): 
                                $award = trim($award);
                                if (!empty($award)):
                            ?>
                                <div class="award-item">
                                    <span class="dashicons dashicons-awards"></span>
                                    <span class="award-name"><?php echo esc_html($award); ?></span>
                                </div>
                            <?php 
                                endif;
                            endforeach; 
                            ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            
        </div>
    </div>
    
    <?php
} else {
    // Archive page - показваме всички парфюмеристи
    ?>
    <div class="perfumers-archive-page perfumer-taxonomy-page">
        <div class="container">
            
            <!-- Breadcrumb navigation -->
            <nav class="breadcrumb">
                <a href="<?php echo home_url(); ?>"><?php _e('Начало', 'parfume-reviews'); ?></a>
                <span class="separator"> › </span>
                <a href="<?php echo home_url('/parfiumi/'); ?>"><?php _e('Парфюми', 'parfume-reviews'); ?></a>
                <span class="separator"> › </span>
                <span class="current"><?php _e('Парфюмеристи', 'parfume-reviews'); ?></span>
            </nav>

            <!-- Archive Header -->
            <div class="archive-header">
                <h1 class="archive-title"><?php _e('Всички Парфюмеристи', 'parfume-reviews'); ?></h1>
                <div class="archive-description">
                    <p><?php _e('Открийте парфюми по техните създатели. Всеки парфюмерист носи своя уникална визия и стил.', 'parfume-reviews'); ?></p>
                </div>
            </div>

            <!-- Perfumers Grid -->
            <div class="perfumers-grid">
                <?php
                $perfumers = get_terms(array(
                    'taxonomy' => 'perfumer',
                    'hide_empty' => false,
                    'orderby' => 'name',
                    'order' => 'ASC'
                ));

                if (!empty($perfumers) && !is_wp_error($perfumers)):
                    foreach ($perfumers as $perfumer):
                        $perfumer_link = get_term_link($perfumer);
                        $perfumer_image_id = get_term_meta($perfumer->term_id, 'perfumer-image-id', true);
                        $perfumer_count = $perfumer->count;
                        ?>
                        <div class="perfumer-card">
                            <a href="<?php echo esc_url($perfumer_link); ?>" class="perfumer-card-link">
                                <div class="perfumer-card-image">
                                    <?php if ($perfumer_image_id): ?>
                                        <?php echo wp_get_attachment_image($perfumer_image_id, 'medium', false, array('class' => 'perfumer-avatar')); ?>
                                    <?php else: ?>
                                        <div class="perfumer-avatar">
                                            <span class="perfumer-initials">
                                                <?php
                                                $name_parts = explode(' ', $perfumer->name);
                                                $initials = '';
                                                foreach ($name_parts as $part) {
                                                    if (!empty($part)) {
                                                        $initials .= mb_substr($part, 0, 1);
                                                        if (strlen($initials) >= 2) break;
                                                    }
                                                }
                                                echo esc_html($initials);
                                                ?>
                                            </span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="perfumer-card-info">
                                    <h3 class="perfumer-card-name"><?php echo esc_html($perfumer->name); ?></h3>
                                    <div class="perfumer-card-count">
                                        <?php printf(_n('%d парфюм', '%d парфюми', $perfumer_count, 'parfume-reviews'), $perfumer_count); ?>
                                    </div>
                                    
                                    <?php if (!empty($perfumer->description)): ?>
                                        <div class="perfumer-card-description">
                                            <?php echo wp_trim_words($perfumer->description, 15, '...'); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </a>
                        </div>
                        <?php
                    endforeach;
                else:
                    ?>
                    <div class="no-perfumers">
                        <p><?php _e('Все още няма добавени парфюмеристи.', 'parfume-reviews'); ?></p>
                    </div>
                    <?php
                endif;
                ?>
            </div>
        </div>
    </div>
    <?php
}

get_footer();
?>