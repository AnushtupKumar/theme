<?php
/**
 * User Segmentation Class
 *
 * @package AI_Commerce
 */

if (!defined('ABSPATH')) {
    exit;
}

class AI_Commerce_User_Segmentation {
    
    /**
     * Segment definitions
     */
    private $segments = array();
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->define_segments();
        
        // Hooks
        add_action('init', array($this, 'init'));
        add_action('wp_login', array($this, 'update_user_segments'), 10, 2);
        add_action('woocommerce_order_status_completed', array($this, 'update_segments_on_purchase'));
        add_action('ai_commerce_behavior_tracked', array($this, 'update_segments_on_behavior'), 10, 3);
        
        // AJAX handlers
        add_action('wp_ajax_ai_commerce_get_user_segments', array($this, 'ajax_get_user_segments'));
        add_action('wp_ajax_nopriv_ai_commerce_get_user_segments', array($this, 'ajax_get_user_segments'));
        
        // Cron job for segment updates
        add_action('ai_commerce_update_segments', array($this, 'batch_update_segments'));
        
        if (!wp_next_scheduled('ai_commerce_update_segments')) {
            wp_schedule_event(time(), 'hourly', 'ai_commerce_update_segments');
        }
    }
    
    /**
     * Initialize
     */
    public function init() {
        // Register custom taxonomy for segments
        register_taxonomy('user_segment', 'user', array(
            'public' => false,
            'show_ui' => false,
            'hierarchical' => false,
            'rewrite' => false,
        ));
    }
    
    /**
     * Define user segments
     */
    private function define_segments() {
        $this->segments = array(
            'new_visitor' => array(
                'name' => __('New Visitor', 'ai-commerce'),
                'description' => __('First-time visitors', 'ai-commerce'),
                'rules' => array(
                    'visit_count' => array('operator' => '=', 'value' => 1),
                    'order_count' => array('operator' => '=', 'value' => 0),
                ),
                'priority' => 1,
            ),
            'browser' => array(
                'name' => __('Browser', 'ai-commerce'),
                'description' => __('Users who browse but haven\'t purchased', 'ai-commerce'),
                'rules' => array(
                    'visit_count' => array('operator' => '>', 'value' => 3),
                    'order_count' => array('operator' => '=', 'value' => 0),
                    'product_views' => array('operator' => '>', 'value' => 5),
                ),
                'priority' => 2,
            ),
            'first_time_buyer' => array(
                'name' => __('First Time Buyer', 'ai-commerce'),
                'description' => __('Made their first purchase', 'ai-commerce'),
                'rules' => array(
                    'order_count' => array('operator' => '=', 'value' => 1),
                    'days_since_first_order' => array('operator' => '<', 'value' => 30),
                ),
                'priority' => 3,
            ),
            'repeat_customer' => array(
                'name' => __('Repeat Customer', 'ai-commerce'),
                'description' => __('Multiple purchases', 'ai-commerce'),
                'rules' => array(
                    'order_count' => array('operator' => '>=', 'value' => 2),
                    'order_count' => array('operator' => '<', 'value' => 5),
                ),
                'priority' => 4,
            ),
            'vip_customer' => array(
                'name' => __('VIP Customer', 'ai-commerce'),
                'description' => __('High-value frequent buyers', 'ai-commerce'),
                'rules' => array(
                    'order_count' => array('operator' => '>=', 'value' => 5),
                    'total_spent' => array('operator' => '>', 'value' => 1000),
                ),
                'priority' => 5,
            ),
            'at_risk' => array(
                'name' => __('At Risk', 'ai-commerce'),
                'description' => __('Haven\'t purchased recently', 'ai-commerce'),
                'rules' => array(
                    'order_count' => array('operator' => '>', 'value' => 0),
                    'days_since_last_order' => array('operator' => '>', 'value' => 90),
                ),
                'priority' => 6,
            ),
            'win_back' => array(
                'name' => __('Win Back', 'ai-commerce'),
                'description' => __('Lost customers', 'ai-commerce'),
                'rules' => array(
                    'order_count' => array('operator' => '>', 'value' => 0),
                    'days_since_last_order' => array('operator' => '>', 'value' => 180),
                ),
                'priority' => 7,
            ),
            'high_intent' => array(
                'name' => __('High Intent', 'ai-commerce'),
                'description' => __('Showing purchase intent', 'ai-commerce'),
                'rules' => array(
                    'cart_abandonment_count' => array('operator' => '>', 'value' => 0),
                    'product_views_last_7_days' => array('operator' => '>', 'value' => 10),
                ),
                'priority' => 8,
            ),
            'bargain_hunter' => array(
                'name' => __('Bargain Hunter', 'ai-commerce'),
                'description' => __('Price-sensitive shoppers', 'ai-commerce'),
                'rules' => array(
                    'coupon_usage_rate' => array('operator' => '>', 'value' => 0.7),
                    'avg_discount_percentage' => array('operator' => '>', 'value' => 20),
                ),
                'priority' => 9,
            ),
            'category_enthusiast' => array(
                'name' => __('Category Enthusiast', 'ai-commerce'),
                'description' => __('Focused on specific categories', 'ai-commerce'),
                'rules' => array(
                    'category_concentration' => array('operator' => '>', 'value' => 0.8),
                    'category_purchase_count' => array('operator' => '>', 'value' => 3),
                ),
                'priority' => 10,
            ),
        );
        
        // Allow filtering of segments
        $this->segments = apply_filters('ai_commerce_user_segments', $this->segments);
    }
    
    /**
     * Get user data for segmentation
     */
    private function get_user_data($user_id) {
        $data = array(
            'user_id' => $user_id,
            'visit_count' => $this->get_visit_count($user_id),
            'order_count' => $this->get_order_count($user_id),
            'total_spent' => $this->get_total_spent($user_id),
            'product_views' => $this->get_product_view_count($user_id),
            'product_views_last_7_days' => $this->get_recent_product_views($user_id, 7),
            'days_since_first_order' => $this->get_days_since_first_order($user_id),
            'days_since_last_order' => $this->get_days_since_last_order($user_id),
            'cart_abandonment_count' => $this->get_cart_abandonment_count($user_id),
            'coupon_usage_rate' => $this->get_coupon_usage_rate($user_id),
            'avg_discount_percentage' => $this->get_avg_discount_percentage($user_id),
            'category_concentration' => $this->get_category_concentration($user_id),
            'category_purchase_count' => $this->get_category_purchase_count($user_id),
            'avg_order_value' => $this->get_avg_order_value($user_id),
            'preferred_device' => $this->get_preferred_device($user_id),
            'preferred_time' => $this->get_preferred_shopping_time($user_id),
        );
        
        return apply_filters('ai_commerce_user_segmentation_data', $data, $user_id);
    }
    
    /**
     * Get visit count
     */
    private function get_visit_count($user_id) {
        return intval(get_user_meta($user_id, '_ai_visit_count', true)) ?: 1;
    }
    
    /**
     * Get order count
     */
    private function get_order_count($user_id) {
        if (!class_exists('WooCommerce')) {
            return 0;
        }
        
        $orders = wc_get_orders(array(
            'customer' => $user_id,
            'status' => array('completed', 'processing'),
            'return' => 'ids',
        ));
        
        return count($orders);
    }
    
    /**
     * Get total spent
     */
    private function get_total_spent($user_id) {
        if (!class_exists('WooCommerce')) {
            return 0;
        }
        
        $customer = new WC_Customer($user_id);
        return $customer->get_total_spent();
    }
    
    /**
     * Get product view count
     */
    private function get_product_view_count($user_id) {
        $viewed_products = get_user_meta($user_id, '_ai_viewed_products', true) ?: array();
        return count($viewed_products);
    }
    
    /**
     * Get recent product views
     */
    private function get_recent_product_views($user_id, $days) {
        $viewed_products = get_user_meta($user_id, '_ai_viewed_products', true) ?: array();
        $cutoff_time = strtotime("-{$days} days");
        
        $recent_views = array_filter($viewed_products, function($view) use ($cutoff_time) {
            return isset($view['timestamp']) && $view['timestamp'] > $cutoff_time;
        });
        
        return count($recent_views);
    }
    
    /**
     * Get days since first order
     */
    private function get_days_since_first_order($user_id) {
        if (!class_exists('WooCommerce')) {
            return PHP_INT_MAX;
        }
        
        $orders = wc_get_orders(array(
            'customer' => $user_id,
            'status' => array('completed'),
            'orderby' => 'date',
            'order' => 'ASC',
            'limit' => 1,
        ));
        
        if (empty($orders)) {
            return PHP_INT_MAX;
        }
        
        $first_order = reset($orders);
        $order_date = $first_order->get_date_created();
        
        return floor((time() - $order_date->getTimestamp()) / DAY_IN_SECONDS);
    }
    
    /**
     * Get days since last order
     */
    private function get_days_since_last_order($user_id) {
        if (!class_exists('WooCommerce')) {
            return PHP_INT_MAX;
        }
        
        $orders = wc_get_orders(array(
            'customer' => $user_id,
            'status' => array('completed'),
            'orderby' => 'date',
            'order' => 'DESC',
            'limit' => 1,
        ));
        
        if (empty($orders)) {
            return PHP_INT_MAX;
        }
        
        $last_order = reset($orders);
        $order_date = $last_order->get_date_created();
        
        return floor((time() - $order_date->getTimestamp()) / DAY_IN_SECONDS);
    }
    
    /**
     * Get cart abandonment count
     */
    private function get_cart_abandonment_count($user_id) {
        return intval(get_user_meta($user_id, '_ai_cart_abandonment_count', true)) ?: 0;
    }
    
    /**
     * Get coupon usage rate
     */
    private function get_coupon_usage_rate($user_id) {
        if (!class_exists('WooCommerce')) {
            return 0;
        }
        
        $total_orders = $this->get_order_count($user_id);
        if ($total_orders == 0) {
            return 0;
        }
        
        $orders_with_coupons = wc_get_orders(array(
            'customer' => $user_id,
            'status' => array('completed'),
            'meta_query' => array(
                array(
                    'key' => '_coupon_lines',
                    'compare' => 'EXISTS',
                ),
            ),
            'return' => 'ids',
        ));
        
        return count($orders_with_coupons) / $total_orders;
    }
    
    /**
     * Get average discount percentage
     */
    private function get_avg_discount_percentage($user_id) {
        if (!class_exists('WooCommerce')) {
            return 0;
        }
        
        $orders = wc_get_orders(array(
            'customer' => $user_id,
            'status' => array('completed'),
            'limit' => -1,
        ));
        
        if (empty($orders)) {
            return 0;
        }
        
        $total_discount = 0;
        $total_subtotal = 0;
        
        foreach ($orders as $order) {
            $total_discount += $order->get_total_discount();
            $total_subtotal += $order->get_subtotal();
        }
        
        if ($total_subtotal == 0) {
            return 0;
        }
        
        return ($total_discount / $total_subtotal) * 100;
    }
    
    /**
     * Get category concentration
     */
    private function get_category_concentration($user_id) {
        $category_purchases = get_user_meta($user_id, '_ai_category_purchases', true) ?: array();
        
        if (empty($category_purchases)) {
            return 0;
        }
        
        $total_purchases = array_sum($category_purchases);
        $max_category_purchases = max($category_purchases);
        
        if ($total_purchases == 0) {
            return 0;
        }
        
        return $max_category_purchases / $total_purchases;
    }
    
    /**
     * Get category purchase count
     */
    private function get_category_purchase_count($user_id) {
        $category_purchases = get_user_meta($user_id, '_ai_category_purchases', true) ?: array();
        
        if (empty($category_purchases)) {
            return 0;
        }
        
        return max($category_purchases);
    }
    
    /**
     * Get average order value
     */
    private function get_avg_order_value($user_id) {
        $order_count = $this->get_order_count($user_id);
        if ($order_count == 0) {
            return 0;
        }
        
        $total_spent = $this->get_total_spent($user_id);
        return $total_spent / $order_count;
    }
    
    /**
     * Get preferred device
     */
    private function get_preferred_device($user_id) {
        $device_usage = get_user_meta($user_id, '_ai_device_usage', true) ?: array();
        
        if (empty($device_usage)) {
            return 'unknown';
        }
        
        arsort($device_usage);
        return key($device_usage);
    }
    
    /**
     * Get preferred shopping time
     */
    private function get_preferred_shopping_time($user_id) {
        $time_usage = get_user_meta($user_id, '_ai_shopping_times', true) ?: array();
        
        if (empty($time_usage)) {
            return 'unknown';
        }
        
        $hour_counts = array();
        foreach ($time_usage as $timestamp) {
            $hour = date('G', $timestamp);
            if (!isset($hour_counts[$hour])) {
                $hour_counts[$hour] = 0;
            }
            $hour_counts[$hour]++;
        }
        
        arsort($hour_counts);
        $peak_hour = key($hour_counts);
        
        if ($peak_hour >= 5 && $peak_hour < 12) {
            return 'morning';
        } elseif ($peak_hour >= 12 && $peak_hour < 17) {
            return 'afternoon';
        } elseif ($peak_hour >= 17 && $peak_hour < 21) {
            return 'evening';
        } else {
            return 'night';
        }
    }
    
    /**
     * Evaluate segment rules
     */
    private function evaluate_rules($user_data, $rules) {
        foreach ($rules as $field => $rule) {
            if (!isset($user_data[$field])) {
                continue;
            }
            
            $value = $user_data[$field];
            $operator = $rule['operator'];
            $compare_value = $rule['value'];
            
            switch ($operator) {
                case '=':
                    if ($value != $compare_value) return false;
                    break;
                case '>':
                    if ($value <= $compare_value) return false;
                    break;
                case '<':
                    if ($value >= $compare_value) return false;
                    break;
                case '>=':
                    if ($value < $compare_value) return false;
                    break;
                case '<=':
                    if ($value > $compare_value) return false;
                    break;
            }
        }
        
        return true;
    }
    
    /**
     * Calculate user segments
     */
    public function calculate_user_segments($user_id) {
        $user_data = $this->get_user_data($user_id);
        $matched_segments = array();
        
        foreach ($this->segments as $segment_key => $segment) {
            if ($this->evaluate_rules($user_data, $segment['rules'])) {
                $matched_segments[$segment_key] = array(
                    'key' => $segment_key,
                    'name' => $segment['name'],
                    'priority' => $segment['priority'],
                    'matched_at' => current_time('timestamp'),
                );
            }
        }
        
        // Sort by priority
        uasort($matched_segments, function($a, $b) {
            return $b['priority'] - $a['priority'];
        });
        
        // Store segments
        update_user_meta($user_id, '_ai_segments', $matched_segments);
        
        // Store primary segment (highest priority)
        if (!empty($matched_segments)) {
            $primary_segment = reset($matched_segments);
            update_user_meta($user_id, '_ai_primary_segment', $primary_segment['key']);
        }
        
        // Trigger action for other plugins
        do_action('ai_commerce_segments_updated', $user_id, $matched_segments);
        
        return $matched_segments;
    }
    
    /**
     * Get user segments
     */
    public function get_user_segments($user_id) {
        $segments = get_user_meta($user_id, '_ai_segments', true);
        
        if (empty($segments)) {
            $segments = $this->calculate_user_segments($user_id);
        }
        
        return $segments;
    }
    
    /**
     * Get primary segment
     */
    public function get_primary_segment($user_id) {
        return get_user_meta($user_id, '_ai_primary_segment', true) ?: 'new_visitor';
    }
    
    /**
     * Update user segments on login
     */
    public function update_user_segments($user_login, $user) {
        $visit_count = $this->get_visit_count($user->ID);
        update_user_meta($user->ID, '_ai_visit_count', $visit_count + 1);
        
        // Track device
        $device = wp_is_mobile() ? 'mobile' : 'desktop';
        $device_usage = get_user_meta($user->ID, '_ai_device_usage', true) ?: array();
        
        if (!isset($device_usage[$device])) {
            $device_usage[$device] = 0;
        }
        $device_usage[$device]++;
        
        update_user_meta($user->ID, '_ai_device_usage', $device_usage);
        
        // Track shopping time
        $shopping_times = get_user_meta($user->ID, '_ai_shopping_times', true) ?: array();
        $shopping_times[] = current_time('timestamp');
        
        // Keep last 50 timestamps
        if (count($shopping_times) > 50) {
            $shopping_times = array_slice($shopping_times, -50);
        }
        
        update_user_meta($user->ID, '_ai_shopping_times', $shopping_times);
        
        // Update segments
        $this->calculate_user_segments($user->ID);
    }
    
    /**
     * Update segments on purchase
     */
    public function update_segments_on_purchase($order_id) {
        $order = wc_get_order($order_id);
        if (!$order) return;
        
        $user_id = $order->get_user_id();
        if (!$user_id) return;
        
        // Update category purchases
        $category_purchases = get_user_meta($user_id, '_ai_category_purchases', true) ?: array();
        
        foreach ($order->get_items() as $item) {
            $product_id = $item->get_product_id();
            $categories = wp_get_post_terms($product_id, 'product_cat', array('fields' => 'names'));
            
            foreach ($categories as $category) {
                if (!isset($category_purchases[$category])) {
                    $category_purchases[$category] = 0;
                }
                $category_purchases[$category]++;
            }
        }
        
        update_user_meta($user_id, '_ai_category_purchases', $category_purchases);
        
        // Update segments
        $this->calculate_user_segments($user_id);
    }
    
    /**
     * Update segments on behavior
     */
    public function update_segments_on_behavior($user_id, $action, $data) {
        // Update relevant metrics based on action
        switch ($action) {
            case 'cart_abandoned':
                $count = $this->get_cart_abandonment_count($user_id);
                update_user_meta($user_id, '_ai_cart_abandonment_count', $count + 1);
                break;
        }
        
        // Recalculate segments if needed
        $this->calculate_user_segments($user_id);
    }
    
    /**
     * Batch update segments
     */
    public function batch_update_segments() {
        // Get users who need segment updates
        $args = array(
            'meta_query' => array(
                'relation' => 'OR',
                array(
                    'key' => '_ai_segments_updated',
                    'value' => strtotime('-1 day'),
                    'compare' => '<',
                    'type' => 'NUMERIC',
                ),
                array(
                    'key' => '_ai_segments_updated',
                    'compare' => 'NOT EXISTS',
                ),
            ),
            'number' => 50, // Process 50 users at a time
        );
        
        $users = get_users($args);
        
        foreach ($users as $user) {
            $this->calculate_user_segments($user->ID);
            update_user_meta($user->ID, '_ai_segments_updated', current_time('timestamp'));
        }
    }
    
    /**
     * AJAX: Get user segments
     */
    public function ajax_get_user_segments() {
        check_ajax_referer('ai-commerce-nonce', 'nonce');
        
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error('Not logged in');
        }
        
        $segments = $this->get_user_segments($user_id);
        $primary_segment = $this->get_primary_segment($user_id);
        
        wp_send_json_success(array(
            'segments' => $segments,
            'primary_segment' => $primary_segment,
            'segment_definitions' => $this->segments,
        ));
    }
    
    /**
     * Get segment statistics
     */
    public function get_segment_statistics() {
        global $wpdb;
        
        $stats = array();
        
        foreach ($this->segments as $segment_key => $segment) {
            $count = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(DISTINCT user_id) FROM {$wpdb->usermeta} 
                WHERE meta_key = '_ai_primary_segment' AND meta_value = %s",
                $segment_key
            ));
            
            $stats[$segment_key] = array(
                'name' => $segment['name'],
                'count' => intval($count),
                'percentage' => 0, // Will calculate after getting total
            );
        }
        
        // Calculate percentages
        $total_users = array_sum(array_column($stats, 'count'));
        if ($total_users > 0) {
            foreach ($stats as $key => &$stat) {
                $stat['percentage'] = round(($stat['count'] / $total_users) * 100, 2);
            }
        }
        
        return $stats;
    }
}

// Initialize
new AI_Commerce_User_Segmentation();