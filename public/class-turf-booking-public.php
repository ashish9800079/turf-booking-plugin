<?php
/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and hooks for
 * the public-facing side of the site.
 */
class Turf_Booking_Public {

    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $plugin_name    The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $version    The current version of this plugin.
     */
    private $version;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param      string    $plugin_name       The name of the plugin.
     * @param      string    $version    The version of this plugin.
     */
    public function __construct( $plugin_name, $version ) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;

        add_action('wp_ajax_check_hudle_slot_availability', array($this, 'check_hudle_slot_availability_ajax'));
add_action('wp_ajax_nopriv_check_hudle_slot_availability', array($this, 'check_hudle_slot_availability_ajax'));

    }

    /**
     * Register the stylesheets for the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function enqueue_styles() {
        wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/turf-booking-public.css', array(), $this->version, 'all' );
        
        // Add Font Awesome if needed
        wp_enqueue_style( $this->plugin_name . '-fontawesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css', array(), '5.15.4', 'all' );
    }

    /**
     * Register the JavaScript for the public-facing side of the site.
     *
     * @since    1.0.0
     */
 /**
 * Register the JavaScript for the public-facing side of the site.
 *
 * @since    1.0.0
 */
public function enqueue_scripts() {
    // Original scripts
    wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/turf-booking-public.js', array( 'jquery' ), $this->version, true );
    
    
    // Add jQuery UI if needed for datepickers and sliders
    wp_enqueue_script( 'jquery-ui-datepicker' );
    wp_enqueue_script( 'jquery-ui-slider' );
    wp_enqueue_style( 'jquery-ui', 'https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css' );
    
    // Add multi-step booking wizard script
    wp_enqueue_script( $this->plugin_name . '-wizard', plugin_dir_url( __FILE__ ) . 'js/turf-booking-wizard.js', array( 'jquery', 'jquery-ui-datepicker' ), $this->version, true );
    
    // Localize script with data
    $localized_data = array(
        'ajax_url' => admin_url( 'admin-ajax.php' ),
        'availability_nonce' => wp_create_nonce( 'tb_availability_nonce' ),
        'booking_nonce' => wp_create_nonce( 'tb_booking_nonce' ),
        'search_nonce' => wp_create_nonce( 'tb_search_nonce' ),
        'filter_nonce' => wp_create_nonce( 'tb_filter_nonce' ),
        'dashboard_nonce' => wp_create_nonce( 'tb_dashboard_nonce' ),
        'currency_symbol' => $this->get_currency_symbol(),
        'locale' => get_locale(),
        'loading_slots' => __( 'Loading available time slots...', 'turf-booking' ),
        'no_slots' => __( 'No time slots available for this date.', 'turf-booking' ),
        'booked_text' => __( 'Booked', 'turf-booking' ),
        'ajax_error' => __( 'An error occurred. Please try again.', 'turf-booking' ),
        'select_date_time' => __( 'Please select a date and time slot.', 'turf-booking' ),
        'fill_contact_info' => __( 'Please fill in all contact information.', 'turf-booking' ),
        'processing_booking' => __( 'Processing your booking...', 'turf-booking' ),
        'booking_error' => __( 'Error processing booking. Please try again.', 'turf-booking' ),
        'confirm_cancel' => __( 'Are you sure you want to cancel this booking?', 'turf-booking' ),
        'processing' => __( 'Processing...', 'turf-booking' ),
    );
    
    wp_localize_script( $this->plugin_name, 'tb_public_params', $localized_data );
    wp_localize_script( $this->plugin_name . '-wizard', 'tb_public_params', $localized_data );
}
    
    /**
     * Get currency symbol
     * 
     * @return string Currency symbol
     */
    private function get_currency_symbol() {
        $general_settings = get_option( 'tb_general_settings' );
        return isset( $general_settings['currency_symbol'] ) ? $general_settings['currency_symbol'] : '₹';
    }
    
    /**
     * Override single court template with plugin template
     * 
     * @param string $template Original template path
     * @return string Modified template path
     */
    public function court_single_template( $template ) {
        // Only override template for single court
        if ( is_singular( 'tb_court' ) ) {
            $custom_template = TURF_BOOKING_PLUGIN_DIR . 'public/templates/single-court.php';
            
            if ( file_exists( $custom_template ) ) {
                return $custom_template;
            }
        }
        
        return $template;
    }
    
    /**
     * Override archive court template with plugin template
     * 
     * @param string $template Original template path
     * @return string Modified template path
     */
    public function court_archive_template( $template ) {
        // Only override template for court archive
        if ( is_post_type_archive( 'tb_court' ) || is_tax( array( 'sport_type', 'location', 'facility' ) ) ) {
            $custom_template = TURF_BOOKING_PLUGIN_DIR . 'public/templates/archive-court.php';
            
            if ( file_exists( $custom_template ) ) {
                return $custom_template;
            }
        }
        
        return $template;
    }
    
    /**
     * Get court availability via AJAX
     */
    public function get_court_availability() {
        // Initialize courts class
        $courts = new Turf_Booking_Courts();
        
        // Call the get_court_availability method
        $courts->get_court_availability();
    }
    
    

    /**
     * Create booking via AJAX
     */
    public function create_booking() {
        // Initialize bookings class
        $bookings = new Turf_Booking_Bookings();
        
        // Call the create_booking method
        $bookings->create_booking();
    }
    
    /**
     * Process Razorpay payment via AJAX
     */
    public function razorpay_payment_callback() {
        // Initialize payments class
        $payments = new Turf_Booking_Payments();
        
        // Call the verify_razorpay_payment method
        $payments->verify_razorpay_payment();
    }
    
    
    public function test_ajax() {
    error_log('Test AJAX function called');
    wp_send_json_success(array('message' => 'AJAX is working'));
}
    /**
     * Add custom query vars
     * 
     * @param array $vars Existing query vars
     * @return array Modified query vars
     */
    public function add_query_vars( $vars ) {
        $vars[] = 'court_id';
        $vars[] = 'booking_id';
        $vars[] = 'booking_date';
        return $vars;
    }
    
    /**
     * Add custom rewrite rules
     */
    public function add_rewrite_rules() {
        // Add rule for booking form with court ID
        add_rewrite_rule(
            'booking/([0-9]+)/?$',
            'index.php?pagename=booking&court_id=$matches[1]',
            'top'
        );
        
        // Add rule for booking form with court ID and date
        add_rewrite_rule(
            'booking/([0-9]+)/([^/]+)/?$',
            'index.php?pagename=booking&court_id=$matches[1]&booking_date=$matches[2]',
            'top'
        );
        
        // Add rule for checkout with booking ID
        add_rewrite_rule(
            'checkout/([0-9]+)/?$',
            'index.php?pagename=checkout&booking_id=$matches[1]',
            'top'
        );
        
        // Add rule for booking confirmation with booking ID
        add_rewrite_rule(
            'booking-confirmation/([0-9]+)/?$',
            'index.php?pagename=booking-confirmation&booking_id=$matches[1]',
            'top'
        );
    }
    
    /**
     * Add body classes for plugin templates
     * 
     * @param array $classes Existing body classes
     * @return array Modified body classes
     */
    public function add_body_classes( $classes ) {
        if ( is_singular( 'tb_court' ) ) {
            $classes[] = 'tb-court-single';
        } elseif ( is_post_type_archive( 'tb_court' ) || is_tax( array( 'sport_type', 'location', 'facility' ) ) ) {
            $classes[] = 'tb-court-archive';
        }
        
        // Check if we're on plugin pages by slug
        $page_settings = get_option( 'tb_page_settings' );
        
        if ( $page_settings && is_page() ) {
            global $post;
            
            foreach ( $page_settings as $key => $page_id ) {
                if ( $post->ID == $page_id ) {
                    $classes[] = 'tb-page-' . $key;
                    break;
                }
            }
        }
        
        return $classes;
    }
    
    /**
     * Register additional REST API endpoints
     */
    public function register_rest_endpoints() {
        register_rest_route( 'turf-booking/v1', '/courts', array(
            'methods' => 'GET',
            'callback' => array( $this, 'rest_get_courts' ),
            'permission_callback' => '__return_true',
        ) );
        
        register_rest_route( 'turf-booking/v1', '/courts/(?P<id>\d+)', array(
            'methods' => 'GET',
            'callback' => array( $this, 'rest_get_court' ),
            'permission_callback' => '__return_true',
        ) );
        
        register_rest_route( 'turf-booking/v1', '/courts/(?P<id>\d+)/availability', array(
            'methods' => 'GET',
            'callback' => array( $this, 'rest_get_court_availability' ),
            'permission_callback' => '__return_true',
        ) );
    }
    
    /**
     * REST API callback for getting all courts
     * 
     * @param WP_REST_Request $request REST API request
     * @return WP_REST_Response REST API response
     */
    public function rest_get_courts( $request ) {
        $courts = new Turf_Booking_Courts();
        
        // Get query parameters
        $params = $request->get_params();
        
        $args = array();
        
        // Add pagination
        if ( isset( $params['per_page'] ) ) {
            $args['posts_per_page'] = intval( $params['per_page'] );
        }
        
        if ( isset( $params['page'] ) ) {
            $args['paged'] = intval( $params['page'] );
        }
        
        // Add sorting
        if ( isset( $params['orderby'] ) ) {
            $args['orderby'] = sanitize_text_field( $params['orderby'] );
        }
        
        if ( isset( $params['order'] ) ) {
            $args['order'] = sanitize_text_field( $params['order'] );
        }
        
        // Get courts
        $court_posts = $courts->get_courts( $args );
        
        // Format courts data
        $formatted_courts = array();
        
        foreach ( $court_posts as $court_post ) {
            $court_details = $courts->get_court_details( $court_post->ID );
            
            if ( $court_details ) {
                $formatted_courts[] = $court_details;
            }
        }
        
        return rest_ensure_response( $formatted_courts );
    }
    
    /**
     * REST API callback for getting a single court
     * 
     * @param WP_REST_Request $request REST API request
     * @return WP_REST_Response REST API response
     */
    public function rest_get_court( $request ) {
        $courts = new Turf_Booking_Courts();
        
        // Get court ID from URL
        $court_id = $request['id'];
        
        // Get court details
        $court_details = $courts->get_court_details( $court_id );
        
        if ( !$court_details ) {
            return new WP_Error( 'court_not_found', __( 'Court not found', 'turf-booking' ), array( 'status' => 404 ) );
        }
        
        return rest_ensure_response( $court_details );
    }
    
    
    
    
    
    
    
    
    /**
 * Handle AJAX review submission
 */
public function submit_review() {
    // Check if user is logged in
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => __('You must be logged in to submit a review.', 'turf-booking')));
    }
    
    // Check nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'tb_submit_review')) {
        wp_send_json_error(array('message' => __('Security check failed.', 'turf-booking')));
    }
    
    // Get form data
    $court_id = isset($_POST['court_id']) ? absint($_POST['court_id']) : 0;
    $rating = isset($_POST['rating']) ? intval($_POST['rating']) : 0;
    $content = isset($_POST['content']) ? sanitize_textarea_field($_POST['content']) : '';
    
    // Validate data
    if (!$court_id) {
        wp_send_json_error(array('message' => __('Invalid court ID.', 'turf-booking')));
    }
    
    if ($rating < 1 || $rating > 5) {
        wp_send_json_error(array('message' => __('Please select a rating between 1 and 5.', 'turf-booking')));
    }
    
    if (empty($content)) {
        wp_send_json_error(array('message' => __('Please enter your review.', 'turf-booking')));
    }
    
    $current_user = wp_get_current_user();
    $user_id = $current_user->ID;
    
    // Check if user has already reviewed this court
    $existing_review = get_comments(array(
        'user_id' => $user_id,
        'post_id' => $court_id,
        'count' => true
    ));
    
    if ($existing_review > 0) {
        wp_send_json_error(array('message' => __('You have already reviewed this court.', 'turf-booking')));
    }
    
    // Check if user has booked this court (skip for admins)
    $verified_booking = false;
    
    if (!current_user_can('manage_options')) {
        // Query bookings
        $args = array(
            'post_type' => 'tb_booking',
            'meta_query' => array(
                'relation' => 'AND',
                array(
                    'key' => '_tb_booking_court_id',
                    'value' => $court_id,
                ),
                array(
                    'key' => '_tb_booking_user_id',
                    'value' => $user_id,
                ),
                array(
                    'key' => '_tb_booking_status',
                    'value' => 'completed',
                ),
            ),
            'posts_per_page' => 1,
        );
        
        $bookings_query = new WP_Query($args);
        
        if (!$bookings_query->have_posts()) {
            wp_send_json_error(array('message' => __('You can only review courts you have booked.', 'turf-booking')));
        }
        
        $verified_booking = true;
    } else {
        // Admins are always verified
        $verified_booking = true;
    }
    
    // Prepare comment data
    $comment_data = array(
        'comment_post_ID' => $court_id,
        'comment_content' => $content,
        'user_id' => $user_id,
        'comment_author' => $current_user->display_name,
        'comment_author_email' => $current_user->user_email,
        'comment_author_url' => $current_user->user_url,
        'comment_type' => 'comment',
        'comment_parent' => 0,
        'comment_approved' => 1,
    );
    
    // Insert comment
    $comment_id = wp_insert_comment($comment_data);
    
    if (!$comment_id) {
        wp_send_json_error(array('message' => __('Failed to submit review. Please try again.', 'turf-booking')));
    }
    
    // Add rating meta
    add_comment_meta($comment_id, 'rating', $rating);
    
    // Add verified booking meta if applicable
    if ($verified_booking) {
        add_comment_meta($comment_id, 'verified_booking', true);
    }
    
    // Update court rating
    $this->update_court_rating($court_id);
    
    wp_send_json_success(array('message' => __('Your review has been submitted successfully!', 'turf-booking')));
}

/**
 * Update overall court rating based on reviews
 * 
 * @param int $court_id The court ID
 */
private function update_court_rating($court_id) {
    // Get all approved comments/reviews
    $comments = get_comments(array(
        'post_id' => $court_id,
        'status' => 'approve',
    ));
    
    // Calculate average rating
    $total_rating = 0;
    $rating_count = 0;
    
    foreach ($comments as $comment) {
        $rating = get_comment_meta($comment->comment_ID, 'rating', true);
        if ($rating) {
            $total_rating += $rating;
            $rating_count++;
        }
    }
    
    $average_rating = $rating_count > 0 ? round($total_rating / $rating_count, 1) : 0;
    
    // Update court meta
    update_post_meta($court_id, '_tb_court_rating', $average_rating);
}




    /**
     * REST API callback for getting court availability
     * 
     * @param WP_REST_Request $request REST API request
     * @return WP_REST_Response REST API response
     */
    public function rest_get_court_availability( $request ) {
        $courts = new Turf_Booking_Courts();
        
        // Get court ID from URL
        $court_id = $request['id'];
        
        // Get query parameters
        $params = $request->get_params();
        
        if ( !isset( $params['date'] ) ) {
            return new WP_Error( 'missing_date', __( 'Date parameter is required', 'turf-booking' ), array( 'status' => 400 ) );
        }
        
        $date = sanitize_text_field( $params['date'] );
        
        // Validate date format (YYYY-MM-DD)
        if ( !preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
            return new WP_Error( 'invalid_date', __( 'Date must be in YYYY-MM-DD format', 'turf-booking' ), array( 'status' => 400 ) );
        }
        
        // Get court details
        $court_details = $courts->get_court_details( $court_id );
        
        if ( !$court_details ) {
            return new WP_Error( 'court_not_found', __( 'Court not found', 'turf-booking' ), array( 'status' => 404 ) );
        }
        
        // Get court opening hours for the day of week
        $day_of_week = strtolower( date( 'l', strtotime( $date ) ) );
        $opening_hours = $court_details['opening_hours'];
        
        // Check if court is closed on this day
        if ( isset( $opening_hours[$day_of_week]['closed'] ) && $opening_hours[$day_of_week]['closed'] ) {
            return new WP_Error( 'court_closed', __( 'Court is closed on this day', 'turf-booking' ), array( 'status' => 400 ) );
        }
        
        // Get time slot duration
        $time_slot_duration = $court_details['time_slot'];
        if ( !$time_slot_duration ) {
            $time_slot_duration = 60; // Default to 1 hour
        }
        
        // Generate all possible time slots for the day
        $from_time = strtotime( $opening_hours[$day_of_week]['from'] );
        $to_time = strtotime( $opening_hours[$day_of_week]['to'] );
        
        $time_slots = array();
        $current_time = $from_time;
        
        while ( $current_time < $to_time ) {
            $slot_start = date( 'H:i', $current_time );
            $slot_end = date( 'H:i', $current_time + ( $time_slot_duration * 60 ) );
            
            $time_slots[] = array(
                'from' => $slot_start,
                'to' => $slot_end,
                'available' => true
            );
            
            $current_time += ( $time_slot_duration * 60 );
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
        foreach ( $booked_slots as $booked_slot ) {
            foreach ( $time_slots as &$slot ) {
                // Check if slots overlap
                if (
                    ( $slot['from'] >= $booked_slot->time_from && $slot['from'] < $booked_slot->time_to ) ||
                    ( $slot['to'] > $booked_slot->time_from && $slot['to'] <= $booked_slot->time_to ) ||
                    ( $slot['from'] <= $booked_slot->time_from && $slot['to'] >= $booked_slot->time_to )
                ) {
                    $slot['available'] = false;
                }
            }
        }
        
        // Check if any slots are in the past (for today's date)
        if ( date( 'Y-m-d' ) === $date ) {
            $current_time = time();
            
            foreach ( $time_slots as &$slot ) {
                $slot_start_time = strtotime( $date . ' ' . $slot['from'] );
                
                if ( $slot_start_time < $current_time ) {
                    $slot['available'] = false;
                }
            }
        }
        
        // Calculate pricing for each slot
        $base_price = $court_details['base_price'];
        
        // Check if it's a weekend
        $is_weekend = ( date( 'N', strtotime( $date ) ) >= 6 );
        $weekend_price = $court_details['weekend_price'];
        
        foreach ( $time_slots as &$slot ) {
            // Base pricing
            $slot['price'] = $base_price;
            
            // Weekend pricing
            if ( $is_weekend && $weekend_price ) {
                $slot['price'] = $weekend_price;
            }
            
            // Peak hour pricing (e.g., 6PM - 10PM)
            $peak_hour_price = $court_details['peak_hour_price'];
            $slot_hour = (int) substr( $slot['from'], 0, 2 );
            
            if ( $peak_hour_price && $slot_hour >= 18 && $slot_hour < 22 ) {
                $slot['price'] = $peak_hour_price;
            }
        }
        
        $response = array(
            'slots' => $time_slots,
            'court_data' => array(
                'name' => $court_details['title'],
                'time_slot_duration' => $time_slot_duration,
                'opening_time' => $opening_hours[$day_of_week]['from'],
                'closing_time' => $opening_hours[$day_of_week]['to'],
            )
        );
        
        return rest_ensure_response( $response );
    }

    public function check_hudle_slot_availability_ajax() {
        global $tb_hudle_api;
        
        if (!$tb_hudle_api) {
            wp_send_json_error(array('message' => 'Hudle API not initialized'));
            return;
        }
        
        $tb_hudle_api->check_slot_availability_ajax();
    }
}
