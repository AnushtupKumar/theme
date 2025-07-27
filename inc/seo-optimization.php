<?php
/**
 * SEO Optimization Functions
 *
 * @package AI_WooCommerce_Theme
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Add meta tags for SEO
 */
function ai_woo_add_meta_tags() {
    if (is_single() || is_page()) {
        global $post;
        $description = get_post_meta($post->ID, '_ai_woo_meta_description', true);
        
        if (empty($description)) {
            $description = wp_trim_words($post->post_excerpt ? $post->post_excerpt : $post->post_content, 20);
        }
        
        if ($description) {
            echo '<meta name="description" content="' . esc_attr($description) . '">' . "\n";
        }
    }
    
    // Add Open Graph tags
    if (is_single() || is_page()) {
        ?>
        <meta property="og:title" content="<?php echo esc_attr(get_the_title()); ?>" />
        <meta property="og:type" content="<?php echo is_single() ? 'article' : 'website'; ?>" />
        <meta property="og:url" content="<?php echo esc_url(get_permalink()); ?>" />
        <?php if (has_post_thumbnail()) : ?>
            <meta property="og:image" content="<?php echo esc_url(get_the_post_thumbnail_url(null, 'large')); ?>" />
        <?php endif; ?>
        <?php
    }
}
add_action('wp_head', 'ai_woo_add_meta_tags', 1);

/**
 * Add schema markup for products
 */
function ai_woo_product_schema() {
    if (!is_product()) {
        return;
    }
    
    global $product;
    
    $schema = array(
        '@context' => 'https://schema.org/',
        '@type' => 'Product',
        'name' => $product->get_name(),
        'image' => wp_get_attachment_url($product->get_image_id()),
        'description' => $product->get_description(),
        'sku' => $product->get_sku(),
        'offers' => array(
            '@type' => 'Offer',
            'url' => get_permalink(),
            'priceCurrency' => get_woocommerce_currency(),
            'price' => $product->get_price(),
            'availability' => $product->is_in_stock() ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock'
        )
    );
    
    if ($product->get_average_rating() > 0) {
        $schema['aggregateRating'] = array(
            '@type' => 'AggregateRating',
            'ratingValue' => $product->get_average_rating(),
            'reviewCount' => $product->get_review_count()
        );
    }
    
    echo '<script type="application/ld+json">' . wp_json_encode($schema) . '</script>' . "\n";
}
add_action('wp_head', 'ai_woo_product_schema');

/**
 * Optimize title tags
 */
function ai_woo_optimize_title($title) {
    if (is_home() || is_front_page()) {
        return get_bloginfo('name') . ' | ' . get_bloginfo('description');
    }
    
    return $title;
}
add_filter('pre_get_document_title', 'ai_woo_optimize_title');

/**
 * Add breadcrumb schema
 */
function ai_woo_breadcrumb_schema() {
    if (!function_exists('woocommerce_breadcrumb') || is_home() || is_front_page()) {
        return;
    }
    
    $breadcrumb = array(
        '@context' => 'https://schema.org',
        '@type' => 'BreadcrumbList',
        'itemListElement' => array()
    );
    
    // Add implementation based on WooCommerce breadcrumbs
    echo '<script type="application/ld+json">' . wp_json_encode($breadcrumb) . '</script>' . "\n";
}
add_action('wp_head', 'ai_woo_breadcrumb_schema');

/**
 * Clean up WordPress head
 */
function ai_woo_clean_head() {
    remove_action('wp_head', 'wp_generator');
    remove_action('wp_head', 'wlwmanifest_link');
    remove_action('wp_head', 'rsd_link');
    remove_action('wp_head', 'wp_shortlink_wp_head');
    remove_action('wp_head', 'adjacent_posts_rel_link_wp_head');
}
add_action('init', 'ai_woo_clean_head');