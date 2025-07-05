/**
 * Filters functionality for Parfume Reviews
 * Handles AJAX filtering, search, and sorting
 */

(function($) {
    'use strict';
    
    let filterTimeout;
    let currentRequest;
    
    const ParfumeFilters = {
        init: function() {
            this.bindEvents();
            this.initializeFilters();
            this.setupSearch();
            this.setupSorting();
            this.setupViewToggle();
        },
        
        bindEvents: function() {
            // Filter form submission
            $(document).on('submit', '#parfume-filters-form', this.handleFormSubmit.bind(this));
            
            // Filter changes
            $(document).on('change', '.filter-checkbox, .filter-select', this.handleFilterChange.bind(this));
            
            // Search input
            $(document).on('input', '.filter-search', this.handleSearchInput.bind(this));
            
            // Clear filters
            $(document).on('click', '.clear-filters', this.clearAllFilters.bind(this));
            
            // Toggle filter sections
            $(document).on('click', '.filter-title', this.toggleFilterSection.bind(this));
            
            // "Select All" functionality
            $(document).on('change', '.select-all', this.handleSelectAll.bind(this));
            
            // Price range
            $(document).on('change', '.price-range', this.handlePriceRange.bind(this));
            
            // Load more button
            $(document).on('click', '.load-more-btn', this.loadMore.bind(this));
        },
        
        initializeFilters: function() {
            // Restore saved filters from URL or localStorage
            this.restoreFiltersFromURL();
            
            // Initialize range sliders
            this.initRangeSliders();
            
            // Initialize collapsible sections
            this.initCollapsibleSections();
            
            // Update results count
            this.updateResultsCount();
        },
        
        setupSearch: function() {
            const searchInputs = $('.filter-search');
            
            searchInputs.each(function() {
                const $input = $(this);
                const $options = $input.siblings('.scrollable-options').find('.filter-option');
                
                $input.on('input', function() {
                    const query = this.value.toLowerCase();
                    
                    $options.each(function() {
                        const text = $(this).text().toLowerCase();
                        $(this).toggle(text.includes(query));
                    });
                });
            });
        },
        
        setupSorting: function() {
            $('#parfume-sort').on('change', function() {
                const sortValue = $(this).val();
                ParfumeFilters.updateURL({ sort: sortValue });
                ParfumeFilters.filterResults();
            });
        },
        
        setupViewToggle: function() {
            $('.view-btn').on('click', function() {
                const view = $(this).data('view');
                
                $('.view-btn').removeClass('active');
                $(this).addClass('active');
                
                $('.parfume-grid').attr('data-view', view);
                
                // Save preference
                localStorage.setItem('parfume_view_preference', view);
            });
            
            // Restore saved view
            const savedView = localStorage.getItem('parfume_view_preference');
            if (savedView) {
                $(`.view-btn[data-view="${savedView}"]`).click();
            }
        },
        
        handleFormSubmit: function(e) {
            e.preventDefault();
            this.filterResults();
        },
        
        handleFilterChange: function(e) {
            clearTimeout(filterTimeout);
            filterTimeout = setTimeout(() => {
                this.filterResults();
            }, 300);
        },
        
        handleSearchInput: function(e) {
            const $input = $(e.target);
            const query = $input.val().toLowerCase();
            const $container = $input.closest('.filter-group');
            const $options = $container.find('.filter-option:not(.select-all)');
            
            $options.each(function() {
                const text = $(this).text().toLowerCase();
                $(this).toggle(text.includes(query));
            });
        },
        
        handleSelectAll: function(e) {
            const $selectAll = $(e.target);
            const $group = $selectAll.closest('.filter-group');
            const $checkboxes = $group.find('input[type="checkbox"]:not(.select-all)');
            
            if ($selectAll.is(':checked')) {
                $checkboxes.prop('checked', false);
            }
            
            this.filterResults();
        },
        
        handlePriceRange: function(e) {
            const $range = $(e.target);
            const value = $range.val();
            const $display = $range.siblings('.range-display');
            
            $display.text(value);
            
            clearTimeout(filterTimeout);
            filterTimeout = setTimeout(() => {
                this.filterResults();
            }, 500);
        },
        
        toggleFilterSection: function(e) {
            const $title = $(e.target).closest('.filter-title');
            const $options = $title.next('.filter-options');
            const $arrow = $title.find('.toggle-arrow');
            
            if ($options.is(':visible')) {
                $options.slideUp(200);
                $arrow.text('▶');
                $title.addClass('collapsed');
            } else {
                $options.slideDown(200);
                $arrow.text('▼');
                $title.removeClass('collapsed');
            }
        },
        
        filterResults: function() {
            // Cancel previous request
            if (currentRequest) {
                currentRequest.abort();
            }
            
            const formData = this.getFormData();
            const url = this.buildFilterURL(formData);
            
            // Show loading
            this.showLoading();
            
            // Update URL without page reload
            this.updateURL(formData);
            
            // Make AJAX request
            currentRequest = $.ajax({
                url: parfumeReviews.ajaxurl,
                type: 'POST',
                data: {
                    action: 'parfume_filter_results',
                    nonce: parfumeReviews.nonce,
                    ...formData
                },
                success: this.handleFilterSuccess.bind(this),
                error: this.handleFilterError.bind(this),
                complete: this.hideLoading.bind(this)
            });
        },
        
        getFormData: function() {
            const data = {};
            const $form = $('#parfume-filters-form');
            
            // Get all form inputs
            $form.find('input, select').each(function() {
                const $input = $(this);
                const name = $input.attr('name');
                const type = $input.attr('type');
                
                if (!name) return;
                
                if (type === 'checkbox' && $input.is(':checked')) {
                    if (!data[name]) data[name] = [];
                    data[name].push($input.val());
                } else if (type !== 'checkbox') {
                    data[name] = $input.val();
                }
            });
            
            // Add sorting
            const sortValue = $('#parfume-sort').val();
            if (sortValue) {
                data.sort = sortValue;
            }
            
            // Add page
            data.paged = 1;
            
            return data;
        },
        
        buildFilterURL: function(data) {
            const url = new URL(window.location);
            
            // Clear existing params
            const keysToDelete = [];
            for (const key of url.searchParams.keys()) {
                keysToDelete.push(key);
            }
            keysToDelete.forEach(key => url.searchParams.delete(key));
            
            // Add new params
            Object.keys(data).forEach(key => {
                if (Array.isArray(data[key])) {
                    data[key].forEach(value => {
                        url.searchParams.append(key + '[]', value);
                    });
                } else if (data[key]) {
                    url.searchParams.set(key, data[key]);
                }
            });
            
            return url.toString();
        },
        
        updateURL: function(data) {
            const url = this.buildFilterURL(data);
            window.history.pushState({}, '', url);
        },
        
        restoreFiltersFromURL: function() {
            const urlParams = new URLSearchParams(window.location.search);
            
            urlParams.forEach((value, key) => {
                if (key.endsWith('[]')) {
                    const name = key.slice(0, -2);
                    $(`input[name="${name}[]"][value="${value}"]`).prop('checked', true);
                } else {
                    $(`input[name="${key}"], select[name="${key}"]`).val(value);
                }
            });
        },
        
        handleFilterSuccess: function(response) {
            if (response.success) {
                // Update results
                $('.parfume-grid').html(response.data.html);
                
                // Update pagination
                if (response.data.pagination) {
                    $('.pagination').html(response.data.pagination);
                }
                
                // Update results count
                this.updateResultsCount(response.data.total);
                
                // Trigger custom event
                $(document).trigger('parfume:filters:updated', [response.data]);
                
                // Re-initialize components
                this.reinitializeComponents();
                
            } else {
                this.handleFilterError();
            }
        },
        
        handleFilterError: function() {
            $('.parfume-grid').html(
                '<div class="filter-error">' +
                '<p>' + parfumeReviews.strings.error + '</p>' +
                '<button class="retry-btn">' + parfumeReviews.strings.retry + '</button>' +
                '</div>'
            );
            
            $('.retry-btn').on('click', () => {
                this.filterResults();
            });
        },
        
        showLoading: function() {
            $('.parfume-grid').addClass('loading');
            $('.filter-submit').addClass('loading');
            
            // Add loading overlay
            if (!$('.loading-overlay').length) {
                $('.parfume-grid').append(
                    '<div class="loading-overlay">' +
                    '<div class="loading-spinner"></div>' +
                    '<p>' + parfumeReviews.strings.loading + '</p>' +
                    '</div>'
                );
            }
        },
        
        hideLoading: function() {
            $('.parfume-grid').removeClass('loading');
            $('.filter-submit').removeClass('loading');
            $('.loading-overlay').remove();
        },
        
        updateResultsCount: function(total) {
            if (typeof total !== 'undefined') {
                $('.results-count').text(`${total} результата намерени`);
            }
        },
        
        reinitializeComponents: function() {
            // Reinitialize any components that need to be rebound
            if (window.ParfumeComparison) {
                window.ParfumeComparison.bindEvents();
            }
            
            if (window.ParfumeCollections) {
                window.ParfumeCollections.bindEvents();
            }
            
            // Lazy load images
            this.initLazyLoading();
        },
        
        initLazyLoading: function() {
            if ('IntersectionObserver' in window) {
                const imageObserver = new IntersectionObserver((entries, observer) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            const img = entry.target;
                            img.src = img.dataset.src;
                            img.classList.remove('lazy');
                            imageObserver.unobserve(img);
                        }
                    });
                });
                
                document.querySelectorAll('img[data-src]').forEach(img => {
                    imageObserver.observe(img);
                });
            }
        },
        
        initRangeSliders: function() {
            $('.price-range').each(function() {
                const $range = $(this);
                const $display = $range.siblings('.range-display');
                
                $range.on('input', function() {
                    $display.text(this.value);
                });
            });
        },
        
        initCollapsibleSections: function() {
            $('.filter-title.collapsed').each(function() {
                $(this).next('.filter-options').hide();
            });
        },
        
        clearAllFilters: function() {
            // Uncheck all checkboxes
            $('#parfume-filters-form input[type="checkbox"]').prop('checked', false);
            
            // Reset selects
            $('#parfume-filters-form select').prop('selectedIndex', 0);
            
            // Reset ranges
            $('#parfume-filters-form input[type="range"]').each(function() {
                this.value = this.defaultValue;
                $(this).siblings('.range-display').text(this.value);
            });
            
            // Clear search inputs
            $('#parfume-filters-form .filter-search').val('');
            
            // Show all options
            $('.filter-option').show();
            
            // Filter results
            this.filterResults();
        },
        
        loadMore: function(e) {
            e.preventDefault();
            
            const $btn = $(e.target);
            const page = parseInt($btn.data('page')) + 1;
            
            $btn.addClass('loading').text(parfumeReviews.strings.loading);
            
            const formData = this.getFormData();
            formData.paged = page;
            
            $.ajax({
                url: parfumeReviews.ajaxurl,
                type: 'POST',
                data: {
                    action: 'parfume_load_more',
                    nonce: parfumeReviews.nonce,
                    ...formData
                },
                success: function(response) {
                    if (response.success) {
                        $('.parfume-grid').append(response.data.html);
                        
                        if (response.data.has_more) {
                            $btn.data('page', page).removeClass('loading').text('Load More');
                        } else {
                            $btn.remove();
                        }
                        
                        // Reinitialize components
                        ParfumeFilters.reinitializeComponents();
                    }
                },
                error: function() {
                    $btn.removeClass('loading').text('Try Again');
                }
            });
        }
    };
    
    // Initialize when document is ready
    $(document).ready(function() {
        ParfumeFilters.init();
        
        // Make available globally
        window.ParfumeFilters = ParfumeFilters;
    });
    
})(jQuery);