# Parfume Reviews - WordPress Plugin

Comprehensive perfume review system for WordPress with advanced features including comparison, import/export, price scraping, and more.

## 🎯 Features

- **Custom Post Type** - Parfume CPT with rich meta fields
- **7 Taxonomies** - Brands, Gender, Aroma Type, Season, Intensity, Notes, Perfumers
- **Comparison System** - Compare up to 4 parfumes side-by-side
- **Import/Export** - JSON and CSV support
- **Price Scraper** - Auto-update prices from stores
- **Mobile Optimized** - Fixed bottom panel for stores
- **SEO Friendly** - Structured data, breadcrumbs
- **Template Override** - Theme-compatible templates
- **Multi-Store Support** - Track prices from multiple retailers

## 📋 Requirements

- PHP 7.4 or higher
- WordPress 5.8 or higher
- MySQL 5.6 or higher

## 🚀 Installation

### Method 1: Manual Installation

1. Download the plugin zip file
2. Go to WordPress Admin → Plugins → Add New → Upload Plugin
3. Choose the zip file and click "Install Now"
4. Activate the plugin

### Method 2: Via Composer

```bash
composer require parfume-reviews/wordpress-plugin
```

### Method 3: Git Clone

```bash
cd wp-content/plugins
git clone https://github.com/yourusername/parfume-reviews.git
```

## ⚙️ Configuration

After activation, go to **Parfumes → Settings** to configure:

### General Settings
- Items per page
- Enable/disable features
- Ratings system

### URL Structure
- Customize all slugs
- Parfume base slug
- Taxonomy slugs

### Mobile Settings
- Fixed bottom panel
- Z-index configuration
- Close button

### Scraper Settings
- Enable auto-update
- Update interval
- Supported stores

### Comparison Settings
- Maximum items
- Comparison page

## 📁 File Structure

```
parfume-reviews/
├── parfume-reviews.php       # Main plugin file
├── uninstall.php             # Uninstall handler
├── composer.json             # Package configuration
│
├── config/                   # Configuration files
│   ├── taxonomies.php       # Taxonomy definitions
│   ├── post-types.php       # Post type configuration
│   └── settings.php         # Settings structure
│
├── includes/                 # PHP classes (PSR-4)
│   ├── core/                # Core system
│   ├── posttypes/           # Custom post types
│   ├── taxonomies/          # Taxonomy management
│   ├── templates/           # Template loader
│   ├── features/            # Plugin features
│   └── admin/               # Admin functionality
│
├── templates/               # Template files
├── assets/                  # CSS/JS files
└── languages/               # Translations
```

## 🎨 Template Override

To override plugin templates in your theme:

1. Create folder: `your-theme/parfume-reviews/`
2. Copy template from `plugins/parfume-reviews/templates/`
3. Edit in your theme folder

Available templates:
- `single-parfume.php` - Single parfume page
- `archive-parfume.php` - Parfume archive
- `taxonomy-*.php` - Taxonomy archives

## 🔧 Developer Guide

### Using the Repository

```php
use Parfume_Reviews\PostTypes\Parfume\Repository;

$repo = new Repository();

// Find by ID
$parfume = $repo->find(123);

// Get top rated
$top_rated = $repo->top_rated(10);

// Search
$results = $repo->search('Chanel No 5');

// By taxonomy
$male_parfumes = $repo->by_term('gender', 'male');
```

### Using the Container

```php
$container = \Parfume_Reviews\Core\Plugin::get_instance()->get_container();

// Get service
$settings = $container->get('settings');
$taxonomies = $container->get('taxonomies');
```

### Adding Custom Fields

Edit `config/post-types.php`:

```php
'meta_boxes' => [
    'custom_box' => [
        'id' => 'custom_box',
        'title' => 'Custom Fields',
        'fields' => [
            'custom_field' => [
                'label' => 'Custom Field',
                'type' => 'text'
            ]
        ]
    ]
]
```

### Hooks & Filters

**Actions:**
```php
// Before template loads
do_action('parfume_reviews_before_template', $template_name, $template, $args);

// After template loads
do_action('parfume_reviews_after_template', $template_name, $template, $args);

// After parfume created
do_action('parfume_reviews_parfume_created', $post_id);
```

**Filters:**
```php
// Modify template path
add_filter('parfume_reviews_get_template', $template, $template_name, $template_path);

// Modify comparison max items
add_filter('parfume_reviews_max_comparison_items', $max_items);

// Modify price display
add_filter('parfume_reviews_format_price', $formatted_price, $price, $currency);
```

## 🛠️ API Usage

### AJAX Endpoints

**Add to Comparison:**
```javascript
jQuery.ajax({
    url: parfumeReviews.ajaxurl,
    type: 'POST',
    data: {
        action: 'add_to_comparison',
        post_id: 123,
        nonce: parfumeReviews.nonce
    }
});
```

**Update Price:**
```javascript
jQuery.ajax({
    url: parfumeSingle.ajaxurl,
    type: 'POST',
    data: {
        action: 'parfume_update_price',
        post_id: 123,
        store_index: 0,
        nonce: parfumeSingle.nonce
    }
});
```

## 📊 Database Schema

### Post Meta (prefixed with `_parfume_`)
- `rating` - Overall rating (0-10)
- `release_year` - Year released
- `longevity` - Duration (weak/moderate/long/very_long)
- `sillage` - Projection (intimate/moderate/strong/enormous)
- `stores` - Array of store data
- `gallery` - Array of image IDs

### Taxonomies
- `marki` - Brands
- `gender` - Gender categories
- `aroma_type` - Aroma types
- `season` - Seasons
- `intensity` - Intensity levels
- `notes` - Aroma notes
- `perfumer` - Perfumers

## 🌐 Localization

The plugin is translation-ready. To translate:

1. Use Poedit or Loco Translate
2. Translate strings in `languages/parfume-reviews.pot`
3. Save as `parfume-reviews-{locale}.mo`
4. Place in `wp-content/languages/plugins/`

Text domain: `parfume-reviews`

## 🧪 Testing

Run PHPUnit tests:

```bash
composer install
composer test
```

Code standards:

```bash
composer phpcs
composer phpcbf
```

## 🐛 Debugging

Enable debug mode in settings or add to `wp-config.php`:

```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

Check logs at: `wp-content/debug.log`

## 📝 Changelog

### Version 2.0.0
- Complete rewrite with modern architecture
- PSR-4 autoloading
- Dependency injection container
- Repository pattern
- Configuration-based setup
- Improved performance
- Better code organization

### Version 1.0.0
- Initial release

## 🤝 Contributing

Contributions are welcome! Please:

1. Fork the repository
2. Create a feature branch
3. Follow WordPress Coding Standards
4. Write tests for new features
5. Submit a pull request

## 📄 License

GPL-2.0-or-later

## 👨‍💻 Author

**Your Name**
- Website: https://example.com
- Email: your.email@example.com

## 🙏 Support

- Documentation: https://docs.example.com
- Issues: https://github.com/yourusername/parfume-reviews/issues
- Support Forum: https://wordpress.org/support/plugin/parfume-reviews/

## 🔗 Links

- Plugin Page: https://wordpress.org/plugins/parfume-reviews/
- GitHub: https://github.com/yourusername/parfume-reviews
- Demo: https://demo.example.com