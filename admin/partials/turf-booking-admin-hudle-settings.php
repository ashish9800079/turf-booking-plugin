<?php
/**
 * Admin settings page for Hudle Integration
 *
 * @link       https://code4sh.com/
 * @since      1.0.0
 *
 * @package    Turf_Booking
 * @subpackage Turf_Booking/admin/partials
 */

// If this file is called directly, abort.
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1><?php _e('Hudle Integration Settings', 'turf-booking'); ?></h1>
    
    <form method="post" action="options.php">
        <?php
        settings_fields('tb_hudle_settings');
        do_settings_sections('tb_hudle_settings');
        submit_button();
        ?>
    </form>
</div>