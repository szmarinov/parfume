<?php
/**
 * Store Schema
 * 
 * Manages scraping schemas for stores
 * 
 * @package Parfume_Reviews
 * @subpackage Features\Stores
 * @since 2.0.0
 */

namespace ParfumeReviews\Features\Stores;

/**
 * StoreSchema Class
 * 
 * Handles schema configuration and validation
 */
class StoreSchema {
    
    /**
     * Available schema fields
     * 
     * @var array
     */
    private $schema_fields = [
        'price_selector' => 'Селектор за цена',
        'old_price_selector' => 'Селектор за стара цена',
        'ml_selector' => 'Селектор за мл. варианти',
        'availability_selector' => 'Селектор за наличност',
        'delivery_selector' => 'Селектор за доставка',
        'name_selector' => 'Селектор за име на продукт',
        'image_selector' => 'Селектор за изображение'
    ];
    
    /**
     * Validate schema
     * 
     * @param array $schema Schema data
     * @return bool|WP_Error
     */
    public function validate_schema($schema) {
        if (!is_array($schema)) {
            return new \WP_Error('invalid_schema', __('Schema трябва да е масив', 'parfume-reviews'));
        }
        
        // Check if at least price selector is present
        if (empty($schema['price_selector'])) {
            return new \WP_Error('missing_price_selector', __('Селекторът за цена е задължителен', 'parfume-reviews'));
        }
        
        // Validate each selector
        foreach ($schema as $key => $value) {
            if (!array_key_exists($key, $this->schema_fields)) {
                continue;
            }
            
            if (empty($value) || !is_string($value)) {
                return new \WP_Error(
                    'invalid_selector',
                    sprintf(__('Невалиден селектор: %s', 'parfume-reviews'), $key)
                );
            }
        }
        
        return true;
    }
    
    /**
     * Get schema fields
     * 
     * @return array
     */
    public function get_schema_fields() {
        return $this->schema_fields;
    }
    
    /**
     * Get default schema template
     * 
     * @return array
     */
    public function get_default_schema() {
        return [
            'price_selector' => '',
            'old_price_selector' => '',
            'ml_selector' => '',
            'availability_selector' => '',
            'delivery_selector' => '',
            'name_selector' => '',
            'image_selector' => ''
        ];
    }
    
    /**
     * Merge schema with defaults
     * 
     * @param array $schema Partial schema
     * @return array Complete schema
     */
    public function merge_with_defaults($schema) {
        return array_merge($this->get_default_schema(), $schema);
    }
    
    /**
     * Parse schema from JSON
     * 
     * @param string $json JSON string
     * @return array|WP_Error
     */
    public function parse_json($json) {
        $schema = json_decode($json, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return new \WP_Error('invalid_json', __('Невалиден JSON формат', 'parfume-reviews'));
        }
        
        $validation = $this->validate_schema($schema);
        
        if (is_wp_error($validation)) {
            return $validation;
        }
        
        return $schema;
    }
    
    /**
     * Export schema to JSON
     * 
     * @param array $schema Schema data
     * @return string
     */
    public function export_to_json($schema) {
        return json_encode($schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
    
    /**
     * Create schema from test results
     * 
     * @param array $test_results Test scraping results
     * @return array
     */
    public function create_from_test($test_results) {
        $schema = $this->get_default_schema();
        
        if (isset($test_results['selectors'])) {
            foreach ($test_results['selectors'] as $field => $selector) {
                if (array_key_exists($field, $schema)) {
                    $schema[$field] = $selector;
                }
            }
        }
        
        return $schema;
    }
    
    /**
     * Compare two schemas
     * 
     * @param array $schema1 First schema
     * @param array $schema2 Second schema
     * @return array Differences
     */
    public function compare_schemas($schema1, $schema2) {
        $differences = [];
        
        $all_keys = array_unique(array_merge(
            array_keys($schema1),
            array_keys($schema2)
        ));
        
        foreach ($all_keys as $key) {
            $value1 = isset($schema1[$key]) ? $schema1[$key] : null;
            $value2 = isset($schema2[$key]) ? $schema2[$key] : null;
            
            if ($value1 !== $value2) {
                $differences[$key] = [
                    'old' => $value1,
                    'new' => $value2
                ];
            }
        }
        
        return $differences;
    }
    
    /**
     * Test selector on HTML
     * 
     * @param string $html HTML content
     * @param string $selector CSS selector
     * @return array|null Matched elements
     */
    public function test_selector($html, $selector) {
        if (empty($selector) || empty($html)) {
            return null;
        }
        
        // Use DOMDocument and DOMXPath for parsing
        libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        $dom->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        
        $xpath = new \DOMXPath($dom);
        
        // Convert CSS selector to XPath (basic conversion)
        $xpath_query = $this->css_to_xpath($selector);
        
        try {
            $nodes = $xpath->query($xpath_query);
            
            if ($nodes === false || $nodes->length === 0) {
                return null;
            }
            
            $results = [];
            foreach ($nodes as $node) {
                $results[] = [
                    'text' => trim($node->textContent),
                    'html' => $dom->saveHTML($node)
                ];
            }
            
            return $results;
            
        } catch (\Exception $e) {
            return null;
        }
    }
    
    /**
     * Convert CSS selector to XPath (basic conversion)
     * 
     * @param string $css_selector CSS selector
     * @return string XPath query
     */
    private function css_to_xpath($css_selector) {
        // Basic CSS to XPath conversion
        // This is a simplified version - for production use a proper library
        
        $xpath = $css_selector;
        
        // .class -> [@class='class']
        $xpath = preg_replace('/\.([a-zA-Z0-9_-]+)/', "*[contains(@class, '$1')]", $xpath);
        
        // #id -> [@id='id']
        $xpath = preg_replace('/#([a-zA-Z0-9_-]+)/', "*[@id='$1']", $xpath);
        
        // element.class -> element[@class='class']
        $xpath = preg_replace('/([a-zA-Z0-9]+)\*\[contains\(@class, \'([^\']+)\'\)\]/', "$1[contains(@class, '$2')]", $xpath);
        
        // Add // prefix if not present
        if (strpos($xpath, '/') !== 0) {
            $xpath = '//' . $xpath;
        }
        
        return $xpath;
    }
    
    /**
     * Auto-detect selectors from HTML
     * 
     * @param string $html HTML content
     * @return array Suggested selectors
     */
    public function auto_detect_selectors($html) {
        if (empty($html)) {
            return [];
        }
        
        $suggestions = [];
        
        // Common price patterns
        $price_patterns = [
            '/class=["\']([^"\']*price[^"\']*)["\']/',
            '/class=["\']([^"\']*amount[^"\']*)["\']/',
            '/id=["\']([^"\']*price[^"\']*)["\']/'
        ];
        
        foreach ($price_patterns as $pattern) {
            if (preg_match($pattern, $html, $matches)) {
                $suggestions['price_selector'][] = '.' . $matches[1];
            }
        }
        
        // Common availability patterns
        $availability_patterns = [
            '/class=["\']([^"\']*stock[^"\']*)["\']/',
            '/class=["\']([^"\']*availability[^"\']*)["\']/',
            '/class=["\']([^"\']*available[^"\']*)["\']/'
        ];
        
        foreach ($availability_patterns as $pattern) {
            if (preg_match($pattern, $html, $matches)) {
                $suggestions['availability_selector'][] = '.' . $matches[1];
            }
        }
        
        // Remove duplicates
        foreach ($suggestions as $field => $values) {
            $suggestions[$field] = array_unique($values);
        }
        
        return $suggestions;
    }
    
    /**
     * Render schema editor UI
     * 
     * @param array $schema Current schema
     * @param int $store_id Store ID
     * @return string HTML
     */
    public function render_schema_editor($schema, $store_id = 0) {
        $schema = $this->merge_with_defaults($schema);
        
        ob_start();
        ?>
        <div class="parfume-schema-editor" data-store-id="<?php echo esc_attr($store_id); ?>">
            <table class="form-table">
                <?php foreach ($this->schema_fields as $field => $label) : ?>
                    <tr>
                        <th scope="row">
                            <label for="schema_<?php echo esc_attr($field); ?>">
                                <?php echo esc_html($label); ?>
                            </label>
                        </th>
                        <td>
                            <input 
                                type="text" 
                                id="schema_<?php echo esc_attr($field); ?>" 
                                name="schema[<?php echo esc_attr($field); ?>]" 
                                value="<?php echo esc_attr($schema[$field]); ?>" 
                                class="large-text schema-field"
                                placeholder="CSS селектор (напр. .product-price)"
                            />
                            <button 
                                type="button" 
                                class="button test-selector-button" 
                                data-field="<?php echo esc_attr($field); ?>"
                            >
                                <?php _e('Тествай', 'parfume-reviews'); ?>
                            </button>
                            <div class="test-result" id="test_result_<?php echo esc_attr($field); ?>"></div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
            
            <div class="schema-actions">
                <button type="button" class="button" id="import-schema-button">
                    <?php _e('Импортирай JSON', 'parfume-reviews'); ?>
                </button>
                <button type="button" class="button" id="export-schema-button">
                    <?php _e('Експортирай JSON', 'parfume-reviews'); ?>
                </button>
                <button type="button" class="button button-primary" id="save-schema-button">
                    <?php _e('Запази Schema', 'parfume-reviews'); ?>
                </button>
            </div>
            
            <div class="schema-json-container" style="display:none;">
                <textarea id="schema-json" rows="10" class="large-text"></textarea>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}