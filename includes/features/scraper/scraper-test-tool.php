<?php
/**
 * Scraper Test Tool
 * 
 * Interactive tool for testing and configuring scraping schemas
 * 
 * @package Parfume_Reviews
 * @subpackage Features\Scraper
 * @since 2.0.0
 */

namespace ParfumeReviews\Features\Scraper;

use ParfumeReviews\Features\Stores\StoreManager;
use ParfumeReviews\Features\Stores\StoreSchema;

/**
 * ScraperTestTool Class
 * 
 * Provides UI for testing selectors and creating schemas
 */
class ScraperTestTool {
    
    /**
     * Store manager
     * 
     * @var StoreManager
     */
    private $store_manager;
    
    /**
     * Store schema
     * 
     * @var StoreSchema
     */
    private $store_schema;
    
    /**
     * Scraper instance
     * 
     * @var Scraper
     */
    private $scraper;
    
    /**
     * Constructor
     * 
     * @param StoreManager $store_manager Store manager
     * @param StoreSchema $store_schema Store schema
     * @param Scraper $scraper Scraper instance
     */
    public function __construct(StoreManager $store_manager, StoreSchema $store_schema, Scraper $scraper) {
        $this->store_manager = $store_manager;
        $this->store_schema = $store_schema;
        $this->scraper = $scraper;
        
        // Register AJAX handlers
        add_action('wp_ajax_parfume_test_scrape', [$this, 'ajax_test_scrape']);
        add_action('wp_ajax_parfume_test_selector', [$this, 'ajax_test_selector']);
        add_action('wp_ajax_parfume_save_test_schema', [$this, 'ajax_save_test_schema']);
    }
    
    /**
     * Render test tool page
     */
    public function render_test_tool_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Нямате права за достъп до тази страница', 'parfume-reviews'));
        }
        
        $stores = $this->store_manager->get_all_stores();
        
        ?>
        <div class="wrap parfume-scraper-test-tool">
            <h1><?php _e('Scraper Test Tool', 'parfume-reviews'); ?></h1>
            <p class="description">
                <?php _e('Тествайте и конфигурирайте scraping schemas за различни магазини', 'parfume-reviews'); ?>
            </p>
            
            <div class="test-tool-container">
                <!-- Step 1: Enter URL -->
                <div class="test-step" id="step-1">
                    <h2><?php _e('Стъпка 1: Въведете тестов Product URL', 'parfume-reviews'); ?></h2>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="test-store-select"><?php _e('Магазин', 'parfume-reviews'); ?></label>
                            </th>
                            <td>
                                <select id="test-store-select" class="regular-text">
                                    <option value=""><?php _e('-- Изберете магазин --', 'parfume-reviews'); ?></option>
                                    <?php foreach ($stores as $store) : ?>
                                        <option value="<?php echo esc_attr($store['id']); ?>">
                                            <?php echo esc_html($store['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="test-product-url"><?php _e('Product URL', 'parfume-reviews'); ?></label>
                            </th>
                            <td>
                                <input 
                                    type="url" 
                                    id="test-product-url" 
                                    class="large-text" 
                                    placeholder="https://example.com/product/parfum"
                                />
                                <p class="description">
                                    <?php _e('Въведете URL на продукт от избрания магазин', 'parfume-reviews'); ?>
                                </p>
                            </td>
                        </tr>
                    </table>
                    
                    <p class="submit">
                        <button type="button" id="fetch-page-button" class="button button-primary">
                            <?php _e('Скрейпни и анализирай', 'parfume-reviews'); ?>
                        </button>
                    </p>
                </div>
                
                <!-- Step 2: Analysis Results -->
                <div class="test-step" id="step-2" style="display:none;">
                    <h2><?php _e('Стъпка 2: Резултати от анализа', 'parfume-reviews'); ?></h2>
                    
                    <div class="analysis-results">
                        <div class="results-grid">
                            <!-- Auto-detected data -->
                            <div class="result-section">
                                <h3><?php _e('Открити данни', 'parfume-reviews'); ?></h3>
                                <div id="detected-data"></div>
                            </div>
                            
                            <!-- Suggested selectors -->
                            <div class="result-section">
                                <h3><?php _e('Предложени селектори', 'parfume-reviews'); ?></h3>
                                <div id="suggested-selectors"></div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Step 3: Configure Schema -->
                <div class="test-step" id="step-3" style="display:none;">
                    <h2><?php _e('Стъпка 3: Конфигурирайте Schema', 'parfume-reviews'); ?></h2>
                    
                    <div class="schema-configuration">
                        <?php 
                        $default_schema = $this->store_schema->get_default_schema();
                        echo $this->store_schema->render_schema_editor($default_schema); 
                        ?>
                    </div>
                </div>
                
                <!-- Step 4: Test Schema -->
                <div class="test-step" id="step-4" style="display:none;">
                    <h2><?php _e('Стъпка 4: Тествайте Schema', 'parfume-reviews'); ?></h2>
                    
                    <p>
                        <button type="button" id="test-schema-button" class="button button-primary">
                            <?php _e('Тествай с избраните селектори', 'parfume-reviews'); ?>
                        </button>
                    </p>
                    
                    <div class="test-results" id="schema-test-results"></div>
                </div>
                
                <!-- Step 5: Preview & Save -->
                <div class="test-step" id="step-5" style="display:none;">
                    <h2><?php _e('Стъпка 5: Preview и Запазване', 'parfume-reviews'); ?></h2>
                    
                    <div class="schema-preview">
                        <h3><?php _e('Preview на оферта', 'parfume-reviews'); ?></h3>
                        <div id="offer-preview"></div>
                    </div>
                    
                    <p class="submit">
                        <button type="button" id="save-schema-final-button" class="button button-primary button-large">
                            <?php _e('Запази Schema за този магазин', 'parfume-reviews'); ?>
                        </button>
                        <button type="button" id="export-schema-json-button" class="button">
                            <?php _e('Експортирай като JSON', 'parfume-reviews'); ?>
                        </button>
                    </p>
                </div>
            </div>
            
            <!-- Loading Overlay -->
            <div class="test-tool-loading" style="display:none;">
                <div class="loading-spinner"></div>
                <div class="loading-text"><?php _e('Зареждане...', 'parfume-reviews'); ?></div>
            </div>
        </div>
        
        <style>
        .test-tool-container {
            background: #fff;
            padding: 20px;
            margin-top: 20px;
            border: 1px solid #c3c4c7;
            border-radius: 4px;
        }
        
        .test-step {
            margin-bottom: 30px;
        }
        
        .test-step h2 {
            margin-top: 0;
            padding-bottom: 10px;
            border-bottom: 2px solid #2271b1;
        }
        
        .results-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-top: 15px;
        }
        
        .result-section {
            background: #f6f7f7;
            padding: 15px;
            border-radius: 4px;
        }
        
        .result-section h3 {
            margin-top: 0;
            font-size: 14px;
            font-weight: 600;
            text-transform: uppercase;
            color: #646970;
        }
        
        .detected-item {
            padding: 10px;
            margin-bottom: 10px;
            background: #fff;
            border-left: 3px solid #2271b1;
        }
        
        .detected-item-label {
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .detected-item-value {
            color: #2271b1;
            font-family: monospace;
        }
        
        .selector-suggestion {
            padding: 10px;
            margin-bottom: 10px;
            background: #fff;
            border: 1px solid #c3c4c7;
            border-radius: 3px;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .selector-suggestion:hover {
            border-color: #2271b1;
            box-shadow: 0 0 0 1px #2271b1;
        }
        
        .selector-suggestion.selected {
            background: #e7f3ff;
            border-color: #2271b1;
        }
        
        .selector-field {
            font-size: 12px;
            color: #646970;
            margin-bottom: 3px;
        }
        
        .selector-value {
            font-family: monospace;
            font-size: 13px;
        }
        
        .schema-preview {
            background: #f6f7f7;
            padding: 20px;
            border-radius: 4px;
            margin-top: 15px;
        }
        
        .offer-preview-box {
            background: #fff;
            padding: 20px;
            border: 1px solid #c3c4c7;
            border-radius: 4px;
        }
        
        .test-tool-loading {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255,255,255,0.9);
            z-index: 999999;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }
        
        .loading-spinner {
            width: 50px;
            height: 50px;
            border: 4px solid #f3f3f3;
            border-top: 4px solid #2271b1;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .loading-text {
            margin-top: 20px;
            font-size: 16px;
            color: #646970;
        }
        
        .test-result-item {
            padding: 15px;
            margin-bottom: 15px;
            background: #f6f7f7;
            border-radius: 4px;
        }
        
        .test-result-item.success {
            border-left: 4px solid #46b450;
        }
        
        .test-result-item.error {
            border-left: 4px solid #dc3232;
        }
        
        .test-result-label {
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .test-result-value {
            font-family: monospace;
            background: #fff;
            padding: 10px;
            border-radius: 3px;
            margin-top: 5px;
        }
        </style>
        <?php
    }
    
    /**
     * AJAX: Test scrape URL
     */
    public function ajax_test_scrape() {
        check_ajax_referer('parfume_scraper_test_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Нямате права за тази операция', 'parfume-reviews'));
        }
        
        $url = isset($_POST['url']) ? esc_url_raw($_POST['url']) : '';
        
        if (empty($url)) {
            wp_send_json_error(__('Невалиден URL', 'parfume-reviews'));
        }
        
        try {
            // Fetch page content
            $response = wp_remote_get($url, [
                'timeout' => 30,
                'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
            ]);
            
            if (is_wp_error($response)) {
                throw new \Exception($response->get_error_message());
            }
            
            $html = wp_remote_retrieve_body($response);
            
            if (empty($html)) {
                throw new \Exception(__('Празно съдържание', 'parfume-reviews'));
            }
            
            // Auto-detect selectors
            $suggestions = $this->store_schema->auto_detect_selectors($html);
            
            // Try to extract data with common patterns
            $detected_data = $this->auto_extract_data($html);
            
            wp_send_json_success([
                'html' => $html,
                'suggestions' => $suggestions,
                'detected_data' => $detected_data
            ]);
            
        } catch (\Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }
    
    /**
     * AJAX: Test individual selector
     */
    public function ajax_test_selector() {
        check_ajax_referer('parfume_scraper_test_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Нямате права за тази операция', 'parfume-reviews'));
        }
        
        $html = isset($_POST['html']) ? $_POST['html'] : '';
        $selector = isset($_POST['selector']) ? sanitize_text_field($_POST['selector']) : '';
        
        if (empty($html) || empty($selector)) {
            wp_send_json_error(__('Липсващи данни', 'parfume-reviews'));
        }
        
        $result = $this->store_schema->test_selector($html, $selector);
        
        if ($result === null) {
            wp_send_json_error(__('Селекторът не намери нищо', 'parfume-reviews'));
        }
        
        wp_send_json_success([
            'matches' => $result,
            'count' => count($result)
        ]);
    }
    
    /**
     * AJAX: Save test schema
     */
    public function ajax_save_test_schema() {
        check_ajax_referer('parfume_scraper_test_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Нямате права за тази операция', 'parfume-reviews'));
        }
        
        $store_id = isset($_POST['store_id']) ? absint($_POST['store_id']) : 0;
        $schema = isset($_POST['schema']) ? $_POST['schema'] : [];
        
        if (!$store_id) {
            wp_send_json_error(__('Невалиден магазин', 'parfume-reviews'));
        }
        
        // Validate schema
        $validation = $this->store_schema->validate_schema($schema);
        
        if (is_wp_error($validation)) {
            wp_send_json_error($validation->get_error_message());
        }
        
        // Save schema to store
        $result = $this->store_manager->update_store($store_id, ['schema' => $schema]);
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }
        
        wp_send_json_success([
            'message' => __('Schema запазена успешно', 'parfume-reviews')
        ]);
    }
    
    /**
     * Auto-extract data from HTML
     * 
     * @param string $html HTML content
     * @return array Detected data
     */
    private function auto_extract_data($html) {
        $data = [];
        
        // Try to find price
        if (preg_match_all('/(\d+[.,]\d{2})\s*(лв|bgn|лева)/i', $html, $matches)) {
            $data['prices'] = array_unique($matches[1]);
        }
        
        // Try to find ML variants
        if (preg_match_all('/(\d+)\s*ml/i', $html, $matches)) {
            $data['ml_variants'] = array_unique($matches[1]);
        }
        
        // Try to find availability
        if (preg_match('/(наличен|в наличност|in stock)/i', $html, $matches)) {
            $data['availability'] = $matches[1];
        }
        
        // Try to find delivery info
        if (preg_match('/(безплатна доставка|free shipping|доставка)/i', $html, $matches)) {
            $data['delivery'] = $matches[1];
        }
        
        return $data;
    }
}