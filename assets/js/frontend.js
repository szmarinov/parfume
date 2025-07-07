/**
 * Parfume Catalog Frontend JavaScript
 */

(function($) {
    'use strict';

    // Глобален обект за плъгина
    window.parfumeCatalog = {
        ajax_url: parfume_catalog_ajax.ajax_url,
        nonce: parfume_catalog_ajax.nonce,
        
        // Модули
        filters: {},
        comparison: {},
        comments: {},
        mobile: {},
        
        // Utility функции
        utils: {
            // Toast съобщения
            showToast: function(message, type = 'info') {
                const toastContainer = this.getToastContainer();
                const toast = $(`
                    <div class="toast ${type}">
                        <div class="toast-content">
                            <span class="toast-message">${message}</span>
                            <button class="toast-close">&times;</button>
                        </div>
                    </div>
                `);
                
                toastContainer.append(toast);
                
                // Автоматично скриване след 5 секунди
                setTimeout(() => {
                    toast.fadeOut(300, () => toast.remove());
                }, 5000);
                
                // Ръчно затваряне
                toast.find('.toast-close').on('click', () => {
                    toast.fadeOut(300, () => toast.remove());
                });
            },
            
            getToastContainer: function() {
                let container = $('.toast-container');
                if (container.length === 0) {
                    container = $('<div class="toast-container"></div>');
                    $('body').append(container);
                }
                return container;
            },
            
            // Проверка за мобилно устройство
            isMobile: function() {
                return window.innerWidth <= 768;
            },
            
            // Debounce функция
            debounce: function(func, wait) {
                let timeout;
                return function executedFunction(...args) {
                    const later = () => {
                        clearTimeout(timeout);
                        func(...args);
                    };
                    clearTimeout(timeout);
                    timeout = setTimeout(later, wait);
                };
            },
            
            // Копиране в clipboard
            copyToClipboard: function(text) {
                if (navigator.clipboard) {
                    navigator.clipboard.writeText(text).then(() => {
                        this.showToast('Копирано в клипборда!', 'success');
                    });
                } else {
                    // Fallback за стари браузъри
                    const textArea = document.createElement('textarea');
                    textArea.value = text;
                    document.body.appendChild(textArea);
                    textArea.focus();
                    textArea.select();
                    try {
                        document.execCommand('copy');
                        this.showToast('Копирано в клипборда!', 'success');
                    } catch (err) {
                        this.showToast('Грешка при копиране', 'error');
                    }
                    document.body.removeChild(textArea);
                }
            }
        },
        
        // Наскоро разгледани парфюми
        addToRecentlyViewed: function(parfume) {
            let recent = JSON.parse(localStorage.getItem('parfume_recently_viewed') || '[]');
            
            // Премахване ако вече съществува
            recent = recent.filter(item => item.id !== parfume.id);
            
            // Добавяне в началото
            recent.unshift(parfume);
            
            // Ограничаване до 10 елемента
            recent = recent.slice(0, 10);
            
            localStorage.setItem('parfume_recently_viewed', JSON.stringify(recent));
        },
        
        loadRecentlyViewed: function(containerId, limit = 4) {
            const container = $('#' + containerId);
            const recent = JSON.parse(localStorage.getItem('parfume_recently_viewed') || '[]');
            
            if (recent.length === 0) {
                container.html('<p>Няма наскоро разгледани парфюми.</p>');
                return;
            }
            
            const items = recent.slice(0, limit);
            let html = '<div class="recently-viewed-grid">';
            
            items.forEach(item => {
                html += `
                    <div class="recently-viewed-item">
                        <a href="${item.url}">
                            ${item.image ? 
                                `<img src="${item.image}" alt="${item.title}" class="recently-viewed-image">` :
                                '<div class="recently-viewed-no-image"><i class="parfume-icon-bottle"></i></div>'
                            }
                            <h4 class="recently-viewed-title">${item.title}</h4>
                        </a>
                    </div>
                `;
            });
            
            html += '</div>';
            container.html(html);
        }
    };

    // ===== ФИЛТРИ МОДУЛ ===== 
    parfumeCatalog.filters = {
        init: function() {
            this.bindEvents();
            this.initAutocomplete();
            this.loadFiltersFromURL();
        },
        
        bindEvents: function() {
            const self = this;
            
            // Промяна на филтри
            $(document).on('change', '.filter-select, .filter-input', function() {
                self.applyFilters();
            });
            
            // Reset бутон
            $(document).on('click', '.btn-reset-filters', function(e) {
                e.preventDefault();
                self.resetFilters();
            });
            
            // Search полето
            $(document).on('input', '.filter-search', parfumeCatalog.utils.debounce(function() {
                self.applyFilters();
            }, 500));
        },
        
        applyFilters: function() {
            const filters = this.getFilterValues();
            const queryString = this.buildQueryString(filters);
            
            // AJAX заявка за нови резултати
            this.loadResults(queryString);
            
            // Обновяване на URL без reload
            if (history.pushState) {
                const newUrl = window.location.pathname + '?' + queryString;
                history.pushState({}, '', newUrl);
            }
        },
        
        applySorting: function(sortValue) {
            const filters = this.getFilterValues();
            filters.orderby = sortValue;
            
            const queryString = this.buildQueryString(filters);
            this.loadResults(queryString);
            
            if (history.pushState) {
                const newUrl = window.location.pathname + '?' + queryString;
                history.pushState({}, '', newUrl);
            }
        },
        
        getFilterValues: function() {
            const filters = {};
            
            $('.filter-select, .filter-input').each(function() {
                const $this = $(this);
                const name = $this.attr('name') || $this.data('filter');
                const value = $this.val();
                
                if (value && value !== '') {
                    filters[name] = value;
                }
            });
            
            return filters;
        },
        
        buildQueryString: function(filters) {
            const params = new URLSearchParams();
            
            for (const [key, value] of Object.entries(filters)) {
                if (Array.isArray(value)) {
                    value.forEach(v => params.append(key + '[]', v));
                } else {
                    params.append(key, value);
                }
            }
            
            return params.toString();
        },
        
        loadResults: function(queryString) {
            const $resultsContainer = $('#parfume-results');
            const $loadingIndicator = $('#loading-indicator');
            
            $loadingIndicator.show();
            $resultsContainer.css('opacity', '0.6');
            
            $.ajax({
                url: parfumeCatalog.ajax_url,
                type: 'POST',
                data: {
                    action: 'parfume_filter_results',
                    nonce: parfumeCatalog.nonce,
                    query: queryString,
                    current_url: window.location.pathname
                },
                success: function(response) {
                    if (response.success) {
                        $resultsContainer.html(response.data.html);
                        
                        // Reinit comparison buttons
                        parfumeCatalog.comparison.initButtons();
                        
                        // Scroll to results
                        $('html, body').animate({
                            scrollTop: $resultsContainer.offset().top - 100
                        }, 500);
                    } else {
                        parfumeCatalog.utils.showToast('Грешка при зареждане на резултатите', 'error');
                    }
                },
                error: function() {
                    parfumeCatalog.utils.showToast('Грешка при връзката със сървъра', 'error');
                },
                complete: function() {
                    $loadingIndicator.hide();
                    $resultsContainer.css('opacity', '1');
                }
            });
        },
        
        resetFilters: function() {
            $('.filter-select').val('');
            $('.filter-input').val('');
            
            // Redirect to clean URL
            window.location.href = window.location.pathname;
        },
        
        loadFiltersFromURL: function() {
            const urlParams = new URLSearchParams(window.location.search);
            
            urlParams.forEach((value, key) => {
                const $field = $(`[name="${key}"], [data-filter="${key}"]`);
                if ($field.length) {
                    $field.val(value);
                }
            });
        },
        
        initAutocomplete: function() {
            $('.filter-autocomplete').each(function() {
                const $input = $(this);
                const taxonomy = $input.data('taxonomy');
                
                $input.autocomplete({
                    source: function(request, response) {
                        $.ajax({
                            url: parfumeCatalog.ajax_url,
                            type: 'POST',
                            data: {
                                action: 'parfume_autocomplete',
                                nonce: parfumeCatalog.nonce,
                                taxonomy: taxonomy,
                                term: request.term
                            },
                            success: function(data) {
                                if (data.success) {
                                    response(data.data);
                                }
                            }
                        });
                    },
                    minLength: 2,
                    select: function(event, ui) {
                        $input.val(ui.item.label);
                        parfumeCatalog.filters.applyFilters();
                        return false;
                    }
                });
            });
        }
    };

    // ===== COMPARISON МОДУЛ =====
    parfumeCatalog.comparison = {
        maxItems: 4,
        
        init: function() {
            this.maxItems = parseInt(parfume_catalog_ajax.comparison_max_items) || 4;
            this.initButtons();
            this.initPopup();
            this.loadComparisonFromStorage();
        },
        
        initButtons: function() {
            const self = this;
            
            $('.parfume-compare-btn').each(function() {
                const $btn = $(this);
                const parfumeId = $btn.data('parfume-id');
                
                // Проверка дали е вече добавен
                if (self.isInComparison(parfumeId)) {
                    self.updateButtonState($btn, true);
                }
                
                $btn.off('click.comparison').on('click.comparison', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    const parfumeId = $(this).data('parfume-id');
                    self.toggleParfume(parfumeId, $(this));
                });
            });
        },
        
        toggleParfume: function(parfumeId, $btn) {
            if (this.isInComparison(parfumeId)) {
                this.removeFromComparison(parfumeId);
                this.updateButtonState($btn, false);
                parfumeCatalog.utils.showToast('Премахнато от сравнението', 'info');
            } else {
                if (this.getComparisonItems().length >= this.maxItems) {
                    parfumeCatalog.utils.showToast(`Максимум ${this.maxItems} парфюма за сравнение`, 'warning');
                    return;
                }
                
                this.addToComparison(parfumeId, $btn);
                this.updateButtonState($btn, true);
                parfumeCatalog.utils.showToast('Добавено за сравнение', 'success');
            }
            
            this.updateComparisonPopup();
        },
        
        addToComparison: function(parfumeId, $btn) {
            const parfumeData = this.extractParfumeData($btn);
            let comparison = this.getComparisonItems();
            
            comparison.push({
                id: parfumeId,
                title: parfumeData.title,
                brand: parfumeData.brand,
                image: parfumeData.image,
                url: parfumeData.url
            });
            
            localStorage.setItem('parfume_comparison', JSON.stringify(comparison));
            this.updateAllButtons();
        },
        
        removeFromComparison: function(parfumeId) {
            let comparison = this.getComparisonItems();
            comparison = comparison.filter(item => item.id != parfumeId);
            localStorage.setItem('parfume_comparison', JSON.stringify(comparison));
            this.updateAllButtons();
        },
        
        extractParfumeData: function($btn) {
            const $card = $btn.closest('.parfume-card, .parfume-header, .brand-parfume-item');
            
            return {
                title: $card.find('.parfume-card-title a, .parfume-title, .brand-parfume-title').first().text().trim(),
                brand: $card.find('.parfume-brand a, .parfume-meta .parfume-brand').first().text().trim(),
                image: $card.find('.parfume-thumbnail, .parfume-featured-image, .brand-parfume-image').first().attr('src') || '',
                url: $card.find('.parfume-card-title a, .brand-parfume-item a').first().attr('href') || window.location.href
            };
        },
        
        isInComparison: function(parfumeId) {
            const comparison = this.getComparisonItems();
            return comparison.some(item => item.id == parfumeId);
        },
        
        getComparisonItems: function() {
            return JSON.parse(localStorage.getItem('parfume_comparison') || '[]');
        },
        
        updateButtonState: function($btn, isAdded) {
            const $text = $btn.find('.compare-text');
            
            if (isAdded) {
                $btn.addClass('added');
                if ($text.length) {
                    $text.text('Премахни от сравнение');
                }
            } else {
                $btn.removeClass('added');
                if ($text.length) {
                    $text.text('Добави за сравнение');
                }
            }
        },
        
        updateAllButtons: function() {
            const self = this;
            $('.parfume-compare-btn').each(function() {
                const $btn = $(this);
                const parfumeId = $btn.data('parfume-id');
                const isAdded = self.isInComparison(parfumeId);
                self.updateButtonState($btn, isAdded);
            });
        },
        
        initPopup: function() {
            const self = this;
            
            // Създаване на popup структурата
            if ($('.comparison-overlay').length === 0) {
                $('body').append(`
                    <div class="comparison-overlay"></div>
                    <div class="comparison-popup">
                        <div class="comparison-header">
                            <h2 class="comparison-title">Сравнение на парфюми</h2>
                            <button class="comparison-close">&times;</button>
                        </div>
                        <div class="comparison-content">
                            <div class="comparison-table-container"></div>
                        </div>
                    </div>
                `);
            }
            
            // Event listeners
            $(document).on('click', '.comparison-overlay, .comparison-close', function() {
                self.closePopup();
            });
            
            $(document).on('click', '.remove-from-comparison', function() {
                const parfumeId = $(this).data('parfume-id');
                self.removeFromComparison(parfumeId);
                self.updateComparisonPopup();
                parfumeCatalog.utils.showToast('Премахнато от сравнението', 'info');
            });
            
            // Показване на popup при имащи повече от 1 парфюм
            this.checkAutoShowPopup();
        },
        
        updateComparisonPopup: function() {
            const items = this.getComparisonItems();
            
            if (items.length === 0) {
                this.closePopup();
                return;
            }
            
            if (items.length >= 2) {
                this.loadComparisonData(items);
            }
        },
        
        loadComparisonData: function(items) {
            const self = this;
            
            $.ajax({
                url: parfumeCatalog.ajax_url,
                type: 'POST',
                data: {
                    action: 'parfume_get_comparison_data',
                    nonce: parfumeCatalog.nonce,
                    parfume_ids: items.map(item => item.id)
                },
                success: function(response) {
                    if (response.success) {
                        self.renderComparisonTable(response.data);
                        self.showPopup();
                    }
                },
                error: function() {
                    parfumeCatalog.utils.showToast('Грешка при зареждане на данните за сравнение', 'error');
                }
            });
        },
        
        renderComparisonTable: function(data) {
            let html = '<table class="comparison-table">';
            
            // Header с парфюмите
            html += '<thead><tr><th>Характеристика</th>';
            data.parfumes.forEach(parfume => {
                html += `
                    <th>
                        <div class="parfume-comparison-item">
                            <img src="${parfume.image}" alt="${parfume.title}" class="comparison-parfume-image">
                            <div class="comparison-parfume-title">${parfume.title}</div>
                            <div class="comparison-parfume-brand">${parfume.brand}</div>
                            <button class="remove-from-comparison" data-parfume-id="${parfume.id}">&times;</button>
                        </div>
                    </th>
                `;
            });
            html += '</tr></thead>';
            
            // Body с характеристиките
            html += '<tbody>';
            
            data.criteria.forEach(criterion => {
                html += `<tr><td><strong>${criterion.label}</strong></td>`;
                
                data.parfumes.forEach(parfume => {
                    const value = parfume.characteristics[criterion.key] || '-';
                    html += `<td>${value}</td>`;
                });
                
                html += '</tr>';
            });
            
            html += '</tbody></table>';
            
            $('.comparison-table-container').html(html);
        },
        
        showPopup: function() {
            $('.comparison-overlay').addClass('active');
            $('.comparison-popup').addClass('active');
            $('body').addClass('comparison-open');
        },
        
        closePopup: function() {
            $('.comparison-overlay').removeClass('active');
            $('.comparison-popup').removeClass('active');
            $('body').removeClass('comparison-open');
        },
        
        checkAutoShowPopup: function() {
            const items = this.getComparisonItems();
            if (items.length >= 2) {
                // Auto show popup след кратка забавка
                setTimeout(() => {
                    this.loadComparisonData(items);
                }, 1000);
            }
        },
        
        loadComparisonFromStorage: function() {
            this.updateAllButtons();
            this.checkAutoShowPopup();
        }
    };

    // ===== COMMENTS МОДУЛ =====
    parfumeCatalog.comments = {
        init: function() {
            this.bindEvents();
            this.initRating();
        },
        
        bindEvents: function() {
            const self = this;
            
            // Форма за коментар
            $(document).on('submit', '.parfume-comment-form', function(e) {
                e.preventDefault();
                self.submitComment($(this));
            });
            
            // Rating звезди
            $(document).on('click', '.rating-input .star', function() {
                self.setRating($(this));
            });
            
            $(document).on('mouseenter', '.rating-input .star', function() {
                self.highlightRating($(this));
            });
            
            $(document).on('mouseleave', '.rating-input', function() {
                self.resetRatingHighlight($(this));
            });
        },
        
        initRating: function() {
            $('.rating-input').each(function() {
                const $container = $(this);
                const currentRating = parseInt($container.data('rating')) || 0;
                
                $container.find('.star').each(function(index) {
                    if (index < currentRating) {
                        $(this).addClass('filled');
                    }
                });
            });
        },
        
        setRating: function($star) {
            const $container = $star.closest('.rating-input');
            const rating = $star.data('rating');
            
            $container.data('rating', rating);
            $container.find('input[name="rating"]').val(rating);
            
            $container.find('.star').each(function(index) {
                if (index < rating) {
                    $(this).addClass('filled');
                } else {
                    $(this).removeClass('filled');
                }
            });
        },
        
        highlightRating: function($star) {
            const $container = $star.closest('.rating-input');
            const rating = $star.data('rating');
            
            $container.find('.star').each(function(index) {
                if (index < rating) {
                    $(this).addClass('highlight');
                } else {
                    $(this).removeClass('highlight');
                }
            });
        },
        
        resetRatingHighlight: function($container) {
            $container.find('.star').removeClass('highlight');
        },
        
        submitComment: function($form) {
            const $submitBtn = $form.find('[type="submit"]');
            const originalText = $submitBtn.text();
            
            $submitBtn.prop('disabled', true).text('Изпраща се...');
            
            $.ajax({
                url: parfumeCatalog.ajax_url,
                type: 'POST',
                data: $form.serialize() + '&action=parfume_submit_comment&nonce=' + parfumeCatalog.nonce,
                success: function(response) {
                    if (response.success) {
                        parfumeCatalog.utils.showToast(response.data.message, 'success');
                        $form[0].reset();
                        $('.rating-input').data('rating', 0).find('.star').removeClass('filled');
                        
                        // Презареждане на коментарите ако е необходимо
                        if (response.data.approved) {
                            location.reload();
                        }
                    } else {
                        parfumeCatalog.utils.showToast(response.data.message, 'error');
                    }
                },
                error: function() {
                    parfumeCatalog.utils.showToast('Грешка при изпращането на коментара', 'error');
                },
                complete: function() {
                    $submitBtn.prop('disabled', false).text(originalText);
                }
            });
        }
    };

    // ===== MOBILE МОДУЛ =====
    parfumeCatalog.mobile = {
        init: function() {
            if (parfumeCatalog.utils.isMobile()) {
                this.initMobileStores();
                this.initMobileNavigation();
            }
            
            // Resize event
            $(window).on('resize', parfumeCatalog.utils.debounce(() => {
                if (parfumeCatalog.utils.isMobile()) {
                    this.initMobileStores();
                } else {
                    this.destroyMobileStores();
                }
            }, 250));
        },
        
        initMobileStores: function() {
            const $sidebar = $('.parfume-stores-sidebar');
            
            if ($sidebar.length && !$sidebar.hasClass('mobile-initialized')) {
                $sidebar.addClass('mobile-initialized mobile-fixed');
                
                // Wrap съдържанието
                const content = $sidebar.html();
                $sidebar.html(`
                    <div class="mobile-stores-toggle">
                        <span class="mobile-stores-title">Сравни цените</span>
                        <div class="mobile-controls">
                            <button class="mobile-close-btn">&times;</button>
                            <button class="mobile-toggle-btn">
                                <i class="arrow-up"></i>
                            </button>
                        </div>
                    </div>
                    <div class="mobile-stores-content">
                        ${content}
                    </div>
                `);
                
                // Hidden indicator
                if ($('.mobile-hidden-indicator').length === 0) {
                    $('body').append('<button class="mobile-hidden-indicator"><i class="icon-stores"></i></button>');
                }
                
                this.bindMobileEvents();
            }
        },
        
        bindMobileEvents: function() {
            const self = this;
            
            // Toggle съдържанието
            $(document).on('click', '.mobile-stores-toggle', function() {
                const $content = $('.mobile-stores-content');
                const $btn = $('.mobile-toggle-btn i');
                
                if ($content.hasClass('expanded')) {
                    $content.removeClass('expanded');
                    $btn.removeClass('arrow-down').addClass('arrow-up');
                } else {
                    $content.addClass('expanded');
                    $btn.removeClass('arrow-up').addClass('arrow-down');
                }
            });
            
            // Скриване на панела
            $(document).on('click', '.mobile-close-btn', function() {
                $('.parfume-stores-sidebar').hide();
                $('.mobile-hidden-indicator').show();
            });
            
            // Показване на панела отново
            $(document).on('click', '.mobile-hidden-indicator', function() {
                $('.parfume-stores-sidebar').show();
                $('.mobile-hidden-indicator').hide();
            });
        },
        
        destroyMobileStores: function() {
            const $sidebar = $('.parfume-stores-sidebar');
            
            if ($sidebar.hasClass('mobile-initialized')) {
                const content = $sidebar.find('.mobile-stores-content').html();
                $sidebar.removeClass('mobile-initialized mobile-fixed').html(content);
                $('.mobile-hidden-indicator').remove();
            }
        },
        
        initMobileNavigation: function() {
            // Smooth scroll за азбучната навигация
            $('.alphabet-link').on('click', function(e) {
                if (parfumeCatalog.utils.isMobile()) {
                    e.preventDefault();
                    const target = $(this).attr('href');
                    const $target = $(target);
                    
                    if ($target.length) {
                        $('html, body').animate({
                            scrollTop: $target.offset().top - 20
                        }, 500);
                    }
                }
            });
        }
    };

    // ===== PROMO CODE FUNCTIONALITY =====
    parfumeCatalog.promoCodes = {
        init: function() {
            this.bindEvents();
        },
        
        bindEvents: function() {
            $(document).on('click', '.btn-promo', function(e) {
                e.preventDefault();
                
                const $btn = $(this);
                const promoCode = $btn.find('.promo-code').text().trim();
                const promoUrl = $btn.data('promo-url') || $btn.closest('.store-offer').find('.btn-to-store').attr('href');
                
                if (promoCode) {
                    // Копиране на кода
                    parfumeCatalog.utils.copyToClipboard(promoCode);
                    
                    // Визуален feedback
                    const originalText = $btn.find('.promo-code').text();
                    $btn.find('.promo-code').text('Копирано!');
                    $btn.addClass('copied');
                    
                    setTimeout(() => {
                        $btn.find('.promo-code').text(originalText);
                        $btn.removeClass('copied');
                    }, 2000);
                    
                    // Пренасочване след кратка пауза
                    setTimeout(() => {
                        if (promoUrl) {
                            window.open(promoUrl, '_blank');
                        }
                    }, 1000);
                }
            });
        }
    };

    // ===== ИНИЦИАЛИЗАЦИЯ =====
    $(document).ready(function() {
        // Инициализация на всички модули
        parfumeCatalog.filters.init();
        parfumeCatalog.comparison.init();
        parfumeCatalog.comments.init();
        parfumeCatalog.mobile.init();
        parfumeCatalog.promoCodes.init();
        
        // Общи events
        
        // External links
        $('a[target="_blank"]').each(function() {
            if (!$(this).attr('rel')) {
                $(this).attr('rel', 'noopener noreferrer');
            }
        });
        
        // Lazy loading за изображения (ако няма native support)
        if ('loading' in HTMLImageElement.prototype === false) {
            // Fallback за стари браузъри
            $('img[loading="lazy"]').each(function() {
                const $img = $(this);
                const observer = new IntersectionObserver((entries) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            const img = entry.target;
                            img.src = img.dataset.src || img.src;
                            observer.unobserve(img);
                        }
                    });
                });
                observer.observe(this);
            });
        }
        
        // Smooth scroll за anchor links
        $('a[href^="#"]').on('click', function(e) {
            const target = $(this).attr('href');
            const $target = $(target);
            
            if ($target.length) {
                e.preventDefault();
                $('html, body').animate({
                    scrollTop: $target.offset().top - 100
                }, 500);
            }
        });
        
        // Back to top button (ако съществува)
        const $backToTop = $('.back-to-top');
        if ($backToTop.length) {
            $(window).on('scroll', function() {
                if ($(this).scrollTop() > 300) {
                    $backToTop.fadeIn();
                } else {
                    $backToTop.fadeOut();
                }
            });
            
            $backToTop.on('click', function(e) {
                e.preventDefault();
                $('html, body').animate({scrollTop: 0}, 500);
            });
        }
        
        // Tooltips инициализация (ако използваме)
        if ($.fn.tooltip) {
            $('[data-tooltip]').tooltip();
        }
        
        // Form validation helpers
        $('.required').on('blur', function() {
            const $field = $(this);
            if (!$field.val().trim()) {
                $field.addClass('error');
            } else {
                $field.removeClass('error');
            }
        });
        
        // Auto-resize textareas
        $('textarea.auto-resize').on('input', function() {
            this.style.height = 'auto';
            this.style.height = (this.scrollHeight) + 'px';
        });
    });

    // ===== WINDOW EVENTS =====
    $(window).on('load', function() {
        // Премахване на loading класове ако има такива
        $('body').removeClass('loading');
        
        // Trigger custom event за други скриптове
        $(document).trigger('parfumeCatalogLoaded');
    });

    // Browser back/forward поддръжка за филтрите
    window.addEventListener('popstate', function(event) {
        if (window.location.pathname.includes('parfum')) {
            parfumeCatalog.filters.loadFiltersFromURL();
            parfumeCatalog.filters.applyFilters();
        }
    });

})(jQuery);