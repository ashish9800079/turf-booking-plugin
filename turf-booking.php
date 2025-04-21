<?php
/**
 * Plugin Name: Turf Booking System
 * Plugin URI: https://code4sh.com/
 * Description: A comprehensive turf booking system with court management, booking calendar, user dashboard, and Razorpay payment integration.
 * Version: 1.0.0
 * Author: 4sh
 * Author URI: https://code4sh.com
 * Text Domain: turf-booking
 * Domain Path: /languages
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Define plugin constants
define('TURF_BOOKING_VERSION', '1.0.0');
define('TURF_BOOKING_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('TURF_BOOKING_PLUGIN_URL', plugin_dir_url(__FILE__));
define('TURF_BOOKING_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * The code that runs during plugin activation.
 */
function activate_turf_booking() {
    require_once TURF_BOOKING_PLUGIN_DIR . 'includes/class-turf-booking-activator.php';
    Turf_Booking_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 */
function deactivate_turf_booking() {
    require_once TURF_BOOKING_PLUGIN_DIR . 'includes/class-turf-booking-deactivator.php';
    Turf_Booking_Deactivator::deactivate();
}

register_activation_hook(__FILE__, 'activate_turf_booking');
register_deactivation_hook(__FILE__, 'deactivate_turf_booking');

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require TURF_BOOKING_PLUGIN_DIR . 'includes/class-turf-booking.php';

/**
 * Begins execution of the plugin.
 */
function run_turf_booking() {
    $plugin = new Turf_Booking();
    $plugin->run();
}
run_turf_booking();