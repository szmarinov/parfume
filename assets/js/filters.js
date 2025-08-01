/**
 * Parfume Reviews - Filters Functionality
 * Обработка на филтрите без 404 грешки
 * МОДИФИЦИРАН ЗА МНОЖЕСТВЕНО ФИЛТРИРАНЕ БЕЗ АВТОМАТИЧНО ПРЕНАСОЧВАНЕ
 */
jQuery(document).ready(function($) {
    
    // Initialize filters functionality
    initializeFilters();
    
    function initializeFilters() {
        // Handle filter form submission
        $('.parfume-filters form').on('submit', function(e) {
            e.preventDefault();
            applyFilters();
        });
        
        // ПРЕМАХНАТО АВТОМАТИЧНОТО ПРЕНАСОЧВАНЕ ПРИ ПРОМЯНА НА ФИЛТРИ
        // Сега филтрите се прилагат само при submit на формата или кликване на бутон "Филтрирай"
        $('.parfume-filters input, .parfume-filters select').on('change', function() {
            // Само актуализираме визуалния интерфейс, БЕЗ да прилагаме филтрите
            updateFilterPreview();
        });
        
        // Handle explicit filter apply button click
        $('.filter-submit .button-primary, .filter-button').on('click', function(e) {
            e.preventDefault();
            applyFilters();
        });
        
        // Handle filter reset
        $('.filter-reset, .button-secondary, .reset-button').on('click', function(e) {
            e.preventDefault();
            resetFilters();
        });
        
        // Handle active filter tag removal
        $(document).on('click', '.remove-tag', function(e) {
            e.preventDefault();
            removeFilter($(this));
        });
        
        // Handle expandable filter sections - ПОПРАВЕНО
        $('.filter-title').on('click', function(e) {
            e.preventDefault();
            toggleFilterSection($(this));
        });
        
        // Handle filter search
        $('.filter-search').on('input', function() {
            filterOptions($(this));
        });
    }
    
    function collectFilters() {
        var filters = {};
        
        // Collect multiple choice filters (checkboxes)
        $('.parfume-filters input[type="checkbox"]:checked').each(function() {
            var name = $(this).attr('name');
            if (name) {
                var key = name.replace('[]', '');
                if (!filters[key]) {
                    filters[key] = [];
                }
                filters[key].push($(this).val());
            }
        });
        
        // Collect single choice filters (selects)
        $('.parfume-filters select').each(function() {
            var name = $(this).attr('name');
            var value = $(this).val();
            if (name && value) {
                filters[name] = value;
            }
        });
        
        // Collect range filters (price inputs)
        var minPrice = $('.parfume-filters input[name="min_price"]').val();
        var maxPrice = $('.parfume-filters input[name="max_price"]').val();
        
        if (minPrice) {
            filters['min_price'] = minPrice;
        }
        if (maxPrice) {
            filters['max_price'] = maxPrice;
        }
        
        return filters;
    }
    
    function buildFilterUrl(filters) {
        var baseUrl = window.location.pathname;
        var params = [];
        
        for (var key in filters) {
            if (filters.hasOwnProperty(key)) {
                var value = filters[key];
                if (Array.isArray(value)) {
                    value.forEach(function(item) {
                        params.push(encodeURIComponent(key) + '[]=' + encodeURIComponent(item));
                    });
                } else {
                    params.push(encodeURIComponent(key) + '=' + encodeURIComponent(value));
                }
            }
        }
        
        if (params.length > 0) {
            return baseUrl + '?' + params.join('&');
        }
        
        return baseUrl;
    }
    
    function showLoadingState() {
        $('.parfume-filters').addClass('loading');
    }
    
    function initializeFromUrl() {
        var urlParams = new URLSearchParams(window.location.search);
        
        // Изчиствам всички филтри първо
        $('.parfume-filters input[type="checkbox"]').prop('checked', false);
        $('.parfume-filters select').val('');
        $('.parfume-filters input[type="number"], .parfume-filters input[type="text"]').val('');
        
        // Зареждам стойностите от URL-а
        urlParams.forEach(function(value, key) {
            if (key.endsWith('[]')) {
                // Множествен избор (checkboxes)
                var cleanKey = key.replace('[]', '');
                var decodedValue = decodeURIComponent(value);
                $('.parfume-filters input[name="' + cleanKey + '[]"][value="' + decodedValue + '"]').prop('checked', true);
            } else {
                // Единичен избор (select, input)
                var decodedValue = decodeURIComponent(value);
                $('.parfume-filters [name="' + key + '"]').val(decodedValue);
            }
        });
    }
    
    function getHumanReadableName(filterType, value) {
        // Търси етикета на checkbox-а или option-а
        var $input = $('.parfume-filters input[name="' + filterType + '[]"][value="' + value + '"], ' +
                      '.parfume-filters option[value="' + value + '"]');
        
        if ($input.length > 0) {
            if ($input.is('input')) {
                var $label = $input.closest('label');
                if ($label.length > 0) {
                    return $label.text().trim().replace(/\(\d+\)$/, '').trim();
                }
            } else {
                return $input.text().trim();
            }
        }
        
        return decodeURIComponent(value);
    }
    
    function createActiveFilterTags() {
        var $activeContainer = $('.active-filters .filter-tags');
        if ($activeContainer.length === 0) return;
        
        $activeContainer.empty();
        
        var urlParams = new URLSearchParams(window.location.search);
        
        urlParams.forEach(function(value, key) {
            if (key.endsWith('[]')) {
                var cleanKey = key.replace('[]', '');
                var decodedValue = decodeURIComponent(value);
                
                // Получаваме човешки четимо име
                var displayName = getHumanReadableName(cleanKey, value);
                
                var $tag = $('<span class="filter-tag">' + 
                           displayName + 
                           '<button class="remove-tag" data-filter-type="' + cleanKey + '" data-filter-value="' + decodedValue + '">×</button>' +
                           '</span>');
                
                $activeContainer.append($tag);
            }
        });
        
        // Show/hide active filters container
        if ($activeContainer.children().length > 0) {
            $('.active-filters').show();
        } else {
            $('.active-filters').hide();
        }
    }
    
    function applyFilters() {
        var filters = collectFilters();
        var filterUrl = buildFilterUrl(filters);
        
        showLoadingState();
        window.location.href = filterUrl;
    }
    
    function updateFilterPreview() {
        var filters = collectFilters();
        var $previewContainer = $('.filter-preview');
        
        if ($previewContainer.length === 0) {
            $previewContainer = $('<div class="filter-preview"></div>');
            $('.filter-submit').before($previewContainer);
        }
        
        $previewContainer.empty();
        
        var hasFilters = false;
        for (var key in filters) {
            if (filters.hasOwnProperty(key)) {
                var value = filters[key];
                if (Array.isArray(value) && value.length > 0) {
                    hasFilters = true;
                    value.forEach(function(item) {
                        var displayName = getHumanReadableName(key, item);
                        $previewContainer.append('<span class="preview-tag">' + displayName + '</span>');
                    });
                } else if (value !== '') {
                    hasFilters = true;
                    $previewContainer.append('<span class="preview-tag">' + key + ': ' + value + '</span>');
                }
            }
        }
        
        if (hasFilters) {
            $previewContainer.show();
        } else {
            $previewContainer.hide();
        }
    }
    
    function resetFilters() {
        // Clear all inputs
        $('.parfume-filters input[type="checkbox"]').prop('checked', false);
        $('.parfume-filters select').val('');
        $('.parfume-filters input[type="number"], .parfume-filters input[type="text"]').val('');
        
        // Remove URL parameters and redirect to clean URL
        var baseUrl = window.location.pathname;
        if (window.location.search !== '') {
            baseUrl = baseUrl.substring(0, baseUrl.indexOf('?'));
        }
        
        window.location.href = baseUrl;
    }
    
    function removeFilter($button) {
        var filterType = $button.data('filter-type');
        var filterValue = $button.data('filter-value');
        
        if (filterType && filterValue) {
            // Uncheck the corresponding checkbox or clear select
            var $input = $('.parfume-filters input[name="' + filterType + '[]"][value="' + filterValue + '"], ' +
                          '.parfume-filters select[name="' + filterType + '"]');
            
            if ($input.is(':checkbox')) {
                $input.prop('checked', false);
            } else {
                $input.val('');
            }
            
            // Apply filters ВЕДНАГА при премахване на таг
            applyFilters();
        }
    }
    
    // ПОПРАВЕНО - функцията за toggle на filter секциите
    function toggleFilterSection($title) {
        var $options = $title.next('.filter-options');
        var $arrow = $title.find('.toggle-arrow');
        
        // Проверяваме дали опциите са скрити
        if ($options.is(':hidden') || $options.hasClass('hidden') || $options.css('display') === 'none') {
            // Показваме секцията
            $options.slideDown(300).removeClass('hidden');
            $title.removeClass('collapsed');
            if ($arrow.length > 0) {
                $arrow.text('▼');
            }
        } else {
            // Скриваме секцията
            $options.slideUp(300).addClass('hidden');
            $title.addClass('collapsed');
            if ($arrow.length > 0) {
                $arrow.text('▶');
            }
        }
    }
    
    // ПОПРАВЕНА функция за търсене в опциите на филтрите
    function filterOptions($searchInput) {
        var searchTerm = $searchInput.val().toLowerCase();
        var $filterContainer, $options;
        
        // Търсим родителския контейнер за филтрите
        $filterContainer = $searchInput.closest('.filter-section, .filter-group, .parfume-filters');
        
        if ($filterContainer.length === 0) {
            // Ако не намерим контейнер, търсим в целия документ
            $filterContainer = $('.parfume-filters');
        }
        
        // Търсим опциите в различни възможни структури
        $options = $filterContainer.find('.filter-option');
        if ($options.length === 0) {
            // Пробваме с други селектори
            $options = $filterContainer.find('.filter-options .filter-option');
        }
        if ($options.length === 0) {
            // Пробваме в scrollable-options
            $options = $filterContainer.find('.scrollable-options .filter-option');
        }
        if ($options.length === 0) {
            // Пробваме с непосредствените siblings
            $options = $searchInput.siblings('.filter-options').find('.filter-option');
        }
        if ($options.length === 0) {
            // Последна опция - търсим в родителя
            $options = $searchInput.parent().find('.filter-option');
        }
        
        // Ако все още нямаме опции, търсим по placeholder текста
        if ($options.length === 0) {
            var placeholder = $searchInput.attr('placeholder');
            if (placeholder && placeholder.includes('марки')) {
                $options = $('.parfume-filters').find('input[name="marki[]"]').closest('.filter-option');
            } else if (placeholder && placeholder.includes('нотки')) {
                $options = $('.parfume-filters').find('input[name="notes[]"]').closest('.filter-option');
            }
        }
        
        // Прилагаме филтриране ако намерим опции
        if ($options.length > 0) {
            $options.each(function() {
                var $option = $(this);
                var optionText = '';
                
                // Вземаме текста от етикета
                var $label = $option.find('label');
                if ($label.length > 0) {
                    optionText = $label.text().toLowerCase();
                } else {
                    optionText = $option.text().toLowerCase();
                }
                
                // Показваме/скриваме според търсения термин
                if (searchTerm === '' || optionText.indexOf(searchTerm) !== -1) {
                    $option.show();
                } else {
                    $option.hide();
                }
            });
        } else {
            // Debug режим - ако има проблем, логираме
            if (typeof console !== 'undefined' && console.log) {
                console.log('FilterOptions Debug: No options found for search input', $searchInput[0]);
                console.log('Filter container:', $filterContainer[0]);
                console.log('Search term:', searchTerm);
            }
        }
    }
    
    // Initialize on page load
    initializeFromUrl();
    createActiveFilterTags();
    
    // Handle browser back/forward
    window.addEventListener('popstate', function() {
        initializeFromUrl();
        createActiveFilterTags();
    });
    
    // Add URL parameter validation
    function validateUrl() {
        var currentUrl = window.location.href;
        
        // Check for malformed URLs
        if (currentUrl.indexOf('%25') !== -1) {
            // Double-encoded URLs - redirect to clean version
            var cleanUrl = decodeURIComponent(currentUrl);
            window.location.replace(cleanUrl);
            return false;
        }
        
        return true;
    }
    
    // Validate URL on load
    validateUrl();
});