/**
 * Parfume Catalog Comparison JavaScript
 * 
 * Система за сравняване на парфюми
 * 
 * @package Parfume_Catalog
 * @since 1.0.0
 */

(function($) {
    'use strict';

    // Глобален обект за comparison системата
    window.parfumeComparison = {
        
        // Настройки и данни
        settings: {
            maxItems: 4,
            enabled: true,
            autoShowPopup: true,
            enableUndo: true,
            enableSearch: true,
            storageKey: 'parfume_comparison',
            undoKey: 'parfume_comparison_undo',
            ajaxUrl: window.parfumeComparison ? window.parfumeComparison.ajaxUrl : '/wp-admin/admin-ajax.php',
            nonce: window.parfumeComparison ? window.parfumeComparison.nonce : ''
        },

        // Локализирани текстове
        strings: {
            addToComparison: 'Добави за сравнение',
            removeFromComparison: 'Премахни от сравнение',
            compare: 'Сравни',
            comparing: 'Сравняване...',
            maxItemsReached: 'Максимум {max} парфюма могат да се сравняват едновременно',
            itemAdded: 'Добавен за сравнение',
            itemRemoved: 'Премахнат от сравнение',
            clearAll: 'Изчисти всички',
            exportCSV: 'Експорт CSV',
            exportPDF: 'Експорт PDF',
            print: 'Принтирай',
            searchPlaceholder: 'Търси парфюми за добавяне...',
            noResults: 'Няма намерени резултати',
            loading: 'Зареждане...',
            error: 'Възникна грешка',
            undo: 'Отмени',
            closeComparison: 'Затвори сравнението',
            minimizeComparison: 'Минимизирай'
        },

        // DOM елементи
        $floatingButton: null,
        $popup: null,
        $popupOverlay: null,
        $comparisonTable: null,
        $itemCount: null,
        $undoButton: null,

        // Състояние
        comparisonData: [],
        lastRemovedItem: null,
        isPopupOpen: false,
        isMinimized: false,

        // Инициализация
        init: function() {
            // Merge settings от WordPress
            if (window.parfumeComparison) {
                $.extend(this.settings, window.parfumeComparison);
            }
            
            // Merge strings от WordPress
            if (window.parfumeComparison && window.parfumeComparison.strings) {
                $.extend(this.strings, window.parfumeComparison.strings);
            }

            if (!this.settings.enabled) {
                return;
            }

            this.loadComparisonData();
            this.createFloatingButton();
            this.createPopup();
            this.bindEvents();
            this.updateUI();
            
            console.log('Parfume Comparison initialized');
        },

        // Зареждане на данни от localStorage
        loadComparisonData: function() {
            try {
                var stored = localStorage.getItem(this.settings.storageKey);
                this.comparisonData = stored ? JSON.parse(stored) : [];
            } catch (e) {
                console.warn('Error loading comparison data:', e);
                this.comparisonData = [];
            }
        },

        // Запазване на данни в localStorage
        saveComparisonData: function() {
            try {
                localStorage.setItem(this.settings.storageKey, JSON.stringify(this.comparisonData));
            } catch (e) {
                console.warn('Error saving comparison data:', e);
            }
        },

        // Създаване на floating button
        createFloatingButton: function() {
            this.$floatingButton = $(`
                <div id="parfume-comparison-float" class="comparison-floating-button" style="display: none;">
                    <div class="float-content">
                        <span class="float-icon">⚖️</span>
                        <span class="float-text">${this.strings.compare}</span>
                        <span class="float-count">0</span>
                    </div>
                    <div class="float-actions">
                        <button class="float-minimize" title="${this.strings.minimizeComparison}">−</button>
                        <button class="float-close" title="${this.strings.closeComparison}">×</button>
                    </div>
                </div>
            `);

            $('body').append(this.$floatingButton);
            this.$itemCount = this.$floatingButton.find('.float-count');
        },

        // Създаване на popup
        createPopup: function() {
            this.$popupOverlay = $(`
                <div id="parfume-comparison-overlay" class="comparison-overlay" style="display: none;">
                    <div class="comparison-popup">
                        <div class="popup-header">
                            <h3 class="popup-title">
                                <span class="title-icon">⚖️</span>
                                ${this.strings.compare}
                                <span class="item-counter">(<span class="counter-value">0</span>)</span>
                            </h3>
                            <div class="popup-actions">
                                ${this.settings.enableUndo ? `<button class="btn-undo" title="${this.strings.undo}" style="display: none;"><span>↶</span> ${this.strings.undo}</button>` : ''}
                                <button class="btn-minimize" title="${this.strings.minimizeComparison}">−</button>
                                <button class="btn-close" title="${this.strings.closeComparison}">×</button>
                            </div>
                        </div>
                        
                        <div class="popup-content">
                            <div class="comparison-controls">
                                ${this.settings.enableSearch ? `
                                    <div class="search-container">
                                        <input type="text" class="comparison-search" placeholder="${this.strings.searchPlaceholder}">
                                        <div class="search-results" style="display: none;"></div>
                                    </div>
                                ` : ''}
                                
                                <div class="action-buttons">
                                    <button class="btn-clear-all">${this.strings.clearAll}</button>
                                    <div class="export-dropdown">
                                        <button class="btn-export">Експорт ▼</button>
                                        <div class="export-menu">
                                            <button class="btn-export-csv">${this.strings.exportCSV}</button>
                                            <button class="btn-export-pdf">${this.strings.exportPDF}</button>
                                            <button class="btn-print">${this.strings.print}</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="comparison-table-container">
                                <table class="comparison-table">
                                    <thead>
                                        <tr class="table-header"></tr>
                                    </thead>
                                    <tbody class="table-body"></tbody>
                                </table>
                            </div>
                            
                            <div class="empty-state" style="display: none;">
                                <div class="empty-icon">⚖️</div>
                                <h4>Няма парфюми за сравнение</h4>
                                <p>Добавете парфюми като кликнете бутона "Добави за сравнение" на страниците на продуктите.</p>
                            </div>
                        </div>
                    </div>
                </div>
            `);

            $('body').append(this.$popupOverlay);
            this.$popup = this.$popupOverlay.find('.comparison-popup');
            this.$comparisonTable = this.$popup.find('.comparison-table');
            this.$undoButton = this.$popup.find('.btn-undo');
        },

        // Event listeners
        bindEvents: function() {
            var self = this;

            // Floating button click
            this.$floatingButton.on('click', '.float-content', function(e) {
                e.preventDefault();
                self.openPopup();
            });

            // Floating button actions
            this.$floatingButton.on('click', '.float-minimize', function(e) {
                e.stopPropagation();
                self.minimizePopup();
            });

            this.$floatingButton.on('click', '.float-close', function(e) {
                e.stopPropagation();
                self.closePopup();
            });

            // Popup header actions
            this.$popup.on('click', '.btn-minimize', function() {
                self.minimizePopup();
            });

            this.$popup.on('click', '.btn-close', function() {
                self.closePopup();
            });

            this.$popup.on('click', '.btn-undo', function() {
                self.undoLastAction();
            });

            // Comparison controls
            this.$popup.on('click', '.btn-clear-all', function() {
                self.clearAll();
            });

            this.$popup.on('click', '.btn-export', function() {
                $(this).siblings('.export-menu').toggle();
            });

            this.$popup.on('click', '.btn-export-csv', function() {
                self.exportToCSV();
            });

            this.$popup.on('click', '.btn-export-pdf', function() {
                self.exportToPDF();
            });

            this.$popup.on('click', '.btn-print', function() {
                self.printComparison();
            });

            // Remove item from comparison
            this.$popup.on('click', '.remove-item', function() {
                var parfumeId = $(this).data('parfume-id');
                self.removeItem(parfumeId);
            });

            // Search functionality
            if (this.settings.enableSearch) {
                var searchTimeout;
                this.$popup.on('input', '.comparison-search', function() {
                    var query = $(this).val().trim();
                    
                    clearTimeout(searchTimeout);
                    
                    if (query.length < 2) {
                        self.$popup.find('.search-results').hide().empty();
                        return;
                    }
                    
                    searchTimeout = setTimeout(function() {
                        self.searchParfumes(query);
                    }, 300);
                });

                // Add search result to comparison
                this.$popup.on('click', '.search-result-item', function() {
                    var parfumeData = $(this).data('parfume');
                    self.addItem(parfumeData);
                    self.$popup.find('.comparison-search').val('');
                    self.$popup.find('.search-results').hide().empty();
                });
            }

            // Overlay click to close
            this.$popupOverlay.on('click', function(e) {
                if (e.target === this) {
                    self.closePopup();
                }
            });

            // Keyboard shortcuts
            $(document).on('keydown', function(e) {
                // Ctrl+Shift+C to toggle comparison
                if (e.ctrlKey && e.shiftKey && e.key === 'C') {
                    e.preventDefault();
                    self.togglePopup();
                }
                
                // Escape to close popup
                if (e.key === 'Escape' && self.isPopupOpen) {
                    self.closePopup();
                }
            });

            // Close export dropdown when clicking outside
            $(document).on('click', function(e) {
                if (!$(e.target).closest('.export-dropdown').length) {
                    $('.export-menu').hide();
                }
            });
        },

        // Добавяне на елемент за сравнение
        addItem: function(parfumeData) {
            // Проверка за максимален брой
            if (this.comparisonData.length >= this.settings.maxItems) {
                this.showMessage(this.strings.maxItemsReached.replace('{max}', this.settings.maxItems), 'warning');
                return false;
            }

            // Проверка дали вече не е добавен
            var exists = this.comparisonData.find(function(item) {
                return item.id == parfumeData.id;
            });

            if (exists) {
                this.showMessage('Парфюмът вече е добавен за сравнение', 'info');
                return false;
            }

            // Добавяне
            this.comparisonData.push(parfumeData);
            this.saveComparisonData();
            this.updateUI();
            this.showMessage(this.strings.itemAdded, 'success');

            // Auto-показване на popup ако е включено
            if (this.settings.autoShowPopup && this.comparisonData.length >= 2 && !this.isPopupOpen) {
                this.openPopup();
            }

            return true;
        },

        // Премахване на елемент от сравнение
        removeItem: function(parfumeId) {
            var index = this.comparisonData.findIndex(function(item) {
                return item.id == parfumeId;
            });

            if (index > -1) {
                // Запазване за undo функционалност
                if (this.settings.enableUndo) {
                    this.lastRemovedItem = {
                        data: this.comparisonData[index],
                        index: index
                    };
                    localStorage.setItem(this.settings.undoKey, JSON.stringify(this.lastRemovedItem));
                }

                // Премахване
                this.comparisonData.splice(index, 1);
                this.saveComparisonData();
                this.updateUI();
                this.showMessage(this.strings.itemRemoved, 'success');

                // Показване на undo бутон
                if (this.settings.enableUndo && this.$undoButton) {
                    this.$undoButton.show();
                    
                    // Auto-скриване след 10 секунди
                    setTimeout(function() {
                        this.$undoButton.fadeOut();
                    }.bind(this), 10000);
                }

                return true;
            }

            return false;
        },

        // Undo на последното действие
        undoLastAction: function() {
            if (!this.lastRemovedItem) {
                try {
                    var stored = localStorage.getItem(this.settings.undoKey);
                    this.lastRemovedItem = stored ? JSON.parse(stored) : null;
                } catch (e) {
                    console.warn('Error loading undo data:', e);
                }
            }

            if (this.lastRemovedItem) {
                // Проверка за максимален брой
                if (this.comparisonData.length >= this.settings.maxItems) {
                    this.showMessage(this.strings.maxItemsReached.replace('{max}', this.settings.maxItems), 'warning');
                    return;
                }

                // Възстановяване на елемента
                var insertIndex = Math.min(this.lastRemovedItem.index, this.comparisonData.length);
                this.comparisonData.splice(insertIndex, 0, this.lastRemovedItem.data);
                
                this.saveComparisonData();
                this.updateUI();
                this.showMessage('Възстановен: ' + this.lastRemovedItem.data.title, 'success');

                // Изчистване на undo данните
                this.lastRemovedItem = null;
                localStorage.removeItem(this.settings.undoKey);
                this.$undoButton.hide();
            }
        },

        // Изчистване на всички елементи
        clearAll: function() {
            if (this.comparisonData.length === 0) {
                return;
            }

            if (confirm('Сигурни ли сте, че искате да премахнете всички парфюми от сравнението?')) {
                this.comparisonData = [];
                this.saveComparisonData();
                this.updateUI();
                this.showMessage('Всички елементи са премахнати', 'success');
            }
        },

        // Търсене на парфюми
        searchParfumes: function(query) {
            var $searchResults = this.$popup.find('.search-results');
            $searchResults.html('<div class="search-loading">' + this.strings.loading + '</div>').show();

            $.ajax({
                url: this.settings.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'parfume_search_suggestions',
                    nonce: this.settings.nonce,
                    query: query,
                    exclude: this.comparisonData.map(function(item) { return item.id; })
                },
                success: function(response) {
                    if (response.success && response.data.suggestions) {
                        var html = '';
                        response.data.suggestions.forEach(function(parfume) {
                            html += `
                                <div class="search-result-item" data-parfume='${JSON.stringify(parfume)}'>
                                    <div class="result-image">
                                        ${parfume.image ? `<img src="${parfume.image}" alt="${parfume.title}">` : '<span class="placeholder">🌸</span>'}
                                    </div>
                                    <div class="result-content">
                                        <div class="result-title">${parfume.title}</div>
                                        <div class="result-meta">${parfume.brand || ''} ${parfume.type || ''}</div>
                                    </div>
                                    <div class="result-action">
                                        <span class="add-icon">+</span>
                                    </div>
                                </div>
                            `;
                        });
                        
                        $searchResults.html(html);
                    } else {
                        $searchResults.html('<div class="no-results">' + this.strings.noResults + '</div>');
                    }
                }.bind(this),
                error: function() {
                    $searchResults.html('<div class="search-error">' + this.strings.error + '</div>');
                }
            });
        },

        // Отваряне на popup
        openPopup: function() {
            if (this.comparisonData.length === 0) {
                this.showMessage('Няма добавени парфюми за сравнение', 'info');
                return;
            }

            this.loadComparisonData();
            this.renderComparisonTable();
            this.$popupOverlay.fadeIn(300);
            this.isPopupOpen = true;
            this.isMinimized = false;
            $('body').addClass('comparison-popup-open');

            // Focus на първия интерактивен елемент
            setTimeout(function() {
                this.$popup.find('button, input').first().focus();
            }.bind(this), 350);
        },

        // Затваряне на popup
        closePopup: function() {
            this.$popupOverlay.fadeOut(300);
            this.isPopupOpen = false;
            this.isMinimized = false;
            $('body').removeClass('comparison-popup-open');
            
            // Скриване на export menu
            this.$popup.find('.export-menu').hide();
        },

        // Минимизиране на popup
        minimizePopup: function() {
            this.$popupOverlay.fadeOut(300);
            this.isPopupOpen = false;
            this.isMinimized = true;
            $('body').removeClass('comparison-popup-open');
        },

        // Toggle на popup
        togglePopup: function() {
            if (this.isPopupOpen) {
                this.closePopup();
            } else {
                this.openPopup();
            }
        },

        // Обновяване на UI
        updateUI: function() {
            var count = this.comparisonData.length;
            
            // Обновяване на floating button
            this.$itemCount.text(count);
            this.$popup.find('.counter-value').text(count);
            
            if (count > 0) {
                this.$floatingButton.show();
            } else {
                this.$floatingButton.hide();
                if (this.isPopupOpen) {
                    this.closePopup();
                }
            }

            // Обновяване на comparison table
            if (this.isPopupOpen) {
                this.renderComparisonTable();
            }

            // Обновяване на external buttons
            this.updateExternalButtons();
        },

        // Обновяване на външните бутони за сравнение
        updateExternalButtons: function() {
            var comparisonIds = this.comparisonData.map(function(item) {
                return item.id.toString();
            });

            $('.parfume-compare-btn, .parfume-card-compare').each(function() {
                var $btn = $(this);
                var parfumeId = $btn.data('parfume-id');
                
                if (parfumeId && comparisonIds.indexOf(parfumeId.toString()) > -1) {
                    $btn.addClass('active').find('.compare-text').text(window.parfumeComparison.strings.removeFromComparison || 'Премахни от сравнение');
                } else {
                    $btn.removeClass('active').find('.compare-text').text(window.parfumeComparison.strings.addToComparison || 'Добави за сравнение');
                }
            });
        },

        // Рендериране на comparison таблица
        renderComparisonTable: function() {
            if (this.comparisonData.length === 0) {
                this.$comparisonTable.hide();
                this.$popup.find('.empty-state').show();
                return;
            }

            this.$popup.find('.empty-state').hide();
            this.$comparisonTable.show();

            // Заявка за пълните данни на парфюмите
            this.fetchComparisonData();
        },

        // Заявка за пълни данни за сравнение
        fetchComparisonData: function() {
            var parfumeIds = this.comparisonData.map(function(item) {
                return item.id;
            });

            $.ajax({
                url: this.settings.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'parfume_get_comparison_data',
                    nonce: this.settings.nonce,
                    parfume_ids: parfumeIds
                },
                success: function(response) {
                    if (response.success && response.data) {
                        this.renderTable(response.data);
                    } else {
                        this.showMessage('Грешка при зареждане на данните за сравнение', 'error');
                    }
                }.bind(this),
                error: function() {
                    this.showMessage('Грешка при заявката за данни', 'error');
                }
            });
        },

        // Рендериране на таблицата с данни
        renderTable: function(data) {
            var $header = this.$comparisonTable.find('.table-header');
            var $body = this.$comparisonTable.find('.table-body');

            // Изчистване
            $header.empty();
            $body.empty();

            // Header с парфюмите
            var headerHtml = '<th class="criteria-header">Критерий</th>';
            data.parfumes.forEach(function(parfume) {
                headerHtml += `
                    <th class="parfume-header">
                        <div class="parfume-header-content">
                            <button class="remove-item" data-parfume-id="${parfume.id}" title="Премахни от сравнение">×</button>
                            <div class="parfume-image">
                                ${parfume.image ? `<img src="${parfume.image}" alt="${parfume.title}">` : '<span class="placeholder">🌸</span>'}
                            </div>
                            <div class="parfume-info">
                                <h4 class="parfume-title">${parfume.title}</h4>
                                <div class="parfume-meta">${parfume.brand || ''} ${parfume.type || ''}</div>
                            </div>
                        </div>
                    </th>
                `;
            });
            $header.html(headerHtml);

            // Body с критериите
            data.criteria.forEach(function(criterion) {
                if (!criterion.enabled) return;

                var rowHtml = `<tr class="criterion-row criterion-${criterion.key}">`;
                rowHtml += `<td class="criterion-label">${criterion.label}</td>`;
                
                data.parfumes.forEach(function(parfume) {
                    var value = parfume.data[criterion.key] || '';
                    rowHtml += `<td class="criterion-value">${this.formatCriterionValue(criterion.key, value)}</td>`;
                }.bind(this));
                
                rowHtml += '</tr>';
                $body.append(rowHtml);
            }.bind(this));
        },

        // Форматиране на стойности според критерия
        formatCriterionValue: function(criterionKey, value) {
            switch (criterionKey) {
                case 'rating':
                    if (value) {
                        var stars = '';
                        var rating = parseFloat(value);
                        for (var i = 1; i <= 5; i++) {
                            stars += i <= rating ? '★' : '☆';
                        }
                        return `<span class="rating-stars">${stars}</span> <span class="rating-value">(${rating})</span>`;
                    }
                    return 'Няма оценки';

                case 'price':
                    if (value && value.min) {
                        if (value.min === value.max) {
                            return `${value.min} лв.`;
                        } else {
                            return `${value.min} - ${value.max} лв.`;
                        }
                    }
                    return 'Няма данни за цена';

                case 'notes':
                    if (Array.isArray(value) && value.length > 0) {
                        return value.slice(0, 5).map(function(note) {
                            return `<span class="note-tag">${note}</span>`;
                        }).join(' ');
                    }
                    return 'Няма данни';

                case 'advantages':
                case 'disadvantages':
                    if (Array.isArray(value) && value.length > 0) {
                        return '<ul class="pros-cons-list">' + 
                               value.map(function(item) {
                                   return `<li>${item}</li>`;
                               }).join('') + 
                               '</ul>';
                    }
                    return 'Няма данни';

                default:
                    return value || 'Няма данни';
            }
        },

        // Export функции
        exportToCSV: function() {
            var parfumeIds = this.comparisonData.map(function(item) {
                return item.id;
            });

            $.ajax({
                url: this.settings.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'parfume_export_comparison',
                    nonce: this.settings.nonce,
                    parfume_ids: parfumeIds,
                    format: 'csv'
                },
                success: function(response) {
                    if (response.success && response.data.download_url) {
                        // Създаване на скрит линк за сваляне
                        var link = document.createElement('a');
                        link.href = response.data.download_url;
                        link.download = 'parfume-comparison.csv';
                        document.body.appendChild(link);
                        link.click();
                        document.body.removeChild(link);
                    } else {
                        this.showMessage('Грешка при експорт в CSV', 'error');
                    }
                }.bind(this),
                error: function() {
                    this.showMessage('Грешка при заявката за експорт', 'error');
                }
            });
        },

        // Export to PDF
        exportToPDF: function() {
            var parfumeIds = this.comparisonData.map(function(item) {
                return item.id;
            });

            $.ajax({
                url: this.settings.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'parfume_export_comparison',
                    nonce: this.settings.nonce,
                    parfume_ids: parfumeIds,
                    format: 'pdf'
                },
                success: function(response) {
                    if (response.success && response.data.download_url) {
                        window.open(response.data.download_url, '_blank');
                    } else {
                        this.showMessage('Грешка при експорт в PDF', 'error');
                    }
                }.bind(this),
                error: function() {
                    this.showMessage('Грешка при заявката за експорт', 'error');
                }
            });
        },

        // Print comparison
        printComparison: function() {
            var printWindow = window.open('', '_blank');
            var printContent = this.generatePrintContent();
            
            printWindow.document.write(printContent);
            printWindow.document.close();
            printWindow.focus();
            printWindow.print();
            printWindow.close();
        },

        // Генериране на съдържание за принтиране
        generatePrintContent: function() {
            var tableHTML = this.$comparisonTable[0].outerHTML;
            
            return `
                <!DOCTYPE html>
                <html>
                <head>
                    <title>Сравнение на парфюми</title>
                    <style>
                        body { font-family: Arial, sans-serif; margin: 20px; }
                        table { border-collapse: collapse; width: 100%; }
                        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                        th { background-color: #f2f2f2; font-weight: bold; }
                        .parfume-image img { max-width: 50px; height: auto; }
                        .note-tag { background: #e8f4f8; padding: 2px 6px; border-radius: 10px; font-size: 0.8em; }
                        .rating-stars { color: #ffb400; }
                        .pros-cons-list { margin: 0; padding-left: 20px; }
                        .remove-item { display: none; }
                    </style>
                </head>
                <body>
                    <h1>Сравнение на парфюми</h1>
                    <p>Генерирано на: ${new Date().toLocaleDateString('bg-BG')}</p>
                    ${tableHTML}
                </body>
                </html>
            `;
        },

        // Показване на съобщения
        showMessage: function(message, type) {
            type = type || 'info';
            
            // Премахване на съществуващи съобщения
            $('.comparison-message').remove();
            
            var $message = $(`
                <div class="comparison-message comparison-message-${type}">
                    <span class="message-text">${message}</span>
                    <button class="message-close" aria-label="Затвори">&times;</button>
                </div>
            `);
            
            // Добавяне към popup или body
            if (this.isPopupOpen) {
                this.$popup.prepend($message);
            } else {
                $('body').prepend($message);
            }
            
            // Auto-скриване след 4 секунди
            setTimeout(function() {
                $message.fadeOut(function() {
                    $message.remove();
                });
            }, 4000);
            
            // Close button
            $message.find('.message-close').on('click', function() {
                $message.fadeOut(function() {
                    $message.remove();
                });
            });
        },

        // External API за използване от други скриптове
        addParfume: function(parfumeData) {
            return this.addItem(parfumeData);
        },

        removeParfume: function(parfumeId) {
            return this.removeItem(parfumeId);
        },

        getComparisonCount: function() {
            return this.comparisonData.length;
        },

        getComparisonItems: function() {
            return this.comparisonData.slice(); // Return copy
        },

        isParfumeInComparison: function(parfumeId) {
            return this.comparisonData.some(function(item) {
                return item.id == parfumeId;
            });
        }
    };

    // Инициализация при зареждане на DOM
    $(document).ready(function() {
        parfumeComparison.init();
    });

    // Интеграция с основния frontend скрипт
    if (window.parfumeCatalog) {
        window.parfumeCatalog.comparison = parfumeComparison;
    }

})(jQuery);