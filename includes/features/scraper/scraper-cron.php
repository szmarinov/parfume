<?php
/**
 * Scraper Cron
 * 
 * WP Cron integration for automated scraping
 * 
 * @package Parfume_Reviews
 * @subpackage Features\Scraper
 * @since 2.0.0
 */

namespace ParfumeReviews\Features\Scraper;

use ParfumeReviews\Features\Stores\StoreRepository;
use ParfumeReviews\Features\Stores\StoreManager;

/**
 * ScraperCron Class
 * 
 * Handles automated scraping via WP Cron
 */
class ScraperCron {
    
    /**
     * Cron hook name
     * 
     * @var string
     */
    const CRON_HOOK = 'parfume_reviews_scraper_cron';
    
    /**
     * Store repository
     * 
     * @var StoreRepository
     */
    private $store_repo;
    
    /**
     * Store manager
     * 
     * @var StoreManager
     */
    private $store_manager;
    
    /**
     * Scraper instance
     * 
     * @var Scraper
     */
    private $scraper;
    
    /**
     * Constructor
     * 
     * @param StoreRepository $store_repo Store repository
     * @param StoreManager $store_manager Store manager
     * @param Scraper $scraper Scraper instance
     */
    public function __construct(StoreRepository $store_repo, StoreManager $store_manager, Scraper $scraper) {
        $this->store_repo = $store_repo;
        $this->store_manager = $store_manager;
        $this->scraper = $scraper;
        
        // Register cron hooks
        add_action(self::CRON_HOOK, [$this, 'run_batch_scrape']);
        
        // Schedule cron on init
        add_action('init', [$this, 'schedule_cron']);
        
        // Clear cron on plugin deactivation
        register_deactivation_hook(PARFUME_REVIEWS_FILE, [$this, 'clear_cron']);
    }
    
    /**
     * Schedule WP Cron
     */
    public function schedule_cron() {
        $settings = get_option('parfume_reviews_settings', []);
        $enabled = isset($settings['enable_scraper']) && $settings['enable_scraper'];
        
        if (!$enabled) {
            $this->clear_cron();
            return;
        }
        
        if (!wp_next_scheduled(self::CRON_HOOK)) {
            // Get interval from settings
            $interval_hours = isset($settings['scrape_interval']) ? absint($settings['scrape_interval']) : 12;
            
            // Schedule with custom interval
            wp_schedule_event(
                time(),
                'parfume_scraper_interval',
                self::CRON_HOOK
            );
            
            // Log scheduling
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Parfume Reviews: Scraper cron scheduled');
            }
        }
    }
    
    /**
     * Clear scheduled cron
     */
    public function clear_cron() {
        $timestamp = wp_next_scheduled(self::CRON_HOOK);
        if ($timestamp) {
            wp_unschedule_event($timestamp, self::CRON_HOOK);
            
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Parfume Reviews: Scraper cron cleared');
            }
        }
    }
    
    /**
     * Add custom cron interval
     * 
     * @param array $schedules Existing schedules
     * @return array Modified schedules
     */
    public function add_cron_interval($schedules) {
        $settings = get_option('parfume_reviews_settings', []);
        $interval_hours = isset($settings['scrape_interval']) ? absint($settings['scrape_interval']) : 12;
        
        $schedules['parfume_scraper_interval'] = [
            'interval' => $interval_hours * HOUR_IN_SECONDS,
            'display' => sprintf(
                __('На всеки %d часа (Parfume Scraper)', 'parfume-reviews'),
                $interval_hours
            )
        ];
        
        return $schedules;
    }
    
    /**
     * Run batch scraping
     */
    public function run_batch_scrape() {
        // Check if scraper is enabled
        $settings = get_option('parfume_reviews_settings', []);
        $enabled = isset($settings['enable_scraper']) && $settings['enable_scraper'];
        
        if (!$enabled) {
            return;
        }
        
        // Get batch size
        $batch_size = isset($settings['scrape_batch_size']) ? absint($settings['scrape_batch_size']) : 10;
        
        // Get stores that need scraping
        $stores_to_scrape = $this->store_repo->get_stores_for_scraping($batch_size);
        
        if (empty($stores_to_scrape)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Parfume Reviews: No stores to scrape');
            }
            return;
        }
        
        // Log batch start
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Parfume Reviews: Starting batch scrape - ' . count($stores_to_scrape) . ' stores');
        }
        
        $success_count = 0;
        $error_count = 0;
        
        // Process each store
        foreach ($stores_to_scrape as $item) {
            try {
                $result = $this->scrape_store_item($item);
                
                if ($result) {
                    $success_count++;
                } else {
                    $error_count++;
                }
                
                // Small delay to avoid overwhelming servers
                usleep(500000); // 0.5 seconds
                
            } catch (\Exception $e) {
                $error_count++;
                
                // Log error
                ScraperMonitor::log_error(
                    $item['post_id'],
                    $item['store_index'],
                    $e->getMessage()
                );
                
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('Parfume Reviews: Scrape error - ' . $e->getMessage());
                }
            }
        }
        
        // Log batch completion
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log(sprintf(
                'Parfume Reviews: Batch scrape completed - Success: %d, Errors: %d',
                $success_count,
                $error_count
            ));
        }
        
        // Update last run time
        update_option('parfume_scraper_last_run', current_time('mysql'));
    }
    
    /**
     * Scrape individual store item
     * 
     * @param array $item Store item data
     * @return bool Success
     */
    private function scrape_store_item($item) {
        $post_id = $item['post_id'];
        $store_index = $item['store_index'];
        $product_url = $item['product_url'];
        $store_id = $item['store_id'];
        
        // Get store schema
        $schema = $this->store_manager->get_store_schema($store_id);
        
        if (empty($schema)) {
            ScraperMonitor::log_error(
                $post_id,
                $store_index,
                __('Липсва schema за този магазин', 'parfume-reviews')
            );
            return false;
        }
        
        try {
            // Perform scraping
            $scraped_data = $this->scraper->scrape_url($product_url);
            
            if (empty($scraped_data)) {
                throw new \Exception(__('Няма извлечени данни', 'parfume-reviews'));
            }
            
            // Update scraped data in repository
            $this->store_repo->update_scraped_data(
                $post_id,
                $store_index,
                $scraped_data,
                'success'
            );
            
            return true;
            
        } catch (\Exception $e) {
            // Update with error status
            $this->store_repo->update_scraped_data(
                $post_id,
                $store_index,
                [],
                'error'
            );
            
            throw $e;
        }
    }
    
    /**
     * Get next scheduled run
     * 
     * @return string|null Datetime string
     */
    public function get_next_run() {
        $timestamp = wp_next_scheduled(self::CRON_HOOK);
        
        if (!$timestamp) {
            return null;
        }
        
        return date('Y-m-d H:i:s', $timestamp);
    }
    
    /**
     * Get last run
     * 
     * @return string|null Datetime string
     */
    public function get_last_run() {
        return get_option('parfume_scraper_last_run', null);
    }
    
    /**
     * Force run now (manual trigger)
     * 
     * @return array Result
     */
    public function force_run() {
        // Check permissions
        if (!current_user_can('manage_options')) {
            return [
                'success' => false,
                'message' => __('Нямате права за тази операция', 'parfume-reviews')
            ];
        }
        
        // Run batch
        $this->run_batch_scrape();
        
        return [
            'success' => true,
            'message' => __('Batch скрейпването е завършено', 'parfume-reviews'),
            'last_run' => $this->get_last_run()
        ];
    }
    
    /**
     * Get cron status
     * 
     * @return array Status info
     */
    public function get_status() {
        $settings = get_option('parfume_reviews_settings', []);
        $enabled = isset($settings['enable_scraper']) && $settings['enable_scraper'];
        
        return [
            'enabled' => $enabled,
            'scheduled' => (bool) wp_next_scheduled(self::CRON_HOOK),
            'next_run' => $this->get_next_run(),
            'last_run' => $this->get_last_run(),
            'interval_hours' => isset($settings['scrape_interval']) ? absint($settings['scrape_interval']) : 12,
            'batch_size' => isset($settings['scrape_batch_size']) ? absint($settings['scrape_batch_size']) : 10
        ];
    }
}

// Register custom cron interval
add_filter('cron_schedules', function($schedules) {
    $settings = get_option('parfume_reviews_settings', []);
    $interval_hours = isset($settings['scrape_interval']) ? absint($settings['scrape_interval']) : 12;
    
    $schedules['parfume_scraper_interval'] = [
        'interval' => $interval_hours * HOUR_IN_SECONDS,
        'display' => sprintf(
            __('На всеки %d часа (Parfume Scraper)', 'parfume-reviews'),
            $interval_hours
        )
    ];
    
    return $schedules;
});