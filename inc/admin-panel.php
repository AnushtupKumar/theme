<?php
/**
 * Admin Panel Functions
 *
 * @package AI_WooCommerce_Theme
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Add admin menu page
 */
function ai_woo_add_admin_menu() {
    add_theme_page(
        __('AI Commerce Theme Options', 'ai-woo-theme'),
        __('Theme Options', 'ai-woo-theme'),
        'manage_options',
        'ai-woo-theme-options',
        'ai_woo_theme_options_page'
    );
}
add_action('admin_menu', 'ai_woo_add_admin_menu');

/**
 * Theme options page callback
 */
function ai_woo_theme_options_page() {
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        
        <form action="options.php" method="post">
            <?php
            settings_fields('ai_woo_theme_options');
            do_settings_sections('ai_woo_theme_options');
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

/**
 * Register theme settings
 */
function ai_woo_register_settings() {
    // Register settings
    register_setting('ai_woo_theme_options', 'ai_woo_options');
    
    // Add settings section
    add_settings_section(
        'ai_woo_general_section',
        __('General Settings', 'ai-woo-theme'),
        'ai_woo_general_section_callback',
        'ai_woo_theme_options'
    );
    
    // Add settings fields
    add_settings_field(
        'ai_woo_enable_ai',
        __('Enable AI Features', 'ai-woo-theme'),
        'ai_woo_enable_ai_callback',
        'ai_woo_theme_options',
        'ai_woo_general_section'
    );
}
add_action('admin_init', 'ai_woo_register_settings');

/**
 * General section callback
 */
function ai_woo_general_section_callback() {
    echo '<p>' . __('Configure general theme settings.', 'ai-woo-theme') . '</p>';
}

/**
 * Enable AI field callback
 */
function ai_woo_enable_ai_callback() {
    $options = get_option('ai_woo_options');
    $checked = isset($options['enable_ai']) ? checked($options['enable_ai'], 1, false) : '';
    echo '<input type="checkbox" name="ai_woo_options[enable_ai]" value="1" ' . $checked . ' />';
    echo '<label>' . __('Enable AI-powered features', 'ai-woo-theme') . '</label>';
}

/**
 * Enqueue admin styles and scripts
 */
function ai_woo_admin_enqueue_scripts($hook) {
    if ('appearance_page_ai-woo-theme-options' !== $hook) {
        return;
    }
    
    wp_enqueue_style('ai-woo-admin', get_template_directory_uri() . '/assets/css/admin.css', array(), AI_WOO_THEME_VERSION);
    wp_enqueue_script('ai-woo-admin', get_template_directory_uri() . '/assets/js/admin.js', array('jquery'), AI_WOO_THEME_VERSION, true);
}
add_action('admin_enqueue_scripts', 'ai_woo_admin_enqueue_scripts');