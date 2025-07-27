<?php
/**
 * Cart Abandonment Recovery System
 *
 * @package AI_Woo_Theme
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Cart Recovery Class
 */
class AI_Woo_Cart_Recovery {
    
    public function __construct() {
        add_action('wp_footer', array($this, 'add_cart_tracking_script'));
        add_action('wp_ajax_save_abandoned_cart', array($this, 'save_abandoned_cart'));
        add_action('wp_ajax_nopriv_save_abandoned_cart', array($this, 'save_abandoned_cart'));
        add_action('ai_woo_send_recovery_emails', array($this, 'send_recovery_emails'));
        
        // Schedule recovery email cron job
        if (!wp_next_scheduled('ai_woo_send_recovery_emails')) {
            wp_schedule_event(time(), 'hourly', 'ai_woo_send_recovery_emails');
        }
    }
    
    /**
     * Add cart tracking JavaScript
     */
    public function add_cart_tracking_script() {
        if (!get_theme_mod('ai_woo_enable_cart_recovery', true)) {
            return;
        }
        ?>
        <script>
        (function() {
            let cartTracker = {
                lastCartData: null,
                abandonmentTimer: null,
                emailCaptured: false,
                
                init: function() {
                    this.trackCartChanges();
                    this.trackEmailCapture();
                    this.startAbandonmentTimer();
                },
                
                trackCartChanges: function() {
                    // Track when items are added to cart
                    jQuery(document).on('added_to_cart', function(event, fragments, cart_hash, button) {
                        cartTracker.updateCartData();
                        cartTracker.resetAbandonmentTimer();
                    });
                    
                    // Track cart updates
                    jQuery(document).on('updated_wc_div', function() {
                        cartTracker.updateCartData();
                        cartTracker.resetAbandonmentTimer();
                    });
                },
                
                trackEmailCapture: function() {
                    // Capture email from checkout form
                    jQuery(document).on('blur', 'input[name="billing_email"]', function() {
                        const email = jQuery(this).val();
                        if (cartTracker.isValidEmail(email)) {
                            cartTracker.emailCaptured = email;
                            cartTracker.saveAbandonedCart();
                        }
                    });
                    
                    // Capture email from newsletter signup
                    jQuery(document).on('submit', '.newsletter-form', function() {
                        const email = jQuery(this).find('input[type="email"]').val();
                        if (cartTracker.isValidEmail(email)) {
                            cartTracker.emailCaptured = email;
                        }
                    });
                },
                
                updateCartData: function() {
                    jQuery.post(ai_woo_ajax.ajax_url, {
                        action: 'get_cart_contents',
                        nonce: ai_woo_ajax.nonce
                    }, function(response) {
                        if (response.success) {
                            cartTracker.lastCartData = response.data;
                        }
                    });
                },
                
                startAbandonmentTimer: function() {
                    this.abandonmentTimer = setTimeout(function() {
                        if (cartTracker.hasCartItems() && !cartTracker.isOnCheckout()) {
                            cartTracker.showAbandonmentModal();
                        }
                    }, 300000); // 5 minutes
                },
                
                resetAbandonmentTimer: function() {
                    if (this.abandonmentTimer) {
                        clearTimeout(this.abandonmentTimer);
                    }
                    this.startAbandonmentTimer();
                },
                
                hasCartItems: function() {
                    return this.lastCartData && this.lastCartData.cart_count > 0;
                },
                
                isOnCheckout: function() {
                    return window.location.pathname.includes('/checkout/');
                },
                
                showAbandonmentModal: function() {
                    const modal = jQuery('#cart-abandonment-modal');
                    if (modal.length && !this.emailCaptured) {
                        modal.fadeIn();
                    }
                },
                
                saveAbandonedCart: function() {
                    if (!this.emailCaptured || !this.hasCartItems()) {
                        return;
                    }
                    
                    jQuery.post(ai_woo_ajax.ajax_url, {
                        action: 'save_abandoned_cart',
                        nonce: ai_woo_ajax.nonce,
                        user_email: this.emailCaptured,
                        cart_data: JSON.stringify(this.lastCartData)
                    });
                },
                
                isValidEmail: function(email) {
                    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    return re.test(email);
                }
            };
            
            // Initialize when DOM is ready
            jQuery(document).ready(function() {
                cartTracker.init();
                cartTracker.updateCartData();
            });
            
            // Handle abandonment modal
            jQuery(document).on('submit', '#cart-abandonment-form', function(e) {
                e.preventDefault();
                const email = jQuery('#abandonment-email').val();
                if (cartTracker.isValidEmail(email)) {
                    cartTracker.emailCaptured = email;
                    cartTracker.saveAbandonedCart();
                    jQuery('#cart-abandonment-modal').fadeOut();
                    
                    // Show success message
                    alert('<?php esc_html_e("Thank you! We've saved your cart and you'll receive a special discount code via email.", "ai-woo-theme"); ?>');
                }
            });
            
            jQuery(document).on('click', '#cart-modal-close, #cart-modal-dismiss', function() {
                jQuery('#cart-abandonment-modal').fadeOut();
            });
        })();
        </script>
        <?php
    }
    
    /**
     * Save abandoned cart data
     */
    public function save_abandoned_cart() {
        check_ajax_referer('ai_woo_nonce', 'nonce');
        
        $user_email = sanitize_email($_POST['user_email']);
        $cart_data = sanitize_text_field($_POST['cart_data']);
        
        if (empty($user_email) || empty($cart_data)) {
            wp_send_json_error('Invalid data');
        }
        
        ai_woo_save_abandoned_cart($user_email, $cart_data);
        wp_send_json_success();
    }
    
    /**
     * Send recovery emails
     */
    public function send_recovery_emails() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'ai_woo_cart_abandonment';
        
        // Get abandoned carts that haven't been recovered and haven't had emails sent
        $abandoned_carts = $wpdb->get_results(
            "SELECT * FROM {$table_name} 
             WHERE recovered = 0 
             AND recovery_email_sent = 0 
             AND abandoned_at < DATE_SUB(NOW(), INTERVAL 1 HOUR)
             LIMIT 50"
        );
        
        foreach ($abandoned_carts as $cart) {
            $this->send_recovery_email($cart);
            
            // Mark as email sent
            $wpdb->update(
                $table_name,
                array('recovery_email_sent' => 1),
                array('id' => $cart->id),
                array('%d'),
                array('%d')
            );
        }
        
        // Send follow-up emails (24 hours later)
        $follow_up_carts = $wpdb->get_results(
            "SELECT * FROM {$table_name} 
             WHERE recovered = 0 
             AND recovery_email_sent = 1 
             AND abandoned_at < DATE_SUB(NOW(), INTERVAL 24 HOUR)
             AND abandoned_at > DATE_SUB(NOW(), INTERVAL 25 HOUR)
             LIMIT 25"
        );
        
        foreach ($follow_up_carts as $cart) {
            $this->send_follow_up_email($cart);
        }
    }
    
    /**
     * Send recovery email
     */
    private function send_recovery_email($cart) {
        $cart_data = json_decode($cart->cart_data, true);
        $discount_code = $this->generate_discount_code($cart->user_email);
        
        $subject = sprintf(__('Complete your purchase and save 10%% - %s', 'ai-woo-theme'), get_bloginfo('name'));
        
        $message = $this->get_recovery_email_template($cart_data, $discount_code);
        
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . get_bloginfo('name') . ' <' . get_option('admin_email') . '>'
        );
        
        wp_mail($cart->user_email, $subject, $message, $headers);
        
        // Track email sent
        ai_woo_track_user_behavior($cart->user_email, 'recovery_email_sent', array(
            'cart_id' => $cart->id,
            'discount_code' => $discount_code,
        ));
    }
    
    /**
     * Send follow-up email
     */
    private function send_follow_up_email($cart) {
        $cart_data = json_decode($cart->cart_data, true);
        $discount_code = $this->generate_discount_code($cart->user_email, 15); // 15% discount
        
        $subject = sprintf(__('Last chance: 15%% off your cart - %s', 'ai-woo-theme'), get_bloginfo('name'));
        
        $message = $this->get_follow_up_email_template($cart_data, $discount_code);
        
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . get_bloginfo('name') . ' <' . get_option('admin_email') . '>'
        );
        
        wp_mail($cart->user_email, $subject, $message, $headers);
    }
    
    /**
     * Generate discount code
     */
    private function generate_discount_code($email, $discount_percent = 10) {
        $code = 'CART' . strtoupper(substr(md5($email . time()), 0, 6));
        
        // Create WooCommerce coupon
        $coupon = array(
            'post_title' => $code,
            'post_content' => '',
            'post_status' => 'publish',
            'post_author' => 1,
            'post_type' => 'shop_coupon'
        );
        
        $coupon_id = wp_insert_post($coupon);
        
        // Set coupon meta
        update_post_meta($coupon_id, 'discount_type', 'percent');
        update_post_meta($coupon_id, 'coupon_amount', $discount_percent);
        update_post_meta($coupon_id, 'individual_use', 'yes');
        update_post_meta($coupon_id, 'usage_limit', 1);
        update_post_meta($coupon_id, 'expiry_date', date('Y-m-d', strtotime('+7 days')));
        update_post_meta($coupon_id, 'customer_email', array($email));
        
        return $code;
    }
    
    /**
     * Get recovery email template
     */
    private function get_recovery_email_template($cart_data, $discount_code) {
        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title><?php esc_html_e('Complete Your Purchase', 'ai-woo-theme'); ?></title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #2563eb; color: white; padding: 20px; text-align: center; }
                .content { padding: 30px 20px; }
                .cart-items { border: 1px solid #ddd; margin: 20px 0; }
                .cart-item { padding: 15px; border-bottom: 1px solid #eee; display: flex; align-items: center; }
                .cart-item:last-child { border-bottom: none; }
                .item-image { width: 80px; height: 80px; margin-right: 15px; }
                .item-details h3 { margin: 0 0 5px 0; }
                .discount-box { background: #f0f9ff; border: 2px solid #2563eb; padding: 20px; text-align: center; margin: 20px 0; }
                .discount-code { font-size: 24px; font-weight: bold; color: #2563eb; }
                .cta-button { display: inline-block; background: #2563eb; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; margin: 20px 0; }
                .footer { background: #f8f9fa; padding: 20px; text-align: center; font-size: 12px; color: #666; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1><?php echo get_bloginfo('name'); ?></h1>
                    <p><?php esc_html_e('Complete Your Purchase', 'ai-woo-theme'); ?></p>
                </div>
                
                <div class="content">
                    <h2><?php esc_html_e('You left something in your cart!', 'ai-woo-theme'); ?></h2>
                    <p><?php esc_html_e('We noticed you were interested in these items. Complete your purchase now and save 10% with the code below:', 'ai-woo-theme'); ?></p>
                    
                    <div class="discount-box">
                        <p><?php esc_html_e('Use code:', 'ai-woo-theme'); ?></p>
                        <div class="discount-code"><?php echo esc_html($discount_code); ?></div>
                        <p><?php esc_html_e('Save 10% on your order', 'ai-woo-theme'); ?></p>
                    </div>
                    
                    <?php if (isset($cart_data['items'])): ?>
                        <div class="cart-items">
                            <?php foreach ($cart_data['items'] as $item): ?>
                                <div class="cart-item">
                                    <?php if (isset($item['image'])): ?>
                                        <img src="<?php echo esc_url($item['image']); ?>" alt="" class="item-image">
                                    <?php endif; ?>
                                    <div class="item-details">
                                        <h3><?php echo esc_html($item['name']); ?></h3>
                                        <p><?php echo esc_html($item['price']); ?> × <?php echo esc_html($item['quantity']); ?></p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    
                    <div style="text-align: center;">
                        <a href="<?php echo esc_url(wc_get_cart_url()); ?>" class="cta-button">
                            <?php esc_html_e('Complete Your Purchase', 'ai-woo-theme'); ?>
                        </a>
                    </div>
                    
                    <p><small><?php esc_html_e('This offer expires in 7 days. Discount code is valid for one-time use only.', 'ai-woo-theme'); ?></small></p>
                </div>
                
                <div class="footer">
                    <p><?php echo get_bloginfo('name'); ?> | <?php echo get_option('admin_email'); ?></p>
                    <p><?php esc_html_e('You received this email because you have items in your shopping cart.', 'ai-woo-theme'); ?></p>
                </div>
            </div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Get follow-up email template
     */
    private function get_follow_up_email_template($cart_data, $discount_code) {
        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title><?php esc_html_e('Last Chance - 15% Off', 'ai-woo-theme'); ?></title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #dc2626; color: white; padding: 20px; text-align: center; }
                .content { padding: 30px 20px; }
                .urgency-box { background: #fef2f2; border: 2px solid #dc2626; padding: 20px; text-align: center; margin: 20px 0; }
                .discount-code { font-size: 28px; font-weight: bold; color: #dc2626; }
                .cta-button { display: inline-block; background: #dc2626; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; margin: 20px 0; }
                .footer { background: #f8f9fa; padding: 20px; text-align: center; font-size: 12px; color: #666; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1><?php esc_html_e('Last Chance!', 'ai-woo-theme'); ?></h1>
                    <p><?php esc_html_e('15% Off Your Cart', 'ai-woo-theme'); ?></p>
                </div>
                
                <div class="content">
                    <h2><?php esc_html_e('Don\'t miss out!', 'ai-woo-theme'); ?></h2>
                    <p><?php esc_html_e('Your cart is still waiting, and we\'ve increased your discount to 15%! This is your last chance to complete your purchase at this special price.', 'ai-woo-theme'); ?></p>
                    
                    <div class="urgency-box">
                        <p><strong><?php esc_html_e('FINAL OFFER:', 'ai-woo-theme'); ?></strong></p>
                        <div class="discount-code"><?php echo esc_html($discount_code); ?></div>
                        <p><?php esc_html_e('Save 15% - Limited Time Only!', 'ai-woo-theme'); ?></p>
                    </div>
                    
                    <div style="text-align: center;">
                        <a href="<?php echo esc_url(wc_get_cart_url()); ?>" class="cta-button">
                            <?php esc_html_e('Claim Your 15% Discount', 'ai-woo-theme'); ?>
                        </a>
                    </div>
                    
                    <p><strong><?php esc_html_e('This offer expires in 24 hours!', 'ai-woo-theme'); ?></strong></p>
                </div>
                
                <div class="footer">
                    <p><?php echo get_bloginfo('name'); ?> | <?php echo get_option('admin_email'); ?></p>
                    <p><a href="#" style="color: #666;"><?php esc_html_e('Unsubscribe from cart recovery emails', 'ai-woo-theme'); ?></a></p>
                </div>
            </div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }
}

/**
 * Initialize Cart Recovery System
 */
new AI_Woo_Cart_Recovery();

/**
 * Save abandoned cart function
 */
function ai_woo_save_abandoned_cart($user_email, $cart_data) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'ai_woo_cart_abandonment';
    
    // Check if cart already exists for this email
    $existing_cart = $wpdb->get_row($wpdb->prepare(
        "SELECT id FROM {$table_name} WHERE user_email = %s AND recovered = 0",
        $user_email
    ));
    
    if ($existing_cart) {
        // Update existing cart
        $wpdb->update(
            $table_name,
            array(
                'cart_data' => $cart_data,
                'abandoned_at' => current_time('mysql'),
                'recovery_email_sent' => 0,
            ),
            array('id' => $existing_cart->id),
            array('%s', '%s', '%d'),
            array('%d')
        );
    } else {
        // Insert new cart
        $wpdb->insert(
            $table_name,
            array(
                'user_email' => $user_email,
                'cart_data' => $cart_data,
                'abandoned_at' => current_time('mysql'),
                'recovered' => 0,
                'recovery_email_sent' => 0,
            ),
            array('%s', '%s', '%s', '%d', '%d')
        );
    }
}

/**
 * Mark cart as recovered when order is completed
 */
function ai_woo_mark_cart_recovered($order_id) {
    $order = wc_get_order($order_id);
    $customer_email = $order->get_billing_email();
    
    global $wpdb;
    $table_name = $wpdb->prefix . 'ai_woo_cart_abandonment';
    
    $wpdb->update(
        $table_name,
        array('recovered' => 1),
        array('user_email' => $customer_email),
        array('%d'),
        array('%s')
    );
}
add_action('woocommerce_thankyou', 'ai_woo_mark_cart_recovered');

/**
 * AJAX handler to get cart contents
 */
function ai_woo_get_cart_contents() {
    check_ajax_referer('ai_woo_nonce', 'nonce');
    
    $cart_data = array(
        'cart_count' => WC()->cart->get_cart_contents_count(),
        'cart_total' => WC()->cart->get_cart_subtotal(),
        'items' => array(),
    );
    
    foreach (WC()->cart->get_cart() as $cart_item) {
        $product = $cart_item['data'];
        $cart_data['items'][] = array(
            'name' => $product->get_name(),
            'price' => wc_price($product->get_price()),
            'quantity' => $cart_item['quantity'],
            'image' => wp_get_attachment_image_src($product->get_image_id(), 'thumbnail')[0] ?? '',
        );
    }
    
    wp_send_json_success($cart_data);
}
add_action('wp_ajax_get_cart_contents', 'ai_woo_get_cart_contents');
add_action('wp_ajax_nopriv_get_cart_contents', 'ai_woo_get_cart_contents');