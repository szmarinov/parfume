/**
 * AJAX Functionality for Parfume Reviews
 * 
 * Handles:
 * - Dynamic filtering without page reload
 * - Load more pagination
 * - Search suggestions
 * - Rating submissions
 * - Collection management
 * - Price updates
 * - Cache management
 */

class ParfumeAjax {
    constructor() {
        this.ajaxUrl = parfumeAjax.ajaxUrl || '/wp-admin/admin-ajax.php';
        this.nonce = parfumeAjax.nonce || '';
        this.isLoading = false;
        this.searchTimeout = null;
        this.cache = new Map();
        this.cacheTimeout = 300000; // 5 minutes
        
        this.init();
    }
    
    init() {
        this.initFilterAjax();
        this.initSearchAjax();
        this.initLoadMore();
        this.initRatingAjax();
        this.initCollectionAjax();
        this.initPriceUpdates();
        this.initInfiniteScroll();
        this.initQuickView();
    }
    
    /**
     * Initialize dynamic filtering
     */
    initFilterAjax() {
        const filterForm = document.getElementById('parfume-filters-form');
        if (!filterForm) return;
        
        // Debounced filter change handler
        const filterInputs = filterForm.querySelectorAll('input[type="checkbox"], input[type="radio"], select');
        
        filterInputs.forEach(input => {
            input.addEventListener('change', () => {
                this.debounce(() => {
                    this.submitFilters(filterForm);
                }, 300);
            });
        });
        
        // Search input handler
        const searchInput = filterForm.querySelector('input[type="search"], input[name="s"]');
        if (searchInput) {
            searchInput.addEventListener('input', (e) => {
                this.debounce(() => {
                    this.submitFilters(filterForm);
                }, 500);
            });
        }
        
        // Prevent form submission and handle via AJAX
        filterForm.addEventListener('submit', (e) => {
            e.preventDefault();
            this.submitFilters(filterForm);
        });
    }
    
    /**
     * Submit filters via AJAX
     */
    async submitFilters(form) {
        if (this.isLoading) return;
        
        this.isLoading = true;
        this.showLoading();
        
        const formData = new FormData(form);
        formData.append('action', 'parfume_filter_posts');
        formData.append('nonce', this.nonce);
        
        // Add current page context
        const currentUrl = new URL(window.location);
        formData.append('current_taxonomy', currentUrl.pathname);
        
        try {
            const response = await this.makeRequest(formData);
            
            if (response.success) {
                this.updateResults(response.data);
                this.updateUrl(formData);
                this.updateFilterCounts(response.data.filter_counts);
            } else {
                this.showError(response.data || 'Грешка при филтриране');
            }
        } catch (error) {
            this.showError('Възникна грешка при заявката');
            console.error('Filter AJAX error:', error);
        } finally {
            this.isLoading = false;
            this.hideLoading();
        }
    }
    
    /**
     * Initialize search with suggestions
     */
    initSearchAjax() {
        const searchInputs = document.querySelectorAll('.parfume-search');
        
        searchInputs.forEach(input => {
            const suggestionsContainer = this.createSuggestionsContainer(input);
            
            input.addEventListener('input', (e) => {
                const query = e.target.value.trim();
                
                if (query.length < 2) {
                    this.hideSuggestions(suggestionsContainer);
                    return;
                }
                
                clearTimeout(this.searchTimeout);
                this.searchTimeout = setTimeout(() => {
                    this.fetchSearchSuggestions(query, suggestionsContainer);
                }, 300);
            });
            
            input.addEventListener('blur', () => {
                setTimeout(() => {
                    this.hideSuggestions(suggestionsContainer);
                }, 200);
            });
        });
    }
    
    /**
     * Fetch search suggestions
     */
    async fetchSearchSuggestions(query, container) {
        const cacheKey = `search_${query}`;
        
        // Check cache first
        if (this.cache.has(cacheKey)) {
            const cached = this.cache.get(cacheKey);
            if (Date.now() - cached.timestamp < this.cacheTimeout) {
                this.displaySuggestions(cached.data, container);
                return;
            }
        }
        
        const formData = new FormData();
        formData.append('action', 'parfume_search_suggestions');
        formData.append('query', query);
        formData.append('nonce', this.nonce);
        
        try {
            const response = await this.makeRequest(formData);
            
            if (response.success) {
                // Cache the results
                this.cache.set(cacheKey, {
                    data: response.data,
                    timestamp: Date.now()
                });
                
                this.displaySuggestions(response.data, container);
            }
        } catch (error) {
            console.error('Search suggestions error:', error);
        }
    }
    
    /**
     * Initialize load more functionality
     */
    initLoadMore() {
        const loadMoreBtn = document.querySelector('.load-more-parfumes');
        if (!loadMoreBtn) return;
        
        loadMoreBtn.addEventListener('click', async (e) => {
            e.preventDefault();
            
            if (this.isLoading) return;
            
            this.isLoading = true;
            loadMoreBtn.textContent = 'Зареждане...';
            loadMoreBtn.disabled = true;
            
            const page = parseInt(loadMoreBtn.dataset.page) || 1;
            const nextPage = page + 1;
            
            const formData = new FormData();
            formData.append('action', 'parfume_load_more');
            formData.append('page', nextPage);
            formData.append('nonce', this.nonce);
            
            // Include current filters
            const filterForm = document.getElementById('parfume-filters-form');
            if (filterForm) {
                const filterData = new FormData(filterForm);
                for (let [key, value] of filterData.entries()) {
                    formData.append(key, value);
                }
            }
            
            try {
                const response = await this.makeRequest(formData);
                
                if (response.success) {
                    this.appendResults(response.data.html);
                    loadMoreBtn.dataset.page = nextPage;
                    
                    if (!response.data.has_more) {
                        loadMoreBtn.style.display = 'none';
                    }
                } else {
                    this.showError('Грешка при зареждане на повече резултати');
                }
            } catch (error) {
                this.showError('Възникна грешка при заявката');
                console.error('Load more error:', error);
            } finally {
                this.isLoading = false;
                loadMoreBtn.textContent = 'Зареди повече';
                loadMoreBtn.disabled = false;
            }
        });
    }
    
    /**
     * Initialize infinite scroll
     */
    initInfiniteScroll() {
        if (!document.querySelector('.enable-infinite-scroll')) return;
        
        const sentinel = document.createElement('div');
        sentinel.className = 'infinite-scroll-sentinel';
        
        const container = document.querySelector('.parfume-grid, .parfume-results');
        if (!container) return;
        
        container.parentNode.insertBefore(sentinel, container.nextSibling);
        
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting && !this.isLoading) {
                    this.loadMoreContent();
                }
            });
        }, {
            rootMargin: '100px'
        });
        
        observer.observe(sentinel);
    }
    
    /**
     * Initialize rating submission
     */
    initRatingAjax() {
        const ratingForms = document.querySelectorAll('.parfume-rating-form');
        
        ratingForms.forEach(form => {
            form.addEventListener('submit', async (e) => {
                e.preventDefault();
                
                const formData = new FormData(form);
                formData.append('action', 'parfume_submit_rating');
                formData.append('nonce', this.nonce);
                
                try {
                    const response = await this.makeRequest(formData);
                    
                    if (response.success) {
                        this.updateRatingDisplay(response.data);
                        this.showSuccess('Рейтингът беше записан успешно!');
                    } else {
                        this.showError(response.data || 'Грешка при записване на рейтинга');
                    }
                } catch (error) {
                    this.showError('Възникна грешка при записване на рейтинга');
                    console.error('Rating error:', error);
                }
            });
        });
        
        // Star rating interaction
        const starRatings = document.querySelectorAll('.star-rating');
        starRatings.forEach(rating => {
            const stars = rating.querySelectorAll('.star');
            
            stars.forEach((star, index) => {
                star.addEventListener('click', () => {
                    const ratingValue = index + 1;
                    this.setStarRating(rating, ratingValue);
                    
                    // Auto-submit if enabled
                    if (rating.dataset.autoSubmit === 'true') {
                        this.submitRating(rating.closest('form'), ratingValue);
                    }
                });
                
                star.addEventListener('mouseenter', () => {
                    this.highlightStars(stars, index + 1);
                });
            });
            
            rating.addEventListener('mouseleave', () => {
                const currentRating = rating.dataset.rating || 0;
                this.highlightStars(stars, currentRating);
            });
        });
    }
    
    /**
     * Initialize collection management
     */
    initCollectionAjax() {
        // Add to collection buttons
        document.addEventListener('click', async (e) => {
            if (e.target.matches('.add-to-collection')) {
                e.preventDefault();
                
                const parfumeId = e.target.dataset.parfumeId;
                const collectionId = e.target.dataset.collectionId;
                
                await this.toggleCollection(parfumeId, collectionId, e.target);
            }
            
            // Remove from collection
            if (e.target.matches('.remove-from-collection')) {
                e.preventDefault();
                
                const parfumeId = e.target.dataset.parfumeId;
                const collectionId = e.target.dataset.collectionId;
                
                await this.removeFromCollection(parfumeId, collectionId, e.target);
            }
            
            // Create new collection
            if (e.target.matches('.create-collection')) {
                e.preventDefault();
                await this.createCollection(e.target);
            }
        });
    }
    
    /**
     * Initialize price update functionality
     */
    initPriceUpdates() {
        const updatePriceButtons = document.querySelectorAll('.update-price-btn');
        
        updatePriceButtons.forEach(button => {
            button.addEventListener('click', async (e) => {
                e.preventDefault();
                
                const storeId = button.dataset.storeId;
                const parfumeId = button.dataset.parfumeId;
                
                await this.updateStorePrice(storeId, parfumeId, button);
            });
        });
        
        // Auto-update prices on page load if needed
        if (document.querySelector('[data-auto-update-prices="true"]')) {
            setTimeout(() => {
                this.autoUpdatePrices();
            }, 2000);
        }
    }
    
    /**
     * Initialize quick view functionality
     */
    initQuickView() {
        document.addEventListener('click', async (e) => {
            if (e.target.matches('.quick-view-btn')) {
                e.preventDefault();
                
                const parfumeId = e.target.dataset.parfumeId;
                await this.openQuickView(parfumeId);
            }
        });
    }
    
    /**
     * Make AJAX request
     */
    async makeRequest(formData) {
        const response = await fetch(this.ajaxUrl, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        return await response.json();
    }
    
    /**
     * Update results container
     */
    updateResults(data) {
        const resultsContainer = document.querySelector('.parfume-results, .parfume-grid');
        if (resultsContainer) {
            resultsContainer.innerHTML = data.html;
            
            // Trigger custom event
            const event = new CustomEvent('parfumeResultsUpdated', {
                detail: { data }
            });
            document.dispatchEvent(event);
        }
        
        // Update results count
        this.updateResultsCount(data.total_found);
        
        // Update pagination
        this.updatePagination(data.pagination);
        
        // Smooth scroll to results
        if (resultsContainer) {
            resultsContainer.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    }
    
    /**
     * Append more results
     */
    appendResults(html) {
        const resultsContainer = document.querySelector('.parfume-results, .parfume-grid');
        if (resultsContainer) {
            resultsContainer.insertAdjacentHTML('beforeend', html);
            
            // Animate new items
            const newItems = resultsContainer.querySelectorAll('.parfume-card:not(.loaded)');
            newItems.forEach((item, index) => {
                item.classList.add('loaded');
                setTimeout(() => {
                    item.style.opacity = '1';
                    item.style.transform = 'translateY(0)';
                }, index * 100);
            });
        }
    }
    
    /**
     * Update URL without page reload
     */
    updateUrl(formData) {
        const url = new URL(window.location);
        
        // Clear existing params
        const keysToRemove = [];
        for (let [key] of url.searchParams) {
            if (key !== 'page') {
                keysToRemove.push(key);
            }
        }
        keysToRemove.forEach(key => url.searchParams.delete(key));
        
        // Add new params
        for (let [key, value] of formData.entries()) {
            if (key !== 'action' && key !== 'nonce' && value !== 'all' && value !== '') {
                url.searchParams.append(key, value);
            }
        }
        
        // Update URL
        window.history.pushState({}, '', url);
    }
    
    /**
     * Display search suggestions
     */
    displaySuggestions(suggestions, container) {
        if (!suggestions || suggestions.length === 0) {
            this.hideSuggestions(container);
            return;
        }
        
        let html = '<div class="search-suggestions">';
        
        suggestions.forEach(suggestion => {
            html += `
                <div class="suggestion-item" data-type="${suggestion.type}" data-id="${suggestion.id}">
                    <div class="suggestion-icon">${this.getSuggestionIcon(suggestion.type)}</div>
                    <div class="suggestion-content">
                        <div class="suggestion-title">${suggestion.title}</div>
                        <div class="suggestion-meta">${suggestion.meta}</div>
                    </div>
                </div>
            `;
        });
        
        html += '</div>';
        
        container.innerHTML = html;
        container.style.display = 'block';
        
        // Add click handlers
        container.querySelectorAll('.suggestion-item').forEach(item => {
            item.addEventListener('click', () => {
                window.location.href = item.dataset.url || '#';
            });
        });
    }
    
    /**
     * Utility functions
     */
    debounce(func, delay) {
        clearTimeout(this.searchTimeout);
        this.searchTimeout = setTimeout(func, delay);
    }
    
    showLoading() {
        const loader = document.querySelector('.parfume-loader');
        if (loader) {
            loader.style.display = 'block';
        }
        
        // Add loading class to results
        const results = document.querySelector('.parfume-results, .parfume-grid');
        if (results) {
            results.classList.add('loading');
        }
    }
    
    hideLoading() {
        const loader = document.querySelector('.parfume-loader');
        if (loader) {
            loader.style.display = 'none';
        }
        
        // Remove loading class
        const results = document.querySelector('.parfume-results, .parfume-grid');
        if (results) {
            results.classList.remove('loading');
        }
    }
    
    showError(message) {
        this.showNotification(message, 'error');
    }
    
    showSuccess(message) {
        this.showNotification(message, 'success');
    }
    
    showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `parfume-notification notification-${type}`;
        notification.textContent = message;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.classList.add('show');
        }, 100);
        
        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => {
                document.body.removeChild(notification);
            }, 300);
        }, 3000);
    }
    
    createSuggestionsContainer(input) {
        const container = document.createElement('div');
        container.className = 'search-suggestions-container';
        container.style.display = 'none';
        
        input.parentNode.style.position = 'relative';
        input.parentNode.appendChild(container);
        
        return container;
    }
    
    hideSuggestions(container) {
        container.style.display = 'none';
    }
    
    getSuggestionIcon(type) {
        const icons = {
            parfume: '🌺',
            brand: '🏷️',
            note: '🌿',
            perfumer: '👨‍🔬'
        };
        
        return icons[type] || '🔍';
    }
    
    updateResultsCount(count) {
        const countElement = document.querySelector('.results-count');
        if (countElement) {
            countElement.textContent = count;
        }
    }
    
    updatePagination(paginationHtml) {
        const paginationContainer = document.querySelector('.pagination-container');
        if (paginationContainer && paginationHtml) {
            paginationContainer.innerHTML = paginationHtml;
        }
    }
    
    updateFilterCounts(counts) {
        if (!counts) return;
        
        Object.entries(counts).forEach(([taxonomy, termCounts]) => {
            Object.entries(termCounts).forEach(([termId, count]) => {
                const countElement = document.querySelector(`[data-term-id="${termId}"] .option-count`);
                if (countElement) {
                    countElement.textContent = `(${count})`;
                }
            });
        });
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    if (typeof parfumeAjax !== 'undefined') {
        window.parfumeAjaxHandler = new ParfumeAjax();
    }
});

// Global functions for backwards compatibility
window.parfumeSubmitFilters = function(form) {
    if (window.parfumeAjaxHandler) {
        window.parfumeAjaxHandler.submitFilters(form);
    }
};

window.parfumeLoadMore = function(button) {
    if (window.parfumeAjaxHandler) {
        button.click();
    }
};