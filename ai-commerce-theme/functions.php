<?php
/**
 * AI Commerce Theme Functions
 *
 * @package AI_Commerce
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define theme constants
define('AI_COMMERCE_VERSION', '1.0.0');
define('AI_COMMERCE_DIR', get_template_directory());
define('AI_COMMERCE_URI', get_template_directory_uri());
define('AI_COMMERCE_INC', AI_COMMERCE_DIR . '/inc');
define('AI_COMMERCE_ASSETS', AI_COMMERCE_URI . '/assets');

// Theme setup
function ai_commerce_setup() {
    // Add theme support
    add_theme_support('automatic-feed-links');
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('html5', array(
        'search-form',
        'comment-form',
        'comment-list',
        'gallery',
        'caption',
        'style',
        'script',
    ));
    add_theme_support('customize-selective-refresh-widgets');
    add_theme_support('woocommerce');
    add_theme_support('wc-product-gallery-zoom');
    add_theme_support('wc-product-gallery-lightbox');
    add_theme_support('wc-product-gallery-slider');
    
    // Add custom logo support
    add_theme_support('custom-logo', array(
        'height'      => 60,
        'width'       => 200,
        'flex-height' => true,
        'flex-width'  => true,
    ));
    
    // Register navigation menus
    register_nav_menus(array(
        'primary' => esc_html__('Primary Menu', 'ai-commerce'),
        'footer'  => esc_html__('Footer Menu', 'ai-commerce'),
        'mobile'  => esc_html__('Mobile Menu', 'ai-commerce'),
    ));
    
    // Set content width
    if (!isset($content_width)) {
        $content_width = 1280;
    }
}
add_action('after_setup_theme', 'ai_commerce_setup');

// Enqueue scripts and styles
function ai_commerce_scripts() {
    // Enqueue styles
    wp_enqueue_style('ai-commerce-style', get_stylesheet_uri(), array(), AI_COMMERCE_VERSION);
    
    // Enqueue Google Fonts
    wp_enqueue_style('ai-commerce-fonts', 'https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap', array(), null);
    
    // Enqueue scripts
    wp_enqueue_script('ai-commerce-vendor', AI_COMMERCE_ASSETS . '/js/vendor.bundle.js', array(), AI_COMMERCE_VERSION, true);
    wp_enqueue_script('ai-commerce-app', AI_COMMERCE_ASSETS . '/js/app.bundle.js', array('ai-commerce-vendor'), AI_COMMERCE_VERSION, true);
    
    // Localize script for AJAX and REST API
    wp_localize_script('ai-commerce-app', 'aiCommerce', array(
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'restUrl' => esc_url_raw(rest_url('ai-commerce/v1')),
        'nonce' => wp_create_nonce('ai-commerce-nonce'),
        'siteUrl' => home_url(),
        'themeUrl' => AI_COMMERCE_URI,
        'isLoggedIn' => is_user_logged_in(),
        'userId' => get_current_user_id(),
        'isShop' => is_shop(),
        'isProduct' => is_product(),
        'isCart' => is_cart(),
        'isCheckout' => is_checkout(),
        'currency' => get_woocommerce_currency(),
        'currencySymbol' => get_woocommerce_currency_symbol(),
        'aiSettings' => get_theme_mod('ai_commerce_ai_settings', array()),
        'personalizationEnabled' => get_theme_mod('ai_personalization_enabled', true),
        'cartRecoveryEnabled' => get_theme_mod('ai_cart_recovery_enabled', true),
        'chatbotEnabled' => get_theme_mod('ai_chatbot_enabled', true),
    ));
    
    // Remove default WooCommerce styles
    wp_dequeue_style('woocommerce-general');
    wp_dequeue_style('woocommerce-layout');
    wp_dequeue_style('woocommerce-smallscreen');
}
add_action('wp_enqueue_scripts', 'ai_commerce_scripts');

// Register widget areas
function ai_commerce_widgets_init() {
    register_sidebar(array(
        'name'          => esc_html__('Sidebar', 'ai-commerce'),
        'id'            => 'sidebar-1',
        'description'   => esc_html__('Add widgets here.', 'ai-commerce'),
        'before_widget' => '<section id="%1$s" class="widget %2$s">',
        'after_widget'  => '</section>',
        'before_title'  => '<h2 class="widget-title">',
        'after_title'   => '</h2>',
    ));
    
    register_sidebar(array(
        'name'          => esc_html__('Shop Sidebar', 'ai-commerce'),
        'id'            => 'shop-sidebar',
        'description'   => esc_html__('Widgets for shop pages.', 'ai-commerce'),
        'before_widget' => '<section id="%1$s" class="widget %2$s">',
        'after_widget'  => '</section>',
        'before_title'  => '<h2 class="widget-title">',
        'after_title'   => '</h2>',
    ));
}
add_action('widgets_init', 'ai_commerce_widgets_init');

// Include required files
require_once AI_COMMERCE_INC . '/class-ai-commerce-loader.php';
require_once AI_COMMERCE_INC . '/customizer/customizer.php';
require_once AI_COMMERCE_INC . '/ai/class-ai-engine.php';
require_once AI_COMMERCE_INC . '/ai/class-personalization.php';
require_once AI_COMMERCE_INC . '/ai/class-cart-recovery.php';
require_once AI_COMMERCE_INC . '/ai/class-chatbot.php';
require_once AI_COMMERCE_INC . '/ai/class-visual-search.php';
require_once AI_COMMERCE_INC . '/ai/class-user-segmentation.php';
require_once AI_COMMERCE_INC . '/ai/class-predictive-search.php';
require_once AI_COMMERCE_INC . '/woocommerce/woocommerce-functions.php';
require_once AI_COMMERCE_INC . '/api/class-rest-api.php';
require_once AI_COMMERCE_INC . '/class-theme-optimizer.php';

// Initialize theme
function ai_commerce_init() {
    // Initialize AI Engine
    $ai_engine = new AI_Commerce_AI_Engine();
    
    // Initialize Personalization
    $personalization = new AI_Commerce_Personalization();
    
    // Initialize Cart Recovery
    $cart_recovery = new AI_Commerce_Cart_Recovery();
    
    // Initialize Chatbot
    $chatbot = new AI_Commerce_Chatbot();
    
    // Initialize REST API
    $rest_api = new AI_Commerce_REST_API();
    
    // Initialize Theme Optimizer
    $optimizer = new AI_Commerce_Theme_Optimizer();
}
add_action('init', 'ai_commerce_init');

// Add body classes
function ai_commerce_body_classes($classes) {
    // Add SPA class
    $classes[] = 'ai-commerce-spa';
    
    // Add personalization class if enabled
    if (get_theme_mod('ai_personalization_enabled', true)) {
        $classes[] = 'ai-personalization-enabled';
    }
    
    // Add user state classes
    if (is_user_logged_in()) {
        $classes[] = 'logged-in-user';
    } else {
        $classes[] = 'guest-user';
    }
    
    return $classes;
}
add_filter('body_class', 'ai_commerce_body_classes');

// Custom excerpt length
function ai_commerce_excerpt_length($length) {
    return 20;
}
add_filter('excerpt_length', 'ai_commerce_excerpt_length');

// Add async/defer attributes to scripts
function ai_commerce_script_attributes($tag, $handle) {
    if ('ai-commerce-app' === $handle) {
        return str_replace(' src', ' defer src', $tag);
    }
    return $tag;
}
add_filter('script_loader_tag', 'ai_commerce_script_attributes', 10, 2);

// SEO optimization
function ai_commerce_seo_meta() {
    if (is_single() || is_page()) {
        global $post;
        $description = get_post_meta($post->ID, '_ai_commerce_meta_description', true);
        if (empty($description)) {
            $description = wp_trim_words($post->post_excerpt ?: $post->post_content, 30);
        }
        ?>
        <meta name="description" content="<?php echo esc_attr($description); ?>">
        <?php
        
        // Open Graph tags
        ?>
        <meta property="og:title" content="<?php echo esc_attr(get_the_title()); ?>">
        <meta property="og:description" content="<?php echo esc_attr($description); ?>">
        <meta property="og:url" content="<?php echo esc_url(get_permalink()); ?>">
        <meta property="og:type" content="<?php echo is_product() ? 'product' : 'article'; ?>">
        <?php
        
        if (has_post_thumbnail()) {
            $thumbnail = wp_get_attachment_image_src(get_post_thumbnail_id(), 'large');
            ?>
            <meta property="og:image" content="<?php echo esc_url($thumbnail[0]); ?>">
            <?php
        }
        
        // Twitter Cards
        ?>
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:title" content="<?php echo esc_attr(get_the_title()); ?>">
        <meta name="twitter:description" content="<?php echo esc_attr($description); ?>">
        <?php
    }
    
    // Schema.org structured data for products
    if (is_product()) {
        global $product;
        $schema = array(
            '@context' => 'https://schema.org/',
            '@type' => 'Product',
            'name' => $product->get_name(),
            'description' => $product->get_short_description(),
            'sku' => $product->get_sku(),
            'offers' => array(
                '@type' => 'Offer',
                'price' => $product->get_price(),
                'priceCurrency' => get_woocommerce_currency(),
                'availability' => $product->is_in_stock() ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
            ),
        );
        
        if ($product->get_average_rating() > 0) {
            $schema['aggregateRating'] = array(
                '@type' => 'AggregateRating',
                'ratingValue' => $product->get_average_rating(),
                'reviewCount' => $product->get_review_count(),
            );
        }
        
        ?>
        <script type="application/ld+json">
        <?php echo json_encode($schema); ?>
        </script>
        <?php
    }
}
add_action('wp_head', 'ai_commerce_seo_meta');

// Performance optimization
function ai_commerce_performance_tweaks() {
    // Remove emoji scripts
    remove_action('wp_head', 'print_emoji_detection_script', 7);
    remove_action('wp_print_styles', 'print_emoji_styles');
    
    // Remove WordPress version
    remove_action('wp_head', 'wp_generator');
    
    // Remove RSD link
    remove_action('wp_head', 'rsd_link');
    
    // Remove Windows Live Writer
    remove_action('wp_head', 'wlwmanifest_link');
    
    // Remove shortlink
    remove_action('wp_head', 'wp_shortlink_wp_head');
}
add_action('init', 'ai_commerce_performance_tweaks');

// Add preconnect for external resources
function ai_commerce_resource_hints($hints, $relation_type) {
    if ('dns-prefetch' === $relation_type || 'preconnect' === $relation_type) {
        $hints[] = '//fonts.googleapis.com';
        $hints[] = '//fonts.gstatic.com';
    }
    return $hints;
}
add_filter('wp_resource_hints', 'ai_commerce_resource_hints', 10, 2);

// Custom login logo
function ai_commerce_login_logo() {
    $custom_logo_id = get_theme_mod('custom_logo');
    if ($custom_logo_id) {
        $logo = wp_get_attachment_image_src($custom_logo_id, 'full');
        ?>
        <style type="text/css">
            #login h1 a, .login h1 a {
                background-image: url(<?php echo esc_url($logo[0]); ?>);
                background-size: contain;
                width: 100%;
                height: 80px;
            }
        </style>
        <?php
    }
}
add_action('login_enqueue_scripts', 'ai_commerce_login_logo');

// AJAX handlers for SPA functionality
add_action('wp_ajax_ai_commerce_load_page', 'ai_commerce_ajax_load_page');
add_action('wp_ajax_nopriv_ai_commerce_load_page', 'ai_commerce_ajax_load_page');

function ai_commerce_ajax_load_page() {
    check_ajax_referer('ai-commerce-nonce', 'nonce');
    
    $page_id = isset($_POST['page_id']) ? intval($_POST['page_id']) : 0;
    $post_type = isset($_POST['post_type']) ? sanitize_text_field($_POST['post_type']) : 'page';
    
    if ($page_id) {
        $post = get_post($page_id);
        if ($post) {
            $content = apply_filters('the_content', $post->post_content);
            wp_send_json_success(array(
                'title' => $post->post_title,
                'content' => $content,
                'meta' => get_post_meta($page_id),
            ));
        }
    }
    
    wp_send_json_error('Page not found');
}

// Add theme update checker
function ai_commerce_check_for_updates() {
    if (is_admin()) {
        // Check for theme updates from custom server
        // This is where you would implement your update logic
    }
}
add_action('admin_init', 'ai_commerce_check_for_updates');