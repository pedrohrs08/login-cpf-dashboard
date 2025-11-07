<?php
/**
 * Plugin Name: CPF Login Travel
 * Description: This plugin is a test - adds the ability to log in to WordPress using the user's CPF (Individual Taxpayer Registry). Only users previously registered by the administrator will be able to access the restricted area. After login validation, the user will be redirected to the personalized Dashboard, which displays their flight information.
 * Version: 2.2.0
 * Author: Pedro Soares 
 * Author URI: https://github.com/pedrohrs08/cpf-login-travel
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'LOGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'LOGIN_URL',  plugin_dir_url( __FILE__ ) );

require_once LOGIN_PATH . 'includes/cpf-travel-db.php';
require_once LOGIN_PATH . 'includes/cpf-travel-functions.php';
require_once LOGIN_PATH . 'includes/cpf-travel-shortcodes.php';
require_once LOGIN_PATH . 'includes/cpf-travel-admin.php';
require_once LOGIN_PATH . 'includes/class-cpf-login.php';
require_once LOGIN_PATH . 'includes/cpf-travel-endpoints.php';

register_activation_hook( __FILE__, 'cpf_travel_install' );
function cpf_travel_install() {
    cpf_travel_create_table();

    if ( ! wp_next_scheduled( 'cpf_travel_sync_hook' ) ) {
        wp_schedule_event( time(), 'hourly', 'cpf_travel_sync_hook' );
    }
}

register_deactivation_hook( __FILE__, 'cpf_travel_deactivate' );
function cpf_travel_deactivate() {
    $timestamp = wp_next_scheduled( 'cpf_travel_sync_hook' );
    if ( $timestamp ) wp_unschedule_event( $timestamp, 'cpf_travel_sync_hook' );
}

add_action( 'cpf_travel_sync_hook', 'cpf_travel_sync_users' );

add_action( 'admin_enqueue_scripts', 'cpf_travel_admin_scripts' );

function cpf_travel_enqueue_custom_styles() {
    wp_enqueue_style(
        'cpf-travel-custom-style',
        LOGIN_URL . 'assets/css/custom-style.css'
    );
}
add_action( 'wp_enqueue_scripts', 'cpf_travel_enqueue_custom_styles' );
function cpf_travel_admin_scripts($hook) {
    if ($hook !== 'toplevel_page_cpf-travel-bookings') {
        return;
    }
    wp_enqueue_script('cpf-travel-admin', LOGIN_URL . 'assets/js/admin.js', ['jquery'], '1.0', true);
    wp_enqueue_style('cpf-travel-admin-style', LOGIN_URL . 'assets/css/admin-style.css');
}
