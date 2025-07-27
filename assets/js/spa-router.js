/**
 * SPA Router for AI-Powered WooCommerce Theme
 * Provides fast navigation without full page reloads
 * 
 * @package AI_Woo_Theme
 */

(function($) {
    'use strict';
    
    // Add to global theme object
    window.AIWooTheme = window.AIWooTheme || {};
    
    /**
     * SPA Router Class
     */
    AIWooTheme.SPARouter = {
        
        // Configuration
        config: {
            enabled: true,
            selectors: {
                links: 'a.spa-link, .main-nav a, .product-card a',
                content: '#main-content',
                loading: '#spa-loading-overlay'
            },
            excludePatterns: [
                '/wp-admin/',
                '/wp-login.php',
                '.pdf',
                '.zip',
                '.doc',
                '.docx',
                'mailto:',
                'tel:',
                '#'
            ],
            cache: {
                enabled: true,
                maxSize: 50,
                ttl: 300000 // 5 minutes
            }
        },
        
        // State
        state: {
            currentUrl: '',
            isLoading: false,
            cache: new Map(),
            history: []
        },
        
        /**
         * Initialize SPA Router
         */
        init: function() {
            if (!this.config.enabled || !this.isSupported()) {
                return false;
            }
            
            this.bindEvents();
            this.setupCache();
            this.preloadCriticalPages();
            this.state.currentUrl = window.location.href;
            
            // Initialize browser history
            this.initHistory();
            
            console.log('SPA Router initialized');
            return true;
        },
        
        /**
         * Check if SPA is supported
         */
        isSupported: function() {
            return !!(window.history && 
                     window.history.pushState && 
                     window.history.replaceState &&
                     document.querySelector &&
                     window.addEventListener);
        },
        
        /**
         * Bind Events
         */
        bindEvents: function() {
            var self = this;
            
            // Handle link clicks
            $(document).on('click', this.config.selectors.links, function(e) {
                return self.handleLinkClick(e, this);
            });
            
            // Handle browser back/forward
            window.addEventListener('popstate', function(e) {
                self.handlePopState(e);
            });
            
            // Handle form submissions
            $(document).on('submit', 'form.spa-form', function(e) {
                return self.handleFormSubmit(e, this);
            });
            
            // Preload on hover
            $(document).on('mouseenter', this.config.selectors.links, function() {
                self.preloadPage(this.href);
            });
        },
        
        /**
         * Handle Link Click
         */
        handleLinkClick: function(e, element) {
            var href = element.href;
            var $element = $(element);
            
            // Check if link should be handled by SPA
            if (!this.shouldHandleLink(href, element)) {
                return true; // Allow default behavior
            }
            
            e.preventDefault();
            
            // Add loading state to clicked element
            $element.addClass('spa-loading');
            
            // Navigate to page
            this.navigateTo(href).then(function() {
                $element.removeClass('spa-loading');
            }).catch(function() {
                $element.removeClass('spa-loading');
                // Fallback to normal navigation
                window.location.href = href;
            });
            
            return false;
        },
        
        /**
         * Handle Pop State (browser back/forward)
         */
        handlePopState: function(e) {
            var url = window.location.href;
            
            if (url !== this.state.currentUrl) {
                this.navigateTo(url, false); // Don't push to history
            }
        },
        
        /**
         * Handle Form Submit
         */
        handleFormSubmit: function(e, form) {
            var $form = $(form);
            var action = $form.attr('action') || window.location.href;
            var method = ($form.attr('method') || 'GET').toUpperCase();
            
            if (!this.shouldHandleLink(action)) {
                return true;
            }
            
            e.preventDefault();
            
            var formData = $form.serialize();
            
            if (method === 'GET') {
                var separator = action.indexOf('?') > -1 ? '&' : '?';
                action = action + separator + formData;
                this.navigateTo(action);
            } else {
                // Handle POST requests
                this.submitForm(action, formData, method);
            }
            
            return false;
        },
        
        /**
         * Check if link should be handled by SPA
         */
        shouldHandleLink: function(href, element) {
            // Check if SPA is enabled
            if (!this.config.enabled) {
                return false;
            }
            
            // Check if it's the same domain
            if (!this.isSameDomain(href)) {
                return false;
            }
            
            // Check exclude patterns
            for (var i = 0; i < this.config.excludePatterns.length; i++) {
                if (href.indexOf(this.config.excludePatterns[i]) > -1) {
                    return false;
                }
            }
            
            // Check if element has spa-disabled class
            if (element && $(element).hasClass('spa-disabled')) {
                return false;
            }
            
            // Check if it's a download link
            if (element && $(element).attr('download')) {
                return false;
            }
            
            // Check if it opens in new window
            if (element && $(element).attr('target') === '_blank') {
                return false;
            }
            
            return true;
        },
        
        /**
         * Check if URL is same domain
         */
        isSameDomain: function(url) {
            var link = document.createElement('a');
            link.href = url;
            
            return link.hostname === window.location.hostname;
        },
        
        /**
         * Navigate to URL
         */
        navigateTo: function(url, pushState) {
            var self = this;
            pushState = pushState !== false;
            
            // Normalize URL
            url = this.normalizeUrl(url);
            
            // Check if already on this page
            if (url === this.state.currentUrl && pushState) {
                return Promise.resolve();
            }
            
            // Show loading
            this.showLoading();
            
            // Track navigation
            this.trackNavigation(url);
            
            return this.loadPage(url).then(function(data) {
                // Update content
                self.updateContent(data);
                
                // Update URL and history
                if (pushState) {
                    self.updateHistory(url, data.title);
                }
                
                // Update current URL
                self.state.currentUrl = url;
                
                // Hide loading
                self.hideLoading();
                
                // Trigger events
                self.triggerNavigationEvents(url, data);
                
                return data;
                
            }).catch(function(error) {
                self.hideLoading();
                console.error('SPA Navigation Error:', error);
                throw error;
            });
        },
        
        /**
         * Load Page Content
         */
        loadPage: function(url) {
            var self = this;
            
            // Check cache first
            if (this.config.cache.enabled) {
                var cached = this.getCachedPage(url);
                if (cached) {
                    return Promise.resolve(cached);
                }
            }
            
            // Load from server
            return new Promise(function(resolve, reject) {
                $.ajax({
                    url: url,
                    type: 'GET',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-SPA-Request': '1'
                    },
                    timeout: 10000,
                    success: function(response) {
                        var data = self.parseResponse(response, url);
                        
                        // Cache the response
                        if (self.config.cache.enabled) {
                            self.cachePageData(url, data);
                        }
                        
                        resolve(data);
                    },
                    error: function(xhr, status, error) {
                        reject(new Error('Failed to load page: ' + error));
                    }
                });
            });
        },
        
        /**
         * Parse Response
         */
        parseResponse: function(html, url) {
            var $response = $('<div>').html(html);
            var $content = $response.find(this.config.selectors.content);
            
            // Extract title
            var title = $response.find('title').text() || document.title;
            
            // Extract meta description
            var description = $response.find('meta[name="description"]').attr('content') || '';
            
            // Extract scripts (for analytics, etc.)
            var scripts = [];
            $response.find('script').each(function() {
                var src = $(this).attr('src');
                var content = $(this).html();
                
                if (src || content) {
                    scripts.push({
                        src: src,
                        content: content,
                        async: $(this).attr('async') !== undefined,
                        defer: $(this).attr('defer') !== undefined
                    });
                }
            });
            
            return {
                url: url,
                title: title,
                description: description,
                content: $content.length ? $content.html() : html,
                scripts: scripts,
                timestamp: Date.now()
            };
        },
        
        /**
         * Update Content
         */
        updateContent: function(data) {
            var $content = $(this.config.selectors.content);
            
            // Update title
            document.title = data.title;
            
            // Update meta description
            var $metaDesc = $('meta[name="description"]');
            if ($metaDesc.length) {
                $metaDesc.attr('content', data.description);
            }
            
            // Update content with animation
            $content.addClass('spa-transitioning');
            
            setTimeout(function() {
                $content.html(data.content);
                
                // Reinitialize components for new content
                AIWooTheme.SPARouter.reinitializeComponents();
                
                // Execute scripts
                AIWooTheme.SPARouter.executeScripts(data.scripts);
                
                $content.removeClass('spa-transitioning');
                
                // Scroll to top
                window.scrollTo(0, 0);
                
            }, 150);
        },
        
        /**
         * Reinitialize Components
         */
        reinitializeComponents: function() {
            // Reinitialize theme components
            if (AIWooTheme.Main) {
                AIWooTheme.Main.initLazyLoading();
                AIWooTheme.Main.initAnimations();
                AIWooTheme.Main.initTooltips();
            }
            
            // Reinitialize WooCommerce components
            if (typeof wc_add_to_cart_params !== 'undefined') {
                $(document.body).trigger('wc_fragment_refresh');
            }
            
            // Trigger custom event for other plugins
            $(document).trigger('spa:content-updated');
        },
        
        /**
         * Execute Scripts
         */
        executeScripts: function(scripts) {
            scripts.forEach(function(script) {
                if (script.src) {
                    // External script
                    var scriptElement = document.createElement('script');
                    scriptElement.src = script.src;
                    scriptElement.async = script.async;
                    scriptElement.defer = script.defer;
                    document.head.appendChild(scriptElement);
                } else if (script.content) {
                    // Inline script
                    try {
                        eval(script.content);
                    } catch (e) {
                        console.error('Error executing inline script:', e);
                    }
                }
            });
        },
        
        /**
         * Update History
         */
        updateHistory: function(url, title) {
            try {
                window.history.pushState({
                    url: url,
                    title: title,
                    timestamp: Date.now()
                }, title, url);
                
                // Add to internal history
                this.state.history.push({
                    url: url,
                    title: title,
                    timestamp: Date.now()
                });
                
                // Limit history size
                if (this.state.history.length > 100) {
                    this.state.history.shift();
                }
                
            } catch (e) {
                console.error('Error updating history:', e);
            }
        },
        
        /**
         * Initialize History
         */
        initHistory: function() {
            // Replace current state
            window.history.replaceState({
                url: window.location.href,
                title: document.title,
                timestamp: Date.now()
            }, document.title, window.location.href);
        },
        
        /**
         * Show Loading
         */
        showLoading: function() {
            if (this.state.isLoading) {
                return;
            }
            
            this.state.isLoading = true;
            $(this.config.selectors.loading).show();
            $('body').addClass('spa-loading');
        },
        
        /**
         * Hide Loading
         */
        hideLoading: function() {
            this.state.isLoading = false;
            $(this.config.selectors.loading).hide();
            $('body').removeClass('spa-loading');
        },
        
        /**
         * Cache Page Data
         */
        cachePageData: function(url, data) {
            // Remove oldest entries if cache is full
            if (this.state.cache.size >= this.config.cache.maxSize) {
                var oldestKey = this.state.cache.keys().next().value;
                this.state.cache.delete(oldestKey);
            }
            
            // Add expiry time
            data.expires = Date.now() + this.config.cache.ttl;
            
            this.state.cache.set(url, data);
        },
        
        /**
         * Get Cached Page
         */
        getCachedPage: function(url) {
            var cached = this.state.cache.get(url);
            
            if (cached && cached.expires > Date.now()) {
                return cached;
            }
            
            // Remove expired cache
            if (cached) {
                this.state.cache.delete(url);
            }
            
            return null;
        },
        
        /**
         * Setup Cache
         */
        setupCache: function() {
            // Clear cache on page unload
            window.addEventListener('beforeunload', function() {
                AIWooTheme.SPARouter.state.cache.clear();
            });
        },
        
        /**
         * Preload Critical Pages
         */
        preloadCriticalPages: function() {
            var criticalPages = [
                ai_woo_ajax.site_url + 'shop/',
                ai_woo_ajax.site_url + 'cart/',
                ai_woo_ajax.site_url + 'my-account/'
            ];
            
            var self = this;
            setTimeout(function() {
                criticalPages.forEach(function(url) {
                    self.preloadPage(url);
                });
            }, 2000);
        },
        
        /**
         * Preload Page
         */
        preloadPage: function(url) {
            if (!this.shouldHandleLink(url) || this.getCachedPage(url)) {
                return;
            }
            
            // Preload in background
            this.loadPage(url).catch(function() {
                // Ignore preload errors
            });
        },
        
        /**
         * Submit Form
         */
        submitForm: function(action, data, method) {
            var self = this;
            
            this.showLoading();
            
            $.ajax({
                url: action,
                type: method,
                data: data,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-SPA-Request': '1'
                },
                success: function(response) {
                    var pageData = self.parseResponse(response, action);
                    self.updateContent(pageData);
                    self.updateHistory(action, pageData.title);
                    self.state.currentUrl = action;
                    self.hideLoading();
                },
                error: function() {
                    self.hideLoading();
                    // Fallback to normal form submission
                    window.location.href = action;
                }
            });
        },
        
        /**
         * Track Navigation
         */
        trackNavigation: function(url) {
            // Track SPA navigation
            if (AIWooTheme.Main && AIWooTheme.Main.trackUserBehavior) {
                AIWooTheme.Main.trackUserBehavior('spa_navigation', {
                    from: this.state.currentUrl,
                    to: url,
                    timestamp: Date.now()
                });
            }
            
            // Google Analytics SPA tracking
            if (typeof gtag !== 'undefined') {
                gtag('config', 'GA_MEASUREMENT_ID', {
                    page_path: new URL(url).pathname
                });
            }
        },
        
        /**
         * Trigger Navigation Events
         */
        triggerNavigationEvents: function(url, data) {
            // Custom events
            $(document).trigger('spa:navigation-complete', {
                url: url,
                data: data
            });
            
            // Google Analytics page view
            if (typeof gtag !== 'undefined') {
                gtag('event', 'page_view', {
                    page_title: data.title,
                    page_location: url
                });
            }
            
            // Facebook Pixel page view
            if (typeof fbq !== 'undefined') {
                fbq('track', 'PageView');
            }
        },
        
        /**
         * Normalize URL
         */
        normalizeUrl: function(url) {
            // Remove hash
            var hashIndex = url.indexOf('#');
            if (hashIndex > -1) {
                url = url.substring(0, hashIndex);
            }
            
            // Ensure trailing slash for consistency
            if (url.endsWith('/') === false && url.indexOf('?') === -1) {
                url += '/';
            }
            
            return url;
        },
        
        /**
         * Clear Cache
         */
        clearCache: function() {
            this.state.cache.clear();
        },
        
        /**
         * Get Cache Stats
         */
        getCacheStats: function() {
            return {
                size: this.state.cache.size,
                maxSize: this.config.cache.maxSize,
                urls: Array.from(this.state.cache.keys())
            };
        }
    };
    
    // Auto-initialize when DOM is ready
    $(document).ready(function() {
        AIWooTheme.SPARouter.init();
    });
    
})(jQuery);