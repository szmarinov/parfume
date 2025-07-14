/**
 * Parfume Reviews Admin Optimization JavaScript
 * Оптимизира зареждането на таксономии в admin
 * 
 * Файл: assets/js/admin-optimization.js
 */

(function($) {
    'use strict';
    
    // Глобални променливи
    let searchTimeout;
    let loadingStates = {};
    
    $(document).ready(function() {
        initTaxonomyOptimizations();
    });
    
    /**
     * Инициализира всички оптимизации за таксономии
     */
    function initTaxonomyOptimizations() {
        initLazyLoading();
        initSearch();
        initAddNewTerm();
        initRemoveTerm();
        initTermSelection();
        initKeyboardNavigation();
    }
    
    /**
     * Инициализира lazy loading за терми
     */
    function initLazyLoading() {
        $('.load-more-terms').on('click', function(e) {
            e.preventDefault();
            
            const $button = $(this);
            const taxonomy = $button.data('taxonomy');
            const postId = $button.data('post-id');
            let page = $button.data('page') || 1;
            
            if (loadingStates[taxonomy]) {
                return; // Вече зарежда
            }
            
            loadingStates[taxonomy] = true;
            $button.prop('disabled', true).text(parfumeAdminOpt.strings.loading);
            
            $.ajax({
                url: parfumeAdminOpt.ajaxurl,
                type: 'POST',
                data: {
                    action: 'load_taxonomy_terms',
                    taxonomy: taxonomy,
                    post_id: postId,
                    page: page,
                    nonce: parfumeAdminOpt.nonce
                },
                success: function(response) {
                    if (response.success) {
                        const $container = $('#' + taxonomy + '-all-terms .categorychecklist');
                        
                        if (page === 1) {
                            $container.empty();
                            $('#' + taxonomy + '-all-terms').show();
                        }
                        
                        $container.append(response.data.html);
                        
                        if (response.data.has_more) {
                            page++;
                            $button.data('page', page);
                            $button.text(parfumeAdminOpt.strings.load_more);
                        } else {
                            $button.hide();
                        }
                    } else {
                        showNotification('Грешка при зареждането на терми', 'error');
                    }
                },
                error: function() {
                    showNotification('Мрежова грешка', 'error');
                },
                complete: function() {
                    loadingStates[taxonomy] = false;
                    $button.prop('disabled', false);
                }
            });
        });
    }
    
    /**
     * Инициализира търсене в реално време
     */
    function initSearch() {
        $('.taxonomy-search-input').on('input', function() {
            const $input = $(this);
            const query = $input.val().trim();
            const taxonomy = $input.data('taxonomy');
            const postId = $input.data('post-id');
            const $results = $('#' + taxonomy + '-search-results');
            
            clearTimeout(searchTimeout);
            
            if (query.length < 2) {
                $results.empty().hide();
                return;
            }
            
            $results.html('<div class="search-loading">' + parfumeAdminOpt.strings.search + '</div>').show();
            
            searchTimeout = setTimeout(function() {
                $.ajax({
                    url: parfumeAdminOpt.ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'search_taxonomy_terms',
                        taxonomy: taxonomy,
                        search: query,
                        post_id: postId,
                        nonce: parfumeAdminOpt.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            $results.html(response.data.html);
                        } else {
                            $results.html('<div class="no-results">' + parfumeAdminOpt.strings.no_results + '</div>');
                        }
                    },
                    error: function() {
                        $results.html('<div class="error">Грешка при търсене</div>');
                    }
                });
            }, 300);
        });
        
        // Скрива резултатите при клик извън тях
        $(document).on('click', function(e) {
            if (!$(e.target).closest('.taxonomy-search-wrap').length) {
                $('.search-results').hide();
            }
        });
    }
    
    /**
     * Инициализира добавяне на нов терм
     */
    function initAddNewTerm() {
        $('.add-new-term').on('click', function(e) {
            e.preventDefault();
            
            const $button = $(this);
            const taxonomy = $button.data('taxonomy');
            const $input = $button.siblings('.new-term-name');
            const termName = $input.val().trim();
            
            if (!termName) {
                $input.focus();
                showNotification('Въведете име на терма', 'warning');
                return;
            }
            
            $button.prop('disabled', true).text(parfumeAdminOpt.strings.loading);
            
            $.ajax({
                url: parfumeAdminOpt.ajaxurl,
                type: 'POST',
                data: {
                    action: 'create_taxonomy_term',
                    taxonomy: taxonomy,
                    term_name: termName,
                    nonce: parfumeAdminOpt.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Добавя новия терм в избраните
                        const termId = response.data.term_id;
                        const termHtml = response.data.term_html;
                        
                        const $selectedContainer = $('#' + taxonomy + '-selected-terms');
                        if ($selectedContainer.length === 0) {
                            // Създава контейнер за избрани терми ако не съществува
                            const $newContainer = $('<div class="taxonomy-selected-terms"><h4>Избрани</h4><ul id="' + taxonomy + '-selected-terms" class="categorychecklist"></ul></div>');
                            $('#taxonomy-' + taxonomy + ' .tabs-panel').prepend($newContainer);
                        }
                        
                        $('#' + taxonomy + '-selected-terms').append(termHtml);
                        
                        // Изчиства полето
                        $input.val('');
                        
                        showNotification('Терм добавен успешно', 'success');
                    } else {
                        showNotification(response.data || 'Грешка при добавяне на терм', 'error');
                    }
                },
                error: function() {
                    showNotification('Мрежова грешка', 'error');
                },
                complete: function() {
                    $button.prop('disabled', false).text(parfumeAdminOpt.strings.add_term);
                }
            });
        });
        
        // Enter key за добавяне на терм
        $('.new-term-name').on('keypress', function(e) {
            if (e.which === 13) { // Enter key
                e.preventDefault();
                $(this).siblings('.add-new-term').click();
            }
        });
    }
    
    /**
     * Инициализира премахване на терм
     */
    function initRemoveTerm() {
        $(document).on('click', '.remove-term', function(e) {
            e.preventDefault();
            
            if (!confirm(parfumeAdminOpt.strings.confirm_remove)) {
                return;
            }
            
            const $button = $(this);
            const $listItem = $button.closest('li');
            const $checkbox = $listItem.find('input[type="checkbox"]');
            
            // Премахва чекбокса
            $checkbox.prop('checked', false);
            
            // Анимация и премахване
            $listItem.fadeOut(300, function() {
                $listItem.remove();
                updateSelectedTermsInput();
            });
        });
    }
    
    /**
     * Инициализира обработка на избор на терми
     */
    function initTermSelection() {
        $(document).on('change', 'input[type="checkbox"][name*="tax_input"]', function() {
            const $checkbox = $(this);
            const taxonomy = extractTaxonomyFromName($checkbox.attr('name'));
            
            updateSelectedTermsInput(taxonomy);
            updateSelectedTermsDisplay(taxonomy);
        });
    }
    
    /**
     * Инициализира keyboard navigation
     */
    function initKeyboardNavigation() {
        $('.categorychecklist').on('keydown', 'input[type="checkbox"]', function(e) {
            const $current = $(this);
            let $target = null;
            
            switch(e.which) {
                case 38: // Arrow Up
                    $target = $current.closest('li').prev().find('input[type="checkbox"]');
                    break;
                case 40: // Arrow Down
                    $target = $current.closest('li').next().find('input[type="checkbox"]');
                    break;
                case 32: // Space
                    e.preventDefault();
                    $current.prop('checked', !$current.prop('checked')).trigger('change');
                    break;
            }
            
            if ($target && $target.length) {
                e.preventDefault();
                $target.focus();
            }
        });
    }
    
    /**
     * Актуализира скритото поле с избрани терми
     */
    function updateSelectedTermsInput(taxonomy) {
        if (!taxonomy) return;
        
        const selectedIds = [];
        $('input[name="tax_input[' + taxonomy + '][]"]:checked').each(function() {
            selectedIds.push($(this).val());
        });
        
        $('#' + taxonomy + '-selected-input').val(selectedIds.join(','));
    }
    
    /**
     * Актуализира показването на избрани терми
     */
    function updateSelectedTermsDisplay(taxonomy) {
        const $selectedContainer = $('#' + taxonomy + '-selected-terms');
        const $checkedBoxes = $('input[name="tax_input[' + taxonomy + '][]"]:checked');
        
        if ($checkedBoxes.length > 0) {
            if ($selectedContainer.length === 0) {
                // Създава контейнер ако не съществува
                const $newContainer = $('<div class="taxonomy-selected-terms"><h4>Избрани</h4><ul id="' + taxonomy + '-selected-terms" class="categorychecklist"></ul></div>');
                $('#taxonomy-' + taxonomy + ' .tabs-panel').prepend($newContainer);
            }
            
            const $list = $('#' + taxonomy + '-selected-terms');
            $list.empty();
            
            $checkedBoxes.each(function() {
                const $checkbox = $(this);
                const termName = $checkbox.siblings().text() || $checkbox.closest('label').text();
                const termId = $checkbox.val();
                
                const $item = $('<li><label class="selectit"><input type="checkbox" name="tax_input[' + taxonomy + '][]" value="' + termId + '" checked="checked">' + termName + '<button type="button" class="remove-term" data-term-id="' + termId + '">×</button></label></li>');
                $list.append($item);
            });
        } else {
            $selectedContainer.hide();
        }
    }
    
    /**
     * Извлича името на таксономията от името на полето
     */
    function extractTaxonomyFromName(fieldName) {
        const match = fieldName.match(/tax_input\[([^\]]+)\]/);
        return match ? match[1] : null;
    }
    
    /**
     * Показва notification
     */
    function showNotification(message, type = 'info') {
        const $notification = $('<div class="notice notice-' + type + ' is-dismissible"><p>' + message + '</p><button type="button" class="notice-dismiss"><span class="screen-reader-text">Отхвърли това известие.</span></button></div>');
        
        $('.wrap h1').after($notification);
        
        // Auto-dismiss след 5 секунди
        setTimeout(function() {
            $notification.fadeOut(300, function() {
                $notification.remove();
            });
        }, 5000);
        
        // Manual dismiss
        $notification.on('click', '.notice-dismiss', function() {
            $notification.fadeOut(300, function() {
                $notification.remove();
            });
        });
    }
    
    /**
     * Дебъг функции (само в development режим)
     */
    if (typeof console !== 'undefined' && parfumeAdminOpt.debug) {
        console.log('Parfume Admin Optimization loaded');
        
        // Performance monitoring
        const perfStart = performance.now();
        $(window).on('load', function() {
            const perfEnd = performance.now();
            console.log('Admin page load time: ' + (perfEnd - perfStart) + ' milliseconds');
        });
    }
    
})(jQuery);

/**
 * Vanilla JS fallback за случаи когато jQuery не е наличен
 */
if (typeof jQuery === 'undefined') {
    console.warn('jQuery не е зареден - някои функции може да не работят правилно');
    
    // Основни fallback функции
    document.addEventListener('DOMContentLoaded', function() {
        // Lazy loading с vanilla JS
        const loadMoreButtons = document.querySelectorAll('.load-more-terms');
        loadMoreButtons.forEach(function(button) {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                console.log('Lazy loading triggered (fallback mode)');
            });
        });
        
        // Основно търсене с vanilla JS
        const searchInputs = document.querySelectorAll('.taxonomy-search-input');
        searchInputs.forEach(function(input) {
            input.addEventListener('input', function() {
                console.log('Search triggered (fallback mode):', this.value);
            });
        });
    });
}