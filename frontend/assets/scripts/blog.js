/**
 * Blog JavaScript for Parfume Reviews
 * Handles blog-specific interactions and functionality
 */

(function($) {
    'use strict';
    
    const ParfumeBlog = {
        
        init: function() {
            this.bindEvents();
            this.initializeBlogFeatures();
            this.setupLazyLoading();
            this.initReadingProgress();
            this.initSocialSharing();
        },
        
        bindEvents: function() {
            // Blog search enhancement
            $(document).on('input', '.blog-search-input', this.debounce(this.handleLiveSearch, 300));
            
            // Category filtering
            $(document).on('click', '.blog-category-filter', this.handleCategoryFilter);
            
            // Load more posts
            $(document).on('click', '.blog-load-more', this.handleLoadMore);
            
            // Reading time calculation
            $(document).on('DOMContentLoaded', this.calculateReadingTime);
            
            // Social sharing tracking
            $(document).on('click', '.sharing-button', this.trackSocialShare);
            
            // Post views tracking
            if ($('body').hasClass('single-parfume-blog')) {
                this.trackPostView();
            }
            
            // Smooth scroll for blog navigation
            $(document).on('click', '.blog-nav-item', this.smoothScrollNavigation);
            
            // Blog card hover effects
            $(document).on('mouseenter', '.blog-card', this.handleCardHover);
            $(document).on('mouseleave', '.blog-card', this.handleCardLeave);
            
            // Enhanced search with filters
            $(document).on('submit', '.blog-search-form', this.handleSearchSubmit);
            
            // Infinite scroll (optional)
            if (parfumeBlog.infiniteScroll) {
                this.initInfiniteScroll();
            }
        },
        
        initializeBlogFeatures: function() {
            // Initialize tooltips for blog meta
            this.initTooltips();
            
            // Setup reading progress bar
            this.setupReadingProgress();
            
            // Initialize image lightbox
            this.initImageLightbox();
            
            // Setup sticky sidebar
            this.initStickySidebar();
            
            // Initialize blog animations
            this.initBlogAnimations();
        },
        
        handleLiveSearch: function(e) {
            const searchTerm = $(e.target).val();
            const $resultsContainer = $('.blog-search-results');
            
            if (searchTerm.length < 3) {
                $resultsContainer.hide();
                return;
            }
            
            $.ajax({
                url: parfumeBlog.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'parfume_blog_live_search',
                    search_term: searchTerm,
                    nonce: parfumeBlog.nonce
                },
                beforeSend: function() {
                    $resultsContainer.html('<div class="search-loading">Searching...</div>').show();
                },
                success: function(response) {
                    if (response.success) {
                        $resultsContainer.html(response.data.html);
                    } else {
                        $resultsContainer.html('<div class="search-no-results">No results found</div>');
                    }
                },
                error: function() {
                    $resultsContainer.html('<div class="search-error">Search error occurred</div>');
                }
            });
        },
        
        handleCategoryFilter: function(e) {
            e.preventDefault();
            
            const $this = $(this);
            const category = $this.data('category');
            const $container = $('.blog-posts-grid');
            
            // Add loading state
            $container.addClass('loading');
            
            $.ajax({
                url: parfumeBlog.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'parfume_blog_filter_posts',
                    category: category,
                    nonce: parfumeBlog.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $container.html(response.data.html).removeClass('loading');
                        
                        // Update active state
                        $('.blog-category-filter').removeClass('active');
                        $this.addClass('active');
                        
                        // Re-initialize animations
                        ParfumeBlog.initBlogAnimations();
                    }
                },
                error: function() {
                    $container.removeClass('loading');
                    console.error('Filter request failed');
                }
            });
        },
        
        handleLoadMore: function(e) {
            e.preventDefault();
            
            const $button = $(this);
            const page = parseInt($button.data('page')) + 1;
            const $container = $('.blog-posts-grid');
            
            $.ajax({
                url: parfumeBlog.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'parfume_blog_load_more',
                    page: page,
                    nonce: parfumeBlog.nonce
                },
                beforeSend: function() {
                    $button.text('Loading...').prop('disabled', true);
                },
                success: function(response) {
                    if (response.success) {
                        $container.append(response.data.html);
                        $button.data('page', page);
                        
                        if (!response.data.has_more) {
                            $button.hide();
                        } else {
                            $button.text('Load More Posts').prop('disabled', false);
                        }
                        
                        // Re-initialize animations for new posts
                        ParfumeBlog.initBlogAnimations();
                    }
                },
                error: function() {
                    $button.text('Load More Posts').prop('disabled', false);
                }
            });
        },
        
        calculateReadingTime: function() {
            const $content = $('.blog-post-content');
            if ($content.length === 0) return;
            
            const text = $content.text();
            const words = text.trim().split(/\s+/).length;
            const readingTime = Math.ceil(words / 200); // 200 words per minute
            
            $('.reading-time-dynamic').text(readingTime + ' min read');
        },
        
        trackSocialShare: function(e) {
            const network = $(this).attr('class').match(/sharing-(\w+)/)[1];
            
            // Track in analytics (if available)
            if (typeof gtag !== 'undefined') {
                gtag('event', 'share', {
                    method: network,
                    content_type: 'blog_post',
                    item_id: $('body').attr('data-post-id') || 'unknown'
                });
            }
            
            // Track internally
            $.ajax({
                url: parfumeBlog.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'parfume_blog_track_share',
                    post_id: $('body').attr('data-post-id'),
                    network: network,
                    nonce: parfumeBlog.nonce
                }
            });
        },
        
        trackPostView: function() {
            const postId = $('body').attr('data-post-id');
            if (!postId) return;
            
            // Wait 10 seconds before tracking view (to ensure engagement)
            setTimeout(function() {
                $.ajax({
                    url: parfumeBlog.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'parfume_blog_track_view',
                        post_id: postId,
                        nonce: parfumeBlog.nonce
                    }
                });
            }, 10000);
        },
        
        smoothScrollNavigation: function(e) {
            const href = $(this).attr('href');
            if (href && href.indexOf('#') === 0) {
                e.preventDefault();
                
                $('html, body').animate({
                    scrollTop: $(href).offset().top - 100
                }, 800, 'easeInOutCubic');
            }
        },
        
        handleCardHover: function() {
            $(this).find('.blog-card-image img').css('transform', 'scale(1.05)');
        },
        
        handleCardLeave: function() {
            $(this).find('.blog-card-image img').css('transform', 'scale(1)');
        },
        
        handleSearchSubmit: function(e) {
            e.preventDefault();
            
            const $form = $(this);
            const searchTerm = $form.find('.blog-search-input').val();
            
            if (searchTerm.length < 2) {
                alert('Please enter at least 2 characters to search.');
                return;
            }
            
            // Add loading indicator
            $form.addClass('searching');
            
            // Submit form normally or via AJAX
            if (parfumeBlog.ajaxSearch) {
                this.performAjaxSearch(searchTerm);
            } else {
                $form[0].submit();
            }
        },
        
        performAjaxSearch: function(searchTerm) {
            $.ajax({
                url: parfumeBlog.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'parfume_blog_search',
                    search_term: searchTerm,
                    nonce: parfumeBlog.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $('.blog-posts-grid').html(response.data.html);
                        $('.blog-search-form').removeClass('searching');
                        
                        // Update URL without refresh
                        if (history.pushState) {
                            const newUrl = window.location.pathname + '?s=' + encodeURIComponent(searchTerm);
                            history.pushState({path: newUrl}, '', newUrl);
                        }
                    }
                },
                error: function() {
                    $('.blog-search-form').removeClass('searching');
                }
            });
        },
        
        initTooltips: function() {
            $('.blog-meta-item[title]').each(function() {
                $(this).tooltip({
                    placement: 'top',
                    trigger: 'hover',
                    delay: { show: 500, hide: 100 }
                });
            });
        },
        
        setupReadingProgress: function() {
            const $progressBar = $('<div class="reading-progress"><div class="reading-progress-fill"></div></div>');
            $('body').append($progressBar);
            
            $(window).on('scroll', function() {
                const $content = $('.blog-post-content');
                if ($content.length === 0) return;
                
                const contentTop = $content.offset().top;
                const contentHeight = $content.outerHeight();
                const windowTop = $(window).scrollTop();
                const windowHeight = $(window).height();
                
                const progress = Math.min(100, Math.max(0, 
                    ((windowTop + windowHeight - contentTop) / contentHeight) * 100
                ));
                
                $('.reading-progress-fill').css('width', progress + '%');
            });
        },
        
        initImageLightbox: function() {
            $('.blog-post-content img').on('click', function() {
                const $img = $(this);
                const src = $img.attr('src');
                const alt = $img.attr('alt') || '';
                
                const $lightbox = $(`
                    <div class="blog-lightbox">
                        <div class="lightbox-overlay"></div>
                        <div class="lightbox-content">
                            <img src="${src}" alt="${alt}">
                            <button class="lightbox-close">&times;</button>
                        </div>
                    </div>
                `);
                
                $('body').append($lightbox);
                
                setTimeout(() => $lightbox.addClass('active'), 10);
                
                $lightbox.on('click', '.lightbox-close, .lightbox-overlay', function() {
                    $lightbox.removeClass('active');
                    setTimeout(() => $lightbox.remove(), 300);
                });
            });
        },
        
        initStickySidebar: function() {
            const $sidebar = $('.blog-posts-sidebar');
            if ($sidebar.length === 0) return;
            
            const $container = $('.blog-posts-container');
            const sidebarTop = $sidebar.offset().top;
            
            $(window).on('scroll resize', function() {
                const windowTop = $(window).scrollTop();
                const containerBottom = $container.offset().top + $container.outerHeight();
                const sidebarHeight = $sidebar.outerHeight();
                
                if (windowTop > sidebarTop - 20) {
                    if (windowTop + sidebarHeight + 40 < containerBottom) {
                        $sidebar.css({
                            position: 'fixed',
                            top: '20px',
                            width: $sidebar.data('original-width') || $sidebar.width()
                        });
                        
                        if (!$sidebar.data('original-width')) {
                            $sidebar.data('original-width', $sidebar.width());
                        }
                    } else {
                        $sidebar.css({
                            position: 'absolute',
                            top: containerBottom - sidebarHeight - 40,
                            width: $sidebar.data('original-width') || 'auto'
                        });
                    }
                } else {
                    $sidebar.css({
                        position: 'static',
                        top: 'auto',
                        width: 'auto'
                    });
                }
            });
        },
        
        initBlogAnimations: function() {
            // Animate blog cards on scroll
            $('.blog-card').each(function(index) {
                const $card = $(this);
                
                if ($card.hasClass('animated')) return;
                
                const cardTop = $card.offset().top;
                const windowBottom = $(window).scrollTop() + $(window).height();
                
                if (windowBottom > cardTop + 100) {
                    setTimeout(() => {
                        $card.addClass('animated slide-up');
                    }, index * 100);
                }
            });
            
            // Continue animation on scroll
            $(window).on('scroll', this.debounce(function() {
                $('.blog-card:not(.animated)').each(function(index) {
                    const $card = $(this);
                    const cardTop = $card.offset().top;
                    const windowBottom = $(window).scrollTop() + $(window).height();
                    
                    if (windowBottom > cardTop + 100) {
                        setTimeout(() => {
                            $card.addClass('animated slide-up');
                        }, index * 50);
                    }
                });
            }, 100));
        },
        
        initInfiniteScroll: function() {
            let loading = false;
            let page = 2;
            
            $(window).on('scroll', this.debounce(function() {
                if (loading) return;
                
                const scrollTop = $(window).scrollTop();
                const windowHeight = $(window).height();
                const documentHeight = $(document).height();
                
                if (scrollTop + windowHeight > documentHeight - 1000) {
                    loading = true;
                    
                    $.ajax({
                        url: parfumeBlog.ajaxUrl,
                        type: 'POST',
                        data: {
                            action: 'parfume_blog_infinite_scroll',
                            page: page,
                            nonce: parfumeBlog.nonce
                        },
                        success: function(response) {
                            if (response.success && response.data.html) {
                                $('.blog-posts-grid').append(response.data.html);
                                page++;
                                
                                // Re-initialize animations
                                ParfumeBlog.initBlogAnimations();
                                
                                if (!response.data.has_more) {
                                    $(window).off('scroll');
                                }
                            }
                            loading = false;
                        },
                        error: function() {
                            loading = false;
                        }
                    });
                }
            }, 250));
        },
        
        initReadingProgress: function() {
            if (!$('.blog-post-content').length) return;
            
            const $progressContainer = $('<div class="reading-progress-container"><div class="reading-progress-bar"></div></div>');
            $('body').prepend($progressContainer);
            
            $(window).on('scroll', this.debounce(function() {
                const $content = $('.blog-post-content');
                const contentTop = $content.offset().top;
                const contentHeight = $content.outerHeight();
                const scrollTop = $(window).scrollTop();
                const windowHeight = $(window).height();
                
                const startReading = contentTop - windowHeight / 2;
                const endReading = contentTop + contentHeight - windowHeight / 2;
                
                let progress = 0;
                if (scrollTop >= startReading) {
                    progress = Math.min(100, ((scrollTop - startReading) / (endReading - startReading)) * 100);
                }
                
                $('.reading-progress-bar').css('width', progress + '%');
            }, 10));
        },
        
        initSocialSharing: function() {
            // Enhanced social sharing with native sharing API
            if (navigator.share) {
                $('.sharing-button').on('click', function(e) {
                    e.preventDefault();
                    
                    const title = document.title;
                    const url = window.location.href;
                    const text = $('meta[name="description"]').attr('content') || '';
                    
                    navigator.share({
                        title: title,
                        text: text,
                        url: url
                    }).catch(err => {
                        // Fall back to original behavior
                        window.open(this.href, '_blank', 'width=600,height=400');
                    });
                });
            }
        },
        
        setupLazyLoading: function() {
            // Lazy load blog images
            if ('IntersectionObserver' in window) {
                const imageObserver = new IntersectionObserver((entries, observer) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            const img = entry.target;
                            img.src = img.dataset.src;
                            img.classList.remove('lazy');
                            observer.unobserve(img);
                        }
                    });
                });
                
                document.querySelectorAll('img[data-src]').forEach(img => {
                    imageObserver.observe(img);
                });
            }
        },
        
        // Utility function for debouncing
        debounce: function(func, wait, immediate) {
            let timeout;
            return function() {
                const context = this;
                const args = arguments;
                const later = function() {
                    timeout = null;
                    if (!immediate) func.apply(context, args);
                };
                const callNow = immediate && !timeout;
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
                if (callNow) func.apply(context, args);
            };
        }
    };
    
    // Initialize when document is ready
    $(document).ready(() => ParfumeBlog.init());
    
    // Make ParfumeBlog globally accessible
    window.ParfumeBlog = ParfumeBlog;
    
})(jQuery);

// Additional CSS for JavaScript enhancements
const blogEnhancementStyles = `
<style>
/* Reading Progress Bar */
.reading-progress-container {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 4px;
    background: rgba(255,255,255,0.1);
    z-index: 9999;
    backdrop-filter: blur(10px);
}

.reading-progress-bar {
    height: 100%;
    background: linear-gradient(90deg, #667eea, #764ba2);
    width: 0%;
    transition: width 0.1s ease;
}

/* Blog Card Animations */
.blog-card {
    opacity: 0;
    transform: translateY(30px);
    transition: all 0.6s ease;
}

.blog-card.animated.slide-up {
    opacity: 1;
    transform: translateY(0);
}

/* Loading States */
.blog-posts-grid.loading {
    opacity: 0.6;
    pointer-events: none;
}

.blog-posts-grid.loading::after {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 40px;
    height: 40px;
    margin: -20px 0 0 -20px;
    border: 3px solid #f3f3f3;
    border-top: 3px solid #667eea;
    border-radius: 50%;
    animation: blogSpin 1s linear infinite;
}

@keyframes blogSpin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Search Enhancement */
.blog-search-form.searching .blog-search-submit {
    animation: blogSpin 1s linear infinite;
}

.blog-search-results {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: white;
    border: 1px solid #e0e0e0;
    border-top: none;
    border-radius: 0 0 10px 10px;
    max-height: 300px;
    overflow-y: auto;
    z-index: 1000;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

.search-loading,
.search-no-results,
.search-error {
    padding: 20px;
    text-align: center;
    color: #666;
}

/* Image Lightbox */
.blog-lightbox {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: 10000;
    opacity: 0;
    visibility: hidden;
    transition: all 0.3s ease;
}

.blog-lightbox.active {
    opacity: 1;
    visibility: visible;
}

.lightbox-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.9);
}

.lightbox-content {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    max-width: 90%;
    max-height: 90%;
}

.lightbox-content img {
    max-width: 100%;
    max-height: 100%;
    border-radius: 8px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.5);
}

.lightbox-close {
    position: absolute;
    top: -15px;
    right: -15px;
    width: 40px;
    height: 40px;
    background: white;
    border: none;
    border-radius: 50%;
    font-size: 20px;
    font-weight: bold;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 2px 10px rgba(0,0,0,0.3);
    transition: all 0.3s ease;
}

.lightbox-close:hover {
    background: #f44336;
    color: white;
    transform: scale(1.1);
}

/* Enhanced Hover Effects */
.blog-card-image {
    overflow: hidden;
}

.blog-card-image img {
    transition: transform 0.4s ease;
}

.blog-card:hover .blog-card-image img {
    transform: scale(1.05);
}

/* Sticky Sidebar Enhancement */
.blog-posts-sidebar {
    transition: all 0.3s ease;
}

/* Category Filter Active State */
.blog-category-filter.active {
    background: #667eea;
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
}

/* Load More Button Enhancement */
.blog-load-more {
    position: relative;
    overflow: hidden;
}

.blog-load-more::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
    transition: left 0.5s;
}

.blog-load-more:hover::before {
    left: 100%;
}

/* Responsive Enhancements */
@media (max-width: 768px) {
    .reading-progress-container {
        height: 3px;
    }
    
    .lightbox-content {
        max-width: 95%;
        max-height: 95%;
    }
    
    .lightbox-close {
        top: -10px;
        right: -10px;
        width: 35px;
        height: 35px;
        font-size: 18px;
    }
}

/* Lazy Loading */
img.lazy {
    opacity: 0;
    transition: opacity 0.3s;
}

img.lazy.loaded {
    opacity: 1;
}
</style>
`;

// Inject the styles
document.head.insertAdjacentHTML('beforeend', blogEnhancementStyles);