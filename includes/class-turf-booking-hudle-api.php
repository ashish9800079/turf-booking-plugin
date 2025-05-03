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
            $this->log_debug("Court $court_id doesn't have Hudle integration");
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
        
        $this->log_debug("Requesting Hudle slots: $url");
        
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
        
        $this->log_debug("Hudle API response: " . wp_remote_retrieve_body($response));
        
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
        
        if (!$court_id || !$date) {
            wp_send_json_error(array('message' => __('Missing required fields', 'turf-booking')));
        }
        
        // Only perform this check if Hudle integration is enabled for this court
        if (!$this->court_has_hudle_integration($court_id)) {
            $this->log_debug("Court $court_id doesn't have Hudle integration, skipping check");
            wp_send_json_success(array('available' => true, 'hudle_integrated' => false));
            return;
        }
        
        // Get slots from Hudle
        $slots = $this->get_slots($court_id, $date);
        
        if (is_wp_error($slots)) {
            $this->log_error("Error getting slots from Hudle: " . $slots->get_error_message());
            wp_send_json_error(array('message' => $slots->get_error_message()));
            return;
        }
        
        // If checking for a specific time slot
        if (isset($_POST['time_from']) && isset($_POST['time_to'])) {
            $time_from = sanitize_text_field($_POST['time_from']);
            $time_to = sanitize_text_field($_POST['time_to']);
            
            // Convert booking times to DateTime objects for comparison
            $booking_start = new DateTime($date . ' ' . $time_from);
            $booking_end = new DateTime($date . ' ' . $time_to);
            
            // Check if any of the required slots are unavailable
            $unavailable_slots = array();
            
            foreach ($slots as $slot) {
                // Skip available slots
                if ($slot['is_available'] === true || $slot['inventory_count'] == 0) {
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
                return;
            }
            
            wp_send_json_success(array('available' => true, 'hudle_integrated' => true));
        } else {
            // If just checking for all slots, return the full data
            wp_send_json_success(array(
                'available' => true,
                'hudle_integrated' => true,
                'slots' => $slots
            ));
        }
    }
    
    /**
     * Sync booking to Hudle
     *
     * @param int $booking_id
     * @return bool|WP_Error
     */
public function sync_booking_to_hudle($booking_id) {
    $this->log_debug("Attempting to sync booking #$booking_id to Hudle using cURL");
    
    // Get booking details
    $court_id = get_post_meta($booking_id, '_tb_booking_court_id', true);
    
    if (!$this->court_has_hudle_integration($court_id)) {
        $this->log_debug("Court #$court_id is not integrated with Hudle, skipping sync");
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
    
    $this->log_debug("Booking details: Court ID: $court_id, Facility ID: $facility_id, Date: $date, Time: $time_from - $time_to");
    
    // Generate timestamps for each 30-minute slot
    $start_datetime = new DateTime($date . ' ' . $time_from);
    $end_datetime = new DateTime($date . ' ' . $time_to);
    
    $current_time = clone $start_datetime;
    $timestamps = array();
    
    while ($current_time < $end_datetime) {
        $timestamps[] = $current_time->format('Y-m-d H:i:s');
        $current_time->add(new DateInterval('PT30M')); // Add 30 minutes
    }
    
    if (empty($timestamps)) {
        $this->log_error("No valid timestamps generated for booking #$booking_id");
        return new WP_Error('invalid_time', __('Could not generate valid time slots', 'turf-booking'));
    }
    
    $this->log_debug("Generated " . count($timestamps) . " timestamps for booking");
    
    // Build the URL for the Hudle API
    $url = sprintf(
        '%s/venues/%s/facilities/%s/bookings',
        $this->api_base_url,
        $this->venue_id,
        $facility_id
    );
    
    $this->log_debug("Sending booking to Hudle: $url");
    
    // Initialize cURL
    $curl = curl_init();
    
    // Create form data array for cURL
    $post_fields = array();
    
    // Add timestamps
    foreach ($timestamps as $index => $timestamp) {
        $post_fields["start_timestamps[$index]"] = $timestamp;
    }
    
    // Add user details
    $post_fields['user_details[name]'] = $user_name;
    $post_fields['user_details[email]'] = $user_email;
    $post_fields['user_details[phone_number]'] = $user_phone;
    $post_fields['user_details[date_of_birth]'] = '2000-01-01'; // Default value
    
    // Add note
    $post_fields['note'] = sprintf(__('Booking #%d from %s', 'turf-booking'), $booking_id, get_bloginfo('name'));
    
    // Add activity id if available
    if (!empty($activity_id)) {
        $post_fields['activity_id'] = $activity_id;
    }
    
    $this->log_debug("cURL post fields: " . print_r($post_fields, true));
    
    // Set cURL options
    curl_setopt_array($curl, array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => $post_fields,
        CURLOPT_HTTPHEADER => array(
            'Authorization: Bearer ' . $this->api_token,
            'Content-Type: multipart/form-data'
        ),
    ));
    
    // Execute cURL request
    $response = curl_exec($curl);
    $err = curl_error($curl);
    $status_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    
    // Close cURL session
    curl_close($curl);
    
    // Log the complete response for debugging
    $this->log_debug("Hudle API response status code: $status_code");
    $this->log_debug("Hudle API raw response: $response");
    
    if ($err) {
        $this->log_error('cURL Error: ' . $err);
        return new WP_Error('api_error', $err);
    }
    
    // Process the response
    $body = json_decode($response, true);
    
    if ($status_code !== 201 || !isset($body['success']) || $body['success'] !== true) {
        $error_message = isset($body['message']) ? $body['message'] : 'Unknown error';
        $this->log_error('Error response from Hudle: ' . $error_message);
        return new WP_Error('api_error', $error_message);
    }
    
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
            
            // Also write to a debug file in the plugin directory if writable
            $log_file = TURF_BOOKING_PLUGIN_DIR . 'hudle-debug.log';
            if (is_writable(TURF_BOOKING_PLUGIN_DIR)) {
                file_put_contents($log_file, date('[Y-m-d H:i:s]') . ' ERROR: ' . $message . "\n", FILE_APPEND);
            }
        }
    }
    
    /**
     * Log debug message
     */
    private function log_debug($message) {
        if ($this->debug_mode) {
            error_log('Hudle API Debug: ' . $message);
            
            // Also write to a debug file in the plugin directory if writable
            $log_file = TURF_BOOKING_PLUGIN_DIR . 'hudle-debug.log';
            if (is_writable(TURF_BOOKING_PLUGIN_DIR)) {
                file_put_contents($log_file, date('[Y-m-d H:i:s]') . ' DEBUG: ' . $message . "\n", FILE_APPEND);
            }
        }
    }
}