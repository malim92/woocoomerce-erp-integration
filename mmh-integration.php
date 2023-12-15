<?php
/*
Plugin Name: MMH Woocommerce integration
Description: Update and sync products from MMH
Version: 1.0
Author: Ali Almazawi
*/

// ini_set('display_errors', '1');
// ini_set('display_startup_errors', '1');
// error_reporting(E_ALL);

if (!defined('ABSPATH')) {
    exit;
} // Exit if accessed directly

require_once(plugin_dir_path(__FILE__) . 'classes/Log.php');
require_once(plugin_dir_path(__FILE__) . 'classes/ProductHandler.php');
require_once(plugin_dir_path(__FILE__) . 'classes/ImageHandler.php');
require_once(plugin_dir_path(__FILE__) . 'classes/AttributeHandler.php');
require_once(plugin_dir_path(__FILE__) . 'classes/BrandHandler.php');
require_once(plugin_dir_path(__FILE__) . 'classes/StockHandler.php');

function activate_mmh_integration_plugin()
{
    wp_schedule_event(time(), 'hourly', 'mmh_product_import_cron');
}
register_activation_hook(__FILE__, 'activate_mmh_integration_plugin');

// Unschedule the cron event on plugin deactivation
function deactivate_mmh_integration_plugin()
{
    wp_clear_scheduled_hook('mmh_product_import_cron');
}
register_deactivation_hook(__FILE__, 'deactivate_mmh_integration_plugin');

// Hook the product import function to the scheduled cron event
add_action('mmh_product_import_cron', 'run_product_import_cron');


function MMH_admin_page()
{
    wp_enqueue_style('admin-styles', plugin_dir_url(__FILE__) . 'assets/css/admin-styles.css');
    wp_enqueue_script('functions', plugin_dir_url(__FILE__) . 'assets/js/functions.js', array('jquery'), '1.0', true);
    echo '<div class="wrap">';

    include(plugin_dir_path(__FILE__) . 'assets/html/admin-main.php');

    echo '</div>';
}

function stock_update_callback()
{
    wp_enqueue_style('admin-styles', plugin_dir_url(__FILE__) . 'assets/css/admin-styles.css');
    wp_enqueue_script('functions', plugin_dir_url(__FILE__) . 'assets/js/functions.js', array('jquery'), '1.0', true);
    echo '<div class="wrap">';

    include(plugin_dir_path(__FILE__) . 'assets/html/stock.php');

    echo '</div>';
}

function mmh_integration_menu()
{
    add_menu_page(
        'MMH Integration',
        'MMH Integration',
        'manage_options',
        'mmh-integration',
        'MMH_admin_page'
    );

    add_submenu_page(
        'mmh-integration', // Parent menu slug
        'Stock update', //$page_title
        'Stock update',//menu_title
        'manage_options',//capability
        'stock-update', //menu_slug
        'stock_update_callback' //callback 
    );

}
add_action('admin_menu', 'mmh_integration_menu');


function your_submenu_page_1_callback()
{
    echo '<div class="wrap">';
    echo '<h2>Submenu Page 1</h2>';
    // Add your content for the first submenu page.
    echo '</div>';
}

function your_submenu_page_2_callback()
{
    echo '<div class="wrap">';
    echo '<h2>Submenu Page 2</h2>';
    // Add your content for the second submenu page.
    echo '</div>';
}
