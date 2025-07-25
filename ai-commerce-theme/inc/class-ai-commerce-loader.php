<?php
/**
 * Theme Loader Class
 *
 * @package AI_Commerce
 */

if (!defined('ABSPATH')) {
    exit;
}

class AI_Commerce_Loader {
    
    /**
     * Instance
     */
    private static $instance = null;
    
    /**
     * Get instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->init_hooks();
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Theme setup
        add_action('after_setup_theme', array($this, 'theme_setup'));
        
        // Assets
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
        add_action('admin_enqueue_scripts', array($this, 'admin_assets'));
        
        // AJAX handlers
        $this->register_ajax_handlers();
        
        // Filters
        add_filter('template_include', array($this, 'spa_template'), 99);
        add_filter('show_admin_bar', array($this, 'hide_admin_bar'));
        
        // WooCommerce
        add_action('after_setup_theme', array($this, 'woocommerce_support'));
        add_filter('woocommerce_enqueue_styles', '__return_empty_array');
    }
    
    /**
     * Theme setup
     */
    public function theme_setup() {
        // Load text domain
        load_theme_textdomain('ai-commerce', AI_COMMERCE_DIR . '/languages');
        
        // Add image sizes
        add_image_size('ai-product-thumb', 300, 300, true);
        add_image_size('ai-product-single', 600, 600, true);
        add_image_size('ai-hero', 1920, 800, true);
    }
    
    /**
     * Enqueue assets
     */
    public function enqueue_assets() {
        // Dequeue unnecessary scripts
        wp_dequeue_script('jquery-migrate');
        
        // Add preload for critical resources
        if (get_theme_mod('enable_preloading', true)) {
            add_action('wp_head', array($this, 'preload_resources'), 1);
        }
    }
    
    /**
     * Admin assets
     */
    public function admin_assets($hook) {
        if ('appearance_page_ai-commerce-settings' === $hook) {
            wp_enqueue_style('ai-commerce-admin', AI_COMMERCE_ASSETS . '/css/admin.css', array(), AI_COMMERCE_VERSION);
            wp_enqueue_script('ai-commerce-admin', AI_COMMERCE_ASSETS . '/js/admin.js', array('jquery'), AI_COMMERCE_VERSION, true);
        }
    }
    
    /**
     * Register AJAX handlers
     */
    private function register_ajax_handlers() {
        $ajax_actions = array(
            'ai_commerce_get_products',
            'ai_commerce_add_to_cart',
            'ai_commerce_remove_from_cart',
            'ai_commerce_update_cart',
            'ai_commerce_get_cart',
            'ai_commerce_search_products',
            'ai_commerce_quick_view',
        );
        
        foreach ($ajax_actions as $action) {
            add_action('wp_ajax_' . $action, array($this, 'handle_ajax'));
            add_action('wp_ajax_nopriv_' . $action, array($this, 'handle_ajax'));
        }
    }
    
    /**
     * Handle AJAX requests
     */
    public function handle_ajax() {
        check_ajax_referer('ai-commerce-nonce', 'nonce');
        
        $action = isset($_POST['action']) ? str_replace('ai_commerce_', '', $_POST['action']) : '';
        $method = 'ajax_' . $action;
        
        if (method_exists($this, $method)) {
            $this->$method();
        } else {
            wp_send_json_error('Invalid action');
        }
    }
    
    /**
     * AJAX: Get products
     */
    private function ajax_get_products() {
        $args = array(
            'post_type' => 'product',
            'posts_per_page' => isset($_POST['per_page']) ? intval($_POST['per_page']) : 12,
            'paged' => isset($_POST['page']) ? intval($_POST['page']) : 1,
        );
        
        if (isset($_POST['category'])) {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => 'product_cat',
                    'field' => 'slug',
                    'terms' => sanitize_text_field($_POST['category']),
                ),
            );
        }
        
        if (isset($_POST['orderby'])) {
            switch ($_POST['orderby']) {
                case 'price':
                    $args['orderby'] = 'meta_value_num';
                    $args['meta_key'] = '_price';
                    $args['order'] = 'ASC';
                    break;
                case 'price-desc':
                    $args['orderby'] = 'meta_value_num';
                    $args['meta_key'] = '_price';
                    $args['order'] = 'DESC';
                    break;
                case 'popularity':
                    $args['orderby'] = 'meta_value_num';
                    $args['meta_key'] = 'total_sales';
                    $args['order'] = 'DESC';
                    break;
                case 'rating':
                    $args['orderby'] = 'meta_value_num';
                    $args['meta_key'] = '_wc_average_rating';
                    $args['order'] = 'DESC';
                    break;
                default:
                    $args['orderby'] = 'date';
                    $args['order'] = 'DESC';
            }
        }
        
        $query = new WP_Query($args);
        $products = array();
        
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                global $product;
                
                $products[] = array(
                    'id' => get_the_ID(),
                    'title' => get_the_title(),
                    'price' => $product->get_price_html(),
                    'regular_price' => $product->get_regular_price(),
                    'sale_price' => $product->get_sale_price(),
                    'image' => wp_get_attachment_url($product->get_image_id()),
                    'permalink' => get_permalink(),
                    'excerpt' => get_the_excerpt(),
                    'in_stock' => $product->is_in_stock(),
                    'rating' => $product->get_average_rating(),
                );
            }
        }
        
        wp_reset_postdata();
        
        wp_send_json_success(array(
            'products' => $products,
            'total' => $query->found_posts,
            'pages' => $query->max_num_pages,
        ));
    }
    
    /**
     * AJAX: Add to cart
     */
    private function ajax_add_to_cart() {
        $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
        $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;
        
        if ($product_id && WC()->cart->add_to_cart($product_id, $quantity)) {
            wp_send_json_success(array(
                'cart_count' => WC()->cart->get_cart_contents_count(),
                'cart_total' => WC()->cart->get_cart_total(),
                'message' => __('Product added to cart', 'ai-commerce'),
            ));
        } else {
            wp_send_json_error(__('Failed to add product to cart', 'ai-commerce'));
        }
    }
    
    /**
     * SPA template
     */
    public function spa_template($template) {
        if (!is_admin() && !is_customize_preview()) {
            // For SPA, always use index.php for frontend routes
            if (is_shop() || is_product_category() || is_product() || is_cart() || is_checkout() || is_account_page()) {
                return AI_COMMERCE_DIR . '/index.php';
            }
        }
        return $template;
    }
    
    /**
     * Hide admin bar for non-admins
     */
    public function hide_admin_bar($show) {
        if (!current_user_can('manage_options')) {
            return false;
        }
        return $show;
    }
    
    /**
     * WooCommerce support
     */
    public function woocommerce_support() {
        add_theme_support('woocommerce', array(
            'thumbnail_image_width' => 300,
            'single_image_width'    => 600,
            'product_grid'          => array(
                'default_rows'    => 3,
                'min_rows'        => 1,
                'default_columns' => get_theme_mod('products_per_row', 4),
                'min_columns'     => 1,
                'max_columns'     => 6,
            ),
        ));
    }
    
    /**
     * Preload resources
     */
    public function preload_resources() {
        // Preload fonts
        $font = get_theme_mod('body_font', 'Inter');
        echo '<link rel="preconnect" href="https://fonts.googleapis.com">';
        echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>';
        echo '<link rel="preload" as="style" href="https://fonts.googleapis.com/css2?family=' . esc_attr($font) . ':wght@300;400;500;600;700&display=swap">';
        
        // Preload critical CSS
        echo '<link rel="preload" as="style" href="' . get_stylesheet_uri() . '">';
        
        // Preload app bundle
        echo '<link rel="preload" as="script" href="' . AI_COMMERCE_ASSETS . '/js/app.bundle.js">';
    }
}

// Initialize
AI_Commerce_Loader::get_instance();