<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and hooks for
 * the admin area functionality.
 */
class Turf_Booking_Admin {

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
     * @param      string    $plugin_name       The name of this plugin.
     * @param      string    $version    The version of this plugin.
     */
    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        
      add_action('admin_init', array($this, 'register_settings'));
      add_action('admin_init', array($this, 'register_hudle_settings'));
      add_action('admin_menu', array($this, 'add_hudle_settings_page'), 20);
    }
    
 

    /**
     * Register the stylesheets for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_styles() {
        wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/turf-booking-admin.css', array(), $this->version, 'all');
        
        // Add jQuery UI CSS for admin
        wp_enqueue_style('jquery-ui', 'https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css');
        
        // Add Font Awesome for admin icons
        wp_enqueue_style($this->plugin_name . '-fontawesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css', array(), '5.15.4', 'all');
    }

    /**
     * Register the JavaScript for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts() {
        wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/turf-booking-admin.js', array('jquery', 'jquery-ui-datepicker', 'jquery-ui-tabs', 'jquery-ui-sortable'), $this->version, false);
        
        // Add media uploader scripts
        wp_enqueue_media();
        
        // Localize script with data
        $localized_data = array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('tb_admin_nonce'),
            'currency_symbol' => $this->get_currency_symbol(),
            'delete_confirm' => __('Are you sure you want to delete this item?', 'turf-booking'),
            'cancel_booking_confirm' => __('Are you sure you want to cancel this booking?', 'turf-booking'),
        );
        
        wp_localize_script($this->plugin_name, 'tb_admin_params', $localized_data);
    }
    
    /**
     * Get currency symbol
     * 
     * @return string Currency symbol
     */
    private function get_currency_symbol() {
        $general_settings = get_option('tb_general_settings');
        return isset($general_settings['currency_symbol']) ? $general_settings['currency_symbol'] : '₹';
    }

    /**
     * Add admin menu items
     */
    public function add_admin_menu() {
        // Main menu
        add_menu_page(
            __('Turf Booking', 'turf-booking'),
            __('Turf Booking', 'turf-booking'),
            'manage_options',
            'turf-booking',
            array($this, 'display_dashboard_page'),
            'dashicons-calendar-alt',
            30
        );
        
        // Dashboard submenu
        add_submenu_page(
            'turf-booking',
            __('Dashboard', 'turf-booking'),
            __('Dashboard', 'turf-booking'),
            'manage_options',
            'turf-booking',
            array($this, 'display_dashboard_page')
        );
        
        // Courts submenu
        add_submenu_page(
            'turf-booking',
            __('Courts', 'turf-booking'),
            __('Courts', 'turf-booking'),
            'manage_options',
            'edit.php?post_type=tb_court',
            null
        );
        
        // Bookings submenu
        add_submenu_page(
            'turf-booking',
            __('Bookings', 'turf-booking'),
            __('Bookings', 'turf-booking'),
            'manage_options',
            'edit.php?post_type=tb_booking',
            null
        );
        
        // Calendar submenu
        add_submenu_page(
            'turf-booking',
            __('Calendar', 'turf-booking'),
            __('Calendar', 'turf-booking'),
            'manage_options',
            'turf-booking-calendar',
            array($this, 'display_calendar_page')
        );
        
        // Reports submenu
        add_submenu_page(
            'turf-booking',
            __('Reports', 'turf-booking'),
            __('Reports', 'turf-booking'),
            'manage_options',
            'turf-booking-reports',
            array($this, 'display_reports_page')
        );
        
        // Settings submenu
        add_submenu_page(
            'turf-booking',
            __('Settings', 'turf-booking'),
            __('Settings', 'turf-booking'),
            'manage_options',
            'turf-booking-settings',
            array($this, 'display_settings_page')
        );
    }

    /**
     * Display admin dashboard page
     */
    public function display_dashboard_page() {
        include_once TURF_BOOKING_PLUGIN_DIR . 'admin/partials/turf-booking-admin-dashboard.php';
    }

    /**
     * Display calendar page
     */
    public function display_calendar_page() {
        include_once TURF_BOOKING_PLUGIN_DIR . 'admin/partials/turf-booking-admin-calendar.php';
    }

    /**
     * Display reports page
     */
    public function display_reports_page() {
        include_once TURF_BOOKING_PLUGIN_DIR . 'admin/partials/turf-booking-admin-reports.php';
    }

    /**
     * Display settings page
     */
    public function display_settings_page() {
        include_once TURF_BOOKING_PLUGIN_DIR . 'admin/partials/turf-booking-admin-settings.php';
    }
    
    /**
     * Register settings
     */
  /**
 * Register settings
 */
public function register_settings() {
    // Register general settings with sanitization callback
    register_setting(
        'tb_general_settings', 
        'tb_general_settings', 
        array($this, 'sanitize_general_settings')
    );
    
    // Register payment settings with sanitization callback
    register_setting(
        'tb_payment_settings', 
        'tb_payment_settings', 
        array($this, 'sanitize_payment_settings')
    );
    
    // Register email settings with sanitization callback
    register_setting(
        'tb_email_settings', 
        'tb_email_settings', 
        array($this, 'sanitize_email_settings')
    );
    
    // Register page settings with sanitization callback
    register_setting(
        'tb_page_settings', 
        'tb_page_settings', 
        array($this, 'sanitize_page_settings')
    );
    
    // Register General Settings section
    add_settings_section(
        'tb_general_settings_section',
        __('General Settings', 'turf-booking'),
        array($this, 'general_settings_section_callback'),
        'tb_general_settings'
    );
    
    // Rest of your code remains the same
    // ...
}

/**
 * Sanitize general settings
 */
public function sanitize_general_settings($input) {
    // Log the input for debugging
    if (WP_DEBUG) {
        error_log('Sanitizing general settings: ' . print_r($input, true));
    }
    
    $sanitized_input = array();
    
    if (isset($input['currency'])) {
        $sanitized_input['currency'] = sanitize_text_field($input['currency']);
    }
    
    if (isset($input['currency_symbol'])) {
        $sanitized_input['currency_symbol'] = sanitize_text_field($input['currency_symbol']);
    }
    
    if (isset($input['date_format'])) {
        $sanitized_input['date_format'] = sanitize_text_field($input['date_format']);
    }
    
    if (isset($input['time_format'])) {
        $sanitized_input['time_format'] = sanitize_text_field($input['time_format']);
    }
    
    if (isset($input['booking_confirmation'])) {
        $sanitized_input['booking_confirmation'] = sanitize_text_field($input['booking_confirmation']);
    }
    
    if (isset($input['max_booking_days_advance'])) {
        $sanitized_input['max_booking_days_advance'] = intval($input['max_booking_days_advance']);
    }
    
    if (isset($input['min_booking_hours_advance'])) {
        $sanitized_input['min_booking_hours_advance'] = intval($input['min_booking_hours_advance']);
    }
    
    if (isset($input['cancellation_policy'])) {
        $sanitized_input['cancellation_policy'] = intval($input['cancellation_policy']);
    }
    
    if (isset($input['refund_policy'])) {
        $sanitized_input['refund_policy'] = sanitize_text_field($input['refund_policy']);
    }
    
    return $sanitized_input;
}

/**
 * Sanitize payment settings
 */
public function sanitize_payment_settings($input) {
    // Log the input for debugging
    if (WP_DEBUG) {
        error_log('Sanitizing payment settings: ' . print_r($input, true));
    }
    
    $sanitized_input = array();
    
    // Handle checkbox array (payment_methods)
    if (isset($input['payment_methods_submitted']) && !isset($input['payment_methods'])) {
        $sanitized_input['payment_methods'] = array();
    } elseif (isset($input['payment_methods']) && is_array($input['payment_methods'])) {
        $sanitized_input['payment_methods'] = array_map('sanitize_text_field', $input['payment_methods']);
    }
    
    if (isset($input['razorpay_enabled'])) {
        $sanitized_input['razorpay_enabled'] = sanitize_text_field($input['razorpay_enabled']);
    }
    
    if (isset($input['razorpay_sandbox'])) {
        $sanitized_input['razorpay_sandbox'] = sanitize_text_field($input['razorpay_sandbox']);
    }
    
    if (isset($input['razorpay_key_id'])) {
        $sanitized_input['razorpay_key_id'] = sanitize_text_field($input['razorpay_key_id']);
    }
    
    if (isset($input['razorpay_key_secret'])) {
        $sanitized_input['razorpay_key_secret'] = sanitize_text_field($input['razorpay_key_secret']);
    }
    
    if (isset($input['require_full_payment'])) {
        $sanitized_input['require_full_payment'] = sanitize_text_field($input['require_full_payment']);
    }
    
    if (isset($input['deposit_amount'])) {
        $sanitized_input['deposit_amount'] = floatval($input['deposit_amount']);
    }
    
    if (isset($input['deposit_type'])) {
        $sanitized_input['deposit_type'] = sanitize_text_field($input['deposit_type']);
    }
    
    if (isset($input['offline_instructions'])) {
        $sanitized_input['offline_instructions'] = wp_kses_post($input['offline_instructions']);
    }
    
    return $sanitized_input;
}

/**
 * Sanitize email settings
 */
public function sanitize_email_settings($input) {
    // Log the input for debugging
    if (WP_DEBUG) {
        error_log('Sanitizing email settings: ' . print_r($input, true));
    }
    
    $sanitized_input = array();
    
    if (isset($input['admin_email'])) {
        $sanitized_input['admin_email'] = sanitize_email($input['admin_email']);
    }
    
    if (isset($input['email_from_name'])) {
        $sanitized_input['email_from_name'] = sanitize_text_field($input['email_from_name']);
    }
    
    if (isset($input['email_from_address'])) {
        $sanitized_input['email_from_address'] = sanitize_email($input['email_from_address']);
    }
    
    if (isset($input['booking_confirmation_subject'])) {
        $sanitized_input['booking_confirmation_subject'] = sanitize_text_field($input['booking_confirmation_subject']);
    }
    
    if (isset($input['booking_confirmation_message'])) {
        $sanitized_input['booking_confirmation_message'] = wp_kses_post($input['booking_confirmation_message']);
    }
    
    if (isset($input['booking_pending_subject'])) {
        $sanitized_input['booking_pending_subject'] = sanitize_text_field($input['booking_pending_subject']);
    }
    
    if (isset($input['booking_pending_message'])) {
        $sanitized_input['booking_pending_message'] = wp_kses_post($input['booking_pending_message']);
    }
    
    if (isset($input['booking_cancelled_subject'])) {
        $sanitized_input['booking_cancelled_subject'] = sanitize_text_field($input['booking_cancelled_subject']);
    }
    
    if (isset($input['booking_cancelled_message'])) {
        $sanitized_input['booking_cancelled_message'] = wp_kses_post($input['booking_cancelled_message']);
    }
    
    if (isset($input['admin_notification_subject'])) {
        $sanitized_input['admin_notification_subject'] = sanitize_text_field($input['admin_notification_subject']);
    }
    
    if (isset($input['admin_notification_message'])) {
        $sanitized_input['admin_notification_message'] = wp_kses_post($input['admin_notification_message']);
    }
    
    return $sanitized_input;
}

/**
 * Sanitize page settings
 */
public function sanitize_page_settings($input) {
    // Log the input for debugging
    if (WP_DEBUG) {
        error_log('Sanitizing page settings: ' . print_r($input, true));
    }
    
    $sanitized_input = array();
    
    if (isset($input['courts'])) {
        $sanitized_input['courts'] = absint($input['courts']);
    }
    
    if (isset($input['my-account'])) {
        $sanitized_input['my-account'] = absint($input['my-account']);
    }
    
    if (isset($input['booking'])) {
        $sanitized_input['booking'] = absint($input['booking']);
    }
    
    if (isset($input['checkout'])) {
        $sanitized_input['checkout'] = absint($input['checkout']);
    }
    
    if (isset($input['booking-confirmation'])) {
        $sanitized_input['booking-confirmation'] = absint($input['booking-confirmation']);
    }
    
    return $sanitized_input;
}
    
    /**
     * General Settings section callback
     */
    public function general_settings_section_callback() {
        echo '<p>' . __('Configure general settings for your turf booking system.', 'turf-booking') . '</p>';
    }
    
    /**
     * Payment Settings section callback
     */
    public function payment_settings_section_callback() {
        echo '<p>' . __('Configure payment settings for your turf booking system.', 'turf-booking') . '</p>';
    }
    
    /**
     * Email Settings section callback
     */
    public function email_settings_section_callback() {
        echo '<p>' . __('Configure email settings for your turf booking system.', 'turf-booking') . '</p>';
    }
    
    /**
     * Page Settings section callback
     */
    public function page_settings_section_callback() {
        echo '<p>' . __('Configure page settings for your turf booking system.', 'turf-booking') . '</p>';
    }
    
    /**
     * Currency field callback
     */
    public function currency_field_callback() {
        $options = get_option('tb_general_settings');
        $currency = isset($options['currency']) ? $options['currency'] : 'INR';
        
        $currencies = array(
            'INR' => __('Indian Rupee (₹)', 'turf-booking'),
            'USD' => __('US Dollar ($)', 'turf-booking'),
            'EUR' => __('Euro (€)', 'turf-booking'),
            'GBP' => __('British Pound (£)', 'turf-booking'),
            'AUD' => __('Australian Dollar ($)', 'turf-booking'),
            'CAD' => __('Canadian Dollar ($)', 'turf-booking'),
            'SGD' => __('Singapore Dollar ($)', 'turf-booking'),
            'AED' => __('UAE Dirham (د.إ)', 'turf-booking'),
        );
        
        echo '<select name="tb_general_settings[currency]" id="tb_currency">';
        foreach ($currencies as $code => $name) {
            echo '<option value="' . esc_attr($code) . '" ' . selected($currency, $code, false) . '>' . esc_html($name) . '</option>';
        }
        echo '</select>';
    }
    
    /**
     * Currency Symbol field callback
     */
    public function currency_symbol_field_callback() {
        $options = get_option('tb_general_settings');
        $currency_symbol = isset($options['currency_symbol']) ? $options['currency_symbol'] : '₹';
        
        echo '<input type="text" id="tb_currency_symbol" name="tb_general_settings[currency_symbol]" value="' . esc_attr($currency_symbol) . '" class="regular-text">';
    }
    
    /**
     * Date Format field callback
     */
    public function date_format_field_callback() {
        $options = get_option('tb_general_settings');
        $date_format = isset($options['date_format']) ? $options['date_format'] : 'd/m/Y';
        
        $date_formats = array(
            'd/m/Y' => date('d/m/Y') . ' (d/m/Y)',
            'm/d/Y' => date('m/d/Y') . ' (m/d/Y)',
            'Y-m-d' => date('Y-m-d') . ' (Y-m-d)',
            'F j, Y' => date('F j, Y') . ' (F j, Y)',
            'j F, Y' => date('j F, Y') . ' (j F, Y)',
        );
        
        echo '<select name="tb_general_settings[date_format]" id="tb_date_format">';
        foreach ($date_formats as $format => $display) {
            echo '<option value="' . esc_attr($format) . '" ' . selected($date_format, $format, false) . '>' . esc_html($display) . '</option>';
        }
        echo '</select>';
    }
    
    /**
     * Time Format field callback
     */
    public function time_format_field_callback() {
        $options = get_option('tb_general_settings');
        $time_format = isset($options['time_format']) ? $options['time_format'] : 'H:i';
        
        $time_formats = array(
            'H:i' => date('H:i') . ' (H:i)',
            'g:i A' => date('g:i A') . ' (g:i A)',
            'g:i a' => date('g:i a') . ' (g:i a)',
        );
        
        echo '<select name="tb_general_settings[time_format]" id="tb_time_format">';
        foreach ($time_formats as $format => $display) {
            echo '<option value="' . esc_attr($format) . '" ' . selected($time_format, $format, false) . '>' . esc_html($display) . '</option>';
        }
        echo '</select>';
    }
    
    /**
     * Booking Confirmation field callback
     */
    public function booking_confirmation_field_callback() {
        $options = get_option('tb_general_settings');
        $booking_confirmation = isset($options['booking_confirmation']) ? $options['booking_confirmation'] : 'auto';
        
        $confirmation_methods = array(
            'auto' => __('Automatic Confirmation', 'turf-booking'),
            'manual' => __('Manual Confirmation (Admin)', 'turf-booking'),
            'payment' => __('Payment Required', 'turf-booking'),
        );
        
        echo '<select name="tb_general_settings[booking_confirmation]" id="tb_booking_confirmation">';
        foreach ($confirmation_methods as $method => $label) {
            echo '<option value="' . esc_attr($method) . '" ' . selected($booking_confirmation, $method, false) . '>' . esc_html($label) . '</option>';
        }
        echo '</select>';
        echo '<p class="description">' . __('How booking confirmations should be handled.', 'turf-booking') . '</p>';
    }
    
    /**
     * Max Booking Days Advance field callback
     */
    public function max_booking_days_advance_field_callback() {
        $options = get_option('tb_general_settings');
        $max_days = isset($options['max_booking_days_advance']) ? intval($options['max_booking_days_advance']) : 30;
        
        echo '<input type="number" id="tb_max_booking_days_advance" name="tb_general_settings[max_booking_days_advance]" value="' . esc_attr($max_days) . '" class="small-text" min="1" max="365">';
        echo '<p class="description">' . __('Maximum number of days in advance that courts can be booked.', 'turf-booking') . '</p>';
    }
    
    /**
     * Min Booking Hours Advance field callback
     */
    public function min_booking_hours_advance_field_callback() {
        $options = get_option('tb_general_settings');
        $min_hours = isset($options['min_booking_hours_advance']) ? intval($options['min_booking_hours_advance']) : 1;
        
        echo '<input type="number" id="tb_min_booking_hours_advance" name="tb_general_settings[min_booking_hours_advance]" value="' . esc_attr($min_hours) . '" class="small-text" min="0" max="48">';
        echo '<p class="description">' . __('Minimum number of hours in advance that courts can be booked.', 'turf-booking') . '</p>';
    }
    
    /**
     * Cancellation Policy field callback
     */
    public function cancellation_policy_field_callback() {
        $options = get_option('tb_general_settings');
        $cancellation_hours = isset($options['cancellation_policy']) ? intval($options['cancellation_policy']) : 24;
        
        echo '<input type="number" id="tb_cancellation_policy" name="tb_general_settings[cancellation_policy]" value="' . esc_attr($cancellation_hours) . '" class="small-text" min="0" max="168">';
        echo '<p class="description">' . __('Number of hours before booking that cancellation is allowed.', 'turf-booking') . '</p>';
    }
    
    /**
     * Refund Policy field callback
     */
    public function refund_policy_field_callback() {
        $options = get_option('tb_general_settings');
        $refund_policy = isset($options['refund_policy']) ? $options['refund_policy'] : 'full';
        
        $refund_policies = array(
            'full' => __('Full Refund', 'turf-booking'),
            'partial' => __('Partial Refund', 'turf-booking'),
            'none' => __('No Refund', 'turf-booking'),
        );
        
        echo '<select name="tb_general_settings[refund_policy]" id="tb_refund_policy">';
        foreach ($refund_policies as $policy => $label) {
            echo '<option value="' . esc_attr($policy) . '" ' . selected($refund_policy, $policy, false) . '>' . esc_html($label) . '</option>';
        }
        echo '</select>';
        echo '<p class="description">' . __('Refund policy for cancelled bookings.', 'turf-booking') . '</p>';
    }
    
    /**
     * Payment Methods field callback
     */
    public function payment_methods_field_callback() {
        $options = get_option('tb_payment_settings');
        $payment_methods = isset($options['payment_methods']) ? (array) $options['payment_methods'] : array('razorpay');
        
        echo '<label><input type="checkbox" name="tb_payment_settings[payment_methods][]" value="razorpay" ' . checked(in_array('razorpay', $payment_methods), true, false) . '> ' . __('Razorpay', 'turf-booking') . '</label><br>';
        echo '<label><input type="checkbox" name="tb_payment_settings[payment_methods][]" value="offline" ' . checked(in_array('offline', $payment_methods), true, false) . '> ' . __('Offline Payment', 'turf-booking') . '</label>';
    }
    
    /**
     * Enable Razorpay field callback
     */
    public function razorpay_enabled_field_callback() {
        $options = get_option('tb_payment_settings');
        $razorpay_enabled = isset($options['razorpay_enabled']) ? $options['razorpay_enabled'] : 'yes';
        
        echo '<label><input type="radio" name="tb_payment_settings[razorpay_enabled]" value="yes" ' . checked($razorpay_enabled, 'yes', false) . '> ' . __('Yes', 'turf-booking') . '</label>';
        echo '<label style="margin-left: 15px;"><input type="radio" name="tb_payment_settings[razorpay_enabled]" value="no" ' . checked($razorpay_enabled, 'no', false) . '> ' . __('No', 'turf-booking') . '</label>';
    }
    
    /**
     * Razorpay Sandbox field callback
     */
    public function razorpay_sandbox_field_callback() {
        $options = get_option('tb_payment_settings');
        $razorpay_sandbox = isset($options['razorpay_sandbox']) ? $options['razorpay_sandbox'] : 'yes';
        
        echo '<label><input type="radio" name="tb_payment_settings[razorpay_sandbox]" value="yes" ' . checked($razorpay_sandbox, 'yes', false) . '> ' . __('Yes', 'turf-booking') . '</label>';
        echo '<label style="margin-left: 15px;"><input type="radio" name="tb_payment_settings[razorpay_sandbox]" value="no" ' . checked($razorpay_sandbox, 'no', false) . '> ' . __('No', 'turf-booking') . '</label>';
        echo '<p class="description">' . __('Use Razorpay sandbox for testing.', 'turf-booking') . '</p>';
    }
    
    /**
     * Razorpay Key ID field callback
     */
    public function razorpay_key_id_field_callback() {
        $options = get_option('tb_payment_settings');
        $razorpay_key_id = isset($options['razorpay_key_id']) ? $options['razorpay_key_id'] : '';
        
        echo '<input type="text" id="tb_razorpay_key_id" name="tb_payment_settings[razorpay_key_id]" value="' . esc_attr($razorpay_key_id) . '" class="regular-text">';
    }
    
    /**
     * Razorpay Key Secret field callback
     */
    public function razorpay_key_secret_field_callback() {
        $options = get_option('tb_payment_settings');
        $razorpay_key_secret = isset($options['razorpay_key_secret']) ? $options['razorpay_key_secret'] : '';
        
        echo '<input type="password" id="tb_razorpay_key_secret" name="tb_payment_settings[razorpay_key_secret]" value="' . esc_attr($razorpay_key_secret) . '" class="regular-text">';
    }
    
    /**
     * Require Full Payment field callback
     */
    public function require_full_payment_field_callback() {
        $options = get_option('tb_payment_settings');
        $require_full_payment = isset($options['require_full_payment']) ? $options['require_full_payment'] : 'yes';
        
        echo '<label><input type="radio" name="tb_payment_settings[require_full_payment]" value="yes" ' . checked($require_full_payment, 'yes', false) . '> ' . __('Yes', 'turf-booking') . '</label>';
        echo '<label style="margin-left: 15px;"><input type="radio" name="tb_payment_settings[require_full_payment]" value="no" ' . checked($require_full_payment, 'no', false) . '> ' . __('No', 'turf-booking') . '</label>';
        echo '<p class="description">' . __('Whether to require full payment at the time of booking.', 'turf-booking') . '</p>';
    }
    
    /**
     * Deposit Amount field callback
     */
    public function deposit_amount_field_callback() {
        $options = get_option('tb_payment_settings');
        $deposit_amount = isset($options['deposit_amount']) ? floatval($options['deposit_amount']) : 0;
        
        echo '<input type="number" id="tb_deposit_amount" name="tb_payment_settings[deposit_amount]" value="' . esc_attr($deposit_amount) . '" class="regular-text" min="0" step="0.01">';
        echo '<p class="description">' . __('Amount or percentage required as deposit if full payment is not required.', 'turf-booking') . '</p>';
    }
    
    /**
     * Deposit Type field callback
     */
    public function deposit_type_field_callback() {
        $options = get_option('tb_payment_settings');
        $deposit_type = isset($options['deposit_type']) ? $options['deposit_type'] : 'percentage';
        
        echo '<label><input type="radio" name="tb_payment_settings[deposit_type]" value="percentage" ' . checked($deposit_type, 'percentage', false) . '> ' . __('Percentage', 'turf-booking') . '</label>';
        echo '<label style="margin-left: 15px;"><input type="radio" name="tb_payment_settings[deposit_type]" value="fixed" ' . checked($deposit_type, 'fixed', false) . '> ' . __('Fixed Amount', 'turf-booking') . '</label>';
    }
    
    /**
     * Admin Email field callback
     */
    public function admin_email_field_callback() {
        $options = get_option('tb_email_settings');
        $admin_email = isset($options['admin_email']) ? $options['admin_email'] : get_option('admin_email');
        
        echo '<input type="email" id="tb_admin_email" name="tb_email_settings[admin_email]" value="' . esc_attr($admin_email) . '" class="regular-text">';
    }
    
    /**
     * Email From Name field callback
     */
    public function email_from_name_field_callback() {
        $options = get_option('tb_email_settings');
        $from_name = isset($options['email_from_name']) ? $options['email_from_name'] : get_bloginfo('name');
        
        echo '<input type="text" id="tb_email_from_name" name="tb_email_settings[email_from_name]" value="' . esc_attr($from_name) . '" class="regular-text">';
    }
    
    /**
     * Email From Address field callback
     */
    public function email_from_address_field_callback() {
        $options = get_option('tb_email_settings');
        $from_address = isset($options['email_from_address']) ? $options['email_from_address'] : get_option('admin_email');
        
        echo '<input type="email" id="tb_email_from_address" name="tb_email_settings[email_from_address]" value="' . esc_attr($from_address) . '" class="regular-text">';
    }
    
    /**
     * Booking Confirmation Subject field callback
     */
    public function booking_confirmation_subject_field_callback() {
        $options = get_option('tb_email_settings');
        $subject = isset($options['booking_confirmation_subject']) ? $options['booking_confirmation_subject'] : __('Your booking has been confirmed', 'turf-booking');
        
        echo '<input type="text" id="tb_booking_confirmation_subject" name="tb_email_settings[booking_confirmation_subject]" value="' . esc_attr($subject) . '" class="regular-text">';
    }
    
    /**
     * Booking Confirmation Message field callback
     */
    public function booking_confirmation_message_field_callback() {
        $options = get_option('tb_email_settings');
        $default_message = "Hello {customer_name},\n\nYour booking has been confirmed.\n\nBooking Details:\nCourt: {court_name}\nDate: {booking_date}\nTime: {booking_time_from} - {booking_time_to}\nTotal: {booking_total}\n\nThank you for your booking.";
        $message = isset($options['booking_confirmation_message']) ? $options['booking_confirmation_message'] : $default_message;
        
        echo '<textarea id="tb_booking_confirmation_message" name="tb_email_settings[booking_confirmation_message]" rows="10" class="large-text">' . esc_textarea($message) . '</textarea>';
        echo '<p class="description">' . __('Available placeholders: {booking_id}, {court_name}, {customer_name}, {customer_email}, {customer_phone}, {booking_date}, {booking_time_from}, {booking_time_to}, {booking_total}', 'turf-booking') . '</p>';
    }
    
    /**
     * Booking Pending Subject field callback
     */
    public function booking_pending_subject_field_callback() {
        $options = get_option('tb_email_settings');
        $subject = isset($options['booking_pending_subject']) ? $options['booking_pending_subject'] : __('Your booking is pending', 'turf-booking');
        
        echo '<input type="text" id="tb_booking_pending_subject" name="tb_email_settings[booking_pending_subject]" value="' . esc_attr($subject) . '" class="regular-text">';
    }
    
    /**
     * Booking Pending Message field callback
     */
    public function booking_pending_message_field_callback() {
        $options = get_option('tb_email_settings');
        $default_message = "Hello {customer_name},\n\nYour booking is pending confirmation.\n\nBooking Details:\nCourt: {court_name}\nDate: {booking_date}\nTime: {booking_time_from} - {booking_time_to}\nTotal: {booking_total}\n\nWe will notify you once your booking is confirmed.";
        $message = isset($options['booking_pending_message']) ? $options['booking_pending_message'] : $default_message;
        
        echo '<textarea id="tb_booking_pending_message" name="tb_email_settings[booking_pending_message]" rows="10" class="large-text">' . esc_textarea($message) . '</textarea>';
        echo '<p class="description">' . __('Available placeholders: {booking_id}, {court_name}, {customer_name}, {customer_email}, {customer_phone}, {booking_date}, {booking_time_from}, {booking_time_to}, {booking_total}', 'turf-booking') . '</p>';
    }
    
    /**
     * Booking Cancelled Subject field callback
     */
    public function booking_cancelled_subject_field_callback() {
        $options = get_option('tb_email_settings');
        $subject = isset($options['booking_cancelled_subject']) ? $options['booking_cancelled_subject'] : __('Your booking has been cancelled', 'turf-booking');
        
        echo '<input type="text" id="tb_booking_cancelled_subject" name="tb_email_settings[booking_cancelled_subject]" value="' . esc_attr($subject) . '" class="regular-text">';
    }
    
    /**
     * Booking Cancelled Message field callback
     */
    public function booking_cancelled_message_field_callback() {
        $options = get_option('tb_email_settings');
        $default_message = "Hello {customer_name},\n\nYour booking has been cancelled.\n\nBooking Details:\nCourt: {court_name}\nDate: {booking_date}\nTime: {booking_time_from} - {booking_time_to}\nTotal: {booking_total}\n\nIf you did not cancel this booking, please contact us.";
        $message = isset($options['booking_cancelled_message']) ? $options['booking_cancelled_message'] : $default_message;
        
        echo '<textarea id="tb_booking_cancelled_message" name="tb_email_settings[booking_cancelled_message]" rows="10" class="large-text">' . esc_textarea($message) . '</textarea>';
        echo '<p class="description">' . __('Available placeholders: {booking_id}, {court_name}, {customer_name}, {customer_email}, {customer_phone}, {booking_date}, {booking_time_from}, {booking_time_to}, {booking_total}', 'turf-booking') . '</p>';
    }
    
    /**
     * Admin Notification Subject field callback
     */
    public function admin_notification_subject_field_callback() {
        $options = get_option('tb_email_settings');
        $subject = isset($options['admin_notification_subject']) ? $options['admin_notification_subject'] : __('New booking received', 'turf-booking');
        
        echo '<input type="text" id="tb_admin_notification_subject" name="tb_email_settings[admin_notification_subject]" value="' . esc_attr($subject) . '" class="regular-text">';
    }
    
    /**
     * Admin Notification Message field callback
     */
    public function admin_notification_message_field_callback() {
        $options = get_option('tb_email_settings');
        $default_message = "Hello Admin,\n\nA new booking has been received.\n\nBooking Details:\nCustomer: {customer_name}\nEmail: {customer_email}\nPhone: {customer_phone}\nCourt: {court_name}\nDate: {booking_date}\nTime: {booking_time_from} - {booking_time_to}\nTotal: {booking_total}\n\nPlease log in to confirm the booking.";
        $message = isset($options['admin_notification_message']) ? $options['admin_notification_message'] : $default_message;
        
        echo '<textarea id="tb_admin_notification_message" name="tb_email_settings[admin_notification_message]" rows="10" class="large-text">' . esc_textarea($message) . '</textarea>';
        echo '<p class="description">' . __('Available placeholders: {booking_id}, {court_name}, {customer_name}, {customer_email}, {customer_phone}, {booking_date}, {booking_time_from}, {booking_time_to}, {booking_total}', 'turf-booking') . '</p>';
    }
    
    /**
     * Page Courts field callback
     */
    public function page_courts_field_callback() {
        $options = get_option('tb_page_settings');
        $page_id = isset($options['courts']) ? $options['courts'] : 0;
        
        wp_dropdown_pages(array(
            'name' => 'tb_page_settings[courts]',
            'id' => 'tb_page_courts',
            'selected' => $page_id,
            'show_option_none' => __('Select a page', 'turf-booking'),
        ));
        echo ' <a href="' . admin_url('post.php?post=' . $page_id . '&action=edit') . '" class="button">' . __('Edit Page', 'turf-booking') . '</a>';
    }
    
    /**
     * Page My Account field callback
     */
    public function page_my_account_field_callback() {
        $options = get_option('tb_page_settings');
        $page_id = isset($options['my-account']) ? $options['my-account'] : 0;
        
        wp_dropdown_pages(array(
            'name' => 'tb_page_settings[my-account]',
            'id' => 'tb_page_my_account',
            'selected' => $page_id,
            'show_option_none' => __('Select a page', 'turf-booking'),
        ));
        echo ' <a href="' . admin_url('post.php?post=' . $page_id . '&action=edit') . '" class="button">' . __('Edit Page', 'turf-booking') . '</a>';
    }
    
    /**
     * Page Booking field callback
     */
    public function page_booking_field_callback() {
        $options = get_option('tb_page_settings');
        $page_id = isset($options['booking']) ? $options['booking'] : 0;
        
        wp_dropdown_pages(array(
            'name' => 'tb_page_settings[booking]',
            'id' => 'tb_page_booking',
            'selected' => $page_id,
            'show_option_none' => __('Select a page', 'turf-booking'),
        ));
        echo ' <a href="' . admin_url('post.php?post=' . $page_id . '&action=edit') . '" class="button">' . __('Edit Page', 'turf-booking') . '</a>';
    }
    
    /**
     * Page Checkout field callback
     */
    public function page_checkout_field_callback() {
        $options = get_option('tb_page_settings');
        $page_id = isset($options['checkout']) ? $options['checkout'] : 0;
        
        wp_dropdown_pages(array(
            'name' => 'tb_page_settings[checkout]',
            'id' => 'tb_page_checkout',
            'selected' => $page_id,
            'show_option_none' => __('Select a page', 'turf-booking'),
        ));
        echo ' <a href="' . admin_url('post.php?post=' . $page_id . '&action=edit') . '" class="button">' . __('Edit Page', 'turf-booking') . '</a>';
    }
    
    /**
     * Page Booking Confirmation field callback
     */
    public function page_booking_confirmation_field_callback() {
        $options = get_option('tb_page_settings');
        $page_id = isset($options['booking-confirmation']) ? $options['booking-confirmation'] : 0;
        
        wp_dropdown_pages(array(
            'name' => 'tb_page_settings[booking-confirmation]',
            'id' => 'tb_page_booking_confirmation',
            'selected' => $page_id,
            'show_option_none' => __('Select a page', 'turf-booking'),
        ));
        echo ' <a href="' . admin_url('post.php?post=' . $page_id . '&action=edit') . '" class="button">' . __('Edit Page', 'turf-booking') . '</a>';
    }
    
    /**
     * Add action links to plugins page
     */
    public function add_action_links($links) {
        $plugin_links = array(
            '<a href="' . admin_url('admin.php?page=turf-booking-settings') . '">' . __('Settings', 'turf-booking') . '</a>',
        );
        
        return array_merge($plugin_links, $links);
    }
    
    /**
     * Create a link to the admin dashboard on admin bar
     */
    public function add_admin_bar_menu($wp_admin_bar) {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        $wp_admin_bar->add_node(array(
            'id' => 'turf-booking',
            'title' => __('Turf Booking', 'turf-booking'),
            'href' => admin_url('admin.php?page=turf-booking'),
        ));
        
        $wp_admin_bar->add_node(array(
            'parent' => 'turf-booking',
            'id' => 'turf-booking-dashboard',
            'title' => __('Dashboard', 'turf-booking'),
            'href' => admin_url('admin.php?page=turf-booking'),
        ));
        
        $wp_admin_bar->add_node(array(
            'parent' => 'turf-booking',
            'id' => 'turf-booking-courts',
            'title' => __('Courts', 'turf-booking'),
            'href' => admin_url('edit.php?post_type=tb_court'),
        ));
        
        $wp_admin_bar->add_node(array(
            'parent' => 'turf-booking',
            'id' => 'turf-booking-bookings',
            'title' => __('Bookings', 'turf-booking'),
            'href' => admin_url('edit.php?post_type=tb_booking'),
        ));
        
        $wp_admin_bar->add_node(array(
            'parent' => 'turf-booking',
            'id' => 'turf-booking-calendar',
            'title' => __('Calendar', 'turf-booking'),
            'href' => admin_url('admin.php?page=turf-booking-calendar'),
        ));
        
        $wp_admin_bar->add_node(array(
            'parent' => 'turf-booking',
            'id' => 'turf-booking-settings',
            'title' => __('Settings', 'turf-booking'),
            'href' => admin_url('admin.php?page=turf-booking-settings'),
        ));
    }
    
    /**
     * Add dashboard widgets
     */
    public function add_dashboard_widgets() {
        wp_add_dashboard_widget(
            'tb_dashboard_widget',
            __('Turf Booking Overview', 'turf-booking'),
            array($this, 'dashboard_widget_callback')
        );
    }
    
    /**
     * Dashboard widget callback
     */
    public function dashboard_widget_callback() {
        // Get quick stats
        $bookings_today = $this->get_bookings_count_for_date(date('Y-m-d'));
        $bookings_upcoming = $this->get_upcoming_bookings_count();
        $courts_count = wp_count_posts('tb_court')->publish;
        
        echo '<div class="tb-dashboard-widget">';
        echo '<p><strong>' . __('Today\'s Bookings:', 'turf-booking') . '</strong> ' . $bookings_today . '</p>';
        echo '<p><strong>' . __('Upcoming Bookings:', 'turf-booking') . '</strong> ' . $bookings_upcoming . '</p>';
        echo '<p><strong>' . __('Active Courts:', 'turf-booking') . '</strong> ' . $courts_count . '</p>';
        echo '<p><a href="' . admin_url('admin.php?page=turf-booking') . '" class="button">' . __('Go to Turf Booking Dashboard', 'turf-booking') . '</a></p>';
        echo '</div>';
    }
    
    /**
     * Get bookings count for a specific date
     */
    private function get_bookings_count_for_date($date) {
        $args = array(
            'post_type' => 'tb_booking',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'meta_query' => array(
                array(
                    'key' => '_tb_booking_date',
                    'value' => $date,
                    'compare' => '=',
                ),
            ),
        );
        
        $query = new WP_Query($args);
        return $query->found_posts;
    }
    
    /**
     * Get upcoming bookings count
     */
    private function get_upcoming_bookings_count() {
        $args = array(
            'post_type' => 'tb_booking',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'meta_query' => array(
                array(
                    'key' => '_tb_booking_date',
                    'value' => date('Y-m-d'),
                    'compare' => '>=',
                    'type' => 'DATE',
                ),
                array(
                    'key' => '_tb_booking_status',
                    'value' => array('pending', 'confirmed'),
                    'compare' => 'IN',
                ),
            ),
        );
        
        $query = new WP_Query($args);
        return $query->found_posts;
    }
    
    /**
     * Process AJAX requests for the admin area
     */
    public function process_admin_ajax() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'tb_admin_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed', 'turf-booking')));
        }
        
        // Check if user has permission
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You do not have permission to perform this action', 'turf-booking')));
        }
        
        // Get action
        $action = isset($_POST['admin_action']) ? sanitize_text_field($_POST['admin_action']) : '';
        
        // Process action
        switch ($action) {
            case 'get_bookings_for_date':
                $date = isset($_POST['date']) ? sanitize_text_field($_POST['date']) : date('Y-m-d');
                $bookings = $this->get_bookings_for_date($date);
                wp_send_json_success(array('bookings' => $bookings));
                break;
                
            case 'get_court_availability':
                $court_id = isset($_POST['court_id']) ? absint($_POST['court_id']) : 0;
                $date = isset($_POST['date']) ? sanitize_text_field($_POST['date']) : date('Y-m-d');
                $availability = $this->get_court_availability($court_id, $date);
                wp_send_json_success(array('availability' => $availability));
                break;
                
            case 'confirm_booking':
                $booking_id = isset($_POST['booking_id']) ? absint($_POST['booking_id']) : 0;
                $result = $this->confirm_booking($booking_id);
                if (is_wp_error($result)) {
                    wp_send_json_error(array('message' => $result->get_error_message()));
                } else {
                    wp_send_json_success(array('message' => __('Booking confirmed successfully', 'turf-booking')));
                }
                break;
                
            case 'cancel_booking':
                $booking_id = isset($_POST['booking_id']) ? absint($_POST['booking_id']) : 0;
                $result = $this->cancel_booking($booking_id);
                if (is_wp_error($result)) {
                    wp_send_json_error(array('message' => $result->get_error_message()));
                } else {
                    wp_send_json_success(array('message' => __('Booking cancelled successfully', 'turf-booking')));
                }
                break;
                
            case 'get_reports_data':
                $report_type = isset($_POST['report_type']) ? sanitize_text_field($_POST['report_type']) : 'bookings';
                $date_range = isset($_POST['date_range']) ? sanitize_text_field($_POST['date_range']) : 'this-month';
                $start_date = isset($_POST['start_date']) ? sanitize_text_field($_POST['start_date']) : '';
                $end_date = isset($_POST['end_date']) ? sanitize_text_field($_POST['end_date']) : '';
                
                $reports_data = $this->get_reports_data($report_type, $date_range, $start_date, $end_date);
                wp_send_json_success(array('data' => $reports_data));
                break;
                
            default:
                wp_send_json_error(array('message' => __('Invalid action', 'turf-booking')));
                break;
        }
    }
    
    /**
     * Get bookings for a specific date
     */
    private function get_bookings_for_date($date) {
        $args = array(
            'post_type' => 'tb_booking',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'meta_key' => '_tb_booking_date',
            'meta_value' => $date,
            'orderby' => 'meta_value',
            'order' => 'ASC',
        );
        
        $bookings_query = new WP_Query($args);
        $bookings = array();
        
        if ($bookings_query->have_posts()) {
            while ($bookings_query->have_posts()) {
                $bookings_query->the_post();
                $booking_id = get_the_ID();
                
                $court_id = get_post_meta($booking_id, '_tb_booking_court_id', true);
                $time_from = get_post_meta($booking_id, '_tb_booking_time_from', true);
                $time_to = get_post_meta($booking_id, '_tb_booking_time_to', true);
                $status = get_post_meta($booking_id, '_tb_booking_status', true);
                $user_name = get_post_meta($booking_id, '_tb_booking_user_name', true);
                $user_email = get_post_meta($booking_id, '_tb_booking_user_email', true);
                $user_phone = get_post_meta($booking_id, '_tb_booking_user_phone', true);
                $payment_status = get_post_meta($booking_id, '_tb_booking_payment_status', true);
                
                $bookings[] = array(
                    'id' => $booking_id,
                    'court_id' => $court_id,
                    'court_name' => get_the_title($court_id),
                    'date' => $date,
                    'time_from' => $time_from,
                    'time_to' => $time_to,
                    'status' => $status,
                    'user_name' => $user_name,
                    'user_email' => $user_email,
                    'user_phone' => $user_phone,
                    'payment_status' => $payment_status,
                );
            }
            
            wp_reset_postdata();
        }
        
        return $bookings;
    }
    
    /**
     * Get court availability for a specific date
     */
    private function get_court_availability($court_id, $date) {
        // Get court details
        $court = get_post($court_id);
        
        if (!$court || $court->post_type !== 'tb_court') {
            return new WP_Error('invalid_court', __('Invalid court', 'turf-booking'));
        }
        
        // Get court opening hours for the day of week
        $day_of_week = strtolower(date('l', strtotime($date)));
        $opening_hours = get_post_meta($court_id, '_tb_court_opening_hours', true);
        
        // Check if court is closed on this day
        if (isset($opening_hours[$day_of_week]['closed']) && $opening_hours[$day_of_week]['closed']) {
            return new WP_Error('court_closed', __('Court is closed on this day', 'turf-booking'));
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
                'available' => true,
                'booking_id' => null,
                'user_name' => '',
            );
            
            $current_time += ($time_slot_duration * 60);
        }
        
        // Check which slots are already booked
        global $wpdb;
        $table_name = $wpdb->prefix . 'tb_booking_slots';
        
        $booked_slots = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT time_from, time_to, booking_id FROM $table_name 
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
                    $slot['booking_id'] = $booked_slot->booking_id;
                    
                    // Get booking user name
                    $user_name = get_post_meta($booked_slot->booking_id, '_tb_booking_user_name', true);
                    $slot['user_name'] = $user_name;
                }
            }
        }
        
        return $time_slots;
    }
    
    /**
     * Confirm a booking
     */
    private function confirm_booking($booking_id) {
        if (!$booking_id) {
            return new WP_Error('invalid_booking', __('Invalid booking ID', 'turf-booking'));
        }
        
        // Check if booking exists
        $booking = get_post($booking_id);
        
        if (!$booking || $booking->post_type !== 'tb_booking') {
            return new WP_Error('booking_not_found', __('Booking not found', 'turf-booking'));
        }
        
        // Check if booking is in pending status
        $booking_status = get_post_meta($booking_id, '_tb_booking_status', true);
        
        if ($booking_status !== 'pending') {
            return new WP_Error('invalid_status', __('Booking is not in pending status', 'turf-booking'));
        }
        
        // Update booking status
        update_post_meta($booking_id, '_tb_booking_status', 'confirmed');
        
        // Send confirmation email
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
        
        // Sync to Hudle
        do_action('tb_after_booking_confirmed', $booking_id);
        
        return true;
    }
    
    /**
     * Cancel a booking
     */
    private function cancel_booking($booking_id) {
        if (!$booking_id) {
            return new WP_Error('invalid_booking', __('Invalid booking ID', 'turf-booking'));
        }
        
        // Check if booking exists
        $booking = get_post($booking_id);
        
        if (!$booking || $booking->post_type !== 'tb_booking') {
            return new WP_Error('booking_not_found', __('Booking not found', 'turf-booking'));
        }
        
        // Check if booking can be cancelled
        $booking_status = get_post_meta($booking_id, '_tb_booking_status', true);
        
        if ($booking_status === 'cancelled' || $booking_status === 'completed') {
            return new WP_Error('invalid_status', __('Booking cannot be cancelled', 'turf-booking'));
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
                    'user_id' => get_current_user_id(),
                ),
                array('%d', '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%d')
            );
        }
        
        // Check if payment was made
        $payment_status = get_post_meta($booking_id, '_tb_booking_payment_status', true);
        
        if ($payment_status === 'completed') {
            // Process refund according to policy
            $general_settings = get_option('tb_general_settings');
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
        
        return true;
    }
    
    /**
     * Get reports data
     */
    private function get_reports_data($report_type, $date_range, $start_date = '', $end_date = '') {
        // Determine date range
        switch ($date_range) {
            case 'today':
                $start_date = date('Y-m-d');
                $end_date = date('Y-m-d');
                break;
                
            case 'yesterday':
                $start_date = date('Y-m-d', strtotime('-1 day'));
                $end_date = date('Y-m-d', strtotime('-1 day'));
                break;
                
            case 'this-week':
                $start_date = date('Y-m-d', strtotime('monday this week'));
                $end_date = date('Y-m-d', strtotime('sunday this week'));
                break;
                
            case 'last-week':
                $start_date = date('Y-m-d', strtotime('monday last week'));
                $end_date = date('Y-m-d', strtotime('sunday last week'));
                break;
                
            case 'this-month':
                $start_date = date('Y-m-01');
                $end_date = date('Y-m-t');
                break;
                
            case 'last-month':
                $start_date = date('Y-m-01', strtotime('last month'));
                $end_date = date('Y-m-t', strtotime('last month'));
                break;
                
            case 'this-year':
                $start_date = date('Y-01-01');
                $end_date = date('Y-12-31');
                break;
                
            case 'last-year':
                $start_date = date('Y-01-01', strtotime('last year'));
                $end_date = date('Y-12-31', strtotime('last year'));
                break;
                
            case 'custom':
                // Use the provided dates
                break;
        }
        
        // Process different report types
        switch ($report_type) {
            case 'bookings':
                return $this->get_bookings_report($start_date, $end_date);
                break;
                
            case 'revenue':
                return $this->get_revenue_report($start_date, $end_date);
                break;
                
            case 'courts':
                return $this->get_courts_report($start_date, $end_date);
                break;
                
            default:
                return new WP_Error('invalid_report', __('Invalid report type', 'turf-booking'));
                break;
        }
    }
    
    /**
     * Get bookings report
     */
    private function get_bookings_report($start_date, $end_date) {
        $report_data = array(
            'total_bookings' => 0,
            'confirmed_bookings' => 0,
            'pending_bookings' => 0,
            'cancelled_bookings' => 0,
            'completed_bookings' => 0,
            'daily_bookings' => array(),
        );
        
        // Query bookings
        $args = array(
            'post_type' => 'tb_booking',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'meta_query' => array(
                array(
                    'key' => '_tb_booking_date',
                    'value' => array($start_date, $end_date),
                    'compare' => 'BETWEEN',
                    'type' => 'DATE',
                ),
            ),
        );
        
        $bookings_query = new WP_Query($args);
        
        if ($bookings_query->have_posts()) {
            $report_data['total_bookings'] = $bookings_query->found_posts;
            
            // Initialize daily bookings array
            $current = $start_date;
            while ($current <= $end_date) {
                $report_data['daily_bookings'][$current] = 0;
                $current = date('Y-m-d', strtotime('+1 day', strtotime($current)));
            }
            
            // Process each booking
            while ($bookings_query->have_posts()) {
                $bookings_query->the_post();
                $booking_id = get_the_ID();
                
                $status = get_post_meta($booking_id, '_tb_booking_status', true);
                $booking_date = get_post_meta($booking_id, '_tb_booking_date', true);
                
                // Count by status
                switch ($status) {
                    case 'confirmed':
                        $report_data['confirmed_bookings']++;
                        break;
                        
                    case 'pending':
                        $report_data['pending_bookings']++;
                        break;
                        
                    case 'cancelled':
                        $report_data['cancelled_bookings']++;
                        break;
                        
                    case 'completed':
                        $report_data['completed_bookings']++;
                        break;
                }
                
                // Add to daily bookings
                if (isset($report_data['daily_bookings'][$booking_date])) {
                    $report_data['daily_bookings'][$booking_date]++;
                }
            }
            
            wp_reset_postdata();
        }
        
        return $report_data;
    }
    
    /**
     * Get revenue report
     */
    private function get_revenue_report($start_date, $end_date) {
        $report_data = array(
            'total_revenue' => 0,
            'completed_payments' => 0,
            'pending_payments' => 0,
            'refunded_payments' => 0,
            'daily_revenue' => array(),
        );
        
        // Query bookings
        $args = array(
            'post_type' => 'tb_booking',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'meta_query' => array(
                array(
                    'key' => '_tb_booking_date',
                    'value' => array($start_date, $end_date),
                    'compare' => 'BETWEEN',
                    'type' => 'DATE',
                ),
            ),
        );
        
        $bookings_query = new WP_Query($args);
        
        if ($bookings_query->have_posts()) {
            // Initialize daily revenue array
            $current = $start_date;
            while ($current <= $end_date) {
                $report_data['daily_revenue'][$current] = 0;
                $current = date('Y-m-d', strtotime('+1 day', strtotime($current)));
            }
            
            // Process each booking
            while ($bookings_query->have_posts()) {
                $bookings_query->the_post();
                $booking_id = get_the_ID();
                
                $payment_status = get_post_meta($booking_id, '_tb_booking_payment_status', true);
                $payment_amount = floatval(get_post_meta($booking_id, '_tb_booking_payment_amount', true));
                $booking_date = get_post_meta($booking_id, '_tb_booking_date', true);
                
                // Count by payment status
                switch ($payment_status) {
                    case 'completed':
                        $report_data['completed_payments']++;
                        $report_data['total_revenue'] += $payment_amount;
                        
                        // Add to daily revenue
                        if (isset($report_data['daily_revenue'][$booking_date])) {
                            $report_data['daily_revenue'][$booking_date] += $payment_amount;
                        }
                        break;
                        
                    case 'pending':
                        $report_data['pending_payments']++;
                        break;
                        
                    case 'refunded':
                    case 'partially_refunded':
                        $report_data['refunded_payments']++;
                        break;
                }
            }
            
            wp_reset_postdata();
        }
        
        return $report_data;
    }
    
    /**
     * Get courts report
     */
    private function get_courts_report($start_date, $end_date) {
        $report_data = array(
            'court_usage' => array(),
            'total_courts' => 0,
            'most_booked_court' => '',
            'most_booked_court_id' => 0,
            'most_booked_court_count' => 0,
            'least_booked_court' => '',
            'least_booked_court_id' => 0,
            'least_booked_court_count' => 0,
        );
        
        // Get all courts
        $courts = get_posts(array(
            'post_type' => 'tb_court',
            'posts_per_page' => -1,
            'post_status' => 'publish',
        ));
        
        $report_data['total_courts'] = count($courts);
        
        // Initialize court usage array
        foreach ($courts as $court) {
            $report_data['court_usage'][$court->ID] = array(
                'id' => $court->ID,
                'name' => $court->post_title,
                'bookings_count' => 0,
                'revenue' => 0,
            );
        }
        
        // Query bookings
        $args = array(
            'post_type' => 'tb_booking',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'meta_query' => array(
                array(
                    'key' => '_tb_booking_date',
                    'value' => array($start_date, $end_date),
                    'compare' => 'BETWEEN',
                    'type' => 'DATE',
                ),
                array(
                    'key' => '_tb_booking_status',
                    'value' => array('confirmed', 'completed'),
                    'compare' => 'IN',
                ),
            ),
        );
        
        $bookings_query = new WP_Query($args);
        
        if ($bookings_query->have_posts()) {
            // Process each booking
            while ($bookings_query->have_posts()) {
                $bookings_query->the_post();
                $booking_id = get_the_ID();
                
                $court_id = get_post_meta($booking_id, '_tb_booking_court_id', true);
                $payment_amount = floatval(get_post_meta($booking_id, '_tb_booking_payment_amount', true));
                $payment_status = get_post_meta($booking_id, '_tb_booking_payment_status', true);
                
                // Add to court usage
                if (isset($report_data['court_usage'][$court_id])) {
                    $report_data['court_usage'][$court_id]['bookings_count']++;
                    
                    if ($payment_status === 'completed') {
                        $report_data['court_usage'][$court_id]['revenue'] += $payment_amount;
                    }
                }
            }
            
            wp_reset_postdata();
        }
        
        // Find most and least booked courts
        $most_booked_count = 0;
        $least_booked_count = PHP_INT_MAX;
        
        foreach ($report_data['court_usage'] as $court_id => $court_data) {
            if ($court_data['bookings_count'] > $most_booked_count) {
                $most_booked_count = $court_data['bookings_count'];
                $report_data['most_booked_court'] = $court_data['name'];
                $report_data['most_booked_court_id'] = $court_id;
                $report_data['most_booked_court_count'] = $most_booked_count;
            }
            
            if ($court_data['bookings_count'] < $least_booked_count && $court_data['bookings_count'] > 0) {
                $least_booked_count = $court_data['bookings_count'];
                $report_data['least_booked_court'] = $court_data['name'];
                $report_data['least_booked_court_id'] = $court_id;
                $report_data['least_booked_court_count'] = $least_booked_count;
            }
        }
        
        return $report_data;
    }
    


    /**
 * Get court price calculation
 */
public function get_court_price() {
    // Check nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'tb_admin_nonce')) {
        wp_send_json_error(array('message' => __('Security check failed', 'turf-booking')));
    }
    
    // Check if user has permission
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => __('You do not have permission to perform this action', 'turf-booking')));
    }
    
    $court_id = isset($_POST['court_id']) ? absint($_POST['court_id']) : 0;
    $date = isset($_POST['date']) ? sanitize_text_field($_POST['date']) : '';
    $time_from = isset($_POST['time_from']) ? sanitize_text_field($_POST['time_from']) : '';
    $time_to = isset($_POST['time_to']) ? sanitize_text_field($_POST['time_to']) : '';
    
    if (!$court_id || !$date || !$time_from || !$time_to) {
        wp_send_json_error(array('message' => __('Invalid parameters', 'turf-booking')));
    }
    
    // Get court base price
    $base_price = get_post_meta($court_id, '_tb_court_base_price', true);
    
    // Check if it's a weekend
    $is_weekend = (date('N', strtotime($date)) >= 6);
    $weekend_price = get_post_meta($court_id, '_tb_court_weekend_price', true);
    
    // Get price
    $price = $base_price;
    
    // Weekend pricing
    if ($is_weekend && $weekend_price) {
        $price = $weekend_price;
    }
    
    // Peak hour pricing (e.g., 6PM - 10PM)
    $peak_hour_price = get_post_meta($court_id, '_tb_court_peak_hour_price', true);
    $slot_hour = (int)substr($time_from, 0, 2);
    
    if ($peak_hour_price && $slot_hour >= 18 && $slot_hour < 22) {
        $price = $peak_hour_price;
    }
    
    // Calculate hours
    $time_from_obj = DateTime::createFromFormat('H:i', $time_from);
    $time_to_obj = DateTime::createFromFormat('H:i', $time_to);
    $interval = $time_from_obj->diff($time_to_obj);
    $hours = $interval->h + ($interval->i / 60);
    
    // Calculate total amount
    $amount = $price * $hours;
    
    wp_send_json_success(array(
        'amount' => $amount,
        'hourly_rate' => $price,
        'hours' => $hours
    ));
}

/**
 * Create manual booking
 */
public function create_manual_booking() {
    // Check nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'tb_admin_nonce')) {
        wp_send_json_error(array('message' => __('Security check failed', 'turf-booking')));
    }
    
    // Check if user has permission
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => __('You do not have permission to perform this action', 'turf-booking')));
    }
    
    // Parse form data
    parse_str($_POST['form_data'], $form_data);
    
    // Validate required fields
    $required_fields = array('court_id', 'date', 'time_from', 'time_to', 'name', 'email', 'phone', 'amount');
    
    foreach ($required_fields as $field) {
        if (empty($form_data[$field])) {
            wp_send_json_error(array('message' => __('Please fill in all required fields', 'turf-booking')));
        }
    }
    
    // Get form fields
    $court_id = absint($form_data['court_id']);
    $date = sanitize_text_field($form_data['date']);
    $time_from = sanitize_text_field($form_data['time_from']);
    $time_to = sanitize_text_field($form_data['time_to']);
    $name = sanitize_text_field($form_data['name']);
    $email = sanitize_email($form_data['email']);
    $phone = sanitize_text_field($form_data['phone']);
    $status = sanitize_text_field($form_data['status']);
    $payment_status = sanitize_text_field($form_data['payment_status']);
    $amount = floatval($form_data['amount']);
    $payment_method = sanitize_text_field($form_data['payment_method']);
    
    // Check if the court exists
    $court = get_post($court_id);
    if (!$court || $court->post_type !== 'tb_court') {
        wp_send_json_error(array('message' => __('Invalid court selected', 'turf-booking')));
    }
    
    // If this court has Hudle integration, verify availability in Hudle first
    global $tb_hudle_api;
    if ($tb_hudle_api && $tb_hudle_api->court_has_hudle_integration($court_id)) {
        // Check if slot is available in Hudle
        $slots = $tb_hudle_api->get_slots($court_id, $date);
        
        if (!is_wp_error($slots)) {
            $slot_available = true;
            $booking_start = new DateTime($date . ' ' . $time_from);
            $booking_end = new DateTime($date . ' ' . $time_to);
            
            foreach ($slots as $slot) {
                if (!$slot['is_available'] || $slot['inventory_count'] == 0) {
                    $slot_start = new DateTime($slot['start_time']);
                    $slot_end = new DateTime($slot['end_time']);
                    
                    if (($slot_start < $booking_end && $slot_end > $booking_start) || 
                        ($slot_start == $booking_start || $slot_end == $booking_end)) {
                        $slot_available = false;
                        break;
                    }
                }
            }
            
            if (!$slot_available) {
                wp_send_json_error(array('message' => __('This time slot is already booked in Hudle. Please select another time.', 'turf-booking')));
                return;
            }
        }
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
                (time_from = %s AND time_to = %s) OR
                (time_from < %s AND time_to > %s) OR
                (time_from < %s AND time_to > %s) OR
                (time_from > %s AND time_from < %s) OR
                (time_to > %s AND time_to < %s)
            )
            AND status = 'booked'",
            $court_id,
            $date,
            $time_from, $time_to,        // Exact match
            $time_from, $time_from,      // Start time falls within booking
            $time_to, $time_to,          // End time falls within booking
            $time_from, $time_to,        // Booking start is within our slot
            $time_from, $time_to         // Booking end is within our slot
        )
    );
    
    if ($existing_booking > 0) {
        wp_send_json_error(array('message' => __('This time slot is already booked', 'turf-booking')));
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
    update_post_meta($booking_id, '_tb_booking_status', $status);
    
    // Save user details
    $user_id = get_current_user_id(); // Admin who created the booking
    update_post_meta($booking_id, '_tb_booking_user_id', $user_id); // Set current admin as the booking creator
    update_post_meta($booking_id, '_tb_booking_user_name', $name);
    update_post_meta($booking_id, '_tb_booking_user_email', $email);
    update_post_meta($booking_id, '_tb_booking_user_phone', $phone);
    
    // Save payment details
    update_post_meta($booking_id, '_tb_booking_payment_amount', $amount);
    update_post_meta($booking_id, '_tb_booking_payment_status', $payment_status);
    update_post_meta($booking_id, '_tb_booking_payment_method', $payment_method);
    
    if ($payment_status === 'completed') {
        update_post_meta($booking_id, '_tb_booking_payment_date', current_time('mysql'));
        update_post_meta($booking_id, '_tb_booking_payment_id', 'manual-' . time());
    }
    
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
    
    // Send booking confirmation email if needed
    if ($status === 'confirmed') {
        // Get bookings object
        $bookings_obj = new Turf_Booking_Bookings();
        // Use private method through a filter
        add_filter('send_booking_confirmation_email', function() use ($bookings_obj, $booking_id) {
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
        });
        
        do_action('send_booking_confirmation_email');
        
        // Sync to Hudle
        do_action('tb_after_booking_confirmed', $booking_id);
    }
    
    wp_send_json_success(array(
        'message' => __('Booking created successfully', 'turf-booking'),
        'booking_id' => $booking_id
    ));
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
 * Add Hudle settings page to the admin menu
 */
public function add_hudle_settings_page() {
    add_submenu_page(
        'turf-booking',
        __('Hudle Integration', 'turf-booking'),
        __('Hudle Integration', 'turf-booking'),
        'manage_options',
        'turf-booking-hudle',
        array($this, 'display_hudle_settings_page')
    );
}

/**
 * Display the Hudle settings page
 */
public function display_hudle_settings_page() {
    require_once TURF_BOOKING_PLUGIN_DIR . 'admin/partials/turf-booking-admin-hudle-settings.php';
}

/**
 * Register Hudle settings
 */
public function register_hudle_settings() {
    // Register settings
    register_setting('tb_hudle_settings', 'tb_hudle_api_token');
    register_setting('tb_hudle_settings', 'tb_hudle_venue_id');
    register_setting('tb_hudle_settings', 'tb_hudle_debug_mode');
    
    // Add settings section
    add_settings_section(
        'tb_hudle_api_settings',
        __('Hudle API Settings', 'turf-booking'),
        array($this, 'hudle_api_settings_callback'),
        'tb_hudle_settings'
    );
    
    // Add settings fields
    add_settings_field(
        'tb_hudle_api_token',
        __('API Token', 'turf-booking'),
        array($this, 'hudle_api_token_callback'),
        'tb_hudle_settings',
        'tb_hudle_api_settings'
    );
    
    add_settings_field(
        'tb_hudle_venue_id',
        __('Venue ID', 'turf-booking'),
        array($this, 'hudle_venue_id_callback'),
        'tb_hudle_settings',
        'tb_hudle_api_settings'
    );
    
    add_settings_field(
        'tb_hudle_debug_mode',
        __('Debug Mode', 'turf-booking'),
        array($this, 'hudle_debug_mode_callback'),
        'tb_hudle_settings',
        'tb_hudle_api_settings'
    );
}

/**
 * Hudle API settings section callback
 */
public function hudle_api_settings_callback() {
    echo '<p>' . __('Configure your Hudle API integration. This allows your bookings to be synced with Hudle.', 'turf-booking') . '</p>';
}

/**
 * API Token field callback
 */
public function hudle_api_token_callback() {
    $api_token = get_option('tb_hudle_api_token', '');
    echo '<input type="password" id="tb_hudle_api_token" name="tb_hudle_api_token" value="' . esc_attr($api_token) . '" class="regular-text">';
    echo '<p class="description">' . __('Enter your Hudle API token for authentication', 'turf-booking') . '</p>';
}

/**
 * Venue ID field callback
 */
public function hudle_venue_id_callback() {
    $venue_id = get_option('tb_hudle_venue_id', '');
    echo '<input type="text" id="tb_hudle_venue_id" name="tb_hudle_venue_id" value="' . esc_attr($venue_id) . '" class="regular-text">';
    echo '<p class="description">' . __('Enter your Hudle venue ID (e.g., 670ab825-c490-4793-9175-87b0c854f2a0)', 'turf-booking') . '</p>';
}

/**
 * Debug Mode field callback
 */
public function hudle_debug_mode_callback() {
    $debug_mode = get_option('tb_hudle_debug_mode', false);
    echo '<input type="checkbox" id="tb_hudle_debug_mode" name="tb_hudle_debug_mode" value="1" ' . checked(1, $debug_mode, false) . '>';
    echo '<label for="tb_hudle_debug_mode">' . __('Enable debug logging for Hudle API', 'turf-booking') . '</label>';
}


}
