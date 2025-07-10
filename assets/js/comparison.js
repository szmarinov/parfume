/**
 * Comparison functionality for Parfume Catalog Plugin
 * 
 * @package ParfumeCatalog
 */

(function($) {
    'use strict';

    // Global comparison object
    window.ParfumeComparison = {
        storageKey: 'parfume_comparison_list',
        maxItems: 4,
        items: [],
        isPopupOpen: false,

        init: function() {
            this.loadFromStorage();
            this.initEventHandlers();
            this.initPopup();
            this.updateUI();
            this.checkSettings();
        },

        /**
         * Check if comparison is enabled
         */
        checkSettings: function() {
            if (typeof parfume_comparison_settings !== 'undefined') {
                this.maxItems = parseInt(parfume_comparison_settings.max_items) || 4;
                
                if (!parfume_comparison_settings.enabled) {
                    // Hide all comparison buttons if disabled
                    $('.compare-btn').hide();
                    return;
                }
            }
        },

        /**
         * Initialize event handlers
         */
        initEventHandlers: function() {
            var self = this;

            // Compare button clicks
            $(document).on('click', '.compare-btn', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                var $btn = $(this);
                var parfumeId = parseInt($btn.data('parfume-id'));
                var action = $btn.data('action');

                if (action === 'add' || !self.hasItem(parfumeId)) {
                    self.addParfume(parfumeId, $btn);
                } else {
                    self.removeParfume(parfumeId);
                }
            });

            // Keyboard shortcuts
            $(document).on('keydown', function(e) {
                // Ctrl/Cmd + Shift + C to toggle comparison popup
                if ((e.ctrlKey || e.metaKey) && e.shiftKey && e.keyCode === 67) {
                    e.preventDefault();
                    self.togglePopup();
                }
                
                // Escape to close popup
                if (e.keyCode === 27 && self.isPopupOpen) {
                    self.closePopup();
                }
            });

            // Window resize handler
            $(window).on('resize', function() {
                if (self.isPopupOpen) {
                    self.repositionPopup();
                }
            });
        },

        /**
         * Initialize comparison popup
         */
        initPopup: function() {
            var self = this;
            
            // Create popup if it doesn't exist
            if (!$('#parfume-comparison-popup').length) {
                this.createPopup();
            }

            // Popup event handlers
            $(document).on('click', '#parfume-comparison-popup .close-popup', function() {
                self.closePopup();
            });

            $(document).on('click', '#parfume-comparison-popup .remove-item', function() {
                var parfumeId = parseInt($(this).data('parfume-id'));
                self.removeParfume(parfumeId);
            });

            $(document).on('click', '#parfume-comparison-popup .clear-all', function() {
                if (confirm('Сигурни ли сте, че искате да премахнете всички парфюми от сравнението?')) {
                    self.clearAll();
                }
            });

            $(document).on('click', '#parfume-comparison-popup .add-parfume-search', function() {
                self.showAddParfumeDialog();
            });

            // Popup drag functionality
            this.makePopupDraggable();

            // Outside click to close
            $(document).on('click', function(e) {
                if (self.isPopupOpen && 
                    !$(e.target).closest('#parfume-comparison-popup').length && 
                    !$(e.target).hasClass('compare-btn')) {
                    self.closePopup();
                }
            });
        },

        /**
         * Create comparison popup
         */
        createPopup: function() {
            var popupHtml = `
                <div id="parfume-comparison-popup" class="parfume-comparison-popup" style="display: none;">
                    <div class="popup-header">
                        <h3>Сравнение на парфюми</h3>
                        <div class="popup-controls">
                            <button class="add-parfume-search" title="Добави парфюм">+</button>
                            <button class="clear-all" title="Изчисти всички">🗑</button>
                            <button class="close-popup" title="Затвори">×</button>
                        </div>
                    </div>
                    <div class="popup-content">
                        <div class="comparison-table-container">
                            <table class="comparison-table">
                                <tbody class="comparison-tbody">
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="popup-footer">
                        <div class="comparison-count">
                            <span class="count">0</span> / <span class="max">${this.maxItems}</span> парфюми
                        </div>
                    </div>
                </div>
            `;
            
            $('body').append(popupHtml);
        },

        /**
         * Make popup draggable
         */
        makePopupDraggable: function() {
            var $popup = $('#parfume-comparison-popup');
            var isDragging = false;
            var currentX, currentY, initialX, initialY;

            $popup.find('.popup-header').on('mousedown', function(e) {
                isDragging = true;
                initialX = e.clientX - $popup.offset().left;
                initialY = e.clientY - $popup.offset().top;
                
                $(document).on('mousemove', drag);
                $(document).on('mouseup', stopDrag);
            });

            function drag(e) {
                if (isDragging) {
                    currentX = e.clientX - initialX;
                    currentY = e.clientY - initialY;
                    
                    $popup.css({
                        left: currentX + 'px',
                        top: currentY + 'px'
                    });
                }
            }

            function stopDrag() {
                isDragging = false;
                $(document).off('mousemove', drag);
                $(document).off('mouseup', stopDrag);
            }
        },

        /**
         * Add parfume to comparison
         */
        addParfume: function(parfumeId, $btn) {
            // Check if already exists
            if (this.hasItem(parfumeId)) {
                this.showMessage('Този парфюм вече е добавен за сравнение.', 'warning');
                return;
            }

            // Check max items limit
            if (this.items.length >= this.maxItems) {
                this.showMessage(`Можете да сравнявате максимум ${this.maxItems} парфюма.`, 'warning');
                return;
            }

            var self = this;

            // Get parfume data
            this.getParfumeData(parfumeId, function(parfumeData) {
                if (parfumeData) {
                    self.items.push(parfumeData);
                    self.saveToStorage();
                    self.updateUI();
                    self.showMessage('Парфюмът е добавен за сравнение!', 'success');
                    
                    // Auto-open popup if we have 2 or more items
                    if (self.items.length >= 2 && !self.isPopupOpen) {
                        self.openPopup();
                    }
                } else {
                    self.showMessage('Грешка при добавяне на парфюма.', 'error');
                }
            });
        },

        /**
         * Remove parfume from comparison
         */
        removeParfume: function(parfumeId) {
            var index = this.items.findIndex(item => item.id === parfumeId);
            
            if (index !== -1) {
                var removedItem = this.items.splice(index, 1)[0];
                this.saveToStorage();
                this.updateUI();
                this.showMessage(`${removedItem.title} е премахнат от сравнението.`, 'info');
                
                // Close popup if no items left
                if (this.items.length === 0 && this.isPopupOpen) {
                    this.closePopup();
                }
            }
        },

        /**
         * Clear all items
         */
        clearAll: function() {
            this.items = [];
            this.saveToStorage();
            this.updateUI();
            this.closePopup();
            this.showMessage('Всички парфюми са премахнати от сравнението.', 'info');
        },

        /**
         * Check if item exists
         */
        hasItem: function(parfumeId) {
            return this.items.some(item => item.id === parfumeId);
        },

        /**
         * Get parfume data via AJAX
         */
        getParfumeData: function(parfumeId, callback) {
            $.ajax({
                url: parfume_comparison_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'get_parfume_comparison_data',
                    nonce: parfume_comparison_ajax.nonce,
                    parfume_id: parfumeId
                },
                success: function(response) {
                    if (response.success) {
                        callback(response.data);
                    } else {
                        callback(null);
                    }
                },
                error: function() {
                    callback(null);
                }
            });
        },

        /**
         * Update UI elements
         */
        updateUI: function() {
            this.updateButtons();
            this.updatePopup();
            this.updateFloatingButton();
        },

        /**
         * Update compare buttons
         */
        updateButtons: function() {
            var self = this;
            
            $('.compare-btn').each(function() {
                var $btn = $(this);
                var parfumeId = parseInt($btn.data('parfume-id'));
                var $icon = $btn.find('.compare-icon');
                var $text = $btn.find('.compare-text');
                
                if (self.hasItem(parfumeId)) {
                    $btn.addClass('active')
                        .removeClass('btn-primary')
                        .addClass('btn-danger')
                        .data('action', 'remove');
                    
                    if ($icon.length) $icon.text('✓');
                    if ($text.length) $text.text('Премахни');
                } else {
                    $btn.removeClass('active btn-danger')
                        .addClass('btn-primary')
                        .data('action', 'add');
                    
                    if ($icon.length) $icon.text('⚖');
                    if ($text.length) $text.text('Сравни');
                }
            });
        },

        /**
         * Update popup content
         */
        updatePopup: function() {
            if (!$('#parfume-comparison-popup').length) return;

            var $tbody = $('.comparison-tbody');
            var $count = $('.comparison-count .count');
            
            // Update count
            $count.text(this.items.length);
            
            if (this.items.length === 0) {
                $tbody.html('<tr><td colspan="100%" class="no-items">Няма добавени парфюми за сравнение</td></tr>');
                return;
            }

            // Build comparison table
            this.buildComparisonTable($tbody);
        },

        /**
         * Build comparison table
         */
        buildComparisonTable: function($tbody) {
            $tbody.empty();
            
            if (this.items.length === 0) return;

            // Get comparison criteria from settings
            var criteria = this.getComparisonCriteria();
            
            // Header row with parfume images and titles
            var headerHtml = '<tr class="parfume-header"><td class="criteria-label">Парфюм</td>';
            
            $.each(this.items, function(index, item) {
                headerHtml += `
                    <td class="parfume-cell">
                        <div class="parfume-item-header">
                            <button class="remove-item" data-parfume-id="${item.id}" title="Премахни">×</button>
                            <div class="parfume-image">
                                ${item.image ? `<img src="${item.image}" alt="${item.title}">` : '<div class="no-image">Няма снимка</div>'}
                            </div>
                            <div class="parfume-title">
                                <a href="${item.url}" target="_blank">${item.title}</a>
                            </div>
                            <div class="parfume-brand">${item.brand || ''}</div>
                        </div>
                    </td>
                `;
            });
            
            headerHtml += '</tr>';
            $tbody.append(headerHtml);

            // Comparison criteria rows
            $.each(criteria, function(key, criterion) {
                var rowHtml = `<tr class="comparison-row"><td class="criteria-label">${criterion.label}</td>`;
                
                $.each(this.items, function(index, item) {
                    var value = this.getItemValue(item, key, criterion);
                    rowHtml += `<td class="parfume-cell">${value}</td>`;
                }.bind(this));
                
                rowHtml += '</tr>';
                $tbody.append(rowHtml);
            }.bind(this));
        },

        /**
         * Get comparison criteria
         */
        getComparisonCriteria: function() {
            // Default criteria
            var defaultCriteria = {
                'brand': { label: 'Марка', type: 'text' },
                'type': { label: 'Тип', type: 'text' },
                'concentration': { label: 'Концентрация', type: 'text' },
                'top_notes': { label: 'Върхни нотки', type: 'array' },
                'middle_notes': { label: 'Средни нотки', type: 'array' },
                'base_notes': { label: 'Базови нотки', type: 'array' },
                'longevity': { label: 'Дълготрайност', type: 'text' },
                'sillage': { label: 'Ароматна следа', type: 'text' },
                'season': { label: 'Сезон', type: 'array' },
                'intensity': { label: 'Интензивност', type: 'text' },
                'price_range': { label: 'Ценови диапазон', type: 'text' },
                'rating': { label: 'Рейтинг', type: 'rating' }
            };

            // Override with settings if available
            if (typeof parfume_comparison_settings !== 'undefined' && 
                parfume_comparison_settings.criteria) {
                return parfume_comparison_settings.criteria;
            }

            return defaultCriteria;
        },

        /**
         * Get item value for specific criterion
         */
        getItemValue: function(item, key, criterion) {
            var value = item[key];
            
            if (!value) return '-';

            switch (criterion.type) {
                case 'array':
                    if (Array.isArray(value)) {
                        return value.join(', ');
                    }
                    return value;
                    
                case 'rating':
                    var stars = '';
                    var rating = parseFloat(value) || 0;
                    for (var i = 1; i <= 5; i++) {
                        stars += i <= rating ? '★' : '☆';
                    }
                    return stars + ' (' + rating + ')';
                    
                case 'text':
                default:
                    return value;
            }
        },

        /**
         * Update floating comparison button
         */
        updateFloatingButton: function() {
            var $floatingBtn = $('.parfume-comparison-floating');
            
            if (this.items.length > 0) {
                if (!$floatingBtn.length) {
                    this.createFloatingButton();
                    $floatingBtn = $('.parfume-comparison-floating');
                }
                
                $floatingBtn.find('.count').text(this.items.length);
                $floatingBtn.show();
            } else {
                $floatingBtn.hide();
            }
        },

        /**
         * Create floating comparison button
         */
        createFloatingButton: function() {
            var buttonHtml = `
                <div class="parfume-comparison-floating" style="display: none;">
                    <button class="floating-compare-btn">
                        <span class="icon">⚖</span>
                        <span class="count">0</span>
                    </button>
                </div>
            `;
            
            $('body').append(buttonHtml);
            
            // Event handler for floating button
            $(document).on('click', '.floating-compare-btn', function() {
                this.togglePopup();
            }.bind(this));
        },

        /**
         * Open popup
         */
        openPopup: function() {
            if (this.items.length === 0) {
                this.showMessage('Добавете поне един парфюм за сравнение.', 'warning');
                return;
            }

            var $popup = $('#parfume-comparison-popup');
            
            this.updatePopup();
            this.repositionPopup();
            
            $popup.fadeIn(300);
            this.isPopupOpen = true;
            
            // Focus management for accessibility
            $popup.find('.close-popup').focus();
        },

        /**
         * Close popup
         */
        closePopup: function() {
            $('#parfume-comparison-popup').fadeOut(300);
            this.isPopupOpen = false;
        },

        /**
         * Toggle popup
         */
        togglePopup: function() {
            if (this.isPopupOpen) {
                this.closePopup();
            } else {
                this.openPopup();
            }
        },

        /**
         * Reposition popup
         */
        repositionPopup: function() {
            var $popup = $('#parfume-comparison-popup');
            var $window = $(window);
            
            // Center the popup
            var left = ($window.width() - $popup.outerWidth()) / 2;
            var top = ($window.height() - $popup.outerHeight()) / 2;
            
            // Ensure popup stays within viewport
            left = Math.max(20, Math.min(left, $window.width() - $popup.outerWidth() - 20));
            top = Math.max(20, Math.min(top, $window.height() - $popup.outerHeight() - 20));
            
            $popup.css({
                left: left + 'px',
                top: top + 'px'
            });
        },

        /**
         * Show add parfume dialog
         */
        showAddParfumeDialog: function() {
            var search = prompt('Въведете име на парфюм за търсене:');
            if (search) {
                this.searchParfumes(search);
            }
        },

        /**
         * Search parfumes
         */
        searchParfumes: function(search) {
            var self = this;
            
            $.ajax({
                url: parfume_comparison_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'search_parfumes_for_comparison',
                    nonce: parfume_comparison_ajax.nonce,
                    search: search
                },
                success: function(response) {
                    if (response.success && response.data.length > 0) {
                        self.showSearchResults(response.data);
                    } else {
                        self.showMessage('Не са намерени парфюми.', 'info');
                    }
                },
                error: function() {
                    self.showMessage('Грешка при търсенето.', 'error');
                }
            });
        },

        /**
         * Show search results
         */
        showSearchResults: function(results) {
            var self = this;
            var resultsHtml = '<div class="search-results"><h4>Резултати от търсенето:</h4>';
            
            $.each(results, function(index, parfume) {
                resultsHtml += `
                    <div class="search-result-item">
                        <span class="parfume-title">${parfume.title}</span>
                        <span class="parfume-brand">${parfume.brand}</span>
                        <button class="add-to-comparison" data-parfume-id="${parfume.id}">Добави</button>
                    </div>
                `;
            });
            
            resultsHtml += '</div>';
            
            // Show in popup or alert
            alert('Функционалността за търсене ще бъде имплементирана в по-късна версия.');
        },

        /**
         * Load items from localStorage
         */
        loadFromStorage: function() {
            try {
                var stored = localStorage.getItem(this.storageKey);
                if (stored) {
                    this.items = JSON.parse(stored);
                    
                    // Validate items
                    this.items = this.items.filter(function(item) {
                        return item && item.id && item.title;
                    });
                }
            } catch (e) {
                console.error('Error loading comparison data:', e);
                this.items = [];
            }
        },

        /**
         * Save items to localStorage
         */
        saveToStorage: function() {
            try {
                localStorage.setItem(this.storageKey, JSON.stringify(this.items));
            } catch (e) {
                console.error('Error saving comparison data:', e);
            }
        },

        /**
         * Show message to user
         */
        showMessage: function(message, type) {
            type = type || 'info';
            
            // Create toast notification
            var $toast = $(`
                <div class="parfume-toast parfume-toast-${type}">
                    <span class="toast-message">${message}</span>
                    <button class="toast-close">×</button>
                </div>
            `);
            
            $('body').append($toast);
            
            // Show toast
            setTimeout(function() {
                $toast.addClass('show');
            }, 100);
            
            // Auto hide after 3 seconds
            setTimeout(function() {
                $toast.removeClass('show');
                setTimeout(function() {
                    $toast.remove();
                }, 300);
            }, 3000);
            
            // Manual close
            $toast.find('.toast-close').on('click', function() {
                $toast.removeClass('show');
                setTimeout(function() {
                    $toast.remove();
                }, 300);
            });
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        if (typeof parfume_comparison_ajax !== 'undefined') {
            ParfumeComparison.init();
        }
    });

    // Expose to global scope
    window.parfumeComparison = ParfumeComparison;

})(jQuery);