<?php
/**
 * Деинсталация на Parfume Catalog плъгин
 * 
 * @package Parfume_Catalog
 */

// Предотвратяване на директен достъп
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

/**
 * Изтриване на всички данни на плъгина при деинсталация
 */
class Parfume_Catalog_Uninstaller {
    
    /**
     * Стартиране на процеса на деинсталация
     */
    public static function uninstall() {
        // Проверка за права на администратор
        if (!current_user_can('activate_plugins')) {
            return;
        }
        
        // Получаване на настройката дали да се изтрият данните
        $delete_data = get_option('parfume_catalog_delete_data_on_uninstall', false);
        
        if ($delete_data) {
            self::delete_custom_posts();
            self::delete_custom_taxonomies();
            self::delete_custom_tables();
            self::delete_options();
            self::delete_user_meta();
            self::delete_uploaded_files();
            self::flush_rewrite_rules();
        }
    }
    
    /**
     * Изтриване на всички парфюми и блог постове
     */
    private static function delete_custom_posts() {
        global $wpdb;
        
        // Парфюми
        $parfume_posts = get_posts(array(
            'post_type' => 'parfumes',
            'numberposts' => -1,
            'post_status' => 'any'
        ));
        
        foreach ($parfume_posts as $post) {
            wp_delete_post($post->ID, true);
        }
        
        // Блог постове
        $blog_posts = get_posts(array(
            'post_type' => 'parfume_blog',
            'numberposts' => -1,
            'post_status' => 'any'
        ));
        
        foreach ($blog_posts as $post) {
            wp_delete_post($post->ID, true);
        }
        
        // Изтриване на мета данни
        $wpdb->query(
            "DELETE FROM {$wpdb->postmeta} 
             WHERE post_id NOT IN (SELECT ID FROM {$wpdb->posts})"
        );
    }
    
    /**
     * Изтриване на custom таксономии и техните термини
     */
    private static function delete_custom_taxonomies() {
        $taxonomies = array(
            'tip-parfum',
            'vid-aromat', 
            'marki',
            'sezon',
            'intenzivnost',
            'notki'
        );
        
        foreach ($taxonomies as $taxonomy) {
            $terms = get_terms(array(
                'taxonomy' => $taxonomy,
                'hide_empty' => false
            ));
            
            if (!is_wp_error($terms)) {
                foreach ($terms as $term) {
                    wp_delete_term($term->term_id, $taxonomy);
                }
            }
        }
    }
    
    /**
     * Изтриване на custom таблици
     */
    private static function delete_custom_tables() {
        global $wpdb;
        
        // Таблица за коментари
        $comments_table = $wpdb->prefix . 'parfume_comments';
        $wpdb->query("DROP TABLE IF EXISTS {$comments_table}");
        
        // Таблица за scraper данни
        $scraper_table = $wpdb->prefix . 'parfume_scraper_data';
        $wpdb->query("DROP TABLE IF EXISTS {$scraper_table}");
        
        // Таблица за scraper логове
        $scraper_logs_table = $wpdb->prefix . 'parfume_scraper_logs';
        $wpdb->query("DROP TABLE IF EXISTS {$scraper_logs_table}");
        
        // Таблица за store schemas
        $schemas_table = $wpdb->prefix . 'parfume_store_schemas';
        $wpdb->query("DROP TABLE IF EXISTS {$schemas_table}");
    }
    
    /**
     * Изтриване на всички опции на плъгина
     */
    private static function delete_options() {
        // Основни настройки
        delete_option('parfume_catalog_version');
        delete_option('parfume_catalog_settings');
        delete_option('parfume_catalog_activation_date');
        
        // URL настройки
        delete_option('parfume_catalog_archive_slug');
        delete_option('parfume_catalog_tip_slug');
        delete_option('parfume_catalog_vid_slug');
        delete_option('parfume_catalog_marki_slug');
        delete_option('parfume_catalog_sezon_slug');
        delete_option('parfume_catalog_blog_slug');
        
        // Scraper настройки
        delete_option('parfume_catalog_scrape_interval');
        delete_option('parfume_catalog_scrape_batch_size');
        delete_option('parfume_catalog_scrape_user_agent');
        delete_option('parfume_catalog_scrape_timeout');
        delete_option('parfume_catalog_scrape_last_run');
        delete_option('parfume_catalog_scrape_pointer');
        
        // Comments настройки
        delete_option('parfume_catalog_comments_moderation');
        delete_option('parfume_catalog_comments_captcha');
        delete_option('parfume_catalog_comments_blocked_words');
        delete_option('parfume_catalog_comments_max_per_ip');
        delete_option('parfume_catalog_comments_notification_email');
        
        // Comparison настройки
        delete_option('parfume_catalog_comparison_enabled');
        delete_option('parfume_catalog_comparison_max_items');
        delete_option('parfume_catalog_comparison_criteria');
        delete_option('parfume_catalog_comparison_popup_style');
        
        // Stores настройки
        delete_option('parfume_catalog_stores');
        delete_option('parfume_catalog_mobile_fixed_panel');
        delete_option('parfume_catalog_mobile_show_x_button');
        delete_option('parfume_catalog_mobile_z_index');
        delete_option('parfume_catalog_mobile_offset');
        
        // Filters настройки
        delete_option('parfume_catalog_filters_ajax');
        delete_option('parfume_catalog_filters_show_counts');
        delete_option('parfume_catalog_filters_autocomplete');
        
        // SEO настройки
        delete_option('parfume_catalog_seo_schema');
        delete_option('parfume_catalog_seo_og_tags');
        delete_option('parfume_catalog_seo_twitter_cards');
        
        // Similar parfumes настройки
        delete_option('parfume_catalog_similar_count');
        delete_option('parfume_catalog_similar_columns');
        delete_option('parfume_catalog_recently_viewed_count');
        delete_option('parfume_catalog_same_brand_count');
        
        // Временни опции
        delete_option('parfume_catalog_flush_rewrite_rules');
        delete_option('parfume_catalog_blog_flush_rewrite');
        delete_option('parfume_catalog_delete_data_on_uninstall');
        
        // Изтриване на всички опции с префикс
        global $wpdb;
        $wpdb->query(
            "DELETE FROM {$wpdb->options} 
             WHERE option_name LIKE 'parfume_catalog_%'"
        );
    }
    
    /**
     * Изтриване на user meta данни
     */
    private static function delete_user_meta() {
        global $wpdb;
        
        $wpdb->query(
            "DELETE FROM {$wpdb->usermeta} 
             WHERE meta_key LIKE 'parfume_catalog_%'"
        );
    }
    
    /**
     * Изтриване на качени файлове
     */
    private static function delete_uploaded_files() {
        $upload_dir = wp_upload_dir();
        $parfume_dir = $upload_dir['basedir'] . '/parfume-catalog/';
        
        if (is_dir($parfume_dir)) {
            self::delete_directory($parfume_dir);
        }
        
        // Изтриване на логове
        $logs_dir = $upload_dir['basedir'] . '/parfume-logs/';
        if (is_dir($logs_dir)) {
            self::delete_directory($logs_dir);
        }
    }
    
    /**
     * Рекурсивно изтриване на директория
     */
    private static function delete_directory($dir) {
        if (!is_dir($dir)) {
            return false;
        }
        
        $files = array_diff(scandir($dir), array('.', '..'));
        
        foreach ($files as $file) {
            $path = $dir . DIRECTORY_SEPARATOR . $file;
            
            if (is_dir($path)) {
                self::delete_directory($path);
            } else {
                unlink($path);
            }
        }
        
        return rmdir($dir);
    }
    
    /**
     * Почистване на rewrite rules
     */
    private static function flush_rewrite_rules() {
        flush_rewrite_rules();
    }
    
    /**
     * Изтриване на cron job-ове
     */
    private static function clear_scheduled_hooks() {
        // Изтриване на scraper cron
        wp_clear_scheduled_hook('parfume_catalog_scraper_cron');
        
        // Изтриване на cleanup cron
        wp_clear_scheduled_hook('parfume_catalog_cleanup_cron');
        
        // Изтриване на всички други scheduled events
        $cron_array = _get_cron_array();
        
        if (is_array($cron_array)) {
            foreach ($cron_array as $timestamp => $hooks) {
                foreach ($hooks as $hook => $events) {
                    if (strpos($hook, 'parfume_catalog_') === 0) {
                        foreach ($events as $event) {
                            wp_unschedule_event($timestamp, $hook, $event['args']);
                        }
                    }
                }
            }
        }
    }
    
    /**
     * Почистване на transients
     */
    private static function delete_transients() {
        global $wpdb;
        
        $wpdb->query(
            "DELETE FROM {$wpdb->options} 
             WHERE option_name LIKE '_transient_parfume_catalog_%' 
             OR option_name LIKE '_transient_timeout_parfume_catalog_%'"
        );
    }
    
    /**
     * Логиране на деинсталацията
     */
    private static function log_uninstall() {
        $log_data = array(
            'timestamp' => current_time('mysql'),
            'user_id' => get_current_user_id(),
            'user_email' => wp_get_current_user()->user_email,
            'site_url' => site_url(),
            'plugin_version' => get_option('parfume_catalog_version', '1.0.0')
        );
        
        // Опционално изпращане на статистика за деинсталация
        $send_stats = get_option('parfume_catalog_send_deactivation_stats', false);
        
        if ($send_stats) {
            wp_remote_post('https://yoursite.com/api/plugin-deactivation', array(
                'body' => $log_data,
                'timeout' => 10
            ));
        }
    }
}

// Стартиране на деинсталацията
Parfume_Catalog_Uninstaller::uninstall();