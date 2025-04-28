<?php
/**
 * Handles all booking functionality.
 */
class Turf_Booking_Bookings {

    /**
     * Initialize the class.
     */
    public function __construct() {
        // Hooks for booking actions
        add_action('wp_ajax_check_court_availability', array($this, 'check_court_availability'));
        add_action('wp_ajax_nopriv_check_court_availability', array($this, 'check_court_availability'));
        
        add_action('wp_ajax_create_booking', array($this, 'create_booking'));
        add_action('wp_ajax_nopriv_create_booking', array($this, 'create_booking'));
        
        add_action('wp_ajax_cancel_booking', array($this, 'cancel_booking'));
        add_action('wp_ajax_nopriv_cancel_booking', array($this, 'cancel_booking'));
    }
    
    /**
     * Check court availability for a specific date
     */
    public function check_court_availability() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'tb_availability_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed', 'turf-booking')));
        }
        
        $court_id = isset($_POST['court_id']) ? absint($_POST['court_id']) : 0;
        $date = isset($_POST['date']) ? sanitize_text_field($_POST['date']) : '';
        
        if (!$court_id || !$date) {
            wp_send_json_error(array('message' => __('Invalid request data', 'turf-booking')));
        }
        
        // Get court opening hours for the day of week
        $day_of_week = strtolower(date('l', strtotime($date)));
        $opening_hours = get_post_meta($court_id, '_tb_court_opening_hours', true);
        
        // Check if court is closed on this day
        if (isset($opening_hours[$day_of_week]['closed']) && $opening_hours[$day_of_week]['closed']) {
            wp_send_json_error(array('message' => __('Court is closed on this day', 'turf-booking')));
        }
        
        // Get time slot duration
        $time_slot_duration = get_post_meta($court_id, '_tb_court_time_slot', true);
        if (!$time_slot_duration) {
            $time_slot_duration = 60; // Default to 1 hour
        }
        
        // Generate all possible time slots for the day
        $from_time = strtotime($opening_hours[$day_of_week]['from']);
        $to_time = strtotime($opening_hours[$day_of_week]['to']);
        
        $time_slots = array();
        $current_time = $from_time;
        
        while ($current_time < $to_time) {
            $slot_start = date('H:i', $current_time);
            $slot_end = date('H:i', $current_time + ($time_slot_duration * 60));
            
            $time_slots[] = array(
                'from' => $slot_start,
                'to' => $slot_end,
                'available' => true
            );
            
            $current_time += ($time_slot_duration * 60);
        }
        
        // Check which slots are already booked
        global $wpdb;
        $table_name = $wpdb->prefix . 'tb_booking_slots';
        
        $booked_slots = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT time_from, time_to FROM $table_name 
                WHERE court_id = %d 
                AND booking_date = %s 
                AND status = 'booked'",
                $court_id,
                $date
            )
        );
        
        // Mark booked slots as unavailable
        foreach ($booked_slots as $booked_slot) {
            foreach ($time_slots as &$slot) {
                // Check if slots overlap
                if (
                    ($slot['from'] >= $booked_slot->time_from && $slot['from'] < $booked_slot->time_to) ||
                    ($slot['to'] > $booked_slot->time_from && $slot['to'] <= $booked_slot->time_to) ||
                    ($slot['from'] <= $booked_slot->time_from && $slot['to'] >= $booked_slot->time_to)
                ) {
                    $slot['available'] = false;
                }
            }
        }
        
        // Check if any slots are in the past (for today's date)
        if (date('Y-m-d') === $date) {
            $current_time = time();
            
            foreach ($time_slots as &$slot) {
                $slot_start_time = strtotime($date . ' ' . $slot['from']);
                
                if ($slot_start_time < $current_time) {
                    $slot['available'] = false;
                }
            }
        }
        
        // Calculate pricing for each slot
        $base_price = get_post_meta($court_id, '_tb_court_base_price', true);
        
        // Check if it's a weekend
        $is_weekend = (date('N', strtotime($date)) >= 6);
        $weekend_price = get_post_meta($court_id, '_tb_court_weekend_price', true);
        
        foreach ($time_slots as &$slot) {
            // Base pricing
            $slot['price'] = $base_price;
            
            // Weekend pricing
            if ($is_weekend && $weekend_price) {
                $slot['price'] = $weekend_price;
            }
            
            // Peak hour pricing (e.g., 6PM - 10PM)
            $peak_hour_price = get_post_meta($court_id, '_tb_court_peak_hour_price', true);
            $slot_hour = (int)substr($slot['from'], 0, 2);
            
            if ($peak_hour_price && $slot_hour >= 18 && $slot_hour < 22) {
                $slot['price'] = $peak_hour_price;
            }
        }
        
        wp_send_json_success(array(
            'slots' => $time_slots,
            'court_data' => array(
                'name' => get_the_title($court_id),
                'time_slot_duration' => $time_slot_duration,
                'opening_time' => $opening_hours[$day_of_week]['from'],
                'closing_time' => $opening_hours[$day_of_week]['to'],
            )
        ));
    }
    
    /**
     * Create a new booking
     */
  /**
 * Create a new booking
 */
public function create_booking() {
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'tb_booking_nonce')) {
        wp_send_json_error(array('message' => __('Security check failed', 'turf-booking')));
    }
    
    // Get and validate input data
    $court_id = isset($_POST['court_id']) ? absint($_POST['court_id']) : 0;
    $date = isset($_POST['date']) ? sanitize_text_field($_POST['date']) : '';
    $time_from = isset($_POST['time_from']) ? sanitize_text_field($_POST['time_from']) : '';
    $time_to = isset($_POST['time_to']) ? sanitize_text_field($_POST['time_to']) : '';
    $name = isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '';
    $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
    $phone = isset($_POST['phone']) ? sanitize_text_field($_POST['phone']) : '';
    $addons = isset($_POST['addons']) ? array_map('absint', (array) $_POST['addons']) : array();
    
    // Validate required fields
    if (!$court_id || !$date || !$time_from || !$time_to || !$name || !$email || !$phone) {
        wp_send_json_error(array('message' => __('All fields are required', 'turf-booking')));
    }
    
    // Check if the court exists
    $court = get_post($court_id);
    if (!$court || $court->post_type !== 'tb_court') {
        wp_send_json_error(array('message' => __('Invalid court selected', 'turf-booking')));
    }
    
    // Check if the slot is available
    global $wpdb;
    $table_name = $wpdb->prefix . 'tb_booking_slots';
    
    $existing_booking = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name 
            WHERE court_id = %d 
            AND booking_date = %s 
            AND (
                (time_from <= %s AND time_to > %s) OR
                (time_from < %s AND time_to >= %s) OR
                (time_from >= %s AND time_to <= %s)
            )
            AND status = 'booked'",
            $court_id,
            $date,
            $time_from,
            $time_from,
            $time_to,
            $time_to,
            $time_from,
            $time_to
        )
    );
    
    if ($existing_booking > 0) {
        wp_send_json_error(array('message' => __('This time slot is no longer available', 'turf-booking')));
    }
    
    // Create booking post
    $booking_title = sprintf(
        __('Booking: %s - %s (%s - %s)', 'turf-booking'),
        get_the_title($court_id),
        $date,
        $time_from,
        $time_to
    );
    
    $booking_id = wp_insert_post(array(
        'post_title' => $booking_title,
        'post_status' => 'publish',
        'post_type' => 'tb_booking',
    ));
    
    if (is_wp_error($booking_id)) {
        wp_send_json_error(array('message' => __('Failed to create booking', 'turf-booking')));
    }
    
    // Save booking meta
    update_post_meta($booking_id, '_tb_booking_court_id', $court_id);
    update_post_meta($booking_id, '_tb_booking_date', $date);
    update_post_meta($booking_id, '_tb_booking_time_from', $time_from);
    update_post_meta($booking_id, '_tb_booking_time_to', $time_to);
    update_post_meta($booking_id, '_tb_booking_status', 'pending');
    
    // Save user details
    $user_id = get_current_user_id();
    update_post_meta($booking_id, '_tb_booking_user_id', $user_id);
    update_post_meta($booking_id, '_tb_booking_user_name', $name);
    update_post_meta($booking_id, '_tb_booking_user_email', $email);
    update_post_meta($booking_id, '_tb_booking_user_phone', $phone);
    
    // Calculate booking amount
    $base_price = get_post_meta($court_id, '_tb_court_base_price', true);
    $is_weekend = (date('N', strtotime($date)) >= 6);
    $weekend_price = get_post_meta($court_id, '_tb_court_weekend_price', true);
    $peak_hour_price = get_post_meta($court_id, '_tb_court_peak_hour_price', true);
    
    $price = $base_price;
    
    // Weekend pricing
    if ($is_weekend && $weekend_price) {
        $price = $weekend_price;
    }
    
    // Peak hour pricing (e.g., 6PM - 10PM)
    $slot_hour = (int)substr($time_from, 0, 2);
    if ($peak_hour_price && $slot_hour >= 18 && $slot_hour < 22) {
        $price = $peak_hour_price;
    }
    
    // Calculate hours
    $time_from_obj = DateTime::createFromFormat('H:i', $time_from);
    $time_to_obj = DateTime::createFromFormat('H:i', $time_to);
    $interval = $time_from_obj->diff($time_to_obj);
    $hours = $interval->h + ($interval->i / 60);
    
    $court_amount = $price * $hours;
    $total_amount = $court_amount;
    
    // Process addons
    if (!empty($addons)) {
        $table_name_addons = $wpdb->prefix . 'tb_booking_addons';
        
        foreach ($addons as $addon_id) {
            $addon_details = $this->get_addon_details($addon_id);
            
            if ($addon_details) {
                $addon_price = floatval($addon_details['price']);
                $addon_type = $addon_details['type'];
                
                // Calculate addon price (either per booking or per hour)
                $addon_total = ($addon_type === 'per_hour') ? $addon_price * $hours : $addon_price;
                $total_amount += $addon_total;
                
                // Store addon in the database
                $wpdb->insert(
                    $table_name_addons,
                    array(
                        'booking_id' => $booking_id,
                        'addon_id' => $addon_id,
                        'addon_name' => $addon_details['name'],
                        'addon_price' => $addon_price,
                        'addon_type' => $addon_type,
                        'created_at' => current_time('mysql'),
                    ),
                    array('%d', '%d', '%s', '%f', '%s', '%s')
                );
            }
        }
    }
    
    // Save payment details
    update_post_meta($booking_id, '_tb_booking_payment_amount', $total_amount);
    update_post_meta($booking_id, '_tb_booking_payment_status', 'pending');
    update_post_meta($booking_id, '_tb_booking_court_amount', $court_amount);
    
    // Create booking slot in database
    $wpdb->insert(
        $table_name,
        array(
            'court_id' => $court_id,
            'booking_id' => $booking_id,
            'booking_date' => $date,
            'time_from' => $time_from,
            'time_to' => $time_to,
            'status' => 'booked',
            'created_at' => current_time('mysql'),
        ),
        array('%d', '%d', '%s', '%s', '%s', '%s', '%s')
    );
    
    // Record in booking slot history
    $table_name_history = $wpdb->prefix . 'tb_booking_slot_history';
    $slot_id = $wpdb->insert_id;
    
    $wpdb->insert(
        $table_name_history,
        array(
            'slot_id' => $slot_id,
            'court_id' => $court_id,
            'booking_id' => $booking_id,
            'booking_date' => $date,
            'time_from' => $time_from,
            'time_to' => $time_to,
            'status' => 'booked',
            'created_at' => current_time('mysql'),
            'user_id' => $user_id,
        ),
        array('%d', '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%d')
    );
    
    // Get booking confirmation method from settings
    $general_settings = get_option('tb_general_settings');
    $confirmation_method = isset($general_settings['booking_confirmation']) ? $general_settings['booking_confirmation'] : 'auto';
    
    // If auto confirmation, confirm booking
    if ($confirmation_method === 'auto') {
        update_post_meta($booking_id, '_tb_booking_status', 'confirmed');
        $this->send_booking_confirmation_email($booking_id);
    } else {
        $this->send_booking_pending_email($booking_id);
    }
    
    // Send admin notification
    $this->send_admin_booking_notification($booking_id);
    
    // Return booking details for payment processing
    wp_send_json_success(array(
        'booking_id' => $booking_id,
        'amount' => $total_amount,
        'confirmation_method' => $confirmation_method,
        'redirect_url' => ($confirmation_method === 'payment') 
            ? add_query_arg('booking_id', $booking_id, get_permalink(get_option('tb_page_settings')['checkout']))
            : add_query_arg('booking_id', $booking_id, get_permalink(get_option('tb_page_settings')['booking-confirmation']))
    ));

    // Trigger action for Hudle integration
do_action('tb_after_booking_confirmed', $booking_id);

}
    
    /**
     * Cancel a booking
     */
    public function cancel_booking() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'tb_cancel_booking_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed', 'turf-booking')));
        }
        
        $booking_id = isset($_POST['booking_id']) ? absint($_POST['booking_id']) : 0;
        $user_id = get_current_user_id();
        
        if (!$booking_id) {
            wp_send_json_error(array('message' => __('Invalid booking ID', 'turf-booking')));
        }
        
        // Check if user can cancel this booking
        $booking_user_id = get_post_meta($booking_id, '_tb_booking_user_id', true);
        
        if (!current_user_can('manage_options') && $booking_user_id != $user_id) {
            wp_send_json_error(array('message' => __('You do not have permission to cancel this booking', 'turf-booking')));
        }
        
        // Check booking status
        $booking_status = get_post_meta($booking_id, '_tb_booking_status', true);
        
        if ($booking_status === 'cancelled' || $booking_status === 'completed') {
            wp_send_json_error(array('message' => __('This booking cannot be cancelled', 'turf-booking')));
        }
        
        // Get cancellation policy
        $general_settings = get_option('tb_general_settings');
        $cancellation_hours = isset($general_settings['cancellation_policy']) ? intval($general_settings['cancellation_policy']) : 24;
        
        // Check if booking can be cancelled according to policy
        $booking_date = get_post_meta($booking_id, '_tb_booking_date', true);
        $booking_time = get_post_meta($booking_id, '_tb_booking_time_from', true);
        $booking_datetime = strtotime($booking_date . ' ' . $booking_time);
        
        if ($booking_datetime - time() < $cancellation_hours * 3600) {
            wp_send_json_error(array('message' => sprintf(
                __('Bookings can only be cancelled at least %d hours in advance', 'turf-booking'),
                $cancellation_hours
            )));
        }
        
        // Update booking status
        update_post_meta($booking_id, '_tb_booking_status', 'cancelled');
        
        // Update booking slot status
        global $wpdb;
        $table_name = $wpdb->prefix . 'tb_booking_slots';
        
        $wpdb->update(
            $table_name,
            array(
                'status' => 'available',
                'booking_id' => null,
            ),
            array(
                'booking_id' => $booking_id,
            ),
            array('%s', null),
            array('%d')
        );
        
        // Record in booking slot history
        $table_name_history = $wpdb->prefix . 'tb_booking_slot_history';
        
        // Get booking slots
        $slots = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $table_name WHERE booking_id = %d",
                $booking_id
            )
        );
        
        foreach ($slots as $slot) {
            $wpdb->insert(
                $table_name_history,
                array(
                    'slot_id' => $slot->id,
                    'court_id' => $slot->court_id,
                    'booking_id' => $booking_id,
                    'booking_date' => $slot->booking_date,
                    'time_from' => $slot->time_from,
                    'time_to' => $slot->time_to,
                    'status' => 'cancelled',
                    'created_at' => current_time('mysql'),
                    'user_id' => $user_id,
                ),
                array('%d', '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%d')
            );
        }
        
        // Check if payment was made
        $payment_status = get_post_meta($booking_id, '_tb_booking_payment_status', true);
        
        if ($payment_status === 'completed') {
            // Process refund according to policy
            $refund_policy = isset($general_settings['refund_policy']) ? $general_settings['refund_policy'] : 'full';
            
            if ($refund_policy === 'full') {
                update_post_meta($booking_id, '_tb_booking_payment_status', 'refunded');
            } else if ($refund_policy === 'partial') {
                update_post_meta($booking_id, '_tb_booking_payment_status', 'partially_refunded');
            } else {
                update_post_meta($booking_id, '_tb_booking_payment_status', 'no_refund');
            }
        }
        
        // Send cancellation email
        $this->send_booking_cancelled_email($booking_id);
        
        wp_send_json_success(array(
            'message' => __('Booking cancelled successfully', 'turf-booking'),
            'redirect_url' => get_permalink(get_option('tb_page_settings')['my-account'])
        ));
    }
    
    /**
     * Send booking confirmation email
     */
    private function send_booking_confirmation_email($booking_id) {
        $email_settings = get_option('tb_email_settings');
        
        $to = get_post_meta($booking_id, '_tb_booking_user_email', true);
        $subject = $email_settings['booking_confirmation_subject'];
        
        $message = $this->replace_email_placeholders(
            $email_settings['booking_confirmation_message'],
            $booking_id
        );
        
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $email_settings['email_from_name'] . ' <' . $email_settings['email_from_address'] . '>',
        );
        
        wp_mail($to, $subject, nl2br($message), $headers);
    }
    
    /**
     * Send booking pending email
     */
    private function send_booking_pending_email($booking_id) {
        $email_settings = get_option('tb_email_settings');
        
        $to = get_post_meta($booking_id, '_tb_booking_user_email', true);
        $subject = $email_settings['booking_pending_subject'];
        
        $message = $this->replace_email_placeholders(
            $email_settings['booking_pending_message'],
            $booking_id
        );
        
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $email_settings['email_from_name'] . ' <' . $email_settings['email_from_address'] . '>',
        );
        
        wp_mail($to, $subject, nl2br($message), $headers);
    }
    
    /**
     * Send booking cancelled email
     */
    private function send_booking_cancelled_email($booking_id) {
        $email_settings = get_option('tb_email_settings');
        
        $to = get_post_meta($booking_id, '_tb_booking_user_email', true);
        $subject = $email_settings['booking_cancelled_subject'];
        
        $message = $this->replace_email_placeholders(
            $email_settings['booking_cancelled_message'],
            $booking_id
        );
        
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $email_settings['email_from_name'] . ' <' . $email_settings['email_from_address'] . '>',
        );
        
        wp_mail($to, $subject, nl2br($message), $headers);
    }
    
    /**
     * Send admin notification for new booking
     */
    private function send_admin_booking_notification($booking_id) {
        $email_settings = get_option('tb_email_settings');
        
        $to = $email_settings['admin_email'];
        $subject = $email_settings['admin_notification_subject'];
        
        $message = $this->replace_email_placeholders(
            $email_settings['admin_notification_message'],
            $booking_id
        );
        
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $email_settings['email_from_name'] . ' <' . $email_settings['email_from_address'] . '>',
        );
        
        wp_mail($to, $subject, nl2br($message), $headers);
    }
    
    /**
     * Replace email placeholders with actual booking data
     */
    private function replace_email_placeholders($content, $booking_id) {
        $court_id = get_post_meta($booking_id, '_tb_booking_court_id', true);
        $customer_name = get_post_meta($booking_id, '_tb_booking_user_name', true);
        $customer_email = get_post_meta($booking_id, '_tb_booking_user_email', true);
        $customer_phone = get_post_meta($booking_id, '_tb_booking_user_phone', true);
        $booking_date = get_post_meta($booking_id, '_tb_booking_date', true);
        $booking_time_from = get_post_meta($booking_id, '_tb_booking_time_from', true);
        $booking_time_to = get_post_meta($booking_id, '_tb_booking_time_to', true);
        $booking_total = get_post_meta($booking_id, '_tb_booking_payment_amount', true);
        
        $general_settings = get_option('tb_general_settings');
        $currency_symbol = isset($general_settings['currency_symbol']) ? $general_settings['currency_symbol'] : '₹';
        
        // Format date and time according to settings
        $date_format = isset($general_settings['date_format']) ? $general_settings['date_format'] : 'd/m/Y';
        $time_format = isset($general_settings['time_format']) ? $general_settings['time_format'] : 'H:i';
        
        $formatted_date = date($date_format, strtotime($booking_date));
        $formatted_time_from = date($time_format, strtotime($booking_time_from));
        $formatted_time_to = date($time_format, strtotime($booking_time_to));
        
        // Replace placeholders
        $placeholders = array(
            '{booking_id}' => $booking_id,
            '{court_name}' => get_the_title($court_id),
            '{customer_name}' => $customer_name,
            '{customer_email}' => $customer_email,
            '{customer_phone}' => $customer_phone,
            '{booking_date}' => $formatted_date,
            '{booking_time_from}' => $formatted_time_from,
            '{booking_time_to}' => $formatted_time_to,
            '{booking_total}' => $currency_symbol . number_format($booking_total, 2),
        );
        
        return str_replace(array_keys($placeholders), array_values($placeholders), $content);
    }
    
    /**
     * Get bookings for a specific user
     */
    public function get_user_bookings($user_id, $status = '') {
        $args = array(
            'post_type' => 'tb_booking',
            'posts_per_page' => -1,
            'meta_query' => array(
                array(
                    'key' => '_tb_booking_user_id',
                    'value' => $user_id,
                ),
            ),
        );
        
        if ($status) {
            $args['meta_query'][] = array(
                'key' => '_tb_booking_status',
                'value' => $status,
            );
        }
        
        return get_posts($args);
    }
    
    /**
     * Get all bookings
     */
    public function get_all_bookings($status = '') {
        $args = array(
            'post_type' => 'tb_booking',
            'posts_per_page' => -1,
        );
        
        if ($status) {
            $args['meta_query'] = array(
                array(
                    'key' => '_tb_booking_status',
                    'value' => $status,
                ),
            );
        }
        
        return get_posts($args);
    }
    
    /**
     * Get booking details
     */
   /**
 * Get booking details
 */
public function get_booking_details($booking_id) {
    $booking = get_post($booking_id);
    
    if (!$booking || $booking->post_type !== 'tb_booking') {
        return false;
    }
    
    $court_id = get_post_meta($booking_id, '_tb_booking_court_id', true);
    
    // Get court amount
    $court_amount = get_post_meta($booking_id, '_tb_booking_court_amount', true);
    if (!$court_amount) {
        // For backwards compatibility
        $court_amount = get_post_meta($booking_id, '_tb_booking_payment_amount', true);
    }
    
    $details = array(
        'id' => $booking_id,
        'court_id' => $court_id,
        'court_name' => get_the_title($court_id),
        'court_image' => get_the_post_thumbnail_url($court_id, 'medium'),
        'date' => get_post_meta($booking_id, '_tb_booking_date', true),
        'time_from' => get_post_meta($booking_id, '_tb_booking_time_from', true),
        'time_to' => get_post_meta($booking_id, '_tb_booking_time_to', true),
        'status' => get_post_meta($booking_id, '_tb_booking_status', true),
        'user_id' => get_post_meta($booking_id, '_tb_booking_user_id', true),
        'user_name' => get_post_meta($booking_id, '_tb_booking_user_name', true),
        'user_email' => get_post_meta($booking_id, '_tb_booking_user_email', true),
        'user_phone' => get_post_meta($booking_id, '_tb_booking_user_phone', true),
        'payment_id' => get_post_meta($booking_id, '_tb_booking_payment_id', true),
        'payment_method' => get_post_meta($booking_id, '_tb_booking_payment_method', true),
        'payment_status' => get_post_meta($booking_id, '_tb_booking_payment_status', true),
        'payment_amount' => get_post_meta($booking_id, '_tb_booking_payment_amount', true),
        'payment_date' => get_post_meta($booking_id, '_tb_booking_payment_date', true),
        'court_amount' => $court_amount,
        'created_at' => $booking->post_date,
        'addons' => $this->get_booking_addons($booking_id),
    );
    
    return $details;
}

/**
 * Get addons for a booking
 *
 * @param int $booking_id Booking ID
 * @return array Array of addon details
 */
private function get_booking_addons($booking_id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'tb_booking_addons';
    
    $addons = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT * FROM $table_name WHERE booking_id = %d",
            $booking_id
        ),
        ARRAY_A
    );
    
    return $addons ?: array();
}


    /**
 * Get addon details by ID
 *
 * @param int $addon_id Addon ID
 * @return array|bool Addon details or false if not found
 */
private function get_addon_details($addon_id) {
    $addon = get_post($addon_id);
    
    if (!$addon || $addon->post_type !== 'tb_addon') {
        return false;
    }
    
    return array(
        'id' => $addon_id,
        'name' => $addon->post_title,
        'price' => get_post_meta($addon_id, '_tb_addon_price', true),
        'type' => get_post_meta($addon_id, '_tb_addon_type', true),
    );
}
}
