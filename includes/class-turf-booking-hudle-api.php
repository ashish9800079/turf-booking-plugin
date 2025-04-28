<?php
/**
 * Hudle API Integration Class
 */
class Turf_Booking_Hudle_API {
    /**
     * API Base URL
     */
    private $api_base_url = 'https://webhook.hudle.in/hook/v1';
    
    /**
     * API Token
     */
    private $api_token;
    
    /**
     * Venue ID
     */
    private $venue_id;
    
    /**
     * Debug mode
     */
    private $debug_mode = false;
    
    /**
     * Constructor
     */
    public function __construct() {
        // Load settings
        $this->api_token = get_option('tb_hudle_api_token', '');
        $this->venue_id = get_option('tb_hudle_venue_id', '');
        $this->debug_mode = get_option('tb_hudle_debug_mode', false);
        
        // Hook into booking creation
        add_action('tb_after_booking_confirmed', array($this, 'sync_booking_to_hudle'), 10, 1);
        
        // Add AJAX handlers for checking slot availability
        add_action('wp_ajax_check_hudle_slot_availability', array($this, 'check_slot_availability_ajax'));
        add_action('wp_ajax_nopriv_check_hudle_slot_availability', array($this, 'check_slot_availability_ajax'));
    }
    
    /**
     * Check if a court has Hudle integration
     */
    public function court_has_hudle_integration($court_id) {
        $facility_id = get_post_meta($court_id, '_tb_hudle_facility_id', true);
        return !empty($facility_id) && !empty($this->venue_id) && !empty($this->api_token);
    }
    
    /**
     * Get slots from Hudle
     *
     * @param int $court_id
     * @param string $date Date in Y-m-d format
     * @return array|WP_Error
     */
    public function get_slots($court_id, $date) {
        if (!$this->court_has_hudle_integration($court_id)) {
            return new WP_Error('integration_missing', __('This court is not integrated with Hudle', 'turf-booking'));
        }
        
        $facility_id = get_post_meta($court_id, '_tb_hudle_facility_id', true);
        
        $url = sprintf(
            '%s/venues/%s/facilities/%s/slots?date=%s',
            $this->api_base_url,
            $this->venue_id,
            $facility_id,
            $date
        );
        
        $response = wp_remote_get($url, array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->api_token
            )
        ));
        
        if (is_wp_error($response)) {
            $this->log_error('Error fetching slots: ' . $response->get_error_message());
            return $response;
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        $status_code = wp_remote_retrieve_response_code($response);
        
        if ($status_code !== 200 || !isset($body['success']) || $body['success'] !== true) {
            $error_message = isset($body['message']) ? $body['message'] : 'Unknown error';
            $this->log_error('Error response from Hudle: ' . $error_message);
            return new WP_Error('api_error', $error_message);
        }
        
        return isset($body['data']) ? $body['data'] : array();
    }
    
    /**
     * Check slot availability
     */
    public function check_slot_availability_ajax() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'tb_availability_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed', 'turf-booking')));
        }
        
        $court_id = isset($_POST['court_id']) ? absint($_POST['court_id']) : 0;
        $date = isset($_POST['date']) ? sanitize_text_field($_POST['date']) : '';
        $time_from = isset($_POST['time_from']) ? sanitize_text_field($_POST['time_from']) : '';
        $time_to = isset($_POST['time_to']) ? sanitize_text_field($_POST['time_to']) : '';
        
        if (!$court_id || !$date || !$time_from || !$time_to) {
            wp_send_json_error(array('message' => __('Missing required fields', 'turf-booking')));
        }
        
        // Only perform this check if Hudle integration is enabled for this court
        if (!$this->court_has_hudle_integration($court_id)) {
            wp_send_json_success(array('available' => true)); // Skip check if no integration
        }
        
        // Get slots from Hudle
        $slots = $this->get_slots($court_id, $date);
        
        if (is_wp_error($slots)) {
            wp_send_json_error(array('message' => $slots->get_error_message()));
        }
        
        // Convert booking times to DateTime objects for comparison
        $booking_start = new DateTime($date . ' ' . $time_from);
        $booking_end = new DateTime($date . ' ' . $time_to);
        
        // Check if any of the required slots are unavailable
        $unavailable_slots = array();
        
        foreach ($slots as $slot) {
            // Skip available slots
            if ($slot['is_available'] === true) {
                continue;
            }
            
            // Convert slot times to DateTime objects
            $slot_start = new DateTime($slot['start_time']);
            $slot_end = new DateTime($slot['end_time']);
            
            // Check if this slot overlaps with our booking
            if (
                ($slot_start < $booking_end && $slot_end > $booking_start) ||
                ($slot_start == $booking_start || $slot_end == $booking_end)
            ) {
                $unavailable_slots[] = array(
                    'start_time' => $slot['start_time'],
                    'end_time' => $slot['end_time']
                );
            }
        }
        
        if (count($unavailable_slots) > 0) {
            wp_send_json_error(array(
                'message' => __('This slot is already booked in Hudle', 'turf-booking'),
                'unavailable_slots' => $unavailable_slots
            ));
        }
        
        wp_send_json_success(array('available' => true));
    }
    
    /**
     * Sync booking to Hudle
     *
     * @param int $booking_id
     * @return bool|WP_Error
     */
    public function sync_booking_to_hudle($booking_id) {
        // Get booking details
        $court_id = get_post_meta($booking_id, '_tb_booking_court_id', true);
        
        if (!$this->court_has_hudle_integration($court_id)) {
            // Skip if no Hudle integration
            return false;
        }
        
        $facility_id = get_post_meta($court_id, '_tb_hudle_facility_id', true);
        $activity_id = get_post_meta($court_id, '_tb_hudle_activity_id', true);
        $date = get_post_meta($booking_id, '_tb_booking_date', true);
        $time_from = get_post_meta($booking_id, '_tb_booking_time_from', true);
        $time_to = get_post_meta($booking_id, '_tb_booking_time_to', true);
        $user_name = get_post_meta($booking_id, '_tb_booking_user_name', true);
        $user_email = get_post_meta($booking_id, '_tb_booking_user_email', true);
        $user_phone = get_post_meta($booking_id, '_tb_booking_user_phone', true);
        
        // Generate timestamps for each 30-minute slot
        $current_time = strtotime($date . ' ' . $time_from);
        $end_time = strtotime($date . ' ' . $time_to);
        $timestamps = array();
        
        while ($current_time < $end_time) {
            $timestamps[] = date('Y-m-d H:i:s', $current_time);
            $current_time += 30 * 60; // Add 30 minutes
        }
        
        if (empty($timestamps)) {
            $this->log_error('No valid timestamps generated for booking #' . $booking_id);
            return new WP_Error('invalid_time', __('Could not generate valid time slots', 'turf-booking'));
        }
        
        // Build the request body
        $body = array();
        
        // Add timestamps
        foreach ($timestamps as $index => $timestamp) {
            $body["start_timestamps[{$index}]"] = $timestamp;
        }
        
        // Add user details
        $body['user_details[name]'] = $user_name;
        $body['user_details[email]'] = $user_email;
        $body['user_details[phone_number]'] = $user_phone;
        
        // Add date of birth (required by Hudle)
        $body['user_details[date_of_birth]'] = '1990-01-01'; // Default value
        
        // Add note
        $body['note'] = sprintf(__('Booking #%d from Turf Booking plugin', 'turf-booking'), $booking_id);
        
        // Add activity id if available
        if (!empty($activity_id)) {
            $body['activity_id'] = $activity_id;
        }
        
        $url = sprintf(
            '%s/venues/%s/facilities/%s/bookings',
            $this->api_base_url,
            $this->venue_id,
            $facility_id
        );
        
        $response = wp_remote_post($url, array(
            'method' => 'POST',
            'timeout' => 45,
            'redirection' => 5,
            'httpversion' => '1.0',
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->api_token,
                'Content-Type' => 'application/x-www-form-urlencoded'
            ),
            'body' => $body
        ));
        
        if (is_wp_error($response)) {
            $this->log_error('Error creating booking in Hudle: ' . $response->get_error_message());
            return $response;
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        $status_code = wp_remote_retrieve_response_code($response);
        
        if ($status_code !== 201 || !isset($body['success']) || $body['success'] !== true) {
            $error_message = isset($body['message']) ? $body['message'] : 'Unknown error';
            $this->log_error('Error response from Hudle: ' . $error_message);
            return new WP_Error('api_error', $error_message);
        }
        
        // Save Hudle booking details to our booking
        if (isset($body['data']['id'])) {
            update_post_meta($booking_id, '_tb_hudle_booking_id', $body['data']['id']);
        }
        
        if (isset($body['data']['reference_id'])) {
            update_post_meta($booking_id, '_tb_hudle_reference_id', $body['data']['reference_id']);
        }
        
        $this->log_debug('Successfully created booking in Hudle for booking #' . $booking_id);
        
        return true;
    }
    
    /**
     * Log error message
     */
    private function log_error($message) {
        if ($this->debug_mode) {
            error_log('Hudle API Error: ' . $message);
        }
    }
    
    /**
     * Log debug message
     */
    private function log_debug($message) {
        if ($this->debug_mode) {
            error_log('Hudle API Debug: ' . $message);
        }
    }
}
