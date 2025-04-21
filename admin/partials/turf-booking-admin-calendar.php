<?php
/**
 * Admin calendar template for Turf Booking
 *
 * @package    Turf_Booking
 * @subpackage Turf_Booking/admin/partials
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Get general settings for formatting
$general_settings = get_option('tb_general_settings', array());
$date_format = isset($general_settings['date_format']) ? $general_settings['date_format'] : 'd/m/Y';
$time_format = isset($general_settings['time_format']) ? $general_settings['time_format'] : 'H:i';
$currency_symbol = isset($general_settings['currency_symbol']) ? $general_settings['currency_symbol'] : '₹';

// Get current date or the date from URL parameter
$current_date = isset($_GET['date']) ? sanitize_text_field($_GET['date']) : date('Y-m-d');

// Validate date format
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $current_date)) {
    $current_date = date('Y-m-d');
}

// Get courts for filtering
$courts = get_posts(array(
    'post_type' => 'tb_court',
    'posts_per_page' => -1,
    'orderby' => 'title',
    'order' => 'ASC',
));

// Get selected court from URL parameter
$selected_court = isset($_GET['court_id']) ? intval($_GET['court_id']) : 0;

// Get all bookings for the selected date
$bookings_args = array(
    'post_type' => 'tb_booking',
    'posts_per_page' => -1,
    'meta_query' => array(
        array(
            'key' => '_tb_booking_date',
            'value' => $current_date,
            'compare' => '=',
        ),
    ),
    'orderby' => 'meta_value',
    'meta_key' => '_tb_booking_time_from',
    'order' => 'ASC',
);

// Add court filter if selected
if ($selected_court > 0) {
    $bookings_args['meta_query'][] = array(
        'key' => '_tb_booking_court_id',
        'value' => $selected_court,
        'compare' => '=',
    );
}

$bookings = get_posts($bookings_args);

// Group bookings by court
$bookings_by_court = array();

foreach ($bookings as $booking) {
    $court_id = get_post_meta($booking->ID, '_tb_booking_court_id', true);
    
    if (!isset($bookings_by_court[$court_id])) {
        $bookings_by_court[$court_id] = array();
    }
    
    $bookings_by_court[$court_id][] = $booking;
}

// Get courts with bookings on this date
$courts_with_bookings = array_keys($bookings_by_court);

// Get all courts if no court filter is applied
if ($selected_court <= 0) {
    $displayed_courts = $courts;
} else {
    // Only show the selected court
    $displayed_courts = array();
    
    foreach ($courts as $court) {
        if ($court->ID == $selected_court) {
            $displayed_courts[] = $court;
            break;
        }
    }
}

// Calculate time slots for the calendar
$earliest_time = '06:00'; // Default earliest time
$latest_time = '22:00';   // Default latest time

// Find the actual earliest and latest times from court opening hours
foreach ($displayed_courts as $court) {
    $opening_hours = get_post_meta($court->ID, '_tb_court_opening_hours', true);
    $day_of_week = strtolower(date('l', strtotime($current_date)));
    
    if (isset($opening_hours[$day_of_week])) {
        $court_open = $opening_hours[$day_of_week]['from'];
        $court_close = $opening_hours[$day_of_week]['to'];
        
        if (strtotime($court_open) < strtotime($earliest_time)) {
            $earliest_time = $court_open;
        }
        
        if (strtotime($court_close) > strtotime($latest_time)) {
            $latest_time = $court_close;
        }
    }
}

// Generate time slots (30-minute intervals)
$time_slots = array();
$current_time = strtotime($earliest_time);
$end_time = strtotime($latest_time);

while ($current_time < $end_time) {
    $time_slots[] = date($time_format, $current_time);
    $current_time += 30 * 60; // Add 30 minutes
}

// Helper function to get booking cell span (in 30-minute intervals)
function get_booking_cell_span($from, $to) {
    $from_time = strtotime($from);
    $to_time = strtotime($to);
    $diff_minutes = ($to_time - $from_time) / 60;
    return max(1, floor($diff_minutes / 30));
}

// Helper function to get booking status class
function get_booking_status_class($status) {
    switch ($status) {
        case 'pending':
            return 'tb-status-pending';
        case 'confirmed':
            return 'tb-status-confirmed';
        case 'cancelled':
            return 'tb-status-cancelled';
        case 'completed':
            return 'tb-status-completed';
        default:
            return '';
    }
}

// Navigation links for previous/next day
$prev_date = date('Y-m-d', strtotime($current_date . ' -1 day'));
$next_date = date('Y-m-d', strtotime($current_date . ' +1 day'));

$prev_link = add_query_arg(array(
    'page' => 'turf-booking-calendar',
    'date' => $prev_date,
    'court_id' => $selected_court,
), admin_url('admin.php'));

$next_link = add_query_arg(array(
    'page' => 'turf-booking-calendar',
    'date' => $next_date,
    'court_id' => $selected_court,
), admin_url('admin.php'));

$today_link = add_query_arg(array(
    'page' => 'turf-booking-calendar',
    'date' => date('Y-m-d'),
    'court_id' => $selected_court,
), admin_url('admin.php'));

?>
<div class="wrap">
    <h1><?php _e('Booking Calendar', 'turf-booking'); ?></h1>
    
    <div class="tb-calendar-container">
        <div class="tb-calendar-header">
            <div class="tb-calendar-navigation">
                <a href="<?php echo esc_url($prev_link); ?>" class="button"><i class="dashicons dashicons-arrow-left-alt2"></i> <?php _e('Previous Day', 'turf-booking'); ?></a>
                <a href="<?php echo esc_url($today_link); ?>" class="button"><?php _e('Today', 'turf-booking'); ?></a>
                <a href="<?php echo esc_url($next_link); ?>" class="button"><?php _e('Next Day', 'turf-booking'); ?> <i class="dashicons dashicons-arrow-right-alt2"></i></a>
            </div>
            
            <div class="tb-calendar-date-picker">
                <form method="get" action="<?php echo admin_url('admin.php'); ?>">
                    <input type="hidden" name="page" value="turf-booking-calendar">
                    <input type="hidden" name="court_id" value="<?php echo esc_attr($selected_court); ?>">
                    <label for="calendar-date"><?php _e('Date:', 'turf-booking'); ?></label>
                    <input type="date" id="calendar-date" name="date" value="<?php echo esc_attr($current_date); ?>" onchange="this.form.submit()">
                </form>
            </div>
            
            <div class="tb-calendar-filter">
                <form method="get" action="<?php echo admin_url('admin.php'); ?>">
                    <input type="hidden" name="page" value="turf-booking-calendar">
                    <input type="hidden" name="date" value="<?php echo esc_attr($current_date); ?>">
                    <label for="court-filter"><?php _e('Filter by Court:', 'turf-booking'); ?></label>
                    <select id="court-filter" name="court_id" onchange="this.form.submit()">
                        <option value="0"><?php _e('All Courts', 'turf-booking'); ?></option>
                        <?php foreach ($courts as $court) : ?>
                            <option value="<?php echo esc_attr($court->ID); ?>" <?php selected($selected_court, $court->ID); ?>><?php echo esc_html($court->post_title); ?></option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </div>

            <div class="tb-manual-booking">
                <button id="tb-add-manual-booking" class="button button-primary">
                    <i class="dashicons dashicons-plus"></i> <?php _e('Add Manual Booking', 'turf-booking'); ?>
                </button>
            </div>

        </div>
        
        <div class="tb-calendar-main">
            <div class="tb-current-date">
                <h2><?php echo date('l, F j, Y', strtotime($current_date)); ?></h2>
            </div>
            
            <?php if (empty($displayed_courts)) : ?>
                <div class="tb-no-courts">
                    <p><?php _e('No courts available.', 'turf-booking'); ?></p>
                </div>
            <?php else : ?>
                <div class="tb-calendar-grid">
                    <table class="tb-calendar-table">
                        <thead>
                            <tr>
                                <th class="tb-time-header"><?php _e('Time', 'turf-booking'); ?></th>
                                <?php foreach ($displayed_courts as $court) : ?>
                                    <th class="tb-court-header"><?php echo esc_html($court->post_title); ?></th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($time_slots as $i => $time_slot) : ?>
                                <tr class="tb-time-row <?php echo ($i % 2 == 0) ? 'tb-even-row' : 'tb-odd-row'; ?>">
                                    <td class="tb-time-cell"><?php echo esc_html($time_slot); ?></td>
                                    
                                    <?php foreach ($displayed_courts as $court) : 
                                        $court_id = $court->ID;
                                        $has_booking = false;
                                        
                                        // Check if there's a booking for this court and time slot
                                        if (isset($bookings_by_court[$court_id])) {
                                            foreach ($bookings_by_court[$court_id] as $booking) {
                                                $booking_time_from = get_post_meta($booking->ID, '_tb_booking_time_from', true);
                                                $booking_time_to = get_post_meta($booking->ID, '_tb_booking_time_to', true);
                                                $booking_status = get_post_meta($booking->ID, '_tb_booking_status', true);
                                                
                                                // Check if booking starts at this time slot
                                                if (date($time_format, strtotime($booking_time_from)) == $time_slot) {
                                                    $has_booking = true;
                                                    $cell_span = get_booking_cell_span($booking_time_from, $booking_time_to);
                                                    $status_class = get_booking_status_class($booking_status);
                                                    
                                                    $user_name = get_post_meta($booking->ID, '_tb_booking_user_name', true);
                                                    $user_phone = get_post_meta($booking->ID, '_tb_booking_user_phone', true);
                                                    ?>
                                                    <td class="tb-booking-cell <?php echo esc_attr($status_class); ?>" rowspan="<?php echo esc_attr($cell_span); ?>">
                                                        <div class="tb-booking-info">
                                                            <span class="tb-booking-time"><?php echo esc_html(date($time_format, strtotime($booking_time_from)) . ' - ' . date($time_format, strtotime($booking_time_to))); ?></span>
                                                            <span class="tb-booking-name"><?php echo esc_html($user_name); ?></span>
                                                            <span class="tb-booking-phone"><?php echo esc_html($user_phone); ?></span>
                                                            <span class="tb-booking-status"><?php echo esc_html(ucfirst($booking_status)); ?></span>
                                                        </div>
                                                        <div class="tb-booking-actions">
                                                            <a href="<?php echo admin_url('post.php?post=' . $booking->ID . '&action=edit'); ?>" class="button-link" title="<?php esc_attr_e('Edit Booking', 'turf-booking'); ?>"><i class="dashicons dashicons-edit"></i></a>
                                                            <a href="#" class="button-link tb-view-booking" data-booking-id="<?php echo esc_attr($booking->ID); ?>" title="<?php esc_attr_e('View Details', 'turf-booking'); ?>"><i class="dashicons dashicons-visibility"></i></a>
                                                        </div>
                                                    </td>
                                                    <?php
                                                    break;
                                                }
                                                
                                                // Check if booking spans this time slot
                                                $current_time = strtotime($time_slot);
                                                $booking_from = strtotime($booking_time_from);
                                                $booking_to = strtotime($booking_time_to);
                                                
                                                if ($current_time > $booking_from && $current_time < $booking_to) {
                                                    $has_booking = true;
                                                    // Don't output anything for spanned cells
                                                    break;
                                                }
                                            }
                                        }
                                        
                                        // If no booking for this time slot, show empty cell
                                        if (!$has_booking) {
                                            echo '<td class="tb-empty-cell"></td>';
                                        }
                                    ?>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="tb-calendar-legend">
            <h3><?php _e('Legend', 'turf-booking'); ?></h3>
            <ul class="tb-legend-list">
                <li><span class="tb-legend-item tb-status-pending"></span> <?php _e('Pending', 'turf-booking'); ?></li>
                <li><span class="tb-legend-item tb-status-confirmed"></span> <?php _e('Confirmed', 'turf-booking'); ?></li>
                <li><span class="tb-legend-item tb-status-completed"></span> <?php _e('Completed', 'turf-booking'); ?></li>
                <li><span class="tb-legend-item tb-status-cancelled"></span> <?php _e('Cancelled', 'turf-booking'); ?></li>
            </ul>
        </div>
    </div>
    
    <!-- Booking Details Modal -->
    <div id="tb-booking-modal" class="tb-modal">
        <div class="tb-modal-content">
            <span class="tb-modal-close">&times;</span>
            <h2><?php _e('Booking Details', 'turf-booking'); ?></h2>
            <div id="tb-booking-details-content"></div>
        </div>
    </div>


    <!-- Manual Booking Modal -->
<div id="tb-manual-booking-modal" class="tb-modal">
    <div class="tb-modal-content">
        <span class="tb-modal-close">&times;</span>
        <h2><?php _e('Add Manual Booking', 'turf-booking'); ?></h2>
        <form id="tb-manual-booking-form">
            <div class="tb-form-row">
                <label for="tb-booking-court"><?php _e('Court', 'turf-booking'); ?></label>
                <select id="tb-booking-court" name="court_id" required>
                    <option value=""><?php _e('Select a court', 'turf-booking'); ?></option>
                    <?php foreach ($courts as $court) : ?>
                        <option value="<?php echo esc_attr($court->ID); ?>"><?php echo esc_html($court->post_title); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="tb-form-row">
                <label for="tb-booking-date"><?php _e('Date', 'turf-booking'); ?></label>
                <input type="date" id="tb-booking-date" name="date" value="<?php echo esc_attr($current_date); ?>" required>
            </div>
            
            <div class="tb-form-row">
                <label for="tb-booking-time-from"><?php _e('Time From', 'turf-booking'); ?></label>
                <input type="time" id="tb-booking-time-from" name="time_from" required>
            </div>
            
            <div class="tb-form-row">
                <label for="tb-booking-time-to"><?php _e('Time To', 'turf-booking'); ?></label>
                <input type="time" id="tb-booking-time-to" name="time_to" required>
            </div>
            
            <div class="tb-form-row">
                <label for="tb-booking-customer-name"><?php _e('Customer Name', 'turf-booking'); ?></label>
                <input type="text" id="tb-booking-customer-name" name="name" required>
            </div>
            
            <div class="tb-form-row">
                <label for="tb-booking-customer-email"><?php _e('Customer Email', 'turf-booking'); ?></label>
                <input type="email" id="tb-booking-customer-email" name="email" required>
            </div>
            
            <div class="tb-form-row">
                <label for="tb-booking-customer-phone"><?php _e('Customer Phone', 'turf-booking'); ?></label>
                <input type="text" id="tb-booking-customer-phone" name="phone" required>
            </div>
            
            <div class="tb-form-row">
                <label for="tb-booking-status"><?php _e('Booking Status', 'turf-booking'); ?></label>
                <select id="tb-booking-status" name="status">
                    <option value="confirmed"><?php _e('Confirmed', 'turf-booking'); ?></option>
                    <option value="pending"><?php _e('Pending', 'turf-booking'); ?></option>
                    <option value="completed"><?php _e('Completed', 'turf-booking'); ?></option>
                </select>
            </div>
            
            <div class="tb-form-row">
                <label for="tb-payment-status"><?php _e('Payment Status', 'turf-booking'); ?></label>
                <select id="tb-payment-status" name="payment_status">
                    <option value="completed"><?php _e('Completed', 'turf-booking'); ?></option>
                    <option value="pending"><?php _e('Pending', 'turf-booking'); ?></option>
                </select>
            </div>
            
            <div class="tb-form-row">
                <label for="tb-payment-amount"><?php _e('Payment Amount', 'turf-booking'); ?></label>
                <div class="tb-amount-input">
                    <span class="tb-currency-symbol"><?php echo esc_html($currency_symbol); ?></span>
                    <input type="number" id="tb-payment-amount" name="amount" step="0.01" required>
                </div>
            </div>
            
            <div class="tb-form-row">
                <label for="tb-payment-method"><?php _e('Payment Method', 'turf-booking'); ?></label>
                <select id="tb-payment-method" name="payment_method">
                    <option value="cash"><?php _e('Cash', 'turf-booking'); ?></option>
                    <option value="bank_transfer"><?php _e('Bank Transfer', 'turf-booking'); ?></option>
                    <option value="card"><?php _e('Card', 'turf-booking'); ?></option>
                    <option value="other"><?php _e('Other', 'turf-booking'); ?></option>
                </select>
            </div>
            
            <div class="tb-form-row tb-form-actions">
                <button type="submit" class="button button-primary"><?php _e('Create Booking', 'turf-booking'); ?></button>
                <button type="button" class="button tb-cancel-manual-booking"><?php _e('Cancel', 'turf-booking'); ?></button>
            </div>
        </form>
    </div>
</div>





</div>

<script>
jQuery(document).ready(function($) {
    // Initialize datepicker
    if ($.datepicker) {
        $('#calendar-date').datepicker({
            dateFormat: 'yy-mm-dd',
            onSelect: function(dateText) {
                $(this).closest('form').submit();
            }
        });
    }
    
    // View booking details
    $('.tb-view-booking').on('click', function(e) {
        e.preventDefault();
        
        var bookingId = $(this).data('booking-id');
        
        // Show loading
        $('#tb-booking-details-content').html('<p><?php _e('Loading booking details...', 'turf-booking'); ?></p>');
        $('#tb-booking-modal').show();
        
        // Load booking details via AJAX
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'tb_get_booking_details',
                booking_id: bookingId,
                nonce: '<?php echo wp_create_nonce('tb_admin_nonce'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    $('#tb-booking-details-content').html(response.data.html);
                } else {
                    $('#tb-booking-details-content').html('<p class="tb-error">' + response.data.message + '</p>');
                }
            },
            error: function() {
                $('#tb-booking-details-content').html('<p class="tb-error"><?php _e('Error loading booking details. Please try again.', 'turf-booking'); ?></p>');
            }
        });
    });
    
    // Close modal
    $('.tb-modal-close').on('click', function() {
        $('#tb-booking-modal').hide();
    });
    
    // Close modal when clicking outside
    $(window).on('click', function(e) {
        if ($(e.target).is('#tb-booking-modal')) {
            $('#tb-booking-modal').hide();
        }
    });



    // Manual booking functionality
$('#tb-add-manual-booking').on('click', function() {
    $('#tb-manual-booking-modal').show();
});

$('.tb-cancel-manual-booking, .tb-modal-close').on('click', function() {
    $('#tb-manual-booking-modal').hide();
});

// Auto calculate price when court and time is selected
$('#tb-booking-court, #tb-booking-time-from, #tb-booking-time-to').on('change', function() {
    calculateBookingAmount();
});

// Calculate booking amount
function calculateBookingAmount() {
    var courtId = $('#tb-booking-court').val();
    var date = $('#tb-booking-date').val();
    var timeFrom = $('#tb-booking-time-from').val();
    var timeTo = $('#tb-booking-time-to').val();
    
    if (!courtId || !date || !timeFrom || !timeTo) {
        return;
    }
    
    // Show loading
    showLoading();
    
    // Get court price details
    $.ajax({
        url: ajaxurl,
        type: 'POST',
        data: {
            action: 'tb_get_court_price',
            court_id: courtId,
            date: date,
            time_from: timeFrom,
            time_to: timeTo,
            nonce: '<?php echo wp_create_nonce('tb_admin_nonce'); ?>'
        },
        success: function(response) {
            hideLoading();
            if (response.success) {
                $('#tb-payment-amount').val(response.data.amount);
            }
        },
        error: function() {
            hideLoading();
        }
    });
}

// Handle manual booking form submission
$('#tb-manual-booking-form').on('submit', function(e) {
    e.preventDefault();
    
    // Show loading
    showLoading();
    
    // Get form data
    var formData = $(this).serialize();
    
    // Submit form via AJAX
    $.ajax({
        url: ajaxurl,
        type: 'POST',
        data: {
            action: 'tb_create_manual_booking',
            form_data: formData,
            nonce: '<?php echo wp_create_nonce('tb_admin_nonce'); ?>'
        },
        success: function(response) {
            hideLoading();
            if (response.success) {
                // Close modal
                $('#tb-manual-booking-modal').hide();
                
                // Show success message
                alert(response.data.message);
                
                // Reload page to reflect changes
                window.location.reload();
            } else {
                alert(response.data.message);
            }
        },
        error: function() {
            hideLoading();
            alert('<?php _e('An error occurred. Please try again.', 'turf-booking'); ?>');
        }
    });
});

// Loading functions
function showLoading() {
    $('body').append('<div class="tb-loading-overlay"><div class="tb-loading-spinner"></div></div>');
}

function hideLoading() {
    $('.tb-loading-overlay').remove();
}



});
</script>

<style>
    .tb-calendar-container {
        background: #fff;
        border: 1px solid #ddd;
        border-radius: 4px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        margin-top: 20px;
        padding: 20px;
    }
    
    .tb-calendar-header {
        display: flex;
        flex-wrap: wrap;
        gap: 15px;
        justify-content: space-between;
        margin-bottom: 20px;
    }
    
    .tb-calendar-navigation {
        display: flex;
        gap: 10px;
    }
    
    .tb-calendar-date-picker,
    .tb-calendar-filter {
        display: flex;
        align-items: center;
    }
    
    .tb-calendar-date-picker label,
    .tb-calendar-filter label {
        margin-right: 10px;
    }
    
    .tb-current-date {
        margin-bottom: 20px;
    }
    
    .tb-current-date h2 {
        font-size: 22px;
        margin: 0;
    }
    
    .tb-calendar-grid {
        overflow-x: auto;
    }
    
    .tb-calendar-table {
        border-collapse: collapse;
        min-width: 100%;
        table-layout: fixed;
    }
    
    .tb-calendar-table th,
    .tb-calendar-table td {
        border: 1px solid #ddd;
        padding: 8px;
        text-align: center;
        vertical-align: top;
    }
    
    .tb-time-header {
        width: 100px;
    }
    
    .tb-court-header {
        min-width: 200px;
    }
    
    .tb-time-cell {
        background: #f9f9f9;
        font-weight: bold;
    }
    
    .tb-even-row {
        background: #f9f9f9;
    }
    
    .tb-empty-cell {
        background: #fff;
    }
    
    .tb-booking-cell {
        background: #f0f0f1;
        position: relative;
    }
    
    .tb-booking-info {
        display: flex;
        flex-direction: column;
        font-size: 13px;
        gap: 4px;
    }
    
    .tb-booking-time {
        font-weight: bold;
    }
    
    .tb-booking-status {
        font-weight: bold;
    }
    
    .tb-booking-actions {
        display: flex;
        gap: 10px;
        justify-content: center;
        margin-top: 8px;
    }
    
    .tb-status-pending {
        background: #f0d669;
    }
    
    .tb-status-confirmed {
        background: #a8dba8;
    }
    
    .tb-status-completed {
        background: #79bd9a;
    }
    
    .tb-status-cancelled {
        background: #f5a3a3;
    }
    
    .tb-calendar-legend {
        margin-top: 20px;
    }
    
    .tb-calendar-legend h3 {
        font-size: 16px;
        margin: 0 0 10px;
    }
    
    .tb-legend-list {
        display: flex;
        flex-wrap: wrap;
        gap: 20px;
        list-style: none;
        margin: 0;
        padding: 0;
    }
    
    .tb-legend-item {
        display: inline-block;
        height: 16px;
        margin-right: 6px;
        vertical-align: middle;
        width: 16px;
    }
    
    .tb-no-courts {
        background: #f9f9f9;
        border: 1px solid #ddd;
        border-radius: 4px;
        padding: 20px;
        text-align: center;
    }
    
    /* Modal styles */
    .tb-modal {
        background-color: rgba(0,0,0,0.4);
        display: none;
        height: 100%;
        left: 0;
        overflow: auto;
        position: fixed;
        top: 0;
        width: 100%;
        z-index: 999;
    }
    
    .tb-modal-content {
        background-color: #fff;
        border-radius: 4px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.5);
        margin: 10% auto;
        max-width: 600px;
        padding: 20px;
        position: relative;
        width: 80%;
    }
    
    .tb-modal-close {
        color: #aaa;
        cursor: pointer;
        float: right;
        font-size: 28px;
        font-weight: bold;
    }
    
    .tb-modal-close:hover {
        color: #000;
    }
    
    @media (max-width: 782px) {
        .tb-calendar-header {
            flex-direction: column;
        }
        
        .tb-calendar-navigation,
        .tb-calendar-date-picker,
        .tb-calendar-filter {
            width: 100%;
        }
    }

    /* Manual booking button */
.tb-manual-booking {
    display: flex;
    align-items: center;
}

/* Manual booking modal */
.tb-modal {
    background-color: rgba(0,0,0,0.4);
    display: none;
    height: 100%;
    left: 0;
    overflow: auto;
    position: fixed;
    top: 0;
    width: 100%;
    z-index: 999;
}

.tb-modal-content {
    background-color: #fff;
    border-radius: 4px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.5);
    margin: 5% auto;
    max-width: 600px;
    padding: 20px;
    position: relative;
    width: 80%;
}

.tb-modal-close {
    color: #aaa;
    cursor: pointer;
    float: right;
    font-size: 28px;
    font-weight: bold;
}

.tb-modal-close:hover {
    color: #000;
}

.tb-form-row {
    margin-bottom: 15px;
}

.tb-form-row label {
    display: block;
    font-weight: bold;
    margin-bottom: 5px;
}

.tb-form-row input,
.tb-form-row select {
    width: 100%;
    padding: 8px;
}

.tb-amount-input {
    position: relative;
}

.tb-currency-symbol {
    position: absolute;
    left: 10px;
    top: 9px;
    color: #666;
}

.tb-amount-input input {
    padding-left: 25px;
}

.tb-form-actions {
    display: flex;
    gap: 10px;
    justify-content: flex-end;
}

/* Loading overlay */
.tb-loading-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(255, 255, 255, 0.7);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 1000;
}

.tb-loading-spinner {
    border: 4px solid #f3f3f3;
    border-top: 4px solid #3498db;
    border-radius: 50%;
    width: 50px;
    height: 50px;
    animation: spin 2s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
</style>