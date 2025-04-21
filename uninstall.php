<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @link       https://yourwebsite.com
 * @since      1.0.0
 *
 * @package    Turf_Booking
 */

// If uninstall not called from WordPress, then exit.
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Define global variables
global $wpdb;
$plugin_options = array(
    'tb_general_settings',
    'tb_payment_settings',
    'tb_email_settings',
    'tb_page_settings'
);

// Check if we need to remove all data
$remove_all_data = get_option('tb_remove_data_on_uninstall', false);

if ($remove_all_data) {
    // Remove custom tables
    $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}tb_booking_slots");
    $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}tb_booking_slot_history");
    $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}tb_payments");
    
    // Get all courts
    $courts = get_posts(array(
        'post_type' => 'tb_court',
        'numberposts' => -1,
        'post_status' => 'any',
        'fields' => 'ids',
    ));
    
    // Delete all courts and their meta
    foreach ($courts as $court_id) {
        wp_delete_post($court_id, true);
    }
    
    // Get all bookings
    $bookings = get_posts(array(
        'post_type' => 'tb_booking',
        'numberposts' => -1,
        'post_status' => 'any',
        'fields' => 'ids',
    ));
    
    // Delete all bookings and their meta
    foreach ($bookings as $booking_id) {
        wp_delete_post($booking_id, true);
    }
    
    // Delete custom taxonomies terms
    $taxonomies = array('sport_type', 'location', 'facility');
    
    foreach ($taxonomies as $taxonomy) {
        $terms = get_terms(array(
            'taxonomy' => $taxonomy,
            'hide_empty' => false,
            'fields' => 'ids',
        ));
        
        foreach ($terms as $term_id) {
            wp_delete_term($term_id, $taxonomy);
        }
    }
    
    // Delete options
    foreach ($plugin_options as $option) {
        delete_option($option);
    }
    
    // Delete the option that stores whether to remove data
    delete_option('tb_remove_data_on_uninstall');
}