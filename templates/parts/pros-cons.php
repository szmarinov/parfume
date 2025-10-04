<?php
/**
 * Template Part: Pros and Cons
 * Displays advantages and disadvantages of the perfume
 */

if (!defined('ABSPATH')) {
    exit;
}

global $post;

// Get pros and cons
$pros = get_post_meta($post->ID, '_parfume_pros', true);
$cons = get_post_meta($post->ID, '_parfume_cons', true);

// Check if we have any data
if (empty($pros) && empty($cons)) {
    return;
}

// Convert to arrays if strings
$pros_array = is_array($pros) ? $pros : array_filter(explode("\n", $pros));
$cons_array = is_array($cons) ? $cons : array_filter(explode("\n", $cons));
?>

<section class="parfume-pros-cons">
    <h2 class="section-title">Предимства и недостатъци</h2>
    
    <div class="pros-cons-grid">
        
        <?php if (!empty($pros_array)) : ?>
        <div class="pros-column">
            <div class="column-header pros-header">
                <svg class="header-icon" width="24" height="24" viewBox="0 0 24 24" fill="none">
                    <path d="M9 12L11 14L15 10M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <h3>Предимства</h3>
            </div>
            <ul class="items-list pros-list">
                <?php foreach ($pros_array as $pro) : 
                    $pro = trim($pro);
                    if (!empty($pro)) :
                ?>
                    <li class="item pros-item">
                        <svg class="item-icon" width="20" height="20" viewBox="0 0 20 20" fill="none">
                            <path d="M7 10L9 12L13 8M19 10C19 14.9706 14.9706 19 10 19C5.02944 19 1 14.9706 1 10C1 5.02944 5.02944 1 10 1C14.9706 1 19 5.02944 19 10Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <span><?php echo esc_html($pro); ?></span>
                    </li>
                <?php 
                    endif;
                endforeach; 
                ?>
            </ul>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($cons_array)) : ?>
        <div class="cons-column">
            <div class="column-header cons-header">
                <svg class="header-icon" width="24" height="24" viewBox="0 0 24 24" fill="none">
                    <path d="M10 14L12 12M12 12L14 10M12 12L10 10M12 12L14 14M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <h3>Недостатъци</h3>
            </div>
            <ul class="items-list cons-list">
                <?php foreach ($cons_array as $con) : 
                    $con = trim($con);
                    if (!empty($con)) :
                ?>
                    <li class="item cons-item">
                        <svg class="item-icon" width="20" height="20" viewBox="0 0 20 20" fill="none">
                            <path d="M8 12L10 10M10 10L12 8M10 10L8 8M10 10L12 12M19 10C19 14.9706 14.9706 19 10 19C5.02944 19 1 14.9706 1 10C1 5.02944 5.02944 1 10 1C14.9706 1 19 5.02944 19 10Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <span><?php echo esc_html($con); ?></span>
                    </li>
                <?php 
                    endif;
                endforeach; 
                ?>
            </ul>
        </div>
        <?php endif; ?>
        
    </div>
</section>

<style>
.parfume-pros-cons {
    margin: 40px 0;
    padding: 30px;
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.section-title {
    font-size: 28px;
    margin-bottom: 30px;
    text-align: center;
    color: #333;
}

.pros-cons-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 30px;
}

.pros-column,
.cons-column {
    background: #f8f9fa;
    border-radius: 8px;
    overflow: hidden;
}

.column-header {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 20px;
    color: #fff;
    font-weight: 600;
}

.pros-header {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
}

.cons-header {
    background: linear-gradient(135deg, #dc3545 0%, #fd7e14 100%);
}

.header-icon {
    width: 24px;
    height: 24px;
}

.column-header h3 {
    margin: 0;
    font-size: 20px;
}

.items-list {
    list-style: none;
    margin: 0;
    padding: 20px;
}

.item {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 12px;
    margin-bottom: 10px;
    background: #fff;
    border-radius: 6px;
    transition: all 0.3s ease;
}

.item:last-child {
    margin-bottom: 0;
}

.item:hover {
    transform: translateX(5px);
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.pros-item {
    border-left: 3px solid #28a745;
}

.cons-item {
    border-left: 3px solid #dc3545;
}

.item-icon {
    flex-shrink: 0;
    margin-top: 2px;
}

.pros-item .item-icon {
    color: #28a745;
}

.cons-item .item-icon {
    color: #dc3545;
}

.item span {
    flex: 1;
    line-height: 1.6;
    color: #333;
}

/* Responsive */
@media (max-width: 768px) {
    .parfume-pros-cons {
        padding: 20px 15px;
    }
    
    .section-title {
        font-size: 24px;
        margin-bottom: 20px;
    }
    
    .pros-cons-grid {
        grid-template-columns: 1fr;
        gap: 20px;
    }
    
    .column-header {
        padding: 15px;
    }
    
    .column-header h3 {
        font-size: 18px;
    }
    
    .items-list {
        padding: 15px;
    }
    
    .item {
        padding: 10px;
    }
}
</style>