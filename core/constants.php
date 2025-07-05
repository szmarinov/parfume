<?php
/**
 * Plugin constants
 */

if (!defined('ABSPATH')) {
    exit;
}

// Plugin info - проверяваме дали константите вече са дефинирани
if (!defined('PARFUME_REVIEWS_VERSION')) {
    define('PARFUME_REVIEWS_VERSION', '1.0.0');
}

if (!defined('PARFUME_REVIEWS_PLUGIN_FILE')) {
    define('PARFUME_REVIEWS_PLUGIN_FILE', dirname(__DIR__) . '/parfume-reviews.php');
}

if (!defined('PARFUME_REVIEWS_PLUGIN_DIR')) {
    define('PARFUME_REVIEWS_PLUGIN_DIR', plugin_dir_path(PARFUME_REVIEWS_PLUGIN_FILE));
}

if (!defined('PARFUME_REVIEWS_PLUGIN_URL')) {
    define('PARFUME_REVIEWS_PLUGIN_URL', plugin_dir_url(PARFUME_REVIEWS_PLUGIN_FILE));
}

if (!defined('PARFUME_REVIEWS_BASENAME')) {
    define('PARFUME_REVIEWS_BASENAME', plugin_basename(PARFUME_REVIEWS_PLUGIN_FILE));
}

// Paths
if (!defined('PARFUME_REVIEWS_TEMPLATES_DIR')) {
    define('PARFUME_REVIEWS_TEMPLATES_DIR', PARFUME_REVIEWS_PLUGIN_DIR . 'templates/');
}

if (!defined('PARFUME_REVIEWS_ASSETS_URL')) {
    define('PARFUME_REVIEWS_ASSETS_URL', PARFUME_REVIEWS_PLUGIN_URL . 'frontend/assets/');
}

// Database
if (!defined('PARFUME_REVIEWS_DB_VERSION')) {
    define('PARFUME_REVIEWS_DB_VERSION', '1.0');
}

// Cache
if (!defined('PARFUME_REVIEWS_CACHE_GROUP')) {
    define('PARFUME_REVIEWS_CACHE_GROUP', 'parfume_reviews');
}

if (!defined('PARFUME_REVIEWS_CACHE_DURATION')) {
    define('PARFUME_REVIEWS_CACHE_DURATION', 3600); // 1 hour
}

// Text domain
if (!defined('PARFUME_REVIEWS_TEXT_DOMAIN')) {
    define('PARFUME_REVIEWS_TEXT_DOMAIN', 'parfume-reviews');
}

// Minimum requirements
if (!defined('PARFUME_REVIEWS_MIN_PHP_VERSION')) {
    define('PARFUME_REVIEWS_MIN_PHP_VERSION', '7.4');
}

if (!defined('PARFUME_REVIEWS_MIN_WP_VERSION')) {
    define('PARFUME_REVIEWS_MIN_WP_VERSION', '5.0');
}