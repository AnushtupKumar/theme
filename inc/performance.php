<?php
/**
 * Performance Optimization Functions
 *
 * @package AI_WooCommerce_Theme
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Enable lazy loading for images
 */
function ai_woo_lazy_load_images($content) {
    if (is_admin() || is_feed() || is_preview()) {
        return $content;
    }
    
    // Add loading="lazy" to images that don't already have it
    $content = preg_replace('/<img((?![^>]*loading=[\'"])[^>]*)>/i', '<img$1 loading="lazy">', $content);
    
    return $content;
}
add_filter('the_content', 'ai_woo_lazy_load_images');
add_filter('post_thumbnail_html', 'ai_woo_lazy_load_images');
add_filter('woocommerce_single_product_image_thumbnail_html', 'ai_woo_lazy_load_images');

/**
 * Optimize script loading
 */
function ai_woo_optimize_scripts() {
    if (!is_admin()) {
        // Move jQuery to footer
        wp_scripts()->add_data('jquery', 'group', 1);
        wp_scripts()->add_data('jquery-core', 'group', 1);
        wp_scripts()->add_data('jquery-migrate', 'group', 1);
    }
}
add_action('wp_enqueue_scripts', 'ai_woo_optimize_scripts', 999);

/**
 * Add defer/async to scripts
 */
function ai_woo_defer_scripts($tag, $handle, $src) {
    // List of scripts to defer
    $defer_scripts = array(
        'ai-woo-main',
        'ai-woo-ai-features',
        'ai-woo-cart-recovery'
    );
    
    if (in_array($handle, $defer_scripts)) {
        return str_replace(' src', ' defer src', $tag);
    }
    
    return $tag;
}
add_filter('script_loader_tag', 'ai_woo_defer_scripts', 10, 3);

/**
 * Preload critical resources
 */
function ai_woo_preload_resources() {
    // Preload fonts
    echo '<link rel="preload" href="' . get_template_directory_uri() . '/assets/fonts/main-font.woff2" as="font" type="font/woff2" crossorigin>' . "\n";
    
    // Preload critical CSS
    echo '<link rel="preload" href="' . get_template_directory_uri() . '/style.css" as="style">' . "\n";
    
    // DNS prefetch for external resources
    echo '<link rel="dns-prefetch" href="//fonts.googleapis.com">' . "\n";
    echo '<link rel="dns-prefetch" href="//ajax.googleapis.com">' . "\n";
}
add_action('wp_head', 'ai_woo_preload_resources', 1);

/**
 * Optimize database queries
 */
function ai_woo_optimize_queries($query) {
    if (!is_admin() && $query->is_main_query()) {
        // Limit post revisions
        if ($query->is_single() || $query->is_page()) {
            $query->set('no_found_rows', true);
        }
        
        // Optimize archive queries
        if ($query->is_archive() || $query->is_search()) {
            $query->set('no_found_rows', true);
            $query->set('update_post_meta_cache', false);
            $query->set('update_post_term_cache', false);
        }
    }
}
add_action('pre_get_posts', 'ai_woo_optimize_queries');

/**
 * Enable browser caching headers
 */
function ai_woo_browser_caching() {
    if (!is_admin()) {
        header('Cache-Control: max-age=31536000, public');
        header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 31536000) . ' GMT');
    }
}
add_action('send_headers', 'ai_woo_browser_caching');

/**
 * Minify HTML output
 */
function ai_woo_minify_html($buffer) {
    if (!is_admin()) {
        // Remove HTML comments
        $buffer = preg_replace('/<!--(?!<!)[^\[>].*?-->/s', '', $buffer);
        
        // Remove unnecessary whitespace
        $buffer = preg_replace('/\s+/', ' ', $buffer);
        
        // Remove whitespace between tags
        $buffer = preg_replace('/>\s+</', '><', $buffer);
    }
    
    return $buffer;
}

/**
 * Start output buffering for HTML minification
 */
function ai_woo_start_minify() {
    if (!is_admin() && !is_feed()) {
        ob_start('ai_woo_minify_html');
    }
}
add_action('init', 'ai_woo_start_minify');

/**
 * Disable unnecessary features
 */
function ai_woo_disable_features() {
    // Disable emojis
    remove_action('wp_head', 'print_emoji_detection_script', 7);
    remove_action('admin_print_scripts', 'print_emoji_detection_script');
    remove_action('wp_print_styles', 'print_emoji_styles');
    remove_action('admin_print_styles', 'print_emoji_styles');
    remove_filter('the_content_feed', 'wp_staticize_emoji');
    remove_filter('comment_text_rss', 'wp_staticize_emoji');
    remove_filter('wp_mail', 'wp_staticize_emoji_for_email');
    
    // Disable embeds
    remove_action('wp_head', 'wp_oembed_add_discovery_links');
    remove_action('wp_head', 'wp_oembed_add_host_js');
}
add_action('init', 'ai_woo_disable_features');