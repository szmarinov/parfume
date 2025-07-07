(function($) {
    'use strict';

    var comparison = {
        items: [],
        maxItems: 4,
        popupVisible: false,

        init: function() {
            this.loadFromStorage();
            this.bindEvents();
            this.updateUI();
        },

        bindEvents: function() {
            var self = this;

            // Add/Remove comparison buttons
            $(document).on('click', '.add-to-comparison', function(e) {
                e.preventDefault();
                var productId = $(this).data('product-id');
                self.toggleItem(productId);
            });

            // Popup controls
            $(document).on('click', '.comparison-popup-toggle', function(e) {
                e.preventDefault();
                self.togglePopup();
            });

            $(document).on('click', '.comparison-popup .close-popup', function(e) {
                e.preventDefault();
                self.hidePopup();
            });

            // Remove from comparison in popup
            $(document).on('click', '.remove-from-comparison', function(e) {
                e.preventDefault();
                var productId = $(this).data('product-id');
                self.removeItem(productId);
            });

            // Clear all
            $(document).on('click', '.clear-comparison', function(e) {
                e.preventDefault();
                if (confirm('Сигурни ли сте, че искате да изчистите всички продукти от сравнението?')) {
                    self.clearAll();
                }
            });

            // Add from search in popup
            $(document).on('keyup', '.comparison-search', function() {
                var search = $(this).val();
                if (search.length >= 2) {
                    self.searchProducts(search);
                } else {
                    $('.comparison-search-results').hide();
                }
            });

            $(document).on('click', '.comparison-search-result', function(e) {
                e.preventDefault();
                var productId = $(this).data('product-id');
                self.addItem(productId);
                $('.comparison-search').val('');
                $('.comparison-search-results').hide();
            });

            // Close popup on ESC
            $(document).on('keydown', function(e) {
                if (e.keyCode === 27 && self.popupVisible) {
                    self.hidePopup();
                }
            });

            // Close popup on overlay click
            $(document).on('click', '.comparison-popup-overlay', function(e) {
                if (e.target === this) {
                    self.hidePopup();
                }
            });
        },

        toggleItem: function(productId) {
            if (this.hasItem(productId)) {
                this.removeItem(productId);
            } else {
                this.addItem(productId);
            }
        },

        addItem: function(productId) {
            if (this.hasItem(productId)) {
                this.showMessage('Този продукт вече е добавен за сравнение', 'warning');
                return;
            }

            if (this.items.length >= this.maxItems) {
                this.showMessage('Максималният брой продукти за сравнение е ' + this.maxItems, 'error');
                return;
            }

            var self = this;
            
            // Get product data
            $.ajax({
                url: parfumeCatalog.ajax_url,
                type: 'POST',
                data: {
                    action: 'get_product_for_comparison',
                    product_id: productId,
                    nonce: parfumeCatalog.nonce
                },
                success: function(response) {
                    if (response.success) {
                        self.items.push(response.data);
                        self.saveToStorage();
                        self.updateUI();
                        self.showMessage('Продуктът е добавен за сравнение', 'success');
                        
                        if (self.items.length >= 2) {
                            self.showComparisonButton();
                        }
                    } else {
                        self.showMessage('Грешка при добавяне на продукта', 'error');
                    }
                },
                error: function() {
                    self.showMessage('Грешка при свързване със сървъра', 'error');
                }
            });
        },

        removeItem: function(productId) {
            this.items = this.items.filter(function(item) {
                return item.id != productId;
            });
            
            this.saveToStorage();
            this.updateUI();
            this.showMessage('Продуктът е премахнат от сравнението', 'info');

            if (this.items.length < 2) {
                this.hideComparisonButton();
                if (this.items.length === 0) {
                    this.hidePopup();
                }
            }
        },

        hasItem: function(productId) {
            return this.items.some(function(item) {
                return item.id == productId;
            });
        },

        clearAll: function() {
            this.items = [];
            this.saveToStorage();
            this.updateUI();
            this.hidePopup();
            this.hideComparisonButton();
            this.showMessage('Всички продукти са премахнати от сравнението', 'info');
        },

        showPopup: function() {
            if (this.items.length < 2) {
                this.showMessage('Трябват поне 2 продукта за сравнение', 'warning');
                return;
            }

            this.renderComparisonTable();
            $('.comparison-popup-overlay').fadeIn(300);
            $('body').addClass('comparison-popup-open');
            this.popupVisible = true;
        },

        hidePopup: function() {
            $('.comparison-popup-overlay').fadeOut(300);
            $('body').removeClass('comparison-popup-open');
            this.popupVisible = false;
        },

        togglePopup: function() {
            if (this.popupVisible) {
                this.hidePopup();
            } else {
                this.showPopup();
            }
        },

        renderComparisonTable: function() {
            if (this.items.length === 0) {
                $('.comparison-popup-content').html('<p>Няма продукти за сравнение</p>');
                return;
            }

            var html = '<div class="comparison-table-wrapper">';
            html += '<table class="comparison-table">';
            
            // Header with product images and names
            html += '<thead><tr><th class="characteristic-column">Характеристика</th>';
            this.items.forEach(function(item) {
                html += '<th class="product-column">';
                html += '<div class="product-header">';
                html += '<button class="remove-from-comparison" data-product-id="' + item.id + '" title="Премахни">×</button>';
                html += '<img src="' + item.image + '" alt="' + item.name + '">';
                html += '<h4>' + item.name + '</h4>';
                html += '</div>';
                html += '</th>';
            });
            html += '</tr></thead>';

            // Body with characteristics
            html += '<tbody>';
            
            var characteristics = this.getComparisonCharacteristics();
            
            characteristics.forEach(function(char) {
                html += '<tr>';
                html += '<td class="characteristic-name">' + char.label + '</td>';
                
                this.items.forEach(function(item) {
                    var value = item[char.key] || '-';
                    html += '<td class="characteristic-value">' + value + '</td>';
                });
                
                html += '</tr>';
            }.bind(this));
            
            html += '</tbody>';
            html += '</table>';
            html += '</div>';

            // Add controls
            html += '<div class="comparison-controls">';
            html += '<button class="button button-primary clear-comparison">Изчисти всички</button>';
            html += '<div class="comparison-search-container">';
            html += '<input type="text" class="comparison-search" placeholder="Търси продукти за добавяне...">';
            html += '<div class="comparison-search-results" style="display: none;"></div>';
            html += '</div>';
            html += '</div>';

            $('.comparison-popup-content').html(html);
        },

        getComparisonCharacteristics: function() {
            return [
                { key: 'brand', label: 'Марка' },
                { key: 'type', label: 'Тип' },
                { key: 'concentration', label: 'Концентрация' },
                { key: 'top_notes', label: 'Върхни нотки' },
                { key: 'middle_notes', label: 'Средни нотки' },
                { key: 'base_notes', label: 'Базови нотки' },
                { key: 'longevity', label: 'Дълготрайност' },
                { key: 'sillage', label: 'Ароматна следа' },
                { key: 'season', label: 'Сезон' },
                { key: 'price_range', label: 'Ценова категория' },
                { key: 'rating', label: 'Рейтинг' }
            ];
        },

        searchProducts: function(search) {
            var self = this;
            
            $.ajax({
                url: parfumeCatalog.ajax_url,
                type: 'POST',
                data: {
                    action: 'search_products_for_comparison',
                    search: search,
                    exclude: this.items.map(function(item) { return item.id; }),
                    nonce: parfumeCatalog.nonce
                },
                success: function(response) {
                    if (response.success) {
                        var html = '';
                        response.data.forEach(function(product) {
                            html += '<div class="comparison-search-result" data-product-id="' + product.id + '">';
                            html += '<img src="' + product.image + '" alt="' + product.name + '">';
                            html += '<span>' + product.name + '</span>';
                            html += '</div>';
                        });
                        $('.comparison-search-results').html(html).show();
                    }
                }
            });
        },

        updateUI: function() {
            var self = this;
            
            // Update all add/remove buttons
            $('.add-to-comparison').each(function() {
                var productId = $(this).data('product-id');
                var button = $(this);
                
                if (self.hasItem(productId)) {
                    button.removeClass('add-to-comparison')
                          .addClass('remove-from-comparison')
                          .html('<span class="dashicons dashicons-minus"></span> Премахни от сравнение');
                } else {
                    button.removeClass('remove-from-comparison')
                          .addClass('add-to-comparison')
                          .html('<span class="dashicons dashicons-plus"></span> Добави за сравнение');
                }
            });

            // Update comparison counter
            $('.comparison-count').text(this.items.length);
            
            // Update comparison button visibility
            if (this.items.length >= 2) {
                this.showComparisonButton();
            } else {
                this.hideComparisonButton();
            }
        },

        showComparisonButton: function() {
            if ($('.comparison-float-button').length === 0) {
                var button = '<div class="comparison-float-button">' +
                           '<button class="comparison-popup-toggle">' +
                           '<span class="dashicons dashicons-analytics"></span>' +
                           'Сравни (<span class="comparison-count">' + this.items.length + '</span>)' +
                           '</button>' +
                           '</div>';
                $('body').append(button);
            }
            $('.comparison-float-button').fadeIn();
        },

        hideComparisonButton: function() {
            $('.comparison-float-button').fadeOut();
        },

        showMessage: function(message, type) {
            var messageClass = 'comparison-message-' + type;
            var messageHtml = '<div class="comparison-message ' + messageClass + '">' + message + '</div>';
            
            $('body').append(messageHtml);
            
            var messageEl = $('.comparison-message').last();
            messageEl.fadeIn(300);
            
            setTimeout(function() {
                messageEl.fadeOut(300, function() {
                    $(this).remove();
                });
            }, 3000);
        },

        saveToStorage: function() {
            try {
                localStorage.setItem('parfume_comparison', JSON.stringify(this.items));
            } catch(e) {
                console.warn('Could not save to localStorage:', e);
            }
        },

        loadFromStorage: function() {
            try {
                var stored = localStorage.getItem('parfume_comparison');
                if (stored) {
                    this.items = JSON.parse(stored);
                }
            } catch(e) {
                console.warn('Could not load from localStorage:', e);
                this.items = [];
            }
        }
    };

    // Initialize when DOM is ready
    $(document).ready(function() {
        // Get max items from settings
        if (typeof parfumeCatalog !== 'undefined' && parfumeCatalog.max_comparison_items) {
            comparison.maxItems = parseInt(parfumeCatalog.max_comparison_items);
        }
        
        comparison.init();
        
        // Create popup HTML if it doesn't exist
        if ($('.comparison-popup-overlay').length === 0) {
            var popupHtml = '<div class="comparison-popup-overlay" style="display: none;">' +
                          '<div class="comparison-popup">' +
                          '<div class="comparison-popup-header">' +
                          '<h3>Сравнение на парфюми</h3>' +
                          '<button class="close-popup">&times;</button>' +
                          '</div>' +
                          '<div class="comparison-popup-content"></div>' +
                          '</div>' +
                          '</div>';
            $('body').append(popupHtml);
        }
    });

    // Make comparison object available globally
    window.parfumeComparison = comparison;

})(jQuery);