/**
 * AI-Powered WooCommerce Theme - Main JavaScript
 * 
 * @package AI_Woo_Theme
 */

(function($) {
    'use strict';
    
    // Theme object
    window.AIWooTheme = window.AIWooTheme || {};
    
    /**
     * Main Theme Class
     */
    AIWooTheme.Main = {
        
        // Cache DOM elements
        $window: $(window),
        $document: $(document),
        $body: $('body'),
        $header: $('#site-header'),
        
        /**
         * Initialize
         */
        init: function() {
            this.bindEvents();
            this.initComponents();
            this.performanceOptimizations();
            this.setupAccessibility();
        },
        
        /**
         * Bind Events
         */
        bindEvents: function() {
            var self = this;
            
            // Window events
            this.$window.on('scroll', this.throttle(this.handleScroll, 16));
            this.$window.on('resize', this.throttle(this.handleResize, 250));
            this.$window.on('load', this.handleLoad);
            
            // Mobile menu toggle
            this.$document.on('click', '#mobile-menu-toggle', this.toggleMobileMenu);
            
            // Search toggle
            this.$document.on('click', '#search-toggle', this.toggleSearch);
            
            // Back to top
            this.$document.on('click', '#back-to-top', this.scrollToTop);
            
            // Cart dropdown
            this.$document.on('click', '#cart-link', this.toggleCartDropdown);
            
            // AI Assistant
            this.$document.on('click', '#ai-assistant-toggle', this.toggleAIAssistant);
            this.$document.on('click', '#ai-panel-close', this.closeAIAssistant);
            
            // Cookie consent
            this.$document.on('click', '#accept-cookies', this.acceptCookies);
            this.$document.on('click', '#customize-cookies', this.customizeCookies);
            
            // Form enhancements
            this.$document.on('focus', 'input, textarea', this.handleInputFocus);
            this.$document.on('blur', 'input, textarea', this.handleInputBlur);
            
            // Product interactions
            this.$document.on('click', '.product-card', this.handleProductClick);
            this.$document.on('mouseenter', '.product-card', this.handleProductHover);
            
            // Smooth scrolling for anchor links
            this.$document.on('click', 'a[href^="#"]', this.smoothScroll);
        },
        
        /**
         * Initialize Components
         */
        initComponents: function() {
            this.initLazyLoading();
            this.initTooltips();
            this.initAnimations();
            this.initProductFilters();
            this.initWishlist();
            this.initQuickView();
            this.updateCartCount();
        },
        
        /**
         * Handle Scroll
         */
        handleScroll: function() {
            var scrollTop = AIWooTheme.Main.$window.scrollTop();
            
            // Header scroll effect
            if (scrollTop > 100) {
                AIWooTheme.Main.$header.addClass('scrolled');
                $('#back-to-top').addClass('visible');
            } else {
                AIWooTheme.Main.$header.removeClass('scrolled');
                $('#back-to-top').removeClass('visible');
            }
            
            // Parallax effects
            AIWooTheme.Main.handleParallax(scrollTop);
            
            // Progress bar for reading
            AIWooTheme.Main.updateReadingProgress(scrollTop);
        },
        
        /**
         * Handle Resize
         */
        handleResize: function() {
            AIWooTheme.Main.handleResponsiveLayout();
            AIWooTheme.Main.recalculateAnimations();
        },
        
        /**
         * Handle Load
         */
        handleLoad: function() {
            AIWooTheme.Main.$body.addClass('loaded');
            AIWooTheme.Main.initPerformanceMetrics();
        },
        
        /**
         * Toggle Mobile Menu
         */
        toggleMobileMenu: function(e) {
            e.preventDefault();
            
            var $toggle = $(this);
            var $mobileNav = $('#mobile-nav');
            
            $toggle.toggleClass('active');
            $mobileNav.toggleClass('active');
            AIWooTheme.Main.$body.toggleClass('mobile-menu-open');
            
            // Accessibility
            var expanded = $toggle.attr('aria-expanded') === 'true';
            $toggle.attr('aria-expanded', !expanded);
        },
        
        /**
         * Toggle Search
         */
        toggleSearch: function(e) {
            e.preventDefault();
            
            var $searchWrapper = $('#search-form-wrapper');
            $searchWrapper.toggleClass('active');
            
            if ($searchWrapper.hasClass('active')) {
                $searchWrapper.find('input[type="search"]').focus();
            }
        },
        
        /**
         * Scroll to Top
         */
        scrollToTop: function(e) {
            e.preventDefault();
            
            $('html, body').animate({
                scrollTop: 0
            }, 800, 'easeInOutCubic');
        },
        
        /**
         * Toggle Cart Dropdown
         */
        toggleCartDropdown: function(e) {
            if ($(window).width() > 768) {
                e.preventDefault();
                $('#mini-cart-dropdown').toggleClass('active');
            }
        },
        
        /**
         * Toggle AI Assistant
         */
        toggleAIAssistant: function(e) {
            e.preventDefault();
            $('#ai-assistant-panel').addClass('active');
            AIWooTheme.Main.$body.addClass('ai-panel-open');
        },
        
        /**
         * Close AI Assistant
         */
        closeAIAssistant: function(e) {
            e.preventDefault();
            $('#ai-assistant-panel').removeClass('active');
            AIWooTheme.Main.$body.removeClass('ai-panel-open');
        },
        
        /**
         * Accept Cookies
         */
        acceptCookies: function(e) {
            e.preventDefault();
            
            localStorage.setItem('ai_woo_cookies_accepted', 'true');
            $('#cookie-consent').fadeOut();
            
            // Initialize tracking scripts
            AIWooTheme.Main.initTrackingScripts();
        },
        
        /**
         * Customize Cookies
         */
        customizeCookies: function(e) {
            e.preventDefault();
            // Open cookie customization modal
            // Implementation would depend on specific requirements
            console.log('Cookie customization requested');
        },
        
        /**
         * Handle Input Focus
         */
        handleInputFocus: function() {
            $(this).parent().addClass('focused');
        },
        
        /**
         * Handle Input Blur
         */
        handleInputBlur: function() {
            if (!$(this).val()) {
                $(this).parent().removeClass('focused');
            }
        },
        
        /**
         * Handle Product Click
         */
        handleProductClick: function(e) {
            // Track product interaction
            var productId = $(this).data('product-id');
            if (productId) {
                AIWooTheme.Main.trackUserBehavior('product_click', {
                    product_id: productId,
                    timestamp: Date.now()
                });
            }
        },
        
        /**
         * Handle Product Hover
         */
        handleProductHover: function() {
            var productId = $(this).data('product-id');
            if (productId) {
                // Preload product data
                AIWooTheme.Main.preloadProductData(productId);
            }
        },
        
        /**
         * Smooth Scroll
         */
        smoothScroll: function(e) {
            var target = $(this.getAttribute('href'));
            
            if (target.length) {
                e.preventDefault();
                
                $('html, body').animate({
                    scrollTop: target.offset().top - 80
                }, 800, 'easeInOutCubic');
            }
        },
        
        /**
         * Initialize Lazy Loading
         */
        initLazyLoading: function() {
            if ('IntersectionObserver' in window) {
                var lazyImages = document.querySelectorAll('.lazy-load');
                var imageObserver = new IntersectionObserver(function(entries, observer) {
                    entries.forEach(function(entry) {
                        if (entry.isIntersecting) {
                            var img = entry.target;
                            if (img.dataset.src) {
                                img.src = img.dataset.src;
                                img.classList.remove('lazy-load');
                                img.classList.add('loaded');
                                imageObserver.unobserve(img);
                            }
                        }
                    });
                });
                
                lazyImages.forEach(function(img) {
                    imageObserver.observe(img);
                });
            }
        },
        
        /**
         * Initialize Tooltips
         */
        initTooltips: function() {
            $('[data-tooltip]').each(function() {
                var $element = $(this);
                var tooltipText = $element.data('tooltip');
                
                $element.on('mouseenter', function() {
                    var $tooltip = $('<div class="tooltip">' + tooltipText + '</div>');
                    $('body').append($tooltip);
                    
                    var offset = $element.offset();
                    $tooltip.css({
                        top: offset.top - $tooltip.outerHeight() - 10,
                        left: offset.left + ($element.outerWidth() / 2) - ($tooltip.outerWidth() / 2)
                    }).addClass('visible');
                });
                
                $element.on('mouseleave', function() {
                    $('.tooltip').remove();
                });
            });
        },
        
        /**
         * Initialize Animations
         */
        initAnimations: function() {
            // Fade in elements on scroll
            var $animatedElements = $('.animate-on-scroll');
            
            if ($animatedElements.length && 'IntersectionObserver' in window) {
                var animationObserver = new IntersectionObserver(function(entries) {
                    entries.forEach(function(entry) {
                        if (entry.isIntersecting) {
                            entry.target.classList.add('animated');
                            animationObserver.unobserve(entry.target);
                        }
                    });
                }, {
                    threshold: 0.1
                });
                
                $animatedElements.each(function() {
                    animationObserver.observe(this);
                });
            }
        },
        
        /**
         * Initialize Product Filters
         */
        initProductFilters: function() {
            $('.product-filter').on('change', function() {
                var filterType = $(this).data('filter');
                var filterValue = $(this).val();
                
                AIWooTheme.Main.filterProducts(filterType, filterValue);
            });
        },
        
        /**
         * Filter Products
         */
        filterProducts: function(type, value) {
            var $products = $('.product-card');
            
            if (value === 'all') {
                $products.show();
            } else {
                $products.each(function() {
                    var $product = $(this);
                    var productValue = $product.data(type);
                    
                    if (productValue === value) {
                        $product.show();
                    } else {
                        $product.hide();
                    }
                });
            }
            
            // Track filter usage
            this.trackUserBehavior('product_filter', {
                filter_type: type,
                filter_value: value
            });
        },
        
        /**
         * Initialize Wishlist
         */
        initWishlist: function() {
            $(document).on('click', '.wishlist-toggle', function(e) {
                e.preventDefault();
                
                var $button = $(this);
                var productId = $button.data('product-id');
                
                $button.toggleClass('active');
                
                // Save to localStorage or send to server
                AIWooTheme.Main.toggleWishlistItem(productId);
            });
        },
        
        /**
         * Toggle Wishlist Item
         */
        toggleWishlistItem: function(productId) {
            var wishlist = JSON.parse(localStorage.getItem('ai_woo_wishlist') || '[]');
            var index = wishlist.indexOf(productId);
            
            if (index > -1) {
                wishlist.splice(index, 1);
            } else {
                wishlist.push(productId);
            }
            
            localStorage.setItem('ai_woo_wishlist', JSON.stringify(wishlist));
            
            // Track wishlist action
            this.trackUserBehavior('wishlist_toggle', {
                product_id: productId,
                action: index > -1 ? 'remove' : 'add'
            });
        },
        
        /**
         * Initialize Quick View
         */
        initQuickView: function() {
            $(document).on('click', '.quick-view-btn', function(e) {
                e.preventDefault();
                
                var productId = $(this).data('product-id');
                AIWooTheme.Main.openQuickView(productId);
            });
        },
        
        /**
         * Open Quick View
         */
        openQuickView: function(productId) {
            // Create modal and load product data
            var $modal = $('<div class="quick-view-modal"><div class="modal-content"><div class="loading">Loading...</div></div></div>');
            $('body').append($modal);
            
            // Load product data via AJAX
            $.post(ai_woo_ajax.ajax_url, {
                action: 'get_product_quick_view',
                product_id: productId,
                nonce: ai_woo_ajax.nonce
            }, function(response) {
                if (response.success) {
                    $modal.find('.modal-content').html(response.data);
                    $modal.addClass('active');
                }
            });
            
            // Track quick view
            this.trackUserBehavior('quick_view', {
                product_id: productId
            });
        },
        
        /**
         * Update Cart Count
         */
        updateCartCount: function() {
            $(document.body).on('added_to_cart removed_from_cart', function(event, fragments) {
                if (fragments && fragments['div.widget_shopping_cart_content']) {
                    $('#mini-cart-dropdown .mini-cart-content').html(fragments['div.widget_shopping_cart_content']);
                }
            });
        },
        
        /**
         * Track User Behavior
         */
        trackUserBehavior: function(action, data) {
            if (typeof ai_woo_ajax !== 'undefined') {
                $.post(ai_woo_ajax.ajax_url, {
                    action: 'track_user_behavior',
                    action_type: action,
                    data: data,
                    nonce: ai_woo_ajax.nonce
                });
            }
        },
        
        /**
         * Preload Product Data
         */
        preloadProductData: function(productId) {
            // Preload product data for faster interactions
            if (!this.preloadedProducts) {
                this.preloadedProducts = {};
            }
            
            if (!this.preloadedProducts[productId]) {
                this.preloadedProducts[productId] = true;
                
                $.post(ai_woo_ajax.ajax_url, {
                    action: 'preload_product_data',
                    product_id: productId,
                    nonce: ai_woo_ajax.nonce
                });
            }
        },
        
        /**
         * Handle Parallax
         */
        handleParallax: function(scrollTop) {
            $('.parallax-element').each(function() {
                var $element = $(this);
                var speed = $element.data('parallax-speed') || 0.5;
                var yPos = -(scrollTop * speed);
                
                $element.css('transform', 'translateY(' + yPos + 'px)');
            });
        },
        
        /**
         * Update Reading Progress
         */
        updateReadingProgress: function(scrollTop) {
            var $progressBar = $('.reading-progress');
            
            if ($progressBar.length && $('article').length) {
                var docHeight = $(document).height();
                var winHeight = $(window).height();
                var scrollPercent = (scrollTop / (docHeight - winHeight)) * 100;
                
                $progressBar.css('width', scrollPercent + '%');
            }
        },
        
        /**
         * Handle Responsive Layout
         */
        handleResponsiveLayout: function() {
            var windowWidth = this.$window.width();
            
            // Update body class for responsive breakpoints
            this.$body.removeClass('mobile tablet desktop');
            
            if (windowWidth < 768) {
                this.$body.addClass('mobile');
            } else if (windowWidth < 1024) {
                this.$body.addClass('tablet');
            } else {
                this.$body.addClass('desktop');
            }
        },
        
        /**
         * Recalculate Animations
         */
        recalculateAnimations: function() {
            // Recalculate animation triggers on resize
            $('.animate-on-scroll').removeClass('animated');
            this.initAnimations();
        },
        
        /**
         * Performance Optimizations
         */
        performanceOptimizations: function() {
            // Preload critical resources
            this.preloadCriticalResources();
            
            // Initialize service worker
            this.initServiceWorker();
            
            // Optimize images
            this.optimizeImages();
        },
        
        /**
         * Preload Critical Resources
         */
        preloadCriticalResources: function() {
            // Preload important pages
            var criticalPages = ['/shop/', '/cart/', '/checkout/'];
            
            criticalPages.forEach(function(page) {
                var link = document.createElement('link');
                link.rel = 'prefetch';
                link.href = page;
                document.head.appendChild(link);
            });
        },
        
        /**
         * Initialize Service Worker
         */
        initServiceWorker: function() {
            if ('serviceWorker' in navigator) {
                navigator.serviceWorker.register('/sw.js')
                    .then(function(registration) {
                        console.log('SW registered');
                    })
                    .catch(function(error) {
                        console.log('SW registration failed');
                    });
            }
        },
        
        /**
         * Optimize Images
         */
        optimizeImages: function() {
            // Convert images to WebP if supported
            if (this.supportsWebP()) {
                $('img[data-webp]').each(function() {
                    $(this).attr('src', $(this).data('webp'));
                });
            }
        },
        
        /**
         * Check WebP Support
         */
        supportsWebP: function() {
            var canvas = document.createElement('canvas');
            canvas.width = 1;
            canvas.height = 1;
            return canvas.toDataURL('image/webp').indexOf('webp') > -1;
        },
        
        /**
         * Setup Accessibility
         */
        setupAccessibility: function() {
            // Skip links
            $('.skip-link').on('click', function(e) {
                e.preventDefault();
                var target = $($(this).attr('href'));
                if (target.length) {
                    target.focus();
                }
            });
            
            // Keyboard navigation
            $(document).on('keydown', this.handleKeyboardNavigation);
            
            // ARIA live regions
            this.setupAriaLiveRegions();
        },
        
        /**
         * Handle Keyboard Navigation
         */
        handleKeyboardNavigation: function(e) {
            // ESC key closes modals
            if (e.keyCode === 27) {
                $('.modal, .dropdown').removeClass('active');
                $('#ai-assistant-panel').removeClass('active');
                AIWooTheme.Main.$body.removeClass('ai-panel-open mobile-menu-open');
            }
            
            // Enter key activates buttons
            if (e.keyCode === 13 && $(e.target).hasClass('btn-like')) {
                $(e.target).click();
            }
        },
        
        /**
         * Setup ARIA Live Regions
         */
        setupAriaLiveRegions: function() {
            if (!$('#aria-live-region').length) {
                $('body').append('<div id="aria-live-region" aria-live="polite" class="sr-only"></div>');
            }
        },
        
        /**
         * Announce to Screen Readers
         */
        announceToScreenReader: function(message) {
            $('#aria-live-region').text(message);
        },
        
        /**
         * Initialize Performance Metrics
         */
        initPerformanceMetrics: function() {
            if ('performance' in window) {
                var loadTime = performance.timing.loadEventEnd - performance.timing.navigationStart;
                
                // Track performance
                this.trackUserBehavior('page_load_time', {
                    load_time: loadTime,
                    page: window.location.pathname
                });
            }
        },
        
        /**
         * Initialize Tracking Scripts
         */
        initTrackingScripts: function() {
            // Initialize analytics after cookie consent
            if (typeof gtag !== 'undefined') {
                gtag('consent', 'update', {
                    'analytics_storage': 'granted'
                });
            }
            
            if (typeof fbq !== 'undefined') {
                fbq('consent', 'grant');
            }
        },
        
        /**
         * Throttle Function
         */
        throttle: function(func, limit) {
            var inThrottle;
            return function() {
                var args = arguments;
                var context = this;
                if (!inThrottle) {
                    func.apply(context, args);
                    inThrottle = true;
                    setTimeout(function() {
                        inThrottle = false;
                    }, limit);
                }
            };
        },
        
        /**
         * Debounce Function
         */
        debounce: function(func, wait, immediate) {
            var timeout;
            return function() {
                var context = this, args = arguments;
                var later = function() {
                    timeout = null;
                    if (!immediate) func.apply(context, args);
                };
                var callNow = immediate && !timeout;
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
                if (callNow) func.apply(context, args);
            };
        }
    };
    
    // Initialize when DOM is ready
    $(document).ready(function() {
        AIWooTheme.Main.init();
    });
    
})(jQuery);