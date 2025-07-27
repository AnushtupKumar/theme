<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="profile" href="https://gmpg.org/xfn/11">
    
    <?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<div id="page" class="site">
    <a class="skip-link screen-reader-text" href="#primary"><?php esc_html_e('Skip to content', 'ai-commerce'); ?></a>

    <header id="masthead" class="ai-header site-header">
        <div class="ai-container">
            <div class="ai-header-content">
                <div class="site-branding">
                    <?php
                    if (has_custom_logo()) {
                        the_custom_logo();
                    } else {
                        ?>
                        <a href="<?php echo esc_url(home_url('/')); ?>" class="ai-logo" rel="home">
                            <?php bloginfo('name'); ?>
                        </a>
                        <?php
                    }
                    ?>
                </div>

                <nav id="site-navigation" class="ai-nav main-navigation">
                    <?php
                    wp_nav_menu(array(
                        'theme_location' => 'primary',
                        'menu_id'        => 'primary-menu',
                        'menu_class'     => 'ai-nav-menu',
                        'container'      => false,
                        'fallback_cb'    => false,
                        'walker'         => new AI_Commerce_Walker_Nav_Menu(),
                    ));
                    ?>
                    
                    <div class="ai-header-actions">
                        <?php if (class_exists('WooCommerce')) : ?>
                            <a href="<?php echo esc_url(wc_get_account_endpoint_url('dashboard')); ?>" class="ai-header-account" title="<?php esc_attr_e('My Account', 'ai-commerce'); ?>">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                    <circle cx="12" cy="7" r="4"></circle>
                                </svg>
                            </a>
                            
                            <a href="<?php echo esc_url(wc_get_cart_url()); ?>" class="ai-header-cart" title="<?php esc_attr_e('View Cart', 'ai-commerce'); ?>">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="9" cy="21" r="1"></circle>
                                    <circle cx="20" cy="21" r="1"></circle>
                                    <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                                </svg>
                                <?php if (WC()->cart->get_cart_contents_count() > 0) : ?>
                                    <span class="ai-cart-count"><?php echo WC()->cart->get_cart_contents_count(); ?></span>
                                <?php endif; ?>
                            </a>
                        <?php endif; ?>
                        
                        <button class="ai-search-toggle" aria-label="<?php esc_attr_e('Toggle Search', 'ai-commerce'); ?>">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="11" cy="11" r="8"></circle>
                                <path d="m21 21-4.35-4.35"></path>
                            </svg>
                        </button>
                    </div>
                </nav>

                <button class="ai-mobile-menu-toggle" aria-controls="primary-menu" aria-expanded="false">
                    <span class="ai-menu-icon">
                        <span></span>
                        <span></span>
                        <span></span>
                    </span>
                </button>
            </div>
        </div>
        
        <!-- Search Overlay -->
        <div class="ai-search-overlay" style="display: none;">
            <div class="ai-container">
                <form role="search" method="get" class="ai-search-form" action="<?php echo esc_url(home_url('/')); ?>">
                    <label>
                        <span class="screen-reader-text"><?php echo _x('Search for:', 'label', 'ai-commerce'); ?></span>
                        <input type="search" class="ai-search-field" placeholder="<?php echo esc_attr_x('Search products, categories...', 'placeholder', 'ai-commerce'); ?>" value="<?php echo get_search_query(); ?>" name="s" />
                    </label>
                    <button type="submit" class="ai-search-submit">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="11" cy="11" r="8"></circle>
                            <path d="m21 21-4.35-4.35"></path>
                        </svg>
                    </button>
                    <?php if (class_exists('WooCommerce')) : ?>
                        <input type="hidden" name="post_type" value="product">
                    <?php endif; ?>
                </form>
                <div class="ai-search-suggestions" style="display: none;"></div>
            </div>
        </div>
    </header>

    <main id="primary" class="site-main">