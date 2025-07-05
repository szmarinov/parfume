<?php
/**
 * Autoloader for Parfume Reviews Plugin
 * Handles automatic loading of plugin classes
 */

namespace Parfume_Reviews\Core;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class Autoloader {
    
    /**
     * Registered prefixes
     * @var array
     */
    private static $prefixes = array();
    
    /**
     * Class mappings for explicit file locations
     * @var array
     */
    private static $class_map = array();
    
    /**
     * Whether autoloader is registered
     * @var bool
     */
    private static $registered = false;
    
    /**
     * Register the autoloader
     */
    public static function register() {
        if (self::$registered) {
            return;
        }
        
        // Register autoloader function
        if (function_exists('spl_autoload_register')) {
            spl_autoload_register(array(__CLASS__, 'autoload'));
            self::$registered = true;
            
            // Setup default mappings
            self::setup_default_mappings();
            
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Parfume Reviews Autoloader registered successfully');
            }
        }
    }
    
    /**
     * Unregister the autoloader
     */
    public static function unregister() {
        if (self::$registered && function_exists('spl_autoload_unregister')) {
            spl_autoload_unregister(array(__CLASS__, 'autoload'));
            self::$registered = false;
        }
    }
    
    /**
     * Setup default namespace to directory mappings
     */
    private static function setup_default_mappings() {
        $plugin_dir = defined('PARFUME_REVIEWS_PLUGIN_DIR') ? PARFUME_REVIEWS_PLUGIN_DIR : plugin_dir_path(dirname(__DIR__));
        
        // Namespace prefixes to directory mappings
        $mappings = array(
            'Parfume_Reviews\\Core\\'         => 'core/',
            'Parfume_Reviews\\PostTypes\\'    => 'post-types/',
            'Parfume_Reviews\\Taxonomies\\'   => 'taxonomies/',
            'Parfume_Reviews\\Frontend\\'     => 'frontend/',
            'Parfume_Reviews\\Frontend\\Shortcodes\\' => 'frontend/shortcodes/',
            'Parfume_Reviews\\Admin\\'        => 'admin/',
            'Parfume_Reviews\\Utils\\'        => 'utils/',
            'Parfume_Reviews\\Templates\\'    => 'templates/',
        );
        
        foreach ($mappings as $prefix => $directory) {
            self::add_namespace($prefix, $plugin_dir . $directory);
        }
        
        // Setup explicit class mappings for specific files
        self::setup_explicit_mappings($plugin_dir);
    }
    
    /**
     * Setup explicit class to file mappings
     */
    private static function setup_explicit_mappings($plugin_dir) {
        self::$class_map = array(
            // Core classes
            'Parfume_Reviews\\Core\\Bootstrap'    => $plugin_dir . 'core/bootstrap.php',
            'Parfume_Reviews\\Core\\Constants'    => $plugin_dir . 'core/constants.php',
            'Parfume_Reviews\\Core\\Autoloader'   => $plugin_dir . 'core/autoloader.php',
            
            // Utils classes
            'Parfume_Reviews\\Utils\\Helpers'           => $plugin_dir . 'utils/helpers.php',
            'Parfume_Reviews\\Utils\\Post_Type_Base'    => $plugin_dir . 'utils/base-classes.php',
            'Parfume_Reviews\\Utils\\Taxonomy_Base'     => $plugin_dir . 'utils/base-classes.php',
            'Parfume_Reviews\\Utils\\Shortcode_Base'    => $plugin_dir . 'utils/base-classes.php',
            'Parfume_Reviews\\Utils\\Admin_Page_Base'   => $plugin_dir . 'utils/base-classes.php',
            'Parfume_Reviews\\Utils\\Widget_Base'       => $plugin_dir . 'utils/base-classes.php',
            'Parfume_Reviews\\Utils\\Ajax_Base'         => $plugin_dir . 'utils/base-classes.php',
            'Parfume_Reviews\\Utils\\Meta_Box_Base'     => $plugin_dir . 'utils/base-classes.php',
            'Parfume_Reviews\\Utils\\Cache'             => $plugin_dir . 'utils/cache.php',
            'Parfume_Reviews\\Utils\\Seo'               => $plugin_dir . 'utils/seo.php',
            'Parfume_Reviews\\Utils\\Singleton'         => $plugin_dir . 'utils/base-classes.php',
            
            // Post Types
            'Parfume_Reviews\\PostTypes\\Parfume'     => $plugin_dir . 'post-types/parfume.php',
            'Parfume_Reviews\\PostTypes\\Blog'        => $plugin_dir . 'post-types/blog.php',
            'Parfume_Reviews\\PostTypes\\Meta_Boxes'  => $plugin_dir . 'post-types/meta-boxes.php',
            
            // Taxonomies
            'Parfume_Reviews\\Taxonomies\\Brands'      => $plugin_dir . 'taxonomies/brands.php',
            'Parfume_Reviews\\Taxonomies\\Notes'       => $plugin_dir . 'taxonomies/notes.php',
            'Parfume_Reviews\\Taxonomies\\Perfumers'   => $plugin_dir . 'taxonomies/perfumers.php',
            'Parfume_Reviews\\Taxonomies\\Gender'      => $plugin_dir . 'taxonomies/gender.php',
            'Parfume_Reviews\\Taxonomies\\Aroma_Type'  => $plugin_dir . 'taxonomies/aroma-type.php',
            'Parfume_Reviews\\Taxonomies\\Season'      => $plugin_dir . 'taxonomies/season.php',
            'Parfume_Reviews\\Taxonomies\\Intensity'   => $plugin_dir . 'taxonomies/intensity.php',
            
            // Frontend Shortcodes
            'Parfume_Reviews\\Frontend\\Shortcodes\\Rating'     => $plugin_dir . 'frontend/shortcodes/rating.php',
            'Parfume_Reviews\\Frontend\\Shortcodes\\Filters'    => $plugin_dir . 'frontend/shortcodes/filters.php',
            'Parfume_Reviews\\Frontend\\Shortcodes\\Stores'     => $plugin_dir . 'frontend/shortcodes/stores.php',
            'Parfume_Reviews\\Frontend\\Shortcodes\\Archives'   => $plugin_dir . 'frontend/shortcodes/archives.php',
            'Parfume_Reviews\\Frontend\\Shortcodes\\Comparison' => $plugin_dir . 'frontend/shortcodes/comparison.php',
            'Parfume_Reviews\\Frontend\\Shortcodes\\Blog'       => $plugin_dir . 'frontend/shortcodes/blog.php',
            
            // Admin classes
            'Parfume_Reviews\\Admin\\Settings'      => $plugin_dir . 'admin/settings.php',
            'Parfume_Reviews\\Admin\\Import_Export' => $plugin_dir . 'admin/import-export.php',
            'Parfume_Reviews\\Admin\\Dashboard'     => $plugin_dir . 'admin/dashboard.php',
        );
    }
    
    /**
     * Add a namespace prefix and directory
     * @param string $prefix The namespace prefix
     * @param string $base_dir A base directory for class files in the namespace
     * @param bool $prepend If true, prepend the base directory to the stack instead of appending it
     */
    public static function add_namespace($prefix, $base_dir, $prepend = false) {
        // Normalize namespace prefix
        $prefix = trim($prefix, '\\') . '\\';
        
        // Normalize the base directory with a trailing separator
        $base_dir = rtrim($base_dir, DIRECTORY_SEPARATOR) . '/';
        
        // Initialize the namespace prefix array
        if (isset(self::$prefixes[$prefix]) === false) {
            self::$prefixes[$prefix] = array();
        }
        
        // Retain the base directory for the namespace prefix
        if ($prepend) {
            array_unshift(self::$prefixes[$prefix], $base_dir);
        } else {
            array_push(self::$prefixes[$prefix], $base_dir);
        }
    }
    
    /**
     * Loads the class file for a given class name
     * @param string $class The fully-qualified class name
     * @return mixed The mapped file name on success, or boolean false on failure
     */
    public static function autoload($class) {
        // Check explicit class mapping first
        if (isset(self::$class_map[$class])) {
            $file = self::$class_map[$class];
            if (self::require_file($file)) {
                return $file;
            }
        }
        
        // Work backwards through the namespace hierarchy to find a matching file
        $prefix = $class;
        
        // Remove one namespace segment at a time until we find a match
        while (false !== $pos = strrpos($prefix, '\\')) {
            
            // Retain the trailing namespace separator in the prefix
            $prefix = substr($class, 0, $pos + 1);
            
            // The rest is the relative class name
            $relative_class = substr($class, $pos + 1);
            
            // Try to load a mapped file for the prefix and relative class
            $mapped_file = self::load_mapped_file($prefix, $relative_class);
            if ($mapped_file) {
                return $mapped_file;
            }
            
            // Remove the trailing namespace separator for the next iteration
            $prefix = rtrim($prefix, '\\');
        }
        
        // Never found a mapped file
        return false;
    }
    
    /**
     * Load the mapped file for a namespace prefix and relative class
     * @param string $prefix The namespace prefix
     * @param string $relative_class The relative class name
     * @return mixed Boolean false if no mapped file can be loaded, or the name of the mapped file that was loaded
     */
    protected static function load_mapped_file($prefix, $relative_class) {
        // Are there any base directories for this namespace prefix?
        if (isset(self::$prefixes[$prefix]) === false) {
            return false;
        }
        
        // Look through base directories for this namespace prefix
        foreach (self::$prefixes[$prefix] as $base_dir) {
            
            // Replace namespace separators with directory separators
            // in the relative class name, append with .php
            $file = $base_dir
                  . str_replace('\\', '/', $relative_class)
                  . '.php';
            
            // Convert class name to lowercase and replace underscores with hyphens for file naming
            $file_alt = $base_dir
                      . strtolower(str_replace(array('\\', '_'), array('/', '-'), $relative_class))
                      . '.php';
            
            // Try both naming conventions
            if (self::require_file($file)) {
                return $file;
            }
            
            if (self::require_file($file_alt)) {
                return $file_alt;
            }
        }
        
        // Never found it
        return false;
    }
    
    /**
     * If a file exists, require it from the file system
     * @param string $file The file to require
     * @return bool True if the file exists and is loaded, false if not
     */
    protected static function require_file($file) {
        if (file_exists($file)) {
            require_once $file;
            return true;
        }
        return false;
    }
    
    /**
     * Get all registered prefixes
     * @return array
     */
    public static function get_prefixes() {
        return self::$prefixes;
    }
    
    /**
     * Get all class mappings
     * @return array
     */
    public static function get_class_map() {
        return self::$class_map;
    }
    
    /**
     * Check if autoloader is registered
     * @return bool
     */
    public static function is_registered() {
        return self::$registered;
    }
    
    /**
     * Debug function to show mapping attempts
     * @param string $class
     */
    public static function debug_class_loading($class) {
        if (!defined('WP_DEBUG') || !WP_DEBUG) {
            return;
        }
        
        $plugin_dir = defined('PARFUME_REVIEWS_PLUGIN_DIR') ? PARFUME_REVIEWS_PLUGIN_DIR : plugin_dir_path(dirname(__DIR__));
        
        error_log("Parfume Reviews Autoloader: Attempting to load class: {$class}");
        
        // Check explicit mapping
        if (isset(self::$class_map[$class])) {
            $file = self::$class_map[$class];
            error_log("  Found in explicit mapping: {$file}");
            error_log("  File exists: " . (file_exists($file) ? 'YES' : 'NO'));
        }
        
        // Check namespace mappings
        foreach (self::$prefixes as $prefix => $dirs) {
            if (strpos($class, $prefix) === 0) {
                error_log("  Matches prefix: {$prefix}");
                foreach ($dirs as $dir) {
                    $relative_class = substr($class, strlen($prefix));
                    $file1 = $dir . str_replace('\\', '/', $relative_class) . '.php';
                    $file2 = $dir . strtolower(str_replace(array('\\', '_'), array('/', '-'), $relative_class)) . '.php';
                    
                    error_log("    Trying: {$file1} - " . (file_exists($file1) ? 'EXISTS' : 'NOT FOUND'));
                    error_log("    Trying: {$file2} - " . (file_exists($file2) ? 'EXISTS' : 'NOT FOUND'));
                }
            }
        }
    }
}