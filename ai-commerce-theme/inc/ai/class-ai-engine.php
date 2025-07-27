<?php
/**
 * AI Engine Class
 *
 * @package AI_Commerce
 */

if (!defined('ABSPATH')) {
    exit;
}

class AI_Commerce_AI_Engine {
    
    /**
     * AI API endpoint
     */
    private $api_endpoint;
    
    /**
     * API Key
     */
    private $api_key;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->api_endpoint = get_theme_mod('ai_api_endpoint', 'https://api.openai.com/v1');
        $this->api_key = get_theme_mod('ai_api_key', '');
        
        add_action('wp_ajax_ai_commerce_get_recommendation', array($this, 'ajax_get_recommendation'));
        add_action('wp_ajax_nopriv_ai_commerce_get_recommendation', array($this, 'ajax_get_recommendation'));
        
        add_action('wp_ajax_ai_commerce_analyze_behavior', array($this, 'ajax_analyze_behavior'));
        add_action('wp_ajax_nopriv_ai_commerce_analyze_behavior', array($this, 'ajax_analyze_behavior'));
    }
    
    /**
     * Make API request to AI service
     */
    private function make_ai_request($endpoint, $data) {
        if (empty($this->api_key)) {
            return new WP_Error('no_api_key', __('AI API key not configured', 'ai-commerce'));
        }
        
        $response = wp_remote_post($this->api_endpoint . $endpoint, array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->api_key,
                'Content-Type' => 'application/json',
            ),
            'body' => json_encode($data),
            'timeout' => 30,
        ));
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $body = wp_remote_retrieve_body($response);
        return json_decode($body, true);
    }
    
    /**
     * Get product recommendations based on user behavior
     */
    public function get_product_recommendations($user_id, $limit = 6) {
        $user_data = $this->get_user_behavior_data($user_id);
        $context = $this->build_recommendation_context($user_data);
        
        // Cache key
        $cache_key = 'ai_recommendations_' . $user_id . '_' . md5(serialize($context));
        $cached = get_transient($cache_key);
        
        if ($cached !== false) {
            return $cached;
        }
        
        // Prepare AI request
        $prompt = $this->build_recommendation_prompt($context);
        
        $ai_response = $this->make_ai_request('/completions', array(
            'model' => 'gpt-3.5-turbo',
            'messages' => array(
                array(
                    'role' => 'system',
                    'content' => 'You are an e-commerce recommendation engine. Analyze user behavior and suggest relevant products.'
                ),
                array(
                    'role' => 'user',
                    'content' => $prompt
                )
            ),
            'temperature' => 0.7,
            'max_tokens' => 500,
        ));
        
        if (is_wp_error($ai_response)) {
            // Fallback to basic recommendations
            return $this->get_fallback_recommendations($limit);
        }
        
        $recommendations = $this->parse_ai_recommendations($ai_response);
        
        // Cache for 1 hour
        set_transient($cache_key, $recommendations, HOUR_IN_SECONDS);
        
        return $recommendations;
    }
    
    /**
     * Get user behavior data
     */
    private function get_user_behavior_data($user_id) {
        $data = array(
            'viewed_products' => get_user_meta($user_id, '_ai_viewed_products', true) ?: array(),
            'purchased_products' => $this->get_user_purchased_products($user_id),
            'cart_items' => $this->get_user_cart_items($user_id),
            'browsing_categories' => get_user_meta($user_id, '_ai_browsing_categories', true) ?: array(),
            'search_queries' => get_user_meta($user_id, '_ai_search_queries', true) ?: array(),
            'avg_order_value' => $this->calculate_avg_order_value($user_id),
            'preferred_price_range' => $this->get_preferred_price_range($user_id),
        );
        
        return $data;
    }
    
    /**
     * Get user's purchased products
     */
    private function get_user_purchased_products($user_id) {
        if (!class_exists('WooCommerce')) {
            return array();
        }
        
        $orders = wc_get_orders(array(
            'customer' => $user_id,
            'status' => array('completed', 'processing'),
            'limit' => 10,
        ));
        
        $products = array();
        foreach ($orders as $order) {
            foreach ($order->get_items() as $item) {
                $products[] = array(
                    'id' => $item->get_product_id(),
                    'name' => $item->get_name(),
                    'category' => $this->get_product_category($item->get_product_id()),
                );
            }
        }
        
        return $products;
    }
    
    /**
     * Get product category
     */
    private function get_product_category($product_id) {
        $terms = get_the_terms($product_id, 'product_cat');
        if ($terms && !is_wp_error($terms)) {
            return $terms[0]->name;
        }
        return '';
    }
    
    /**
     * Get user's cart items
     */
    private function get_user_cart_items($user_id) {
        if (!class_exists('WooCommerce') || !is_user_logged_in()) {
            return array();
        }
        
        $cart = WC()->cart;
        $items = array();
        
        foreach ($cart->get_cart() as $cart_item) {
            $items[] = array(
                'id' => $cart_item['product_id'],
                'name' => $cart_item['data']->get_name(),
                'price' => $cart_item['data']->get_price(),
            );
        }
        
        return $items;
    }
    
    /**
     * Calculate average order value
     */
    private function calculate_avg_order_value($user_id) {
        if (!class_exists('WooCommerce')) {
            return 0;
        }
        
        $orders = wc_get_orders(array(
            'customer' => $user_id,
            'status' => array('completed'),
            'limit' => 10,
        ));
        
        if (empty($orders)) {
            return 0;
        }
        
        $total = 0;
        foreach ($orders as $order) {
            $total += $order->get_total();
        }
        
        return $total / count($orders);
    }
    
    /**
     * Get preferred price range
     */
    private function get_preferred_price_range($user_id) {
        $avg_order_value = $this->calculate_avg_order_value($user_id);
        
        if ($avg_order_value == 0) {
            return array('min' => 0, 'max' => 100);
        }
        
        return array(
            'min' => $avg_order_value * 0.5,
            'max' => $avg_order_value * 1.5,
        );
    }
    
    /**
     * Build recommendation context
     */
    private function build_recommendation_context($user_data) {
        return array(
            'user_preferences' => $this->extract_preferences($user_data),
            'shopping_patterns' => $this->analyze_shopping_patterns($user_data),
            'current_session' => $this->get_current_session_data(),
        );
    }
    
    /**
     * Extract user preferences
     */
    private function extract_preferences($user_data) {
        $preferences = array();
        
        // Extract category preferences
        $categories = array();
        foreach ($user_data['purchased_products'] as $product) {
            if (!empty($product['category'])) {
                $categories[] = $product['category'];
            }
        }
        
        $preferences['favorite_categories'] = array_unique($categories);
        $preferences['price_sensitivity'] = $this->calculate_price_sensitivity($user_data);
        
        return $preferences;
    }
    
    /**
     * Calculate price sensitivity
     */
    private function calculate_price_sensitivity($user_data) {
        $avg_value = $user_data['avg_order_value'];
        
        if ($avg_value < 50) {
            return 'budget-conscious';
        } elseif ($avg_value < 150) {
            return 'moderate';
        } else {
            return 'premium';
        }
    }
    
    /**
     * Analyze shopping patterns
     */
    private function analyze_shopping_patterns($user_data) {
        return array(
            'frequency' => $this->calculate_shopping_frequency($user_data),
            'seasonality' => $this->detect_seasonal_patterns($user_data),
            'brand_loyalty' => $this->assess_brand_loyalty($user_data),
        );
    }
    
    /**
     * Calculate shopping frequency
     */
    private function calculate_shopping_frequency($user_data) {
        // Simplified frequency calculation
        $purchase_count = count($user_data['purchased_products']);
        
        if ($purchase_count > 20) {
            return 'frequent';
        } elseif ($purchase_count > 5) {
            return 'regular';
        } else {
            return 'occasional';
        }
    }
    
    /**
     * Detect seasonal patterns
     */
    private function detect_seasonal_patterns($user_data) {
        // Placeholder for seasonal pattern detection
        return array(
            'current_season' => $this->get_current_season(),
            'preferred_seasons' => array(),
        );
    }
    
    /**
     * Get current season
     */
    private function get_current_season() {
        $month = date('n');
        
        if ($month >= 3 && $month <= 5) {
            return 'spring';
        } elseif ($month >= 6 && $month <= 8) {
            return 'summer';
        } elseif ($month >= 9 && $month <= 11) {
            return 'fall';
        } else {
            return 'winter';
        }
    }
    
    /**
     * Assess brand loyalty
     */
    private function assess_brand_loyalty($user_data) {
        // Simplified brand loyalty assessment
        return 'moderate';
    }
    
    /**
     * Get current session data
     */
    private function get_current_session_data() {
        return array(
            'device' => wp_is_mobile() ? 'mobile' : 'desktop',
            'time_of_day' => $this->get_time_of_day(),
            'referrer' => wp_get_referer(),
        );
    }
    
    /**
     * Get time of day
     */
    private function get_time_of_day() {
        $hour = date('G');
        
        if ($hour >= 5 && $hour < 12) {
            return 'morning';
        } elseif ($hour >= 12 && $hour < 17) {
            return 'afternoon';
        } elseif ($hour >= 17 && $hour < 21) {
            return 'evening';
        } else {
            return 'night';
        }
    }
    
    /**
     * Build recommendation prompt
     */
    private function build_recommendation_prompt($context) {
        $prompt = "Based on the following user data, recommend products:\n\n";
        $prompt .= "User Preferences:\n";
        $prompt .= json_encode($context['user_preferences']) . "\n\n";
        $prompt .= "Shopping Patterns:\n";
        $prompt .= json_encode($context['shopping_patterns']) . "\n\n";
        $prompt .= "Current Session:\n";
        $prompt .= json_encode($context['current_session']) . "\n\n";
        $prompt .= "Please suggest 6 product categories or types that would interest this user.";
        
        return $prompt;
    }
    
    /**
     * Parse AI recommendations
     */
    private function parse_ai_recommendations($ai_response) {
        if (!isset($ai_response['choices'][0]['message']['content'])) {
            return $this->get_fallback_recommendations(6);
        }
        
        $content = $ai_response['choices'][0]['message']['content'];
        
        // Parse the AI response and get product IDs
        // This is a simplified version - in production, you'd have more sophisticated parsing
        return $this->get_products_by_ai_suggestions($content);
    }
    
    /**
     * Get products based on AI suggestions
     */
    private function get_products_by_ai_suggestions($suggestions) {
        $args = array(
            'post_type' => 'product',
            'posts_per_page' => 6,
            'meta_key' => 'total_sales',
            'orderby' => 'meta_value_num',
            'order' => 'DESC',
        );
        
        $products = get_posts($args);
        
        return wp_list_pluck($products, 'ID');
    }
    
    /**
     * Get fallback recommendations
     */
    private function get_fallback_recommendations($limit) {
        // Fallback to best sellers
        $args = array(
            'post_type' => 'product',
            'posts_per_page' => $limit,
            'meta_key' => 'total_sales',
            'orderby' => 'meta_value_num',
            'order' => 'DESC',
        );
        
        $products = get_posts($args);
        
        return wp_list_pluck($products, 'ID');
    }
    
    /**
     * AJAX handler for getting recommendations
     */
    public function ajax_get_recommendation() {
        check_ajax_referer('ai-commerce-nonce', 'nonce');
        
        $user_id = get_current_user_id();
        if (!$user_id) {
            // For guests, use session-based recommendations
            $user_id = $this->get_guest_session_id();
        }
        
        $recommendations = $this->get_product_recommendations($user_id);
        
        wp_send_json_success(array(
            'recommendations' => $recommendations,
            'personalized' => true,
        ));
    }
    
    /**
     * Get guest session ID
     */
    private function get_guest_session_id() {
        if (!session_id()) {
            session_start();
        }
        
        if (!isset($_SESSION['ai_guest_id'])) {
            $_SESSION['ai_guest_id'] = 'guest_' . uniqid();
        }
        
        return $_SESSION['ai_guest_id'];
    }
    
    /**
     * AJAX handler for analyzing behavior
     */
    public function ajax_analyze_behavior() {
        check_ajax_referer('ai-commerce-nonce', 'nonce');
        
        $action = isset($_POST['behavior_action']) ? sanitize_text_field($_POST['behavior_action']) : '';
        $data = isset($_POST['data']) ? $_POST['data'] : array();
        
        $user_id = get_current_user_id();
        if (!$user_id) {
            $user_id = $this->get_guest_session_id();
        }
        
        switch ($action) {
            case 'view_product':
                $this->track_product_view($user_id, $data);
                break;
            case 'add_to_cart':
                $this->track_add_to_cart($user_id, $data);
                break;
            case 'search':
                $this->track_search($user_id, $data);
                break;
            case 'category_browse':
                $this->track_category_browse($user_id, $data);
                break;
        }
        
        wp_send_json_success(array('tracked' => true));
    }
    
    /**
     * Track product view
     */
    private function track_product_view($user_id, $data) {
        $product_id = isset($data['product_id']) ? intval($data['product_id']) : 0;
        
        if ($product_id) {
            $viewed_products = get_user_meta($user_id, '_ai_viewed_products', true) ?: array();
            
            // Add timestamp to track recency
            $viewed_products[] = array(
                'product_id' => $product_id,
                'timestamp' => current_time('timestamp'),
            );
            
            // Keep only last 50 views
            if (count($viewed_products) > 50) {
                $viewed_products = array_slice($viewed_products, -50);
            }
            
            update_user_meta($user_id, '_ai_viewed_products', $viewed_products);
        }
    }
    
    /**
     * Track add to cart
     */
    private function track_add_to_cart($user_id, $data) {
        $product_id = isset($data['product_id']) ? intval($data['product_id']) : 0;
        
        if ($product_id) {
            $cart_history = get_user_meta($user_id, '_ai_cart_history', true) ?: array();
            
            $cart_history[] = array(
                'product_id' => $product_id,
                'timestamp' => current_time('timestamp'),
            );
            
            update_user_meta($user_id, '_ai_cart_history', $cart_history);
        }
    }
    
    /**
     * Track search
     */
    private function track_search($user_id, $data) {
        $query = isset($data['query']) ? sanitize_text_field($data['query']) : '';
        
        if ($query) {
            $search_queries = get_user_meta($user_id, '_ai_search_queries', true) ?: array();
            
            $search_queries[] = array(
                'query' => $query,
                'timestamp' => current_time('timestamp'),
            );
            
            // Keep only last 20 searches
            if (count($search_queries) > 20) {
                $search_queries = array_slice($search_queries, -20);
            }
            
            update_user_meta($user_id, '_ai_search_queries', $search_queries);
        }
    }
    
    /**
     * Track category browse
     */
    private function track_category_browse($user_id, $data) {
        $category = isset($data['category']) ? sanitize_text_field($data['category']) : '';
        
        if ($category) {
            $browsing_categories = get_user_meta($user_id, '_ai_browsing_categories', true) ?: array();
            
            if (!isset($browsing_categories[$category])) {
                $browsing_categories[$category] = 0;
            }
            
            $browsing_categories[$category]++;
            
            update_user_meta($user_id, '_ai_browsing_categories', $browsing_categories);
        }
    }
}