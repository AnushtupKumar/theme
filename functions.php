<?php
/**
 * AI-Powered WooCommerce Theme Functions
 *
 * @package AI_Woo_Theme
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Theme constants
define('AI_WOO_THEME_VERSION', '1.0.0');
define('AI_WOO_THEME_PATH', get_template_directory());
define('AI_WOO_THEME_URL', get_template_directory_uri());

/**
 * Theme Setup
 */
function ai_woo_theme_setup() {
    // Add theme support
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('html5', array(
        'search-form',
        'comment-form',
        'comment-list',
        'gallery',
        'caption',
        'style',
        'script'
    ));
    add_theme_support('customize-selective-refresh-widgets');
    add_theme_support('responsive-embeds');
    
    // WooCommerce support
    add_theme_support('woocommerce');
    add_theme_support('wc-product-gallery-zoom');
    add_theme_support('wc-product-gallery-lightbox');
    add_theme_support('wc-product-gallery-slider');
    
    // Custom logo support
    add_theme_support('custom-logo', array(
        'height'      => 250,
        'width'       => 250,
        'flex-width'  => true,
        'flex-height' => true,
    ));
    
    // Register navigation menus
    register_nav_menus(array(
        'primary' => esc_html__('Primary Menu', 'ai-woo-theme'),
        'footer'  => esc_html__('Footer Menu', 'ai-woo-theme'),
    ));
    
    // Add image sizes
    add_image_size('ai-woo-product-thumb', 300, 300, true);
    add_image_size('ai-woo-hero', 1200, 600, true);
}
add_action('after_setup_theme', 'ai_woo_theme_setup');

/**
 * Enqueue Scripts and Styles
 */
function ai_woo_theme_scripts() {
    // Main stylesheet
    wp_enqueue_style('ai-woo-theme-style', get_stylesheet_uri(), array(), AI_WOO_THEME_VERSION);
    
    // Custom CSS for customizer
    wp_add_inline_style('ai-woo-theme-style', ai_woo_get_customizer_css());
    
    // Main JavaScript
    wp_enqueue_script('ai-woo-theme-script', AI_WOO_THEME_URL . '/assets/js/main.js', array('jquery'), AI_WOO_THEME_VERSION, true);
    
    // AI Integration Script
    wp_enqueue_script('ai-woo-ai-script', AI_WOO_THEME_URL . '/assets/js/ai-integration.js', array('jquery'), AI_WOO_THEME_VERSION, true);
    
    // SPA Router
    wp_enqueue_script('ai-woo-router', AI_WOO_THEME_URL . '/assets/js/spa-router.js', array('jquery'), AI_WOO_THEME_VERSION, true);
    
    // Cart Abandonment Recovery
    wp_enqueue_script('ai-woo-cart-recovery', AI_WOO_THEME_URL . '/assets/js/cart-recovery.js', array('jquery'), AI_WOO_THEME_VERSION, true);
    
    // Localize script for AJAX
    wp_localize_script('ai-woo-theme-script', 'ai_woo_ajax', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('ai_woo_nonce'),
        'site_url' => home_url('/'),
    ));
    
    // Google Fonts
    wp_enqueue_style('ai-woo-fonts', 'https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap', array(), null);
    
    // Font Awesome for icons
    wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css', array(), '6.0.0');
    
    // Comment reply script
    if (is_singular() && comments_open() && get_option('thread_comments')) {
        wp_enqueue_script('comment-reply');
    }
}
add_action('wp_enqueue_scripts', 'ai_woo_theme_scripts');

/**
 * Admin Scripts and Styles
 */
function ai_woo_admin_scripts($hook) {
    if ('toplevel_page_ai-woo-theme-options' === $hook) {
        wp_enqueue_style('ai-woo-admin-style', AI_WOO_THEME_URL . '/assets/css/admin.css', array(), AI_WOO_THEME_VERSION);
        wp_enqueue_script('ai-woo-admin-script', AI_WOO_THEME_URL . '/assets/js/admin.js', array('jquery'), AI_WOO_THEME_VERSION, true);
        wp_enqueue_media();
    }
}
add_action('admin_enqueue_scripts', 'ai_woo_admin_scripts');

/**
 * Include Required Files
 */
// List of PHP files to include from the /inc directory.
$ai_woo_includes = array(
    'customizer.php',
    'ai-integration.php',
    'woocommerce-functions.php',
    'cart-recovery.php',
    'admin-panel.php',    // optional – only loaded if present.
    'seo-optimization.php', // optional – only loaded if present.
    'performance.php',      // optional – only loaded if present.
);

// Require each file if it exists to avoid fatal errors during theme load.
foreach ($ai_woo_includes as $file) {
    $filepath = AI_WOO_THEME_PATH . '/inc/' . $file;
    if (file_exists($filepath)) {
        require_once $filepath;
    }
}

/**
 * Widget Areas
 */
function ai_woo_widgets_init() {
    register_sidebar(array(
        'name'          => esc_html__('Sidebar', 'ai-woo-theme'),
        'id'            => 'sidebar-1',
        'description'   => esc_html__('Add widgets here.', 'ai-woo-theme'),
        'before_widget' => '<section id="%1$s" class="widget %2$s">',
        'after_widget'  => '</section>',
        'before_title'  => '<h2 class="widget-title">',
        'after_title'   => '</h2>',
    ));
    
    register_sidebar(array(
        'name'          => esc_html__('Footer 1', 'ai-woo-theme'),
        'id'            => 'footer-1',
        'description'   => esc_html__('Footer widget area 1', 'ai-woo-theme'),
        'before_widget' => '<div id="%1$s" class="widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h3 class="widget-title">',
        'after_title'   => '</h3>',
    ));
    
    register_sidebar(array(
        'name'          => esc_html__('Footer 2', 'ai-woo-theme'),
        'id'            => 'footer-2',
        'description'   => esc_html__('Footer widget area 2', 'ai-woo-theme'),
        'before_widget' => '<div id="%1$s" class="widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h3 class="widget-title">',
        'after_title'   => '</h3>',
    ));
    
    register_sidebar(array(
        'name'          => esc_html__('Footer 3', 'ai-woo-theme'),
        'id'            => 'footer-3',
        'description'   => esc_html__('Footer widget area 3', 'ai-woo-theme'),
        'before_widget' => '<div id="%1$s" class="widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h3 class="widget-title">',
        'after_title'   => '</h3>',
    ));
}
add_action('widgets_init', 'ai_woo_widgets_init');

/**
 * Custom Post Types
 */
function ai_woo_custom_post_types() {
    // AI Recommendations Post Type
    register_post_type('ai_recommendations', array(
        'labels' => array(
            'name' => 'AI Recommendations',
            'singular_name' => 'AI Recommendation',
        ),
        'public' => false,
        'show_ui' => true,
        'show_in_menu' => 'ai-woo-theme-options',
        'supports' => array('title', 'editor', 'custom-fields'),
        'capability_type' => 'post',
    ));
    
    // Layout Templates Post Type
    register_post_type('layout_templates', array(
        'labels' => array(
            'name' => 'Layout Templates',
            'singular_name' => 'Layout Template',
        ),
        'public' => false,
        'show_ui' => true,
        'show_in_menu' => 'ai-woo-theme-options',
        'supports' => array('title', 'editor', 'custom-fields'),
        'capability_type' => 'post',
    ));
}
add_action('init', 'ai_woo_custom_post_types');

/**
 * AJAX Handlers
 */
function ai_woo_ajax_get_recommendations() {
    check_ajax_referer('ai_woo_nonce', 'nonce');
    
    $user_id = get_current_user_id();
    $product_id = intval($_POST['product_id']);
    
    // Get AI recommendations
    $recommendations = ai_woo_get_product_recommendations($user_id, $product_id);
    
    wp_send_json_success($recommendations);
}
add_action('wp_ajax_get_recommendations', 'ai_woo_ajax_get_recommendations');
add_action('wp_ajax_nopriv_get_recommendations', 'ai_woo_ajax_get_recommendations');

function ai_woo_ajax_track_user_behavior() {
    check_ajax_referer('ai_woo_nonce', 'nonce');
    
    $user_id = get_current_user_id() ?: session_id();
    $action = sanitize_text_field($_POST['action_type']);
    $data = $_POST['data'];
    
    // Track user behavior for AI learning
    ai_woo_track_user_behavior($user_id, $action, $data);
    
    wp_send_json_success();
}
add_action('wp_ajax_track_user_behavior', 'ai_woo_ajax_track_user_behavior');
add_action('wp_ajax_nopriv_track_user_behavior', 'ai_woo_ajax_track_user_behavior');

function ai_woo_ajax_save_cart_abandonment() {
    check_ajax_referer('ai_woo_nonce', 'nonce');
    
    $cart_data = $_POST['cart_data'];
    $user_email = sanitize_email($_POST['user_email']);
    
    // Save cart abandonment data
    ai_woo_save_abandoned_cart($user_email, $cart_data);
    
    wp_send_json_success();
}
add_action('wp_ajax_save_cart_abandonment', 'ai_woo_ajax_save_cart_abandonment');
add_action('wp_ajax_nopriv_save_cart_abandonment', 'ai_woo_ajax_save_cart_abandonment');

/**
 * Content Width
 */
function ai_woo_content_width() {
    $GLOBALS['content_width'] = apply_filters('ai_woo_content_width', 1200);
}
add_action('after_setup_theme', 'ai_woo_content_width', 0);

/**
 * Custom Body Classes
 */
function ai_woo_body_classes($classes) {
    // Add class for SPA functionality
    $classes[] = 'spa-enabled';
    
    // Add WooCommerce classes
    if (class_exists('WooCommerce')) {
        $classes[] = 'woocommerce-enabled';
        
        if (is_woocommerce() || is_cart() || is_checkout()) {
            $classes[] = 'woocommerce-page';
        }
    }
    
    // Add customizer classes
    if (get_theme_mod('ai_woo_layout_style', 'default') !== 'default') {
        $classes[] = 'layout-' . get_theme_mod('ai_woo_layout_style');
    }
    
    return $classes;
}
add_filter('body_class', 'ai_woo_body_classes');

/**
 * Customizer CSS
 */
function ai_woo_get_customizer_css() {
    $css = '';
    
    // Primary color
    $primary_color = get_theme_mod('ai_woo_primary_color', '#2563eb');
    if ($primary_color !== '#2563eb') {
        $css .= ':root { --primary-color: ' . esc_attr($primary_color) . '; }';
    }
    
    // Secondary color
    $secondary_color = get_theme_mod('ai_woo_secondary_color', '#1e40af');
    if ($secondary_color !== '#1e40af') {
        $css .= ':root { --secondary-color: ' . esc_attr($secondary_color) . '; }';
    }
    
    // Accent color
    $accent_color = get_theme_mod('ai_woo_accent_color', '#f59e0b');
    if ($accent_color !== '#f59e0b') {
        $css .= ':root { --accent-color: ' . esc_attr($accent_color) . '; }';
    }
    
    // Custom CSS
    $custom_css = get_theme_mod('ai_woo_custom_css', '');
    if (!empty($custom_css)) {
        $css .= $custom_css;
    }
    
    return $css;
}

/**
 * SEO Meta Tags
 */
function ai_woo_seo_meta_tags() {
    if (is_front_page()) {
        $description = get_theme_mod('ai_woo_site_description', get_bloginfo('description'));
        echo '<meta name="description" content="' . esc_attr($description) . '">' . "\n";
    } elseif (is_single() || is_page()) {
        $excerpt = get_the_excerpt();
        if ($excerpt) {
            echo '<meta name="description" content="' . esc_attr($excerpt) . '">' . "\n";
        }
    }
    
    // Open Graph tags
    echo '<meta property="og:site_name" content="' . esc_attr(get_bloginfo('name')) . '">' . "\n";
    echo '<meta property="og:type" content="website">' . "\n";
    echo '<meta property="og:url" content="' . esc_url(get_permalink()) . '">' . "\n";
    
    if (is_single() || is_page()) {
        echo '<meta property="og:title" content="' . esc_attr(get_the_title()) . '">' . "\n";
        if (has_post_thumbnail()) {
            $image = wp_get_attachment_image_src(get_post_thumbnail_id(), 'large');
            echo '<meta property="og:image" content="' . esc_url($image[0]) . '">' . "\n";
        }
    }
}
add_action('wp_head', 'ai_woo_seo_meta_tags');

/**
 * Performance Optimizations
 */
function ai_woo_performance_optimizations() {
    // Remove unnecessary WordPress features
    remove_action('wp_head', 'wp_generator');
    remove_action('wp_head', 'wlwmanifest_link');
    remove_action('wp_head', 'rsd_link');
    remove_action('wp_head', 'wp_shortlink_wp_head');
    
    // Disable emojis
    remove_action('wp_head', 'print_emoji_detection_script', 7);
    remove_action('wp_print_styles', 'print_emoji_styles');
    remove_action('admin_print_scripts', 'print_emoji_detection_script');
    remove_action('admin_print_styles', 'print_emoji_styles');
}
add_action('init', 'ai_woo_performance_optimizations');

/**
 * Database Tables Creation
 */
function ai_woo_create_tables() {
    global $wpdb;
    
    $charset_collate = $wpdb->get_charset_collate();
    
    // User behavior tracking table
    $table_name = $wpdb->prefix . 'ai_woo_user_behavior';
    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        user_id varchar(255) NOT NULL,
        action_type varchar(100) NOT NULL,
        product_id int(11),
        data longtext,
        timestamp datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY user_id (user_id),
        KEY action_type (action_type)
    ) $charset_collate;";
    
    // Cart abandonment table
    $table_name2 = $wpdb->prefix . 'ai_woo_cart_abandonment';
    $sql2 = "CREATE TABLE $table_name2 (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        user_email varchar(255) NOT NULL,
        cart_data longtext NOT NULL,
        abandoned_at datetime DEFAULT CURRENT_TIMESTAMP,
        recovered tinyint(1) DEFAULT 0,
        recovery_email_sent tinyint(1) DEFAULT 0,
        PRIMARY KEY (id),
        KEY user_email (user_email)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
    dbDelta($sql2);
}
register_activation_hook(__FILE__, 'ai_woo_create_tables');

/**
 * Theme Activation
 */
function ai_woo_theme_activation() {
    // Create database tables
    ai_woo_create_tables();
    
    // Set default customizer options
    set_theme_mod('ai_woo_primary_color', '#2563eb');
    set_theme_mod('ai_woo_secondary_color', '#1e40af');
    set_theme_mod('ai_woo_accent_color', '#f59e0b');
    set_theme_mod('ai_woo_layout_style', 'default');
    set_theme_mod('ai_woo_enable_ai', true);
    set_theme_mod('ai_woo_enable_cart_recovery', true);
}
add_action('after_switch_theme', 'ai_woo_theme_activation');