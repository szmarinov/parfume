# Parfume Reviews Plugin - Deployment Guide

## 📦 Какво е създадено

### ✅ Създадени файлове (готови за deployment):

#### Core Files
- `parfume-reviews.php` - Main plugin file
- `uninstall.php` - Uninstall handler
- `composer.json` - Package configuration
- `README.md` - Documentation

#### Configuration
- `config/taxonomies.php` - Taxonomy definitions
- `config/post-types.php` - Post type configuration
- `config/settings.php` - Settings structure

#### PHP Classes (includes/)
- `core/plugin.php` - Main orchestrator
- `core/container.php` - DI Container
- `core/loader.php` - Hook manager
- `posttypes/parfume/posttype.php` - Post type class
- `posttypes/parfume/metaboxes.php` - Meta boxes
- `posttypes/parfume/repository.php` - Data access
- `taxonomies/taxonomymanager.php` - Taxonomy manager
- `taxonomies/registrar.php` - Taxonomy registration
- `taxonomies/rewritehandler.php` - URL handling
- `templates/loader.php` - Template loader
- `features/comparison/comparison.php` - Comparison feature
- `features/importexport/importexport.php` - Import/Export
- `features/scraper/scraper.php` - Price scraper
- `admin/settings/settingsmanager.php` - Settings manager

#### Templates
- `templates/single-parfume.php` - Single parfume template
- `templates/archive-parfume.php` - Archive template
- `templates/taxonomy-perfumer.php` - Perfumer taxonomy
- `templates/taxonomy.php` - Generic taxonomy
- `templates/parts/parfume-card.php` - Card component
- `templates/parts/filters.php` - Filter form
- `templates/parts/breadcrumbs.php` - Breadcrumbs
- `templates/parts/pagination.php` - Pagination

#### CSS Files
- `assets/css/main.css` - Main styles
- `assets/css/single-parfume.css` - Single page styles
- `assets/css/archive.css` - Archive styles
- `assets/css/comparison.css` - Comparison styles

#### JavaScript Files
- `assets/js/main.js` - Main JavaScript
- `assets/js/comparison.js` - Comparison functionality

---

## 🚀 Deployment Steps

### Step 1: Създаване на файловата структура

```bash
# В wp-content/plugins/ създайте папката
mkdir parfume-reviews
cd parfume-reviews

# Създайте основните директории
mkdir -p config
mkdir -p includes/core
mkdir -p includes/posttypes/parfume
mkdir -p includes/taxonomies
mkdir -p includes/templates
mkdir -p includes/features/comparison
mkdir -p includes/features/importexport
mkdir -p includes/features/scraper
mkdir -p includes/admin/settings
mkdir -p templates/parts
mkdir -p assets/css
mkdir -p assets/js
mkdir -p assets/images
mkdir -p languages
```

### Step 2: Копиране на файловете

Копирайте всички създадени файлове в съответните директории:

**ВАЖНО:** Файловете и папките трябва да са с **малки букви**:
- `includes/Core/` → `includes/core/`
- `Plugin.php` → `plugin.php`
- и т.н.

### Step 3: Placeholder изображение

Създайте placeholder изображение:
```bash
# В assets/images/
# Добавете placeholder.jpg (500x500px)
```

### Step 4: Останали необходими файлове

#### .gitignore
```
vendor/
node_modules/
.DS_Store
*.log
.idea/
.vscode/
```

#### languages/parfume-reviews.pot
```
# Генерирайте с WP-CLI:
wp i18n make-pot . languages/parfume-reviews.pot
```

---

## 🔧 След deployment

### 1. Активирайте плъгина
```
WordPress Admin → Plugins → Activate "Parfume Reviews"
```

### 2. Проверете rewrite rules
```
Settings → Permalinks → Save Changes
```

### 3. Конфигурирайте настройките
```
Парфюми → Настройки
```

Конфигурирайте:
- URL slugs
- Mobile settings
- Comparison settings
- Scraper settings (ако ще използвате)

### 4. Добавете default terms

Плъгинът автоматично ще добави default terms за:
- Gender (Мъжки, Дамски, Унисекс...)
- Brands (Chanel, Dior, Tom Ford...)
- Season (Пролет, Лято, Есен, Зима)
- Intensity (Силни, Средни, Леки)
- Aroma Type (Тоалетна вода, Парфюмна вода...)
- Notes (Дървесни, Цитрусови...)

---

## ⚠️ Важни забележки

### PSR-4 Autoloading

Файловете се зареждат автоматично чрез PSR-4 autoloader.

**Naming convention:**
```
Namespace: Parfume_Reviews\Core\Plugin
File: includes/core/plugin.php
Class: Plugin
```

### Namespace структура

```php
Parfume_Reviews\                    // Root namespace
├── Core\                          // includes/core/
│   ├── Plugin
│   ├── Container
│   └── Loader
├── PostTypes\Parfume\             // includes/posttypes/parfume/
│   ├── PostType
│   ├── MetaBoxes
│   └── Repository
├── Taxonomies\                    // includes/taxonomies/
│   ├── TaxonomyManager
│   ├── Registrar
│   └── RewriteHandler
└── Features\                      // includes/features/
    ├── Comparison\Comparison
    ├── ImportExport\ImportExport
    └── Scraper\Scraper
```

### Browser Storage Restriction

**ВАЖНО:** Плъгинът НЕ използва localStorage/sessionStorage за comparison!

Използва:
- Session storage на сървъра (PHP $_SESSION)
- AJAX calls за sync

---

## 🧪 Testing

### Основни функции за тестване:

1. **Post Type**
   - Създаване на парфюм
   - Запазване на meta fields
   - Галерия от снимки

2. **Taxonomies**
   - Марки, Пол, Нотки работят ли?
   - URL-ите правилни ли са?
   - Archive pages зареждат ли се?

3. **Comparison**
   - Добавяне към сравнение
   - Премахване
   - Comparison bar показва ли се?

4. **Templates**
   - Single parfume page
   - Archive page
   - Taxonomy pages
   - Theme override работи ли?

5. **Import/Export**
   - Export to JSON
   - Export to CSV
   - Import from JSON
   - Import from CSV

---

## 📝 Още незавършени функции

### За довършване (по желание):

1. **JavaScript файлове:**
   - `assets/js/single-parfume.js`
   - `assets/js/archive.js`
   - `assets/js/admin-settings.js`

2. **CSS файлове:**
   - `assets/css/admin-settings.css`

3. **Template helpers:**
   - Helper functions за breadcrumbs
   - Helper functions за filters
   - Helper functions за rating stars

4. **Advanced features:**
   - Collections (user wishlist)
   - Reviews system
   - Advanced filtering with AJAX

---

## 🐛 Troubleshooting

### Проблем: 404 на taxonomy pages

**Решение:**
```
Settings → Permalinks → Save Changes
```

### Проблем: Templates не се зареждат

**Проверка:**
```php
// Проверете дали файлът съществува
ls -la templates/single-parfume.php

// Проверете permissions
chmod 644 templates/*.php
```

### Проблем: Class not found

**Решение:**
```
// Проверете namespace и file path
// Namespace: Parfume_Reviews\Core\Plugin
// File: includes/core/plugin.php (малки букви!)
```

---

## 📞 Support

За въпроси и проблеми:
- Check README.md
- Check code comments
- Enable WP_DEBUG mode

---

## 🎉 Success!

Ако всичко работи правилно:
- ✅ Плъгинът е активен
- ✅ Може да създавате парфюми
- ✅ URL-ите работят
- ✅ Templates се зареждат
- ✅ Comparison работи
- ✅ Import/Export работи

**Честито! Плъгинът е готов за употреба! 🚀**