<?php
/**
 * Predictive Search Class
 *
 * @package AI_Commerce
 */

if (!defined('ABSPATH')) {
    exit;
}

class AI_Commerce_Predictive_Search {
    
    /**
     * Search index table name
     */
    private $table_name;
    
    /**
     * Constructor
     */
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'ai_search_index';
        
        // Hooks
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        
        // AJAX handlers
        add_action('wp_ajax_ai_commerce_predictive_search', array($this, 'ajax_predictive_search'));
        add_action('wp_ajax_nopriv_ai_commerce_predictive_search', array($this, 'ajax_predictive_search'));
        add_action('wp_ajax_ai_commerce_search_suggestions', array($this, 'ajax_search_suggestions'));
        add_action('wp_ajax_nopriv_ai_commerce_search_suggestions', array($this, 'ajax_search_suggestions'));
        
        // Index management
        add_action('save_post_product', array($this, 'update_search_index'), 10, 3);
        add_action('delete_post', array($this, 'remove_from_index'));
        add_action('ai_commerce_rebuild_search_index', array($this, 'rebuild_search_index'));
        
        // Schedule index optimization
        if (!wp_next_scheduled('ai_commerce_optimize_search_index')) {
            wp_schedule_event(time(), 'daily', 'ai_commerce_optimize_search_index');
        }
        add_action('ai_commerce_optimize_search_index', array($this, 'optimize_search_index'));
    }
    
    /**
     * Initialize
     */
    public function init() {
        // Create search index table if not exists
        $this->create_search_index_table();
        
        // Register search widget
        add_shortcode('ai_predictive_search', array($this, 'render_search_widget'));
    }
    
    /**
     * Create search index table
     */
    private function create_search_index_table() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS {$this->table_name} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            post_id bigint(20) NOT NULL,
            title text NOT NULL,
            content longtext,
            sku varchar(100),
            category_names text,
            category_ids text,
            tag_names text,
            attributes text,
            price decimal(10,2),
            sale_price decimal(10,2),
            stock_status varchar(20),
            popularity_score int(11) DEFAULT 0,
            search_keywords text,
            ai_embeddings text,
            last_updated datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY post_id (post_id),
            KEY sku (sku),
            KEY price (price),
            KEY popularity_score (popularity_score),
            FULLTEXT KEY search_index (title, content, category_names, tag_names, attributes, search_keywords)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Enqueue scripts
     */
    public function enqueue_scripts() {
        wp_localize_script('ai-commerce-app', 'aiPredictiveSearch', array(
            'searchUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ai-predictive-search'),
            'minChars' => 2,
            'debounceDelay' => 300,
            'maxSuggestions' => 10,
            'enableAI' => get_theme_mod('ai_search_enabled', true),
            'showCategories' => true,
            'showProducts' => true,
            'showPrices' => true,
            'showImages' => true,
        ));
    }
    
    /**
     * AJAX: Predictive search
     */
    public function ajax_predictive_search() {
        check_ajax_referer('ai-predictive-search', 'nonce');
        
        $query = isset($_POST['query']) ? sanitize_text_field($_POST['query']) : '';
        $user_id = get_current_user_id();
        
        if (strlen($query) < 2) {
            wp_send_json_error('Query too short');
        }
        
        // Get user context for personalization
        $user_context = $this->get_user_context($user_id);
        
        // Perform search
        $results = $this->perform_predictive_search($query, $user_context);
        
        // Track search for analytics and learning
        $this->track_search($query, $user_id, $results);
        
        wp_send_json_success($results);
    }
    
    /**
     * Get user context for personalization
     */
    private function get_user_context($user_id) {
        $context = array(
            'user_id' => $user_id,
            'segment' => 'new_visitor',
            'browsing_history' => array(),
            'purchase_history' => array(),
            'preferred_categories' => array(),
            'price_range' => array('min' => 0, 'max' => 1000),
            'device' => wp_is_mobile() ? 'mobile' : 'desktop',
            'location' => $this->get_user_location(),
        );
        
        if ($user_id) {
            // Get user segment
            $segmentation = new AI_Commerce_User_Segmentation();
            $context['segment'] = $segmentation->get_primary_segment($user_id);
            
            // Get browsing history
            $viewed_products = get_user_meta($user_id, '_ai_viewed_products', true) ?: array();
            $context['browsing_history'] = array_slice($viewed_products, -10);
            
            // Get purchase history categories
            $category_purchases = get_user_meta($user_id, '_ai_category_purchases', true) ?: array();
            arsort($category_purchases);
            $context['preferred_categories'] = array_keys(array_slice($category_purchases, 0, 3));
            
            // Get price range preference
            $ai_engine = new AI_Commerce_AI_Engine();
            $context['price_range'] = $ai_engine->get_preferred_price_range($user_id);
        }
        
        return $context;
    }
    
    /**
     * Get user location
     */
    private function get_user_location() {
        // Simple IP-based location detection
        $ip = $_SERVER['REMOTE_ADDR'];
        $location = get_transient('ai_location_' . md5($ip));
        
        if (false === $location) {
            // Use a geolocation service (simplified for demo)
            $location = array(
                'country' => 'US',
                'region' => 'Unknown',
                'city' => 'Unknown',
            );
            
            set_transient('ai_location_' . md5($ip), $location, DAY_IN_SECONDS);
        }
        
        return $location;
    }
    
    /**
     * Perform predictive search
     */
    private function perform_predictive_search($query, $user_context) {
        global $wpdb;
        
        $results = array(
            'query' => $query,
            'suggestions' => array(),
            'categories' => array(),
            'products' => array(),
            'ai_insights' => array(),
            'spell_correction' => null,
            'related_searches' => array(),
        );
        
        // Spell correction
        $corrected_query = $this->get_spell_correction($query);
        if ($corrected_query !== $query) {
            $results['spell_correction'] = $corrected_query;
        }
        
        // Get search suggestions
        $results['suggestions'] = $this->get_search_suggestions($query, $user_context);
        
        // Predict categories
        $results['categories'] = $this->predict_categories($query, $user_context);
        
        // Search products
        $results['products'] = $this->search_products($query, $user_context);
        
        // Get AI insights
        if (get_theme_mod('ai_search_insights', true)) {
            $results['ai_insights'] = $this->get_ai_search_insights($query, $user_context);
        }
        
        // Get related searches
        $results['related_searches'] = $this->get_related_searches($query);
        
        return $results;
    }
    
    /**
     * Get spell correction
     */
    private function get_spell_correction($query) {
        // Simple spell correction using common misspellings
        $corrections = array(
            'tshirt' => 't-shirt',
            'shooes' => 'shoes',
            'jeens' => 'jeans',
            'accesories' => 'accessories',
            'jwelry' => 'jewelry',
        );
        
        $lower_query = strtolower($query);
        foreach ($corrections as $wrong => $correct) {
            if (strpos($lower_query, $wrong) !== false) {
                return str_ireplace($wrong, $correct, $query);
            }
        }
        
        // Use AI for more complex corrections if enabled
        if (get_theme_mod('ai_spell_correction', true)) {
            return $this->ai_spell_correction($query);
        }
        
        return $query;
    }
    
    /**
     * AI spell correction
     */
    private function ai_spell_correction($query) {
        $api_key = get_theme_mod('ai_api_key', '');
        if (empty($api_key)) {
            return $query;
        }
        
        // Check cache
        $cache_key = 'ai_spell_' . md5($query);
        $cached = get_transient($cache_key);
        if ($cached !== false) {
            return $cached;
        }
        
        $response = wp_remote_post('https://api.openai.com/v1/chat/completions', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json',
            ),
            'body' => json_encode(array(
                'model' => 'gpt-3.5-turbo',
                'messages' => array(
                    array(
                        'role' => 'system',
                        'content' => 'You are a spell checker for e-commerce searches. Correct spelling errors while preserving the intent. Return only the corrected query, nothing else.',
                    ),
                    array(
                        'role' => 'user',
                        'content' => $query,
                    ),
                ),
                'temperature' => 0.1,
                'max_tokens' => 50,
            )),
            'timeout' => 5,
        ));
        
        if (!is_wp_error($response)) {
            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);
            
            if (isset($data['choices'][0]['message']['content'])) {
                $corrected = trim($data['choices'][0]['message']['content']);
                set_transient($cache_key, $corrected, HOUR_IN_SECONDS);
                return $corrected;
            }
        }
        
        return $query;
    }
    
    /**
     * Get search suggestions
     */
    private function get_search_suggestions($query, $user_context) {
        global $wpdb;
        
        $suggestions = array();
        
        // Get popular searches starting with query
        $popular_searches = $wpdb->get_results($wpdb->prepare(
            "SELECT query, COUNT(*) as count 
            FROM {$wpdb->prefix}ai_search_log 
            WHERE query LIKE %s 
            AND query != %s
            GROUP BY query 
            ORDER BY count DESC 
            LIMIT 5",
            $wpdb->esc_like($query) . '%',
            $query
        ));
        
        foreach ($popular_searches as $search) {
            $suggestions[] = array(
                'text' => $search->query,
                'type' => 'popular',
                'count' => $search->count,
            );
        }
        
        // Get product title suggestions
        $product_suggestions = $wpdb->get_results($wpdb->prepare(
            "SELECT DISTINCT title, popularity_score 
            FROM {$this->table_name} 
            WHERE title LIKE %s 
            ORDER BY popularity_score DESC 
            LIMIT 5",
            '%' . $wpdb->esc_like($query) . '%'
        ));
        
        foreach ($product_suggestions as $product) {
            $suggestions[] = array(
                'text' => $product->title,
                'type' => 'product',
                'popularity' => $product->popularity_score,
            );
        }
        
        // Get AI-powered suggestions based on user context
        if (get_theme_mod('ai_suggestions_enabled', true)) {
            $ai_suggestions = $this->get_ai_suggestions($query, $user_context);
            $suggestions = array_merge($suggestions, $ai_suggestions);
        }
        
        // Remove duplicates and limit
        $unique_suggestions = array();
        $seen = array();
        
        foreach ($suggestions as $suggestion) {
            $key = strtolower($suggestion['text']);
            if (!isset($seen[$key])) {
                $seen[$key] = true;
                $unique_suggestions[] = $suggestion;
            }
        }
        
        return array_slice($unique_suggestions, 0, 10);
    }
    
    /**
     * Get AI suggestions
     */
    private function get_ai_suggestions($query, $user_context) {
        $api_key = get_theme_mod('ai_api_key', '');
        if (empty($api_key)) {
            return array();
        }
        
        // Build context prompt
        $context_prompt = "User segment: {$user_context['segment']}\n";
        if (!empty($user_context['preferred_categories'])) {
            $context_prompt .= "Preferred categories: " . implode(', ', $user_context['preferred_categories']) . "\n";
        }
        $context_prompt .= "Price range: \${$user_context['price_range']['min']} - \${$user_context['price_range']['max']}\n";
        
        $response = wp_remote_post('https://api.openai.com/v1/chat/completions', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json',
            ),
            'body' => json_encode(array(
                'model' => 'gpt-3.5-turbo',
                'messages' => array(
                    array(
                        'role' => 'system',
                        'content' => 'You are an e-commerce search assistant. Given a partial search query and user context, suggest relevant search completions. Return a JSON array of suggestions.',
                    ),
                    array(
                        'role' => 'user',
                        'content' => "Query: {$query}\n{$context_prompt}\nSuggest 5 relevant search completions.",
                    ),
                ),
                'temperature' => 0.7,
                'max_tokens' => 200,
            )),
            'timeout' => 5,
        ));
        
        if (!is_wp_error($response)) {
            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);
            
            if (isset($data['choices'][0]['message']['content'])) {
                $content = $data['choices'][0]['message']['content'];
                $ai_suggestions = json_decode($content, true);
                
                if (is_array($ai_suggestions)) {
                    $formatted_suggestions = array();
                    foreach ($ai_suggestions as $suggestion) {
                        $formatted_suggestions[] = array(
                            'text' => is_array($suggestion) ? $suggestion['text'] : $suggestion,
                            'type' => 'ai',
                            'relevance' => 0.9,
                        );
                    }
                    return $formatted_suggestions;
                }
            }
        }
        
        return array();
    }
    
    /**
     * Predict categories
     */
    private function predict_categories($query, $user_context) {
        global $wpdb;
        
        // Search for matching categories
        $categories = $wpdb->get_results($wpdb->prepare(
            "SELECT DISTINCT t.term_id, t.name, t.slug, tt.count
            FROM {$wpdb->terms} t
            INNER JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id
            WHERE tt.taxonomy = 'product_cat'
            AND (t.name LIKE %s OR t.slug LIKE %s)
            ORDER BY tt.count DESC
            LIMIT 5",
            '%' . $wpdb->esc_like($query) . '%',
            '%' . $wpdb->esc_like($query) . '%'
        ));
        
        $predicted_categories = array();
        
        foreach ($categories as $category) {
            $predicted_categories[] = array(
                'id' => $category->term_id,
                'name' => $category->name,
                'slug' => $category->slug,
                'count' => $category->count,
                'confidence' => $this->calculate_category_confidence($query, $category, $user_context),
            );
        }
        
        // Use AI to predict additional categories
        if (get_theme_mod('ai_category_prediction', true)) {
            $ai_categories = $this->ai_predict_categories($query, $user_context);
            $predicted_categories = $this->merge_category_predictions($predicted_categories, $ai_categories);
        }
        
        // Sort by confidence
        usort($predicted_categories, function($a, $b) {
            return $b['confidence'] <=> $a['confidence'];
        });
        
        return array_slice($predicted_categories, 0, 5);
    }
    
    /**
     * Calculate category confidence
     */
    private function calculate_category_confidence($query, $category, $user_context) {
        $confidence = 0.5;
        
        // Exact match bonus
        if (stripos($category->name, $query) === 0) {
            $confidence += 0.3;
        }
        
        // User preference bonus
        if (in_array($category->name, $user_context['preferred_categories'])) {
            $confidence += 0.2;
        }
        
        // Popularity bonus
        if ($category->count > 50) {
            $confidence += 0.1;
        }
        
        return min($confidence, 1.0);
    }
    
    /**
     * AI predict categories
     */
    private function ai_predict_categories($query, $user_context) {
        $api_key = get_theme_mod('ai_api_key', '');
        if (empty($api_key)) {
            return array();
        }
        
        // Get all categories for context
        $all_categories = get_terms(array(
            'taxonomy' => 'product_cat',
            'hide_empty' => false,
            'fields' => 'names',
        ));
        
        $response = wp_remote_post('https://api.openai.com/v1/chat/completions', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json',
            ),
            'body' => json_encode(array(
                'model' => 'gpt-3.5-turbo',
                'messages' => array(
                    array(
                        'role' => 'system',
                        'content' => 'You are an e-commerce category predictor. Given a search query, predict the most likely product categories from the available list. Return a JSON array with category names and confidence scores.',
                    ),
                    array(
                        'role' => 'user',
                        'content' => "Query: {$query}\nAvailable categories: " . implode(', ', array_slice($all_categories, 0, 50)) . "\nPredict top 3 categories with confidence scores.",
                    ),
                ),
                'temperature' => 0.3,
                'max_tokens' => 200,
            )),
            'timeout' => 5,
        ));
        
        if (!is_wp_error($response)) {
            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);
            
            if (isset($data['choices'][0]['message']['content'])) {
                $content = $data['choices'][0]['message']['content'];
                $predictions = json_decode($content, true);
                
                if (is_array($predictions)) {
                    $ai_categories = array();
                    foreach ($predictions as $prediction) {
                        $term = get_term_by('name', $prediction['name'], 'product_cat');
                        if ($term) {
                            $ai_categories[] = array(
                                'id' => $term->term_id,
                                'name' => $term->name,
                                'slug' => $term->slug,
                                'count' => $term->count,
                                'confidence' => floatval($prediction['confidence']),
                            );
                        }
                    }
                    return $ai_categories;
                }
            }
        }
        
        return array();
    }
    
    /**
     * Merge category predictions
     */
    private function merge_category_predictions($existing, $ai_predictions) {
        $merged = $existing;
        $existing_ids = array_column($existing, 'id');
        
        foreach ($ai_predictions as $ai_cat) {
            if (!in_array($ai_cat['id'], $existing_ids)) {
                $merged[] = $ai_cat;
            } else {
                // Update confidence if AI prediction is higher
                foreach ($merged as &$cat) {
                    if ($cat['id'] == $ai_cat['id'] && $ai_cat['confidence'] > $cat['confidence']) {
                        $cat['confidence'] = $ai_cat['confidence'];
                    }
                }
            }
        }
        
        return $merged;
    }
    
    /**
     * Search products
     */
    private function search_products($query, $user_context) {
        global $wpdb;
        
        // Build search query with relevance scoring
        $search_query = $wpdb->prepare(
            "SELECT 
                post_id,
                title,
                content,
                price,
                sale_price,
                stock_status,
                category_names,
                popularity_score,
                MATCH(title, content, category_names, tag_names, attributes, search_keywords) 
                AGAINST(%s IN NATURAL LANGUAGE MODE) as relevance_score
            FROM {$this->table_name}
            WHERE MATCH(title, content, category_names, tag_names, attributes, search_keywords) 
            AGAINST(%s IN NATURAL LANGUAGE MODE)
            ORDER BY relevance_score DESC, popularity_score DESC
            LIMIT 20",
            $query,
            $query
        );
        
        $results = $wpdb->get_results($search_query);
        
        $products = array();
        foreach ($results as $result) {
            $product = wc_get_product($result->post_id);
            if (!$product) continue;
            
            // Calculate personalized score
            $personalized_score = $this->calculate_personalized_score(
                $product,
                $result->relevance_score,
                $user_context
            );
            
            $products[] = array(
                'id' => $result->post_id,
                'title' => $result->title,
                'price' => $product->get_price_html(),
                'regular_price' => $product->get_regular_price(),
                'sale_price' => $product->get_sale_price(),
                'image' => wp_get_attachment_url($product->get_image_id()),
                'permalink' => get_permalink($result->post_id),
                'excerpt' => wp_trim_words($result->content, 20),
                'in_stock' => $product->is_in_stock(),
                'categories' => $result->category_names,
                'relevance_score' => $result->relevance_score,
                'personalized_score' => $personalized_score,
                'is_personalized' => $personalized_score > $result->relevance_score,
            );
        }
        
        // Sort by personalized score
        usort($products, function($a, $b) {
            return $b['personalized_score'] <=> $a['personalized_score'];
        });
        
        return array_slice($products, 0, 10);
    }
    
    /**
     * Calculate personalized score
     */
    private function calculate_personalized_score($product, $base_score, $user_context) {
        $score = $base_score;
        
        // Price range bonus
        $price = $product->get_price();
        if ($price >= $user_context['price_range']['min'] && 
            $price <= $user_context['price_range']['max']) {
            $score *= 1.2;
        }
        
        // Category preference bonus
        $categories = wp_get_post_terms($product->get_id(), 'product_cat', array('fields' => 'names'));
        foreach ($categories as $category) {
            if (in_array($category, $user_context['preferred_categories'])) {
                $score *= 1.3;
                break;
            }
        }
        
        // Recently viewed bonus
        $viewed_ids = array_column($user_context['browsing_history'], 'product_id');
        if (in_array($product->get_id(), $viewed_ids)) {
            $score *= 0.8; // Reduce score for already viewed items
        }
        
        // Segment-based adjustments
        switch ($user_context['segment']) {
            case 'bargain_hunter':
                if ($product->is_on_sale()) {
                    $score *= 1.5;
                }
                break;
            case 'vip_customer':
                // Boost premium products
                if ($price > 100) {
                    $score *= 1.2;
                }
                break;
        }
        
        return $score;
    }
    
    /**
     * Get AI search insights
     */
    private function get_ai_search_insights($query, $user_context) {
        $insights = array(
            'intent' => $this->predict_search_intent($query),
            'sentiment' => $this->analyze_query_sentiment($query),
            'urgency' => $this->detect_purchase_urgency($query),
            'recommendations' => array(),
        );
        
        // Generate personalized recommendations
        if ($insights['intent'] === 'purchase') {
            $insights['recommendations'][] = array(
                'type' => 'tip',
                'message' => __('Based on your search, you might also like our bestsellers in this category', 'ai-commerce'),
            );
        }
        
        if ($insights['urgency'] === 'high') {
            $insights['recommendations'][] = array(
                'type' => 'offer',
                'message' => __('Limited time offer: Free shipping on orders over $50', 'ai-commerce'),
            );
        }
        
        return $insights;
    }
    
    /**
     * Predict search intent
     */
    private function predict_search_intent($query) {
        $purchase_keywords = array('buy', 'price', 'cheap', 'best', 'deal', 'sale', 'discount');
        $research_keywords = array('review', 'compare', 'vs', 'difference', 'how to', 'guide');
        $support_keywords = array('return', 'warranty', 'shipping', 'help', 'support');
        
        $lower_query = strtolower($query);
        
        foreach ($purchase_keywords as $keyword) {
            if (strpos($lower_query, $keyword) !== false) {
                return 'purchase';
            }
        }
        
        foreach ($research_keywords as $keyword) {
            if (strpos($lower_query, $keyword) !== false) {
                return 'research';
            }
        }
        
        foreach ($support_keywords as $keyword) {
            if (strpos($lower_query, $keyword) !== false) {
                return 'support';
            }
        }
        
        return 'browse';
    }
    
    /**
     * Analyze query sentiment
     */
    private function analyze_query_sentiment($query) {
        $positive_words = array('love', 'great', 'excellent', 'perfect', 'amazing', 'best');
        $negative_words = array('bad', 'poor', 'terrible', 'worst', 'hate', 'problem');
        
        $lower_query = strtolower($query);
        $sentiment_score = 0;
        
        foreach ($positive_words as $word) {
            if (strpos($lower_query, $word) !== false) {
                $sentiment_score++;
            }
        }
        
        foreach ($negative_words as $word) {
            if (strpos($lower_query, $word) !== false) {
                $sentiment_score--;
            }
        }
        
        if ($sentiment_score > 0) return 'positive';
        if ($sentiment_score < 0) return 'negative';
        return 'neutral';
    }
    
    /**
     * Detect purchase urgency
     */
    private function detect_purchase_urgency($query) {
        $urgent_keywords = array('urgent', 'asap', 'today', 'now', 'immediately', 'quick', 'fast');
        
        $lower_query = strtolower($query);
        
        foreach ($urgent_keywords as $keyword) {
            if (strpos($lower_query, $keyword) !== false) {
                return 'high';
            }
        }
        
        return 'normal';
    }
    
    /**
     * Get related searches
     */
    private function get_related_searches($query) {
        global $wpdb;
        
        // Get searches that often follow this query
        $related = $wpdb->get_results($wpdb->prepare(
            "SELECT DISTINCT next_query, COUNT(*) as count
            FROM {$wpdb->prefix}ai_search_sequences
            WHERE previous_query = %s
            GROUP BY next_query
            ORDER BY count DESC
            LIMIT 5",
            $query
        ));
        
        $related_searches = array();
        foreach ($related as $search) {
            $related_searches[] = $search->next_query;
        }
        
        // If not enough related searches, use AI
        if (count($related_searches) < 3 && get_theme_mod('ai_related_searches', true)) {
            $ai_related = $this->get_ai_related_searches($query);
            $related_searches = array_unique(array_merge($related_searches, $ai_related));
        }
        
        return array_slice($related_searches, 0, 5);
    }
    
    /**
     * Get AI related searches
     */
    private function get_ai_related_searches($query) {
        $api_key = get_theme_mod('ai_api_key', '');
        if (empty($api_key)) {
            return array();
        }
        
        $response = wp_remote_post('https://api.openai.com/v1/chat/completions', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json',
            ),
            'body' => json_encode(array(
                'model' => 'gpt-3.5-turbo',
                'messages' => array(
                    array(
                        'role' => 'system',
                        'content' => 'You are an e-commerce search assistant. Given a search query, suggest 5 related searches that users might be interested in. Return only a JSON array of search queries.',
                    ),
                    array(
                        'role' => 'user',
                        'content' => "Original search: {$query}",
                    ),
                ),
                'temperature' => 0.7,
                'max_tokens' => 150,
            )),
            'timeout' => 5,
        ));
        
        if (!is_wp_error($response)) {
            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);
            
            if (isset($data['choices'][0]['message']['content'])) {
                $content = $data['choices'][0]['message']['content'];
                $related = json_decode($content, true);
                
                if (is_array($related)) {
                    return $related;
                }
            }
        }
        
        return array();
    }
    
    /**
     * Track search
     */
    private function track_search($query, $user_id, $results) {
        global $wpdb;
        
        // Log search
        $wpdb->insert(
            $wpdb->prefix . 'ai_search_log',
            array(
                'query' => $query,
                'user_id' => $user_id ?: 0,
                'results_count' => count($results['products']),
                'clicked_result' => 0, // Will be updated when user clicks
                'search_time' => current_time('mysql'),
                'user_segment' => $user_id ? get_user_meta($user_id, '_ai_primary_segment', true) : 'guest',
            )
        );
        
        $search_id = $wpdb->insert_id;
        
        // Track search sequence
        $session_key = 'ai_last_search';
        $last_search = isset($_SESSION[$session_key]) ? $_SESSION[$session_key] : null;
        
        if ($last_search && $last_search['query'] !== $query) {
            $wpdb->insert(
                $wpdb->prefix . 'ai_search_sequences',
                array(
                    'previous_query' => $last_search['query'],
                    'next_query' => $query,
                    'time_diff' => time() - $last_search['time'],
                    'user_id' => $user_id ?: 0,
                )
            );
        }
        
        $_SESSION[$session_key] = array(
            'query' => $query,
            'time' => time(),
            'search_id' => $search_id,
        );
        
        // Update product popularity scores based on search
        if (!empty($results['products'])) {
            $product_ids = array_column($results['products'], 'id');
            $wpdb->query(
                "UPDATE {$this->table_name} 
                SET popularity_score = popularity_score + 1 
                WHERE post_id IN (" . implode(',', array_map('intval', $product_ids)) . ")"
            );
        }
    }
    
    /**
     * Update search index
     */
    public function update_search_index($post_id, $post, $update) {
        if ($post->post_type !== 'product' || $post->post_status !== 'publish') {
            return;
        }
        
        global $wpdb;
        
        $product = wc_get_product($post_id);
        if (!$product) return;
        
        // Get categories
        $categories = wp_get_post_terms($post_id, 'product_cat');
        $category_names = wp_list_pluck($categories, 'name');
        $category_ids = wp_list_pluck($categories, 'term_id');
        
        // Get tags
        $tags = wp_get_post_terms($post_id, 'product_tag');
        $tag_names = wp_list_pluck($tags, 'name');
        
        // Get attributes
        $attributes = array();
        foreach ($product->get_attributes() as $attribute) {
            $attributes[] = $attribute->get_name() . ': ' . implode(', ', $attribute->get_options());
        }
        
        // Generate search keywords using AI
        $search_keywords = $this->generate_search_keywords($product);
        
        // Calculate initial popularity score
        $popularity_score = $this->calculate_initial_popularity($product);
        
        // Insert or update index
        $wpdb->replace(
            $this->table_name,
            array(
                'post_id' => $post_id,
                'title' => $product->get_name(),
                'content' => $product->get_description() . ' ' . $product->get_short_description(),
                'sku' => $product->get_sku(),
                'category_names' => implode(', ', $category_names),
                'category_ids' => implode(',', $category_ids),
                'tag_names' => implode(', ', $tag_names),
                'attributes' => implode(', ', $attributes),
                'price' => $product->get_price(),
                'sale_price' => $product->get_sale_price(),
                'stock_status' => $product->get_stock_status(),
                'popularity_score' => $popularity_score,
                'search_keywords' => $search_keywords,
                'last_updated' => current_time('mysql'),
            ),
            array('%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%f', '%f', '%s', '%d', '%s', '%s')
        );
    }
    
    /**
     * Generate search keywords
     */
    private function generate_search_keywords($product) {
        $keywords = array();
        
        // Add basic keywords
        $keywords[] = $product->get_name();
        $keywords[] = $product->get_sku();
        
        // Add category names
        $categories = wp_get_post_terms($product->get_id(), 'product_cat', array('fields' => 'names'));
        $keywords = array_merge($keywords, $categories);
        
        // Add tag names
        $tags = wp_get_post_terms($product->get_id(), 'product_tag', array('fields' => 'names'));
        $keywords = array_merge($keywords, $tags);
        
        // Generate AI keywords if enabled
        if (get_theme_mod('ai_keyword_generation', true)) {
            $ai_keywords = $this->generate_ai_keywords($product);
            $keywords = array_merge($keywords, $ai_keywords);
        }
        
        return implode(', ', array_unique(array_filter($keywords)));
    }
    
    /**
     * Generate AI keywords
     */
    private function generate_ai_keywords($product) {
        $api_key = get_theme_mod('ai_api_key', '');
        if (empty($api_key)) {
            return array();
        }
        
        $product_info = sprintf(
            "Product: %s\nDescription: %s\nCategory: %s",
            $product->get_name(),
            wp_trim_words($product->get_description(), 50),
            implode(', ', wp_get_post_terms($product->get_id(), 'product_cat', array('fields' => 'names')))
        );
        
        $response = wp_remote_post('https://api.openai.com/v1/chat/completions', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json',
            ),
            'body' => json_encode(array(
                'model' => 'gpt-3.5-turbo',
                'messages' => array(
                    array(
                        'role' => 'system',
                        'content' => 'You are an SEO expert. Generate 10 relevant search keywords for the given product. Return only a JSON array of keywords.',
                    ),
                    array(
                        'role' => 'user',
                        'content' => $product_info,
                    ),
                ),
                'temperature' => 0.5,
                'max_tokens' => 150,
            )),
            'timeout' => 10,
        ));
        
        if (!is_wp_error($response)) {
            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);
            
            if (isset($data['choices'][0]['message']['content'])) {
                $content = $data['choices'][0]['message']['content'];
                $keywords = json_decode($content, true);
                
                if (is_array($keywords)) {
                    return $keywords;
                }
            }
        }
        
        return array();
    }
    
    /**
     * Calculate initial popularity
     */
    private function calculate_initial_popularity($product) {
        $score = 0;
        
        // Sales count
        $total_sales = get_post_meta($product->get_id(), 'total_sales', true);
        $score += min($total_sales * 10, 1000);
        
        // Rating
        if ($product->get_average_rating() > 0) {
            $score += $product->get_average_rating() * 20;
        }
        
        // Review count
        $score += min($product->get_review_count() * 5, 500);
        
        // Featured product bonus
        if ($product->is_featured()) {
            $score += 200;
        }
        
        // On sale bonus
        if ($product->is_on_sale()) {
            $score += 100;
        }
        
        return $score;
    }
    
    /**
     * Remove from index
     */
    public function remove_from_index($post_id) {
        global $wpdb;
        
        $post_type = get_post_type($post_id);
        if ($post_type === 'product') {
            $wpdb->delete($this->table_name, array('post_id' => $post_id), array('%d'));
        }
    }
    
    /**
     * Rebuild search index
     */
    public function rebuild_search_index() {
        $products = wc_get_products(array(
            'status' => 'publish',
            'limit' => -1,
        ));
        
        foreach ($products as $product) {
            $this->update_search_index($product->get_id(), get_post($product->get_id()), false);
        }
    }
    
    /**
     * Optimize search index
     */
    public function optimize_search_index() {
        global $wpdb;
        
        // Remove orphaned entries
        $wpdb->query(
            "DELETE FROM {$this->table_name} 
            WHERE post_id NOT IN (SELECT ID FROM {$wpdb->posts} WHERE post_type = 'product' AND post_status = 'publish')"
        );
        
        // Update popularity scores based on recent activity
        $this->update_popularity_scores();
    }
    
    /**
     * Update popularity scores
     */
    private function update_popularity_scores() {
        global $wpdb;
        
        // Decay old popularity scores
        $wpdb->query("UPDATE {$this->table_name} SET popularity_score = popularity_score * 0.95");
        
        // Boost products with recent sales
        $recent_orders = wc_get_orders(array(
            'status' => array('completed'),
            'date_created' => '>' . (time() - WEEK_IN_SECONDS),
            'return' => 'ids',
        ));
        
        foreach ($recent_orders as $order_id) {
            $order = wc_get_order($order_id);
            foreach ($order->get_items() as $item) {
                $product_id = $item->get_product_id();
                $wpdb->query($wpdb->prepare(
                    "UPDATE {$this->table_name} 
                    SET popularity_score = popularity_score + 50 
                    WHERE post_id = %d",
                    $product_id
                ));
            }
        }
    }
    
    /**
     * Render search widget
     */
    public function render_search_widget($atts) {
        $atts = shortcode_atts(array(
            'placeholder' => __('Search for products...', 'ai-commerce'),
            'show_categories' => true,
            'show_suggestions' => true,
            'class' => 'ai-predictive-search',
        ), $atts);
        
        ob_start();
        ?>
        <div class="<?php echo esc_attr($atts['class']); ?>">
            <form role="search" method="get" class="ai-search-form" action="<?php echo esc_url(home_url('/')); ?>">
                <div class="ai-search-input-wrapper">
                    <input type="search" 
                           class="ai-search-input" 
                           placeholder="<?php echo esc_attr($atts['placeholder']); ?>" 
                           value="<?php echo get_search_query(); ?>" 
                           name="s" 
                           autocomplete="off"
                           data-show-categories="<?php echo esc_attr($atts['show_categories']); ?>"
                           data-show-suggestions="<?php echo esc_attr($atts['show_suggestions']); ?>">
                    <button type="submit" class="ai-search-submit">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="11" cy="11" r="8"></circle>
                            <path d="m21 21-4.35-4.35"></path>
                        </svg>
                    </button>
                    <?php if (class_exists('WooCommerce')) : ?>
                        <input type="hidden" name="post_type" value="product">
                    <?php endif; ?>
                </div>
                
                <div class="ai-search-dropdown" style="display: none;">
                    <div class="ai-search-suggestions"></div>
                    <div class="ai-search-categories"></div>
                    <div class="ai-search-products"></div>
                    <div class="ai-search-insights"></div>
                </div>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * AJAX: Get search suggestions
     */
    public function ajax_search_suggestions() {
        check_ajax_referer('ai-predictive-search', 'nonce');
        
        $query = isset($_POST['query']) ? sanitize_text_field($_POST['query']) : '';
        
        if (strlen($query) < 2) {
            wp_send_json_success(array());
        }
        
        $user_id = get_current_user_id();
        $user_context = $this->get_user_context($user_id);
        
        $suggestions = $this->get_search_suggestions($query, $user_context);
        
        wp_send_json_success($suggestions);
    }
}

// Initialize
new AI_Commerce_Predictive_Search();