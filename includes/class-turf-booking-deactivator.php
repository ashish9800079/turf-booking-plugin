<?php
/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 */
class Turf_Booking_Deactivator {

    /**
     * Run tasks during plugin deactivation.
     *
     * @since    1.0.0
     */
    public static function deactivate() {
        // Clear any scheduled events
        wp_clear_scheduled_hook('tb_daily_events');
        
        // Flush rewrite rules to remove any custom post type rules
        flush_rewrite_rules();
        
        // Any additional cleanup if needed
    }
}