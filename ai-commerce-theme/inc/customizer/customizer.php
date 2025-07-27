<?php
/**
 * Theme Customizer
 *
 * @package AI_Commerce
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Add postMessage support for site title and description
 */
function ai_commerce_customize_register($wp_customize) {
    $wp_customize->get_setting('blogname')->transport         = 'postMessage';
    $wp_customize->get_setting('blogdescription')->transport  = 'postMessage';
    $wp_customize->get_setting('header_textcolor')->transport = 'postMessage';
    
    // AI Settings Section
    $wp_customize->add_section('ai_commerce_ai_settings', array(
        'title'    => __('AI Settings', 'ai-commerce'),
        'priority' => 30,
    ));
    
    // AI API Key
    $wp_customize->add_setting('ai_api_key', array(
        'default'           => '',
        'sanitize_callback' => 'sanitize_text_field',
    ));
    
    $wp_customize->add_control('ai_api_key', array(
        'label'       => __('AI API Key', 'ai-commerce'),
        'section'     => 'ai_commerce_ai_settings',
        'type'        => 'text',
        'description' => __('Enter your OpenAI API key for AI features', 'ai-commerce'),
    ));
    
    // AI API Endpoint
    $wp_customize->add_setting('ai_api_endpoint', array(
        'default'           => 'https://api.openai.com/v1',
        'sanitize_callback' => 'esc_url_raw',
    ));
    
    $wp_customize->add_control('ai_api_endpoint', array(
        'label'       => __('AI API Endpoint', 'ai-commerce'),
        'section'     => 'ai_commerce_ai_settings',
        'type'        => 'url',
        'description' => __('AI service endpoint URL', 'ai-commerce'),
    ));
    
    // Enable AI Personalization
    $wp_customize->add_setting('ai_personalization_enabled', array(
        'default'           => true,
        'sanitize_callback' => 'wp_validate_boolean',
    ));
    
    $wp_customize->add_control('ai_personalization_enabled', array(
        'label'   => __('Enable AI Personalization', 'ai-commerce'),
        'section' => 'ai_commerce_ai_settings',
        'type'    => 'checkbox',
    ));
    
    // Enable Cart Recovery
    $wp_customize->add_setting('ai_cart_recovery_enabled', array(
        'default'           => true,
        'sanitize_callback' => 'wp_validate_boolean',
    ));
    
    $wp_customize->add_control('ai_cart_recovery_enabled', array(
        'label'   => __('Enable AI Cart Recovery', 'ai-commerce'),
        'section' => 'ai_commerce_ai_settings',
        'type'    => 'checkbox',
    ));
    
    // Enable Chatbot
    $wp_customize->add_setting('ai_chatbot_enabled', array(
        'default'           => true,
        'sanitize_callback' => 'wp_validate_boolean',
    ));
    
    $wp_customize->add_control('ai_chatbot_enabled', array(
        'label'   => __('Enable AI Chatbot', 'ai-commerce'),
        'section' => 'ai_commerce_ai_settings',
        'type'    => 'checkbox',
    ));
    
    // Theme Colors Section
    $wp_customize->add_section('ai_commerce_colors', array(
        'title'    => __('Theme Colors', 'ai-commerce'),
        'priority' => 40,
    ));
    
    // Primary Color
    $wp_customize->add_setting('primary_color', array(
        'default'           => '#2563eb',
        'sanitize_callback' => 'sanitize_hex_color',
        'transport'         => 'postMessage',
    ));
    
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'primary_color', array(
        'label'   => __('Primary Color', 'ai-commerce'),
        'section' => 'ai_commerce_colors',
    )));
    
    // Secondary Color
    $wp_customize->add_setting('secondary_color', array(
        'default'           => '#7c3aed',
        'sanitize_callback' => 'sanitize_hex_color',
        'transport'         => 'postMessage',
    ));
    
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'secondary_color', array(
        'label'   => __('Secondary Color', 'ai-commerce'),
        'section' => 'ai_commerce_colors',
    )));
    
    // Accent Color
    $wp_customize->add_setting('accent_color', array(
        'default'           => '#f59e0b',
        'sanitize_callback' => 'sanitize_hex_color',
        'transport'         => 'postMessage',
    ));
    
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'accent_color', array(
        'label'   => __('Accent Color', 'ai-commerce'),
        'section' => 'ai_commerce_colors',
    )));
    
    // Layout Settings Section
    $wp_customize->add_section('ai_commerce_layout', array(
        'title'    => __('Layout Settings', 'ai-commerce'),
        'priority' => 50,
    ));
    
    // Container Width
    $wp_customize->add_setting('container_width', array(
        'default'           => '1280',
        'sanitize_callback' => 'absint',
        'transport'         => 'postMessage',
    ));
    
    $wp_customize->add_control('container_width', array(
        'label'       => __('Container Width (px)', 'ai-commerce'),
        'section'     => 'ai_commerce_layout',
        'type'        => 'number',
        'input_attrs' => array(
            'min'  => 960,
            'max'  => 1920,
            'step' => 10,
        ),
    ));
    
    // Products per row
    $wp_customize->add_setting('products_per_row', array(
        'default'           => '4',
        'sanitize_callback' => 'absint',
    ));
    
    $wp_customize->add_control('products_per_row', array(
        'label'   => __('Products per Row', 'ai-commerce'),
        'section' => 'ai_commerce_layout',
        'type'    => 'select',
        'choices' => array(
            '2' => '2',
            '3' => '3',
            '4' => '4',
            '5' => '5',
            '6' => '6',
        ),
    ));
    
    // Typography Section
    $wp_customize->add_section('ai_commerce_typography', array(
        'title'    => __('Typography', 'ai-commerce'),
        'priority' => 60,
    ));
    
    // Body Font
    $wp_customize->add_setting('body_font', array(
        'default'           => 'Inter',
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'postMessage',
    ));
    
    $wp_customize->add_control('body_font', array(
        'label'   => __('Body Font', 'ai-commerce'),
        'section' => 'ai_commerce_typography',
        'type'    => 'select',
        'choices' => array(
            'Inter'     => 'Inter',
            'Roboto'    => 'Roboto',
            'Open Sans' => 'Open Sans',
            'Lato'      => 'Lato',
            'Poppins'   => 'Poppins',
            'Montserrat' => 'Montserrat',
        ),
    ));
    
    // Base Font Size
    $wp_customize->add_setting('base_font_size', array(
        'default'           => '16',
        'sanitize_callback' => 'absint',
        'transport'         => 'postMessage',
    ));
    
    $wp_customize->add_control('base_font_size', array(
        'label'       => __('Base Font Size (px)', 'ai-commerce'),
        'section'     => 'ai_commerce_typography',
        'type'        => 'number',
        'input_attrs' => array(
            'min'  => 12,
            'max'  => 24,
            'step' => 1,
        ),
    ));
    
    // Footer Settings Section
    $wp_customize->add_section('ai_commerce_footer', array(
        'title'    => __('Footer Settings', 'ai-commerce'),
        'priority' => 70,
    ));
    
    // Footer About Text
    $wp_customize->add_setting('ai_commerce_footer_about', array(
        'default'           => 'Your trusted AI-powered e-commerce solution for personalized shopping experiences.',
        'sanitize_callback' => 'wp_kses_post',
    ));
    
    $wp_customize->add_control('ai_commerce_footer_about', array(
        'label'   => __('Footer About Text', 'ai-commerce'),
        'section' => 'ai_commerce_footer',
        'type'    => 'textarea',
    ));
    
    // Social Media Links
    $social_platforms = array('facebook', 'twitter', 'instagram', 'linkedin');
    
    foreach ($social_platforms as $platform) {
        $wp_customize->add_setting('ai_commerce_' . $platform . '_url', array(
            'default'           => '',
            'sanitize_callback' => 'esc_url_raw',
        ));
        
        $wp_customize->add_control('ai_commerce_' . $platform . '_url', array(
            'label'   => ucfirst($platform) . ' URL',
            'section' => 'ai_commerce_footer',
            'type'    => 'url',
        ));
    }
    
    // Performance Settings Section
    $wp_customize->add_section('ai_commerce_performance', array(
        'title'    => __('Performance Settings', 'ai-commerce'),
        'priority' => 80,
    ));
    
    // Enable Lazy Loading
    $wp_customize->add_setting('enable_lazy_loading', array(
        'default'           => true,
        'sanitize_callback' => 'wp_validate_boolean',
    ));
    
    $wp_customize->add_control('enable_lazy_loading', array(
        'label'       => __('Enable Lazy Loading', 'ai-commerce'),
        'section'     => 'ai_commerce_performance',
        'type'        => 'checkbox',
        'description' => __('Lazy load images for better performance', 'ai-commerce'),
    ));
    
    // Enable Preloading
    $wp_customize->add_setting('enable_preloading', array(
        'default'           => true,
        'sanitize_callback' => 'wp_validate_boolean',
    ));
    
    $wp_customize->add_control('enable_preloading', array(
        'label'       => __('Enable Resource Preloading', 'ai-commerce'),
        'section'     => 'ai_commerce_performance',
        'type'        => 'checkbox',
        'description' => __('Preload critical resources for faster page loads', 'ai-commerce'),
    ));
    
    // Cache Duration
    $wp_customize->add_setting('cache_duration', array(
        'default'           => '3600',
        'sanitize_callback' => 'absint',
    ));
    
    $wp_customize->add_control('cache_duration', array(
        'label'       => __('Cache Duration (seconds)', 'ai-commerce'),
        'section'     => 'ai_commerce_performance',
        'type'        => 'number',
        'description' => __('How long to cache AI recommendations', 'ai-commerce'),
        'input_attrs' => array(
            'min'  => 300,
            'max'  => 86400,
            'step' => 300,
        ),
    ));
}
add_action('customize_register', 'ai_commerce_customize_register');

/**
 * Render custom CSS based on Customizer settings
 */
function ai_commerce_customizer_css() {
    $primary_color = get_theme_mod('primary_color', '#2563eb');
    $secondary_color = get_theme_mod('secondary_color', '#7c3aed');
    $accent_color = get_theme_mod('accent_color', '#f59e0b');
    $container_width = get_theme_mod('container_width', '1280');
    $body_font = get_theme_mod('body_font', 'Inter');
    $base_font_size = get_theme_mod('base_font_size', '16');
    
    ?>
    <style type="text/css">
        :root {
            --primary-color: <?php echo esc_attr($primary_color); ?>;
            --secondary-color: <?php echo esc_attr($secondary_color); ?>;
            --accent-color: <?php echo esc_attr($accent_color); ?>;
            --container-width: <?php echo esc_attr($container_width); ?>px;
            --body-font: '<?php echo esc_attr($body_font); ?>', sans-serif;
            --base-font-size: <?php echo esc_attr($base_font_size); ?>px;
        }
        
        body {
            font-family: var(--body-font);
            font-size: var(--base-font-size);
        }
        
        .ai-container {
            max-width: var(--container-width);
        }
        
        .ai-btn-primary {
            background-color: var(--primary-color);
        }
        
        .ai-btn-secondary {
            background-color: var(--secondary-color);
        }
        
        .ai-product-price,
        .ai-logo,
        .ai-nav-link:hover {
            color: var(--primary-color);
        }
        
        .ai-btn-outline {
            border-color: var(--primary-color);
            color: var(--primary-color);
        }
        
        .ai-btn-outline:hover {
            background-color: var(--primary-color);
        }
        
        .ai-form-input:focus,
        .ai-form-select:focus,
        .ai-form-textarea:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(<?php echo esc_attr(ai_commerce_hex_to_rgb($primary_color)); ?>, 0.1);
        }
        
        .ai-chat-button {
            background-color: var(--primary-color);
        }
        
        <?php if (get_theme_mod('products_per_row', '4') !== '4') : ?>
        .ai-product-grid {
            grid-template-columns: repeat(<?php echo esc_attr(get_theme_mod('products_per_row', '4')); ?>, 1fr);
        }
        <?php endif; ?>
    </style>
    <?php
}
add_action('wp_head', 'ai_commerce_customizer_css');

/**
 * Convert hex color to RGB
 */
function ai_commerce_hex_to_rgb($hex) {
    $hex = str_replace('#', '', $hex);
    
    if (strlen($hex) == 3) {
        $r = hexdec(substr($hex, 0, 1) . substr($hex, 0, 1));
        $g = hexdec(substr($hex, 1, 1) . substr($hex, 1, 1));
        $b = hexdec(substr($hex, 2, 1) . substr($hex, 2, 1));
    } else {
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
    }
    
    return "$r, $g, $b";
}

/**
 * Binds JS handlers to make Theme Customizer preview reload changes asynchronously.
 */
function ai_commerce_customize_preview_js() {
    wp_enqueue_script('ai-commerce-customizer', AI_COMMERCE_ASSETS . '/js/customizer.js', array('customize-preview'), AI_COMMERCE_VERSION, true);
}