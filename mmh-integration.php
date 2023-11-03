<?php
/*
Plugin Name: MMH Woocommerce integration
Description: Update and sync products from MMH
Version: 1.0
Author: Ali Almazawi
*/

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

require_once(plugin_dir_path(__FILE__) . 'classes/Log.php');
require_once(plugin_dir_path(__FILE__) . 'classes/ProductHandler.php');

function MMH_admin_page()
{
    wp_enqueue_style('admin-styles', plugin_dir_url(__FILE__) . 'assets/css/admin-styles.css');
    wp_enqueue_script('functions', plugin_dir_url(__FILE__) . 'assets/js/functions.js', array('jquery'), '1.0', true);
    echo '<div class="wrap">';

    include(plugin_dir_path(__FILE__) . 'assets/html/admin-main.php');

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
        'Submenu Page 1',
        'Submenu Page 1',
        'manage_options',
        'your-submenu-1-slug',
        'your_submenu_page_1_callback'
    );

    add_submenu_page(
        'mmh-integration', // Parent menu slug
        'Submenu Page 2',
        'Submenu Page 2',
        'manage_options',
        'your-submenu-2-slug',
        'your_submenu_page_2_callback'
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
