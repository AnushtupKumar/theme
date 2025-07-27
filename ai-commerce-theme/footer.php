    </main><!-- #primary -->

    <footer id="colophon" class="ai-footer site-footer">
        <div class="ai-container">
            <div class="ai-footer-content">
                <div class="ai-footer-section">
                    <h3><?php esc_html_e('About Us', 'ai-commerce'); ?></h3>
                    <?php
                    $footer_about = get_theme_mod('ai_commerce_footer_about', 'Your trusted AI-powered e-commerce solution for personalized shopping experiences.');
                    echo '<p>' . esc_html($footer_about) . '</p>';
                    ?>
                </div>
                
                <div class="ai-footer-section">
                    <h3><?php esc_html_e('Quick Links', 'ai-commerce'); ?></h3>
                    <?php
                    wp_nav_menu(array(
                        'theme_location' => 'footer',
                        'menu_class'     => 'ai-footer-menu',
                        'container'      => false,
                        'depth'          => 1,
                        'fallback_cb'    => false,
                    ));
                    ?>
                </div>
                
                <?php if (class_exists('WooCommerce')) : ?>
                <div class="ai-footer-section">
                    <h3><?php esc_html_e('Customer Service', 'ai-commerce'); ?></h3>
                    <ul>
                        <li><a href="<?php echo esc_url(wc_get_page_permalink('myaccount')); ?>"><?php esc_html_e('My Account', 'ai-commerce'); ?></a></li>
                        <li><a href="<?php echo esc_url(wc_get_page_permalink('shop')); ?>"><?php esc_html_e('Shop', 'ai-commerce'); ?></a></li>
                        <li><a href="<?php echo esc_url(wc_get_cart_url()); ?>"><?php esc_html_e('Cart', 'ai-commerce'); ?></a></li>
                        <li><a href="<?php echo esc_url(wc_get_checkout_url()); ?>"><?php esc_html_e('Checkout', 'ai-commerce'); ?></a></li>
                    </ul>
                </div>
                <?php endif; ?>
                
                <div class="ai-footer-section">
                    <h3><?php esc_html_e('Newsletter', 'ai-commerce'); ?></h3>
                    <p><?php esc_html_e('Subscribe to get special offers and AI-powered recommendations.', 'ai-commerce'); ?></p>
                    <form class="ai-newsletter-form" action="#" method="post">
                        <div class="ai-form-group">
                            <input type="email" class="ai-form-input" placeholder="<?php esc_attr_e('Your email address', 'ai-commerce'); ?>" required>
                            <button type="submit" class="ai-btn ai-btn-primary"><?php esc_html_e('Subscribe', 'ai-commerce'); ?></button>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="ai-footer-bottom">
                <div class="ai-footer-copyright">
                    <?php
                    printf(
                        esc_html__('&copy; %1$s %2$s. All rights reserved. Powered by AI Commerce Theme.', 'ai-commerce'),
                        date('Y'),
                        get_bloginfo('name')
                    );
                    ?>
                </div>
                
                <div class="ai-footer-social">
                    <?php
                    $social_links = array(
                        'facebook' => get_theme_mod('ai_commerce_facebook_url'),
                        'twitter' => get_theme_mod('ai_commerce_twitter_url'),
                        'instagram' => get_theme_mod('ai_commerce_instagram_url'),
                        'linkedin' => get_theme_mod('ai_commerce_linkedin_url'),
                    );
                    
                    foreach ($social_links as $platform => $url) {
                        if ($url) {
                            printf(
                                '<a href="%s" target="_blank" rel="noopener noreferrer" aria-label="%s">%s</a>',
                                esc_url($url),
                                esc_attr(ucfirst($platform)),
                                esc_html(ucfirst($platform))
                            );
                        }
                    }
                    ?>
                </div>
            </div>
        </div>
    </footer><!-- #colophon -->
    
    <?php if (get_theme_mod('ai_chatbot_enabled', true)) : ?>
    <!-- AI Chat Widget -->
    <div class="ai-chat-widget" id="ai-chat-widget">
        <button class="ai-chat-button" aria-label="<?php esc_attr_e('Open AI Assistant', 'ai-commerce'); ?>">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                <circle cx="9" cy="10" r="1" fill="currentColor"></circle>
                <circle cx="12" cy="10" r="1" fill="currentColor"></circle>
                <circle cx="15" cy="10" r="1" fill="currentColor"></circle>
            </svg>
        </button>
        <div class="ai-chat-window" style="display: none;">
            <div class="ai-chat-header">
                <h4><?php esc_html_e('AI Shopping Assistant', 'ai-commerce'); ?></h4>
                <button class="ai-chat-close" aria-label="<?php esc_attr_e('Close chat', 'ai-commerce'); ?>">×</button>
            </div>
            <div class="ai-chat-messages">
                <div class="ai-chat-message ai-chat-bot">
                    <p><?php esc_html_e('Hello! I\'m your AI shopping assistant. How can I help you today?', 'ai-commerce'); ?></p>
                </div>
            </div>
            <form class="ai-chat-input-form">
                <input type="text" class="ai-chat-input" placeholder="<?php esc_attr_e('Type your message...', 'ai-commerce'); ?>">
                <button type="submit" class="ai-chat-send">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="22" y1="2" x2="11" y2="13"></line>
                        <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
                    </svg>
                </button>
            </form>
        </div>
    </div>
    <?php endif; ?>
    
    <?php if (get_theme_mod('ai_cart_recovery_enabled', true) && class_exists('WooCommerce')) : ?>
    <!-- Cart Recovery Modal (hidden by default, shown by JS when appropriate) -->
    <div class="ai-cart-recovery" id="ai-cart-recovery" style="display: none;">
        <button class="ai-cart-recovery-close" aria-label="<?php esc_attr_e('Close', 'ai-commerce'); ?>">×</button>
        <h3><?php esc_html_e('Wait! Don\'t leave yet!', 'ai-commerce'); ?></h3>
        <p><?php esc_html_e('You have items in your cart. Complete your purchase and get', 'ai-commerce'); ?> <strong>10% OFF</strong>!</p>
        <div class="ai-cart-recovery-code">
            <span><?php esc_html_e('Use code:', 'ai-commerce'); ?></span>
            <strong>SAVE10</strong>
        </div>
        <a href="<?php echo esc_url(wc_get_cart_url()); ?>" class="ai-btn ai-btn-primary"><?php esc_html_e('Return to Cart', 'ai-commerce'); ?></a>
    </div>
    <?php endif; ?>

</div><!-- #page -->

<?php wp_footer(); ?>

</body>
</html>