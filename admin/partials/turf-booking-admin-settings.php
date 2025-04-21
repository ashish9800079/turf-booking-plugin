<?php
/**
 * Admin settings template for Turf Booking
 *
 * @package    Turf_Booking
 * @subpackage Turf_Booking/admin/partials
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Get current active tab
$active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'general';

// Get settings
$general_settings = get_option('tb_general_settings', array());
$payment_settings = get_option('tb_payment_settings', array());
$email_settings = get_option('tb_email_settings', array());
$page_settings = get_option('tb_page_settings', array());

// Get currency symbol
$currency_symbol = isset($general_settings['currency_symbol']) ? $general_settings['currency_symbol'] : '₹';

// Add debugging for troubleshooting
if (WP_DEBUG) {
    error_log('Turf Booking Settings - Current General Settings: ' . print_r($general_settings, true));
    error_log('Turf Booking Settings - Current Payment Settings: ' . print_r($payment_settings, true));
    error_log('Turf Booking Settings - Current Email Settings: ' . print_r($email_settings, true));
    error_log('Turf Booking Settings - Current Page Settings: ' . print_r($page_settings, true));
}
?>
<div class="wrap">
    <h1><?php _e('Turf Booking Settings', 'turf-booking'); ?></h1>
    
    <h2 class="nav-tab-wrapper">
        <a href="?page=turf-booking-settings&tab=general" class="nav-tab <?php echo $active_tab == 'general' ? 'nav-tab-active' : ''; ?>"><?php _e('General', 'turf-booking'); ?></a>
        <a href="?page=turf-booking-settings&tab=payment" class="nav-tab <?php echo $active_tab == 'payment' ? 'nav-tab-active' : ''; ?>"><?php _e('Payment', 'turf-booking'); ?></a>
        <a href="?page=turf-booking-settings&tab=email" class="nav-tab <?php echo $active_tab == 'email' ? 'nav-tab-active' : ''; ?>"><?php _e('Email', 'turf-booking'); ?></a>
        <a href="?page=turf-booking-settings&tab=pages" class="nav-tab <?php echo $active_tab == 'pages' ? 'nav-tab-active' : ''; ?>"><?php _e('Pages', 'turf-booking'); ?></a>
    </h2>
    
    <div class="tb-settings-container">
        <?php if ($active_tab == 'general') : ?>
            <form method="post" action="options.php">
                <?php 
                settings_fields('tb_general_settings');
                do_settings_sections('tb_general_settings');
                ?>
                
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><?php _e('Currency', 'turf-booking'); ?></th>
                        <td>
                            <select name="tb_general_settings[currency]" id="tb_currency">
                                <option value="INR" <?php selected(isset($general_settings['currency']) ? $general_settings['currency'] : 'INR', 'INR'); ?>><?php _e('Indian Rupee (₹)', 'turf-booking'); ?></option>
                                <option value="USD" <?php selected(isset($general_settings['currency']) ? $general_settings['currency'] : 'INR', 'USD'); ?>><?php _e('US Dollar ($)', 'turf-booking'); ?></option>
                                <option value="EUR" <?php selected(isset($general_settings['currency']) ? $general_settings['currency'] : 'INR', 'EUR'); ?>><?php _e('Euro (€)', 'turf-booking'); ?></option>
                                <option value="GBP" <?php selected(isset($general_settings['currency']) ? $general_settings['currency'] : 'INR', 'GBP'); ?>><?php _e('British Pound (£)', 'turf-booking'); ?></option>
                                <option value="AUD" <?php selected(isset($general_settings['currency']) ? $general_settings['currency'] : 'INR', 'AUD'); ?>><?php _e('Australian Dollar ($)', 'turf-booking'); ?></option>
                                <option value="CAD" <?php selected(isset($general_settings['currency']) ? $general_settings['currency'] : 'INR', 'CAD'); ?>><?php _e('Canadian Dollar ($)', 'turf-booking'); ?></option>
                                <option value="SGD" <?php selected(isset($general_settings['currency']) ? $general_settings['currency'] : 'INR', 'SGD'); ?>><?php _e('Singapore Dollar ($)', 'turf-booking'); ?></option>
                                <option value="AED" <?php selected(isset($general_settings['currency']) ? $general_settings['currency'] : 'INR', 'AED'); ?>><?php _e('UAE Dirham (د.إ)', 'turf-booking'); ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Currency Symbol', 'turf-booking'); ?></th>
                        <td>
                            <input type="text" id="tb_currency_symbol" name="tb_general_settings[currency_symbol]" value="<?php echo esc_attr(isset($general_settings['currency_symbol']) ? $general_settings['currency_symbol'] : '₹'); ?>" class="regular-text">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Date Format', 'turf-booking'); ?></th>
                        <td>
                            <select name="tb_general_settings[date_format]" id="tb_date_format">
                                <option value="d/m/Y" <?php selected(isset($general_settings['date_format']) ? $general_settings['date_format'] : 'd/m/Y', 'd/m/Y'); ?>><?php echo date('d/m/Y'); ?> (d/m/Y)</option>
                                <option value="m/d/Y" <?php selected(isset($general_settings['date_format']) ? $general_settings['date_format'] : 'd/m/Y', 'm/d/Y'); ?>><?php echo date('m/d/Y'); ?> (m/d/Y)</option>
                                <option value="Y-m-d" <?php selected(isset($general_settings['date_format']) ? $general_settings['date_format'] : 'd/m/Y', 'Y-m-d'); ?>><?php echo date('Y-m-d'); ?> (Y-m-d)</option>
                                <option value="F j, Y" <?php selected(isset($general_settings['date_format']) ? $general_settings['date_format'] : 'd/m/Y', 'F j, Y'); ?>><?php echo date('F j, Y'); ?> (F j, Y)</option>
                                <option value="j F, Y" <?php selected(isset($general_settings['date_format']) ? $general_settings['date_format'] : 'd/m/Y', 'j F, Y'); ?>><?php echo date('j F, Y'); ?> (j F, Y)</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Time Format', 'turf-booking'); ?></th>
                        <td>
                            <select name="tb_general_settings[time_format]" id="tb_time_format">
                                <option value="H:i" <?php selected(isset($general_settings['time_format']) ? $general_settings['time_format'] : 'H:i', 'H:i'); ?>><?php echo date('H:i'); ?> (H:i)</option>
                                <option value="g:i A" <?php selected(isset($general_settings['time_format']) ? $general_settings['time_format'] : 'H:i', 'g:i A'); ?>><?php echo date('g:i A'); ?> (g:i A)</option>
                                <option value="g:i a" <?php selected(isset($general_settings['time_format']) ? $general_settings['time_format'] : 'H:i', 'g:i a'); ?>><?php echo date('g:i a'); ?> (g:i a)</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Booking Confirmation', 'turf-booking'); ?></th>
                        <td>
                            <select name="tb_general_settings[booking_confirmation]" id="tb_booking_confirmation">
                                <option value="auto" <?php selected(isset($general_settings['booking_confirmation']) ? $general_settings['booking_confirmation'] : 'auto', 'auto'); ?>><?php _e('Automatic Confirmation', 'turf-booking'); ?></option>
                                <option value="manual" <?php selected(isset($general_settings['booking_confirmation']) ? $general_settings['booking_confirmation'] : 'auto', 'manual'); ?>><?php _e('Manual Confirmation (Admin)', 'turf-booking'); ?></option>
                                <option value="payment" <?php selected(isset($general_settings['booking_confirmation']) ? $general_settings['booking_confirmation'] : 'auto', 'payment'); ?>><?php _e('Payment Required', 'turf-booking'); ?></option>
                            </select>
                            <p class="description"><?php _e('How booking confirmations should be handled.', 'turf-booking'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Maximum Days in Advance for Booking', 'turf-booking'); ?></th>
                        <td>
                            <input type="number" id="tb_max_booking_days_advance" name="tb_general_settings[max_booking_days_advance]" value="<?php echo esc_attr(isset($general_settings['max_booking_days_advance']) ? intval($general_settings['max_booking_days_advance']) : 30); ?>" class="small-text" min="1" max="365">
                            <p class="description"><?php _e('Maximum number of days in advance that courts can be booked.', 'turf-booking'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Minimum Hours in Advance for Booking', 'turf-booking'); ?></th>
                        <td>
                            <input type="number" id="tb_min_booking_hours_advance" name="tb_general_settings[min_booking_hours_advance]" value="<?php echo esc_attr(isset($general_settings['min_booking_hours_advance']) ? intval($general_settings['min_booking_hours_advance']) : 2); ?>" class="small-text" min="0" max="48">
                            <p class="description"><?php _e('Minimum number of hours in advance that courts can be booked.', 'turf-booking'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Cancellation Policy (Hours)', 'turf-booking'); ?></th>
                        <td>
                            <input type="number" id="tb_cancellation_policy" name="tb_general_settings[cancellation_policy]" value="<?php echo esc_attr(isset($general_settings['cancellation_policy']) ? intval($general_settings['cancellation_policy']) : 24); ?>" class="small-text" min="0" max="168">
                            <p class="description"><?php _e('Number of hours before booking that cancellation is allowed.', 'turf-booking'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Refund Policy', 'turf-booking'); ?></th>
                        <td>
                            <select name="tb_general_settings[refund_policy]" id="tb_refund_policy">
                                <option value="full" <?php selected(isset($general_settings['refund_policy']) ? $general_settings['refund_policy'] : 'full', 'full'); ?>><?php _e('Full Refund', 'turf-booking'); ?></option>
                                <option value="partial" <?php selected(isset($general_settings['refund_policy']) ? $general_settings['refund_policy'] : 'full', 'partial'); ?>><?php _e('Partial Refund', 'turf-booking'); ?></option>
                                <option value="none" <?php selected(isset($general_settings['refund_policy']) ? $general_settings['refund_policy'] : 'full', 'none'); ?>><?php _e('No Refund', 'turf-booking'); ?></option>
                            </select>
                            <p class="description"><?php _e('Refund policy for cancelled bookings.', 'turf-booking'); ?></p>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(); ?>
            </form>
            
        <?php elseif ($active_tab == 'payment') : ?>
            <form method="post" action="options.php">
                <?php 
                settings_fields('tb_payment_settings');
                do_settings_sections('tb_payment_settings');
                ?>
                
                <table class="form-table" role="presentation">
<tr>
    <th scope="row"><?php _e('Payment Methods', 'turf-booking'); ?></th>
    <td>
        <?php 
        $payment_methods = isset($payment_settings['payment_methods']) ? (array)$payment_settings['payment_methods'] : array('razorpay');
        ?>
        <label><input type="checkbox" name="tb_payment_settings[payment_methods][]" value="razorpay" <?php checked(in_array('razorpay', $payment_methods), true); ?>> <?php _e('Razorpay', 'turf-booking'); ?></label><br>
        <label><input type="checkbox" name="tb_payment_settings[payment_methods][]" value="knitpay" <?php checked(in_array('knitpay', $payment_methods), true); ?>> <?php _e('Knit Pay', 'turf-booking'); ?></label><br>
        <label><input type="checkbox" name="tb_payment_settings[payment_methods][]" value="offline" <?php checked(in_array('offline', $payment_methods), true); ?>> <?php _e('Offline Payment', 'turf-booking'); ?></label>
        <input type="hidden" name="tb_payment_settings[payment_methods_submitted]" value="1">
    </td>
</tr>

    
                </table>
                <h3><?php _e('Knit Pay Settings', 'turf-booking'); ?></h3>
<table class="form-table" role="presentation">
    <tr>
        <th scope="row"><?php _e('Enable Knit Pay', 'turf-booking'); ?></th>
        <td>
            <label><input type="radio" name="tb_payment_settings[knitpay_enabled]" value="yes" <?php checked(isset($payment_settings['knitpay_enabled']) ? $payment_settings['knitpay_enabled'] : 'no', 'yes'); ?>> <?php _e('Yes', 'turf-booking'); ?></label>
            <label style="margin-left: 15px;"><input type="radio" name="tb_payment_settings[knitpay_enabled]" value="no" <?php checked(isset($payment_settings['knitpay_enabled']) ? $payment_settings['knitpay_enabled'] : 'no', 'no'); ?>> <?php _e('No', 'turf-booking'); ?></label>
        </td>
    </tr>
    <tr>
        <th scope="row"><?php _e('Payment Gateway', 'turf-booking'); ?></th>
        <td>
            <select name="tb_payment_settings[knitpay_gateway]" id="tb_knitpay_gateway">
                <option value=""><?php _e('Select a Gateway', 'turf-booking'); ?></option>
                <?php 
                // Get available gateways from Knit Pay if the plugin is active
                if (function_exists('knit_pay_get_available_gateways')) {
                    $gateways = knit_pay_get_available_gateways();
                    foreach ($gateways as $id => $gateway) {
                        echo '<option value="' . esc_attr($id) . '" ' . selected(isset($payment_settings['knitpay_gateway']) ? $payment_settings['knitpay_gateway'] : '', $id, false) . '>' . esc_html($gateway['name']) . '</option>';
                    }
                } else {
                    echo '<option value="" disabled>' . __('Knit Pay plugin not active', 'turf-booking') . '</option>';
                }
                ?>
            </select>
            <p class="description"><?php _e('Select which payment gateway to use with Knit Pay', 'turf-booking'); ?></p>
        </td>
    </tr>
</table>
                <h3><?php _e('Razorpay Settings', 'turf-booking'); ?></h3>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><?php _e('Enable Razorpay', 'turf-booking'); ?></th>
                        <td>
                            <label><input type="radio" name="tb_payment_settings[razorpay_enabled]" value="yes" <?php checked(isset($payment_settings['razorpay_enabled']) ? $payment_settings['razorpay_enabled'] : 'yes', 'yes'); ?>> <?php _e('Yes', 'turf-booking'); ?></label>
                            <label style="margin-left: 15px;"><input type="radio" name="tb_payment_settings[razorpay_enabled]" value="no" <?php checked(isset($payment_settings['razorpay_enabled']) ? $payment_settings['razorpay_enabled'] : 'yes', 'no'); ?>> <?php _e('No', 'turf-booking'); ?></label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Razorpay Sandbox Mode', 'turf-booking'); ?></th>
                        <td>
                            <label><input type="radio" name="tb_payment_settings[razorpay_sandbox]" value="yes" <?php checked(isset($payment_settings['razorpay_sandbox']) ? $payment_settings['razorpay_sandbox'] : 'yes', 'yes'); ?>> <?php _e('Yes', 'turf-booking'); ?></label>
                            <label style="margin-left: 15px;"><input type="radio" name="tb_payment_settings[razorpay_sandbox]" value="no" <?php checked(isset($payment_settings['razorpay_sandbox']) ? $payment_settings['razorpay_sandbox'] : 'yes', 'no'); ?>> <?php _e('No', 'turf-booking'); ?></label>
                            <p class="description"><?php _e('Use Razorpay sandbox for testing.', 'turf-booking'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Razorpay Key ID', 'turf-booking'); ?></th>
                        <td>
                            <input type="text" id="tb_razorpay_key_id" name="tb_payment_settings[razorpay_key_id]" value="<?php echo esc_attr(isset($payment_settings['razorpay_key_id']) ? $payment_settings['razorpay_key_id'] : ''); ?>" class="regular-text">
                            <p class="description"><?php _e('Enter your Razorpay Key ID. You can find this in your Razorpay Dashboard.', 'turf-booking'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Razorpay Key Secret', 'turf-booking'); ?></th>
                        <td>
                            <input type="password" id="tb_razorpay_key_secret" name="tb_payment_settings[razorpay_key_secret]" value="<?php echo esc_attr(isset($payment_settings['razorpay_key_secret']) ? $payment_settings['razorpay_key_secret'] : ''); ?>" class="regular-text">
                            <p class="description"><?php _e('Enter your Razorpay Key Secret. You can find this in your Razorpay Dashboard.', 'turf-booking'); ?></p>
                        </td>
                    </tr>
                </table>
                
                <h3><?php _e('Payment Options', 'turf-booking'); ?></h3>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><?php _e('Require Full Payment', 'turf-booking'); ?></th>
                        <td>
                            <label><input type="radio" name="tb_payment_settings[require_full_payment]" value="yes" <?php checked(isset($payment_settings['require_full_payment']) ? $payment_settings['require_full_payment'] : 'yes', 'yes'); ?>> <?php _e('Yes', 'turf-booking'); ?></label>
                            <label style="margin-left: 15px;"><input type="radio" name="tb_payment_settings[require_full_payment]" value="no" <?php checked(isset($payment_settings['require_full_payment']) ? $payment_settings['require_full_payment'] : 'yes', 'no'); ?>> <?php _e('No', 'turf-booking'); ?></label>
                            <p class="description"><?php _e('Whether to require full payment at the time of booking.', 'turf-booking'); ?></p>
                        </td>
                    </tr>
                    <tr id="deposit_amount_row" class="js-show-if-partial-payment">
                        <th scope="row"><?php _e('Deposit Amount', 'turf-booking'); ?></th>
                        <td>
                            <input type="number" id="tb_deposit_amount" name="tb_payment_settings[deposit_amount]" value="<?php echo esc_attr(isset($payment_settings['deposit_amount']) ? floatval($payment_settings['deposit_amount']) : 0); ?>" class="regular-text" min="0" step="0.01">
                            <p class="description"><?php _e('Amount or percentage required as deposit if full payment is not required.', 'turf-booking'); ?></p>
                        </td>
                    </tr>
                    <tr id="deposit_type_row" class="js-show-if-partial-payment">
                        <th scope="row"><?php _e('Deposit Type', 'turf-booking'); ?></th>
                        <td>
                            <label><input type="radio" name="tb_payment_settings[deposit_type]" value="percentage" <?php checked(isset($payment_settings['deposit_type']) ? $payment_settings['deposit_type'] : 'percentage', 'percentage'); ?>> <?php _e('Percentage', 'turf-booking'); ?></label>
                            <label style="margin-left: 15px;"><input type="radio" name="tb_payment_settings[deposit_type]" value="fixed" <?php checked(isset($payment_settings['deposit_type']) ? $payment_settings['deposit_type'] : 'percentage', 'fixed'); ?>> <?php _e('Fixed Amount', 'turf-booking'); ?></label>
                        </td>
                    </tr>
                </table>
                
                <h3><?php _e('Offline Payment Settings', 'turf-booking'); ?></h3>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><?php _e('Payment Instructions', 'turf-booking'); ?></th>
                        <td>
                            <?php
                            $offline_instructions = isset($payment_settings['offline_instructions']) ? $payment_settings['offline_instructions'] : '';
                            wp_editor(
                                $offline_instructions,
                                'tb_offline_payment_instructions',
                                array(
                                    'textarea_name' => 'tb_payment_settings[offline_instructions]',
                                    'textarea_rows' => 5,
                                    'media_buttons' => false,
                                    'teeny' => true,
                                )
                            );
                            ?>
                            <p class="description"><?php _e('Instructions that will be shown to customers who choose offline payment.', 'turf-booking'); ?></p>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(); ?>
            </form>
            
        <?php elseif ($active_tab == 'email') : ?>
            <form method="post" action="options.php">
                <?php 
                settings_fields('tb_email_settings');
                do_settings_sections('tb_email_settings');
                ?>
                
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><?php _e('Admin Email', 'turf-booking'); ?></th>
                        <td>
                            <input type="email" id="tb_admin_email" name="tb_email_settings[admin_email]" value="<?php echo esc_attr(isset($email_settings['admin_email']) ? $email_settings['admin_email'] : get_option('admin_email')); ?>" class="regular-text">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Email From Name', 'turf-booking'); ?></th>
                        <td>
                            <input type="text" id="tb_email_from_name" name="tb_email_settings[email_from_name]" value="<?php echo esc_attr(isset($email_settings['email_from_name']) ? $email_settings['email_from_name'] : get_bloginfo('name')); ?>" class="regular-text">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Email From Address', 'turf-booking'); ?></th>
                        <td>
                            <input type="email" id="tb_email_from_address" name="tb_email_settings[email_from_address]" value="<?php echo esc_attr(isset($email_settings['email_from_address']) ? $email_settings['email_from_address'] : get_option('admin_email')); ?>" class="regular-text">
                        </td>
                    </tr>
                </table>
                
                <h3><?php _e('Booking Confirmation Email', 'turf-booking'); ?></h3>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><?php _e('Subject', 'turf-booking'); ?></th>
                        <td>
                            <input type="text" id="tb_booking_confirmation_subject" name="tb_email_settings[booking_confirmation_subject]" value="<?php echo esc_attr(isset($email_settings['booking_confirmation_subject']) ? $email_settings['booking_confirmation_subject'] : __('Your booking has been confirmed', 'turf-booking')); ?>" class="regular-text">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Message', 'turf-booking'); ?></th>
                        <td>
                            <?php 
                            $default_message = "Hello {customer_name},\n\nYour booking has been confirmed.\n\nBooking Details:\nCourt: {court_name}\nDate: {booking_date}\nTime: {booking_time_from} - {booking_time_to}\nTotal: {booking_total}\n\nThank you for your booking.";
                            $confirmation_message = isset($email_settings['booking_confirmation_message']) ? $email_settings['booking_confirmation_message'] : $default_message;
                            ?>
                            <textarea id="tb_booking_confirmation_message" name="tb_email_settings[booking_confirmation_message]" rows="10" class="large-text"><?php echo esc_textarea($confirmation_message); ?></textarea>
                            <p class="description"><?php _e('Available placeholders: {booking_id}, {court_name}, {customer_name}, {customer_email}, {customer_phone}, {booking_date}, {booking_time_from}, {booking_time_to}, {booking_total}', 'turf-booking'); ?></p>
                        </td>
                    </tr>
                </table>
                
                <h3><?php _e('Booking Pending Email', 'turf-booking'); ?></h3>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><?php _e('Subject', 'turf-booking'); ?></th>
                        <td>
                            <input type="text" id="tb_booking_pending_subject" name="tb_email_settings[booking_pending_subject]" value="<?php echo esc_attr(isset($email_settings['booking_pending_subject']) ? $email_settings['booking_pending_subject'] : __('Your booking is pending', 'turf-booking')); ?>" class="regular-text">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Message', 'turf-booking'); ?></th>
                        <td>
                            <?php 
                            $default_message = "Hello {customer_name},\n\nYour booking is pending confirmation.\n\nBooking Details:\nCourt: {court_name}\nDate: {booking_date}\nTime: {booking_time_from} - {booking_time_to}\nTotal: {booking_total}\n\nWe will notify you once your booking is confirmed.";
                            $pending_message = isset($email_settings['booking_pending_message']) ? $email_settings['booking_pending_message'] : $default_message;
                            ?>
                            <textarea id="tb_booking_pending_message" name="tb_email_settings[booking_pending_message]" rows="10" class="large-text"><?php echo esc_textarea($pending_message); ?></textarea>
                            <p class="description"><?php _e('Available placeholders: {booking_id}, {court_name}, {customer_name}, {customer_email}, {customer_phone}, {booking_date}, {booking_time_from}, {booking_time_to}, {booking_total}', 'turf-booking'); ?></p>
                        </td>
                    </tr>
                </table>
                
                <h3><?php _e('Booking Cancelled Email', 'turf-booking'); ?></h3>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><?php _e('Subject', 'turf-booking'); ?></th>
                        <td>
                            <input type="text" id="tb_booking_cancelled_subject" name="tb_email_settings[booking_cancelled_subject]" value="<?php echo esc_attr(isset($email_settings['booking_cancelled_subject']) ? $email_settings['booking_cancelled_subject'] : __('Your booking has been cancelled', 'turf-booking')); ?>" class="regular-text">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Message', 'turf-booking'); ?></th>
                        <td>
                            <?php 
                            $default_message = "Hello {customer_name},\n\nYour booking has been cancelled.\n\nBooking Details:\nCourt: {court_name}\nDate: {booking_date}\nTime: {booking_time_from} - {booking_time_to}\nTotal: {booking_total}\n\nIf you did not cancel this booking, please contact us.";
                            $cancelled_message = isset($email_settings['booking_cancelled_message']) ? $email_settings['booking_cancelled_message'] : $default_message;
                            ?>
                            <textarea id="tb_booking_cancelled_message" name="tb_email_settings[booking_cancelled_message]" rows="10" class="large-text"><?php echo esc_textarea($cancelled_message); ?></textarea>
                            <p class="description"><?php _e('Available placeholders: {booking_id}, {court_name}, {customer_name}, {customer_email}, {customer_phone}, {booking_date}, {booking_time_from}, {booking_time_to}, {booking_total}', 'turf-booking'); ?></p>
                        </td>
                    </tr>
                </table>
                
                <h3><?php _e('Admin Notification Email', 'turf-booking'); ?></h3>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><?php _e('Subject', 'turf-booking'); ?></th>
                        <td>
                            <input type="text" id="tb_admin_notification_subject" name="tb_email_settings[admin_notification_subject]" value="<?php echo esc_attr(isset($email_settings['admin_notification_subject']) ? $email_settings['admin_notification_subject'] : __('New booking received', 'turf-booking')); ?>" class="regular-text">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Message', 'turf-booking'); ?></th>
                        <td>
                            <?php 
                            $default_message = "Hello Admin,\n\nA new booking has been received.\n\nBooking Details:\nCustomer: {customer_name}\nEmail: {customer_email}\nPhone: {customer_phone}\nCourt: {court_name}\nDate: {booking_date}\nTime: {booking_time_from} - {booking_time_to}\nTotal: {booking_total}\n\nPlease log in to confirm the booking.";
                            $admin_message = isset($email_settings['admin_notification_message']) ? $email_settings['admin_notification_message'] : $default_message;
                            ?>
                            <textarea id="tb_admin_notification_message" name="tb_email_settings[admin_notification_message]" rows="10" class="large-text"><?php echo esc_textarea($admin_message); ?></textarea>
                            <p class="description"><?php _e('Available placeholders: {booking_id}, {court_name}, {customer_name}, {customer_email}, {customer_phone}, {booking_date}, {booking_time_from}, {booking_time_to}, {booking_total}', 'turf-booking'); ?></p>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(); ?>
            </form>
            
        <?php elseif ($active_tab == 'pages') : ?>
            <form method="post" action="options.php">
                <?php 
                settings_fields('tb_page_settings');
                do_settings_sections('tb_page_settings');
                ?>
                
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><?php _e('Courts Page', 'turf-booking'); ?></th>
                        <td>
                            <?php
                            $courts_page_id = isset($page_settings['courts']) ? $page_settings['courts'] : 0;
                            wp_dropdown_pages(array(
                                'name' => 'tb_page_settings[courts]',
                                'id' => 'tb_page_courts',
                                'selected' => $courts_page_id,
                                'show_option_none' => __('Select a page', 'turf-booking'),
                            ));
                            ?>
                            <?php if ($courts_page_id) : ?>
                                <a href="<?php echo admin_url('post.php?post=' . $courts_page_id . '&action=edit'); ?>" class="button" style="margin-left: 10px;"><?php _e('Edit Page', 'turf-booking'); ?></a>
                                <a href="<?php echo get_permalink($courts_page_id); ?>" class="button" style="margin-left: 5px;" target="_blank"><?php _e('View Page', 'turf-booking'); ?></a>
                            <?php endif; ?>
                            <p class="description"><?php _e('Page that displays the available courts.', 'turf-booking'); ?></p>
                            <p class="description"><strong><?php _e('Shortcode:', 'turf-booking'); ?></strong> <code>[turf_booking_courts]</code></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('My Account Page', 'turf-booking'); ?></th>
                        <td>
                            <?php
                            $my_account_page_id = isset($page_settings['my-account']) ? $page_settings['my-account'] : 0;
                            wp_dropdown_pages(array(
                                'name' => 'tb_page_settings[my-account]',
                                'id' => 'tb_page_my_account',
                                'selected' => $my_account_page_id,
                                'show_option_none' => __('Select a page', 'turf-booking'),
                            ));
                            ?>
                            <?php if ($my_account_page_id) : ?>
                                <a href="<?php echo admin_url('post.php?post=' . $my_account_page_id . '&action=edit'); ?>" class="button" style="margin-left: 10px;"><?php _e('Edit Page', 'turf-booking'); ?></a>
                                <a href="<?php echo get_permalink($my_account_page_id); ?>" class="button" style="margin-left: 5px;" target="_blank"><?php _e('View Page', 'turf-booking'); ?></a>
                            <?php endif; ?>
                            <p class="description"><?php _e('Page where users can view their bookings and account details.', 'turf-booking'); ?></p>
                            <p class="description"><strong><?php _e('Shortcode:', 'turf-booking'); ?></strong> <code>[turf_booking_account]</code></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Booking Page', 'turf-booking'); ?></th>
                        <td>
                            <?php
                            $booking_page_id = isset($page_settings['booking']) ? $page_settings['booking'] : 0;
                            wp_dropdown_pages(array(
                                'name' => 'tb_page_settings[booking]',
                                'id' => 'tb_page_booking',
                                'selected' => $booking_page_id,
                                'show_option_none' => __('Select a page', 'turf-booking'),
                            ));
                            ?>
                            <?php if ($booking_page_id) : ?>
                                <a href="<?php echo admin_url('post.php?post=' . $booking_page_id . '&action=edit'); ?>" class="button" style="margin-left: 10px;"><?php _e('Edit Page', 'turf-booking'); ?></a>
                                <a href="<?php echo get_permalink($booking_page_id); ?>" class="button" style="margin-left: 5px;" target="_blank"><?php _e('View Page', 'turf-booking'); ?></a>
                            <?php endif; ?>
                            <p class="description"><?php _e('Page where users can book courts.', 'turf-booking'); ?></p>
                            <p class="description"><strong><?php _e('Shortcode:', 'turf-booking'); ?></strong> <code>[turf_booking_form]</code></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Checkout Page', 'turf-booking'); ?></th>
                        <td>
                            <?php
                            $checkout_page_id = isset($page_settings['checkout']) ? $page_settings['checkout'] : 0;
                            wp_dropdown_pages(array(
                                'name' => 'tb_page_settings[checkout]',
                                'id' => 'tb_page_checkout',
                                'selected' => $checkout_page_id,
                                'show_option_none' => __('Select a page', 'turf-booking'),
                            ));
                            ?>
                            <?php if ($checkout_page_id) : ?>
                                <a href="<?php echo admin_url('post.php?post=' . $checkout_page_id . '&action=edit'); ?>" class="button" style="margin-left: 10px;"><?php _e('Edit Page', 'turf-booking'); ?></a>
                                <a href="<?php echo get_permalink($checkout_page_id); ?>" class="button" style="margin-left: 5px;" target="_blank"><?php _e('View Page', 'turf-booking'); ?></a>
                            <?php endif; ?>
                            <p class="description"><?php _e('Page where users can complete payment for their booking.', 'turf-booking'); ?></p>
                            <p class="description"><strong><?php _e('Shortcode:', 'turf-booking'); ?></strong> <code>[turf_booking_checkout]</code></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Booking Confirmation Page', 'turf-booking'); ?></th>
                        <td>
                            <?php
                            $confirmation_page_id = isset($page_settings['booking-confirmation']) ? $page_settings['booking-confirmation'] : 0;
                            wp_dropdown_pages(array(
                                'name' => 'tb_page_settings[booking-confirmation]',
                                'id' => 'tb_page_booking_confirmation',
                                'selected' => $confirmation_page_id,
                                'show_option_none' => __('Select a page', 'turf-booking'),
                            ));
                            ?>
                            <?php if ($confirmation_page_id) : ?>
                                <a href="<?php echo admin_url('post.php?post=' . $confirmation_page_id . '&action=edit'); ?>" class="button" style="margin-left: 10px;"><?php _e('Edit Page', 'turf-booking'); ?></a>
                                <a href="<?php echo get_permalink($confirmation_page_id); ?>" class="button" style="margin-left: 5px;" target="_blank"><?php _e('View Page', 'turf-booking'); ?></a>
                            <?php endif; ?>
                            <p class="description"><?php _e('Page shown after successful booking or payment.', 'turf-booking'); ?></p>
                            <p class="description"><strong><?php _e('Shortcode:', 'turf-booking'); ?></strong> <code>[turf_booking_confirmation]</code></p>
                        </td>
                    </tr>
                </table>
                
                <h3><?php _e('Shortcodes Reference', 'turf-booking'); ?></h3>
                <table class="widefat" style="margin-top: 10px;">
                    <thead>
                        <tr>
                            <th><?php _e('Shortcode', 'turf-booking'); ?></th>
                            <th><?php _e('Description', 'turf-booking'); ?></th>
                            <th><?php _e('Example', 'turf-booking'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><code>[turf_booking_courts]</code></td>
                            <td><?php _e('Displays all available courts in a grid layout.', 'turf-booking'); ?></td>
                            <td><code>[turf_booking_courts count="12" columns="3"]</code></td>
                        </tr>
                        <tr>
                            <td><code>[turf_booking_account]</code></td>
                            <td><?php _e('Displays the user dashboard with bookings and account information.', 'turf-booking'); ?></td>
                            <td><code>[turf_booking_account]</code></td>
                        </tr>
                        <tr>
                            <td><code>[turf_booking_form]</code></td>
                            <td><?php _e('Displays the booking form for a specific court.', 'turf-booking'); ?></td>
                            <td><code>[turf_booking_form court_id="123"]</code></td>
                        </tr>
                        <tr>
                            <td><code>[turf_booking_checkout]</code></td>
                            <td><?php _e('Displays the checkout page for payment processing.', 'turf-booking'); ?></td>
                            <td><code>[turf_booking_checkout]</code></td>
                        </tr>
                        <tr>
                            <td><code>[turf_booking_confirmation]</code></td>
                            <td><?php _e('Displays booking confirmation details.', 'turf-booking'); ?></td>
                            <td><code>[turf_booking_confirmation]</code></td>
                        </tr>
                    </tbody>
                </table>
                
                <?php submit_button(); ?>
            </form>
        <?php endif; ?>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Toggle Razorpay settings visibility
    function toggleRazorpaySettings() {
        var razorpayEnabled = $('input[name="tb_payment_settings[razorpay_enabled]"][value="yes"]').is(':checked');
        if (razorpayEnabled) {
            $('.js-show-if-razorpay-enabled').show();
        } else {
            $('.js-show-if-razorpay-enabled').hide();
        }
    }
    
    // Toggle deposit settings visibility
    function toggleDepositSettings() {
        var requireFullPayment = $('input[name="tb_payment_settings[require_full_payment]"][value="yes"]').is(':checked');
        if (requireFullPayment) {
            $('.js-show-if-partial-payment').hide();
        } else {
            $('.js-show-if-partial-payment').show();
        }
    }
    
    // Debug current settings
    if (window.console) {
        console.log('Current General Settings:', <?php echo json_encode($general_settings); ?>);
        console.log('Current Payment Settings:', <?php echo json_encode($payment_settings); ?>);
        console.log('Current Email Settings:', <?php echo json_encode($email_settings); ?>);
        console.log('Current Page Settings:', <?php echo json_encode($page_settings); ?>);
    }
    
    // Initial toggle states
    toggleRazorpaySettings();
    toggleDepositSettings();
    
    // Event listeners
    $('input[name="tb_payment_settings[razorpay_enabled]"]').on('change', toggleRazorpaySettings);
    $('input[name="tb_payment_settings[require_full_payment]"]').on('change', toggleDepositSettings);
    
    // Form submission debugging
    $('form').on('submit', function() {
        if (window.console) {
            console.log('Form submitted:', $(this).serialize());
        }
    });
});
</script>

<style>
    .tb-settings-container {
        background: #fff;
        padding: 20px;
        border: 1px solid #ccc;
        margin-top: 20px;
    }
    
    .form-table th {
        width: 250px;
    }
    
    .tb-razorpay-test-info {
        background: #f8f9fa;
        border-left: 4px solid #007cba;
        padding: 10px 15px;
        margin: 15px 0;
    }
    
    /* Toggle visibility based on settings */
    .js-show-if-razorpay-enabled {
        display: none;
    }
    
    .js-show-if-partial-payment {
        display: table-row;
    }
    
    /* Debug styles */
    .tb-debug-info {
        background: #f5f5f5;
        border: 1px solid #ddd;
        margin: 20px 0;
        padding: 10px;
        max-height: 200px;
        overflow: auto;
    }
</style>