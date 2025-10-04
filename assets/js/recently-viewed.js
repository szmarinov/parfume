/**
 * Recently Viewed Parfumes
 * Tracks and displays recently viewed perfumes using localStorage
 */

(function() {
    'use strict';

    const RecentlyViewed = {
        storageKey: 'parfume_recently_viewed',
        maxItems: 10,
        expiryDays: 30,

        init: function() {
            // Only run on single parfume pages
            if (!document.body.classList.contains('single-parfume')) {
                return;
            }

            this.trackCurrentParfume();
            this.cleanExpired();
        },

        trackCurrentParfume: function() {
            // Get current parfume data from meta tags or data attributes
            const parfumeData = this.getCurrentParfumeData();
            
            if (!parfumeData) {
                return;
            }

            // Get existing items
            let items = this.getItems();

            // Remove if already exists (to update timestamp and move to top)
            items = items.filter(item => item.id !== parfumeData.id);

            // Add to beginning
            items.unshift(parfumeData);

            // Limit to max items
            if (items.length > this.maxItems) {
                items = items.slice(0, this.maxItems);
            }

            // Save
            this.saveItems(items);
        },

        getCurrentParfumeData: function() {
            // Try to get data from various sources
            const postId = document.body.getAttribute('data-post-id');
            const title = document.querySelector('h1.entry-title, h1.parfume-title');
            const thumbnail = document.querySelector('.parfume-thumbnail, .post-thumbnail img');
            
            if (!postId || !title) {
                return null;
            }

            return {
                id: parseInt(postId),
                title: title.textContent.trim(),
                thumbnail: thumbnail ? thumbnail.src : '',
                url: window.location.href,
                timestamp: Date.now()
            };
        },

        getItems: function() {
            try {
                const data = localStorage.getItem(this.storageKey);
                return data ? JSON.parse(data) : [];
            } catch (e) {
                console.error('Error reading recently viewed:', e);
                return [];
            }
        },

        saveItems: function(items) {
            try {
                localStorage.setItem(this.storageKey, JSON.stringify(items));
            } catch (e) {
                console.error('Error saving recently viewed:', e);
            }
        },

        cleanExpired: function() {
            const items = this.getItems();
            const now = Date.now();
            const expiryTime = this.expiryDays * 24 * 60 * 60 * 1000;

            const validItems = items.filter(item => {
                return (now - item.timestamp) < expiryTime;
            });

            if (validItems.length !== items.length) {
                this.saveItems(validItems);
            }
        },

        // Public method to get items for display
        getRecentlyViewed: function(count = 4, excludeCurrentId = null) {
            let items = this.getItems();

            // Exclude current post
            if (excludeCurrentId) {
                items = items.filter(item => item.id !== excludeCurrentId);
            }

            // Return limited number
            return items.slice(0, count);
        },

        // Public method to render items
        renderItems: function(containerId, count = 4) {
            const container = document.getElementById(containerId);
            
            if (!container) {
                return;
            }

            const currentId = parseInt(document.body.getAttribute('data-post-id'));
            const items = this.getRecentlyViewed(count, currentId);

            if (items.length === 0) {
                container.innerHTML = '<p class="no-items">Все още не сте разглеждали други парфюми</p>';
                return;
            }

            let html = '<div class="recently-viewed-grid">';
            
            items.forEach(item => {
                html += `
                    <article class="recently-viewed-item">
                        <a href="${this.escapeHtml(item.url)}" class="item-link">
                            <div class="item-image">
                                ${item.thumbnail ? 
                                    `<img src="${this.escapeHtml(item.thumbnail)}" alt="${this.escapeHtml(item.title)}">` :
                                    `<div class="no-thumbnail">
                                        <svg width="40" height="40" viewBox="0 0 24 24" fill="none">
                                            <path d="M4 16L8.586 11.414C9.367 10.633 10.633 10.633 11.414 11.414L16 16M14 14L15.586 12.414C16.367 11.633 17.633 11.633 18.414 12.414L20 14M14 8H14.01M6 20H18C18.5304 20 19.0391 19.7893 19.4142 19.4142C19.7893 19.0391 20 18.5304 20 18V6C20 5.46957 19.7893 4.96086 19.4142 4.58579C19.0391 4.21071 18.5304 4 18 4H6C5.46957 4 4.96086 4.21071 4.58579 4.58579C4.21071 4.96086 4 5.46957 4 6V18C4 18.5304 4.21071 19.0391 4.58579 19.4142C4.96086 19.7893 5.46957 20 6 20Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                    </div>`
                                }
                            </div>
                            <h3 class="item-title">${this.escapeHtml(item.title)}</h3>
                        </a>
                    </article>
                `;
            });
            
            html += '</div>';
            container.innerHTML = html;
        },

        escapeHtml: function(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        },

        // Clear all items (for admin/debug purposes)
        clear: function() {
            localStorage.removeItem(this.storageKey);
        }
    };

    // Initialize on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            RecentlyViewed.init();
        });
    } else {
        RecentlyViewed.init();
    }

    // Expose to global scope for external use
    window.ParfumeRecentlyViewed = RecentlyViewed;

})();