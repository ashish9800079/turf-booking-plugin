<?php
/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 */
class Turf_Booking_Activator {

    /**
     * Activate the plugin.
     * - Create necessary database tables
     * - Set up default options
     * - Add required user roles and capabilities
     */
    public static function activate() {
        // Create custom database tables
        self::create_tables();
        
        // Set up default options
        self::setup_options();
        
        // Create pages
        self::create_pages();
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Create custom database tables needed for the plugin.
     */
    private static function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Table for booking slots
        $table_name = $wpdb->prefix . 'tb_booking_slots';
        
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            court_id bigint(20) NOT NULL,
            booking_id bigint(20) DEFAULT NULL,
            booking_date date NOT NULL,
            time_from time NOT NULL,
            time_to time NOT NULL,
            status varchar(20) NOT NULL DEFAULT 'available',
            created_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY court_id (court_id),
            KEY booking_id (booking_id),
            KEY booking_date (booking_date),
            KEY status (status)
        ) $charset_collate;";
        
        // Table for booking slot changes (for maintaining history)
        $table_name_history = $wpdb->prefix . 'tb_booking_slot_history';
        
        $sql .= "CREATE TABLE $table_name_history (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            slot_id bigint(20) NOT NULL,
            court_id bigint(20) NOT NULL,
            booking_id bigint(20) DEFAULT NULL,
            booking_date date NOT NULL,
            time_from time NOT NULL,
            time_to time NOT NULL,
            status varchar(20) NOT NULL,
            created_at datetime NOT NULL,
            user_id bigint(20) DEFAULT NULL,
            PRIMARY KEY  (id),
            KEY slot_id (slot_id),
            KEY court_id (court_id),
            KEY booking_id (booking_id)
        ) $charset_collate;";
        
        // Table for payments
        $table_name_payments = $wpdb->prefix . 'tb_payments';
        
        $sql .= "CREATE TABLE $table_name_payments (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            booking_id bigint(20) NOT NULL,
            payment_id varchar(100) DEFAULT NULL,
            amount decimal(10,2) NOT NULL DEFAULT 0,
            currency varchar(10) NOT NULL DEFAULT 'INR',
            payment_method varchar(50) NOT NULL,
            payment_status varchar(50) NOT NULL DEFAULT 'pending',
            transaction_data longtext DEFAULT NULL,
            created_at datetime NOT NULL,
            updated_at datetime DEFAULT NULL,
            PRIMARY KEY  (id),
            KEY booking_id (booking_id),
            KEY payment_id (payment_id),
            KEY payment_status (payment_status)
        ) $charset_collate;";



// Add after the existing table creation code within the create_tables method

// Table for booking addons
$table_name_addons = $wpdb->prefix . 'tb_booking_addons';

$sql .= "CREATE TABLE $table_name_addons (
    id bigint(20) NOT NULL AUTO_INCREMENT,
    booking_id bigint(20) NOT NULL,
    addon_id bigint(20) NOT NULL,
    addon_name varchar(255) NOT NULL,
    addon_price decimal(10,2) NOT NULL DEFAULT 0,
    addon_type varchar(20) NOT NULL DEFAULT 'per_booking',
    created_at datetime NOT NULL,
    PRIMARY KEY  (id),
    KEY booking_id (booking_id),
    KEY addon_id (addon_id)
) $charset_collate;";




        
        // Execute SQL
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Set up default options for the plugin.
     */
    private static function setup_options() {
        // General settings
        $general_settings = array(
            'currency' => 'INR',
            'currency_symbol' => '₹',
            'date_format' => 'd/m/Y',
            'time_format' => 'H:i',
            'booking_confirmation' => 'auto', // auto, manual, payment
            'max_booking_days_advance' => 30,
            'min_booking_hours_advance' => 2,
            'cancellation_policy' => 24, // hours before booking
            'refund_policy' => 'full', // full, partial, none
        );
        
        if (!get_option('tb_general_settings')) {
            add_option('tb_general_settings', $general_settings);
        }
        
        // Payment settings
        $payment_settings = array(
            'payment_methods' => array('razorpay'),
            'razorpay_enabled' => 'yes',
            'razorpay_sandbox' => 'yes',
            'razorpay_key_id' => '',
            'razorpay_key_secret' => '',
            'require_full_payment' => 'yes',
            'deposit_amount' => 0,
            'deposit_type' => 'percentage', // percentage, fixed
        );
        
        if (!get_option('tb_payment_settings')) {
            add_option('tb_payment_settings', $payment_settings);
        }
        
        // Email settings
        $email_settings = array(
            'admin_email' => get_option('admin_email'),
            'email_from_name' => get_option('blogname'),
            'email_from_address' => get_option('admin_email'),
            'booking_confirmation_subject' => 'Your booking has been confirmed',
            'booking_confirmation_message' => "Hello {customer_name},\n\nYour booking has been confirmed.\n\nBooking Details:\nCourt: {court_name}\nDate: {booking_date}\nTime: {booking_time_from} - {booking_time_to}\nTotal: {booking_total}\n\nThank you for your booking.",
            'booking_pending_subject' => 'Your booking is pending',
            'booking_pending_message' => "Hello {customer_name},\n\nYour booking is pending confirmation.\n\nBooking Details:\nCourt: {court_name}\nDate: {booking_date}\nTime: {booking_time_from} - {booking_time_to}\nTotal: {booking_total}\n\nWe will notify you once your booking is confirmed.",
            'booking_cancelled_subject' => 'Your booking has been cancelled',
            'booking_cancelled_message' => "Hello {customer_name},\n\nYour booking has been cancelled.\n\nBooking Details:\nCourt: {court_name}\nDate: {booking_date}\nTime: {booking_time_from} - {booking_time_to}\nTotal: {booking_total}\n\nIf you did not cancel this booking, please contact us.",
            'admin_notification_subject' => 'New booking received',
            'admin_notification_message' => "Hello Admin,\n\nA new booking has been received.\n\nBooking Details:\nCustomer: {customer_name}\nEmail: {customer_email}\nPhone: {customer_phone}\nCourt: {court_name}\nDate: {booking_date}\nTime: {booking_time_from} - {booking_time_to}\nTotal: {booking_total}\n\nPlease log in to confirm the booking.",
        );
        
        if (!get_option('tb_email_settings')) {
            add_option('tb_email_settings', $email_settings);
        }
    }
    
    /**
     * Create necessary pages for the plugin.
     */
    private static function create_pages() {
        $pages = array(
            'courts' => array(
                'title' => __('Courts', 'turf-booking'),
                'content' => '[turf_booking_courts]',
            ),
            'my-account' => array(
                'title' => __('My Account', 'turf-booking'),
                'content' => '[turf_booking_account]',
            ),
            'booking' => array(
                'title' => __('Booking', 'turf-booking'),
                'content' => '[turf_booking_form]',
            ),
            'checkout' => array(
                'title' => __('Checkout', 'turf-booking'),
                'content' => '[turf_booking_checkout]',
            ),
            'booking-confirmation' => array(
                'title' => __('Booking Confirmation', 'turf-booking'),
                'content' => '[turf_booking_confirmation]',
            ),
        );
        
        $page_settings = array();
        
        foreach ($pages as $slug => $page_data) {
            // Check if the page already exists
            $page_exists = get_page_by_path($slug);
            
            if (!$page_exists) {
                // Create the page
                $page_id = wp_insert_post(
                    array(
                        'post_title' => $page_data['title'],
                        'post_content' => $page_data['content'],
                        'post_status' => 'publish',
                        'post_type' => 'page',
                        'post_name' => $slug,
                    )
                );
                $page_settings[$slug] = $page_id;
            } else {
                $page_settings[$slug] = $page_exists->ID;
            }
        }
        
        // Save page IDs
        if (!get_option('tb_page_settings')) {
            add_option('tb_page_settings', $page_settings);
        } else {
            update_option('tb_page_settings', $page_settings);
        }
    }
}
