<?php
/*
Plugin Name: taraz group plgugins
Plugin URI: https://tarazgroup.com/
Description: 
Version: 1.0
Author:Aryan Mostafavi
Author URI: https://your-website.com/
License: 
*/

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Add a link to the settings page on the plugins listing screen
function setting_page($links) {
    $settings_link = '<a href="پنل تنظیمات">تنظیمات</a>';
    array_push($links, $settings_link);
    return $links;
}
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'setting_page');

// Register the plugin settings
function taraz_plugin_register_settings() {
    add_settings_section('taraz_plugin', 'A to Z Plugin Settings', '', 't_plugin');
    add_settings_field('taraz_plugin_token', 'Token', 'taraz_plugin_token_callback', 't_plugin', 'taraz_plugin');
    register_setting('t_plugin', 'taraz_plugin_token');
}
add_action('admin_init', 'taraz_plugin_register_settings');

// Render the token input field
function taraz_plugin_token_callback() {
    $token = get_option('taraz_plugin_token');
    echo '<input type="text" name="taraz_plugin_token" value="' . esc_attr($token) . '" />';
}

// Create the plugin settings page
function a_to_z_plugin_settings_page() {
    ?>
    <div class="wrap">
        <h1>A to Z Plugin Settings</h1>
        <form method="post" action="options.php">
            <?php settings_fields('t_plugin'); ?>
            <?php do_settings_sections('t_plugin'); ?>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}
function a_to_z_plugin_add_settings_page() {
    add_options_page('A to Z Plugin Settings', 'A to Z Plugin', 'manage_options', 't_plugin', 'a_to_z_plugin_settings_page');
}
add_action('admin_menu', 'a_to_z_plugin_add_settings_page');


// Send sales to the URL
function a_to_z_plugin_send_sales() {
    $token = get_option('taraz_plugin_token');
    $sales_url = 'http://127.0.0.1:8080/tws/sale';

    // Get the latest order
    $args = array(
        'limit' => 1,
        'status' => 'completed',
        'orderby' => 'date',
        'order' => 'DESC',
    );
    $latest_order = wc_get_orders($args);

    // Prepare the data to send
    $data = array(
        'token' => $token,
        'sales' => $latest_order,
    );

    // Send the data
    wp_remote_post($sales_url, array(
        'headers' => array('Content-Type' => 'application/json'),
        'body' => json_encode($data),
    ));
}
add_action('woocommerce_thankyou', 'a_to_z_plugin_send_sales');


// Retrieve product inventory from the URL
function a_to_z_plugin_retrieve_inventory() {
    $token = get_option('taraz_plugin_token');
    $inventory_url = 'http://127.0.0.1:8080/tws/inventory';

    // Prepare the data to send
    $data = array(
        'token' => $token,
    );

    // Retrieve the inventory
    $response = wp_remote_post($inventory_url, array(
        'headers' => array('Content-Type' => 'application/json'),
        'body' => json_encode($data),
    ));

    // Process the response
    if (!is_wp_error($response)) {
        $body = wp_remote_retrieve_body($response);
        $inventory = json_decode($body, true);

        // Process the inventory data
        // ...
    }
}
add_action('wp_loaded', 'a_to_z_plugin_retrieve_inventory');
