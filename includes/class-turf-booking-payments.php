<?php
/**
 * Handle payments with Razorpay
 */
class Turf_Booking_Payments {

    /**
     * Razorpay API Key ID
     */
    private $key_id;
    
    /**
     * Razorpay API Key Secret
     */
    private $key_secret;
    
    /**
     * Whether to use sandbox mode
     */
    private $sandbox_mode;
    
    /**
     * Initialize the class.
     */
    public function __construct() {
        // Load Razorpay settings
        $payment_settings = get_option('tb_payment_settings');
        $this->key_id = isset($payment_settings['razorpay_key_id']) ? $payment_settings['razorpay_key_id'] : '';
        $this->key_secret = isset($payment_settings['razorpay_key_secret']) ? $payment_settings['razorpay_key_secret'] : '';
        $this->sandbox_mode = isset($payment_settings['razorpay_sandbox']) && $payment_settings['razorpay_sandbox'] === 'yes';
        
       

    }
    
    
    public function test_ajax_function() {
    error_log('Test AJAX function called');
    wp_send_json_success(array('message' => 'AJAX is working'));
}
    
    /**
     * Process Razorpay payment - Create order
     */
    public function process_razorpay_payment() {
        
        
 error_log('Entering process_razorpay_payment');
    
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'tb_razorpay_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed', 'turf-booking')));
            return;
        }
        
        $booking_id = isset($_POST['booking_id']) ? absint($_POST['booking_id']) : 0;
        
        if (!$booking_id) {
            wp_send_json_error(array('message' => __('Invalid booking ID', 'turf-booking')));
            return;
        }
        
        // Log debug information
        error_log('Razorpay Process Payment - Booking ID: ' . $booking_id);
        
        // Get booking details
        $booking_details = $this->get_booking_details($booking_id);
        
        if (!$booking_details) {
            wp_send_json_error(array('message' => __('Booking not found', 'turf-booking')));
            return;
        }
        
        // Check if booking is still in pending status
        if ($booking_details['status'] !== 'pending' && $booking_details['status'] !== 'confirmed') {
            wp_send_json_error(array('message' => __('This booking cannot be processed for payment', 'turf-booking')));
            return;
        }
        
        // Check if payment is already completed
        if ($booking_details['payment_status'] === 'completed') {
            wp_send_json_error(array('message' => __('Payment has already been processed for this booking', 'turf-booking')));
            return;
        }
        
        // Check if API credentials are set
        if (empty($this->key_id) || empty($this->key_secret)) {
            wp_send_json_error(array('message' => __('Razorpay API credentials are not configured', 'turf-booking')));
            return;
        }
        
        // Create Razorpay order
        $amount = round($booking_details['payment_amount'] * 100); // Razorpay expects amount in paise
        
        // Make sure we have a positive integer amount
        if ($amount <= 0) {
            wp_send_json_error(array('message' => __('Invalid payment amount', 'turf-booking')));
            return;
        }
        
        $api_url = 'https://api.razorpay.com/v1/orders';
        
        $order_data = array(
            'amount' => $amount,
            'currency' => 'INR',
            'receipt' => 'booking_' . $booking_id,
            'payment_capture' => 1,
            'notes' => array(
                'booking_id' => $booking_id,
                'court_name' => $booking_details['court_name'],
                'booking_date' => $booking_details['date'],
                'booking_time' => $booking_details['time_from'] . ' - ' . $booking_details['time_to'],
            ),
        );
        
        error_log('Razorpay Order Data: ' . print_r($order_data, true));
        
        $args = array(
            'method' => 'POST',
            'timeout' => 45,
            'redirection' => 5,
            'httpversion' => '1.0',
            'headers' => array(
                'Authorization' => 'Basic ' . base64_encode($this->key_id . ':' . $this->key_secret),
                'Content-Type' => 'application/json',
            ),
            'body' => json_encode($order_data),
        );
        
        $response = wp_remote_post($api_url, $args);
        
        if (is_wp_error($response)) {
            $error_message = $response->get_error_message();
            error_log('Razorpay API Error: ' . $error_message);
            wp_send_json_error(array('message' => $error_message));
            return;
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        error_log('Razorpay API Response Code: ' . $response_code);
        error_log('Razorpay API Response: ' . print_r($body, true));
        
        if ($response_code >= 400) {
            $error_message = isset($body['error']['description']) ? $body['error']['description'] : __('Error creating Razorpay order', 'turf-booking');
            wp_send_json_error(array('message' => $error_message));
            return;
        }
        
        if (!isset($body['id'])) {
            wp_send_json_error(array('message' => __('Invalid response from Razorpay API', 'turf-booking')));
            return;
        }
        
        // Save Razorpay order ID to booking
        update_post_meta($booking_id, '_tb_booking_razorpay_order_id', $body['id']);
        
        // Get customer details
        $customer_name = $booking_details['user_name'];
        $customer_email = $booking_details['user_email'];
        $customer_phone = $booking_details['user_phone'];
        
        // Return payment data for frontend
        wp_send_json_success(array(
            'order_id' => $body['id'],
            'amount' => $amount,
            'currency' => 'INR',
            'key_id' => $this->key_id,
            'booking_id' => $booking_id,
            'customer_name' => $customer_name,
            'customer_email' => $customer_email,
            'customer_phone' => $customer_phone,
            'description' => sprintf(
                __('Booking for %s on %s (%s - %s)', 'turf-booking'),
                $booking_details['court_name'],
                $booking_details['date'],
                $booking_details['time_from'],
                $booking_details['time_to']
            ),
        ));
    }
    
    /**
     * Verify Razorpay payment
     */
    public function verify_razorpay_payment() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'tb_razorpay_verify_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed', 'turf-booking')));
            return;
        }
        
        $booking_id = isset($_POST['booking_id']) ? absint($_POST['booking_id']) : 0;
        $razorpay_payment_id = isset($_POST['razorpay_payment_id']) ? sanitize_text_field($_POST['razorpay_payment_id']) : '';
        $razorpay_order_id = isset($_POST['razorpay_order_id']) ? sanitize_text_field($_POST['razorpay_order_id']) : '';
        $razorpay_signature = isset($_POST['razorpay_signature']) ? sanitize_text_field($_POST['razorpay_signature']) : '';
        
        error_log('Razorpay Verify Payment - Booking ID: ' . $booking_id);
        error_log('Razorpay Payment ID: ' . $razorpay_payment_id);
        error_log('Razorpay Order ID: ' . $razorpay_order_id);
        error_log('Razorpay Signature: ' . $razorpay_signature);
        
        if (!$booking_id || !$razorpay_payment_id || !$razorpay_order_id || !$razorpay_signature) {
            wp_send_json_error(array('message' => __('Invalid payment data', 'turf-booking')));
            return;
        }
        
        // Verify signature
        $generated_signature = hash_hmac('sha256', $razorpay_order_id . '|' . $razorpay_payment_id, $this->key_secret);
        
        error_log('Generated Signature: ' . $generated_signature);
        
        if ($generated_signature !== $razorpay_signature) {
            wp_send_json_error(array('message' => __('Payment verification failed. Invalid signature.', 'turf-booking')));
            return;
        }
        
        // Fetch payment details from Razorpay
        $api_url = 'https://api.razorpay.com/v1/payments/' . $razorpay_payment_id;
        
        $args = array(
            'method' => 'GET',
            'timeout' => 45,
            'redirection' => 5,
            'httpversion' => '1.0',
            'headers' => array(
                'Authorization' => 'Basic ' . base64_encode($this->key_id . ':' . $this->key_secret),
            ),
        );
        
        $response = wp_remote_get($api_url, $args);
        
        if (is_wp_error($response)) {
            $error_message = $response->get_error_message();
            error_log('Razorpay API Error: ' . $error_message);
            wp_send_json_error(array('message' => $error_message));
            return;
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $payment_data = json_decode(wp_remote_retrieve_body($response), true);
        
        error_log('Razorpay API Response Code: ' . $response_code);
        error_log('Razorpay API Response: ' . print_r($payment_data, true));
        
        if ($response_code >= 400) {
            $error_message = isset($payment_data['error']['description']) ? $payment_data['error']['description'] : __('Error fetching payment details', 'turf-booking');
            wp_send_json_error(array('message' => $error_message));
            return;
        }
        
        // Verify payment status
        if (!isset($payment_data['status']) || $payment_data['status'] !== 'captured') {
            wp_send_json_error(array('message' => __('Payment has not been captured', 'turf-booking')));
            return;
        }
        
        // Update booking payment status
        update_post_meta($booking_id, '_tb_booking_payment_id', $razorpay_payment_id);
        update_post_meta($booking_id, '_tb_booking_payment_method', 'razorpay');
        update_post_meta($booking_id, '_tb_booking_payment_status', 'completed');
        update_post_meta($booking_id, '_tb_booking_payment_date', current_time('mysql'));
        
        // Update booking status to confirmed
        update_post_meta($booking_id, '_tb_booking_status', 'confirmed');
    
        
        // Record payment in database
        global $wpdb;
        $table_name = $wpdb->prefix . 'tb_payments';
        
        $wpdb->insert(
            $table_name,
            array(
                'booking_id' => $booking_id,
                'payment_id' => $razorpay_payment_id,
                'amount' => $payment_data['amount'] / 100, // Convert from paise to rupees
                'currency' => $payment_data['currency'],
                'payment_method' => 'razorpay',
                'payment_status' => 'completed',
                'transaction_data' => json_encode($payment_data),
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql'),
            ),
            array('%d', '%s', '%f', '%s', '%s', '%s', '%s', '%s', '%s')
        );
        
        if ($wpdb->last_error) {
            error_log('Database Error: ' . $wpdb->last_error);
        }
        
        // Send booking confirmation email
        $this->send_payment_confirmation_email($booking_id);
        
        wp_send_json_success(array(
            'message' => __('Payment successful', 'turf-booking'),
            'redirect_url' => add_query_arg('booking_id', $booking_id, get_permalink(get_option('tb_page_settings')['booking-confirmation']))
        ));
    }
    
    /**
     * Get booking details
     */
    private function get_booking_details($booking_id) {
        $booking = get_post($booking_id);
        
        if (!$booking || $booking->post_type !== 'tb_booking') {
            return false;
        }
        
        $court_id = get_post_meta($booking_id, '_tb_booking_court_id', true);
        
        $details = array(
            'id' => $booking_id,
            'court_id' => $court_id,
            'court_name' => get_the_title($court_id),
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
        );
        
        return $details;
    }
    
    /**
     * Send payment confirmation email
     */
    private function send_payment_confirmation_email($booking_id) {
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
        $payment_id = get_post_meta($booking_id, '_tb_booking_payment_id', true);
        
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
            '{payment_id}' => $payment_id,
        );
        
        return str_replace(array_keys($placeholders), array_values($placeholders), $content);
    }
    
    /**
     * Get Razorpay checkout script
     */
    public function get_razorpay_checkout_script($booking_id) {
        // Get booking details
        $booking_details = $this->get_booking_details($booking_id);
        
        if (!$booking_details) {
            return '<p>' . __('Booking not found', 'turf-booking') . '</p>';
        }
        
        // Check if booking is still in pending or confirmed status
        if ($booking_details['status'] !== 'pending' && $booking_details['status'] !== 'confirmed') {
            return '<p>' . __('This booking cannot be processed for payment', 'turf-booking') . '</p>';
        }
        
        // Check if payment is already completed
        if ($booking_details['payment_status'] === 'completed') {
            return '<p>' . __('Payment has already been processed for this booking', 'turf-booking') . '</p>';
        }
        
        // Check if API credentials are set
        if (empty($this->key_id) || empty($this->key_secret)) {
            return '<p>' . __('Razorpay API credentials are not configured. Please contact site administrator.', 'turf-booking') . '</p>';
        }
        
        // Generate nonce
        $razorpay_nonce = wp_create_nonce('tb_razorpay_nonce');
        $verify_nonce = wp_create_nonce('tb_razorpay_verify_nonce');
        
        // Get general settings
        $general_settings = get_option('tb_general_settings');
        $currency_symbol = isset($general_settings['currency_symbol']) ? $general_settings['currency_symbol'] : '₹';
        
        // Format date and time according to settings
        $date_format = isset($general_settings['date_format']) ? $general_settings['date_format'] : 'd/m/Y';
        $time_format = isset($general_settings['time_format']) ? $general_settings['time_format'] : 'H:i';
        
        $formatted_date = date($date_format, strtotime($booking_details['date']));
        $formatted_time_from = date($time_format, strtotime($booking_details['time_from']));
        $formatted_time_to = date($time_format, strtotime($booking_details['time_to']));
        
        // Output Razorpay checkout form
        ob_start();
        ?>
        <div id="razorpay-checkout-container">
            <div class="tb-payment-details">
                <h3><?php _e('Booking Details', 'turf-booking'); ?></h3>
                <table class="tb-payment-details-table">
                    <tr>
                        <th><?php _e('Court', 'turf-booking'); ?></th>
                        <td><?php echo esc_html($booking_details['court_name']); ?></td>
                    </tr>
                    <tr>
                        <th><?php _e('Date', 'turf-booking'); ?></th>
                        <td><?php echo esc_html($formatted_date); ?></td>
                    </tr>
                    <tr>
                        <th><?php _e('Time', 'turf-booking'); ?></th>
                        <td><?php echo esc_html($formatted_time_from . ' - ' . $formatted_time_to); ?></td>
                    </tr>
                    <tr>
                        <th><?php _e('Amount', 'turf-booking'); ?></th>
                        <td><?php echo esc_html($currency_symbol . number_format($booking_details['payment_amount'], 2)); ?></td>
                    </tr>
                </table>
            </div>
            <button id="razorpay-checkout-button" class="button" style=" width: 100%; "><?php _e('Pay Now', 'turf-booking'); ?></button>
        </div>
        
        <div id="razorpay-loader" style="display: none;">
            <div class="tb-loader"></div>
            <p><?php _e('Processing payment...', 'turf-booking'); ?></p>
        </div>
        
        <div id="razorpay-error" style="display: none;">
            <p class="tb-error-message"></p>
        </div>
        
        <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
        <script>
            jQuery(document).ready(function($) {
                
      
                
                $('#razorpay-checkout-button').on('click', function(e) {
                    e.preventDefault();
                    
                    // Show loader
                    $('#razorpay-checkout-container').hide();
                    $('#razorpay-loader').show();
                    
                    // Create Razorpay order
                    $.ajax({
                        url: '<?php echo admin_url('admin-ajax.php'); ?>',
                        type: 'POST',
                        data: {
                            action: 'process_razorpay_payment',
                            booking_id: <?php echo $booking_id; ?>,
                            nonce: '<?php echo $razorpay_nonce; ?>'
                        },
                        success: function(response) {
                            console.log('Process Payment Response:', response);
                            
                            if (response.success) {
                                // Show Razorpay checkout
                                var options = {
                                    key: response.data.key_id,
                                    amount: response.data.amount,
                                    currency: response.data.currency,
                                    name: '<?php echo esc_js(get_bloginfo('name')); ?>',
                                    description: response.data.description,
                                    order_id: response.data.order_id,
                                    handler: function(razorpayResponse) {
                                        console.log('Razorpay Handler Response:', razorpayResponse);
                                        
                                        // Show loader
                                        $('#razorpay-loader').show();
                                        
                                        // Verify payment
                                        $.ajax({
                                            url: '<?php echo admin_url('admin-ajax.php'); ?>',
                                            type: 'POST',
                                            data: {
                                                action: 'verify_razorpay_payment',
                                                booking_id: <?php echo $booking_id; ?>,
                                                razorpay_payment_id: razorpayResponse.razorpay_payment_id,
                                                razorpay_order_id: razorpayResponse.razorpay_order_id,
                                                razorpay_signature: razorpayResponse.razorpay_signature,
                                                nonce: '<?php echo $verify_nonce; ?>'
                                            },
                                            success: function(verifyResponse) {
                                                console.log('Verify Payment Response:', verifyResponse);
                                                
                                                if (verifyResponse.success) {
                                                    window.location.href = verifyResponse.data.redirect_url;
                                                } else {
                                                    $('#razorpay-loader').hide();
                                                    $('#razorpay-error').show();
                                                    $('#razorpay-error .tb-error-message').text(verifyResponse.data.message);
                                                }
                                            },
                                            error: function(xhr, status, error) {
                                                console.error('Verify Payment AJAX Error:', xhr.responseText);
                                                $('#razorpay-loader').hide();
                                                $('#razorpay-error').show();
                                                $('#razorpay-error .tb-error-message').text('<?php _e('An error occurred while processing your payment. Please contact support.', 'turf-booking'); ?>');
                                            }
                                        });
                                    },
                                    prefill: {
                                        name: response.data.customer_name,
                                        email: response.data.customer_email,
                                        contact: response.data.customer_phone
                                    },
                                    notes: {
                                        booking_id: response.data.booking_id
                                    },
                                    theme: {
                                        color: '#3399cc'
                                    },
                                    modal: {
                                        ondismiss: function() {
                                            $('#razorpay-loader').hide();
                                            $('#razorpay-checkout-container').show();
                                        }
                                    }
                                };
                                
                                var rzp = new Razorpay(options);
                                rzp.on('payment.failed', function (response){
                                    console.error('Payment Failed:', response.error);
                                    $('#razorpay-loader').hide();
                                    $('#razorpay-error').show();
                                    $('#razorpay-error .tb-error-message').text(response.error.description || 'Payment failed. Please try again.');
                                });
                                
                                rzp.open();
                                
                                // Hide loader
                                $('#razorpay-loader').hide();
                            } else {
                                $('#razorpay-loader').hide();
                                $('#razorpay-error').show();
                                $('#razorpay-error .tb-error-message').text(response.data.message);
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error('Process Payment AJAX Error:', xhr.responseText);
                            $('#razorpay-loader').hide();
                            $('#razorpay-error').show();
                            $('#razorpay-error .tb-error-message').text('<?php _e('An error occurred while creating your payment. Please try again.', 'turf-booking'); ?>');
                        }
                    });
                });
            });
        </script>
        <style>
            .tb-payment-details {
                margin-bottom: 20px;
            }
            
            .tb-payment-details-table {
                width: 100%;
                border-collapse: collapse;
                margin-bottom: 20px;
            }
            
            .tb-payment-details-table th,
            .tb-payment-details-table td {
                padding: 10px;
                border: 1px solid #ddd;
            }
            
            .tb-payment-details-table th {
                background-color: #f5f5f5;
                text-align: left;
                width: 30%;
            }
            
            .tb-button {
                background-color: #3399cc;
                color: #fff;
                border: none;
                padding: 10px 20px;
                font-size: 16px;
                cursor: pointer;
                border-radius: 4px;
            }
            
            .tb-button:hover {
                background-color: #2980b9;
            }
            
            .tb-loader {
                border: 4px solid #f3f3f3;
                border-top: 4px solid #3399cc;
                border-radius: 50%;
                width: 30px;
                height: 30px;
                animation: spin 2s linear infinite;
                margin: 0 auto 20px;
            }
            
            @keyframes spin {
                0% { transform: rotate(0deg); }
                100% { transform: rotate(360deg); }
            }
            
            .tb-error-message {
                color: #e74c3c;
                font-weight: bold;
            }
        </style>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Generate invoice for a booking
     */
    public function generate_invoice($booking_id) {
        // Get booking details
        $booking_details = $this->get_booking_details($booking_id);
        
        if (!$booking_details) {
            return '<p>' . __('Booking not found', 'turf-booking') . '</p>';
        }
        
        $general_settings = get_option('tb_general_settings');
        $currency_symbol = isset($general_settings['currency_symbol']) ? $general_settings['currency_symbol'] : '₹';
        
        // Format date and time according to settings
        $date_format = isset($general_settings['date_format']) ? $general_settings['date_format'] : 'd/m/Y';
        $time_format = isset($general_settings['time_format']) ? $general_settings['time_format'] : 'H:i';
        
        $formatted_date = date($date_format, strtotime($booking_details['date']));
        $formatted_time_from = date($time_format, strtotime($booking_details['time_from']));
        $formatted_time_to = date($time_format, strtotime($booking_details['time_to']));
        
        // Calculate hours
        $time_from_obj = DateTime::createFromFormat('H:i', $booking_details['time_from']);
        $time_to_obj = DateTime::createFromFormat('H:i', $booking_details['time_to']);
        $interval = $time_from_obj->diff($time_to_obj);
        $hours = $interval->h + ($interval->i / 60);
        
        // Calculate base price per hour
        $price_per_hour = $booking_details['payment_amount'] / $hours;
        
        ob_start();
        ?>
        <div class="tb-invoice">
            <div class="tb-invoice-header">
                <div class="tb-invoice-logo">
                    <?php if (has_custom_logo()) : ?>
                        <?php echo get_custom_logo(); ?>
                    <?php else : ?>
                        <h2><?php echo get_bloginfo('name'); ?></h2>
                    <?php endif; ?>
                </div>
                <div class="tb-invoice-title">
                    <h1><?php _e('INVOICE', 'turf-booking'); ?></h1>
                    <p><?php _e('Invoice #:', 'turf-booking'); ?> <?php echo esc_html($booking_id); ?></p>
                    <p><?php _e('Date:', 'turf-booking'); ?> <?php echo date($date_format); ?></p>
                </div>
            </div>
            
            <div class="tb-invoice-addresses">
                <div class="tb-invoice-from">
                    <h3><?php _e('From', 'turf-booking'); ?></h3>
                    <p><?php echo get_bloginfo('name'); ?></p>
                    <p><?php echo get_option('tb_business_address', get_bloginfo('admin_email')); ?></p>
                </div>
                <div class="tb-invoice-to">
                    <h3><?php _e('To', 'turf-booking'); ?></h3>
                    <p><?php echo esc_html($booking_details['user_name']); ?></p>
                    <p><?php echo esc_html($booking_details['user_email']); ?></p>
                    <p><?php echo esc_html($booking_details['user_phone']); ?></p>
                </div>
            </div>
            
            <div class="tb-invoice-details">
                <table class="tb-invoice-table">
                    <thead>
                        <tr>
                            <th><?php _e('Description', 'turf-booking'); ?></th>
                            <th><?php _e('Hours', 'turf-booking'); ?></th>
                            <th><?php _e('Rate', 'turf-booking'); ?></th>
                            <th><?php _e('Amount', 'turf-booking'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>
                                <?php 
                                echo sprintf(
                                    __('%s - Booking for %s (%s - %s)', 'turf-booking'),
                                    esc_html($booking_details['court_name']),
                                    esc_html($formatted_date),
                                    esc_html($formatted_time_from),
                                    esc_html($formatted_time_to)
                                );
                                ?>
                            </td>
                            <td><?php echo number_format($hours, 2); ?></td>
                            <td><?php echo esc_html($currency_symbol . number_format($price_per_hour, 2)); ?></td>
                            <td><?php echo esc_html($currency_symbol . number_format($booking_details['payment_amount'], 2)); ?></td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="3"><?php _e('Total', 'turf-booking'); ?></th>
                            <td><?php echo esc_html($currency_symbol . number_format($booking_details['payment_amount'], 2)); ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            
            <div class="tb-invoice-payment">
                <h3><?php _e('Payment Information', 'turf-booking'); ?></h3>
                <p><?php _e('Payment Method:', 'turf-booking'); ?> 
                    <?php 
                    if ($booking_details['payment_method'] === 'razorpay') {
                        _e('Razorpay', 'turf-booking');
                    } else {
                        echo esc_html(ucfirst($booking_details['payment_method']));
                    }
                    ?>
                </p>
                <p><?php _e('Payment ID:', 'turf-booking'); ?> <?php echo esc_html($booking_details['payment_id']); ?></p>
                <p><?php _e('Payment Status:', 'turf-booking'); ?> 
                    <?php 
                    if ($booking_details['payment_status'] === 'completed') {
                        _e('Paid', 'turf-booking');
                    } else {
                        echo esc_html(ucfirst($booking_details['payment_status']));
                    }
                    ?>
                </p>
                <p><?php _e('Payment Date:', 'turf-booking'); ?> 
                    <?php 
                    if ($booking_details['payment_date']) {
                        echo date($date_format, strtotime($booking_details['payment_date']));
                    } else {
                        _e('N/A', 'turf-booking');
                    }
                    ?>
                </p>
            </div>
            
            <div class="tb-invoice-footer">
                <p><?php _e('Thank you for your business!', 'turf-booking'); ?></p>
            </div>
        </div>
        
        <style>
            .tb-invoice {
                max-width: 800px;
                margin: 0 auto;
                padding: 20px;
                font-family: Arial, sans-serif;
                border: 1px solid #ddd;
                background-color: #fff;
            }
            
            .tb-invoice-header {
                display: flex;
                justify-content: space-between;
                margin-bottom: 30px;
                padding-bottom: 20px;
                border-bottom: 1px solid #ddd;
            }
            
            .tb-invoice-logo img {
                max-height: 80px;
            }
            
            .tb-invoice-title h1 {
                margin: 0 0 10px;
                font-size: 24px;
                color: #333;
            }
            
            .tb-invoice-title p {
                margin: 0 0 5px;
            }
            
            .tb-invoice-addresses {
                display: flex;
                justify-content: space-between;
                margin-bottom: 30px;
            }
            
            .tb-invoice-from,
            .tb-invoice-to {
                width: 48%;
            }
            
            .tb-invoice-from h3,
            .tb-invoice-to h3 {
                margin: 0 0 10px;
                padding-bottom: 5px;
                border-bottom: 1px solid #ddd;
                font-size: 16px;
            }
            
            .tb-invoice-details {
                margin-bottom: 30px;
            }
            
            .tb-invoice-table {
                width: 100%;
                border-collapse: collapse;
            }
            
            .tb-invoice-table th,
            .tb-invoice-table td {
                padding: 10px;
                border: 1px solid #ddd;
                text-align: left;
            }
            
            .tb-invoice-table th {
                background-color: #f5f5f5;
            }
            
            .tb-invoice-table tfoot {
                font-weight: bold;
            }
            
            .tb-invoice-payment {
                margin-bottom: 30px;
            }
            
            .tb-invoice-payment h3 {
                margin: 0 0 10px;
                padding-bottom: 5px;
                border-bottom: 1px solid #ddd;
                font-size: 16px;
            }
            
            .tb-invoice-footer {
                margin-top: 50px;
                padding-top: 20px;
                border-top: 1px solid #ddd;
                text-align: center;
                font-style: italic;
            }
            
            @media print {
                body * {
                    visibility: hidden;
                }
                
                .tb-invoice,
                .tb-invoice * {
                    visibility: visible;
                }
                
                .tb-invoice {
                    position: absolute;
                    left: 0;
                    top: 0;
                    width: 100%;
                    border: none;
                }
            }
        </style>
        
        <div class="tb-print-button">
            <button onclick="window.print();" class="tb-button"><?php _e('Print Invoice', 'turf-booking'); ?></button>
        </div>
        <?php
        return ob_get_clean();
    }
}
