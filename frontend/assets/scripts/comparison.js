/**
 * Parfume Comparison JavaScript
 * 
 * Handles all comparison functionality including:
 * - Add/remove from comparison
 * - Widget management
 * - Popup functionality
 * - Cookie management
 */

(function($) {
    'use strict';
    
    class ParfumeComparison {
        constructor() {
            this.comparisonList = this.getComparisonList();
            this.maxItems = parseInt(parfumeComparison.maxItems) || 4;
            this.widget = null;
            this.popup = null;
            
            this.init();
        }
        
        init() {
            this.bindEvents();
            this.updateButtons();
            this.initWidget();
            this.initPopup();
            this.updateWidget();
        }
        
        bindEvents() {
            // Compare button clicks
            $(document).on('click', '.parfume-compare-btn', (e) => {
                e.preventDefault();
                this.handleCompareButton($(e.currentTarget));
            });
            
            // Widget events
            $(document).on('click', '.widget-toggle', (e) => {
                this.toggleWidget();
            });
            
            $(document).on('click', '.widget-close', (e) => {
                this.hideWidget();
            });
            
            $(document).on('click', '.compare-view-btn', (e) => {
                this.showPopup();
            });
            
            $(document).on('click', '.compare-clear-btn', (e) => {
                this.clearComparison();
            });
            
            $(document).on('click', '.compare-item-remove', (e) => {
                const perfumeId = $(e.currentTarget).data('perfume-id');
                this.removeFromComparison(perfumeId);
            });
            
            // Popup events
            $(document).on('click', '.popup-close, .popup-close-btn, .popup-overlay', (e) => {
                if (e.target === e.currentTarget) {
                    this.hidePopup();
                }
            });
            
            $(document).on('click', '.remove-from-compare', (e) => {
                const perfumeId = $(e.currentTarget).data('perfume-id');
                this.removeFromComparison(perfumeId);
            });
            
            // Keyboard events
            $(document).on('keydown', (e) => {
                if (e.key === 'Escape' && this.popup && this.popup.is(':visible')) {
                    this.hidePopup();
                }
            });
        }
        
        handleCompareButton(button) {
            const perfumeId = parseInt(button.data('perfume-id'));
            const isInComparison = this.comparisonList.includes(perfumeId);
            
            if (isInComparison) {
                this.removeFromComparison(perfumeId);
            } else {
                this.addToComparison(perfumeId, button);
            }
        }
        
        addToComparison(perfumeId, button = null) {
            if (this.comparisonList.includes(perfumeId)) {
                this.showMessage(parfumeComparison.strings.error, 'error');
                return;
            }
            
            if (this.comparisonList.length >= this.maxItems) {
                this.showMessage(parfumeComparison.strings.maxItems, 'warning');
                return;
            }
            
            if (button) {
                button.addClass('loading');
            }
            
            const data = {
                action: 'add_to_comparison',
                perfume_id: perfumeId,
                nonce: parfumeComparison.nonce
            };
            
            $.ajax({
                url: parfumeComparison.ajaxurl,
                type: 'POST',
                data: data,
                success: (response) => {
                    if (response.success) {
                        this.comparisonList.push(perfumeId);
                        this.setComparisonList(this.comparisonList);
                        this.updateButtons();
                        this.updateWidget();
                        this.showWidget();
                        this.showMessage(parfumeComparison.strings.added, 'success');
                    } else {
                        this.showMessage(response.data || parfumeComparison.strings.error, 'error');
                    }
                },
                error: () => {
                    this.showMessage(parfumeComparison.strings.error, 'error');
                },
                complete: () => {
                    if (button) {
                        button.removeClass('loading');
                    }
                }
            });
        }
        
        removeFromComparison(perfumeId) {
            if (!this.comparisonList.includes(perfumeId)) {
                return;
            }
            
            const data = {
                action: 'remove_from_comparison',
                perfume_id: perfumeId,
                nonce: parfumeComparison.nonce
            };
            
            $.ajax({
                url: parfumeComparison.ajaxurl,
                type: 'POST',
                data: data,
                success: (response) => {
                    if (response.success) {
                        this.comparisonList = this.comparisonList.filter(id => id !== perfumeId);
                        this.setComparisonList(this.comparisonList);
                        this.updateButtons();
                        this.updateWidget();
                        
                        if (this.comparisonList.length === 0) {
                            this.hideWidget();
                            this.hidePopup();
                        }
                        
                        this.showMessage(parfumeComparison.strings.removed, 'success');
                    } else {
                        this.showMessage(response.data || parfumeComparison.strings.error, 'error');
                    }
                },
                error: () => {
                    this.showMessage(parfumeComparison.strings.error, 'error');
                }
            });
        }
        
        clearComparison() {
            if (this.comparisonList.length === 0) {
                return;
            }
            
            if (!confirm('Сигурни ли сте, че искате да изчистите сравняването?')) {
                return;
            }
            
            // Remove each item individually to maintain consistency
            const itemsToRemove = [...this.comparisonList];
            itemsToRemove.forEach(perfumeId => {
                this.removeFromComparison(perfumeId);
            });
        }
        
        initWidget() {
            this.widget = $('#parfume-compare-widget');
            if (this.widget.length === 0) {
                // Create widget if it doesn't exist
                this.createWidget();
            }
        }
        
        createWidget() {
            const widgetHtml = `
                <div id="parfume-compare-widget" class="parfume-compare-widget position-bottom-right auto-hide" style="display: none;">
                    <div class="widget-header">
                        <h4>Сравняване</h4>
                        <button class="widget-toggle" type="button">
                            <span class="toggle-icon">−</span>
                        </button>
                        <button class="widget-close" type="button">×</button>
                    </div>
                    
                    <div class="widget-content">
                        <div class="compare-items" id="compare-items-list">
                            <!-- Items will be populated dynamically -->
                        </div>
                        
                        <div class="widget-actions">
                            <button type="button" class="compare-view-btn" disabled>
                                Сравни (<span class="compare-count">0</span>)
                            </button>
                            <button type="button" class="compare-clear-btn">
                                Изчисти
                            </button>
                        </div>
                    </div>
                </div>
            `;
            
            $('body').append(widgetHtml);
            this.widget = $('#parfume-compare-widget');
        }
        
        updateWidget() {
            if (!this.widget || this.widget.length === 0) {
                return;
            }
            
            const count = this.comparisonList.length;
            const itemsList = this.widget.find('#compare-items-list');
            const countSpan = this.widget.find('.compare-count');
            const viewBtn = this.widget.find('.compare-view-btn');
            
            // Update count
            countSpan.text(count);
            
            // Enable/disable view button
            viewBtn.prop('disabled', count < 2);
            
            // Clear current items
            itemsList.empty();
            
            if (count === 0) {
                this.hideWidget();
                return;
            }
            
            // Add items to widget
            this.comparisonList.forEach(perfumeId => {
                this.addItemToWidget(perfumeId);
            });
        }
        
        addItemToWidget(perfumeId) {
            const itemsList = this.widget.find('#compare-items-list');
            
            // Get perfume data (simplified - in real implementation you might want to cache this)
            const perfumeTitle = $(`.parfume-compare-btn[data-perfume-id="${perfumeId}"]`).data('perfume-title') || `Парфюм ${perfumeId}`;
            const perfumeUrl = $(`.parfume-compare-btn[data-perfume-id="${perfumeId}"]`).closest('.parfume-card').find('a').first().attr('href') || '#';
            const perfumeThumbnail = $(`.parfume-compare-btn[data-perfume-id="${perfumeId}"]`).closest('.parfume-card').find('img').first().attr('src') || '';
            
            const itemHtml = `
                <div class="compare-item" data-perfume-id="${perfumeId}">
                    ${perfumeThumbnail ? `
                        <div class="compare-item-image">
                            <img src="${perfumeThumbnail}" alt="${perfumeTitle}" />
                        </div>
                    ` : ''}
                    <div class="compare-item-info">
                        <h5 class="compare-item-title">${perfumeTitle}</h5>
                    </div>
                    <button class="compare-item-remove" data-perfume-id="${perfumeId}" title="Премахни">×</button>
                </div>
            `;
            
            itemsList.append(itemHtml);
        }
        
        showWidget() {
            if (this.widget && this.comparisonList.length > 0) {
                this.widget.fadeIn(300);
            }
        }
        
        hideWidget() {
            if (this.widget) {
                this.widget.fadeOut(300);
            }
        }
        
        toggleWidget() {
            if (!this.widget) return;
            
            const content = this.widget.find('.widget-content');
            const toggleIcon = this.widget.find('.toggle-icon');
            
            if (content.is(':visible')) {
                content.slideUp(300);
                toggleIcon.text('+');
                this.widget.addClass('minimized');
            } else {
                content.slideDown(300);
                toggleIcon.text('−');
                this.widget.removeClass('minimized');
            }
        }
        
        initPopup() {
            this.popup = $('#parfume-compare-popup');
            if (this.popup.length === 0) {
                this.createPopup();
            }
        }
        
        createPopup() {
            const popupHtml = `
                <div id="parfume-compare-popup" class="parfume-compare-popup" style="display: none;">
                    <div class="popup-overlay"></div>
                    <div class="popup-content">
                        <div class="popup-header">
                            <h3>Сравняване на парфюми</h3>
                            <button class="popup-close" type="button">×</button>
                        </div>
                        
                        <div class="popup-body" id="compare-popup-body">
                            <div class="loading-spinner">
                                <div class="spinner"></div>
                                <p>Зареждане...</p>
                            </div>
                        </div>
                        
                        <div class="popup-footer">
                            <button type="button" class="popup-close-btn">
                                Затвори
                            </button>
                        </div>
                    </div>
                </div>
            `;
            
            $('body').append(popupHtml);
            this.popup = $('#parfume-compare-popup');
        }
        
        showPopup() {
            if (this.comparisonList.length < 2) {
                this.showMessage(parfumeComparison.strings.minItems, 'warning');
                return;
            }
            
            if (!this.popup) {
                this.initPopup();
            }
            
            const popupBody = this.popup.find('#compare-popup-body');
            
            // Show loading
            popupBody.html(`
                <div class="loading-spinner">
                    <div class="spinner"></div>
                    <p>${parfumeComparison.strings.loading}</p>
                </div>
            `);
            
            this.popup.fadeIn(300);
            $('body').addClass('popup-open');
            
            // Load comparison data
            this.loadComparisonData();
        }
        
        hidePopup() {
            if (this.popup) {
                this.popup.fadeOut(300);
                $('body').removeClass('popup-open');
            }
        }
        
        loadComparisonData() {
            const data = {
                action: 'get_comparison_data',
                nonce: parfumeComparison.nonce
            };
            
            $.ajax({
                url: parfumeComparison.ajaxurl,
                type: 'POST',
                data: data,
                success: (response) => {
                    if (response.success) {
                        const popupBody = this.popup.find('#compare-popup-body');
                        popupBody.html(response.data.html);
                    } else {
                        this.showMessage(response.data || parfumeComparison.strings.error, 'error');
                        this.hidePopup();
                    }
                },
                error: () => {
                    this.showMessage(parfumeComparison.strings.error, 'error');
                    this.hidePopup();
                }
            });
        }
        
        updateButtons() {
            $('.parfume-compare-btn').each((index, button) => {
                const $button = $(button);
                const perfumeId = parseInt($button.data('perfume-id'));
                const isInComparison = this.comparisonList.includes(perfumeId);
                const text = $button.data('text');
                const addedText = $button.data('added-text');
                
                if (isInComparison) {
                    $button.addClass('added');
                    $button.find('.compare-text').text(addedText);
                } else {
                    $button.removeClass('added');
                    $button.find('.compare-text').text(text);
                }
            });
        }
        
        getComparisonList() {
            const cookieValue = this.getCookie('parfume_comparison');
            if (cookieValue) {
                return cookieValue.split(',').map(id => parseInt(id)).filter(id => !isNaN(id));
            }
            return [];
        }
        
        setComparisonList(list) {
            const cookieValue = list.join(',');
            const expiryDate = new Date();
            expiryDate.setTime(expiryDate.getTime() + (30 * 24 * 60 * 60 * 1000)); // 30 days
            
            document.cookie = `parfume_comparison=${cookieValue}; expires=${expiryDate.toUTCString()}; path=/; SameSite=Lax`;
        }
        
        getCookie(name) {
            const nameEQ = name + "=";
            const ca = document.cookie.split(';');
            for (let i = 0; i < ca.length; i++) {
                let c = ca[i];
                while (c.charAt(0) === ' ') c = c.substring(1, c.length);
                if (c.indexOf(nameEQ) === 0) return c.substring(nameEQ.length, c.length);
            }
            return null;
        }
        
        showMessage(message, type = 'info') {
            // Create toast notification
            const toastId = 'parfume-toast-' + Date.now();
            const toastClass = `parfume-toast toast-${type}`;
            
            const toastHtml = `
                <div id="${toastId}" class="${toastClass}" style="position: fixed; top: 20px; right: 20px; z-index: 10001; padding: 12px 20px; border-radius: 6px; color: white; font-weight: 500; box-shadow: 0 4px 12px rgba(0,0,0,0.15); max-width: 300px; transform: translateX(100%); transition: transform 0.3s ease;">
                    ${message}
                    <button class="toast-close" style="background: none; border: none; color: white; margin-left: 10px; cursor: pointer; font-size: 16px;">×</button>
                </div>
            `;
            
            $('body').append(toastHtml);
            const toast = $(`#${toastId}`);
            
            // Set colors based on type
            const colors = {
                success: '#28a745',
                error: '#dc3545',
                warning: '#ffc107',
                info: '#17a2b8'
            };
            
            toast.css('background', colors[type] || colors.info);
            
            // Animate in
            setTimeout(() => {
                toast.css('transform', 'translateX(0)');
            }, 100);
            
            // Close button
            toast.find('.toast-close').on('click', () => {
                this.hideToast(toast);
            });
            
            // Auto hide after 5 seconds
            setTimeout(() => {
                this.hideToast(toast);
            }, 5000);
        }
        
        hideToast(toast) {
            toast.css('transform', 'translateX(100%)');
            setTimeout(() => {
                toast.remove();
            }, 300);
        }
        
        // Public API methods
        addPerfume(perfumeId) {
            this.addToComparison(parseInt(perfumeId));
        }
        
        removePerfume(perfumeId) {
            this.removeFromComparison(parseInt(perfumeId));
        }
        
        clearAll() {
            this.clearComparison();
        }
        
        getComparisonCount() {
            return this.comparisonList.length;
        }
        
        getComparisonItems() {
            return [...this.comparisonList];
        }
        
        showComparisonPopup() {
            this.showPopup();
        }
    }
    
    // Initialize when document is ready
    $(document).ready(() => {
        // Only initialize if we have the necessary data
        if (typeof parfumeComparison !== 'undefined') {
            window.parfumeComparisonInstance = new ParfumeComparison();
        }
    });
    
    // Expose API globally
    window.ParfumeComparison = {
        add: (perfumeId) => {
            if (window.parfumeComparisonInstance) {
                window.parfumeComparisonInstance.addPerfume(perfumeId);
            }
        },
        remove: (perfumeId) => {
            if (window.parfumeComparisonInstance) {
                window.parfumeComparisonInstance.removePerfume(perfumeId);
            }
        },
        clear: () => {
            if (window.parfumeComparisonInstance) {
                window.parfumeComparisonInstance.clearAll();
            }
        },
        show: () => {
            if (window.parfumeComparisonInstance) {
                window.parfumeComparisonInstance.showComparisonPopup();
            }
        },
        count: () => {
            return window.parfumeComparisonInstance ? window.parfumeComparisonInstance.getComparisonCount() : 0;
        },
        items: () => {
            return window.parfumeComparisonInstance ? window.parfumeComparisonInstance.getComparisonItems() : [];
        }
    };

})(jQuery);