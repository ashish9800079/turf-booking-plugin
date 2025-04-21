<?php
/**
 * Template for booking confirmation
 *
 * This template is used to display booking confirmation details
 * after a successful booking or payment.
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Get booking details from Bookings class
$bookings = new Turf_Booking_Bookings();
$booking_details = $bookings->get_booking_details($booking_id);

if (!$booking_details) {
    echo '<div class="tb-error-message">';
    echo '<p>' . __('Booking not found or has been deleted.', 'turf-booking') . '</p>';
    echo '</div>';
    return;
}

// Get court data
$court_id = $booking_details['court_id'];
$court = get_post($court_id);

if (!$court) {
    echo '<div class="tb-error-message">';
    echo '<p>' . __('Court information not found.', 'turf-booking') . '</p>';
    echo '</div>';
    return;
}

// Get current user ID
$user_id = get_current_user_id();

// Check if user is authorized to view this booking
$booking_user_id = $booking_details['user_id'];

if (!current_user_can('manage_options') && $booking_user_id != $user_id) {
    echo '<div class="tb-error-message">';
    echo '<p>' . __('You do not have permission to view this booking.', 'turf-booking') . '</p>';
    echo '</div>';
    return;
}

// Get general settings for date/time format and currency
$general_settings = get_option('tb_general_settings');
$currency_symbol = isset($general_settings['currency_symbol']) ? $general_settings['currency_symbol'] : '₹';
$date_format = isset($general_settings['date_format']) ? $general_settings['date_format'] : 'd/m/Y';
$time_format = isset($general_settings['time_format']) ? $general_settings['time_format'] : 'H:i';

// Format date and time
$formatted_date = date($date_format, strtotime($booking_details['date']));
$formatted_time_from = date($time_format, strtotime($booking_details['time_from']));
$formatted_time_to = date($time_format, strtotime($booking_details['time_to']));

// Get page settings
$page_settings = get_option('tb_page_settings');
$my_account_url = isset($page_settings['my-account']) ? get_permalink($page_settings['my-account']) : '';
$courts_url = isset($page_settings['courts']) ? get_permalink($page_settings['courts']) : get_post_type_archive_link('tb_court');

// Get payments class to generate invoice if needed
$payments = new Turf_Booking_Payments();
?>

<div class="tb-confirmation-container">
    <div class="tb-confirmation-header">
        <?php if ($booking_details['status'] === 'confirmed' || $booking_details['payment_status'] === 'completed'): ?>
            <div class="tb-confirmation-icon tb-success">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="64" height="64" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                    <polyline points="22 4 12 14.01 9 11.01"></polyline>
                </svg>
            </div>
            <h2><?php _e('Booking Confirmed', 'turf-booking'); ?></h2>
            <p class="tb-confirmation-message"><?php _e('Your booking has been successfully confirmed!', 'turf-booking'); ?></p>
        <?php elseif ($booking_details['status'] === 'pending'): ?>
            <div class="tb-confirmation-icon tb-pending">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="64" height="64" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <polyline points="12 6 12 12 16 14"></polyline>
                </svg>
            </div>
            <h2><?php _e('Booking Pending', 'turf-booking'); ?></h2>
            <p class="tb-confirmation-message"><?php _e('Your booking is pending confirmation.', 'turf-booking'); ?></p>
        <?php elseif ($booking_details['status'] === 'cancelled'): ?>
            <div class="tb-confirmation-icon tb-cancelled">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="64" height="64" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="15" y1="9" x2="9" y2="15"></line>
                    <line x1="9" y1="9" x2="15" y2="15"></line>
                </svg>
            </div>
            <h2><?php _e('Booking Cancelled', 'turf-booking'); ?></h2>
            <p class="tb-confirmation-message"><?php _e('This booking has been cancelled.', 'turf-booking'); ?></p>
        <?php endif; ?>
    </div>
    
    <div class="tb-confirmation-details">
        <div class="tb-confirmation-ref">
            <span><?php _e('Booking Reference:', 'turf-booking'); ?></span>
            <strong>#<?php echo esc_html($booking_details['id']); ?></strong>
        </div>
        
        <div class="tb-confirmation-content">
            <div class="tb-confirmation-court">
                <div class="tb-court-image">
                    <?php if (has_post_thumbnail($court_id)): ?>
                        <?php echo get_the_post_thumbnail($court_id, 'thumbnail'); ?>
                    <?php else: ?>
                        <div class="tb-no-image"><?php _e('No Image', 'turf-booking'); ?></div>
                    <?php endif; ?>
                </div>
                
                <div class="tb-court-info">
                    <h3><?php echo esc_html($court->post_title); ?></h3>
                    
                    <?php
                    // Get court location
                    $locations = get_the_terms($court_id, 'location');
                    if ($locations && !is_wp_error($locations)): 
                    ?>
                        <div class="tb-court-location">
                            <i class="fas fa-map-marker-alt"></i>
                            <?php echo esc_html($locations[0]->name); ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="tb-confirmation-booking-details">
                <h3><?php _e('Booking Information', 'turf-booking'); ?></h3>
                
                <div class="tb-details-grid">
                    <div class="tb-detail-item">
                        <div class="tb-detail-icon">
                            <i class="fas fa-calendar"></i>
                        </div>
                        <div class="tb-detail-content">
                            <span class="tb-detail-label"><?php _e('Date', 'turf-booking'); ?></span>
                            <span class="tb-detail-value"><?php echo esc_html($formatted_date); ?></span>
                        </div>
                    </div>
                    
                    <div class="tb-detail-item">
                        <div class="tb-detail-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="tb-detail-content">
                            <span class="tb-detail-label"><?php _e('Time', 'turf-booking'); ?></span>
                            <span class="tb-detail-value"><?php echo esc_html($formatted_time_from . ' - ' . $formatted_time_to); ?></span>
                        </div>
                    </div>
                    
                    <div class="tb-detail-item">
                        <div class="tb-detail-icon">
                            <i class="fas fa-tag"></i>
                        </div>
                        <div class="tb-detail-content">
                            <span class="tb-detail-label"><?php _e('Status', 'turf-booking'); ?></span>
                            <span class="tb-detail-value tb-status-<?php echo esc_attr($booking_details['status']); ?>">
                                <?php 
                                switch ($booking_details['status']) {
                                    case 'confirmed':
                                        _e('Confirmed', 'turf-booking');
                                        break;
                                    case 'pending':
                                        _e('Pending', 'turf-booking');
                                        break;
                                    case 'cancelled':
                                        _e('Cancelled', 'turf-booking');
                                        break;
                                    case 'completed':
                                        _e('Completed', 'turf-booking');
                                        break;
                                    default:
                                        echo esc_html(ucfirst($booking_details['status']));
                                }
                                ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="tb-detail-item">
                        <div class="tb-detail-icon">
                            <i class="fas fa-money-bill"></i>
                        </div>
                        <div class="tb-detail-content">
                            <span class="tb-detail-label"><?php _e('Total Amount', 'turf-booking'); ?></span>
                            <span class="tb-detail-value"><?php echo esc_html($currency_symbol . number_format($booking_details['payment_amount'], 2)); ?></span>
                        </div>
                    </div>
                </div>
            </div>
            
            <?php if ($booking_details['payment_status'] !== 'pending' && $booking_details['payment_id']): ?>
                <div class="tb-confirmation-payment-details">
                    <h3><?php _e('Payment Information', 'turf-booking'); ?></h3>
                    
                    <div class="tb-details-grid">
                        <div class="tb-detail-item">
                            <div class="tb-detail-icon">
                                <i class="fas fa-credit-card"></i>
                            </div>
                            <div class="tb-detail-content">
                                <span class="tb-detail-label"><?php _e('Payment Method', 'turf-booking'); ?></span>
                                <span class="tb-detail-value">
                                    <?php 
                                    if ($booking_details['payment_method'] === 'razorpay') {
                                        _e('Razorpay', 'turf-booking');
                                    } else {
                                        echo esc_html(ucfirst($booking_details['payment_method']));
                                    }
                                    ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="tb-detail-item">
                            <div class="tb-detail-icon">
                                <i class="fas fa-hashtag"></i>
                            </div>
                            <div class="tb-detail-content">
                                <span class="tb-detail-label"><?php _e('Payment ID', 'turf-booking'); ?></span>
                                <span class="tb-detail-value"><?php echo esc_html($booking_details['payment_id']); ?></span>
                            </div>
                        </div>
                        
                        <div class="tb-detail-item">
                            <div class="tb-detail-icon">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <div class="tb-detail-content">
                                <span class="tb-detail-label"><?php _e('Payment Status', 'turf-booking'); ?></span>
                                <span class="tb-detail-value tb-payment-status-<?php echo esc_attr($booking_details['payment_status']); ?>">
                                    <?php 
                                    switch ($booking_details['payment_status']) {
                                        case 'completed':
                                            _e('Paid', 'turf-booking');
                                            break;
                                        case 'pending':
                                            _e('Pending', 'turf-booking');
                                            break;
                                        case 'refunded':
                                            _e('Refunded', 'turf-booking');
                                            break;
                                        case 'partially_refunded':
                                            _e('Partially Refunded', 'turf-booking');
                                            break;
                                        default:
                                            echo esc_html(ucfirst($booking_details['payment_status']));
                                    }
                                    ?>
                                </span>
                            </div>
                        </div>
                        
                        <?php if ($booking_details['payment_date']): ?>
                            <div class="tb-detail-item">
                                <div class="tb-detail-icon">
                                    <i class="fas fa-calendar-check"></i>
                                </div>
                                <div class="tb-detail-content">
                                    <span class="tb-detail-label"><?php _e('Payment Date', 'turf-booking'); ?></span>
                                    <span class="tb-detail-value"><?php echo esc_html(date($date_format, strtotime($booking_details['payment_date']))); ?></span>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <div class="tb-confirmation-customer-details">
                <h3><?php _e('Customer Information', 'turf-booking'); ?></h3>
                
                <div class="tb-details-grid">
                    <div class="tb-detail-item">
                        <div class="tb-detail-icon">
                            <i class="fas fa-user"></i>
                        </div>
                        <div class="tb-detail-content">
                            <span class="tb-detail-label"><?php _e('Name', 'turf-booking'); ?></span>
                            <span class="tb-detail-value"><?php echo esc_html($booking_details['user_name']); ?></span>
                        </div>
                    </div>
                    
                    <div class="tb-detail-item">
                        <div class="tb-detail-icon">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <div class="tb-detail-content">
                            <span class="tb-detail-label"><?php _e('Email', 'turf-booking'); ?></span>
                            <span class="tb-detail-value"><?php echo esc_html($booking_details['user_email']); ?></span>
                        </div>
                    </div>
                    
                    <div class="tb-detail-item">
                        <div class="tb-detail-icon">
                            <i class="fas fa-phone"></i>
                        </div>
                        <div class="tb-detail-content">
                            <span class="tb-detail-label"><?php _e('Phone', 'turf-booking'); ?></span>
                            <span class="tb-detail-value"><?php echo esc_html($booking_details['user_phone']); ?></span>
                        </div>
                    </div>
                </div>
            </div>
            
            <?php if (!empty($booking_details['addons'])): ?>
                <div class="tb-confirmation-addons">
                    <h3><?php _e('Add-ons', 'turf-booking'); ?></h3>
                    
                    <ul class="tb-addons-list">
                        <?php foreach ($booking_details['addons'] as $addon): ?>
                            <li class="tb-addon-item">
                                <span class="tb-addon-name"><?php echo esc_html($addon['addon_name']); ?></span>
                                <span class="tb-addon-price">
                                    <?php 
                                    echo esc_html($currency_symbol . number_format($addon['addon_price'], 2));
                                    echo ' ';
                                    if ($addon['addon_type'] === 'per_hour') {
                                        _e('per hour', 'turf-booking');
                                    } else {
                                        _e('per booking', 'turf-booking');
                                    }
                                    ?>
                                </span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="tb-confirmation-actions">
        <?php if ($booking_details['payment_status'] === 'completed'): ?>
            <a href="#" class="tb-button tb-button-invoice" onclick="window.print(); return false;">
                <i class="fas fa-print"></i> <?php _e('Print Confirmation', 'turf-booking'); ?>
            </a>
        <?php elseif ($booking_details['status'] === 'pending' || $booking_details['payment_status'] === 'pending'): ?>
            <a href="<?php echo esc_url(add_query_arg('booking_id', $booking_id, get_permalink($page_settings['checkout']))); ?>" class="tb-button tb-button-payment">
                <i class="fas fa-credit-card"></i> <?php _e('Continue to Payment', 'turf-booking'); ?>
            </a>
        <?php endif; ?>
        
        <a href="<?php echo esc_url($my_account_url); ?>" class="tb-button tb-button-secondary">
            <i class="fas fa-user"></i> <?php _e('My Account', 'turf-booking'); ?>
        </a>
        
        <a href="<?php echo esc_url($courts_url); ?>" class="tb-button tb-button-secondary">
            <i class="fas fa-globe"></i> <?php _e('Book Another Court', 'turf-booking'); ?>
        </a>
    </div>
</div>

<style>
/* Booking Confirmation Styles */
.tb-confirmation-container {
    max-width: 900px;
    margin: 0 auto;
    margin-top:2rem;
}

.tb-confirmation-header {
    text-align: center;
    margin-bottom: 30px;
    padding-bottom: 30px;
    border-bottom: 1px solid #e5e5e5;
}

.tb-confirmation-icon {
    width: 80px;
    height: 80px;
    margin: 0 auto 20px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.tb-confirmation-icon.tb-success {
    background-color: rgba(46, 204, 113, 0.1);
    color: #2ecc71;
}

.tb-confirmation-icon.tb-pending {
    background-color: rgba(243, 156, 18, 0.1);
    color: #f39c12;
}

.tb-confirmation-icon.tb-cancelled {
    background-color: rgba(231, 76, 60, 0.1);
    color: #e74c3c;
}

.tb-confirmation-header h2 {
    font-size: 20px;
    margin: 0 0 10px;
    color: #333;
}

.tb-confirmation-message {
    font-size: 15px;
    color: #666;
        margin: 0;
    line-height: 1;
    padding: 0 !important;
    
}

.tb-confirmation-details {
    background: #fff;
    padding: 15px;
    border-radius: 10px;
}
.tb-confirmation-ref {
    margin-bottom: 20px;
    padding: 10px 15px;
    background-color: #f9f9f9;
    border-radius: 4px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.tb-confirmation-ref span {
    color: #666;
    font-size: 16px;
}

.tb-confirmation-ref strong {
    font-size: 18px;
    color: #333;
}

.tb-confirmation-content {
    margin-bottom: 30px;
}

.tb-confirmation-court {
    display: flex;
        border: 1px solid #efefef;
    border-radius: 10px;
    overflow: hidden;
    padding:20px;
    margin-bottom: 20px;
}

.tb-court-image {
    width: 120px;
    height: 120px;
    overflow: hidden;
}

.tb-court-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.tb-no-image {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: #e0e0e0;
    color: #666;
    font-style: italic;
}

.tb-court-info {
    flex: 1;
    padding: 15px;
}

.tb-court-info h3 {
    margin: 0 0 10px;
    font-size: 20px;
    color: #333;
}

.tb-court-location {
    display: flex;
    align-items: center;
    color: #666;
}

.tb-court-location i {
    margin-right: 5px;
    color: #3399cc;
}

.tb-confirmation-booking-details,
.tb-confirmation-payment-details,
.tb-confirmation-customer-details,
.tb-confirmation-addons {
    margin-bottom: 25px;
    padding: 20px;
    background-color: #fff;
    border: 1px solid #efefef;
    border-radius: 10px;
}

.tb-confirmation-booking-details h3,
.tb-confirmation-payment-details h3,
.tb-confirmation-customer-details h3,
.tb-confirmation-addons h3 {
    margin: 0 0 20px;
    padding-bottom: 10px;
    border-bottom: 1px solid #e5e5e5;
    font-size: 15px;
    color: #333;
}

.tb-details-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 15px;
}

.tb-detail-item {
    display: flex;
    align-items: center;
}

.tb-detail-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background-color: rgba(51, 153, 204, 0.1);
    color: #3399cc;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 15px;
}

.tb-detail-content {
    flex: 1;
    display: flex;
    flex-direction: column;
}

.tb-detail-label {
    font-size: 14px;
    color: #666;
    margin-bottom: 2px;
}

.tb-detail-value {
    font-size: 13px;
    font-weight: 600;
    color: #333;
}

.tb-status-confirmed,
.tb-payment-status-completed {
    color: #2ecc71;
}

.tb-status-pending,
.tb-payment-status-pending {
    color: #f39c12;
}

.tb-status-cancelled,
.tb-payment-status-refunded {
    color: #e74c3c;
}

.tb-payment-status-partially_refunded {
    color: #e67e22;
}

.tb-addons-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.tb-addon-item {
    display: flex;
    justify-content: space-between;
    padding: 10px 0;
    border-bottom: 1px solid #e5e5e5;
}

.tb-addon-item:last-child {
    border-bottom: none;
}

.tb-addon-name {
    font-weight: 600;
    color: #333;
}

.tb-addon-price {
    color: #3399cc;
}

.tb-confirmation-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    justify-content: center;
    margin-top: 30px;
}

.tb-button {
    display: inline-flex;
    align-items: center;
    padding: 12px 24px;
    border-radius: 4px;
    font-weight: 600;
    text-decoration: none;
    cursor: pointer;
    transition: all 0.3s ease;
}

.tb-button i {
    margin-right: 8px;
}

.tb-button-invoice {
    background-color: #3498db;
    color: #fff;
}

.tb-button-payment {
    background-color: #2ecc71;
    color: #fff;
}

.tb-button-secondary {
    background-color: #f1f1f1;
    color: #333;
}

.tb-button:hover {
    opacity: 0.9;
    transform: translateY(-1px);
}

/* Responsive styles */
@media screen and (max-width: 767px) {
    .tb-confirmation-container {
        padding: 20px 0px;
    }
    
    .tb-confirmation-header h2 {
        font-size: 18px;
    }
    
    .tb-confirmation-message {
        font-size: 16px;
    }
    
    .tb-details-grid {
        grid-template-columns: 1fr;
    }
    
    .tb-confirmation-court {
        flex-direction: column;
    }
    
    .tb-court-image {
        width: 100%;
        height: 150px;
    }
    
    .tb-button {
        width: 100%;
        justify-content: center;
    }
}

/* Print styles */
@media print {
    .tb-confirmation-actions {
        display: none;
    }
    
    .tb-confirmation-container {
        box-shadow: none;
        padding: 0;
    }
    
    body * {
        box-shadow: none !important;
    }
}
</style>