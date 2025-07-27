<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="profile" href="https://gmpg.org/xfn/11">
    
    <!-- Preconnect to external domains for performance -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preconnect" href="https://cdnjs.cloudflare.com">
    
    <!-- DNS prefetch for external resources -->
    <link rel="dns-prefetch" href="//fonts.googleapis.com">
    <link rel="dns-prefetch" href="//cdnjs.cloudflare.com">
    
    <!-- Theme color for mobile browsers -->
    <meta name="theme-color" content="<?php echo esc_attr(get_theme_mod('ai_woo_primary_color', '#2563eb')); ?>">
    
    <!-- Structured data for SEO -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "WebSite",
        "name": "<?php bloginfo('name'); ?>",
        "url": "<?php echo esc_url(home_url('/')); ?>",
        "description": "<?php bloginfo('description'); ?>"
        <?php if (class_exists('WooCommerce')): ?>,
        "potentialAction": {
            "@type": "SearchAction",
            "target": "<?php echo esc_url(home_url('/')); ?>?s={search_term_string}",
            "query-input": "required name=search_term_string"
        }
        <?php endif; ?>
    }
    </script>
    
    <?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<!-- Skip Link for Accessibility -->
<a class="sr-only" href="#main-content"><?php esc_html_e('Skip to main content', 'ai-woo-theme'); ?></a>

<!-- Loading Overlay for SPA -->
<div id="spa-loading-overlay" class="spa-loading-overlay" style="display: none;">
    <div class="loading-content">
        <div class="loading"></div>
        <p><?php esc_html_e('Loading...', 'ai-woo-theme'); ?></p>
    </div>
</div>

<header class="site-header" id="site-header">
    <div class="container">
        <div class="header-content">
            <!-- Logo -->
            <div class="site-branding">
                <?php if (has_custom_logo()): ?>
                    <div class="custom-logo">
                        <?php the_custom_logo(); ?>
                    </div>
                <?php else: ?>
                    <h1 class="site-title">
                        <a href="<?php echo esc_url(home_url('/')); ?>" class="logo" rel="home">
                            <?php bloginfo('name'); ?>
                        </a>
                    </h1>
                    <?php if (get_bloginfo('description')): ?>
                        <p class="site-description"><?php bloginfo('description'); ?></p>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

            <!-- Mobile Menu Toggle -->
            <button class="mobile-menu-toggle" id="mobile-menu-toggle" aria-label="<?php esc_attr_e('Toggle mobile menu', 'ai-woo-theme'); ?>">
                <span></span>
                <span></span>
                <span></span>
            </button>

            <!-- Navigation -->
            <nav class="main-nav" id="main-nav">
                <?php
                wp_nav_menu(array(
                    'theme_location' => 'primary',
                    'menu_id'        => 'primary-menu',
                    'container'      => false,
                    'fallback_cb'    => 'ai_woo_fallback_menu',
                    'walker'         => new AI_Woo_Walker_Nav_Menu(),
                ));
                ?>
            </nav>

            <!-- Header Actions -->
            <div class="header-actions">
                <!-- Search -->
                <div class="header-search">
                    <button class="search-toggle" id="search-toggle" aria-label="<?php esc_attr_e('Toggle search', 'ai-woo-theme'); ?>">
                        <i class="fas fa-search"></i>
                    </button>
                    <div class="search-form-wrapper" id="search-form-wrapper">
                        <?php get_search_form(); ?>
                    </div>
                </div>

                <!-- User Account -->
                <?php if (class_exists('WooCommerce')): ?>
                    <div class="header-account">
                        <a href="<?php echo esc_url(wc_get_page_permalink('myaccount')); ?>" class="account-link" aria-label="<?php esc_attr_e('My Account', 'ai-woo-theme'); ?>">
                            <i class="fas fa-user"></i>
                            <?php if (is_user_logged_in()): ?>
                                <span class="account-text"><?php esc_html_e('Account', 'ai-woo-theme'); ?></span>
                            <?php else: ?>
                                <span class="account-text"><?php esc_html_e('Login', 'ai-woo-theme'); ?></span>
                            <?php endif; ?>
                        </a>
                    </div>

                    <!-- Shopping Cart -->
                    <div class="header-cart">
                        <a href="<?php echo esc_url(wc_get_cart_url()); ?>" class="cart-link" id="cart-link" aria-label="<?php esc_attr_e('View cart', 'ai-woo-theme'); ?>">
                            <div class="cart-icon">
                                <i class="fas fa-shopping-cart"></i>
                                <span class="cart-count" id="cart-count">
                                    <?php echo WC()->cart->get_cart_contents_count(); ?>
                                </span>
                            </div>
                            <div class="cart-info">
                                <span class="cart-text"><?php esc_html_e('Cart', 'ai-woo-theme'); ?></span>
                                <span class="cart-total" id="cart-total">
                                    <?php echo WC()->cart->get_cart_subtotal(); ?>
                                </span>
                            </div>
                        </a>
                        
                        <!-- Mini Cart Dropdown -->
                        <div class="mini-cart-dropdown" id="mini-cart-dropdown">
                            <div class="mini-cart-content">
                                <?php woocommerce_mini_cart(); ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- AI Assistant Toggle -->
                <?php if (get_theme_mod('ai_woo_enable_ai', true)): ?>
                    <div class="ai-assistant-toggle">
                        <button class="ai-toggle-btn" id="ai-assistant-toggle" aria-label="<?php esc_attr_e('Toggle AI Assistant', 'ai-woo-theme'); ?>">
                            <i class="fas fa-robot"></i>
                            <span class="ai-text"><?php esc_html_e('AI', 'ai-woo-theme'); ?></span>
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Mobile Navigation -->
    <div class="mobile-nav" id="mobile-nav">
        <div class="mobile-nav-content">
            <?php
            wp_nav_menu(array(
                'theme_location' => 'primary',
                'menu_id'        => 'mobile-menu',
                'container'      => false,
                'fallback_cb'    => 'ai_woo_fallback_menu',
            ));
            ?>
            
            <!-- Mobile Search -->
            <div class="mobile-search">
                <?php get_search_form(); ?>
            </div>
            
            <!-- Mobile Account Links -->
            <?php if (class_exists('WooCommerce')): ?>
                <div class="mobile-account-links">
                    <a href="<?php echo esc_url(wc_get_page_permalink('myaccount')); ?>" class="mobile-account-link">
                        <i class="fas fa-user"></i>
                        <?php if (is_user_logged_in()): ?>
                            <?php esc_html_e('My Account', 'ai-woo-theme'); ?>
                        <?php else: ?>
                            <?php esc_html_e('Login / Register', 'ai-woo-theme'); ?>
                        <?php endif; ?>
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</header>

<!-- AI Assistant Panel -->
<?php if (get_theme_mod('ai_woo_enable_ai', true)): ?>
    <div class="ai-assistant-panel" id="ai-assistant-panel">
        <div class="ai-panel-header">
            <h3>
                <i class="fas fa-robot"></i>
                <?php esc_html_e('AI Shopping Assistant', 'ai-woo-theme'); ?>
            </h3>
            <button class="ai-panel-close" id="ai-panel-close" aria-label="<?php esc_attr_e('Close AI Assistant', 'ai-woo-theme'); ?>">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="ai-panel-content">
            <div class="ai-chat-messages" id="ai-chat-messages">
                <div class="ai-message">
                    <div class="ai-avatar">
                        <i class="fas fa-robot"></i>
                    </div>
                    <div class="ai-message-content">
                        <p><?php esc_html_e('Hello! I\'m your AI shopping assistant. How can I help you find the perfect products today?', 'ai-woo-theme'); ?></p>
                    </div>
                </div>
            </div>
            <div class="ai-chat-input">
                <input type="text" id="ai-chat-input" placeholder="<?php esc_attr_e('Ask me anything...', 'ai-woo-theme'); ?>">
                <button id="ai-chat-send" aria-label="<?php esc_attr_e('Send message', 'ai-woo-theme'); ?>">
                    <i class="fas fa-paper-plane"></i>
                </button>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php
/**
 * Fallback menu function
 */
function ai_woo_fallback_menu() {
    echo '<ul id="primary-menu" class="menu">';
    echo '<li><a href="' . esc_url(home_url('/')) . '">' . esc_html__('Home', 'ai-woo-theme') . '</a></li>';
    if (class_exists('WooCommerce')) {
        echo '<li><a href="' . esc_url(wc_get_page_permalink('shop')) . '">' . esc_html__('Shop', 'ai-woo-theme') . '</a></li>';
    }
    echo '<li><a href="' . esc_url(home_url('/about/')) . '">' . esc_html__('About', 'ai-woo-theme') . '</a></li>';
    echo '<li><a href="' . esc_url(home_url('/contact/')) . '">' . esc_html__('Contact', 'ai-woo-theme') . '</a></li>';
    echo '</ul>';
}

/**
 * Custom Walker for Navigation Menu
 */
class AI_Woo_Walker_Nav_Menu extends Walker_Nav_Menu {
    
    function start_lvl(&$output, $depth = 0, $args = null) {
        $indent = str_repeat("\t", $depth);
        $output .= "\n$indent<ul class=\"sub-menu\">\n";
    }

    function end_lvl(&$output, $depth = 0, $args = null) {
        $indent = str_repeat("\t", $depth);
        $output .= "$indent</ul>\n";
    }

    function start_el(&$output, $item, $depth = 0, $args = null, $id = 0) {
        $indent = ($depth) ? str_repeat("\t", $depth) : '';

        $classes = empty($item->classes) ? array() : (array) $item->classes;
        $classes[] = 'menu-item-' . $item->ID;

        $class_names = join(' ', apply_filters('nav_menu_css_class', array_filter($classes), $item, $args));
        $class_names = $class_names ? ' class="' . esc_attr($class_names) . '"' : '';

        $id = apply_filters('nav_menu_item_id', 'menu-item-' . $item->ID, $item, $args);
        $id = $id ? ' id="' . esc_attr($id) . '"' : '';

        $output .= $indent . '<li' . $id . $class_names .'>';

        $attributes = ! empty($item->attr_title) ? ' title="'  . esc_attr($item->attr_title) .'"' : '';
        $attributes .= ! empty($item->target) ? ' target="' . esc_attr($item->target) .'"' : '';
        $attributes .= ! empty($item->xfn) ? ' rel="'    . esc_attr($item->xfn) .'"' : '';
        $attributes .= ! empty($item->url) ? ' href="'   . esc_attr($item->url) .'"' : '';

        $item_output = isset($args->before) ? $args->before : '';
        $item_output .= '<a' . $attributes . ' class="spa-link">';
        $item_output .= (isset($args->link_before) ? $args->link_before : '') . apply_filters('the_title', $item->title, $item->ID) . (isset($args->link_after) ? $args->link_after : '');
        $item_output .= '</a>';
        $item_output .= isset($args->after) ? $args->after : '';

        $output .= apply_filters('walker_nav_menu_start_el', $item_output, $item, $depth, $args);
    }

    function end_el(&$output, $item, $depth = 0, $args = null) {
        $output .= "</li>\n";
    }
}
?>