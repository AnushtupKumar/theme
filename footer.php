<footer class="site-footer" id="site-footer">
    <div class="container">
        <!-- Footer Widgets -->
        <div class="footer-widgets">
            <div class="footer-widget-area">
                <?php if (is_active_sidebar('footer-1')): ?>
                    <div class="footer-widget">
                        <?php dynamic_sidebar('footer-1'); ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="footer-widget-area">
                <?php if (is_active_sidebar('footer-2')): ?>
                    <div class="footer-widget">
                        <?php dynamic_sidebar('footer-2'); ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="footer-widget-area">
                <?php if (is_active_sidebar('footer-3')): ?>
                    <div class="footer-widget">
                        <?php dynamic_sidebar('footer-3'); ?>
                    </div>
                <?php else: ?>
                    <!-- Default footer content -->
                    <div class="footer-widget">
                        <h3><?php esc_html_e('Contact Info', 'ai-woo-theme'); ?></h3>
                        <div class="contact-info">
                            <p>
                                <i class="fas fa-envelope"></i>
                                <a href="mailto:info@example.com">info@example.com</a>
                            </p>
                            <p>
                                <i class="fas fa-phone"></i>
                                <a href="tel:+1234567890">+1 (234) 567-890</a>
                            </p>
                            <p>
                                <i class="fas fa-map-marker-alt"></i>
                                123 Business Street, City, State 12345
                            </p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Footer Bottom -->
        <div class="footer-bottom">
            <div class="footer-info">
                <p class="copyright">
                    &copy; <?php echo date('Y'); ?> 
                    <a href="<?php echo esc_url(home_url('/')); ?>"><?php bloginfo('name'); ?></a>. 
                    <?php esc_html_e('All rights reserved.', 'ai-woo-theme'); ?>
                </p>
                
                <?php if (get_theme_mod('ai_woo_show_powered_by', true)): ?>
                    <p class="powered-by">
                        <?php esc_html_e('Powered by AI Technology', 'ai-woo-theme'); ?>
                        <i class="fas fa-robot"></i>
                    </p>
                <?php endif; ?>
            </div>

            <!-- Footer Navigation -->
            <nav class="footer-nav">
                <?php
                wp_nav_menu(array(
                    'theme_location' => 'footer',
                    'menu_id'        => 'footer-menu',
                    'container'      => false,
                    'fallback_cb'    => false,
                    'depth'          => 1,
                ));
                ?>
            </nav>

            <!-- Social Media Links -->
            <?php if (get_theme_mod('ai_woo_show_social', true)): ?>
                <div class="social-links">
                    <?php if (get_theme_mod('ai_woo_facebook_url')): ?>
                        <a href="<?php echo esc_url(get_theme_mod('ai_woo_facebook_url')); ?>" 
                           target="_blank" rel="noopener noreferrer" 
                           aria-label="<?php esc_attr_e('Facebook', 'ai-woo-theme'); ?>">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                    <?php endif; ?>
                    
                    <?php if (get_theme_mod('ai_woo_twitter_url')): ?>
                        <a href="<?php echo esc_url(get_theme_mod('ai_woo_twitter_url')); ?>" 
                           target="_blank" rel="noopener noreferrer" 
                           aria-label="<?php esc_attr_e('Twitter', 'ai-woo-theme'); ?>">
                            <i class="fab fa-twitter"></i>
                        </a>
                    <?php endif; ?>
                    
                    <?php if (get_theme_mod('ai_woo_instagram_url')): ?>
                        <a href="<?php echo esc_url(get_theme_mod('ai_woo_instagram_url')); ?>" 
                           target="_blank" rel="noopener noreferrer" 
                           aria-label="<?php esc_attr_e('Instagram', 'ai-woo-theme'); ?>">
                            <i class="fab fa-instagram"></i>
                        </a>
                    <?php endif; ?>
                    
                    <?php if (get_theme_mod('ai_woo_linkedin_url')): ?>
                        <a href="<?php echo esc_url(get_theme_mod('ai_woo_linkedin_url')); ?>" 
                           target="_blank" rel="noopener noreferrer" 
                           aria-label="<?php esc_attr_e('LinkedIn', 'ai-woo-theme'); ?>">
                            <i class="fab fa-linkedin-in"></i>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</footer>

<!-- Back to Top Button -->
<button class="back-to-top" id="back-to-top" aria-label="<?php esc_attr_e('Back to top', 'ai-woo-theme'); ?>">
    <i class="fas fa-chevron-up"></i>
</button>

<!-- Cart Abandonment Modal -->
<?php if (get_theme_mod('ai_woo_enable_cart_recovery', true) && class_exists('WooCommerce')): ?>
    <div class="cart-abandonment-modal" id="cart-abandonment-modal">
        <div class="modal-overlay"></div>
        <div class="modal-content">
            <div class="modal-header">
                <h3><?php esc_html_e('Don\'t Leave Your Cart Behind!', 'ai-woo-theme'); ?></h3>
                <button class="modal-close" id="cart-modal-close" aria-label="<?php esc_attr_e('Close modal', 'ai-woo-theme'); ?>">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <p><?php esc_html_e('We noticed you have items in your cart. Enter your email to save your cart and get a special discount!', 'ai-woo-theme'); ?></p>
                <form id="cart-abandonment-form">
                    <div class="form-group">
                        <input type="email" id="abandonment-email" placeholder="<?php esc_attr_e('Enter your email', 'ai-woo-theme'); ?>" required>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <?php esc_html_e('Save Cart & Get 10% Off', 'ai-woo-theme'); ?>
                        </button>
                        <button type="button" class="btn btn-secondary" id="cart-modal-dismiss">
                            <?php esc_html_e('No Thanks', 'ai-woo-theme'); ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Cookie Consent Banner -->
<?php if (get_theme_mod('ai_woo_show_cookie_consent', true)): ?>
    <div class="cookie-consent" id="cookie-consent">
        <div class="cookie-content">
            <div class="cookie-text">
                <p>
                    <?php echo esc_html(get_theme_mod('ai_woo_cookie_text', 
                        __('We use cookies to enhance your browsing experience and provide personalized content. By continuing to use our site, you agree to our use of cookies.', 'ai-woo-theme')
                    )); ?>
                </p>
            </div>
            <div class="cookie-actions">
                <button class="btn btn-primary" id="accept-cookies">
                    <?php esc_html_e('Accept All', 'ai-woo-theme'); ?>
                </button>
                <button class="btn btn-secondary" id="customize-cookies">
                    <?php esc_html_e('Customize', 'ai-woo-theme'); ?>
                </button>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Performance and Analytics -->
<?php if (get_theme_mod('ai_woo_google_analytics_id')): ?>
    <!-- Google Analytics -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo esc_attr(get_theme_mod('ai_woo_google_analytics_id')); ?>"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', '<?php echo esc_js(get_theme_mod('ai_woo_google_analytics_id')); ?>');
    </script>
<?php endif; ?>

<?php if (get_theme_mod('ai_woo_facebook_pixel_id')): ?>
    <!-- Facebook Pixel -->
    <script>
        !function(f,b,e,v,n,t,s)
        {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
        n.callMethod.apply(n,arguments):n.queue.push(arguments)};
        if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
        n.queue=[];t=b.createElement(e);t.async=!0;
        t.src=v;s=b.getElementsByTagName(e)[0];
        s.parentNode.insertBefore(t,s)}(window, document,'script',
        'https://connect.facebook.net/en_US/fbevents.js');
        fbq('init', '<?php echo esc_js(get_theme_mod('ai_woo_facebook_pixel_id')); ?>');
        fbq('track', 'PageView');
    </script>
    <noscript>
        <img height="1" width="1" style="display:none"
             src="https://www.facebook.com/tr?id=<?php echo esc_attr(get_theme_mod('ai_woo_facebook_pixel_id')); ?>&ev=PageView&noscript=1"/>
    </noscript>
<?php endif; ?>

<!-- Schema.org Structured Data -->
<?php if (is_front_page()): ?>
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "Organization",
        "name": "<?php bloginfo('name'); ?>",
        "url": "<?php echo esc_url(home_url('/')); ?>",
        "description": "<?php bloginfo('description'); ?>",
        "logo": "<?php echo esc_url(wp_get_attachment_image_src(get_theme_mod('custom_logo'), 'full')[0]); ?>"
        <?php if (get_theme_mod('ai_woo_facebook_url') || get_theme_mod('ai_woo_twitter_url') || get_theme_mod('ai_woo_instagram_url')): ?>
        ,"sameAs": [
            <?php $social_links = array(); ?>
            <?php if (get_theme_mod('ai_woo_facebook_url')): $social_links[] = '"' . esc_url(get_theme_mod('ai_woo_facebook_url')) . '"'; endif; ?>
            <?php if (get_theme_mod('ai_woo_twitter_url')): $social_links[] = '"' . esc_url(get_theme_mod('ai_woo_twitter_url')) . '"'; endif; ?>
            <?php if (get_theme_mod('ai_woo_instagram_url')): $social_links[] = '"' . esc_url(get_theme_mod('ai_woo_instagram_url')) . '"'; endif; ?>
            <?php echo implode(',', $social_links); ?>
        ]
        <?php endif; ?>
    }
    </script>
<?php endif; ?>

<?php wp_footer(); ?>

<!-- Inline Critical JavaScript for Performance -->
<script>
// Critical performance optimizations
(function() {
    // Lazy loading images
    if ('IntersectionObserver' in window) {
        const imageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.src = img.dataset.src || img.src;
                    img.classList.remove('lazy-load');
                    img.classList.add('loaded');
                    imageObserver.unobserve(img);
                }
            });
        });

        document.querySelectorAll('.lazy-load').forEach(img => {
            imageObserver.observe(img);
        });
    }

    // Preload critical resources on hover
    document.addEventListener('mouseover', function(e) {
        if (e.target.matches('a[href]')) {
            const link = document.createElement('link');
            link.rel = 'prefetch';
            link.href = e.target.href;
            document.head.appendChild(link);
        }
    }, { once: true });

    // Service Worker registration for PWA capabilities
    if ('serviceWorker' in navigator && '<?php echo esc_js(get_theme_mod('ai_woo_enable_pwa', false)); ?>') {
        navigator.serviceWorker.register('/sw.js')
            .then(registration => console.log('SW registered'))
            .catch(error => console.log('SW registration failed'));
    }
})();

// Initialize theme on DOM ready
document.addEventListener('DOMContentLoaded', function() {
    // Initialize SPA router
    if (typeof AIWooSPARouter !== 'undefined') {
        AIWooSPARouter.init();
    }
    
    // Initialize AI features
    if (typeof AIWooAI !== 'undefined') {
        AIWooAI.init();
    }
    
    // Initialize cart recovery
    if (typeof AIWooCartRecovery !== 'undefined') {
        AIWooCartRecovery.init();
    }
});
</script>

</body>
</html>