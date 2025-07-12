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
        
        // Handle expandable filter sections
        $('.filter-title').on('click', function() {
            toggleFilterSection($(this));
        });
        
        // Handle filter search
        $('.filter-search').on('input', function() {
            filterOptions($(this));
        });
    }
    
    function applyFilters() {
        var filters = collectFilters();
        var url = buildFilterUrl(filters);
        
        // Show loading state
        showLoadingState();
        
        // Navigate to filtered URL
        window.location.href = url;
    }
    
    // НОВА ФУНКЦИЯ ЗА PREVIEW НА ФИЛТРИТЕ БЕЗ ПРЕНАСОЧВАНЕ
    function updateFilterPreview() {
        var filters = collectFilters();
        var selectedCount = 0;
        
        // Преброяваме избраните филтри
        for (var key in filters) {
            if (filters.hasOwnProperty(key)) {
                if (Array.isArray(filters[key])) {
                    selectedCount += filters[key].length;
                } else if (filters[key] !== '') {
                    selectedCount++;
                }
            }
        }
        
        // Актуализираме бутона за филтриране
        var $submitBtn = $('.filter-submit .button-primary, .filter-button');
        if (selectedCount > 0) {
            $submitBtn.text('Филтрирай (' + selectedCount + ')').addClass('has-filters');
        } else {
            $submitBtn.text('Филтрирай').removeClass('has-filters');
        }
        
        // Показваме preview на избраните филтри (опционално)
        createFilterPreview(filters);
    }
    
    // НОВА ФУНКЦИЯ ЗА PREVIEW НА ИЗБРАНИТЕ ФИЛТРИ
    function createFilterPreview(filters) {
        var $previewContainer = $('.filter-preview');
        if ($previewContainer.length === 0) {
            // Създаваме контейнер за preview ако не съществува
            $previewContainer = $('<div class="filter-preview" style="margin-top: 15px; padding: 10px; background: #f8f9fa; border-radius: 6px; display: none;"></div>');
            $('.filter-submit').before($previewContainer);
        }
        
        $previewContainer.empty();
        
        var hasFilters = false;
        var previewHtml = '<small><strong>Избрани филтри:</strong> ';
        var previewItems = [];
        
        for (var key in filters) {
            if (filters.hasOwnProperty(key)) {
                var value = filters[key];
                if (Array.isArray(value) && value.length > 0) {
                    hasFilters = true;
                    var humanReadableValues = [];
                    
                    // Получаваме човешки четими имена за всяка стойност
                    value.forEach(function(val) {
                        var humanName = getHumanReadableName(key, val);
                        humanReadableValues.push(humanName);
                    });
                    
                    var filterLabel = getFilterLabel(key);
                    previewItems.push(filterLabel + ': ' + humanReadableValues.join(', '));
                } else if (value !== '') {
                    hasFilters = true;
                    var humanName = getHumanReadableName(key, value);
                    var filterLabel = getFilterLabel(key);
                    previewItems.push(filterLabel + ': ' + humanName);
                }
            }
        }
        
        if (hasFilters) {
            previewHtml += previewItems.join(' | ') + '</small>';
            $previewContainer.html(previewHtml).show();
        } else {
            $previewContainer.hide();
        }
    }
    
    // НОВА ФУНКЦИЯ ЗА ПОЛУЧАВАНЕ НА ЧОВЕШКИ ЧЕТИМИ ИМЕНА
    function getHumanReadableName(filterType, value) {
        // Първо декодираме URL encoding
        var decodedValue = decodeURIComponent(value);
        
        // Търсим съответния label в DOM
        var $matchingOption = $('.parfume-filters input[name="' + filterType + '[]"][value="' + value + '"]').closest('.filter-option');
        if ($matchingOption.length === 0) {
            // Пробваме с декодираната стойност
            $matchingOption = $('.parfume-filters input[value="' + decodedValue + '"]').closest('.filter-option');
        }
        
        if ($matchingOption.length > 0) {
            var labelText = $matchingOption.find('label').clone();
            // Премахваме count-а ако има
            labelText.find('.count, .filter-count').remove();
            var cleanText = labelText.text().trim();
            if (cleanText) {
                return cleanText;
            }
        }
        
        // Ако не намерим в DOM, пробваме да декодираме и форматираме
        try {
            // Двойно декодиране за случай че е двойно кодирано
            var doubleDecoded = decodeURIComponent(decodedValue);
            if (doubleDecoded !== decodedValue) {
                return doubleDecoded;
            }
        } catch (e) {
            // Игнорираме грешките при декодиране
        }
        
        // Ако всичко останало не е работило, поне заменяме тиретата с интервали и капитализираме
        return decodedValue.replace(/-/g, ' ').replace(/\b\w/g, function(l) { return l.toUpperCase(); });
    }
    
    // НОВА ФУНКЦИЯ ЗА ПОЛУЧАВАНЕ НА LABELS НА ФИЛТРИТЕ
    function getFilterLabel(filterType) {
        var filterLabels = {
            'gender': 'Категория',
            'aroma_type': 'Тип арома', 
            'marki': 'Марка',
            'season': 'Сезон',
            'intensity': 'Интензивност',
            'notes': 'Нотки',
            'perfumer': 'Парфюмер',
            'min_price': 'Мин. цена',
            'max_price': 'Макс. цена',
            'min_rating': 'Мин. рейтинг',
            'orderby': 'Сортиране',
            'order': 'Посока'
        };
        
        return filterLabels[filterType] || filterType;
    }
    
    function collectFilters() {
        var filters = {};
        
        // Collect taxonomy filters
        $('.parfume-filters input[type="checkbox"]:checked').each(function() {
            var name = $(this).attr('name');
            var value = $(this).val();
            
            if (name && value) {
                // Remove [] from name if present
                var cleanName = name.replace('[]', '');
                
                if (!filters[cleanName]) {
                    filters[cleanName] = [];
                }
                filters[cleanName].push(value);
            }
        });
        
        // Collect select filters
        $('.parfume-filters select').each(function() {
            var name = $(this).attr('name');
            var value = $(this).val();
            
            if (name && value && value !== '') {
                filters[name] = value;
            }
        });
        
        // Collect price range
        var minPrice = $('.parfume-filters input[name="min_price"]').val();
        var maxPrice = $('.parfume-filters input[name="max_price"]').val();
        
        if (minPrice && minPrice !== '') {
            filters.min_price = minPrice;
        }
        if (maxPrice && maxPrice !== '') {
            filters.max_price = maxPrice;
        }
        
        // Collect rating filter
        var minRating = $('.parfume-filters input[name="min_rating"]').val();
        if (minRating && minRating !== '') {
            filters.min_rating = minRating;
        }
        
        // Collect sorting
        var orderby = $('.parfume-filters select[name="orderby"]').val();
        var order = $('.parfume-filters select[name="order"]').val();
        
        if (orderby && orderby !== '') {
            filters.orderby = orderby;
        }
        if (order && order !== '') {
            filters.order = order;
        }
        
        return filters;
    }
    
    function buildFilterUrl(filters) {
        // Get current base URL
        var baseUrl = window.location.pathname;
        
        // Remove any existing query parameters from base URL
        if (baseUrl.indexOf('?') !== -1) {
            baseUrl = baseUrl.substring(0, baseUrl.indexOf('?'));
        }
        
        // Build query string
        var queryParams = [];
        
        for (var key in filters) {
            if (filters.hasOwnProperty(key)) {
                var value = filters[key];
                
                if (Array.isArray(value)) {
                    // Multiple values for same parameter
                    value.forEach(function(val) {
                        queryParams.push(encodeURIComponent(key + '[]') + '=' + encodeURIComponent(val));
                    });
                } else {
                    // Single value
                    queryParams.push(encodeURIComponent(key) + '=' + encodeURIComponent(value));
                }
            }
        }
        
        var queryString = queryParams.join('&');
        var finalUrl = baseUrl + (queryString ? '?' + queryString : '');
        
        console.log('Built filter URL:', finalUrl);
        return finalUrl;
    }
    
    function resetFilters() {
        // Clear all form fields
        $('.parfume-filters input[type="checkbox"]').prop('checked', false);
        $('.parfume-filters select').val('');
        $('.parfume-filters input[type="text"], .parfume-filters input[type="number"]').val('');
        
        // Hide preview
        $('.filter-preview').hide();
        
        // Reset button text
        $('.filter-submit .button-primary, .filter-button').text('Филтрирай').removeClass('has-filters');
        
        // Navigate to clean URL
        var baseUrl = window.location.pathname;
        if (baseUrl.indexOf('?') !== -1) {
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
    
    function toggleFilterSection($title) {
        var $options = $title.next('.filter-options');
        var $arrow = $title.find('.toggle-arrow');
        
        if ($options.is(':visible')) {
            $options.slideUp(300);
            $title.addClass('collapsed');
            $arrow.text('▶');
        } else {
            $options.slideDown(300);
            $title.removeClass('collapsed');
            $arrow.text('▼');
        }
    }
    
    function filterOptions($searchInput) {
        var searchTerm = $searchInput.val().toLowerCase();
        var $options = $searchInput.siblings('.filter-options').find('.filter-option');
        
        $options.each(function() {
            var $option = $(this);
            var optionText = $option.find('label').text().toLowerCase();
            
            if (optionText.indexOf(searchTerm) !== -1) {
                $option.show();
            } else {
                $option.hide();
            }
        });
    }
    
    function showLoadingState() {
        $('.parfume-filters').addClass('filters-loading');
        $('.filter-submit .button-primary, .filter-button').text('Зареждане...');
    }
    
    function hideLoadingState() {
        $('.parfume-filters').removeClass('filters-loading');
        $('.filter-submit .button-primary, .filter-button').text('Филтрирай');
    }
    
    // Initialize filter state from URL on page load
    function initializeFromUrl() {
        var urlParams = new URLSearchParams(window.location.search);
        
        // Set checkbox values
        urlParams.forEach(function(value, key) {
            if (key.endsWith('[]')) {
                var cleanKey = key.replace('[]', '');
                var $checkbox = $('.parfume-filters input[name="' + key + '"][value="' + decodeURIComponent(value) + '"]');
                $checkbox.prop('checked', true);
            } else {
                var $input = $('.parfume-filters input[name="' + key + '"], .parfume-filters select[name="' + key + '"]');
                $input.val(decodeURIComponent(value));
            }
        });
        
        // Актуализираме preview при зареждане
        updateFilterPreview();
    }
    
    // Create active filter tags
    function createActiveFilterTags() {
        var $activeContainer = $('.active-filters .filter-tags');
        if ($activeContainer.length === 0) {
            return;
        }
        
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