<?php
/**
 * WooCommerce Functions and Integration
 *
 * @package AI_Woo_Theme
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Remove default WooCommerce styles
 */
add_filter('woocommerce_enqueue_styles', '__return_empty_array');

/**
 * Custom WooCommerce template overrides
 */
function ai_woo_woocommerce_template_path() {
    return 'woocommerce/';
}
add_filter('woocommerce_template_path', 'ai_woo_woocommerce_template_path');

/**
 * Customize WooCommerce product loop
 */
function ai_woo_custom_product_loop() {
    remove_action('woocommerce_before_shop_loop_item', 'woocommerce_template_loop_product_link_open', 10);
    remove_action('woocommerce_after_shop_loop_item', 'woocommerce_template_loop_product_link_close', 5);
    remove_action('woocommerce_shop_loop_item_title', 'woocommerce_template_loop_product_title', 10);
    remove_action('woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_rating', 5);
    remove_action('woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_price', 10);
    remove_action('woocommerce_after_shop_loop_item', 'woocommerce_template_loop_add_to_cart', 10);
    
    // Add custom product card template
    add_action('woocommerce_before_shop_loop_item', 'ai_woo_product_card_open', 10);
    add_action('woocommerce_before_shop_loop_item_title', 'ai_woo_product_image_wrapper_open', 5);
    add_action('woocommerce_before_shop_loop_item_title', 'ai_woo_product_badges', 8);
    add_action('woocommerce_before_shop_loop_item_title', 'ai_woo_product_quick_actions', 12);
    add_action('woocommerce_before_shop_loop_item_title', 'ai_woo_product_image_wrapper_close', 15);
    add_action('woocommerce_shop_loop_item_title', 'ai_woo_product_info_wrapper_open', 5);
    add_action('woocommerce_shop_loop_item_title', 'ai_woo_product_title', 10);
    add_action('woocommerce_after_shop_loop_item_title', 'ai_woo_product_rating', 5);
    add_action('woocommerce_after_shop_loop_item_title', 'ai_woo_product_price', 10);
    add_action('woocommerce_after_shop_loop_item_title', 'ai_woo_product_excerpt', 15);
    add_action('woocommerce_after_shop_loop_item', 'ai_woo_product_actions', 5);
    add_action('woocommerce_after_shop_loop_item', 'ai_woo_product_info_wrapper_close', 8);
    add_action('woocommerce_after_shop_loop_item', 'ai_woo_product_card_close', 15);
}
add_action('init', 'ai_woo_custom_product_loop');

/**
 * Product card wrapper open
 */
function ai_woo_product_card_open() {
    global $product;
    $product_id = $product->get_id();
    $classes = array('product-card', 'card');
    
    if ($product->is_on_sale()) {
        $classes[] = 'on-sale';
    }
    
    if (!$product->is_in_stock()) {
        $classes[] = 'out-of-stock';
    }
    
    echo '<div class="' . esc_attr(implode(' ', $classes)) . '" data-product-id="' . esc_attr($product_id) . '">';
}

/**
 * Product card wrapper close
 */
function ai_woo_product_card_close() {
    echo '</div>';
}

/**
 * Product image wrapper open
 */
function ai_woo_product_image_wrapper_open() {
    echo '<div class="product-image-wrapper">';
    echo '<a href="' . esc_url(get_permalink()) . '" class="product-image-link">';
}

/**
 * Product image wrapper close
 */
function ai_woo_product_image_wrapper_close() {
    echo '</a>';
    echo '</div>';
}

/**
 * Product info wrapper open
 */
function ai_woo_product_info_wrapper_open() {
    echo '<div class="product-info">';
}

/**
 * Product info wrapper close
 */
function ai_woo_product_info_wrapper_close() {
    echo '</div>';
}

/**
 * Custom product badges
 */
function ai_woo_product_badges() {
    global $product;
    
    echo '<div class="product-badges">';
    
    if ($product->is_on_sale()) {
        $percentage = '';
        if ($product->get_regular_price() && $product->get_sale_price()) {
            $percentage = round((($product->get_regular_price() - $product->get_sale_price()) / $product->get_regular_price()) * 100);
            $percentage = '-' . $percentage . '%';
        }
        
        echo '<span class="badge sale-badge">' . ($percentage ? $percentage : esc_html__('Sale', 'ai-woo-theme')) . '</span>';
    }
    
    if (!$product->is_in_stock()) {
        echo '<span class="badge out-of-stock-badge">' . esc_html__('Out of Stock', 'ai-woo-theme') . '</span>';
    }
    
    if ($product->is_featured()) {
        echo '<span class="badge featured-badge">' . esc_html__('Featured', 'ai-woo-theme') . '</span>';
    }
    
    // Custom AI-powered badge
    if (ai_woo_is_recommended_product($product->get_id())) {
        echo '<span class="badge ai-recommended-badge"><i class="fas fa-robot"></i> ' . esc_html__('AI Pick', 'ai-woo-theme') . '</span>';
    }
    
    echo '</div>';
}

/**
 * Product quick actions
 */
function ai_woo_product_quick_actions() {
    global $product;
    
    echo '<div class="product-quick-actions">';
    
    // Wishlist button
    echo '<button class="quick-action wishlist-toggle" data-product-id="' . esc_attr($product->get_id()) . '" data-tooltip="' . esc_attr__('Add to Wishlist', 'ai-woo-theme') . '">';
    echo '<i class="far fa-heart"></i>';
    echo '</button>';
    
    // Quick view button
    echo '<button class="quick-action quick-view-btn" data-product-id="' . esc_attr($product->get_id()) . '" data-tooltip="' . esc_attr__('Quick View', 'ai-woo-theme') . '">';
    echo '<i class="fas fa-eye"></i>';
    echo '</button>';
    
    // Compare button
    echo '<button class="quick-action compare-btn" data-product-id="' . esc_attr($product->get_id()) . '" data-tooltip="' . esc_attr__('Compare', 'ai-woo-theme') . '">';
    echo '<i class="fas fa-balance-scale"></i>';
    echo '</button>';
    
    echo '</div>';
}

/**
 * Custom product title
 */
function ai_woo_product_title() {
    echo '<h3 class="product-title">';
    echo '<a href="' . esc_url(get_permalink()) . '">' . get_the_title() . '</a>';
    echo '</h3>';
}

/**
 * Custom product rating
 */
function ai_woo_product_rating() {
    global $product;
    
    if ($product->get_average_rating()) {
        $rating = $product->get_average_rating();
        $review_count = $product->get_review_count();
        
        echo '<div class="product-rating">';
        echo '<div class="stars">';
        
        for ($i = 1; $i <= 5; $i++) {
            if ($i <= $rating) {
                echo '<i class="fas fa-star"></i>';
            } elseif ($i - 0.5 <= $rating) {
                echo '<i class="fas fa-star-half-alt"></i>';
            } else {
                echo '<i class="far fa-star"></i>';
            }
        }
        
        echo '</div>';
        echo '<span class="rating-count">(' . $review_count . ')</span>';
        echo '</div>';
    }
}

/**
 * Custom product price
 */
function ai_woo_product_price() {
    global $product;
    
    echo '<div class="product-price">';
    echo $product->get_price_html();
    echo '</div>';
}

/**
 * Product excerpt
 */
function ai_woo_product_excerpt() {
    global $product;
    
    $excerpt = $product->get_short_description();
    if ($excerpt) {
        echo '<div class="product-excerpt">';
        echo wp_trim_words($excerpt, 15);
        echo '</div>';
    }
}

/**
 * Product actions
 */
function ai_woo_product_actions() {
    global $product;
    
    echo '<div class="product-actions">';
    
    if ($product->is_purchasable() && $product->is_in_stock()) {
        // Add to cart button
        $button_text = $product->is_type('simple') ? __('Add to Cart', 'ai-woo-theme') : __('Select Options', 'ai-woo-theme');
        $button_class = 'btn btn-primary add-to-cart-btn';
        
        if ($product->is_type('simple')) {
            $button_class .= ' ajax-add-to-cart';
        }
        
        echo '<a href="' . esc_url($product->add_to_cart_url()) . '" class="' . esc_attr($button_class) . '" data-product-id="' . esc_attr($product->get_id()) . '">';
        echo esc_html($button_text);
        echo '</a>';
    } else {
        echo '<span class="btn btn-secondary disabled">' . esc_html__('Read More', 'ai-woo-theme') . '</span>';
    }
    
    echo '</div>';
}

/**
 * Check if product is AI recommended
 */
function ai_woo_is_recommended_product($product_id) {
    $user_id = get_current_user_id() ?: session_id();
    $recommendations = ai_woo_get_product_recommendations($user_id, null, 10);
    
    foreach ($recommendations as $recommended_product) {
        if ($recommended_product->get_id() == $product_id) {
            return true;
        }
    }
    
    return false;
}

/**
 * Customize single product page
 */
function ai_woo_single_product_customizations() {
    // Remove default hooks
    remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_title', 5);
    remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_rating', 10);
    remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_price', 10);
    remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_excerpt', 20);
    remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30);
    remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_meta', 40);
    
    // Add custom hooks
    add_action('woocommerce_single_product_summary', 'ai_woo_single_product_title', 5);
    add_action('woocommerce_single_product_summary', 'ai_woo_single_product_rating_price', 10);
    add_action('woocommerce_single_product_summary', 'ai_woo_single_product_excerpt', 20);
    add_action('woocommerce_single_product_summary', 'ai_woo_single_product_add_to_cart', 30);
    add_action('woocommerce_single_product_summary', 'ai_woo_single_product_meta', 40);
    add_action('woocommerce_single_product_summary', 'ai_woo_single_product_social_share', 50);
    
    // Add AI recommendations
    add_action('woocommerce_output_related_products_args', 'ai_woo_single_product_recommendations');
}
add_action('init', 'ai_woo_single_product_customizations');

/**
 * Single product title
 */
function ai_woo_single_product_title() {
    echo '<h1 class="product-title">' . get_the_title() . '</h1>';
}

/**
 * Single product rating and price
 */
function ai_woo_single_product_rating_price() {
    global $product;
    
    echo '<div class="product-rating-price">';
    
    // Rating
    if ($product->get_average_rating()) {
        ai_woo_product_rating();
    }
    
    // Price
    echo '<div class="product-price single-price">';
    echo $product->get_price_html();
    echo '</div>';
    
    echo '</div>';
}

/**
 * Single product excerpt
 */
function ai_woo_single_product_excerpt() {
    global $product;
    
    $excerpt = $product->get_short_description();
    if ($excerpt) {
        echo '<div class="product-excerpt single-excerpt">';
        echo $excerpt;
        echo '</div>';
    }
}

/**
 * Single product add to cart
 */
function ai_woo_single_product_add_to_cart() {
    woocommerce_template_single_add_to_cart();
}

/**
 * Single product meta
 */
function ai_woo_single_product_meta() {
    global $product;
    
    echo '<div class="product-meta">';
    
    // SKU
    if ($product->get_sku()) {
        echo '<span class="sku-wrapper">';
        echo '<strong>' . esc_html__('SKU:', 'ai-woo-theme') . '</strong> ';
        echo '<span class="sku">' . esc_html($product->get_sku()) . '</span>';
        echo '</span>';
    }
    
    // Categories
    $categories = get_the_terms($product->get_id(), 'product_cat');
    if ($categories && !is_wp_error($categories)) {
        echo '<span class="categories-wrapper">';
        echo '<strong>' . esc_html__('Categories:', 'ai-woo-theme') . '</strong> ';
        $category_links = array();
        foreach ($categories as $category) {
            $category_links[] = '<a href="' . esc_url(get_term_link($category)) . '">' . esc_html($category->name) . '</a>';
        }
        echo implode(', ', $category_links);
        echo '</span>';
    }
    
    // Tags
    $tags = get_the_terms($product->get_id(), 'product_tag');
    if ($tags && !is_wp_error($tags)) {
        echo '<span class="tags-wrapper">';
        echo '<strong>' . esc_html__('Tags:', 'ai-woo-theme') . '</strong> ';
        $tag_links = array();
        foreach ($tags as $tag) {
            $tag_links[] = '<a href="' . esc_url(get_term_link($tag)) . '">' . esc_html($tag->name) . '</a>';
        }
        echo implode(', ', $tag_links);
        echo '</span>';
    }
    
    echo '</div>';
}

/**
 * Single product social share
 */
function ai_woo_single_product_social_share() {
    $product_url = get_permalink();
    $product_title = get_the_title();
    $product_image = wp_get_attachment_image_src(get_post_thumbnail_id(), 'large')[0];
    
    echo '<div class="product-social-share">';
    echo '<h4>' . esc_html__('Share this product:', 'ai-woo-theme') . '</h4>';
    echo '<div class="social-share-buttons">';
    
    // Facebook
    $facebook_url = 'https://www.facebook.com/sharer/sharer.php?u=' . urlencode($product_url);
    echo '<a href="' . esc_url($facebook_url) . '" target="_blank" class="social-share-btn facebook" data-tooltip="' . esc_attr__('Share on Facebook', 'ai-woo-theme') . '">';
    echo '<i class="fab fa-facebook-f"></i>';
    echo '</a>';
    
    // Twitter
    $twitter_url = 'https://twitter.com/intent/tweet?url=' . urlencode($product_url) . '&text=' . urlencode($product_title);
    echo '<a href="' . esc_url($twitter_url) . '" target="_blank" class="social-share-btn twitter" data-tooltip="' . esc_attr__('Share on Twitter', 'ai-woo-theme') . '">';
    echo '<i class="fab fa-twitter"></i>';
    echo '</a>';
    
    // Pinterest
    if ($product_image) {
        $pinterest_url = 'https://pinterest.com/pin/create/button/?url=' . urlencode($product_url) . '&media=' . urlencode($product_image) . '&description=' . urlencode($product_title);
        echo '<a href="' . esc_url($pinterest_url) . '" target="_blank" class="social-share-btn pinterest" data-tooltip="' . esc_attr__('Pin on Pinterest', 'ai-woo-theme') . '">';
        echo '<i class="fab fa-pinterest"></i>';
        echo '</a>';
    }
    
    // WhatsApp
    $whatsapp_url = 'https://wa.me/?text=' . urlencode($product_title . ' ' . $product_url);
    echo '<a href="' . esc_url($whatsapp_url) . '" target="_blank" class="social-share-btn whatsapp" data-tooltip="' . esc_attr__('Share on WhatsApp', 'ai-woo-theme') . '">';
    echo '<i class="fab fa-whatsapp"></i>';
    echo '</a>';
    
    echo '</div>';
    echo '</div>';
}

/**
 * AI-powered product recommendations
 */
function ai_woo_single_product_recommendations() {
    global $product;
    
    $user_id = get_current_user_id() ?: session_id();
    $recommendations = ai_woo_get_product_recommendations($user_id, $product->get_id(), 4);
    
    if (!empty($recommendations)) {
        echo '<section class="ai-product-recommendations">';
        echo '<div class="container">';
        echo '<div class="ai-badge">';
        echo '<i class="fas fa-robot"></i>';
        echo esc_html__('AI Powered', 'ai-woo-theme');
        echo '</div>';
        echo '<h2>' . esc_html__('Recommended for You', 'ai-woo-theme') . '</h2>';
        echo '<div class="woocommerce-products">';
        
        foreach ($recommendations as $recommended_product) {
            $GLOBALS['product'] = $recommended_product;
            wc_get_template_part('content', 'product');
        }
        
        echo '</div>';
        echo '</div>';
        echo '</section>';
        
        // Restore global product
        $GLOBALS['product'] = $product;
    }
}

/**
 * Customize cart page
 */
function ai_woo_cart_customizations() {
    // Add cross-sells based on AI recommendations
    remove_action('woocommerce_cart_collaterals', 'woocommerce_cross_sell_display');
    add_action('woocommerce_cart_collaterals', 'ai_woo_cart_cross_sells', 10);
}
add_action('init', 'ai_woo_cart_customizations');

/**
 * AI-powered cart cross-sells
 */
function ai_woo_cart_cross_sells() {
    $user_id = get_current_user_id() ?: session_id();
    $cart_items = WC()->cart->get_cart();
    
    if (empty($cart_items)) {
        return;
    }
    
    // Get product IDs from cart
    $cart_product_ids = array();
    foreach ($cart_items as $cart_item) {
        $cart_product_ids[] = $cart_item['product_id'];
    }
    
    // Get AI recommendations based on cart contents
    $recommendations = ai_woo_get_product_recommendations($user_id, $cart_product_ids[0], 4);
    
    // Filter out products already in cart
    $filtered_recommendations = array();
    foreach ($recommendations as $recommended_product) {
        if (!in_array($recommended_product->get_id(), $cart_product_ids)) {
            $filtered_recommendations[] = $recommended_product;
        }
    }
    
    if (!empty($filtered_recommendations)) {
        echo '<div class="cart-cross-sells">';
        echo '<div class="ai-badge">';
        echo '<i class="fas fa-robot"></i>';
        echo esc_html__('AI Suggested', 'ai-woo-theme');
        echo '</div>';
        echo '<h2>' . esc_html__('You might also like', 'ai-woo-theme') . '</h2>';
        echo '<div class="woocommerce-products cross-sells">';
        
        foreach (array_slice($filtered_recommendations, 0, 4) as $recommended_product) {
            $GLOBALS['product'] = $recommended_product;
            wc_get_template_part('content', 'product');
        }
        
        echo '</div>';
        echo '</div>';
    }
}

/**
 * AJAX handler for quick view
 */
function ai_woo_ajax_product_quick_view() {
    check_ajax_referer('ai_woo_nonce', 'nonce');
    
    $product_id = intval($_POST['product_id']);
    $product = wc_get_product($product_id);
    
    if (!$product) {
        wp_send_json_error('Product not found');
    }
    
    // Set global product
    $GLOBALS['product'] = $product;
    
    ob_start();
    ?>
    <div class="quick-view-content">
        <button class="quick-view-close" aria-label="<?php esc_attr_e('Close', 'ai-woo-theme'); ?>">
            <i class="fas fa-times"></i>
        </button>
        
        <div class="quick-view-product">
            <div class="quick-view-images">
                <?php
                $attachment_ids = $product->get_gallery_image_ids();
                if ($attachment_ids) {
                    echo '<div class="product-gallery">';
                    foreach ($attachment_ids as $attachment_id) {
                        echo wp_get_attachment_image($attachment_id, 'medium', false, array('class' => 'gallery-image'));
                    }
                    echo '</div>';
                } else {
                    echo wp_get_attachment_image($product->get_image_id(), 'medium');
                }
                ?>
            </div>
            
            <div class="quick-view-summary">
                <h2 class="product-title"><?php echo $product->get_name(); ?></h2>
                
                <?php if ($product->get_average_rating()): ?>
                    <div class="product-rating">
                        <?php ai_woo_product_rating(); ?>
                    </div>
                <?php endif; ?>
                
                <div class="product-price">
                    <?php echo $product->get_price_html(); ?>
                </div>
                
                <?php if ($product->get_short_description()): ?>
                    <div class="product-excerpt">
                        <?php echo $product->get_short_description(); ?>
                    </div>
                <?php endif; ?>
                
                <div class="product-actions">
                    <?php if ($product->is_purchasable() && $product->is_in_stock()): ?>
                        <a href="<?php echo esc_url($product->add_to_cart_url()); ?>" 
                           class="btn btn-primary add-to-cart-btn ajax-add-to-cart" 
                           data-product-id="<?php echo esc_attr($product->get_id()); ?>">
                            <?php echo $product->is_type('simple') ? __('Add to Cart', 'ai-woo-theme') : __('Select Options', 'ai-woo-theme'); ?>
                        </a>
                    <?php endif; ?>
                    
                    <a href="<?php echo esc_url(get_permalink($product->get_id())); ?>" class="btn btn-secondary">
                        <?php esc_html_e('View Details', 'ai-woo-theme'); ?>
                    </a>
                </div>
            </div>
        </div>
    </div>
    <?php
    
    $content = ob_get_clean();
    wp_send_json_success($content);
}
add_action('wp_ajax_get_product_quick_view', 'ai_woo_ajax_product_quick_view');
add_action('wp_ajax_nopriv_get_product_quick_view', 'ai_woo_ajax_product_quick_view');

/**
 * Customize WooCommerce messages
 */
function ai_woo_custom_woocommerce_messages() {
    // Custom success messages
    add_filter('wc_add_to_cart_message_html', 'ai_woo_custom_add_to_cart_message', 10, 3);
}
add_action('init', 'ai_woo_custom_woocommerce_messages');

/**
 * Custom add to cart message
 */
function ai_woo_custom_add_to_cart_message($message, $products, $show_qty) {
    $titles = array();
    $count = 0;
    
    foreach ($products as $product_id => $qty) {
        $titles[] = ($qty > 1 ? absint($qty) . ' &times; ' : '') . sprintf('"%s"', strip_tags(get_the_title($product_id)));
        $count += $qty;
    }
    
    $titles = array_filter($titles);
    $added_text = sprintf(_n('%s has been added to your cart.', '%s have been added to your cart.', $count, 'ai-woo-theme'), wc_format_list_of_items($titles));
    
    $message = sprintf(
        '<div class="wc-message-content">%s <a href="%s" class="btn btn-sm btn-secondary">%s</a></div>',
        esc_html($added_text),
        esc_url(wc_get_cart_url()),
        esc_html__('View Cart', 'ai-woo-theme')
    );
    
    return $message;
}

/**
 * Add structured data for products
 */
function ai_woo_product_structured_data() {
    if (!is_product()) {
        return;
    }
    
    global $product;
    
    $structured_data = array(
        '@context' => 'https://schema.org',
        '@type' => 'Product',
        'name' => $product->get_name(),
        'description' => wp_strip_all_tags($product->get_short_description() ?: $product->get_description()),
        'sku' => $product->get_sku(),
        'offers' => array(
            '@type' => 'Offer',
            'price' => $product->get_price(),
            'priceCurrency' => get_woocommerce_currency(),
            'availability' => $product->is_in_stock() ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
            'url' => get_permalink($product->get_id()),
        ),
    );
    
    if ($product->get_image_id()) {
        $image = wp_get_attachment_image_src($product->get_image_id(), 'large');
        $structured_data['image'] = $image[0];
    }
    
    if ($product->get_average_rating()) {
        $structured_data['aggregateRating'] = array(
            '@type' => 'AggregateRating',
            'ratingValue' => $product->get_average_rating(),
            'reviewCount' => $product->get_review_count(),
        );
    }
    
    echo '<script type="application/ld+json">' . json_encode($structured_data) . '</script>';
}
add_action('wp_head', 'ai_woo_product_structured_data');