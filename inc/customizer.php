<?php
/**
 * Theme Customizer
 *
 * @package AI_Woo_Theme
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Add postMessage support for site title and description for the Theme Customizer.
 */
function ai_woo_customize_register($wp_customize) {
    
    // Add custom sections
    $wp_customize->add_section('ai_woo_general', array(
        'title'    => __('General Settings', 'ai-woo-theme'),
        'priority' => 30,
    ));
    
    $wp_customize->add_section('ai_woo_colors', array(
        'title'    => __('Colors', 'ai-woo-theme'),
        'priority' => 40,
    ));
    
    $wp_customize->add_section('ai_woo_typography', array(
        'title'    => __('Typography', 'ai-woo-theme'),
        'priority' => 50,
    ));
    
    $wp_customize->add_section('ai_woo_layout', array(
        'title'    => __('Layout Options', 'ai-woo-theme'),
        'priority' => 60,
    ));
    
    $wp_customize->add_section('ai_woo_homepage', array(
        'title'    => __('Homepage Settings', 'ai-woo-theme'),
        'priority' => 70,
    ));
    
    $wp_customize->add_section('ai_woo_ai_settings', array(
        'title'    => __('AI Features', 'ai-woo-theme'),
        'priority' => 80,
    ));
    
    $wp_customize->add_section('ai_woo_social', array(
        'title'    => __('Social Media', 'ai-woo-theme'),
        'priority' => 90,
    ));
    
    $wp_customize->add_section('ai_woo_performance', array(
        'title'    => __('Performance & SEO', 'ai-woo-theme'),
        'priority' => 100,
    ));

    // GENERAL SETTINGS
    
    // Site Description
    $wp_customize->add_setting('ai_woo_site_description', array(
        'default'           => get_bloginfo('description'),
        'sanitize_callback' => 'sanitize_textarea_field',
        'transport'         => 'postMessage',
    ));
    
    $wp_customize->add_control('ai_woo_site_description', array(
        'label'   => __('Site Description', 'ai-woo-theme'),
        'section' => 'ai_woo_general',
        'type'    => 'textarea',
    ));

    // COLORS
    
    // Primary Color
    $wp_customize->add_setting('ai_woo_primary_color', array(
        'default'           => '#2563eb',
        'sanitize_callback' => 'sanitize_hex_color',
        'transport'         => 'postMessage',
    ));
    
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'ai_woo_primary_color', array(
        'label'   => __('Primary Color', 'ai-woo-theme'),
        'section' => 'ai_woo_colors',
    )));
    
    // Secondary Color
    $wp_customize->add_setting('ai_woo_secondary_color', array(
        'default'           => '#1e40af',
        'sanitize_callback' => 'sanitize_hex_color',
        'transport'         => 'postMessage',
    ));
    
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'ai_woo_secondary_color', array(
        'label'   => __('Secondary Color', 'ai-woo-theme'),
        'section' => 'ai_woo_colors',
    )));
    
    // Accent Color
    $wp_customize->add_setting('ai_woo_accent_color', array(
        'default'           => '#f59e0b',
        'sanitize_callback' => 'sanitize_hex_color',
        'transport'         => 'postMessage',
    ));
    
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'ai_woo_accent_color', array(
        'label'   => __('Accent Color', 'ai-woo-theme'),
        'section' => 'ai_woo_colors',
    )));

    // TYPOGRAPHY
    
    // Font Family
    $wp_customize->add_setting('ai_woo_font_family', array(
        'default'           => 'Inter',
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'postMessage',
    ));
    
    $wp_customize->add_control('ai_woo_font_family', array(
        'label'   => __('Font Family', 'ai-woo-theme'),
        'section' => 'ai_woo_typography',
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
    
    // Font Size
    $wp_customize->add_setting('ai_woo_font_size', array(
        'default'           => '16',
        'sanitize_callback' => 'absint',
        'transport'         => 'postMessage',
    ));
    
    $wp_customize->add_control('ai_woo_font_size', array(
        'label'   => __('Base Font Size (px)', 'ai-woo-theme'),
        'section' => 'ai_woo_typography',
        'type'    => 'number',
        'input_attrs' => array(
            'min'  => 12,
            'max'  => 24,
            'step' => 1,
        ),
    ));

    // LAYOUT OPTIONS
    
    // Layout Style
    $wp_customize->add_setting('ai_woo_layout_style', array(
        'default'           => 'default',
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'refresh',
    ));
    
    $wp_customize->add_control('ai_woo_layout_style', array(
        'label'   => __('Layout Style', 'ai-woo-theme'),
        'section' => 'ai_woo_layout',
        'type'    => 'select',
        'choices' => array(
            'default'   => __('Default', 'ai-woo-theme'),
            'boxed'     => __('Boxed', 'ai-woo-theme'),
            'wide'      => __('Wide', 'ai-woo-theme'),
            'fullwidth' => __('Full Width', 'ai-woo-theme'),
        ),
    ));
    
    // Container Width
    $wp_customize->add_setting('ai_woo_container_width', array(
        'default'           => '1200',
        'sanitize_callback' => 'absint',
        'transport'         => 'postMessage',
    ));
    
    $wp_customize->add_control('ai_woo_container_width', array(
        'label'   => __('Container Width (px)', 'ai-woo-theme'),
        'section' => 'ai_woo_layout',
        'type'    => 'number',
        'input_attrs' => array(
            'min'  => 960,
            'max'  => 1920,
            'step' => 10,
        ),
    ));

    // HOMEPAGE SETTINGS
    
    // Hero Title
    $wp_customize->add_setting('ai_woo_hero_title', array(
        'default'           => __('Welcome to Our AI-Powered Store', 'ai-woo-theme'),
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'postMessage',
    ));
    
    $wp_customize->add_control('ai_woo_hero_title', array(
        'label'   => __('Hero Title', 'ai-woo-theme'),
        'section' => 'ai_woo_homepage',
        'type'    => 'text',
    ));
    
    // Hero Description
    $wp_customize->add_setting('ai_woo_hero_description', array(
        'default'           => __('Discover personalized products with our AI-powered recommendations', 'ai-woo-theme'),
        'sanitize_callback' => 'sanitize_textarea_field',
        'transport'         => 'postMessage',
    ));
    
    $wp_customize->add_control('ai_woo_hero_description', array(
        'label'   => __('Hero Description', 'ai-woo-theme'),
        'section' => 'ai_woo_homepage',
        'type'    => 'textarea',
    ));
    
    // Hero Image
    $wp_customize->add_setting('ai_woo_hero_image', array(
        'default'           => '',
        'sanitize_callback' => 'esc_url_raw',
    ));
    
    $wp_customize->add_control(new WP_Customize_Image_Control($wp_customize, 'ai_woo_hero_image', array(
        'label'   => __('Hero Image', 'ai-woo-theme'),
        'section' => 'ai_woo_homepage',
    )));

    // AI FEATURES
    
    // Enable AI
    $wp_customize->add_setting('ai_woo_enable_ai', array(
        'default'           => true,
        'sanitize_callback' => 'wp_validate_boolean',
        'transport'         => 'refresh',
    ));
    
    $wp_customize->add_control('ai_woo_enable_ai', array(
        'label'   => __('Enable AI Features', 'ai-woo-theme'),
        'section' => 'ai_woo_ai_settings',
        'type'    => 'checkbox',
    ));
    
    // AI API Key
    $wp_customize->add_setting('ai_woo_ai_api_key', array(
        'default'           => '',
        'sanitize_callback' => 'sanitize_text_field',
    ));
    
    $wp_customize->add_control('ai_woo_ai_api_key', array(
        'label'       => __('AI API Key', 'ai-woo-theme'),
        'description' => __('Enter your AI service API key for personalized recommendations', 'ai-woo-theme'),
        'section'     => 'ai_woo_ai_settings',
        'type'        => 'password',
    ));
    
    // Enable Cart Recovery
    $wp_customize->add_setting('ai_woo_enable_cart_recovery', array(
        'default'           => true,
        'sanitize_callback' => 'wp_validate_boolean',
        'transport'         => 'refresh',
    ));
    
    $wp_customize->add_control('ai_woo_enable_cart_recovery', array(
        'label'   => __('Enable Cart Abandonment Recovery', 'ai-woo-theme'),
        'section' => 'ai_woo_ai_settings',
        'type'    => 'checkbox',
    ));

    // SOCIAL MEDIA
    
    // Show Social Links
    $wp_customize->add_setting('ai_woo_show_social', array(
        'default'           => true,
        'sanitize_callback' => 'wp_validate_boolean',
    ));
    
    $wp_customize->add_control('ai_woo_show_social', array(
        'label'   => __('Show Social Media Links', 'ai-woo-theme'),
        'section' => 'ai_woo_social',
        'type'    => 'checkbox',
    ));
    
    // Facebook URL
    $wp_customize->add_setting('ai_woo_facebook_url', array(
        'default'           => '',
        'sanitize_callback' => 'esc_url_raw',
    ));
    
    $wp_customize->add_control('ai_woo_facebook_url', array(
        'label'   => __('Facebook URL', 'ai-woo-theme'),
        'section' => 'ai_woo_social',
        'type'    => 'url',
    ));
    
    // Twitter URL
    $wp_customize->add_setting('ai_woo_twitter_url', array(
        'default'           => '',
        'sanitize_callback' => 'esc_url_raw',
    ));
    
    $wp_customize->add_control('ai_woo_twitter_url', array(
        'label'   => __('Twitter URL', 'ai-woo-theme'),
        'section' => 'ai_woo_social',
        'type'    => 'url',
    ));
    
    // Instagram URL
    $wp_customize->add_setting('ai_woo_instagram_url', array(
        'default'           => '',
        'sanitize_callback' => 'esc_url_raw',
    ));
    
    $wp_customize->add_control('ai_woo_instagram_url', array(
        'label'   => __('Instagram URL', 'ai-woo-theme'),
        'section' => 'ai_woo_social',
        'type'    => 'url',
    ));
    
    // LinkedIn URL
    $wp_customize->add_setting('ai_woo_linkedin_url', array(
        'default'           => '',
        'sanitize_callback' => 'esc_url_raw',
    ));
    
    $wp_customize->add_control('ai_woo_linkedin_url', array(
        'label'   => __('LinkedIn URL', 'ai-woo-theme'),
        'section' => 'ai_woo_social',
        'type'    => 'url',
    ));

    // PERFORMANCE & SEO
    
    // Google Analytics ID
    $wp_customize->add_setting('ai_woo_google_analytics_id', array(
        'default'           => '',
        'sanitize_callback' => 'sanitize_text_field',
    ));
    
    $wp_customize->add_control('ai_woo_google_analytics_id', array(
        'label'   => __('Google Analytics ID', 'ai-woo-theme'),
        'section' => 'ai_woo_performance',
        'type'    => 'text',
    ));
    
    // Facebook Pixel ID
    $wp_customize->add_setting('ai_woo_facebook_pixel_id', array(
        'default'           => '',
        'sanitize_callback' => 'sanitize_text_field',
    ));
    
    $wp_customize->add_control('ai_woo_facebook_pixel_id', array(
        'label'   => __('Facebook Pixel ID', 'ai-woo-theme'),
        'section' => 'ai_woo_performance',
        'type'    => 'text',
    ));
    
    // Enable PWA
    $wp_customize->add_setting('ai_woo_enable_pwa', array(
        'default'           => false,
        'sanitize_callback' => 'wp_validate_boolean',
    ));
    
    $wp_customize->add_control('ai_woo_enable_pwa', array(
        'label'       => __('Enable PWA Features', 'ai-woo-theme'),
        'description' => __('Enable Progressive Web App features for better mobile experience', 'ai-woo-theme'),
        'section'     => 'ai_woo_performance',
        'type'        => 'checkbox',
    ));
    
    // Show Cookie Consent
    $wp_customize->add_setting('ai_woo_show_cookie_consent', array(
        'default'           => true,
        'sanitize_callback' => 'wp_validate_boolean',
    ));
    
    $wp_customize->add_control('ai_woo_show_cookie_consent', array(
        'label'   => __('Show Cookie Consent Banner', 'ai-woo-theme'),
        'section' => 'ai_woo_performance',
        'type'    => 'checkbox',
    ));
    
    // Cookie Consent Text
    $wp_customize->add_setting('ai_woo_cookie_text', array(
        'default'           => __('We use cookies to enhance your browsing experience and provide personalized content. By continuing to use our site, you agree to our use of cookies.', 'ai-woo-theme'),
        'sanitize_callback' => 'sanitize_textarea_field',
    ));
    
    $wp_customize->add_control('ai_woo_cookie_text', array(
        'label'   => __('Cookie Consent Text', 'ai-woo-theme'),
        'section' => 'ai_woo_performance',
        'type'    => 'textarea',
    ));
    
    // Custom CSS
    $wp_customize->add_setting('ai_woo_custom_css', array(
        'default'           => '',
        'sanitize_callback' => 'wp_strip_all_tags',
    ));
    
    $wp_customize->add_control('ai_woo_custom_css', array(
        'label'   => __('Custom CSS', 'ai-woo-theme'),
        'section' => 'ai_woo_performance',
        'type'    => 'textarea',
    ));
    
    // Show Powered By
    $wp_customize->add_setting('ai_woo_show_powered_by', array(
        'default'           => true,
        'sanitize_callback' => 'wp_validate_boolean',
    ));
    
    $wp_customize->add_control('ai_woo_show_powered_by', array(
        'label'   => __('Show "Powered by AI" in Footer', 'ai-woo-theme'),
        'section' => 'ai_woo_performance',
        'type'    => 'checkbox',
    ));

    // Bind JS handlers to instantly live-preview changes
    $wp_customize->get_setting('blogname')->transport         = 'postMessage';
    $wp_customize->get_setting('blogdescription')->transport  = 'postMessage';
    $wp_customize->get_setting('header_textcolor')->transport = 'postMessage';

    if (isset($wp_customize->selective_refresh)) {
        $wp_customize->selective_refresh->add_partial('blogname', array(
            'selector'        => '.site-title a',
            'render_callback' => 'ai_woo_customize_partial_blogname',
        ));
        $wp_customize->selective_refresh->add_partial('blogdescription', array(
            'selector'        => '.site-description',
            'render_callback' => 'ai_woo_customize_partial_blogdescription',
        ));
    }
}
add_action('customize_register', 'ai_woo_customize_register');

/**
 * Render the site title for the selective refresh partial.
 */
function ai_woo_customize_partial_blogname() {
    bloginfo('name');
}

/**
 * Render the site tagline for the selective refresh partial.
 */
function ai_woo_customize_partial_blogdescription() {
    bloginfo('description');
}

/**
 * Binds JS handlers to make Theme Customizer preview reload changes asynchronously.
 */
function ai_woo_customize_preview_js() {
    wp_enqueue_script('ai-woo-customizer', AI_WOO_THEME_URL . '/assets/js/customizer.js', array('customize-preview'), AI_WOO_THEME_VERSION, true);
}
add_action('customize_preview_init', 'ai_woo_customize_preview_js');

/**
 * Customizer Controls JS
 */
function ai_woo_customize_controls_js() {
    wp_enqueue_script('ai-woo-customizer-controls', AI_WOO_THEME_URL . '/assets/js/customizer-controls.js', array('customize-controls'), AI_WOO_THEME_VERSION, true);
}
add_action('customize_controls_enqueue_scripts', 'ai_woo_customize_controls_js');