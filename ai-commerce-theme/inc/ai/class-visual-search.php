<?php
/**
 * Visual Search Class
 *
 * @package AI_Commerce
 */

if (!defined('ABSPATH')) {
    exit;
}

class AI_Commerce_Visual_Search {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('wp_ajax_ai_commerce_visual_search', array($this, 'handle_visual_search'));
        add_action('wp_ajax_nopriv_ai_commerce_visual_search', array($this, 'handle_visual_search'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_shortcode('ai_visual_search', array($this, 'render_visual_search'));
    }
    
    /**
     * Enqueue scripts
     */
    public function enqueue_scripts() {
        wp_localize_script('ai-commerce-app', 'aiVisualSearch', array(
            'uploadUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ai-visual-search'),
            'maxFileSize' => 5 * 1024 * 1024, // 5MB
            'allowedTypes' => array('image/jpeg', 'image/png', 'image/webp'),
        ));
    }
    
    /**
     * Handle visual search AJAX request
     */
    public function handle_visual_search() {
        check_ajax_referer('ai-visual-search', 'nonce');
        
        if (!isset($_FILES['image'])) {
            wp_send_json_error(__('No image uploaded', 'ai-commerce'));
        }
        
        $uploaded_file = $_FILES['image'];
        
        // Validate file
        if (!$this->validate_image($uploaded_file)) {
            wp_send_json_error(__('Invalid image file', 'ai-commerce'));
        }
        
        // Process image with AI
        $image_features = $this->extract_image_features($uploaded_file['tmp_name']);
        
        if (is_wp_error($image_features)) {
            wp_send_json_error($image_features->get_error_message());
        }
        
        // Find similar products
        $similar_products = $this->find_similar_products($image_features);
        
        // Track search for analytics
        $this->track_visual_search($image_features);
        
        wp_send_json_success(array(
            'products' => $similar_products,
            'features' => $image_features,
            'message' => sprintf(
                __('Found %d similar products', 'ai-commerce'),
                count($similar_products)
            ),
        ));
    }
    
    /**
     * Validate uploaded image
     */
    private function validate_image($file) {
        // Check file size
        if ($file['size'] > 5 * 1024 * 1024) {
            return false;
        }
        
        // Check file type
        $allowed_types = array('image/jpeg', 'image/png', 'image/webp');
        if (!in_array($file['type'], $allowed_types)) {
            return false;
        }
        
        // Verify it's actually an image
        $image_info = getimagesize($file['tmp_name']);
        if (!$image_info) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Extract image features using AI
     */
    private function extract_image_features($image_path) {
        // Convert image to base64
        $image_data = base64_encode(file_get_contents($image_path));
        
        // Prepare API request
        $api_key = get_theme_mod('ai_api_key', '');
        if (empty($api_key)) {
            return new WP_Error('no_api_key', __('AI API key not configured', 'ai-commerce'));
        }
        
        // Call AI Vision API
        $response = wp_remote_post('https://api.openai.com/v1/chat/completions', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json',
            ),
            'body' => json_encode(array(
                'model' => 'gpt-4-vision-preview',
                'messages' => array(
                    array(
                        'role' => 'user',
                        'content' => array(
                            array(
                                'type' => 'text',
                                'text' => 'Analyze this product image and extract key features including: category, color, style, material, pattern, and any distinctive characteristics. Return as JSON.',
                            ),
                            array(
                                'type' => 'image_url',
                                'image_url' => array(
                                    'url' => 'data:image/jpeg;base64,' . $image_data,
                                ),
                            ),
                        ),
                    ),
                ),
                'max_tokens' => 500,
            )),
            'timeout' => 30,
        ));
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (!isset($data['choices'][0]['message']['content'])) {
            return new WP_Error('api_error', __('Failed to analyze image', 'ai-commerce'));
        }
        
        // Parse AI response
        $features_text = $data['choices'][0]['message']['content'];
        $features = json_decode($features_text, true);
        
        if (!$features) {
            // Fallback parsing if JSON decode fails
            $features = $this->parse_features_from_text($features_text);
        }
        
        return $features;
    }
    
    /**
     * Parse features from text response
     */
    private function parse_features_from_text($text) {
        $features = array(
            'category' => '',
            'color' => array(),
            'style' => '',
            'material' => '',
            'pattern' => '',
            'characteristics' => array(),
        );
        
        // Simple parsing logic - in production, use more sophisticated NLP
        if (preg_match('/category[:\s]+([^\n,]+)/i', $text, $matches)) {
            $features['category'] = trim($matches[1]);
        }
        
        if (preg_match('/color[:\s]+([^\n,]+)/i', $text, $matches)) {
            $features['color'] = array_map('trim', explode(',', $matches[1]));
        }
        
        if (preg_match('/style[:\s]+([^\n,]+)/i', $text, $matches)) {
            $features['style'] = trim($matches[1]);
        }
        
        return $features;
    }
    
    /**
     * Find similar products based on image features
     */
    private function find_similar_products($features) {
        $args = array(
            'post_type' => 'product',
            'posts_per_page' => 12,
            'meta_query' => array('relation' => 'OR'),
            'tax_query' => array('relation' => 'OR'),
        );
        
        // Search by category
        if (!empty($features['category'])) {
            $categories = get_terms(array(
                'taxonomy' => 'product_cat',
                'name__like' => $features['category'],
                'hide_empty' => false,
            ));
            
            if (!empty($categories)) {
                $args['tax_query'][] = array(
                    'taxonomy' => 'product_cat',
                    'field' => 'term_id',
                    'terms' => wp_list_pluck($categories, 'term_id'),
                );
            }
        }
        
        // Search by color
        if (!empty($features['color'])) {
            foreach ($features['color'] as $color) {
                $args['meta_query'][] = array(
                    'key' => '_product_attributes',
                    'value' => $color,
                    'compare' => 'LIKE',
                );
            }
        }
        
        // Search by tags
        if (!empty($features['style']) || !empty($features['material'])) {
            $tag_names = array_filter(array($features['style'], $features['material']));
            $tags = get_terms(array(
                'taxonomy' => 'product_tag',
                'name__like' => implode(' ', $tag_names),
                'hide_empty' => false,
            ));
            
            if (!empty($tags)) {
                $args['tax_query'][] = array(
                    'taxonomy' => 'product_tag',
                    'field' => 'term_id',
                    'terms' => wp_list_pluck($tags, 'term_id'),
                );
            }
        }
        
        // If no specific matches, get popular products
        if (empty($args['meta_query']) && empty($args['tax_query'])) {
            $args['meta_key'] = 'total_sales';
            $args['orderby'] = 'meta_value_num';
            $args['order'] = 'DESC';
        }
        
        $query = new WP_Query($args);
        $products = array();
        
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                global $product;
                
                $similarity_score = $this->calculate_similarity_score($product, $features);
                
                $products[] = array(
                    'id' => get_the_ID(),
                    'title' => get_the_title(),
                    'price' => $product->get_price_html(),
                    'image' => wp_get_attachment_url($product->get_image_id()),
                    'permalink' => get_permalink(),
                    'similarity' => $similarity_score,
                    'in_stock' => $product->is_in_stock(),
                );
            }
        }
        
        wp_reset_postdata();
        
        // Sort by similarity score
        usort($products, function($a, $b) {
            return $b['similarity'] <=> $a['similarity'];
        });
        
        return array_slice($products, 0, 12);
    }
    
    /**
     * Calculate similarity score between product and image features
     */
    private function calculate_similarity_score($product, $features) {
        $score = 0;
        $max_score = 100;
        
        // Category match (40 points)
        $product_categories = wp_get_post_terms($product->get_id(), 'product_cat');
        foreach ($product_categories as $cat) {
            if (stripos($cat->name, $features['category']) !== false) {
                $score += 40;
                break;
            }
        }
        
        // Color match (30 points)
        $product_attributes = $product->get_attributes();
        if (!empty($features['color']) && !empty($product_attributes)) {
            foreach ($features['color'] as $color) {
                foreach ($product_attributes as $attribute) {
                    if (stripos($attribute->get_data()['value'], $color) !== false) {
                        $score += 15;
                        break;
                    }
                }
            }
        }
        
        // Style/Material match (20 points)
        $product_tags = wp_get_post_terms($product->get_id(), 'product_tag');
        $feature_keywords = array_filter(array($features['style'], $features['material']));
        
        foreach ($product_tags as $tag) {
            foreach ($feature_keywords as $keyword) {
                if (stripos($tag->name, $keyword) !== false) {
                    $score += 10;
                }
            }
        }
        
        // Popularity bonus (10 points)
        $total_sales = get_post_meta($product->get_id(), 'total_sales', true);
        if ($total_sales > 100) {
            $score += 10;
        } elseif ($total_sales > 50) {
            $score += 5;
        }
        
        return min($score, $max_score);
    }
    
    /**
     * Track visual search for analytics
     */
    private function track_visual_search($features) {
        $user_id = get_current_user_id();
        if (!$user_id) {
            $user_id = 'guest_' . session_id();
        }
        
        $search_data = array(
            'timestamp' => current_time('timestamp'),
            'features' => $features,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'],
        );
        
        // Store in user meta for personalization
        $search_history = get_user_meta($user_id, '_ai_visual_searches', true) ?: array();
        $search_history[] = $search_data;
        
        // Keep only last 20 searches
        if (count($search_history) > 20) {
            $search_history = array_slice($search_history, -20);
        }
        
        update_user_meta($user_id, '_ai_visual_searches', $search_history);
        
        // Trigger action for other plugins/analytics
        do_action('ai_commerce_visual_search', $features, $user_id);
    }
    
    /**
     * Render visual search interface
     */
    public function render_visual_search($atts) {
        $atts = shortcode_atts(array(
            'button_text' => __('Search by Image', 'ai-commerce'),
            'class' => 'ai-visual-search-widget',
        ), $atts);
        
        ob_start();
        ?>
        <div class="<?php echo esc_attr($atts['class']); ?>">
            <button class="ai-btn ai-btn-outline ai-visual-search-trigger">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                    <circle cx="8.5" cy="8.5" r="1.5"></circle>
                    <polyline points="21 15 16 10 5 21"></polyline>
                </svg>
                <?php echo esc_html($atts['button_text']); ?>
            </button>
            
            <div class="ai-visual-search-modal" style="display: none;">
                <div class="ai-visual-search-content">
                    <h3><?php esc_html_e('Search by Image', 'ai-commerce'); ?></h3>
                    <p><?php esc_html_e('Upload an image to find similar products', 'ai-commerce'); ?></p>
                    
                    <div class="ai-visual-search-dropzone">
                        <input type="file" id="ai-visual-search-input" accept="image/*" style="display: none;">
                        <label for="ai-visual-search-input" class="ai-dropzone-label">
                            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                <polyline points="7 10 12 15 17 10"></polyline>
                                <line x1="12" y1="15" x2="12" y2="3"></line>
                            </svg>
                            <span><?php esc_html_e('Drop image here or click to upload', 'ai-commerce'); ?></span>
                        </label>
                    </div>
                    
                    <div class="ai-visual-search-preview" style="display: none;">
                        <img src="" alt="">
                        <button class="ai-btn ai-btn-primary ai-search-similar">
                            <?php esc_html_e('Find Similar Products', 'ai-commerce'); ?>
                        </button>
                    </div>
                    
                    <div class="ai-visual-search-results" style="display: none;">
                        <h4><?php esc_html_e('Similar Products', 'ai-commerce'); ?></h4>
                        <div class="ai-product-grid"></div>
                    </div>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}

// Initialize
new AI_Commerce_Visual_Search();