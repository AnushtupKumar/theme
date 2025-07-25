<?php
/**
 * AI Integration Functions
 *
 * @package AI_Woo_Theme
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * AI Recommendation Engine Class
 */
class AI_Woo_Recommendation_Engine {
    
    private $api_key;
    private $api_endpoint = 'https://api.openai.com/v1/completions';
    
    public function __construct() {
        $this->api_key = get_theme_mod('ai_woo_ai_api_key', '');
    }
    
    /**
     * Get product recommendations based on user behavior
     */
    public function get_product_recommendations($user_id, $product_id = null, $limit = 6) {
        // Get user behavior data
        $user_behavior = $this->get_user_behavior($user_id);
        
        // Get product data for context
        $product_context = $this->get_product_context($product_id);
        
        // Generate recommendations using AI
        if (!empty($this->api_key)) {
            return $this->get_ai_recommendations($user_behavior, $product_context, $limit);
        } else {
            // Fallback to rule-based recommendations
            return $this->get_rule_based_recommendations($user_id, $product_id, $limit);
        }
    }
    
    /**
     * Get AI-powered recommendations using external API
     */
    private function get_ai_recommendations($user_behavior, $product_context, $limit) {
        $prompt = $this->build_recommendation_prompt($user_behavior, $product_context, $limit);
        
        $response = wp_remote_post($this->api_endpoint, array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->api_key,
                'Content-Type' => 'application/json',
            ),
            'body' => json_encode(array(
                'model' => 'text-davinci-003',
                'prompt' => $prompt,
                'max_tokens' => 500,
                'temperature' => 0.7,
            )),
            'timeout' => 30,
        ));
        
        if (is_wp_error($response)) {
            return $this->get_rule_based_recommendations(null, null, $limit);
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (isset($data['choices'][0]['text'])) {
            return $this->parse_ai_recommendations($data['choices'][0]['text']);
        }
        
        return $this->get_rule_based_recommendations(null, null, $limit);
    }
    
    /**
     * Build AI prompt for recommendations
     */
    private function build_recommendation_prompt($user_behavior, $product_context, $limit) {
        $prompt = "Based on the following user behavior and product context, recommend {$limit} products:\n\n";
        
        $prompt .= "User Behavior:\n";
        foreach ($user_behavior as $behavior) {
            $prompt .= "- {$behavior['action_type']}: {$behavior['data']}\n";
        }
        
        if ($product_context) {
            $prompt .= "\nCurrent Product Context:\n";
            $prompt .= "- Category: {$product_context['category']}\n";
            $prompt .= "- Price Range: {$product_context['price_range']}\n";
            $prompt .= "- Tags: " . implode(', ', $product_context['tags']) . "\n";
        }
        
        $prompt .= "\nPlease provide product IDs in JSON format: [123, 456, 789, ...]";
        
        return $prompt;
    }
    
    /**
     * Parse AI recommendations response
     */
    private function parse_ai_recommendations($ai_response) {
        // Extract JSON from AI response
        preg_match('/\[[\d,\s]+\]/', $ai_response, $matches);
        
        if (!empty($matches[0])) {
            $product_ids = json_decode($matches[0], true);
            if (is_array($product_ids)) {
                return $this->get_products_by_ids($product_ids);
            }
        }
        
        return array();
    }
    
    /**
     * Fallback rule-based recommendations
     */
    private function get_rule_based_recommendations($user_id, $product_id, $limit) {
        $recommendations = array();
        
        // Get recently viewed products
        if ($user_id) {
            $recent_views = $this->get_recent_user_views($user_id, 5);
            $categories = array();
            
            foreach ($recent_views as $view) {
                $product = wc_get_product($view['product_id']);
                if ($product) {
                    $product_categories = wp_get_post_terms($product->get_id(), 'product_cat');
                    foreach ($product_categories as $cat) {
                        $categories[] = $cat->term_id;
                    }
                }
            }
            
            // Get products from same categories
            if (!empty($categories)) {
                $args = array(
                    'post_type' => 'product',
                    'posts_per_page' => $limit,
                    'post_status' => 'publish',
                    'meta_query' => array(
                        array(
                            'key' => '_visibility',
                            'value' => array('catalog', 'visible'),
                            'compare' => 'IN'
                        )
                    ),
                    'tax_query' => array(
                        array(
                            'taxonomy' => 'product_cat',
                            'field' => 'term_id',
                            'terms' => array_unique($categories),
                        )
                    ),
                    'orderby' => 'rand'
                );
                
                $products = get_posts($args);
                foreach ($products as $product) {
                    $recommendations[] = wc_get_product($product->ID);
                }
            }
        }
        
        // If we don't have enough recommendations, add popular products
        if (count($recommendations) < $limit) {
            $popular_products = $this->get_popular_products($limit - count($recommendations));
            $recommendations = array_merge($recommendations, $popular_products);
        }
        
        return array_slice($recommendations, 0, $limit);
    }
    
    /**
     * Get user behavior data
     */
    private function get_user_behavior($user_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'ai_woo_user_behavior';
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table_name} WHERE user_id = %s ORDER BY timestamp DESC LIMIT 20",
            $user_id
        ), ARRAY_A);
        
        return $results ?: array();
    }
    
    /**
     * Get product context for AI
     */
    private function get_product_context($product_id) {
        if (!$product_id) {
            return null;
        }
        
        $product = wc_get_product($product_id);
        if (!$product) {
            return null;
        }
        
        $categories = wp_get_post_terms($product_id, 'product_cat');
        $tags = wp_get_post_terms($product_id, 'product_tag');
        
        return array(
            'category' => !empty($categories) ? $categories[0]->name : '',
            'price_range' => $this->get_price_range($product->get_price()),
            'tags' => array_map(function($tag) { return $tag->name; }, $tags),
        );
    }
    
    /**
     * Get price range category
     */
    private function get_price_range($price) {
        $price = floatval($price);
        
        if ($price < 25) return 'budget';
        if ($price < 100) return 'mid-range';
        if ($price < 500) return 'premium';
        return 'luxury';
    }
    
    /**
     * Get products by IDs
     */
    private function get_products_by_ids($product_ids) {
        $products = array();
        
        foreach ($product_ids as $id) {
            $product = wc_get_product($id);
            if ($product && $product->is_purchasable()) {
                $products[] = $product;
            }
        }
        
        return $products;
    }
    
    /**
     * Get recent user views
     */
    private function get_recent_user_views($user_id, $limit) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'ai_woo_user_behavior';
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT DISTINCT product_id, data FROM {$table_name} 
             WHERE user_id = %s AND action_type = 'product_view' AND product_id IS NOT NULL 
             ORDER BY timestamp DESC LIMIT %d",
            $user_id, $limit
        ), ARRAY_A);
    }
    
    /**
     * Get popular products
     */
    private function get_popular_products($limit) {
        $args = array(
            'post_type' => 'product',
            'posts_per_page' => $limit,
            'meta_key' => 'total_sales',
            'orderby' => 'meta_value_num',
            'order' => 'DESC',
            'post_status' => 'publish',
        );
        
        $products = array();
        $popular_posts = get_posts($args);
        
        foreach ($popular_posts as $post) {
            $product = wc_get_product($post->ID);
            if ($product) {
                $products[] = $product;
            }
        }
        
        return $products;
    }
}

/**
 * Initialize AI Recommendation Engine
 */
$ai_recommendation_engine = new AI_Woo_Recommendation_Engine();

/**
 * Track user behavior for AI learning
 */
function ai_woo_track_user_behavior($user_id, $action_type, $data) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'ai_woo_user_behavior';
    
    // Extract product ID if available
    $product_id = null;
    if (is_array($data) && isset($data['product_id'])) {
        $product_id = intval($data['product_id']);
    }
    
    $wpdb->insert(
        $table_name,
        array(
            'user_id' => $user_id,
            'action_type' => $action_type,
            'product_id' => $product_id,
            'data' => is_array($data) ? json_encode($data) : $data,
            'timestamp' => current_time('mysql'),
        ),
        array('%s', '%s', '%d', '%s', '%s')
    );
}

/**
 * Get product recommendations
 */
function ai_woo_get_product_recommendations($user_id, $product_id = null, $limit = 6) {
    global $ai_recommendation_engine;
    return $ai_recommendation_engine->get_product_recommendations($user_id, $product_id, $limit);
}

/**
 * Auto-track product views
 */
function ai_woo_track_product_view() {
    if (is_product()) {
        global $post;
        $user_id = get_current_user_id() ?: session_id();
        
        ai_woo_track_user_behavior($user_id, 'product_view', array(
            'product_id' => $post->ID,
            'timestamp' => time(),
            'url' => get_permalink($post->ID),
        ));
    }
}
add_action('wp_head', 'ai_woo_track_product_view');

/**
 * Track search queries
 */
function ai_woo_track_search($query) {
    if (!is_admin() && $query->is_search() && $query->is_main_query()) {
        $user_id = get_current_user_id() ?: session_id();
        $search_term = get_search_query();
        
        if (!empty($search_term)) {
            ai_woo_track_user_behavior($user_id, 'search', array(
                'search_term' => $search_term,
                'results_count' => $query->found_posts,
            ));
        }
    }
}
add_action('pre_get_posts', 'ai_woo_track_search');

/**
 * Track cart additions
 */
function ai_woo_track_add_to_cart($cart_item_key, $product_id, $quantity) {
    $user_id = get_current_user_id() ?: session_id();
    
    ai_woo_track_user_behavior($user_id, 'add_to_cart', array(
        'product_id' => $product_id,
        'quantity' => $quantity,
        'cart_total' => WC()->cart->get_cart_contents_count(),
    ));
}
add_action('woocommerce_add_to_cart', 'ai_woo_track_add_to_cart', 10, 3);

/**
 * Track purchases
 */
function ai_woo_track_purchase($order_id) {
    $order = wc_get_order($order_id);
    $user_id = $order->get_customer_id() ?: $order->get_billing_email();
    
    foreach ($order->get_items() as $item) {
        ai_woo_track_user_behavior($user_id, 'purchase', array(
            'product_id' => $item->get_product_id(),
            'quantity' => $item->get_quantity(),
            'order_total' => $order->get_total(),
            'order_id' => $order_id,
        ));
    }
}
add_action('woocommerce_thankyou', 'ai_woo_track_purchase');

/**
 * AI-powered dynamic pricing
 */
function ai_woo_dynamic_pricing($price, $product) {
    if (!get_theme_mod('ai_woo_enable_dynamic_pricing', false)) {
        return $price;
    }
    
    $user_id = get_current_user_id() ?: session_id();
    $user_behavior = ai_woo_get_user_behavior_score($user_id);
    
    // Adjust price based on user behavior score
    $adjustment = 0;
    
    if ($user_behavior['loyalty_score'] > 80) {
        $adjustment = -0.05; // 5% discount for loyal customers
    } elseif ($user_behavior['engagement_score'] < 20) {
        $adjustment = -0.10; // 10% discount for low engagement users
    }
    
    if ($adjustment !== 0) {
        $new_price = $price * (1 + $adjustment);
        return max($new_price, $price * 0.5); // Never discount more than 50%
    }
    
    return $price;
}
add_filter('woocommerce_product_get_price', 'ai_woo_dynamic_pricing', 10, 2);

/**
 * Get user behavior score
 */
function ai_woo_get_user_behavior_score($user_id) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'ai_woo_user_behavior';
    
    // Get user activity in last 30 days
    $recent_activity = $wpdb->get_results($wpdb->prepare(
        "SELECT action_type, COUNT(*) as count FROM {$table_name} 
         WHERE user_id = %s AND timestamp > DATE_SUB(NOW(), INTERVAL 30 DAY) 
         GROUP BY action_type",
        $user_id
    ), ARRAY_A);
    
    $scores = array(
        'loyalty_score' => 0,
        'engagement_score' => 0,
        'purchase_score' => 0,
    );
    
    foreach ($recent_activity as $activity) {
        switch ($activity['action_type']) {
            case 'product_view':
                $scores['engagement_score'] += $activity['count'] * 1;
                break;
            case 'add_to_cart':
                $scores['engagement_score'] += $activity['count'] * 3;
                break;
            case 'purchase':
                $scores['loyalty_score'] += $activity['count'] * 10;
                $scores['purchase_score'] += $activity['count'] * 10;
                break;
            case 'search':
                $scores['engagement_score'] += $activity['count'] * 2;
                break;
        }
    }
    
    // Normalize scores to 0-100 range
    $scores['loyalty_score'] = min(100, $scores['loyalty_score']);
    $scores['engagement_score'] = min(100, $scores['engagement_score']);
    $scores['purchase_score'] = min(100, $scores['purchase_score']);
    
    return $scores;
}

/**
 * AI-powered layout optimization
 */
function ai_woo_optimize_layout_for_user($user_id) {
    $behavior_score = ai_woo_get_user_behavior_score($user_id);
    
    // Determine optimal layout based on user behavior
    if ($behavior_score['purchase_score'] > 50) {
        // High-value customers see premium layout
        return 'premium';
    } elseif ($behavior_score['engagement_score'] > 70) {
        // Engaged users see feature-rich layout
        return 'feature-rich';
    } else {
        // New/low-engagement users see simplified layout
        return 'simplified';
    }
}

/**
 * Smart product sorting based on user preferences
 */
function ai_woo_smart_product_sorting($query) {
    if (!is_admin() && $query->is_main_query() && (is_shop() || is_product_category())) {
        $user_id = get_current_user_id() ?: session_id();
        $user_preferences = ai_woo_get_user_preferences($user_id);
        
        if (!empty($user_preferences['preferred_sort'])) {
            $query->set('orderby', $user_preferences['preferred_sort']);
            $query->set('order', $user_preferences['sort_order']);
        }
    }
}
add_action('pre_get_posts', 'ai_woo_smart_product_sorting');

/**
 * Get user preferences based on behavior
 */
function ai_woo_get_user_preferences($user_id) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'ai_woo_user_behavior';
    
    // Analyze user behavior to determine preferences
    $price_behavior = $wpdb->get_results($wpdb->prepare(
        "SELECT AVG(CAST(JSON_EXTRACT(data, '$.price') AS DECIMAL)) as avg_price 
         FROM {$table_name} 
         WHERE user_id = %s AND action_type = 'purchase' 
         AND JSON_EXTRACT(data, '$.price') IS NOT NULL",
        $user_id
    ), ARRAY_A);
    
    $preferences = array(
        'preferred_sort' => 'date',
        'sort_order' => 'DESC',
        'price_range' => 'all',
    );
    
    if (!empty($price_behavior[0]['avg_price'])) {
        $avg_price = floatval($price_behavior[0]['avg_price']);
        
        if ($avg_price > 200) {
            $preferences['preferred_sort'] = 'price';
            $preferences['sort_order'] = 'DESC';
            $preferences['price_range'] = 'high';
        } elseif ($avg_price < 50) {
            $preferences['preferred_sort'] = 'price';
            $preferences['sort_order'] = 'ASC';
            $preferences['price_range'] = 'low';
        }
    }
    
    return $preferences;
}