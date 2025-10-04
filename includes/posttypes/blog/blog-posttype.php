<?php
/**
 * Blog Post Type
 * Custom post type for blog articles
 */

if (!defined('ABSPATH')) {
    exit;
}

class Parfume_Blog_PostType {
    
    public function __construct() {
        add_action('init', [$this, 'register_post_type']);
        add_action('admin_menu', [$this, 'add_menu_items']);
    }
    
    /**
     * Register Blog Post Type
     */
    public function register_post_type() {
        $labels = [
            'name' => 'Blog',
            'singular_name' => 'Blog Post',
            'menu_name' => 'Blog',
            'add_new' => 'Добави нов',
            'add_new_item' => 'Добави нов Blog Post',
            'edit_item' => 'Редактирай Blog Post',
            'new_item' => 'Нов Blog Post',
            'view_item' => 'Виж Blog Post',
            'view_items' => 'Виж Blog Posts',
            'search_items' => 'Търси Blog Posts',
            'not_found' => 'Няма намерени blog posts',
            'not_found_in_trash' => 'Няма намерени blog posts в кошчето',
            'all_items' => 'Всички Blog Posts',
            'archives' => 'Blog Архив',
            'attributes' => 'Blog Атрибути',
            'insert_into_item' => 'Вмъкни в blog post',
            'uploaded_to_this_item' => 'Качено към този blog post'
        ];
        
        $args = [
            'labels' => $labels,
            'public' => true,
            'publicly_queryable' => true,
            'show_ui' => true,
            'show_in_menu' => false, // We'll add it manually under Parfumes menu
            'show_in_nav_menus' => true,
            'show_in_admin_bar' => true,
            'show_in_rest' => true,
            'capability_type' => 'post',
            'hierarchical' => false,
            'menu_position' => 6,
            'menu_icon' => 'dashicons-edit-large',
            'supports' => [
                'title',
                'editor',
                'author',
                'thumbnail',
                'excerpt',
                'comments',
                'revisions',
                'custom-fields'
            ],
            'has_archive' => true,
            'rewrite' => [
                'slug' => 'blog',
                'with_front' => false
            ],
            'query_var' => true,
            'can_export' => true,
            'delete_with_user' => false
        ];
        
        register_post_type('parfume_blog', $args);
    }
    
    /**
     * Add menu items under Parfumes menu
     */
    public function add_menu_items() {
        // Add "Blog" link
        add_submenu_page(
            'edit.php?post_type=parfume',
            'Blog',
            'Blog',
            'edit_posts',
            'edit.php?post_type=parfume_blog'
        );
        
        // Add "Add Blog" link
        add_submenu_page(
            'edit.php?post_type=parfume',
            'Добави Blog Post',
            'Добави Blog',
            'edit_posts',
            'post-new.php?post_type=parfume_blog'
        );
    }
}

// Initialize
new Parfume_Blog_PostType();