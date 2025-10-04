<?php
/**
 * Scraper Logger
 * 
 * Detailed logging system for scraper operations
 * 
 * @package Parfume_Reviews
 * @subpackage Features\Scraper
 * @since 2.0.0
 */

namespace Parfume_Reviews\Features\Scraper;

/**
 * Logger Class
 * 
 * Manages scraper logging with detailed diagnostics
 */
class Logger {
    
    /**
     * Log levels
     */
    const LEVEL_INFO = 'info';
    const LEVEL_SUCCESS = 'success';
    const LEVEL_WARNING = 'warning';
    const LEVEL_ERROR = 'error';
    const LEVEL_DEBUG = 'debug';
    
    /**
     * Log file path
     * 
     * @var string
     */
    private $log_file;
    
    /**
     * Enable logging
     * 
     * @var bool
     */
    private $enabled = true;
    
    /**
     * Enable debug mode
     * 
     * @var bool
     */
    private $debug_mode = false;
    
    /**
     * Constructor
     */
    public function __construct() {
        $upload_dir = wp_upload_dir();
        $log_dir = $upload_dir['basedir'] . '/parfume-reviews-logs';
        
        // Create log directory if not exists
        if (!file_exists($log_dir)) {
            wp_mkdir_p($log_dir);
            
            // Add .htaccess to protect logs
            file_put_contents(
                $log_dir . '/.htaccess',
                "Order deny,allow\nDeny from all"
            );
        }
        
        $this->log_file = $log_dir . '/scraper-' . date('Y-m-d') . '.log';
        
        // Get settings
        $settings = get_option('parfume_reviews_settings', []);
        $this->enabled = isset($settings['enable_scraper_logging']) ? (bool) $settings['enable_scraper_logging'] : true;
        $this->debug_mode = isset($settings['debug_mode']) ? (bool) $settings['debug_mode'] : false;
    }
    
    /**
     * Log message
     * 
     * @param string $message Log message
     * @param string $level Log level
     * @param array $context Additional context
     */
    public function log($message, $level = self::LEVEL_INFO, $context = []) {
        if (!$this->enabled) {
            return;
        }
        
        // Skip debug messages if debug mode is off
        if ($level === self::LEVEL_DEBUG && !$this->debug_mode) {
            return;
        }
        
        $timestamp = current_time('Y-m-d H:i:s');
        $level_upper = strtoupper($level);
        
        $log_entry = sprintf(
            "[%s] [%s] %s",
            $timestamp,
            $level_upper,
            $message
        );
        
        // Add context if available
        if (!empty($context)) {
            $log_entry .= ' | Context: ' . wp_json_encode($context, JSON_UNESCAPED_UNICODE);
        }
        
        $log_entry .= PHP_EOL;
        
        // Write to file
        file_put_contents($this->log_file, $log_entry, FILE_APPEND);
        
        // Also log to WP debug log if enabled
        if (defined('WP_DEBUG') && WP_DEBUG && defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
            error_log('Parfume Reviews Scraper: ' . $message);
        }
    }
    
    /**
     * Log info message
     * 
     * @param string $message Message
     * @param array $context Context
     */
    public function info($message, $context = []) {
        $this->log($message, self::LEVEL_INFO, $context);
    }
    
    /**
     * Log success message
     * 
     * @param string $message Message
     * @param array $context Context
     */
    public function success($message, $context = []) {
        $this->log($message, self::LEVEL_SUCCESS, $context);
    }
    
    /**
     * Log warning message
     * 
     * @param string $message Message
     * @param array $context Context
     */
    public function warning($message, $context = []) {
        $this->log($message, self::LEVEL_WARNING, $context);
    }
    
    /**
     * Log error message
     * 
     * @param string $message Message
     * @param array $context Context
     */
    public function error($message, $context = []) {
        $this->log($message, self::LEVEL_ERROR, $context);
    }
    
    /**
     * Log debug message
     * 
     * @param string $message Message
     * @param array $context Context
     */
    public function debug($message, $context = []) {
        $this->log($message, self::LEVEL_DEBUG, $context);
    }
    
    /**
     * Log scrape attempt
     * 
     * @param string $url URL being scraped
     * @param string $store_name Store name
     */
    public function log_scrape_start($url, $store_name) {
        $this->info("Starting scrape", [
            'store' => $store_name,
            'url' => $url
        ]);
    }
    
    /**
     * Log scrape success
     * 
     * @param string $url URL scraped
     * @param string $store_name Store name
     * @param array $data Scraped data
     */
    public function log_scrape_success($url, $store_name, $data) {
        $this->success("Scrape successful", [
            'store' => $store_name,
            'url' => $url,
            'price' => isset($data['price']) ? $data['price'] : 'N/A',
            'in_stock' => isset($data['in_stock']) ? ($data['in_stock'] ? 'Yes' : 'No') : 'N/A',
            'name' => isset($data['name']) ? substr($data['name'], 0, 50) : 'N/A'
        ]);
    }
    
    /**
     * Log scrape failure
     * 
     * @param string $url URL attempted
     * @param string $store_name Store name
     * @param string $error Error message
     */
    public function log_scrape_failure($url, $store_name, $error) {
        $this->error("Scrape failed", [
            'store' => $store_name,
            'url' => $url,
            'error' => $error
        ]);
    }
    
    /**
     * Log missing data
     * 
     * @param string $url URL
     * @param string $store_name Store name
     * @param array $missing Missing fields
     */
    public function log_missing_data($url, $store_name, $missing) {
        $this->warning("Missing data fields", [
            'store' => $store_name,
            'url' => $url,
            'missing_fields' => implode(', ', $missing)
        ]);
    }
    
    /**
     * Log HTTP request details
     * 
     * @param string $url URL
     * @param int $response_code Response code
     * @param float $time_taken Time in seconds
     */
    public function log_http_request($url, $response_code, $time_taken) {
        $this->debug("HTTP Request", [
            'url' => $url,
            'response_code' => $response_code,
            'time' => round($time_taken, 2) . 's'
        ]);
    }
    
    /**
     * Get recent logs
     * 
     * @param int $lines Number of lines to retrieve
     * @return array Log lines
     */
    public function get_recent_logs($lines = 100) {
        if (!file_exists($this->log_file)) {
            return [];
        }
        
        $file = file($this->log_file);
        return array_slice($file, -$lines);
    }
    
    /**
     * Clear logs
     * 
     * @param int $days Delete logs older than X days (0 = all)
     */
    public function clear_logs($days = 0) {
        $upload_dir = wp_upload_dir();
        $log_dir = $upload_dir['basedir'] . '/parfume-reviews-logs';
        
        if (!file_exists($log_dir)) {
            return;
        }
        
        $files = glob($log_dir . '/scraper-*.log');
        
        foreach ($files as $file) {
            if ($days === 0) {
                // Delete all
                unlink($file);
            } else {
                // Delete old files
                $file_time = filemtime($file);
                $days_old = (time() - $file_time) / (60 * 60 * 24);
                
                if ($days_old > $days) {
                    unlink($file);
                }
            }
        }
        
        $this->info("Logs cleared", ['days' => $days]);
    }
    
    /**
     * Get log file path
     * 
     * @return string
     */
    public function get_log_file() {
        return $this->log_file;
    }
    
    /**
     * Get log statistics
     * 
     * @return array Statistics
     */
    public function get_stats() {
        if (!file_exists($this->log_file)) {
            return [
                'total' => 0,
                'success' => 0,
                'errors' => 0,
                'warnings' => 0
            ];
        }
        
        $content = file_get_contents($this->log_file);
        
        return [
            'total' => substr_count($content, '[INFO]') + 
                      substr_count($content, '[SUCCESS]') + 
                      substr_count($content, '[ERROR]') + 
                      substr_count($content, '[WARNING]'),
            'success' => substr_count($content, '[SUCCESS]'),
            'errors' => substr_count($content, '[ERROR]'),
            'warnings' => substr_count($content, '[WARNING]')
        ];
    }
}